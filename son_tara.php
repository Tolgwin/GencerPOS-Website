<?php
// son_tara.php

$certPath      = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$userId        = '3930311899';
$password      = 'vi39mmkgww0301';
$lang          = 'tr';
$userWsdl      = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl';
$connectorWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?wsdl';

echo '<pre>';

$ctx = stream_context_create(['ssl' => [
    'verify_peer'      => true,
    'verify_peer_name' => true,
    'cafile'           => $certPath,
]]);

$soapOpts = [
    'trace'          => true,
    'exceptions'     => true,
    'cache_wsdl'     => WSDL_CACHE_NONE,
    'stream_context' => $ctx,
    'encoding'       => 'UTF-8',
];

function freshLogin(string $wsdl, array $opts, string $uid, string $pw, string $lang): array {
    $uc = new SoapClient($wsdl, $opts);
    $uc->wsLogin(['userId' => $uid, 'password' => $pw, 'lang' => $lang]);
    preg_match('/CSAPSESSIONID=([^;]+)/i', $uc->__getLastResponseHeaders(), $m);
    return [$uc, $m[1] ?? null];
}

function freshConn(string $wsdl, array $opts, string $cert, string $sid): SoapClient {
    $ctx = stream_context_create([
        'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sid],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $cert],
    ]);
    return new SoapClient($wsdl, array_merge($opts, ['stream_context' => $ctx]));
}

$faturaNo   = 'TST2026000000001';
$faturaDate = date('Y-m-d');
$faturaTime = date('H:i:s');
$uuid       = strtoupper(sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
    mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
    mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)));

// ── XML — BOM yok, trim'li, tek satır boşluk yok ──
$ublXml = trim('<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
  <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
  <cbc:ProfileID>TICARIFATURA</cbc:ProfileID>
  <cbc:ID>' . $faturaNo . '</cbc:ID>
  <cbc:CopyIndicator>false</cbc:CopyIndicator>
  <cbc:UUID>' . $uuid . '</cbc:UUID>
  <cbc:IssueDate>' . $faturaDate . '</cbc:IssueDate>
  <cbc:IssueTime>' . $faturaTime . '</cbc:IssueTime>
  <cbc:InvoiceTypeCode>SATIS</cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>TRY</cbc:DocumentCurrencyCode>
  <cbc:LineCountNumeric>1</cbc:LineCountNumeric>
  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyIdentification><cbc:ID schemeID="VKN">' . $userId . '</cbc:ID></cac:PartyIdentification>
      <cac:PartyName><cbc:Name>TEST SATICI A.Ş.</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>Test Sokak No:1</cbc:StreetName>
        <cbc:CitySubdivisionName>Şişli</cbc:CitySubdivisionName>
        <cbc:CityName>İstanbul</cbc:CityName>
        <cac:Country><cbc:Name>Türkiye</cbc:Name></cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID>' . $userId . '</cbc:CompanyID>
        <cac:TaxScheme><cbc:Name>VKN</cbc:Name></cac:TaxScheme>
      </cac:PartyTaxScheme>
    </cac:Party>
  </cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyIdentification><cbc:ID schemeID="VKN">' . $userId . '</cbc:ID></cac:PartyIdentification>
      <cac:PartyName><cbc:Name>TEST ALICI A.Ş.</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>Alıcı Sokak No:2</cbc:StreetName>
        <cbc:CitySubdivisionName>Kadıköy</cbc:CitySubdivisionName>
        <cbc:CityName>İstanbul</cbc:CityName>
        <cac:Country><cbc:Name>Türkiye</cbc:Name></cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID>' . $userId . '</cbc:CompanyID>
        <cac:TaxScheme><cbc:Name>VKN</cbc:Name></cac:TaxScheme>
      </cac:PartyTaxScheme>
    </cac:Party>
  </cac:AccountingCustomerParty>
  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>
    <cac:TaxSubtotal>
      <cbc:TaxableAmount currencyID="TRY">100.00</cbc:TaxableAmount>
      <cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>
      <cac:TaxCategory>
        <cbc:Percent>18</cbc:Percent>
        <cac:TaxScheme><cbc:Name>KDV</cbc:Name><cbc:TaxTypeCode>0015</cbc:TaxTypeCode></cac:TaxScheme>
      </cac:TaxCategory>
    </cac:TaxSubtotal>
  </cac:TaxTotal>
  <cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="TRY">100.00</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="TRY">100.00</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="TRY">118.00</cbc:TaxInclusiveAmount>
    <cbc:PayableAmount currencyID="TRY">118.00</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
  <cac:InvoiceLine>
    <cbc:ID>1</cbc:ID>
    <cbc:InvoicedQuantity unitCode="C62">1</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="TRY">100.00</cbc:LineExtensionAmount>
    <cac:TaxTotal>
      <cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>
      <cac:TaxSubtotal>
        <cbc:TaxableAmount currencyID="TRY">100.00</cbc:TaxableAmount>
        <cbc:TaxAmount currencyID="TRY">18.00</cbc:TaxAmount>
        <cac:TaxCategory>
          <cbc:Percent>18</cbc:Percent>
          <cac:TaxScheme><cbc:Name>KDV</cbc:Name><cbc:TaxTypeCode>0015</cbc:TaxTypeCode></cac:TaxScheme>
        </cac:TaxCategory>
      </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:Item>
      <cbc:Description>Test ürün</cbc:Description>
      <cbc:Name>Test Ürün</cbc:Name>
    </cac:Item>
    <cac:Price>
      <cbc:PriceAmount currencyID="TRY">100.00</cbc:PriceAmount>
    </cac:Price>
  </cac:InvoiceLine>
</Invoice>');

// BOM temizle (UTF-8 BOM: EF BB BF)
$ublXml = preg_replace('/^\xEF\xBB\xBF/', '', $ublXml);

// XML geçerliliğini kontrol et
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$loaded = $dom->loadXML($ublXml);
if (!$loaded) {
    echo "❌ XML GEÇERSİZ:\n";
    foreach (libxml_get_errors() as $e) echo "  " . $e->message;
    echo "\n";
} else {
    echo "✅ XML geçerli, uzunluk: " . strlen($ublXml) . " byte\n";
    echo "   İlk 80 karakter: " . substr($ublXml, 0, 80) . "\n\n";
}

// ────────────────────────────────────────────────────────
// TEST 1 — temelKontrollerIleBelgeGonder (belgeTuru YOK)
// ────────────────────────────────────────────────────────
echo "── TEST 1: temelKontrollerIleBelgeGonder ──\n";
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

try {
    $resp = $conn->temelKontrollerIleBelgeGonder([
        'vergiTcKimlikNo' => $userId,
        'belgeNo'         => $faturaNo,
        'veri'            => base64_encode($ublXml),
        'belgeHash'       => hash('sha256', $ublXml),
        'mimeType'        => 'application/xml',
        'belgeVersiyon'   => 'V_1_3',
    ]);
    echo "✅ BAŞARILI! belgeOid: " . ($resp->belgeOid ?? print_r($resp, true)) . "\n";
} catch (SoapFault $e) {
    echo "❌ " . $e->getMessage() . "\n";
}
try { $uc->logout(['userId' => $userId]); } catch (SoapFault $e) {}

// ────────────────────────────────────────────────────────
// TEST 2 — IRSALIYE + tüm versiyonlar (tür geçiyor!)
// ────────────────────────────────────────────────────────
echo "\n── TEST 2: belgeTuru=IRSALIYE + versiyon tarama ──\n";
$versiyonlar = ['V_1_3', 'V_1_2', 'V_1_0', 'V_2_0', 'V_2_1', '1.2', '1.3', 'TR1.2'];
foreach ($versiyonlar as $ver) {
    [$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
    $conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

    echo "  → belgeVersiyon=[$ver] ... ";
    try {
        $resp = $conn->belgeGonder([
            'vergiTcKimlikNo' => $userId,
            'belgeTuru'       => 'IRSALIYE',
            'belgeNo'         => $faturaNo,
            'veri'            => base64_encode($ublXml),
            'belgeHash'       => hash('sha256', $ublXml),
            'mimeType'        => 'application/xml',
            'belgeVersiyon'   => $ver,
        ]);
        echo "✅ BAŞARILI!\n";
        print_r($resp);
    } catch (SoapFault $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'versiyon') !== false || stripos($msg, 'version') !== false) {
            echo "❌ versiyon geçersiz\n";
        } else {
            echo "⚠️  VERSİYON GEÇTİ → $msg\n";
        }
    }
    try { $uc->logout(['userId' => $userId]); } catch (SoapFault $e) {}
    usleep(150000);
}

// ────────────────────────────────────────────────────────
// TEST 3 — belgeGonderExt (gidenBelgeParametreleri)
// ────────────────────────────────────────────────────────
echo "\n── TEST 3: belgeGonderExt ──\n";
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

try {
    $resp = $conn->belgeGonderExt([
        'parametreler' => [
            'vergiTcKimlikNo' => $userId,
            'belgeTuru'       => 'EFATURA',
            'belgeNo'         => $faturaNo,
            'veri'            => base64_encode($ublXml),
            'belgeHash'       => hash('sha256', $ublXml),
            'mimeType'        => 'application/xml',
            'belgeVersiyon'   => 'V_1_3',
            'wsFaturaNo'      => $faturaNo,
            'gonderenEtiket'  => 'urn:mail:defaultpk@' . $userId,
            'alanEtiket'      => 'urn:mail:defaultpk@' . $userId,
        ]
    ]);
    echo "✅ BAŞARILI! belgeOid: " . ($resp->belgeOid ?? print_r($resp, true)) . "\n";
} catch (SoapFault $e) {
    echo "❌ " . $e->getMessage() . "\n";
}
try { $uc->logout(['userId' => $userId]); } catch (SoapFault $e) {}

echo "\n✅ Tamamlandı\n";
echo '</pre>';
