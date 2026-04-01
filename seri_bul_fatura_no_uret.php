<?php
// seri_bul_fatura_no_uret.php

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
// 1. seriTanimDegerleriniGetir — Struct'ı önce öğren
// ────────────────────────────────────────────────────────────
echo "── seriTanimDegerleriniGetir Struct ──\n";
$types = $conn->__getTypes();
foreach ($types as $type) {
    if (stripos($type, 'struct seriTanim') === 0) {
        echo $type . "\n";
    }
}
echo "\n";

// ── Parametre kombinasyonlarını dene ──
echo "── seriTanimDegerleriniGetir Denemeleri ──\n";

$denemeler = [
    'Sadece vknTckn' => ['vknTckn' => $userId],
    'vknTckn + belgeTuru FATURA' => ['vknTckn' => $userId, 'belgeTuru' => 'FATURA'],
    'vknTckn + tip FATURA' => ['vknTckn' => $userId, 'tip' => 'FATURA'],
    'vergiTcKimlikNo' => ['vergiTcKimlikNo' => $userId],
    'Boş parametre' => [],
];

foreach ($denemeler as $aciklama => $params) {
    echo "  → Deneme: [$aciklama]\n";
    try {
        $resp = $conn->seriTanimDegerleriniGetir($params);
        echo "  ✅ BAŞARILI!\n";
        print_r($resp);
        echo "\n";
        break; // Başarılı olunca dur
    } catch (SoapFault $e) {
        echo "  ❌ " . $e->getMessage() . "\n\n";
    }
}

// ────────────────────────────────────────────────────────────
// 2. faturaNoUret — Bilinen seri kodlarını dene
// ────────────────────────────────────────────────────────────
echo "── faturaNoUret Denemeleri ──\n";

// GİB standart test seri kodları
$seriKodlari = ['TST', 'TES', 'TEST', 'ABC', 'FAT', 'INV', 'GIB', 'EFT'];

foreach ($seriKodlari as $kod) {
    echo "  → faturaKodu: [$kod] deneniyor...\n";
    try {
        $noResp = $conn->faturaNoUret([
            'vknTckn' => $userId,
            'faturaKodu' => $kod,
        ]);
        $faturaNo = is_array($noResp->return)
            ? $noResp->return[0]
            : $noResp->return;
        echo "  ✅ BAŞARILI! Fatura No: $faturaNo\n\n";
        break;
    } catch (SoapFault $e) {
        echo "  ❌ " . $e->getMessage() . "\n";
    }
}

// ────────────────────────────────────────────────────────────
// 3. Ham SOAP yanıtlarını göster (debug)
// ────────────────────────────────────────────────────────────
echo "\n── Son İstek (Ham XML) ──\n";
echo htmlspecialchars((string) $conn->__getLastRequest()) . "\n";

echo "\n── Son Yanıt (Ham XML) ──\n";
echo htmlspecialchars((string) $conn->__getLastResponse()) . "\n";

// ── Logout ──
try {
    $userClient->logout(['userId' => $userId]);
    echo "\n✅ Logout\n";
} catch (SoapFault $e) {
    echo "\n⚠️ Logout: " . $e->getMessage() . "\n";
}

echo '</pre>';
