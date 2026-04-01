<?php
// soap_baglanti_test.php

$certPath = 'C:\\laragon\\bin\\php\\php-8.2.x\\cacert.pem';
$wsdl = 'https://efaturatest.qnbesolutions.com.tr/EFaturaService.svc?wsdl';

echo '<pre>';

// ── 1. Sertifika dosyası var mı? ──
echo file_exists($certPath)
    ? "✅ cacert.pem bulundu\n"
    : "❌ cacert.pem bulunamadı: $certPath\n";

// ── 2. CURL ile HTTPS testi ──
$ch = curl_init($wsdl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_CAINFO => $certPath,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_FOLLOWLOCATION => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlHata = curl_error($ch);
curl_close($ch);

if ($curlHata) {
    echo "❌ CURL Hatası: $curlHata\n";
} else {
    echo "✅ CURL HTTP Kodu: $httpCode\n";
    echo "✅ WSDL ilk 200 karakter:\n";
    echo htmlspecialchars(substr($response, 0, 200)) . "\n";
}

// ── 3. SOAP bağlantı testi ──
echo "\n── SOAP Testi ──\n";
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
    ]);

    echo "✅ SOAP bağlantısı başarılı!\n";
    echo "📋 Mevcut metodlar:\n";
    print_r($client->__getFunctions());

} catch (SoapFault $e) {
    echo "❌ SOAP Hatası: " . $e->getMessage() . "\n";
} catch (Throwable $e) {
    echo "❌ Genel Hata: " . $e->getMessage() . "\n";
}

echo '</pre>';

