package main

import (
	"elk-esia-gateway/crypto"
	"elk-esia-gateway/proxy"
	"log"
	"log/slog"
	"os"

	"github.com/gin-gonic/gin"
)

func main() {
	// Считываем настройки логирования из ENV (задаются через docker-compose/.env)
	logLevel := os.Getenv("LOG_LEVEL")     // например, "INFO" или "DEBUG"
	logHandler := os.Getenv("LOG_HANDLER") // например, "json" или "text"

	// Инициализируем логгер с динамическими параметрами среды
	proxy.InitLogger(logLevel, logHandler)

	slog.Info("ЕСИА Шлюз запускается...", "level", logLevel, "handler", logHandler)

	// 1. Инициализация фабрики и провайдера криптосистемы
	cryptoSystem := os.Getenv("CRYPTO_SYSTEM")
	factory := crypto.NewFactory(cryptoSystem)

	provider, err := factory.CreateProvider()
	if err != nil {
		log.Fatalf("Ошибка инициализации крипто-провайдера: %v", err)
	}

	server := proxy.NewEsiaServer(
		os.Getenv("GATEWAY_CLIENT_ID"),
		os.Getenv("ESIA_TARGET_HOST"),
		provider,
	)

	slog.Info("ЕСИА Прокси", "Сервер", server)

	// 2. Инициализация Gin Router
	r := gin.Default()

	server.Init(r)

	listenPort := os.Getenv("HTTP_LISTEN_PORT")
	if listenPort == "" {
		listenPort = ":8080"
	}

	slog.Info("ЕСИА Шлюз успешно запущен", "Порт", listenPort)
	err = r.Run(listenPort)
	if err != nil {
		log.Fatalf("Ошибка запуска ЕСИА Шлюза: %v]", err)
	}
}
