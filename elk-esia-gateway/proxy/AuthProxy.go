package proxy

import (
	"context"
	"fmt"
	"net/http"
	"net/url"
	"time"

	"github.com/gin-gonic/gin"
)

// TODO : логирование

// AuthRequest представляет валидацию для GET /aas/oauth2/ac
type AuthRequest struct {
	TimestampRequest
	ClientID     string `form:"client_id" json:"client_id" binding:"required"`
	Scope        string `form:"scope" json:"scope" binding:"required"`
	ResponseType string `form:"response_type" json:"response_type" binding:"required,eq=code"`
	RedirectURI  string `form:"redirect_uri" json:"redirect_uri" binding:"required,url"`
	State        string `form:"state" json:"state" binding:"required"`
	ClientSecret string `form:"client_secret" json:"client_secret" binding:"required"`
}

// HandleAuthProxy обрабатывает GET /aas/oauth2/ac (v1 -> v2/ac)
// @Summary      Трансляция авторизации (v1 -> v2/ac)
// @Description  Принимает GET-запрос спецификации v1, проверяет параметры, генерирует МСК метку времени, строит сырую ГОСТ подпись с реверсом байт и перенаправляет браузер в контур ЕСИА v2.
// @Tags         OAuth2 Proxy
// @Accept       json
// @Produce      json
// @Param        client_id     query    string  true   "Мнемоника ИС"
// @Param        scope         query    string  true   "Области доступа"
// @Param        response_type query    string  true   "Тип ответа (должен быть code)"
// @Param        redirect_uri  query    string  true   "URL возврата"
// @Param        state         query    string  true   "Идентификатор сессии"
// @Param        client_secret formData string  true   "Отсоединенная подпись PKCS#7 от старой ИС"
// @Param        timestamp     query    string  false  "Метка времени v1"
// @Success      302           {string} string  "Перенаправление на официальный URL ЕСИА v2/ac"
// @Failure      400           {object} map[string]interface{} "Ошибка валидации параметров"
// @Failure      500           {object} map[string]interface{} "Ошибка криптографии"
// @Router       /aas/oauth2/ac [get]
func (s *EsiaServer) HandleAuthProxy(c *gin.Context) {
	var req AuthRequest
	// Автоматическая привязка и строгая валидация URL Query параметров
	if err := c.ShouldBindQuery(&req); err != nil {
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

	// 2. Инициализируем/валидируем timestamp.
	// Если его не было — req.Timestamp заполнится текущим временем.
	if err := req.ValidateTimestamp(); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid_timestamp", "details": err.Error()})
		return
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	// 3. Вызываем метод SignRaw для сырой reversed-подписи (v2)
	clientSecret, err := s.CryptoProvider.SignRaw(ctx, req.ForV2Signature())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "crypto_failed", "details": err.Error()})
		return
	}
	// Переопределяем client_secret на выходе значения подписью версии v2 перед отправкой
	req.ClientSecret = clientSecret

	// 4. Построение целевого URL перенаправления в ЕСИА v2/ac на основе единого метода маршалинга
	targetURL := fmt.Sprintf("https://%s/aas/oauth2/v2/ac?%s", s.EsiaTargetHost, req.ToQueryParams().Encode())

	c.Redirect(http.StatusFound, targetURL)
}

// ForV1Signature Собирает 4 параметра БЕЗ разделителей для ЕСИА v1/v3
// Порядок строго по МР ЕСИА: scope + timestamp + client_id + state
// TODO : возможно рефакторинг с TokenProxy
func (req *AuthRequest) ForV1Signature() string {
	return req.Scope + req.Timestamp + req.ClientID + req.State
}

// ForV2Signature Собирает 5 параметров БЕЗ разделителей для ЕСИА v2/ac
// Порядок строго по МР ЕСИА: client_id + scope + timestamp + state + redirect_uri
func (req *AuthRequest) ForV2Signature() string {
	return req.ClientID + req.Scope + req.Timestamp + req.State + req.RedirectURI
}

// ToQueryParams сериализует все текущие поля структуры в url.Values
func (req *AuthRequest) ToQueryParams() url.Values {
	q := url.Values{}
	q.Set("client_id", req.ClientID)
	q.Set("scope", req.Scope)
	q.Set("response_type", req.ResponseType)
	q.Set("redirect_uri", req.RedirectURI)
	q.Set("state", req.State)
	q.Set("timestamp", req.Timestamp)
	q.Set("client_secret", req.ClientSecret)
	return q
}
