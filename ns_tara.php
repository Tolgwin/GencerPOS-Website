<?php
// ns_tara.php

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

// ── Farklı namespace kombinasyonları ──
$namespaceSets = [

    // 1) GİB resmi — urn:oasis UBL 2.1
    'GIB_OASIS_21' => [
        'inv' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
    ],

    // 2) GİB eski — docs.oasis-open.org
    'GIB_DOCS_OASIS' => [
        'inv' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
    ],

    // 3) docs.oasis-open.org/ubl/os-UBL-2.1
    'OASIS_OPEN_21' => [
        'inv' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
    ],

    // 4) GİB TR özel namespace
    'GIB_TR' => [
        'inv' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
    ],

    // 5) Namespace YOK — sadece lokal isim
    'NO_NAMESPACE' => [
        'inv' => '',
        'cac' => '',
        'cbc' => '',
    ],

    // 6) GİB 2013 eski format
    'GIB_2013' => [
        'inv' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
    ],
];

// Namespace setleri aynı olduğu için XML template'i string ile üretelim
// Her kombinasyon için farklı xmlns değerleri deneyelim
$xmlTemplates = [

    'NS_OASIS_STD' =>
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
        '</cac:InvoiceLine></Invoice>',

    // xmlns:xsi + schemaLocation ekli
    'NS_WITH_SCHEMA_LOCATION' =>
        '<?xml version="1.0" encoding="UTF-8"?>' .
        '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' .
        ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' .
        ' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' .
        ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"' .
        ' xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 UBL-Invoice-2.1.xsd">' .
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
        '</cac:InvoiceLine></Invoice>',

    // Namespace YOK
    'NO_NAMESPACE' =>
        '<?xml version="1.0" encoding="UTF-8"?>' .
        '<Invoice>' .
        '<UBLVersionID>2.1</UBLVersionID>' .
        '<CustomizationID>TR1.2</CustomizationID>' .
        '<ProfileID>TICARIFATURA</ProfileID>' .
        '<ID>' . $faturaNo . '</ID>' .
        '<CopyIndicator>false</CopyIndicator>' .
        '<UUID>' . $uuid . '</UUID>' .
        '<IssueDate>' . $faturaDate . '</IssueDate>' .
        '<IssueTime>' . $faturaTime . '</IssueTime>' .
        '<InvoiceTypeCode>SATIS</InvoiceTypeCode>' .
        '<DocumentCurrencyCode>TRY</DocumentCurrencyCode>' .
        '</Invoice>',

    // GİB'in kendi şema URL'si ile
    'GIB_SCHEMA_URL' =>
        '<?xml version="1.0" encoding="UTF-8"?>' .
        '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"' .
        ' xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"' .
        ' xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"' .
        ' xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">' .
        '<ext:UBLExtensions><ext:UBLExtension><ext:ExtensionContent/></ext:UBLExtension></ext:UBLExtensions>' .
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
        '</cac:InvoiceLine></Invoice>',
];

foreach ($xmlTemplates as $label => $xmlStr) {
    echo "── [$label] ──\n";
    echo "  Uzunluk: " . strlen($xmlStr) . " byte\n";
    flush();

    [$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
    $conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

    try {
        $resp = $conn->temelKontrollerIleBelgeGonder([
            'vergiTcKimlikNo' => $userId,
            'belgeNo'         => $faturaNo,
            'veri'            => $xmlStr,
            'belgeHash'       => hash('sha256', $xmlStr),
            'mimeType'        => 'application/xml',
            'belgeVersiyon'   => 'V_1_3',
        ]);
        echo "  ✅ BAŞARILI! belgeOid: " . ($resp->belgeOid ?? print_r($resp,true)) . "\n";
    } catch (SoapFault $e) {
        $msg = $e->getMessage();
        // Sadece kritik kısmı göster
        if (preg_match('/(cvc-[^"]+|Cannot find[^"]+|lineNumber[^"]+)/i', $msg, $m2)) {
            echo "  ❌ " . $m2[1] . "\n";
        } else {
            echo "  ❌ " . substr($msg, 0, 200) . "\n";
        }
    }

    try { $uc->logout(['userId' => $userId]); } catch (Throwable $e) {}
    usleep(200000);
    flush();
}

echo "\n✅ Bitti\n";
echo '</pre>';
