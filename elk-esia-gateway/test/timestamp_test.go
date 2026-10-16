package test

import (
	"elk-esia-gateway/proxy"
	"testing"
	"time"

	"github.com/stretchr/testify/suite"
)

// TimestampTestSuite объединяет все тесты для проверки временных меток ЕСИА
type TimestampTestSuite struct {
	suite.Suite
}

// Вызывается автоматически go test для запуска всего тест-сьюта
func TestTimestampTestSuite(t *testing.T) {
	suite.Run(t, new(TimestampTestSuite))
}

// TestValidateTimestamp_Success_UTC проверяет валидацию актуального времени в UTC (+0000)
func (suite *TimestampTestSuite) TestValidateTimestamp_Success_UTC() {
	inputStr := time.Now().UTC().Format(proxy.EsiaTimestampLayout)
	req := proxy.TimestampRequest{Timestamp: inputStr}

	err := req.ValidateTimestamp()

	suite.NoError(err, "Ожидалась успешная валидация для текущего времени UTC")
	suite.Equal(inputStr, req.Timestamp, "Строка timestamp не должна изменяться прокси-сервером")
}

// TestValidateTimestamp_Success_CustomTimezone проверяет работу с региональными часовыми поясами (например, Самара +0400)
func (suite *TimestampTestSuite) TestValidateTimestamp_Success_CustomTimezone() {
	samaraZone := time.FixedZone("SAMT", 4*60*60)
	inputStr := time.Now().In(samaraZone).Format(proxy.EsiaTimestampLayout)
	req := proxy.TimestampRequest{Timestamp: inputStr}

	err := req.ValidateTimestamp()

	suite.NoError(err, "Ожидалась успешная валидация для валидного регионального времени")
	suite.Equal(inputStr, req.Timestamp, "Прокси должен сохранять оригинальную часовую зону ИС без изменений")
}

// TestValidateTimestamp_Failure_Expired проверяет блокировку запроса, отставшего на 6 минут
func (suite *TimestampTestSuite) TestValidateTimestamp_Failure_Expired() {
	expiredStr := time.Now().UTC().Add(-6 * time.Minute).Format(proxy.EsiaTimestampLayout)
	req := proxy.TimestampRequest{Timestamp: expiredStr}

	err := req.ValidateTimestamp()

	suite.Error(err, "Ожидалась ошибка отклонения времени для устаревшего запроса")
	suite.ErrorContains(err, "clock skew too high", "Текст ошибки должен сообщать о превышении допустимого окна расхождения")
}

// TestValidateTimestamp_Failure_Future проверяет блокировку запроса, спешащего на 6 минут
func (suite *TimestampTestSuite) TestValidateTimestamp_Failure_Future() {
	futureStr := time.Now().UTC().Add(6 * time.Minute).Format(proxy.EsiaTimestampLayout)
	req := proxy.TimestampRequest{Timestamp: futureStr}

	err := req.ValidateTimestamp()

	suite.Error(err, "Ожидалась ошибка отклонения времени для запроса из будущего")
	suite.ErrorContains(err, "clock skew too high", "Текст ошибки должен сообщать о превышении допустимого окна расхождения")
}

// TestValidateTimestamp_Failure_InvalidFormat проверяет реакцию на некорректный строковый формат
func (suite *TimestampTestSuite) TestValidateTimestamp_Failure_InvalidFormat() {
	// Передаем стандартный RFC3339 вместо маски ЕСИА (yyyy.MM.dd HH:mm:ss Z)
	req := proxy.TimestampRequest{Timestamp: "2026-10-06T02:00:00Z"}

	err := req.ValidateTimestamp()

	suite.Error(err, "Ожидалась ошибка парсинга при неверном формате даты")
	suite.ErrorContains(err, "invalid timestamp format", "Текст ошибки должен указывать на некорректный формат строки")
}

// TestValidateTimestamp_Success_Empty проверяет, что если ИС не передала timestamp,
// прокси генерирует его самостоятельно на базе текущего времени и успешно завершает метод.
func (s *TimestampTestSuite) TestValidateTimestamp_Success_Empty() {
	req := proxy.TimestampRequest{Timestamp: ""}

	err := req.ValidateTimestamp()

	s.NoError(err, "Пустой timestamp не должен вызывать ошибку, прокси должен сгенерировать его сам")
	s.NotEmpty(req.Timestamp, "Поле Timestamp должно быть заполнено автоматически")

	// Проверяем, что сгенерированная строка парсится обратно без ошибок
	parsedTime, parseErr := time.Parse(proxy.EsiaTimestampLayout, req.Timestamp)
	s.NoError(parseErr, "Сгенерированный timestamp должен иметь валидный формат ЕСИА")

	// Проверяем, что сгенерированное время близко к текущему времени сервера (дельта в пределах 2 секунд)
	s.WithinDuration(time.Now().UTC(), parsedTime.UTC(), 2*time.Second, "Сгенерированное время должно соответствовать текущему времени сервера")
}
