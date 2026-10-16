package proxy

import (
	"elk-esia-gateway/crypto"
	"log/slog"
	"os"
	"strings"

	"github.com/gin-gonic/gin"
)

type EsiaServer struct {
	ClientID       string
	EsiaTargetHost string
	CryptoProvider crypto.Provider
}

func NewEsiaServer(clientId string, esiaTargetHost string, cryptoProvider crypto.Provider) EsiaServer {
	return EsiaServer{
		ClientID:       clientId,
		EsiaTargetHost: esiaTargetHost,
		CryptoProvider: cryptoProvider,
	}
}

func (s *EsiaServer) Init(r *gin.Engine) {

	// Настройка мимикрии под эндпоинты v1
	r.GET("/aas/oauth2/ac", s.HandleAuthProxy)
	r.POST("/aas/oauth2/te", s.HandleTokenProxy)

	// Настройка мимикрии под эндпоинт выхода v1
	r.GET("/idp/ext/Logout", s.HandleLogoutProxy)

	// Настройка Reverse Proxy для чтения ресурсов
	r.GET("/rs/prns/*any", s.HandleResourceProxy)
	r.GET("/rs/orgs/*any", s.HandleResourceProxy)
}

const DEBUG = "DEBUG"
const INFO = "INFO"
const WARN = "WARN"
const WARNING = "WARNING"
const ERROR = "ERROR"

const LOG_TEXT = "text"
const LOG_JSON = "json"

// InitLogger настраивает глобальный логгер slog на основе переданных конфигураций.
// Поддерживает уровни: "DEBUG", [по умолчанию] "INFO", "WARN" / "WARNING", "ERROR"
// Поддерживает хендлеры: [по умолчанию] "json" (для prod/ELK), "text" (для dev/тестов)
func InitLogger(level string, logHandler string) {
	// 1. Определяем уровень логирования (Default: Info)
	var slogLevel = slog.LevelInfo

	switch strings.ToUpper(strings.TrimSpace(level)) {
	case DEBUG:
		slogLevel = slog.LevelDebug
	case WARN, WARNING:
		slogLevel = slog.LevelWarn
	case ERROR:
		slogLevel = slog.LevelError
	case INFO:
	default:
	}

	opts := &slog.HandlerOptions{
		Level: slogLevel,
	}

	// 2. Инициализируем выбранный тип хендлера (Default: JSON)
	var handler slog.Handler
	switch strings.ToLower(strings.TrimSpace(logHandler)) {
	case LOG_TEXT:
		handler = slog.NewTextHandler(os.Stdout, opts)
	default:
		handler = slog.NewJSONHandler(os.Stdout, opts)
	}

	// 3. Устанавливаем логгер как дефолтный для всего рантайма приложения
	slog.SetDefault(
		slog.New(handler),
	)
}
