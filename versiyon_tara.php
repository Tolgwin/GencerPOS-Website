<?php
// versiyon_tara.php

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

// ────────────────────────────────────────────────────────
// 1. WSDL'den tüm enum / string sabitlerini tara
// ────────────────────────────────────────────────────────
echo "── WSDL Type Listesi (versiyon içerenler) ──\n";
$types = $conn->__getTypes();
foreach ($types as $type) {
  if (
    stripos($type, 'versiyon') !== false ||
    stripos($type, 'version') !== false ||
    stripos($type, 'V_1') !== false ||
    stripos($type, 'UBL') !== false
  ) {
    echo $type . "\n";
  }
}
echo "\n";

// ────────────────────────────────────────────────────────
// 2. Olası versiyon değerlerini dene
// ────────────────────────────────────────────────────────
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

$faturaNo = 'TST2026000000001';
$faturaDate = date('Y-m-d');
$faturaTime = date('H:i:s');
$matrah = '100.00';
$kdv = '18.00';
$toplam = '118.00';

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
  <cbc:IssueDate>{$faturaDate}</cbc:IssueDate>
  <cbc:IssueTime>{$faturaTime}</cbc:IssueTime>
  <cbc:InvoiceTypeCode>SATIS</cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>TRY</cbc:DocumentCurrencyCode>
  <cbc:LineCountNumeric>1</cbc:LineCountNumeric>
  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyIdentification><cbc:ID schemeID="VKN">{$userId}</cbc:ID></cac:PartyIdentification>
      <cac:PartyName><cbc:Name>TEST SATICI FİRMA A.Ş.</cbc:Name></cac:PartyName>
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
      <cac:PartyName><cbc:Name>TEST ALICI FİRMA A.Ş.</cbc:Name></cac:PartyName>
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
    <cbc:TaxAmount currencyID="TRY">{$kdv}</cbc:TaxAmount>
    <cac:TaxSubtotal>
      <cbc:TaxableAmount currencyID="TRY">{$matrah}</cbc:TaxableAmount>
      <cbc:TaxAmount currencyID="TRY">{$kdv}</cbc:TaxAmount>
      <cac:TaxCategory>
        <cbc:Percent>18</cbc:Percent>
        <cac:TaxScheme><cbc:Name>KDV</cbc:Name><cbc:TaxTypeCode>0015</cbc:TaxTypeCode></cac:TaxScheme>
      </cac:TaxCategory>
    </cac:TaxSubtotal>
  </cac:TaxTotal>
  <cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="TRY">{$matrah}</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="TRY">{$matrah}</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="TRY">{$toplam}</cbc:TaxInclusiveAmount>
    <cbc:PayableAmount currencyID="TRY">{$toplam}</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
  <cac:InvoiceLine>
    <cbc:ID>1</cbc:ID>
    <cbc:InvoicedQuantity unitCode="C62">1</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="TRY">{$matrah}</cbc:LineExtensionAmount>
    <cac:TaxTotal>
      <cbc:TaxAmount currencyID="TRY">{$kdv}</cbc:TaxAmount>
      <cac:TaxSubtotal>
        <cbc:TaxableAmount currencyID="TRY">{$matrah}</cbc:TaxableAmount>
        <cbc:TaxAmount currencyID="TRY">{$kdv}</cbc:TaxAmount>
        <cac:TaxCategory>
          <cbc:Percent>18</cbc:Percent>
          <cac:TaxScheme><cbc:Name>KDV</cbc:Name><cbc:TaxTypeCode>0015</cbc:TaxTypeCode></cac:TaxScheme>
        </cac:TaxCategory>
      </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:Item>
      <cbc:Description>Test ürün açıklaması</cbc:Description>
      <cbc:Name>Test Ürün</cbc:Name>
    </cac:Item>
    <cac:Price>
      <cbc:PriceAmount currencyID="TRY">{$matrah}</cbc:PriceAmount>
    </cac:Price>
  </cac:InvoiceLine>
</Invoice>
XML;

// Olası tüm versiyon değerleri
$versiyonlar = [
  'V_1_2',
  'V_1_3',
  'V_1_0',
  'UBL-TR 1.2',
  'UBL-TR1.2',
  'UBLTR12',
  '1.2',
  '1.3',
  'TR1.2',
  'TR_1_2',
  'FATURA',
  'EARSIV',
  'V_2_1',
  '2.1',
];

echo "── belgeVersiyon Denemeleri ──\n\n";

foreach ($versiyonlar as $versiyon) {
  echo "  → belgeVersiyon: [$versiyon] ... ";
  try {
    $resp = $conn->belgeGonder([
      'vergiTcKimlikNo' => $userId,
      'belgeTuru' => 'FATURA',
      'belgeNo' => $faturaNo,
      'veri' => base64_encode($ublXml),
      'belgeHash' => hash('sha256', $ublXml),
      'mimeType' => 'application/xml',
      'belgeVersiyon' => $versiyon,
    ]);
    echo "✅ BAŞARILI!\n";
    print_r($resp);
    echo "\n";
    break;
  } catch (SoapFault $e) {
    $msg = $e->getMessage();
    // Versiyon geçti ama başka hata → bu da bilgi!
    if (stripos($msg, 'versiyon') === false && stripos($msg, 'version') === false) {
      echo "⚠️  Versiyon geçti! Yeni hata: $msg\n";
      break;
    }
    echo "❌ $msg\n";
  }
}

// ── Logout ──
try {
  $userClient->logout(['userId' => $userId]);
  echo "\n✅ Logout\n";
} catch (SoapFault $e) {
  echo "\n⚠️ Logout: " . $e->getMessage() . "\n";
}

echo '</pre>';
