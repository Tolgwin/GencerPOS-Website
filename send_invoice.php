<?php
// ============================================================
//  QNB eSolutions - e-Fatura Gönderim Scripti
// ============================================================
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

$streamContext = stream_context_create([
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
]);
$config['soap_options']['stream_context'] = $streamContext;

// -------------------------------------------------------
// YARDIMCI FONKSİYONLAR
// -------------------------------------------------------
function log_msg(string $type, string $msg): void
{
    $prefix = match ($type) {
        'ok'            => "\033[32m[OK]\033[0m",
        'error', 'hata' => "\033[31m[HATA]\033[0m",
        'info'          => "\033[36m[BİLGİ]\033[0m",
        'warn', 'uyari' => "\033[33m[UYARI]\033[0m",
        default         => "[LOG]",
    };
    echo $prefix . " " . $msg . "\n";
}

function create_soap_client(string $wsdl, array $options): SoapClient
{
    try {
        return new SoapClient($wsdl, $options);
    } catch (SoapFault $e) {
        log_msg('hata', "SOAP Client oluşturulamadı [{$wsdl}]: " . $e->getMessage());
        exit(1);
    }
}

function belgeGonder(SoapClient $connectorClient, string $xmlContent, array $config): ?object
{
    $xml = simplexml_load_string($xmlContent);
    $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $belgeNo = (string) $xml->xpath('//cbc:ID')[0];

    log_msg('info', "belgeGonder() çağrılıyor... BelgeNo: $belgeNo");

    $base64Xml = base64_encode($xmlContent);
    log_msg('info', "base64 uzunluğu: " . strlen($base64Xml) . " karakter");

    $ns = 'http://service.connector.uut.cs.com.tr/';

    // Her parametreyi ayrı SoapVar olarak tanımla
    $params = [
        new SoapVar($config['username'], XSD_STRING,  null, null, 'vergiTcKimlikNo', $ns),
        new SoapVar('FATURA',            XSD_STRING,  null, null, 'belgeTuru',       $ns),
        new SoapVar($belgeNo,            XSD_STRING,  null, null, 'belgeNo',         $ns),
        new SoapVar($base64Xml,          XSD_BASE64BINARY, null, null, 'belge',      $ns),
        new SoapVar($belgeNo,            XSD_STRING,  null, null, 'yerelBelgeNo',    $ns),
    ];

    // Wrapper element
    $wrapper = new SoapVar(
        $params,
        SOAP_ENC_OBJECT,
        null,
        null,
        'belgeGonder',
        $ns
    );

    try {
        $result = $connectorClient->__soapCall('belgeGonder', [$wrapper]);
        log_msg('ok', "belgeGonder başarılı!");
        print_r($result);
        return $result;
    } catch (SoapFault $e) {
        log_msg('hata', "belgeGonder başarısız: " . $e->getMessage());
        log_msg('info', "Son İstek:\n" . $connectorClient->__getLastRequest());
        log_msg('info', "Son Yanıt:\n" . $connectorClient->__getLastResponse());
        return null;
    }
}




// -------------------------------------------------------
// ADIM 1: UBL XML DOSYASINI OKU
// -------------------------------------------------------
log_msg('info', "UBL XML dosyası okunuyor: " . $config['ubl_xml_path']);

if (!file_exists($config['ubl_xml_path'])) {
    log_msg('hata', "Dosya bulunamadı: " . $config['ubl_xml_path']);
    exit(1);
}

$ublXmlContent = file_get_contents($config['ubl_xml_path']);

if (empty(trim($ublXmlContent))) {
    log_msg('hata', "XML dosyası boş.");
    exit(1);
}

libxml_use_internal_errors(true);
if (simplexml_load_string($ublXmlContent) === false) {
    foreach (libxml_get_errors() as $err) {
        log_msg('hata', "XML Hatası: " . trim($err->message));
    }
    libxml_clear_errors();
    exit(1);
}
libxml_clear_errors();

log_msg('ok', "UBL XML başarıyla okundu (" . strlen($ublXmlContent) . " byte).");

// -------------------------------------------------------
// ADIM 2: userService → wsLogin()
// -------------------------------------------------------
log_msg('info', "Gönderilen userId: [" . $config['username'] . "]");
log_msg('info', "Gönderilen password uzunluğu: " . strlen($config['password']) . " karakter");
log_msg('info', "userService bağlanılıyor...");

$userClient = create_soap_client($config['wsdl_user'], $config['soap_options']);
log_msg('ok', "userService SOAP Client hazır.");
log_msg('info', "wsLogin() çağrılıyor...");

try {
    $loginResponse = $userClient->wsLogin([
        'userId'   => $config['username'],
        'password' => $config['password'],
        'lang'     => 'tr',
    ]);

    log_msg('ok', "wsLogin başarılı!");
    echo "\n--- wsLogin Yanıtı ---\n";
    print_r($loginResponse);
    echo "----------------------\n\n";

    $cookies = method_exists($userClient, '__getCookies') ? $userClient->__getCookies() : [];
    if (!empty($cookies)) {
        log_msg('ok', "Session cookie alındı: " . json_encode($cookies));
    } else {
        log_msg('uyari', "Cookie boş geldi.");
    }

} catch (SoapFault $e) {
    log_msg('hata', "wsLogin başarısız: " . $e->getMessage());
    exit(1);
}

// -------------------------------------------------------
// ADIM 3: connectorService — Cookie Taşı
// -------------------------------------------------------
log_msg('info', "connectorService bağlanılıyor...");

$connectorClient = create_soap_client($config['wsdl_connector'], $config['soap_options']);

if (!empty($cookies)) {
    foreach ($cookies as $cookieName => $cookieValue) {
        $connectorClient->__setCookie(
            $cookieName,
            is_array($cookieValue) ? $cookieValue[0] : $cookieValue
        );
    }
    log_msg('ok', "Session cookie connectorService'e aktarıldı.");
}

log_msg('ok', "connectorService SOAP Client hazır.");

// -------------------------------------------------------
// ADIM 4: belgeGonder()
// -------------------------------------------------------
$gonderResponse = belgeGonder($connectorClient, $ublXmlContent, $config);
$ettn = $gonderResponse->ettn ?? null;

// -------------------------------------------------------
// ADIM 5: gidenBelgeDurumSorgula()
// -------------------------------------------------------
if ($ettn) {
    log_msg('info', "gidenBelgeDurumSorgula() çağrılıyor... ETTN: $ettn");
    try {
        $durumResponse = $connectorClient->__soapCall('gidenBelgeDurumSorgula', [['ettn' => $ettn]]);
        log_msg('ok', "gidenBelgeDurumSorgula başarılı!");
        echo "\n--- gidenBelgeDurumSorgula Yanıtı ---\n";
        print_r($durumResponse);
        echo "--------------------------------------\n\n";
    } catch (SoapFault $e) {
        log_msg('hata', "gidenBelgeDurumSorgula başarısız: " . $e->getMessage());
    }
} else {
    log_msg('uyari', "ETTN alınamadığı için durum sorgulanamayacak.");
}

// -------------------------------------------------------
// ADIM 6: logout()
// -------------------------------------------------------
log_msg('info', "logout() çağrılıyor...");

try {
    $userClient->logout([]);
    log_msg('ok', "Oturum kapatıldı.");
} catch (SoapFault $e) {
    log_msg('uyari', "Logout başarısız (kritik değil): " . $e->getMessage());
}

echo "\n\033[32m✓ İşlem tamamlandı.\033[0m\n";
