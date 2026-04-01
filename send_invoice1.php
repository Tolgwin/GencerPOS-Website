<?php
// send_invoice.php - Tam sürüm

// ── Renk sabitleri ──────────────────────────────────────────────
define('C_INFO',  "\033[36m[BİLGİ]\033[0m ");
define('C_OK',    "\033[32m[OK]\033[0m ");
define('C_ERR',   "\033[31m[HATA]\033[0m ");
define('C_WARN',  "\033[33m[UYARI]\033[0m ");

function log_msg(string $type, string $msg): void {
    $prefix = match($type) {
        'ok'    => C_OK,
        'hata'  => C_ERR,
        'uyari' => C_WARN,
        default => C_INFO,
    };
    echo $prefix . $msg . PHP_EOL;
}

// ── Yapılandırma ────────────────────────────────────────────────
$config = [
    'username'     => '3930311899',
    'password'     => 'Tolga3583.',
    'xml_file'     => __DIR__ . '/invoice.xml',
    'user_wsdl'    => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl',
    'connector_wsdl' => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl',
    'connector_url'  => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService',
];

// ── XML Oku ─────────────────────────────────────────────────────
log_msg('info', "UBL XML dosyası okunuyor: {$config['xml_file']}");
if (!file_exists($config['xml_file'])) {
    log_msg('hata', "Dosya bulunamadı: {$config['xml_file']}");
    exit(1);
}
$xmlContent = file_get_contents($config['xml_file']);
log_msg('ok', "UBL XML başarıyla okundu (" . strlen($xmlContent) . " byte).");

// ── BelgeNo al ──────────────────────────────────────────────────
$xmlObj = simplexml_load_string($xmlContent);
$xmlObj->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
$belgeNo = (string) $xmlObj->xpath('//cbc:ID')[0];

// ── SOAP Ortak Seçenekler ───────────────────────────────────────
$soapOptions = [
    'trace'              => true,
    'exceptions'         => true,
    'cache_wsdl'         => WSDL_CACHE_NONE,
    'connection_timeout' => 30,
    'encoding'           => 'UTF-8',
];

// ── 1. Login ────────────────────────────────────────────────────
// ── 1. Login ────────────────────────────────────────────────────
log_msg('info', "Gönderilen userId: [{$config['username']}]");
log_msg('info', "Gönderilen password uzunluğu: " . strlen($config['password']) . " karakter");
log_msg('info', "userService bağlanılıyor...");

try {
    $userClient = new SoapClient($config['user_wsdl'], $soapOptions);
    log_msg('ok', "userService SOAP Client hazır.");

    log_msg('info', "wsLogin() çağrılıyor...");
    $loginResult = $userClient->wsLogin([
        'userId'   => $config['username'],
        'password' => $config['password'],
        'lang'     => 'tr',   // ← EKLENDİ
    ]);
    log_msg('ok', "wsLogin başarılı!");
    echo "--- wsLogin Yanıtı ---\n";
    print_r($loginResult);
    echo "----------------------\n";

} catch (SoapFault $e) {
    log_msg('hata', "wsLogin başarısız: " . $e->getMessage());
    log_msg('info', "Son İstek:\n" . $userClient->__getLastRequest());
    exit(1);
}


// ── Cookie al ───────────────────────────────────────────────────
$cookieHeader = '';
if (preg_match_all('/Set-Cookie:\s*([^;]+);/i', $userClient->__getLastResponseHeaders(), $matches)) {
    $cookies = array_map('trim', $matches[1]);
    $cookieHeader = implode('; ', $cookies);
    log_msg('ok', "Session cookie alındı: $cookieHeader");
} else {
    log_msg('hata', "Session cookie alınamadı. Yanıt başlıklarını kontrol edin.");
    log_msg('info', "Son İstek:\n" . $userClient->__getLastRequest());
    log_msg('info', "Son Yanıt:\n" . $userClient->__getLastResponse());
    exit(1);
}


// ── 2. connectorService ─────────────────────────────────────────
log_msg('info', "connectorService bağlanılıyor...");

try {
    $connectorClient = new SoapClient($config['connector_wsdl'], array_merge($soapOptions, [
        'stream_context' => stream_context_create([
            'http' => [
                'header' => "Cookie: $cookieHeader\r\n",
            ],
        ]),
    ]));
    log_msg('ok', "Session cookie connectorService'e aktarıldı.");
    log_msg('ok', "connectorService SOAP Client hazır.");

} catch (SoapFault $e) {
    log_msg('hata', "connectorService bağlantı hatası: " . $e->getMessage());
    exit(1);
}

// ── 3. belgeGonder — cURL ile HAM HTTP ─────────────────────────
log_msg('info', "belgeGonder() çağrılıyor... BelgeNo: $belgeNo");

$base64Xml = base64_encode($xmlContent);
log_msg('info', "base64 uzunluğu: " . strlen($base64Xml) . " karakter");

$soapEnvelope = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:con="http://service.connector.uut.cs.com.tr/">
  <soapenv:Header/>
  <soapenv:Body>
    <con:belgeGonder>
      <vergiTcKimlikNo>' . $config['username'] . '</vergiTcKimlikNo>
      <belgeTuru>FATURA_UBL</belgeTuru>
      <belgeNo>' . htmlspecialchars($belgeNo) . '</belgeNo>
      <veri>' . $base64Xml . '</veri>
      <belgeHash>' . strtoupper(md5($xmlContent)) . '</belgeHash>
      <mimeType>application/xml</mimeType>
      <belgeVersiyon>1.0</belgeVersiyon>
      <erpKodu></erpKodu>
      <alanEtiket></alanEtiket>
      <gonderenEtiket></gonderenEtiket>
      <xsltAdi></xsltAdi>
      <xsltVeri></xsltVeri>
      <subeKodu></subeKodu>
    </con:belgeGonder>
  </soapenv:Body>
</soapenv:Envelope>';


log_msg('info', "Gönderilen SOAP Envelope (ilk 500 karakter):\n" . substr($soapEnvelope, 0, 500));

// cURL ile gönder — PHP SOAP serializer tamamen devre dışı
$ch = curl_init($config['connector_url']);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $soapEnvelope,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: text/xml; charset=UTF-8',
        'SOAPAction: "http://service.connector.uut.cs.com.tr/belgeGonder"',
        'Cookie: ' . $cookieHeader,
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 30,
]);

$curlResponse = curl_exec($ch);
$curlError    = curl_error($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

log_msg('info', "HTTP Durum Kodu: $httpCode");

if ($curlError) {
    log_msg('hata', "cURL hatası: $curlError");
    exit(1);
}

log_msg('info', "Ham Yanıt:\n$curlResponse");

// ── Yanıt Parse ─────────────────────────────────────────────────
$dom = new DOMDocument();
$dom->loadXML($curlResponse);
$xpath = new DOMXPath($dom);

// Önce fault kontrolü
$faultNode = $xpath->query('//faultstring');
if ($faultNode->length > 0) {
    log_msg('hata', "belgeGonder başarısız: " . $faultNode->item(0)->nodeValue);
} else {
    // Tüm Body içeriğini göster
    $bodyNodes = $xpath->query('//*[local-name()="Body"]//*');
    log_msg('info', "Yanıt içindeki tüm node'lar:");
    foreach ($bodyNodes as $node) {
        echo "  <" . $node->localName . "> = " . trim($node->nodeValue) . PHP_EOL;
    }

    // ETTN veya return veya herhangi bir değer tutan node
    $resultNode = $xpath->query(
        '//*[local-name()="ettn"]
        | //*[local-name()="return"]
        | //*[local-name()="belgeGonderResponse"]
        | //*[local-name()="belgeGonderResult"]'
    );

    if ($resultNode->length > 0) {
        $ettn = trim($resultNode->item(0)->nodeValue);
        log_msg('ok', "belgeGonder başarılı! ETTN/Sonuç: $ettn");
    } else {
        log_msg('ok', "belgeGonder başarılı! Ham yanıt: $curlResponse");
    }
}


// ── 4. Logout ───────────────────────────────────────────────────
log_msg('info', "logout() çağrılıyor...");

try {
    $userClient->logout([]);
    log_msg('ok', "Oturum kapatıldı.");
} catch (SoapFault $e) {
    log_msg('uyari', "Logout başarısız (kritik değil): " . $e->getMessage());
}

echo "\n\033[32m✓ İşlem tamamlandı.\033[0m\n";
