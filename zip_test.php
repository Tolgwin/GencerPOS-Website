<?php
// zip_test.php

$certPath      = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$userId        = '3930311899';
$password      = 'vi39mmkgww0301';
$lang          = 'tr';
$userWsdl      = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl';
$connectorWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?wsdl';

set_time_limit(300);
ini_set('default_socket_timeout', 30);

echo '<pre>';
flush();

$soapOpts = [
    'trace'              => true,
    'exceptions'         => true,
    'cache_wsdl'         => WSDL_CACHE_NONE,
    'encoding'           => 'UTF-8',
    'connection_timeout' => 15,
    'stream_context'     => stream_context_create(['ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
        'cafile'           => $certPath,
    ]]),
];

function freshLogin(string $wsdl, array $opts, string $uid, string $pw, string $lang): array {
    $uc = new SoapClient($wsdl, $opts);
    $uc->wsLogin(['userId' => $uid, 'password' => $pw, 'lang' => $lang]);
    preg_match('/CSAPSESSIONID=([^;]+)/i', $uc->__getLastResponseHeaders(), $m);
    return [$uc, $m[1] ?? null];
}

function freshConn(string $wsdl, array $opts, string $cert, string $sid): SoapClient {
    $opts['stream_context'] = stream_context_create([
        'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sid],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $cert],
    ]);
    return new SoapClient($wsdl, $opts);
}

$faturaNo   = 'TST2026000000001';
$faturaDate = date('Y-m-d');
$faturaTime = date('H:i:s');
$uuid       = strtoupper(sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
    mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)));

// ── XML ──
$xmlStr =
    '<?xml version="1.0" encoding="UTF-8"?>' .
    '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' .
    ' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' .
    ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">' .
    '<cbc:UBLVersionID>2.1</cbc:UBLVersionID>' .
    '<cbc:CustomizationID>TR1.2</cbc:CustomizationID>' .
    '<cbc:ProfileID>TICARIFATURA</cbc:ProfileID>' .
    '<cbc:ID>' . $faturaNo . '</cbc:ID>' .
    '<cbc:CopyIndicator>false</cbc:CopyIndicator>' .
    '<cbc:UUID>' . $uuid . '</cbc:UUID>' .
    '<cbc:IssueDate>' . $faturaDate . '</cbc:IssueDate>' .
    '<cbc:IssueTime>' . $faturaTime . '</cbc:IssueTime>' .
    '<cbc:InvoiceTypeCode>SATIS</cbc:InvoiceTypeCode>' .
    '<cbc:DocumentCurrencyCode>TRY</cbc:DocumentCurrencyCode>' .
    '<cbc:LineCountNumeric>1</cbc:LineCountNumeric>' .
    '<cac:AccountingSupplierParty><cac:Party>' .
    '<cac:PartyIdentification><cbc:ID schemeID="VKN">' . $userId . '</cbc:ID></cac:PartyIdentification>' .
    '<cac:PartyName><cbc:Name>TEST SATICI</cbc:Name></cac:PartyName>' .
    '<cac:PartyTaxScheme><cbc:CompanyID>' . $userId . '</cbc:CompanyID>' .
    '<cac:TaxScheme><cbc:Name>VKN</cbc:Name></cac:TaxScheme></cac:PartyTaxScheme>' .
    '</cac:Party></cac:AccountingSupplierParty>' .
    '<cac:AccountingCustomerParty><cac:Party>' .
    '<cac:PartyIdentification><cbc:ID schemeID="VKN">' . $userId . '</cbc:ID></cac:PartyIdentification>' .
    '<cac:PartyName><cbc:Name>TEST ALICI</cbc:Name></cac:PartyName>' .
    '<cac:PartyTaxScheme><cbc:CompanyID>' . $userId . '</cbc:CompanyID>' .
    '<cac:TaxScheme><cbc:Name>VKN</cbc:Name></cac:TaxScheme></cac:PartyTaxScheme>' .
    '</cac:Party></cac:AccountingCustomerParty>' .
    '<cac:TaxTotal><cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>' .
    '<cac:TaxSubtotal>' .
    '<cbc:TaxableAmount currencyID="TRY">100.00</cbc:TaxableAmount>' .
    '<cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>' .
    '<cac:TaxCategory><cbc:Percent>18</cbc:Percent>' .
    '<cac:TaxScheme><cbc:Name>KDV</cbc:Name><cbc:TaxTypeCode>0015</cbc:TaxTypeCode></cac:TaxScheme>' .
    '</cac:TaxCategory></cac:TaxSubtotal></cac:TaxTotal>' .
    '<cac:LegalMonetaryTotal>' .
    '<cbc:LineExtensionAmount currencyID="TRY">100.00</cbc:LineExtensionAmount>' .
    '<cbc:TaxExclusiveAmount currencyID="TRY">100.00</cbc:TaxExclusiveAmount>' .
    '<cbc:TaxInclusiveAmount currencyID="TRY">118.00</cbc:TaxInclusiveAmount>' .
    '<cbc:PayableAmount currencyID="TRY">118.00</cbc:PayableAmount>' .
    '</cac:LegalMonetaryTotal>' .
    '<cac:InvoiceLine><cbc:ID>1</cbc:ID>' .
    '<cbc:InvoicedQuantity unitCode="C62">1</cbc:InvoicedQuantity>' .
    '<cbc:LineExtensionAmount currencyID="TRY">100.00</cbc:LineExtensionAmount>' .
    '<cac:TaxTotal><cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>' .
    '<cac:TaxSubtotal>' .
    '<cbc:TaxableAmount currencyID="TRY">100.00</cbc:TaxableAmount>' .
    '<cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>' .
    '<cac:TaxCategory><cbc:Percent>18</cbc:Percent>' .
    '<cac:TaxScheme><cbc:Name>KDV</cbc:Name><cbc:TaxTypeCode>0015</cbc:TaxTypeCode></cac:TaxScheme>' .
    '</cac:TaxCategory></cac:TaxSubtotal></cac:TaxTotal>' .
    '<cac:Item><cbc:Description>Test urun</cbc:Description><cbc:Name>Test Urun</cbc:Name></cac:Item>' .
    '<cac:Price><cbc:PriceAmount currencyID="TRY">100.00</cbc:PriceAmount></cac:Price>' .
    '</cac:InvoiceLine></Invoice>';

// ── ZIP üret (bellekte) ──
$zipFile = tempnam(sys_get_temp_dir(), 'efatura_') . '.zip';
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString($faturaNo . '.xml', $xmlStr);
$zip->close();
$zipBytes = file_get_contents($zipFile);
unlink($zipFile);

echo "XML  uzunluk : " . strlen($xmlStr)   . " byte\n";
echo "ZIP  uzunluk : " . strlen($zipBytes) . " byte\n";
echo "XML  SHA256  : " . hash('sha256', $xmlStr)   . "\n";
echo "ZIP  SHA256  : " . hash('sha256', $zipBytes) . "\n\n";
flush();

// ── Test kombinasyonları ──
$tests = [
    // [label, veri, hash, mimeType, versiyon]
    ['XML + application/xml  + V_1_3',  $xmlStr,   hash('sha256',$xmlStr),   'application/xml',  'V_1_3'],
    ['XML + application/xml  + V_1_2',  $xmlStr,   hash('sha256',$xmlStr),   'application/xml',  'V_1_2'],
    ['XML + text/xml         + V_1_3',  $xmlStr,   hash('sha256',$xmlStr),   'text/xml',         'V_1_3'],
    ['ZIP + application/zip  + V_1_3',  $zipBytes, hash('sha256',$zipBytes), 'application/zip',  'V_1_3'],
    ['ZIP + application/zip  + V_1_2',  $zipBytes, hash('sha256',$zipBytes), 'application/zip',  'V_1_2'],
    ['ZIP + application/zip  + V_2_0',  $zipBytes, hash('sha256',$zipBytes), 'application/zip',  'V_2_0'],
    ['ZIP + application/octet-stream',  $zipBytes, hash('sha256',$zipBytes), 'application/octet-stream', 'V_1_3'],
];

foreach ($tests as [$label, $veri, $hash, $mime, $ver]) {
    echo "── [$label] ──\n";
    flush();

    [$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
    $conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

    try {
        $resp = $conn->temelKontrollerIleBelgeGonder([
            'vergiTcKimlikNo' => $userId,
            'belgeNo'         => $faturaNo,
            'veri'            => $veri,
            'belgeHash'       => $hash,
            'mimeType'        => $mime,
            'belgeVersiyon'   => $ver,
        ]);
        echo "  ✅ BAŞARILI! " . print_r($resp, true) . "\n";
    } catch (SoapFault $e) {
        $msg = $e->getMessage();
        if (preg_match('/(cvc-[^<]+|Cannot find[^<]+|lineNumber[^<]+|belge[^<]+|hata[^<]+)/i', $msg, $m2)) {
            echo "  ❌ " . trim($m2[1]) . "\n";
        } else {
            echo "  ❌ " . substr($msg, 0, 250) . "\n";
        }
    }

    try { $uc->logout(['userId' => $userId]); } catch (Throwable $e) {}
    usleep(200000);
    flush();
}

echo "\n✅ Bitti\n";
echo '</pre>';
