package test

import (
	"bytes"
	"context"
	"elk-esia-gateway/crypto"
	"elk-esia-gateway/proxy"
	"encoding/base64"
	"fmt"
	"log/slog"
	"net/http"
	"net/http/httptest"
	"net/url"
	"os"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/suite"
)

// Относительные пути к эталонным тестовым ключам
const (
	esiaCertNoPass = "../cert/esia/ca.crt"
	esiaKeyNoPass  = "../cert/esia/ca.key"
)

// Точка входа для запуска интеграционного сьюта через стандартный Go toolchain
func TestRunEsiaIntegrationTestSuite(t *testing.T) {
	suite.Run(t, new(EsiaIntegrationTestSuite))
}

// GatewayIntegrationTestSuite инкапсулирует сквозные сценарии взаимодействия ИС и шлюза
type EsiaIntegrationTestSuite struct {
	suite.Suite
	Ctx            context.Context
	Router         *gin.Engine
	NoPassProvider *crypto.OpenSslGostProvider
	ClientID       string
}

// SetupSuite инициализирует общее окружение для всех интеграционных сценариев
func (suite *EsiaIntegrationTestSuite) SetupSuite() {
	proxy.InitLogger(proxy.DEBUG, proxy.LOG_TEXT)

	// 1. Проверяем наличие тестовых артефактов на диске
	required := []string{esiaCertNoPass, esiaKeyNoPass}
	for _, path := range required {
		if _, err := os.Stat(path); os.IsNotExist(err) {
			suite.T().Skipf("Критический файл %s отсутствует. Пропуск интеграционного сьюта.", path)
		}
	}

	suite.Ctx = context.Background()
	suite.ClientID = "TEST_ORB_LK"

	// 3. Инстанцируем нативный крипто-провайдер для эмуляции ИС и шлюза
	suite.NoPassProvider = &crypto.OpenSslGostProvider{
		KeyPath:  esiaKeyNoPass,
		CertPath: esiaCertNoPass,
	}

	// 4. Поднимаем Gin в тестовом режиме и разворачиваем роутинг шлюза
	gin.SetMode(gin.TestMode)
	suite.Router = gin.New()

	gatewayServer := proxy.NewEsiaServer(
		suite.ClientID,
		"esia-portal1.test.gosuslugi.ru", // Тестовый контур ЕСИА
		suite.NoPassProvider,
	)
	gatewayServer.Init(suite.Router)
}

// TestAuthProxyEndpoint_Success проверяет успешную трансляцию GET /aas/oauth2/ac (v1 -> v2/ac)
func (suite *EsiaIntegrationTestSuite) TestAuthProxyEndpoint_Success() {
	// ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры по спецификации v1
	authReq := proxy.AuthRequest{
		ClientID:     suite.ClientID,
		Scope:        "fullname birthdate snils",
		ResponseType: "code",
		RedirectURI:  "https://client-app.ru",
		State:        "integration-secure-session-state-token",
		Timestamp:    proxy.NowTimestamp(),
	}

	err, w := suite.makeAuthRequest(authReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusFound, w.Code, "Шлюз должен ответить редиректом 302 Found")

	redirectLocation := w.Header().Get("Location")
	suite.NotEmpty(redirectLocation)

	parsedTargetURL, err := url.Parse(redirectLocation)
	suite.NoError(err)

	// Проверяем корректность трансляции хоста и эндпоинта назначения под v2/ac
	suite.Equal("https", parsedTargetURL.Scheme)
	suite.Equal("esia-portal1.test.gosuslugi.ru", parsedTargetURL.Host)
	suite.Equal("/aas/oauth2/v2/ac", parsedTargetURL.Path)

	// Проверяем, что шлюз сохранил параметры сессии и пересчитал подпись под v2
	targetQuery := parsedTargetURL.Query()
	suite.Equal(authReq.ClientID, targetQuery.Get("client_id"))
	suite.Equal(authReq.Scope, targetQuery.Get("scope"))
	suite.Equal(authReq.RedirectURI, targetQuery.Get("redirect_uri"))
	suite.Equal(authReq.State, targetQuery.Get("state"))
	suite.Equal(authReq.Timestamp, targetQuery.Get("timestamp"))

	v2ClientSecretOutput := targetQuery.Get("client_secret")
	suite.NotEmpty(v2ClientSecretOutput)

	// Декодируем итоговый client_secret: сырая reversed подпись ГОСТ должна занимать строго 64 байта
	decodedV2Sig, err := base64.RawURLEncoding.DecodeString(v2ClientSecretOutput)
	suite.NoError(err, "Финальный v2 client_secret имеет некорректный Base64 URL формат")
	suite.Len(decodedV2Sig, 64, "Размер сырой подписи ГОСТ v2 должен составлять ровно 64 байта")
}

// TestAuthProxyEndpoint_NoTimestamp проверяет успешную трансляцию, если ИС не передает timestamp
func (suite *EsiaIntegrationTestSuite) TestAuthProxyEndpoint_NoTimestamp() {
	// ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры по спецификации v1
	authReq := proxy.AuthRequest{
		ClientID:     suite.ClientID,
		Scope:        "fullname birthdate snils",
		ResponseType: "code",
		RedirectURI:  "https://client-app.ru",
		State:        "integration-secure-session-state-token",
	}

	err, w := suite.makeAuthRequest(authReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusFound, w.Code, "Шлюз должен ответить редиректом 302 Found")

	redirectLocation := w.Header().Get("Location")
	suite.NotEmpty(redirectLocation)

	parsedTargetURL, err := url.Parse(redirectLocation)
	suite.NoError(err)

	// Проверяем, что шлюз сохранил параметры сессии и пересчитал подпись под v2
	targetQuery := parsedTargetURL.Query()
	suite.NotEmpty(targetQuery.Get("timestamp"))
}

// TestAuthProxyEndpoint_WrongSystemID проверяет ошибку при неверном значении client_id
func (suite *EsiaIntegrationTestSuite) TestAuthProxyEndpoint_WrongSystemID() {
	// ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры по спецификации v1
	authReq := proxy.AuthRequest{
		ClientID:     "MY_OWN_SYSTEM",
		Scope:        "fullname birthdate snils",
		ResponseType: "code",
		RedirectURI:  "https://client-app.ru",
		State:        "integration-secure-session-state-token",
		Timestamp:    proxy.NowTimestamp(),
	}

	err, w := suite.makeAuthRequest(authReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusBadRequest, w.Code, "Шлюз должен ответить 400 Bad Request")
}

// makeAuthRequest подписывает авторизационный запрос и отправляет через локальный сервер в ЕСИА
func (suite *EsiaIntegrationTestSuite) makeAuthRequest(authReq proxy.AuthRequest) (error, *httptest.ResponseRecorder) {

	slog.Debug(">>> Request", "AuthRequest", authReq)

	// Подписываем от лица легаси-приложения (отсоединенный PKCS#7)
	clientSecret, err := suite.NoPassProvider.SignPKCS7(suite.Ctx, authReq.ForV1Signature())
	suite.NoError(err, "Не удалось сгенерировать входящую легаси PKCS#7 подпись")

	authReq.ClientSecret = clientSecret

	// Подготавливаем URL и упаковываем параметры в URL Query форму
	targetInboundURL := fmt.Sprintf("/aas/oauth2/ac?%s", authReq.ToQueryParams().Encode())

	slog.Debug("  > HTTP", "URL", targetInboundURL)

	// Выполняем виртуальный вызов к шлюзу
	req, err := http.NewRequestWithContext(suite.Ctx, "GET", targetInboundURL, nil)
	suite.NoError(err)

	w := httptest.NewRecorder()
	suite.Router.ServeHTTP(w, req)

	slog.Debug("  < HTTP", "Body", w.Body.String())

	slog.Debug("<<< Request", "Result", w.Result())

	return nil, w
}

// TestTokenProxyEndpoint_MockSuccess полностью эмулирует обмен кода на токен (v1 PKCS#7 -> v3 PKCS#7)
// с использованием локального мок-сервера ЕСИА
func (suite *EsiaIntegrationTestSuite) TestTokenProxyEndpoint_MockSuccess() {
	// 1. ПОДГОТОВКА СТЕНДА: Поднимаем мок-сервер Госуслуг в памяти теста
	mockEsiaServer := suite.mockEsiaTokenExchange()
	defer mockEsiaServer.Close()

	// 2. НАСТРОЙКА ШЛЮЗА: Временно перенаправляем шлюз с Минцифры на наш мок-сервер

	// Извлекаем чистый хост (IP:PORT) локального сервера из URL вида http://127.0.0.1:45321
	parsedMockURL, _ := url.Parse(mockEsiaServer.URL)

	gatewayServer := proxy.NewEsiaServer(suite.ClientID, parsedMockURL.Host, suite.NoPassProvider)

	// Поднимаем тестовый роутер Gin именно для этого теста
	router := gin.New()
	gatewayServer.Init(router)

	// 3. ЭМУЛЯЦИЯ ЛЕГАСИ-ПРИЛОЖЕНИЯ: Готовим POST-запрос v1 обмена токена
	tokenReq := proxy.TokenRequest{
		GrantType:   "authorization_code",
		ClientID:    suite.ClientID,
		Code:        "authenticated-code-from-successful-user-login",
		RedirectURI: "https://client-app.ru",
		State:       "integration-secure-session-state-token",
		Scope:       "fullname birthdate snils",
		Timestamp:   proxy.NowTimestamp(),
	}

	err, w := suite.makeTokenRequest(router, tokenReq)
	suite.NoError(err)

	// 5. ВЕРИФИКАЦИЯ РЕЗУЛЬТАТА ТРАНСЛЯЦИИ ТРАФИКА
	suite.Equal(http.StatusOK, w.Code, "Шлюз должен успешно проксировать ответ от мок-сервера ЕСИА со статусом 200 OK")

	// Проверяем, что тело ответа ЕСИА долетело обратно до легаси-приложения в неизменном виде
	suite.Contains(w.Body.String(), "fake-esia-access-token-jwt-string")
	suite.Contains(w.Body.String(), "Bearer")
	suite.Contains(w.Body.String(), tokenReq.State)
}

// TestTokenProxyEndpoint_EsiaFailure проверяет ошибку при прямом обращении к ЕСИА
func (suite *EsiaIntegrationTestSuite) TestTokenProxyEndpoint_EsiaFailure() {
	// ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры по спецификации v1
	tokenReq := proxy.TokenRequest{
		GrantType:   "authorization_code",
		ClientID:    suite.ClientID,
		Code:        "authenticated-code-from-successful-user-login",
		RedirectURI: "https://client-app.ru",
		State:       "integration-secure-session-state-token",
		Scope:       "fullname birthdate snils",
		Timestamp:   proxy.NowTimestamp(),
	}

	err, w := suite.makeTokenRequest(suite.Router, tokenReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusBadRequest, w.Code, "Шлюз должен ответить 400 Bad Request")
}

// TestTokenProxyEndpoint_WrongSystemID проверяет ошибку при неверном значении client_id
func (suite *EsiaIntegrationTestSuite) TestTokenProxyEndpoint_WrongSystemID() {
	// ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры по спецификации v1
	tokenReq := proxy.TokenRequest{
		GrantType:   "authorization_code",
		ClientID:    "MY_OWN_SYSTEM",
		Code:        "authenticated-code-from-successful-user-login",
		RedirectURI: "https://client-app.ru",
		State:       "integration-secure-session-state-token",
		Scope:       "fullname birthdate snils",
		Timestamp:   proxy.NowTimestamp(),
	}

	err, w := suite.makeTokenRequest(suite.Router, tokenReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusBadRequest, w.Code, "Шлюз должен ответить 400 Bad Request")
}

// makeTokenRequest подписывает авторизационный запрос и отправляет через шлюз в мок-сервер ЕСИА
func (suite *EsiaIntegrationTestSuite) makeTokenRequest(router *gin.Engine, tokenReq proxy.TokenRequest) (error, *httptest.ResponseRecorder) {

	slog.Debug(">>> Request", "TokenRequest", tokenReq)

	// Подписываем от лица легаси-приложения (отсоединенный PKCS#7)
	clientSecret, err := suite.NoPassProvider.SignPKCS7(suite.Ctx, tokenReq.ForV1Signature())
	suite.NoError(err, "Не удалось сгенерировать входящую легаси PKCS#7 подпись")

	tokenReq.ClientSecret = clientSecret

	formFields := tokenReq.ToPostForm()

	slog.Debug("  > HTTP", "Form", formFields)

	// 4. ВЫПОЛНЕНИЕ ВЫЗОВА К ШЛЮЗУ
	req, err := http.NewRequestWithContext(suite.Ctx, "POST", "/aas/oauth2/te", bytes.NewBufferString(formFields.Encode()))
	suite.NoError(err)
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	slog.Debug("  < HTTP", "Body", w.Body.String())

	slog.Debug("<<< Request", "Result", w.Result())

	return nil, w
}

func (suite *EsiaIntegrationTestSuite) mockEsiaTokenExchange() *httptest.Server {
	server := httptest.NewTLSServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// Шлюз переводит запрос на современный эндпоинт ЕСИА v3 (/aas/oauth2/v2/te)
		suite.Assert().Equal("/aas/oauth2/v3/te", r.URL.Path)
		suite.Assert().Equal("POST", r.Method)
		suite.Assert().Equal("application/x-www-form-urlencoded", r.Header.Get("Content-Type"))

		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte(`{
			"access_token": "fake-esia-access-token-jwt-string",
			"token_type": "Bearer",
			"expires_in": 3600,
			"state": "integration-secure-session-state-token"
		}`))
	}))

	// ВАЖНО ДЛЯ CGO/HTTP КЛИЕНТА: Модифицируем стандартный http.Client шлюза,
	// чтобы он доверял локальному самоподписанному сертификату этого HTTPS мок-сервера.
	// Без этой привязки исходящий http.PostForm вернет ошибку "x509: certificate signed by unknown authority".
	http.DefaultTransport.(*http.Transport).TLSClientConfig = server.Client().Transport.(*http.Transport).TLSClientConfig

	return server
}

// TestLogoutProxyEndpoint_Success эмулирует сквозной интеграционный сценарий выхода:
//  1. PHP-система формирует легаси-запрос Logout (v1) и подписывает его через PKCS#7 (SignPKCS7).
//  2. Отправляет GET-запрос в наш микросервис шлюза на эндпоинт /idp/ext/Logout.
//  3. Шлюз возвращает браузеру редирект 302 Found.
func (suite *EsiaIntegrationTestSuite) TestLogoutProxyEndpoint_Success() {
	// 1. ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры Logout по спецификации v1
	logoutReq := proxy.LogoutRequest{
		ClientID:    suite.ClientID,
		RedirectURL: "https://client-app.ru",
	}

	err, w := suite.makeLogoutRequest(logoutReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusFound, w.Code, "Шлюз должен ответить редиректом 302 Found")

	redirectLocation := w.Header().Get("Location")
	suite.NotEmpty(redirectLocation, "Заголовок Location в ответе 302 пуст")

	parsedTargetURL, err := url.Parse(redirectLocation)
	suite.NoError(err)

	// Проверяем корректность трансляции хоста и целевого эндпоинта выхода под спецификацию v2
	suite.Equal("https", parsedTargetURL.Scheme)
	suite.Equal("esia-portal1.test.gosuslugi.ru", parsedTargetURL.Host)
	suite.Equal("/aas/oauth2/api/logout", parsedTargetURL.Path)

	// Выделяем Query-параметры, которые шлюз пересчитал для Госуслуг v2 Logout
	targetQuery := parsedTargetURL.Query()
	suite.Equal(logoutReq.ClientID, targetQuery.Get("client_id"))
	suite.Equal(logoutReq.RedirectURL, targetQuery.Get("redirect_url"))
}

// TestLogoutProxyEndpoint_WrongSystemID проверяет ошибку при неверном значении client_id
func (suite *EsiaIntegrationTestSuite) TestLogoutProxyEndpoint_WrongSystemID() {
	// ЭМУЛЯЦИЯ ЛК ВНЕШНЕЙ СИСТЕМЫ: Формируем параметры по спецификации v1
	logoutReq := proxy.LogoutRequest{
		ClientID:    "MY_OWN_SYSTEM",
		RedirectURL: "https://client-app.ru",
	}

	err, w := suite.makeLogoutRequest(logoutReq)
	suite.NoError(err)

	// ВЕРИФИКАЦИЯ ОТВЕТА ШЛЮЗА
	suite.Equal(http.StatusBadRequest, w.Code, "Шлюз должен ответить 400 Bad Request")
}

// makeLogoutRequest формирует запрос и отправляет на локальный сервер
func (suite *EsiaIntegrationTestSuite) makeLogoutRequest(logoutReq proxy.LogoutRequest) (error, *httptest.ResponseRecorder) {

	slog.Debug(">>> Request", "LogoutRequest", logoutReq)

	// Подготавливаем URL и упаковываем параметры в URL Query форму
	targetInboundURL := fmt.Sprintf("/idp/ext/Logout?%s", logoutReq.ToQueryParams().Encode())

	slog.Debug("  > HTTP", "URL", targetInboundURL)

	// Выполняем виртуальный вызов к шлюзу
	req, err := http.NewRequestWithContext(suite.Ctx, "GET", targetInboundURL, nil)
	suite.NoError(err)

	w := httptest.NewRecorder()
	suite.Router.ServeHTTP(w, req)

	slog.Debug("  < HTTP", "Body", w.Body.String())

	slog.Debug("<<< Request", "Result", w.Result())

	return nil, w
}
