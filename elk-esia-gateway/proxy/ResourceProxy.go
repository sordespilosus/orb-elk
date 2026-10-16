package proxy

import (
	"fmt"
	"io"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
)

// TODO : логирование

// HandleResourceProxy реализует прозрачный Reverse Proxy для GET /rs/prns/* и /rs/orgs/*
func (s *EsiaServer) HandleResourceProxy(c *gin.Context) {
	// Проксирование запросов защищенных ресурсов напрямую в ЕСИА без изменений
	targetURL := fmt.Sprintf("https://%s%s", s.EsiaTargetHost, c.Request.URL.Path)
	if c.Request.URL.RawQuery != "" {
		targetURL += "?" + c.Request.URL.RawQuery
	}

	// TODO : В ЕСИА v1 профиль пользователя мог отдаваться одним ответом.
	// В v3 данные разбиты на отдельные эндпоинты (`/ctc` — контакты, `/elements` — документы и т.д.).
	// В прокси потребуется реализовать паттерн **API Gateway / Aggregator**, который выполнит параллельные запросы
	// к ЕСИА v3, соберет сущности и склеит их в старый формат, ожидаемый вашей системой.

	req, err := http.NewRequest(c.Request.Method, targetURL, c.Request.Body)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "proxy_error"})
		return
	}

	// Копируем заголовки авторизации (Bearer Token)
	for reqName, reqValues := range c.Request.Header {
		for _, reqValue := range reqValues {
			req.Header.Add(reqName, reqValue)
		}
	}

	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		c.JSON(http.StatusBadGateway, gin.H{"error": "esia_resource_unreachable"})
		return
	}
	defer resp.Body.Close()

	c.Header("Content-Type", resp.Header.Get("Content-Type"))
	c.Status(resp.StatusCode)
	io.Copy(c.Writer, resp.Body)
}
