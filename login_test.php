<?php
// login_test.php

$certPath = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$wsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl';

// ── Buraya QNB'den aldığın bilgileri gir ──
$userId = '3930311899';   // ← değiştir
$password = 'vi39mmkgww0301';           // ← değiştir
$lang = 'tr';

echo '<pre>';

try {
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'cafile' => $certPath,
        ]
    ]);

    $client = new SoapClient($wsdl, [
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'stream_context' => $ctx,
        'encoding' => 'UTF-8',
    ]);

    echo "── wsLogin İsteği ──\n";
    $response = $client->wsLogin([
        'userId' => $userId,
        'password' => $password,
        'lang' => $lang,
    ]);

    echo "✅ Login başarılı!\n\n";

    // ── Response içeriği ──
    echo "── Response ──\n";
    print_r($response);

    // ── HTTP Response Headers (Session Cookie burada) ──
    $responseHeaders = $client->__getLastResponseHeaders();
    echo "\n── Response Headers ──\n";
    echo $responseHeaders . "\n";

    // ── Session ID'yi yakala ──
    if (preg_match('/Set-Cookie:\s*JSESSIONID=([^;]+)/i', $responseHeaders, $m)) {
        echo "🔑 JSESSIONID : " . $m[1] . "\n";
    } else {
        echo "ℹ️  Cookie header bulunamadı (farklı auth yöntemi olabilir)\n";
    }

    // ── Gönderilen & Gelen Ham SOAP ──
    echo "\n── Gönderilen SOAP XML ──\n";
    echo htmlspecialchars($client->__getLastRequest()) . "\n";

    echo "\n── Gelen SOAP XML ──\n";
    echo htmlspecialchars($client->__getLastResponse()) . "\n";

} catch (SoapFault $e) {
    echo "❌ SOAP Hatası  : " . $e->getMessage() . "\n";
    echo "📋 Fault Code   : " . ($e->faultcode ?? '-') . "\n";
    echo "📋 Fault Detail : " . print_r($e->detail ?? '-', true) . "\n\n";

    echo "── Gönderilen SOAP XML ──\n";
    echo htmlspecialchars($client->__getLastRequest() ?? '') . "\n";

    echo "\n── Gelen SOAP XML ──\n";
    echo htmlspecialchars($client->__getLastResponse() ?? '') . "\n";
}

echo '</pre>';
