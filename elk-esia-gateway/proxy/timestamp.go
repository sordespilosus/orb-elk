package proxy

import (
	"fmt"
	"time"
)

const (
	// EsiaTimestampLayout — стандартный формат времени ЕСИА (например: "2013.01.25 14:36:11 +0400")
	EsiaTimestampLayout = "2006.01.02 15:04:05 -0700"
	// MaxClockSkew — окно допустимого расхождения часов между ИС и прокси (5 минут)
	MaxClockSkew = 5 * time.Minute
)

var MSK *time.Location

func init() {
	loc, err := time.LoadLocation("Europe/Moscow")
	if err != nil {
		loc = time.FixedZone("MSK", 3*60*60)
	}
	MSK = loc
}

func NowTimestamp() string {
	return time.Now().In(MSK).Format(EsiaTimestampLayout)
}

// TimestampRequest предоставляет общую логику контроля времени для AuthRequest и TokenRequest
type TimestampRequest struct {
	Timestamp string `form:"timestamp" json:"timestamp" formData:"timestamp"` // Опционально в v1
}

// ValidateTimestamp проверяет актуальность входящего timestamp от ИС.
// Защищает stateless-прокси от атак повторения (Replay) и холостой нагрузки на ГОСТ-криптографию.
// Сама строка времени остается без изменений, сохраняя оригинальный часовой пояс ИС.
// Если ИС не передала timestamp, прокси генерирует его на основе текущего времени (UTC).
func (tsReq *TimestampRequest) ValidateTimestamp() error {
	// Кейс обратной совместимости: ИС не прислала timestamp. Заполняем текущим временем прокси в формате UTC (+0000)
	if tsReq.Timestamp == "" {
		tsReq.Timestamp = NowTimestamp()
		return nil
	}

	// Парсим строку времени с сохранением переданного ИС часового пояса
	parsedTime, err := time.Parse(EsiaTimestampLayout, tsReq.Timestamp)
	if err != nil {
		return fmt.Errorf("invalid timestamp format: %w", err)
	}

	// Математическое сравнение меток в UTC для вычисления физической дельты времени
	now := time.Now().UTC()
	diff := now.Sub(parsedTime.UTC())
	if diff < 0 {
		diff = -diff
	}

	// Если часы ИС и прокси разошлись более чем на 5 минут — отклоняем запрос до шага подписи
	if diff > MaxClockSkew {
		return fmt.Errorf("clock skew too high: difference is %v, max allowed is %v", diff, MaxClockSkew)
	}

	return nil
}
