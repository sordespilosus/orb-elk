package crypto

import (
	"context"
)

// CryptoProProvider КриптоПРО (Заглушка)
type CryptoProProvider struct {
	Provider
	URL string
}

// SignRaw реализует логику подписи для ЕСИА v2/ac (Сырая ГОСТ-подпись с разворотом байт)
func (p *CryptoProProvider) SignRaw(ctx context.Context, data string) (string, error) {
	// TODO : В реальной интеграции выполняется запрос к ПО "КриптоПРО"
	return "MII_Mock_Signature_From_CryptoPro...", nil
}

// SignPKCS7 реализует классическую detached PKCS#7 подпись для ЕСИА v3/te и верификации v1 ИС
func (p *CryptoProProvider) SignPKCS7(ctx context.Context, data string) (string, error) {
	// TODO : В реальной интеграции выполняется запрос к ПО "КриптоПРО"
	return "MII_Mock_Signature_From_CryptoPro...", nil
}
