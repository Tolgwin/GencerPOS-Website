<?php
// kontrol.php
echo '<pre>';

$certPath = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';

echo "── Dosya Kontrolü ──\n";
echo 'cacert.pem mevcut : ' . (file_exists($certPath) ? '✅ EVET' : '❌ HAYIR') . "\n";
echo 'Dosya boyutu      : ' . (file_exists($certPath) ? number_format(filesize($certPath)) . ' byte' : '-') . "\n\n";

echo "── php.ini Değerleri ──\n";
echo 'curl.cainfo       : ' . ini_get('curl.cainfo') . "\n";
echo 'openssl.cafile    : ' . ini_get('openssl.cafile') . "\n";
echo 'soap.cache_dir    : ' . ini_get('soap.wsdl_cache_dir') . "\n\n";

echo "── Extension Durumu ──\n";
echo 'soap    : ' . (extension_loaded('soap') ? '✅' : '❌') . "\n";
echo 'curl    : ' . (extension_loaded('curl') ? '✅' : '❌') . "\n";
echo 'openssl : ' . (extension_loaded('openssl') ? '✅' : '❌') . "\n\n";

echo "── CURL SSL Testi ──\n";
$ch = curl_init('https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_CAINFO => $certPath,
    CURLOPT_TIMEOUT => 10,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "❌ CURL Hatası : $err\n";
} else {
    echo "✅ HTTP Kodu   : $code\n";
    echo "✅ WSDL boyutu : " . number_format(strlen($res)) . " byte\n";
}

echo "\n── SOAP Testi ──\n";
try {
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'cafile' => $certPath,
        ]
    ]);

    $client = new SoapClient(
        'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl',
        [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => $ctx,
        ]
    );
    echo "✅ SOAP bağlantısı başarılı!\n";
    echo "📋 Metodlar:\n";
    foreach ($client->__getFunctions() as $fn) {
        echo "  • $fn\n";
    }
} catch (SoapFault $e) {
    echo "❌ SOAP Hatası : " . $e->getMessage() . "\n";
}

echo '</pre>';
