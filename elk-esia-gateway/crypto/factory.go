package crypto

import (
	"fmt"
	"net/http"
	"os"
	"time"
)

// Factory управляет созданием экземпляров провайдеров
type Factory struct {
	cryptoSystem string
}

func NewFactory(system string) *Factory {
	if system == "" {
		system = "open-ssl-gost" // Значение по умолчанию
	}
	return &Factory{cryptoSystem: system}
}

// CreateProvider инстанцирует необходимую обертку на основе конфигурации
func (f *Factory) CreateProvider() (Provider, error) {
	switch f.cryptoSystem {
	case "open-ssl-gost":
		return &OpenSslGostProvider{
			KeyPath:     os.Getenv("OPENSSL_KEY_PATH"),
			KeyPassword: os.Getenv("OPENSSL_KEY_PASSWORD"),
			CertPath:    os.Getenv("OPENSSL_CERT_PATH"),
		}, nil
	case "adapter-infra":
		return &AdapterInfraProvider{
			URL:    os.Getenv("CRYPTO_ADAPTER_URL"),
			KeyID:  os.Getenv("CRYPTO_ADAPTER_KEY_ID"),
			Client: &http.Client{Timeout: 5 * time.Second},
		}, nil
	case "crypto-pro":
		return &CryptoProProvider{
			URL: os.Getenv("CRYPTO_ADAPTER_URL"),
		}, nil
	default:
		return nil, fmt.Errorf("unsupported crypto system: %s", f.cryptoSystem)
	}
}
