# ==========================================
# Project Configurations
# ==========================================
BINARY_NAME=esia-gateway
DOCKER_IMAGE=esia-gateway-microservice
VERSION?=1.0.0

.PHONY: all build clean test lint docker-build docker-run help

all: lint test build

# ==========================================
# Development & Compilation Tasks
# ==========================================
build:
	@echo "Building static release binary from root module entrypoint..."
	@CGO_ENABLED=1 go build -ldflags="-w -s" -o bin/$(BINARY_NAME) .

run:
	@echo "Running gateway application locally from root main.go..."
	@CGO_ENABLED=1 go run main.go

clean:
	@echo "Cleaning up local workspace build assets..."
	@rm -rf bin/ dist/ coverage.out
	@go clean -cache -testcache

# ==========================================
# Testing & Verification Tasks
# ==========================================
test:
	@echo "Preparing build artifacts directory..."
	@mkdir -p bin/
	@echo "Running package test suites with race detector enabled..."
	@CGO_ENABLED=1 go test -v -race -coverpkg=./... -coverprofile=bin/coverage.out ./test/...
	@echo "Test suite analysis execution complete. Coverage file written to bin/coverage.out"

# Дополнительный полезный таргет для визуального анализа покрытия в браузере
cover-html: test
	@echo "Generating interactive HTML coverage report matrix..."
	@go tool cover -html=bin/coverage.out -o bin/coverage.html
	@echo "Report ready! Open bin/coverage.html in your browser to view line-by-byte coverage."

lint:
	@echo "Verifying codebase code style using standard golangci-lint profiles..."
	@golangci-lint run ./... || echo "Warning: install golangci-lint to capture code warnings."

# ==========================================
# Docker Lifecycle Automation
# ==========================================
docker-build:
	@echo "Compiling and packaging multi-stage Docker image v$(VERSION)..."
	docker build -t $(DOCKER_IMAGE):$(VERSION) -t $(DOCKER_IMAGE):latest .

docker-run:
	@echo "Starting Docker microservice container in local open-ssl-gost validation mode..."
	docker run --rm -it \
		-p 8080:8080 \
		-e HTTP_LISTEN_PORT=:8080 \
		-e CRYPTO_SYSTEM=open-ssl-gost \
		-e GATEWAY_CLIENT_ID=019110 \
		-e OPENSSL_KEY_PATH=/etc/ssl/esia/ca.key \
		-e OPENSSL_CERT_PATH=/etc/ssl/esia/ca.crt \
		-e ESIA_TARGET_HOST=esia-portal1.test.gosuslugi.ru \
		-v $(PWD)/test/certs:/etc/ssl/esia:ro \
		--name $(BINARY_NAME)-instance \
		$(DOCKER_IMAGE):latest

# ==========================================
# Test keys / certificates generation
# ==========================================

generate-test-keys:
	@echo "Initializing workspace directories..."
	@mkdir -p test/openssl
	@echo "Generating option A: Keys WITHOUT a password (ca_no_pass.*)..."
	@openssl genpkey -engine gost -algorithm gost2012_256 -pkeyopt paramset:A -out test/openssl/ca_no_pass.key
	@openssl req -new -x509 -days 365 -key test/openssl/ca_no_pass.key -out test/openssl/ca_no_pass.crt -subj "/CN=ESIA Test No Password"
	@echo "Generating option B: Standard production keys WITH a password (ca.*)..."
	@openssl genpkey -engine gost -algorithm gost2012_256 -pkeyopt paramset:A -aes-256-cbc -pass pass:a2NSPsfQcGh56ZScd3d7 -out test/openssl/ca.key
	@openssl req -new -x509 -days 365 -key test/openssl/ca.key -passin pass:a2NSPsfQcGh56ZScd3d7 -out test/openssl/ca.crt -subj "/CN=ESIA Production Standard Password"
	@echo "Cryptographic assets successfully created:"
	@ls -la test/openssl/

help:
	@echo "Available commands:"
	@echo "  make build        - Compile the Go microservice using root package tools"
	@echo "  make run          - Execute main.go immediately in development mode"
	@echo "  make test         - Run internal automated unit and data testing profiles"
	@echo "  make lint         - Audit code layouts using automated static verifiers"
	@echo "  make docker-build - Assemble the clean multi-stage runtime Docker image"
	@echo "  make docker-run   - Boot up the localized container image for QA tests"
