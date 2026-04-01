<?php
// belgeturi_tara.php

$certPath = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$userId = '3930311899';
$password = 'vi39mmkgww0301';
$lang = 'tr';
$userWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl';
$connectorWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?wsdl';

$faturaNo = 'TST2026000000001';

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

// ── UBL XML ──
$uuid = strtoupper(sprintf(
  '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff),
  mt_rand(0, 0x0fff) | 0x4000,
  mt_rand(0, 0x3fff) | 0x8000,
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff),
  mt_rand(0, 0xffff)
));

$ublXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
  <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
  <cbc:ProfileID>TICARIFATURA</cbc:ProfileID>
  <cbc:ID>{$faturaNo}</cbc:ID>
  <cbc:CopyIndicator>false</cbc:CopyIndicator>
  <cbc:UUID>{$uuid}</cbc:UUID>
  <cbc:IssueDate>{date('Y-m-d')}</cbc:IssueDate>
  <cbc:IssueTime>{date('H:i:s')}</cbc:IssueTime>
  <cbc:InvoiceTypeCode>SATIS</cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>TRY</cbc:DocumentCurrencyCode>
  <cbc:LineCountNumeric>1</cbc:LineCountNumeric>
  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyIdentification><cbc:ID schemeID="VKN">{$userId}</cbc:ID></cac:PartyIdentification>
      <cac:PartyName><cbc:Name>TEST SATICI A.Ş.</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>Test Sokak No:1</cbc:StreetName>
        <cbc:CitySubdivisionName>Şişli</cbc:CitySubdivisionName>
        <cbc:CityName>İstanbul</cbc:CityName>
        <cac:Country><cbc:Name>Türkiye</cbc:Name></cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID>{$userId}</cbc:CompanyID>
        <cac:TaxScheme><cbc:Name>VKN</cbc:Name></cac:TaxScheme>
      </cac:PartyTaxScheme>
    </cac:Party>
  </cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyIdentification><cbc:ID schemeID="VKN">{$userId}</cbc:ID></cac:PartyIdentification>
      <cac:PartyName><cbc:Name>TEST ALICI A.Ş.</cbc:Name></cac:PartyName>
      <cac:PostalAddress>
        <cbc:StreetName>Alıcı Sokak No:2</cbc:StreetName>
        <cbc:CitySubdivisionName>Kadıköy</cbc:CitySubdivisionName>
        <cbc:CityName>İstanbul</cbc:CityName>
        <cac:Country><cbc:Name>Türkiye</cbc:Name></cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID>{$userId}</cbc:CompanyID>
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
</Invoice>
XML;

// ────────────────────────────────────────────────────────
// Önce WSDL'den belgeTuru enum/sabitlerini tara
// ────────────────────────────────────────────────────────
$userClient = new SoapClient($userWsdl, $soapOpts);
$userClient->wsLogin(['userId' => $userId, 'password' => $password, 'lang' => $lang]);
preg_match('/CSAPSESSIONID=([^;]+)/i', $userClient->__getLastResponseHeaders(), $m);
$sid = $m[1] ?? null;

$ctx0 = stream_context_create([
  'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sid],
  'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $certPath],
]);
$conn0 = new SoapClient($connectorWsdl, array_merge($soapOpts, ['stream_context' => $ctx0]));

echo "── WSDL'den belgeTuru ipuçları ──\n";
foreach ($conn0->__getTypes() as $type) {
  if (
    stripos($type, 'belgeTuru') !== false ||
    stripos($type, 'belge_turu') !== false ||
    stripos($type, 'EFATURA') !== false ||
    stripos($type, 'EARSIV') !== false ||
    stripos($type, 'IRSALIYE') !== false ||
    stripos($type, 'MUSTERI') !== false
  ) {
    echo $type . "\n";
  }
}
echo "\n";

try {
  $userClient->logout(['userId' => $userId]);
} catch (SoapFault $e) {
}

// ────────────────────────────────────────────────────────
// belgeTuru adayları — V_1_3 ile dene
// ────────────────────────────────────────────────────────
$belgeTurleri = [
  // GİB standart adları
  'EFATURA',
  'E_FATURA',
  'eFatura',
  'efatura',
  'TICARIFATURA',
  'TEMELFATURA',
  // e-Arşiv varyantları
  'EARSIVFATURA',
  'E_ARSIV',
  'EARSIV_FATURA',
  'eArsiv',
  // İrsaliye
  'IRSALIYE',
  'EIRSALIYE',
  // Diğer
  'INVOICE',
  'UBL',
  'XML',
  '1',
  'SATIS',
];

echo "── belgeTuru Denemeleri (belgeVersiyon=V_1_3) ──\n\n";

$found = false;
foreach ($belgeTurleri as $tur) {
  if ($found)
    break;

  // Taze login
  $uc = new SoapClient($userWsdl, $soapOpts);
  $uc->wsLogin(['userId' => $userId, 'password' => $password, 'lang' => $lang]);
  preg_match('/CSAPSESSIONID=([^;]+)/i', $uc->__getLastResponseHeaders(), $mx);
  $sid2 = $mx[1] ?? null;

  $ctx2 = stream_context_create([
    'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sid2],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $certPath],
  ]);
  $conn2 = new SoapClient($connectorWsdl, array_merge($soapOpts, ['stream_context' => $ctx2]));

  echo "  → belgeTuru=[$tur] ... ";

  try {
    $resp = $conn2->belgeGonder([
      'vergiTcKimlikNo' => $userId,
      'belgeTuru' => $tur,
      'belgeNo' => $faturaNo,
      'veri' => base64_encode($ublXml),
      'belgeHash' => hash('sha256', $ublXml),
      'mimeType' => 'application/xml',
      'belgeVersiyon' => 'V_1_3',
    ]);
    echo "✅ BAŞARILI!\n";
    print_r($resp);
    $found = true;

  } catch (SoapFault $e) {
    $msg = $e->getMessage();
    if (
      stripos($msg, 'tur') === false &&
      stripos($msg, 'type') === false &&
      stripos($msg, 'versiyon') === false &&
      stripos($msg, 'version') === false
    ) {
      echo "⚠️  TÜR GEÇTİ! Yeni hata: $msg\n";
      $found = true;
    } else {
      echo "❌ $msg\n";
    }
  }

  try {
    $uc->logout(['userId' => $userId]);
  } catch (SoapFault $e) {
  }
  if ($found)
    break;
  usleep(200000);
}

echo "\n✅ Tarama Tamamlandı\n";
echo '</pre>';
