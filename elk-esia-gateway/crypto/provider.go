package crypto

import "context"

// Provider определяет обязательный контракт для всех крипто-движков шлюза
type Provider interface {
	// SignRaw ГОСТ Р 34.10-2012 вычисляет «сырую» (raw) подпись,
	// разворачивает байты в обратном порядке (reversed) и кодирует в Base64 RawURL.
	// Используется для эндпоинта ЕСИА /aas/oauth2/v2/ac.
	SignRaw(ctx context.Context, data string) (string, error)

	// SignPKCS7 формирует отсоединенную (detached) PKCS#7 / CMS подпись в DER-формате,
	// закодированную в Base64 RawURL.
	// Используется для проверки входящих запросов ИС v1 и отправки в ЕСИА /aas/oauth2/v3/te.
	SignPKCS7(ctx context.Context, data string) (string, error)

	// VerifyPKCS7 математически проверяет отсоединенную подпись PKCS#7 (Base64 URL-safe)
	// на основе переданных сырых данных и публичного сертификата, загруженного в провайдера.
	VerifyPKCS7(ctx context.Context, data string, base64URLSig string) (bool, error)
}
