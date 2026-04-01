<?php
// seri_listele.php

$certPath = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$userId = '3930311899';
$password = 'vi39mmkgww0301';
$lang = 'tr';
$userWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl';
$connectorWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?wsdl';

echo '<pre>';

$ctx = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'cafile' => $certPath,
    ]
]);

$soapOpts = [
    'trace' => true,
    'exceptions' => true,
    'cache_wsdl' => WSDL_CACHE_NONE,
    'stream_context' => $ctx,
    'encoding' => 'UTF-8',
];

// ── Login ──
$userClient = new SoapClient($userWsdl, $soapOpts);
$userClient->wsLogin(['userId' => $userId, 'password' => $password, 'lang' => $lang]);
preg_match('/CSAPSESSIONID=([^;]+)/i', $userClient->__getLastResponseHeaders(), $m);
$sessionId = $m[1] ?? null;
echo "🔑 Session : $sessionId\n\n";

$cookieCtx = stream_context_create([
    'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sessionId],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $certPath],
]);
$conn = new SoapClient($connectorWsdl, array_merge($soapOpts, ['stream_context' => $cookieCtx]));

// ────────────────────────────────────────────────────────────
// 1. seriTanimDegerleriniGetir
// ────────────────────────────────────────────────────────────
echo "── seriTanimDegerleriniGetir ──\n";
try {
    $seriResp = $conn->seriTanimDegerleriniGetir([
        'vknTckn' => $userId,
        'belgeTuru' => 'FATURA',   // FATURA | IRSALIYE
    ]);
    echo "✅ Seri Listesi:\n";
    print_r($seriResp);
} catch (SoapFault $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo htmlspecialchars($conn->__getLastResponse()) . "\n";
}

echo "\n";

// ────────────────────────────────────────────────────────────
// 2. faturaNoUret — WSDL'den gerçek parametre adlarını al
// ────────────────────────────────────────────────────────────
echo "── faturaNoUret Struct Tanımı ──\n";
$types = $conn->__getTypes();
foreach ($types as $type) {
    if (stripos($type, 'struct faturaNoUret ') === 0) {
        echo $type . "\n";
    }
}

// ────────────────────────────────────────────────────────────
// 3. kontorBilgisiGetir — Kontor durumu
// ────────────────────────────────────────────────────────────
echo "\n── Kontor Bilgisi ──\n";
try {
    $kontorResp = $conn->kontorBilgisiGetir([
        'vknTckn' => $userId,
    ]);
    print_r($kontorResp);
} catch (SoapFault $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    echo htmlspecialchars($conn->__getLastResponse()) . "\n";
}

// ── Logout ──
try {
    $userClient->logout(['userId' => $userId]);
    echo "\n✅ Logout\n";
} catch (SoapFault $e) {
    echo "\n⚠️ Logout: " . $e->getMessage() . "\n";
}

echo '</pre>';
