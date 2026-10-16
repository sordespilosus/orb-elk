# ==============================================================================
# ЭТАП 1: СБОРКА БИНАРНИКА И ДВИЖКА ГОСТ (BUILD ENVIRONMENT)
# ==============================================================================
FROM golang:1.27.1-bookworm AS builder

# Устанавливаем системные зависимости для сборки CGO-контура, CMake и OpenSSL 3
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    libssl-dev \
    pkg-config \
    git \
    cmake \
    make \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------------------------
# СБОРКА И КОМПИЛЯЦИЯ GOST-ENGINE ДЛЯ OPENSSL 3 (СТАБИЛЬНЫЙ ТЕГ v3.0.3)
# ------------------------------------------------------------------------------
WORKDIR /src

# Клонируем официальный репозиторий gost-engine/engine со всеми сабмодулями
RUN git clone --recurse-submodules https://github.com/gost-engine/engine.git gost-engine && \
    cd gost-engine && \
    git checkout v3.0.3 && \
    git submodule update --init --recursive && \
    mkdir build && cd build && \
    cmake -DCMAKE_BUILD_TYPE=Release -DCMAKE_C_FLAGS="-Wno-error -Wno-stringop-overflow" .. && \
    make && make install

# ------------------------------------------------------------------------------
# СБОРКА ИСПОЛНЯЕМОГО ФАЙЛА ШЛЮЗА ЧЕРЕЗ MAKEFILE
# ------------------------------------------------------------------------------
WORKDIR /app

# Копируем и кэшируем зависимости Go слоев Docker
COPY go.mod go.sum ./
RUN go mod download

# Копируем исходный код И отлаженный Makefile проекта
COPY . .

# Вызываем системный таргет компиляции из вашего Makefile
RUN make build

# ==============================================================================
# ЭТАП 2: МИНИМАЛЬНЫЙ ПРОМЫШЛЕННЫЙ ОБРАЗ (PRODUCTION RUNTIME)
# ==============================================================================
FROM debian:bookworm-slim AS runner

# Устанавливаем только чистый рантайн OpenSSL 3 и корневые сертификаты систем
RUN apt-get update && apt-get install -y --no-install-recommends \
    openssl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Копируем скомпилированный ГОСТ-модуль (gost.so) из сборочного контейнера
COPY --from=builder /usr/lib/x86_64-linux-gnu/engines-3/gost.so /usr/lib/x86_64-linux-gnu/engines-3/gost.so

# ------------------------------------------------------------------------------
# КОНФИГУРАЦИЯ НАЦИОНАЛЬНЫХ ПРОВАЙДЕРОВ OPENSSL 3
# ------------------------------------------------------------------------------
# Конфигурируем openssl.cnf для автоматической активации gost-engine в рантайме Си-потоков
RUN sed -i 's/\[openssl_init\]/\[openssl_init\]\nengines = engines_sect/' /etc/ssl/openssl.cnf && \
    echo "\n[engines_sect]\ngost = gost_sect\n\n[gost_sect]\nengine_id = gost\ndynamic_path = /usr/lib/x86_64-linux-gnu/engines-3/gost.so\ndefault_algorithms = ALL" >> /etc/ssl/openssl.cnf

WORKDIR /app

# Создаем изолированную директорию под монтирование ca.key и ca.crt на сервере
RUN mkdir -p /etc/esia/crypto

# Копируем собранный через Makefile легковесный бинарник шлюза
COPY --from=builder /app/bin/esia-gateway .
# Пробрасываем шаблон конфигурации
COPY --from=builder /app/.env.example ./

# Открываем порт для обслуживания входящих HTTP запросов от вашего приложения
EXPOSE 8080

# Переключаем фреймворк Gin в высокопроизводительный режим продакшена
ENV GIN_MODE=release

# Точка входа в контейнер
CMD ["./esia-gateway"]
