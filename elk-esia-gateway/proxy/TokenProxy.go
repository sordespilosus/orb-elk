package proxy

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"time"

	"github.com/gin-gonic/gin"
)

// TODO : логирование

// TokenRequest представляет валидацию для POST /aas/oauth2/te
type TokenRequest struct {
	TimestampRequest
	GrantType    string `form:"grant_type" json:"grant_type" binding:"required,oneof=authorization_code refresh_token"`
	ClientID     string `form:"client_id" json:"client_id" binding:"required"`
	Code         string `form:"code" json:"code" binding:"required"`
	RedirectURI  string `form:"redirect_uri" json:"redirect_uri" binding:"required,url"`
	ClientSecret string `form:"client_secret" json:"client_secret" binding:"required"` // v1 PKCS#7 подпись
	State        string `form:"state" json:"state" binding:"required"`
	Scope        string `form:"scope" json:"scope"` // Опционально
}

// HandleTokenProxy обрабатывает POST /aas/oauth2/te (Трансляция запроса обмена кода из v1 в v3/te)
// @Summary      Трансляция обмена кода на токен (v1 -> v3/te)
// @Description  Перехватывает POST-формат v1, производит входящий контроль подписи ИС методом сверки сигнатур. В случае успеха отбрасывает её, формирует новую метку времени и отсоединенную PKCS#7 подпись по спецификации v3, запрашивает токены у Госуслуг и возвращает JWT обратно.
// @Tags         OAuth2 Proxy
// @Accept       x-www-form-urlencoded
// @Produce      json
// @Param        grant_type    formData string  true   "Тип grant (authorization_code или refresh_token)"
// @Param        client_id     formData string  true   "Мнемоника ИС"
// @Param        code          formData string  true   "Код авторизации"
// @Param        redirect_uri  formData string  true   "URL возврата"
// @Param        client_secret formData string  true   "Отсоединенная подпись PKCS#7 от старой ИС"
// @Param        state         formData string  true   "Идентификатор сессии"
// @Param        timestamp     formData string  true   "Метка времени подписи v1"
// @Param        scope         formData string  false  "Области доступа"
// @Success      200           {object} map[string]interface{} "JSON-структура с токенами от ЕСИА"
// @Failure      400           {object} map[string]interface{} "Невалидные параметры или ошибка проверки подписи"
// @Failure      500           {object} map[string]interface{} "Внутренний сбой крипто-движка шлюза"
// @Router       /aas/oauth2/te [post]
func (s *EsiaServer) HandleTokenProxy(c *gin.Context) {
	var req TokenRequest
	// Автоматическая привязка и валидация x-www-form-urlencoded тела POST-запроса
	if err := c.ShouldBind(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "validation_failed", "details": err.Error()})
		return
	}

	if req.ClientID != s.ClientID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid_client_id"})
		return
	}

	// 1. Проверяем подпись, пришедшую от старой ИС
	isValid, verifyErr := s.CryptoProvider.VerifyPKCS7(c.Request.Context(), req.ForV1Signature(), req.ClientSecret)

	if !isValid || verifyErr != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"error":             "invalid_grant",
			"error_description": "Ошибка математической валидации PKCS#7 подписи приложения (неверный ключ или данные)",
			"details":           verifyErr.Error(),
		})
		return
	}

	// ПОДГОТОВКА НОВОГО ЗАПРОСА ДЛЯ ЕСИА v3
	req.Validate()

	// 2. Инициализируем/валидируем timestamp.
	// Если его не было — req.Timestamp заполнится текущим временем.
	if err := req.ValidateTimestamp(); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid_timestamp", "details": err.Error()})
		return
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	// 3. Генерируем подпись для отправки в ЕСИА v3/te (Классический detached PKCS#7 из 4 параметров)
	clientSecret, err := s.CryptoProvider.SignPKCS7(ctx, req.ForV1Signature())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":   "crypto_failed",
			"details": "Ошибка подписания исходящего пакета для ЕСИА v3: " + err.Error(),
		})
		return
	}
	req.ClientSecret = clientSecret

	// СБОРКА И ПЕРЕДАЧА PAYLOAD В ЕСИА v3/te

	// 4. Отправляем сформированный POST-запрос напрямую в целевой контур ЕСИА
	esiaTokenEndpoint := fmt.Sprintf("https://%s/aas/oauth2/v3/te", s.EsiaTargetHost)
	esiaResp, err := http.PostForm(esiaTokenEndpoint, req.ToPostForm())
	if err != nil {
		c.JSON(http.StatusBadGateway, gin.H{"error": "esia_unreachable", "details": err.Error()})
		return
	}
	defer esiaResp.Body.Close()

	// ТРАНСЛЯЦИЯ ОТВЕТА ОБРАТНО В LEGACY-СИСТЕМУ

	// 5. Полностью копируем заголовки и JSON-ответ (содержащий JWT access_token и id_token) обратно клиенту
	c.Header("Content-Type", esiaResp.Header.Get("Content-Type"))
	c.Status(esiaResp.StatusCode)
	io.Copy(c.Writer, esiaResp.Body)
}

// ForV1Signature Собирает 4 параметра БЕЗ разделителей для ЕСИА v1/v3
// Порядок строго по МР ЕСИА: scope + timestamp + client_id + state
func (req *TokenRequest) ForV1Signature() string {
	return req.Scope + req.Timestamp + req.ClientID + req.State
}

func (req *TokenRequest) Validate() {
	if req.Scope == "" {
		req.Scope = "fullname birthdate" // TODO: Дефолтный набор прав, если параметр пуст
	}
}

// ToPostForm сериализует параметры в формат url.Values для отправки в теле POST-запроса (x-www-form-urlencoded)
func (req *TokenRequest) ToPostForm() url.Values {
	form := url.Values{}
	form.Set("client_id", req.ClientID)
	form.Set("code", req.Code)
	form.Set("grant_type", req.GrantType)
	form.Set("redirect_uri", req.RedirectURI)
	form.Set("state", req.State)
	form.Set("scope", req.Scope)
	form.Set("timestamp", req.Timestamp)
	form.Set("client_secret", req.ClientSecret)
	return form
}
