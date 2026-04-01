<?php
// wsdl_listele.php

$certPath = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$baseUrl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws';

$userId = '3930311899';
$password = 'vi39mmkgww0301';
$lang = 'tr';

// ── Bilinen WSDL endpoint'leri ──
$services = [
    'userService' => $baseUrl . '/userService?wsdl',
    'earchiveService' => $baseUrl . '/earchiveService?wsdl',
    'einvoiceService' => $baseUrl . '/einvoiceService?wsdl',
    'dispatchService' => $baseUrl . '/dispatchService?wsdl',
    'eledgerService' => $baseUrl . '/eledgerService?wsdl',
];

echo '<pre>';

// ── 1. Login & Cookie al ──
$ctx = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'cafile' => $certPath,
    ]
]);

$userClient = new SoapClient($services['userService'], [
    'trace' => true,
    'exceptions' => true,
    'cache_wsdl' => WSDL_CACHE_NONE,
    'stream_context' => $ctx,
    'encoding' => 'UTF-8',
]);

$userClient->wsLogin([
    'userId' => $userId,
    'password' => $password,
    'lang' => $lang,
]);

// Cookie'yi yakala
$headers = $userClient->__getLastResponseHeaders();
preg_match('/CSAPSESSIONID=([^;]+)/i', $headers, $m);
$sessionId = $m[1] ?? null;

if (!$sessionId) {
    echo "❌ Session alınamadı!\n";
    exit;
}

echo "🔑 CSAPSESSIONID : $sessionId\n\n";

// ── 2. Her servisi tara ──
$cookieHeader = new SoapHeader(
    'http://service.csap.cs.com.tr/',
    'Cookie',
    'CSAPSESSIONID=' . $sessionId
);

echo "── Mevcut Servisler & Metodlar ──\n\n";

foreach ($services as $name => $wsdl) {
    echo "📦 $name\n";
    echo str_repeat('─', 40) . "\n";

    // WSDL erişilebilir mi?
    $ch = curl_init($wsdl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CAINFO => $certPath,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Cookie: CSAPSESSIONID=' . $sessionId],
    ]);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $code !== 200) {
        echo "  ❌ Erişilemiyor (HTTP $code" . ($err ? " / $err" : '') . ")\n\n";
        continue;
    }

    try {
        $client = new SoapClient($wsdl, [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => $ctx,
            'encoding' => 'UTF-8',
        ]);

        $functions = $client->__getFunctions();
        foreach ($functions as $fn) {
            echo "  • $fn\n";
        }
    } catch (SoapFault $e) {
        echo "  ⚠️  WSDL parse hatası: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo '</pre>';
