package crypto

import (
	"context"
	"net/http"
)

// AdapterInfraProvider для Адаптер-Инфраструктура (Внешний шлюз)
type AdapterInfraProvider struct {
	Provider
	URL    string
	KeyID  string
	Client *http.Client
}

type AdapterSignRequest struct {
	KeyID      string `json:"key_id"`
	DataToSign string `json:"data_to_sign"`
}

type AdapterSignResponse struct {
	SignatureBase64UrlSafe string `json:"signature_base64_urlsafe"`
}

// SignRaw реализует логику подписи для ЕСИА v2/ac (Сырая ГОСТ-подпись с разворотом байт)
func (p *AdapterInfraProvider) SignRaw(ctx context.Context, data string) (string, error) {
	// TODO : В реальной интеграции выполняется gRPC или REST-запрос к ПО "Адаптер Инфраструктура"
	// Пример REST реализации:
	// ... логика маршалинга AdapterSignRequest и выполнения POST к p.URL ...
	return "MII_Mock_Signature_From_Adapter_Infra...", nil
}

// SignPKCS7 реализует классическую detached PKCS#7 подпись для ЕСИА v3/te и верификации v1 ИС
func (p *AdapterInfraProvider) SignPKCS7(ctx context.Context, data string) (string, error) {
	// TODO : В реальной интеграции выполняется gRPC или REST-запрос к ПО "Адаптер Инфраструктура"
	// Пример REST реализации:
	// ... логика маршалинга AdapterSignRequest и выполнения POST к p.URL ...
	return "MII_Mock_Signature_From_Adapter_Infra...", nil
}
