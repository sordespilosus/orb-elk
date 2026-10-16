package proxy

import (
	"fmt"
	"net/http"
	"net/url"

	"github.com/gin-gonic/gin"
)

// TODO : логирование

// LogoutRequest представляет валидацию для GET /idp/ext/Logout
type LogoutRequest struct {
	ClientID    string `form:"client_id" json:"client_id" binding:"required"`
	RedirectURL string `form:"redirect_url" json:"redirect_url" binding:"required,url"`
}

// HandleLogoutProxy обрабатывает GET /idp/ext/Logout (v1 -> v2/v3 Logout)
// @Summary      Трансляция завершения сессии (v1 -> v2/v3 Logout)
// @Description  Перенаправляет GET-выход старой системы на новый безопасный Logout Госуслуг.
// @Tags         OAuth2 Proxy
// @Accept       json
// @Produce      json
// @Param        client_id    query    string  true   "Мнемоника ИС"
// @Param        redirect_url query    string  true   "URL возврата после выхода"
// @Success      302          {string} string  "Перенаправление на официальный эндпоинт выхода ЕСИА"
// @Failure      400          {object} map[string]interface{} "Невалидные параметры"
// @Router       /idp/ext/Logout [get]
func (s *EsiaServer) HandleLogoutProxy(c *gin.Context) {
	var req LogoutRequest
	if err := c.ShouldBindQuery(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "validation_failed", "details": err.Error()})
		return
	}

	if req.ClientID != s.ClientID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid_client_id"})
		return
	}

	// Построение целевого URL перенаправления по спецификации Б.6.7 (БЕЗ подписи, меток времени и state)
	targetURL := fmt.Sprintf("https://%s/aas/oauth2/api/logout?%s", s.EsiaTargetHost, req.ToQueryParams().Encode())

	// Делаем прозрачный 302 редирект для браузера пользователя
	c.Redirect(http.StatusFound, targetURL)
}

// ToQueryParams сериализует параметры Logout в url.Values для формирования URL GET-запроса
func (req *LogoutRequest) ToQueryParams() url.Values {
	q := url.Values{}
	q.Set("client_id", req.ClientID)
	q.Set("redirect_url", req.RedirectURL)
	return q
}
