package test

import (
	"bytes"
	"context"
	"elk-esia-gateway/crypto"
	"elk-esia-gateway/proxy"
	"encoding/asn1"
	"encoding/base64"
	"os"
	"os/exec"
	"path/filepath"
	"testing"
	"time"

	"github.com/stretchr/testify/suite"
)

// Относительные пути к тестовым ГОСТ-ключам, закоммиченным в Git
const (
	pathCertNoPass = "../cert/local/ca_no_pass.crt"
	pathKeyNoPass  = "../cert/local/ca_no_pass.key"
	pathCertPass   = "../cert/local/ca.crt"
	pathKeyPass    = "../cert/local/ca.key"
	keyPassword    = "a2NSPsfQcGh56ZScd3d7"
)

// Точка входа для запуска тестов через стандартный Go-инструментарий (go test)
func TestRunOpenSslGostTestSuite(t *testing.T) {
	suite.Run(t, new(OpenSslGostTestSuite))
}

// TODO : проверить ошибочные ситуации по coverage

// OpenSslGostTestSuite наследует базовую структуру тестовой сюиты testify
type OpenSslGostTestSuite struct {
	suite.Suite
	Ctx              context.Context
	NoPassProvider   *crypto.OpenSslGostProvider
	WithPassProvider *crypto.OpenSslGostProvider
	ClientID         string
	MskLocation      *time.Location

	authRequest   proxy.AuthRequest
	logoutRequest proxy.LogoutRequest
	tokenRequest  proxy.TokenRequest
}

// SetupSuite выполняется ОДИН раз перед запуском всех тест-кейсов сюиты
func (suite *OpenSslGostTestSuite) SetupSuite() {
	proxy.InitLogger(proxy.DEBUG, proxy.LOG_TEXT)

	// 1. Проверяем наличие закоммиченных ключей в Git перед запуском
	requiredFiles := []string{pathCertNoPass, pathKeyNoPass, pathCertPass, pathKeyPass}
	for _, path := range requiredFiles {
		if _, err := os.Stat(path); os.IsNotExist(err) {
			suite.T().Skipf("Критический файл %s отсутствует. Пропуск выполнения сюиты.", path)
		}
	}

	// 2. Инициализируем московскую временную зону (UTC+3) согласно МР ЕСИА
	loc, err := time.LoadLocation("Europe/Moscow")
	if err != nil {
		loc = time.FixedZone("MSK", 3*60*60)
	}
	suite.MskLocation = loc
	suite.Ctx = context.Background()
	suite.ClientID = "ESIA_GATEWAY"

	// 3. Инстанцируем изолированные провайдеры для прогона сценариев
	suite.NoPassProvider = &crypto.OpenSslGostProvider{
		KeyPath:  pathKeyNoPass,
		CertPath: pathCertNoPass,
	}
	suite.WithPassProvider = &crypto.OpenSslGostProvider{
		KeyPath:     pathKeyPass,
		CertPath:    pathCertPass,
		KeyPassword: keyPassword,
	}
}
func (suite *OpenSslGostTestSuite) SetupTest() {
	// подготовим запросы
	suite.authRequest = proxy.AuthRequest{
		ClientID:    suite.ClientID,
		Scope:       "fullname birthdate snils",
		RedirectURI: "https://my-app.com",
		State:       "test-session-state-auth",
		Timestamp:   proxy.NowTimestamp(),
	}

	suite.logoutRequest = proxy.LogoutRequest{
		ClientID:    suite.ClientID,
		RedirectURL: "https://my-app.com",
	}

	suite.tokenRequest = proxy.TokenRequest{
		ClientID:  suite.ClientID,
		State:     "test-token-exchange-state",
		Scope:     "fullname email",
		Timestamp: proxy.NowTimestamp(),
	}
}

// TestV1AuthEndpointSignature проверяет подпись v1: 4 параметра без разделителей, классический detached PKCS#7 / CMS
func (suite *OpenSslGostTestSuite) TestV1AuthEndpointSignature() {

	// Сборка строки по правилам ЕСИА v1: scope + timestamp + client_id + state
	v1StringToSign := suite.authRequest.ForV1Signature()

	sig, err := suite.NoPassProvider.SignPKCS7(suite.Ctx, v1StringToSign)
	suite.NoError(err, "SignPKCS7 v1 вернул неожиданную ошибку")

	decodedSig, err := base64.RawURLEncoding.DecodeString(sig)
	suite.NoError(err, "Ошибка декодирования Base64 PKCS#7")

	// Проверяем, что вывод является валидным двоичным контейнером (начинается с тега Sequence 0x30)
	var rawSequence asn1.RawValue
	_, err = asn1.Unmarshal(decodedSig, &rawSequence)
	suite.NoError(err, "Вывод SignPKCS7 не является валидным двоичным ASN.1/DER контейнером")

	// =========================================================================
	// КОНТРОЛЬ №1 — Внутренняя верификация через метод Provider.VerifyPKCS7
	// =========================================================================
	isValid, verifyErr := suite.NoPassProvider.VerifyPKCS7(suite.Ctx, v1StringToSign, sig)
	suite.NoError(verifyErr, "Внутренний метод VerifyPKCS7 вернул ошибку при верификации")
	suite.True(isValid, "Внутренний метод VerifyPKCS7 определил легитимную подпись как невалидную")

	// =========================================================================
	// КОНТРОЛЬ №2 — Внешняя эталонная валидация через системную утилиту OpenSSL CMS CLI
	// =========================================================================
	suite.verifyGostSignaturePKCS7(v1StringToSign, sig)
}

// TestV2AuthEndpointSignature проверяет подпись v2/ac: 5 параметров без разделителей, reversed raw ГОСТ
func (suite *OpenSslGostTestSuite) TestV2AuthEndpointSignature() {

	// Сборка строки по правилам ЕСИА v2/ac: client_id + scope + timestamp + state + redirect_uri
	v2StringToSign := suite.authRequest.ForV2Signature()

	sig, err := suite.NoPassProvider.SignRaw(suite.Ctx, v2StringToSign)
	suite.NoError(err, "SignRaw v2/ac вернул неожиданную ошибку")

	decodedSig, err := base64.RawURLEncoding.DecodeString(sig)
	suite.NoError(err, "Ошибка декодирования Base64 URL-safe")

	// Математическое утверждение: сырая ГОСТ подпись должна занимать ровно 64 байта
	suite.Len(decodedSig, 64, "Размер сырой подписи ГОСТ должен составлять ровно 64 байта")

	// валидируем подпись в OpenSSL
	suite.verifyGostSignatureRaw(v2StringToSign, sig)
}

// TestV3TokenEndpointSignature проверяет подпись v3/te: 4 параметра, классический detached PKCS#7 / CMS
func (suite *OpenSslGostTestSuite) TestV3TokenEndpointSignature() {
	suite.tokenRequest.Validate()

	// Сборка строки по правилам ЕСИА v3/te: scope + timestamp + client_id + state
	v3StringToSign := suite.tokenRequest.ForV1Signature()

	sig, err := suite.NoPassProvider.SignPKCS7(suite.Ctx, v3StringToSign)
	suite.NoError(err, "SignPKCS7 v3/te вернул неожиданную ошибку")

	decodedSig, err := base64.RawURLEncoding.DecodeString(sig)
	suite.NoError(err, "Ошибка декодирования Base64 PKCS#7")

	// Проверяем, что вывод является валидным двоичным контейнером (начинается с тега Sequence 0x30)
	var rawSequence asn1.RawValue
	_, err = asn1.Unmarshal(decodedSig, &rawSequence)
	suite.NoError(err, "Вывод SignPKCS7 не является валидным двоичным ASN.1/DER контейнером")

	// =========================================================================
	// КОНТРОЛЬ №1 — Внутренняя верификация через метод Provider.VerifyPKCS7
	// =========================================================================
	isValid, verifyErr := suite.NoPassProvider.VerifyPKCS7(suite.Ctx, v3StringToSign, sig)
	suite.NoError(verifyErr, "Внутренний метод VerifyPKCS7 вернул ошибку при верификации")
	suite.True(isValid, "Внутренний метод VerifyPKCS7 определил легитимную подпись как невалидную")

	// =========================================================================
	// КОНТРОЛЬ №2 — Внешняя эталонная валидация через системную утилиту OpenSSL CMS CLI
	// =========================================================================
	suite.verifyGostSignaturePKCS7(v3StringToSign, sig)
}

// TestV3TokenEndpointSignatureWPassword проверяет подпись v3/te: 4 параметра, классический detached PKCS#7 / CMS
// Сертификат с паролем
func (suite *OpenSslGostTestSuite) TestV3TokenEndpointSignatureWPassword() {
	suite.tokenRequest.Validate()

	// Сборка строки по правилам ЕСИА v3/te: scope + timestamp + client_id + state
	v3StringToSign := suite.tokenRequest.ForV1Signature()

	sig, err := suite.WithPassProvider.SignPKCS7(suite.Ctx, v3StringToSign)
	suite.NoError(err, "SignPKCS7 v3/te вернул неожиданную ошибку")

	decodedSig, err := base64.RawURLEncoding.DecodeString(sig)
	suite.NoError(err, "Ошибка декодирования Base64 PKCS#7")

	// Проверяем, что вывод является валидным двоичным контейнером (начинается с тега Sequence 0x30)
	var rawSequence asn1.RawValue
	_, err = asn1.Unmarshal(decodedSig, &rawSequence)
	suite.NoError(err, "Вывод SignPKCS7 не является валидным двоичным ASN.1/DER контейнером")

	// =========================================================================
	// КОНТРОЛЬ №1 — Внутренняя верификация зашифрованной подписи через Provider.VerifyPKCS7
	// Проверяет, что провайдер корректно разблокировал ca.key и связал EVP_PKEY ГОСТ контекст для верификатора
	// =========================================================================
	isValid, verifyErr := suite.WithPassProvider.VerifyPKCS7(suite.Ctx, v3StringToSign, sig)
	suite.NoError(verifyErr, "Внутренний зашифрованный метод VerifyPKCS7 вернул ошибку при верификации")
	suite.True(isValid, "Внутренний зашифрованный метод VerifyPKCS7 определил легитимную подпись как невалидную")

	// =========================================================================
	// КОНТРОЛЬ №2 — Внешняя эталонная валидация через системную утилиту OpenSSL CMS CLI
	// =========================================================================
	suite.verifyGostSignaturePKCS7WithPassword(v3StringToSign, sig)
}

// TestEncryptedKeyDecryption проверяет разбор зашифрованного контейнера ключа по паролю (KeyPassword)
func (suite *OpenSslGostTestSuite) TestEncryptedKeyDecryption() {
	testData := "fullname birthdate 2026.10.04 03:55:00 +0300 019110 session_state"

	// Проверяем, что провайдер WithPassProvider успешно расшифровывает ca.key в памяти
	sig, err := suite.WithPassProvider.SignRaw(suite.Ctx, testData)
	suite.NoError(err, "Не удалось расшифровать ключ по паролю или подписать payload")

	decoded, _ := base64.RawURLEncoding.DecodeString(sig)
	suite.Len(decoded, 64, "Подпись зашифрованным ключом должна занимать 64 байта")
}

// ======================================================
// функции проверки подписи используя OpenSSL
// ======================================================

// verifyGostSignatureRaw проверяет сырую (raw reversed) ГОСТ-подпись для ключей БЕЗ пароля.
// Подходит для верификации вывода эндпоинтов v2/ac и Logout.
func (suite *OpenSslGostTestSuite) verifyGostSignatureRaw(plainData, base64URLSig string) {

	// 1. Декодируем входящую Base64 URL-safe подпись обратно в бинарный вид
	decodedSig, err := base64.RawURLEncoding.DecodeString(base64URLSig)
	suite.NoError(err, "verifyGostSignatureRaw: ошибка Base64-декодирования")
	suite.Len(decodedSig, 64, "verifyGostSignatureRaw: размер сырой подписи ГОСТ 256 должен быть ровно 64 байта")

	// 2. ВОССТАНАВЛИВАЕМ ПРЯМОЙ ПОРЯДОК БАЙТ ДЛЯ ИНСТРУМЕНТАРИЯ OPENSSL
	// Так как шлюз возвращает инвертированную подпись по требованиям ЕСИА, для стандартного CLI разворачиваем её назад
	directSig := make([]byte, 64)
	copy(directSig, decodedSig)
	for i, j := 0, len(directSig)-1; i < j; i, j = i+1, j-1 {
		directSig[i], directSig[j] = directSig[j], directSig[i]
	}

	// Записываем бинарную прямую подпись во временный файл для передачи в OpenSSL
	tempDir := suite.T().TempDir()
	tmpSigFile := filepath.Join(tempDir, "gost_raw.sig")
	tmpDataFile := filepath.Join(tempDir, "plain_data.txt")

	suite.NoError(os.WriteFile(tmpSigFile, directSig, 0644))
	suite.NoError(os.WriteFile(tmpDataFile, []byte(plainData), 0644))

	// 3. МАТЕМАТИЧЕСКАЯ ВЕРИФИКАЦИЯ СЫРЫХ БАЙТ ЧЕРЕЗ СОВРЕМЕННЫЙ ИНСТРУМЕНТАРИЙ PKEYUTL
	// Флаг -certin сообщает OpenSSL, что в качестве ключа передан X.509 сертификат (.crt)
	// OpenSSL 3 сам извлечет открытый ключ и проверит подпись
	cmd := exec.CommandContext(suite.Ctx, "openssl", "pkeyutl",
		"-engine", "gost",
		"-verify",
		"-certin",
		"-inkey", pathCertNoPass, // Путь к нашему .crt файлу сертификата
		"-sigfile", tmpSigFile, // Файл восстановленной прямой подписи
		"-in", tmpDataFile, // Исходные подписанные данные
		"-rawin",                 // Обязательный флаг для работы с сырым текстом
		"-digest", "streebog256", // Алгоритм хэширования ГОСТ Р 34.11-2012
	)

	var stdout, stderr bytes.Buffer
	cmd.Stdout = &stdout
	cmd.Stderr = &stderr

	err = cmd.Run()

	// OpenSSL CLI при успешной математической проверке выводит в stdout "Signature Verified Successfully"
	suite.NoError(err, "OpenSSL CLI провалил верификацию сырой подписи через pkeyutl. Stderr: %s", stderr.String())
	suite.Contains(stdout.String(), "Signature Verified Successfully", "Открытый ключ сертификата не соответствует сформированной сырой подписи")
}

// verifyGostSignaturePKCS7 проверяет отсоединенный CMS/PKCS#7 контейнер для ключей БЕЗ пароля.
// Подходит для верификации вывода эндпоинта v3/te.
func (suite *OpenSslGostTestSuite) verifyGostSignaturePKCS7(plainData, base64URLSig string) {
	// 1. Декодируем PKCS#7 DER-контейнер
	decodedSig, err := base64.RawURLEncoding.DecodeString(base64URLSig)
	suite.NoError(err, "verifyGostSignaturePKCS7: ошибка Base64-декодирования")

	// Сбрасываем бинарную подпись и исходные данные во временные файлы
	tempDir := suite.T().TempDir()
	tmpSigFile := filepath.Join(tempDir, "go_v3.p7")
	tmpDataFile := filepath.Join(tempDir, "v3_plain.txt")
	suite.NoError(os.WriteFile(tmpSigFile, decodedSig, 0644))
	suite.NoError(os.WriteFile(tmpDataFile, []byte(plainData), 0644))

	// 2. ВЫЗЫВАЕМ OPENSSL CMS ДЛЯ СТРОГОГО АУДИТА СТРУКТУРЫ И ПОДПИСАНТА
	// Команда: openssl cms -verify -inform DER -in go_v3.p7 -content v3_plain.txt -signer ca_no_pass.crt -noverify
	cmd := exec.CommandContext(suite.Ctx, "openssl", "cms",
		"-verify",
		"-inform", "DER",
		"-in", tmpSigFile,
		"-content", tmpDataFile,
		"-signer", pathCertNoPass,
		"-noverify", // Отключаем проверку цепочки доверия до корневого удостоверяющего центра для тестового самоподписанного сертификата
	)

	var stdout, stderr bytes.Buffer
	cmd.Stdout = &stdout
	cmd.Stderr = &stderr

	err = cmd.Run()

	// Если структура CMS-пакета легитимна — OpenSSL выводит в stderr строку "Verification successful"
	suite.NoError(err, "OpenSSL CLI провалил аудит PKCS#7 подписи. Stderr: %s", stderr.String())
	suite.Contains(stderr.String(), "Verification successful", "Контейнер PKCS#7 поврежден или не соответствует сертификату")
}

// verifyGostSignaturePKCS7WithPassword проверяет отсоединенный CMS/PKCS#7 контейнер,
// сформированный с использованием зашифрованного паролем закрытого ключа.
// Поддерживает сквозной контроль деривации KeyPassword.
func (suite *OpenSslGostTestSuite) verifyGostSignaturePKCS7WithPassword(plainData, base64URLSig string) {

	// 1. Декодируем и сохраняем структуру во временные файлы
	decodedSig, err := base64.RawURLEncoding.DecodeString(base64URLSig)
	suite.NoError(err, "verifyGostSignaturePKCS7WithPassword: ошибка Base64-декодирования")

	tempDir := suite.T().TempDir()
	tmpSigFile := filepath.Join(tempDir, "go_encrypted_v3.p7")
	tmpDataFile := filepath.Join(tempDir, "v3_encrypted_plain.txt")
	suite.NoError(os.WriteFile(tmpSigFile, decodedSig, 0644))
	suite.NoError(os.WriteFile(tmpDataFile, []byte(plainData), 0644))

	// 2. ВЕРИФИКАЦИЯ КОНТЕЙНЕРА С ПОМОЩЬЮ ПРОИЗВОДСТВЕННОГО СЕРТИФИКАТА CA.CRT
	// Важно: Для верификации готовой подписи пароль и закрытый ключ уже не требуются,
	// но OpenSSL CLI проверит, что подпись была сгенерирована математически корректным ключом,
	// разблокированным по правильному паролю.
	cmd := exec.CommandContext(suite.Ctx, "openssl", "cms",
		"-verify",
		"-inform", "DER",
		"-in", tmpSigFile,
		"-content", tmpDataFile,
		"-signer", pathCertPass, // Публичный производственный сертификат (ca.crt)
		"-noverify",
	)

	var stdout, stderr bytes.Buffer
	cmd.Stdout = &stdout
	cmd.Stderr = &stderr

	err = cmd.Run()

	suite.NoError(err, "OpenSSL CLI провалил аудит зашифрованной подписи PKCS#7. Stderr: %s", stderr.String())
	suite.Contains(stderr.String(), "Verification successful", "Ошибка расшифрования или невалидная CMS-структура")
}
