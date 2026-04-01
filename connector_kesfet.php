<?php
// connector_kesfet.php

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

// ── 1. Login & Cookie al ──────────────────────────────────────
$userClient = new SoapClient($userWsdl, $soapOpts);
$userClient->wsLogin([
    'userId' => $userId,
    'password' => $password,
    'lang' => $lang,
]);

$headers = $userClient->__getLastResponseHeaders();
preg_match('/CSAPSESSIONID=([^;]+)/i', $headers, $m);
$sessionId = $m[1] ?? null;

if (!$sessionId) {
    echo "❌ Session alınamadı!\n";
    exit;
}
echo "🔑 CSAPSESSIONID : $sessionId\n\n";

// ── 2. connectorService'e cookie ile bağlan ───────────────────
$connClient = new SoapClient($connectorWsdl, array_merge($soapOpts, [
    'cookie_file' => tempnam(sys_get_temp_dir(), 'soap'),
]));

// Cookie'yi HTTP header olarak enjekte et
$cookieCtx = stream_context_create([
    'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sessionId],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'cafile' => $certPath,
    ],
]);

$connClient = new SoapClient($connectorWsdl, array_merge($soapOpts, [
    'stream_context' => $cookieCtx,
]));

// ── 3. Mevcut metodları listele ───────────────────────────────
echo "── connectorService Metodları ──\n";
$functions = $connClient->__getFunctions();
foreach ($functions as $fn) {
    echo "  • $fn\n";
}

echo "\n── connectorService Tipleri ──\n";
$types = $connClient->__getTypes();
foreach (array_slice($types, 0, 20) as $t) {  // İlk 20 tip
    echo "  » $t\n";
}

// ── 4. Logout ─────────────────────────────────────────────────
try {
    $userClient->logout(['userId' => $userId]);
    echo "\n✅ Logout başarılı\n";
} catch (SoapFault $e) {
    echo "\n⚠️  Logout: " . $e->getMessage() . "\n";
}

echo '</pre>';
