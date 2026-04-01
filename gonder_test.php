<?php
// gonder_test.php

$certPath     = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$endpoint     = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService';

// ── Kimlik Bilgileri ──────────────────────────────────────
$kullanici    = '3930311899';   // ← QNB'nin sana verdiği kullanıcı adı
$sifre        = 'vi39mmkgww0301';           // ← QNB'nin sana verdiği şifre
// ─────────────────────────────────────────────────────────

// ── Fatura Bilgileri ──────────────────────────────────────
$aliciVkn     = '0123456789';             // ← Faturayı KESECEĞİN firmanın VKN'i
$xmlDosya     = __DIR__ . '/fatura.xml';
// ─────────────────────────────────────────────────────────

$soapNS = 'http://service.connector.uut.cs.com.tr/';
$wsseNS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

echo '<pre>';

// ════════════════════════════════════════
// YARDIMCI: Ham SOAP isteği gönder
// ════════════════════════════════════════
function soapIstegi(string $endpoint, string $soapXml, string $certPath): array
{
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $soapXml,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CAINFO         => $certPath,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ""',
            'Content-Length: ' . strlen($soapXml),
        ],
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    return ['response' => $response, 'httpCode' => $httpCode, 'curlErr' => $curlErr];
}

// ════════════════════════════════════════
// YARDIMCI: Güzel XML çıktısı
// ════════════════════════════════════════
function xmlGoster(string $xml): void
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if ($dom->loadXML($xml)) {
        echo htmlspecialchars($dom->saveXML()) . "\n";
    } else {
        echo htmlspecialchars($xml ?: '(boş yanıt)') . "\n";
    }
}

// ════════════════════════════════════════
// ADIM 1: XML OKU + HAZIRLA
// ════════════════════════════════════════
echo "── XML Hazırla ──\n";

$xmlIcerik = file_get_contents($xmlDosya);
if (!$xmlIcerik) { die("❌ fatura.xml okunamadı\n"); }

$xmlIcerik = preg_replace('/^\xEF\xBB\xBF/', '', $xmlIcerik);
$xmlIcerik = trim($xmlIcerik);

$dom = new DOMDocument();
$dom->loadXML($xmlIcerik);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');

$belgeNo         = trim($xpath->query('//cbc:ID')->item(0)->nodeValue);
$customizationID = trim($xpath->query('//cbc:CustomizationID')->item(0)->nodeValue ?? '');
$versionMap      = ['TR1.2' => '1.0', 'TR1.3' => '1.0'];
$belgeVersiyon   = "1.0";
$hash            = hash('sha256', $xmlIcerik);
$base64Veri      = base64_encode($xmlIcerik);

echo "Belge No      : $belgeNo\n";
echo "belgeVersiyon : $belgeVersiyon\n";
echo "SHA256        : $hash\n\n";

// ════════════════════════════════════════
// ADIM 2: BELGE GÖNDER
// ════════════════════════════════════════
echo "── Belge Gönder ──\n";

// Değerleri doğrula
echo "Kullanıcı : '$kullanici'\n";
echo "Alıcı VKN : '$aliciVkn'\n\n";

$kullaniciXml = htmlspecialchars($kullanici, ENT_XML1, 'UTF-8');
$sifreXml     = htmlspecialchars($sifre,     ENT_XML1, 'UTF-8');


// String birleştirme ile SOAP oluştur (heredoc yok)
$soap  = '<?xml version="1.0" encoding="UTF-8"?>';
// ── Sabitler ──────────────────────────────────────────────
$soapNS  = 'http://service.connector.uut.cs.com.tr/';
$wsseNS  = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

// ── Hash: MD5 ─────────────────────────────────────────────
$hash = strtoupper(md5($xmlIcerik)); // MD5, büyük harf

// ── SOAP Oluştur ──────────────────────────────────────────
$soap  = '<?xml version="1.0" encoding="UTF-8"?>';
$soap .= '<soapenv:Envelope';
$soap .= ' xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"';
$soap .= ' xmlns:ser="' . $soapNS . '">';

$soap .= '<soapenv:Header>';
$soap .= '<wsse:Security xmlns:wsse="' . $wsseNS . '">';
$soap .= '<wsse:UsernameToken>';
$soap .= '<wsse:Username>' . htmlspecialchars($kullanici) . '</wsse:Username>';
$soap .= '<wsse:Password>'  . htmlspecialchars($sifre)    . '</wsse:Password>';
$soap .= '</wsse:UsernameToken>';
$soap .= '</wsse:Security>';
$soap .= '</soapenv:Header>';

$soap .= '<soapenv:Body>';
$soap .= '<ser:belgeGonderExt>';
$soap .= '<parametreler>';
$soap .= '<vergiTcKimlikNo>' . htmlspecialchars($aliciVkn)   . '</vergiTcKimlikNo>';
$soap .= '<belgeTuru>FATURA_UBL</belgeTuru>';
$soap .= '<belgeNo>'         . htmlspecialchars($belgeNo)    . '</belgeNo>';
$soap .= '<veri>'            . $base64Veri                   . '</veri>';
$soap .= '<belgeHash>'       . $hash                         . '</belgeHash>';
$soap .= '<mimeType>application_xml</mimeType>';
$soap .= '<belgeVersiyon>1.0</belgeVersiyon>';
$soap .= '<erpKodu/>';
$soap .= '<alanEtiket/>';
$soap .= '<gonderenEtiket/>';
$soap .= '<xsltAdi/>';
$soap .= '<xsltVeri/>';
$soap .= '<subeKodu/>';
$soap .= '<donusTipiVersiyon/>';
$soap .= '</parametreler>';
$soap .= '</ser:belgeGonderExt>';
$soap .= '</soapenv:Body>';
$soap .= '</soapenv:Envelope>';


echo "── Ham SOAP ──\n";
echo htmlspecialchars($soap) . "\n\n";
die(); // Şimdilik gönderme, sadece SOAP'ı gör

$sonuc = soapIstegi($endpoint, $soap, $certPath);
echo "HTTP Kodu : {$sonuc['httpCode']}\n\n";

if ($sonuc['curlErr']) {
    echo "❌ cURL Hata: {$sonuc['curlErr']}\n";
}

echo "── Sunucu Yanıtı ──\n";
xmlGoster($sonuc['response']);

echo '</pre>';