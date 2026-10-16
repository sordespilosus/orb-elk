package crypto

import (
	"context"
	"encoding/base64"
	"errors"
	"fmt"
	"unsafe"
)

/*
#cgo pkg-config: libcrypto
#include <openssl/evp.h>
#include <openssl/pem.h>
#include <openssl/cms.h>
#include <openssl/err.h>

#include <stdio.h>

// 1. Нативная функция для генерации классической отсоединенной PKCS#7 подписи (для v3/te) с паролем key_password
static int sign_pkcs7_native(const char* data, int data_len, const char* cert_path, const char* key_path, const char* key_password, unsigned char** out_sig, int* out_len, char* err_buf, int err_len) {
    BIO *in_bio = NULL, *cert_bio = NULL, *key_bio = NULL, *out_bio = NULL;
    X509 *cert = NULL;
    EVP_PKEY *pkey = NULL;
    CMS_ContentInfo *cms = NULL;
    unsigned int flags = CMS_DETACHED | CMS_BINARY | CMS_NOSMIMECAP;
    int success = 0;
    BUF_MEM *bptr = NULL;

    OpenSSL_add_all_algorithms();
    ERR_load_crypto_strings();

    in_bio = BIO_new_mem_buf((void*)data, data_len);
    cert_bio = BIO_new_file(cert_path, "r");
    key_bio = BIO_new_file(key_path, "r");

    if (!in_bio || !cert_bio || !key_bio) {
        snprintf(err_buf, err_len, "Ошибка инициализации BIO буферов OpenSSL");
        goto cleanup;
    }

    cert = PEM_read_bio_X509(cert_bio, NULL, NULL, NULL);

    // Передаем key_password в качестве аргумента. Если он равен NULL или пустой, OpenSSL отработает стандартно
    pkey = PEM_read_bio_PrivateKey(key_bio, NULL, NULL, (void*)key_password);

    if (!cert || !pkey) {
        snprintf(err_buf, err_len, "Ошибка чтения ГОСТ-сертификата или закрытого ключа (возможно неверный пароль)");
        goto cleanup;
    }

    cms = CMS_sign(cert, pkey, NULL, in_bio, flags);
    if (!cms) {
        unsigned long err = ERR_get_error();
        ERR_error_string_n(err, err_buf, err_len);
        goto cleanup;
    }

    out_bio = BIO_new(BIO_s_mem());
    if (!i2d_CMS_bio(out_bio, cms)) {
        snprintf(err_buf, err_len, "Ошибка сериализации CMS в DER формат");
        goto cleanup;
    }

    BIO_get_mem_ptr(out_bio, &bptr);
    *out_sig = (unsigned char*)malloc(bptr->length);
    memcpy(*out_sig, bptr->data, bptr->length);
    *out_len = bptr->length;
    success = 1;

cleanup:
    if (cms) CMS_ContentInfo_free(cms);
    if (cert) X509_free(cert);
    if (pkey) EVP_PKEY_free(pkey);
    if (in_bio) BIO_free(in_bio);
    if (cert_bio) BIO_free(cert_bio);
    if (key_bio) BIO_free(key_bio);
    if (out_bio) BIO_free(out_bio);
    return success;
}

// 2. Нативная функция для генерации «сырой» ГОСТ подписи (для v2/ac) с паролем key_password
static int sign_raw_native(const char* data, int data_len, const char* key_path, const char* key_password, unsigned char** out_sig, int* out_len, char* err_buf, int err_len) {
    BIO *key_bio = NULL;
    EVP_PKEY *pkey = NULL;
    EVP_MD_CTX *mctx = NULL;
    int success = 0;
    size_t sig_len = 0;

    OpenSSL_add_all_algorithms();
    ERR_load_crypto_strings();

    key_bio = BIO_new_file(key_path, "r");
    if (!key_bio) {
        snprintf(err_buf, err_len, "Ошибка открытия файла закрытого ключа");
        goto cleanup;
    }

    // Передаем key_password для разблокировки контейнера приватного ключа
    pkey = PEM_read_bio_PrivateKey(key_bio, NULL, NULL, (void*)key_password);
    if (!pkey) {
        snprintf(err_buf, err_len, "Ошибка парсинга закрытого ключа ГОСТ (возможно неверный пароль)");
        goto cleanup;
    }

    mctx = EVP_MD_CTX_new();
    if (!mctx) {
        snprintf(err_buf, err_len, "Ошибка создания EVP контекста");
        goto cleanup;
    }

    // Инициализируем подпись. Движок GOST автоматически подставит нужный дайджест (например, grasshopper/streebog)
    if (EVP_DigestSignInit(mctx, NULL, NULL, NULL, pkey) <= 0) {
        unsigned long err = ERR_get_error();
        ERR_error_string_n(err, err_buf, err_len);
        goto cleanup;
    }

    if (EVP_DigestSignUpdate(mctx, data, data_len) <= 0) {
        snprintf(err_buf, err_len, "Ошибка обновления хэша данных");
        goto cleanup;
    }

    // Первым вызовом определяем необходимый размер буфера подписи
    if (EVP_DigestSignFinal(mctx, NULL, &sig_len) <= 0) {
        snprintf(err_buf, err_len, "Ошибка определения размера сырой подписи");
        goto cleanup;
    }

    *out_sig = (unsigned char*)malloc(sig_len);
    if (EVP_DigestSignFinal(mctx, *out_sig, &sig_len) <= 0) {
        snprintf(err_buf, err_len, "Ошибка вычисления итоговой сырой подписи");
        goto cleanup;
    }

    *out_len = (int)sig_len;
    success = 1;

cleanup:
    if (mctx) EVP_MD_CTX_free(mctx);
    if (pkey) EVP_PKEY_free(pkey);
    if (key_bio) BIO_free(key_bio);
    return success;
}

// Нативная функция OpenSSL для верификации отсоединенной подписи CMS/PKCS#7
static int verify_pkcs7_native(const char* data, int data_len, const char* cert_path, const char* sig, int sig_len, char* err_buf, int err_len) {
    BIO *in_bio = NULL, *cert_bio = NULL, *sig_bio = NULL;
    X509 *cert = NULL;
    X509_STORE *store = NULL;
    CMS_ContentInfo *cms = NULL;
    unsigned int flags = CMS_DETACHED | CMS_BINARY | CMS_NOSMIMECAP | CMS_NO_SIGNER_CERT_VERIFY;
    int success = 0;

    OpenSSL_add_all_algorithms();
    ERR_load_crypto_strings();

    in_bio = BIO_new_mem_buf((void*)data, data_len);
    cert_bio = BIO_new_file(cert_path, "r");
    sig_bio = BIO_new_mem_buf((void*)sig, sig_len);

    if (!in_bio || !cert_bio || !sig_bio) {
        snprintf(err_buf, err_len, "Ошибка инициализации BIO буферов верификации");
        goto cleanup;
	}

    cert = PEM_read_bio_X509(cert_bio, NULL, NULL, NULL);
    if (!cert) {
        snprintf(err_buf, err_len, "Ошибка чтения публичного сертификата верификации");
        goto cleanup;
    }

    // Читаем входящую подпись из DER-формата
    cms = d2i_CMS_bio(sig_bio, NULL);
    if (!cms) {
        snprintf(err_buf, err_len, "Ошибка демаршалинга входящей подписи в CMS структуру");
        goto cleanup;
    }

    store = X509_STORE_new();
    if (!store) goto cleanup;

    X509_STORE_add_cert(store, cert);

    // Официальный вызов верификации OpenSSL
    if (CMS_verify(cms, NULL, NULL, in_bio, NULL, flags) > 0) {
        success = 1;
    } else {
        unsigned long err = ERR_get_error();
        ERR_error_string_n(err, err_buf, err_len);
    }

cleanup:
    if (cms) CMS_ContentInfo_free(cms);
    if (cert) X509_free(cert);
    if (store) X509_STORE_free(store);
    if (in_bio) BIO_free(in_bio);
    if (cert_bio) BIO_free(cert_bio);
    if (sig_bio) BIO_free(sig_bio);

	return success;
}
*/
import "C"

type OpenSslGostProvider struct {
	Provider
	KeyPath     string
	KeyPassword string
	CertPath    string
}

// SignRaw реализует логику подписи для ЕСИА v2/ac (Сырая ГОСТ-подпись с разворотом байт)
func (p *OpenSslGostProvider) SignRaw(ctx context.Context, data string) (string, error) {
	if p.KeyPath == "" {
		return "", errors.New("openssl native provider требует OPENSSL_KEY_PATH")
	}

	cData := C.CString(data)
	defer C.free(unsafe.Pointer(cData))

	cKeyPath := C.CString(p.KeyPath)
	defer C.free(unsafe.Pointer(cKeyPath))

	// Обрабатываем пароль: если пустой, передаем NULL в C-функцию
	var cKeyPassword *C.char
	if p.KeyPassword != "" {
		cKeyPassword = C.CString(p.KeyPassword)
		defer C.free(unsafe.Pointer(cKeyPassword))
	}
	errBuf := make([]byte, 512)
	cErrBuf := (*C.char)(unsafe.Pointer(&errBuf[0]))

	var outSig *C.uchar
	var outLen C.int

	ok := C.sign_raw_native(
		cData,
		C.int(len(data)),
		cKeyPath,
		cKeyPassword,
		&outSig,
		&outLen,
		cErrBuf,
		C.int(len(errBuf)),
	)
	if ok != 1 {
		errStr := C.GoString(cErrBuf)
		if errStr == "" {
			errStr = "Неизвестная ошибка"
		}
		return "", fmt.Errorf("ошибка вычисления PKCS7 подписи: %s", errStr)
	}

	rawBytes := C.GoBytes(unsafe.Pointer(outSig), outLen)
	C.free(unsafe.Pointer(outSig))

	// ВАЖНО: Разворачиваем байтовый массив подписи в обратном порядке (Reversed), как требует ЕСИА v2/ac
	for i, j := 0, len(rawBytes)-1; i < j; i, j = i+1, j-1 {
		rawBytes[i], rawBytes[j] = rawBytes[j], rawBytes[i]
	}

	// Возвращаем URL-Safe Base64 БЕЗ знаков заполнения (padding '=')
	return base64.RawURLEncoding.EncodeToString(rawBytes), nil
}

// SignPKCS7 реализует классическую detached PKCS#7 подпись для ЕСИА v3/te и верификации v1 ИС
func (p *OpenSslGostProvider) SignPKCS7(ctx context.Context, data string) (string, error) {
	if p.KeyPath == "" || p.CertPath == "" {
		return "", errors.New("openssl native provider требует KEY и CERT пути")
	}

	cData := C.CString(data)
	defer C.free(unsafe.Pointer(cData))

	cCertPath := C.CString(p.CertPath)
	defer C.free(unsafe.Pointer(cCertPath))

	cKeyPath := C.CString(p.KeyPath)
	defer C.free(unsafe.Pointer(cKeyPath))

	// Обрабатываем пароль для PKCS#7
	var cKeyPassword *C.char
	if p.KeyPassword != "" {
		cKeyPassword = C.CString(p.KeyPassword)
		defer C.free(unsafe.Pointer(cKeyPassword))
	}
	errBuf := make([]byte, 512)
	cErrBuf := (*C.char)(unsafe.Pointer(&errBuf[0]))

	var outSig *C.uchar
	var outLen C.int

	ok := C.sign_pkcs7_native(
		cData,
		C.int(len(data)),
		cCertPath,
		cKeyPath,
		cKeyPassword,
		&outSig,
		&outLen,
		cErrBuf,
		C.int(len(errBuf)),
	)
	if ok != 1 {
		errStr := C.GoString(cErrBuf)
		if errStr == "" {
			errStr = "Неизвестная ошибка"
		}
		return "", fmt.Errorf("ошибка вычисления PKCS7 подписи: %s", errStr)
	}

	goBytes := C.GoBytes(unsafe.Pointer(outSig), outLen)
	C.free(unsafe.Pointer(outSig))

	// Возвращаем URL-Safe Base64 БЕЗ знаков заполнения (padding '=')
	return base64.RawURLEncoding.EncodeToString(goBytes), nil
}

// VerifyPKCS7 декодирует Base64 URL-safe подпись и математически проверяет её через OpenSSL CMS_verify
func (p *OpenSslGostProvider) VerifyPKCS7(ctx context.Context, data string, base64URLSig string) (bool, error) {
	if p.CertPath == "" {
		return false, errors.New("openssl native provider требует CertPath для верификации")
	}

	// 1. Декодируем входящую подпись в бинарный DER-формат
	sigBytes, err := base64.RawURLEncoding.DecodeString(base64URLSig)
	if err != nil {
		return false, fmt.Errorf("ошибка декодирования Base64 подписи: %w", err)
	}

	// 2. Подготавливаем безопасные указатели CGO для строк данных и путей
	cData := C.CString(data)
	defer C.free(unsafe.Pointer(cData))

	cCertPath := C.CString(p.CertPath)
	defer C.free(unsafe.Pointer(cCertPath))

	cSigBytes := (*C.char)(unsafe.Pointer(&sigBytes[0]))

	errBuf := make([]byte, 512)
	cErrBuf := (*C.char)(unsafe.Pointer(&errBuf[0]))

	// 3. Вызываем нативный метод верификации
	ok := C.verify_pkcs7_native(
		cData,
		C.int(len(data)),
		cCertPath,
		cSigBytes,
		C.int(len(sigBytes)),
		cErrBuf,
		C.int(len(errBuf)),
	)
	if ok != 1 {
		errStr := C.GoString(cErrBuf)
		if errStr == "" {
			errStr = "Неизвестная ошибка"
		}
		return false, fmt.Errorf("математическая проверка подписи провалена: %s", errStr)
	}

	return true, nil
}
