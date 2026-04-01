<?php
// parametre_tara.php

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
$faturaDate = date('Y-m-d');
$faturaTime = date('H:i:s');

$ublXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
  <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
  <cbc:ProfileID>TICARIFATURA</cbc:ProfileID>
  <cbc:ID>TST2026000000001</cbc:ID>
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
// Önce faturaNoUret ile geçerli fatura no al
// ────────────────────────────────────────────────────────
function freshConn(string $wsdl, array $opts, string $cert, string $sid): SoapClient
{
  $ctx = stream_context_create([
    'http' => ['header' => 'Cookie: CSAPSESSIONID=' . $sid],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => $cert],
  ]);
  return new SoapClient($wsdl, array_merge($opts, ['stream_context' => $ctx]));
}

function freshLogin(string $wsdl, array $opts, string $uid, string $pw, string $lang): array
{
  $uc = new SoapClient($wsdl, $opts);
  $uc->wsLogin(['userId' => $uid, 'password' => $pw, 'lang' => $lang]);
  preg_match('/CSAPSESSIONID=([^;]+)/i', $uc->__getLastResponseHeaders(), $m);
  return [$uc, $m[1] ?? null];
}

// ── faturaNoUret ──
[$uc0, $sid0] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn0 = freshConn($connectorWsdl, $soapOpts, $certPath, $sid0);
try {
  $noResp = $conn0->faturaNoUret(['vknTckn' => $userId, 'faturaKodu' => 'TST']);
  $faturaNo = is_object($noResp) ? $noResp->return : (string) $noResp;
  echo "📄 Fatura No : $faturaNo\n\n";
} catch (SoapFault $e) {
  $faturaNo = 'TST2026000000001';
  echo "⚠️ faturaNoUret hata, manuel: $faturaNo\n\n";
}
try {
  $uc0->logout(['userId' => $userId]);
} catch (SoapFault $e) {
}

// ────────────────────────────────────────────────────────
// XML içindeki fatura no'yu güncelle
// ────────────────────────────────────────────────────────
$ublXml = str_replace('TST2026000000001', $faturaNo, $ublXml);

// ────────────────────────────────────────────────────────
// mimeType kombinasyonları — EFATURA + V_1_3 sabit
// ────────────────────────────────────────────────────────
$mimeTypes = [
  'application/xml',
  'text/xml',
  'application/zip',
  'application/octet-stream',
];

echo "── 1. mimeType Denemeleri ──\n";
$goodMime = null;
foreach ($mimeTypes as $mime) {
  [$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
  $conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

  echo "  → mimeType=[$mime] ... ";
  try {
    $resp = $conn->belgeGonder([
      'vergiTcKimlikNo' => $userId,
      'belgeTuru' => 'EFATURA',
      'belgeNo' => $faturaNo,
      'veri' => base64_encode($ublXml),
      'belgeHash' => hash('sha256', $ublXml),
      'mimeType' => $mime,
      'belgeVersiyon' => 'V_1_3',
    ]);
    echo "✅ BAŞARILI!\n";
    print_r($resp);
    $goodMime = $mime;
  } catch (SoapFault $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'mime') === false && stripos($msg, 'tur') === false && stripos($msg, 'type') === false) {
      echo "⚠️  mimeType geçti! Yeni hata: $msg\n";
      $goodMime = $mime;
    } else {
      echo "❌ $msg\n";
    }
  }
  try {
    $uc->logout(['userId' => $userId]);
  } catch (SoapFault $e) {
  }
  if ($goodMime)
    break;
  usleep(200000);
}

// ────────────────────────────────────────────────────────
// ZIP olarak dene — bazı sistemler sıkıştırılmış ister
// ────────────────────────────────────────────────────────
echo "\n── 2. ZIP Formatında Dene ──\n";
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

// Bellekte ZIP oluştur
$zipFile = tempnam(sys_get_temp_dir(), 'fatura_') . '.zip';
$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE);
$zip->addFromString($faturaNo . '.xml', $ublXml);
$zip->close();
$zipData = file_get_contents($zipFile);
unlink($zipFile);

echo "  → belgeTuru=[EFATURA] mimeType=[application/zip] veri=ZIP ... ";
try {
  $resp = $conn->belgeGonder([
    'vergiTcKimlikNo' => $userId,
    'belgeTuru' => 'EFATURA',
    'belgeNo' => $faturaNo,
    'veri' => base64_encode($zipData),
    'belgeHash' => hash('sha256', $zipData),
    'mimeType' => 'application/zip',
    'belgeVersiyon' => 'V_1_3',
  ]);
  echo "✅ BAŞARILI!\n";
  print_r($resp);
} catch (SoapFault $e) {
  $msg = $e->getMessage();
  echo "❌ $msg\n";
  if (stripos($msg, 'tur') === false && stripos($msg, 'versiyon') === false) {
    echo "   → ZIP formatı geçti! Hata başka bir şey.\n";
  }
}
try {
  $uc->logout(['userId' => $userId]);
} catch (SoapFault $e) {
}

// ────────────────────────────────────────────────────────
// 3. belgeGonderWithValidate metodunu dene
// ────────────────────────────────────────────────────────
echo "\n── 3. belgeGonderWithValidate Dene ──\n";
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

echo "  → belgeGonderWithValidate ... ";
try {
  $resp = $conn->belgeGonderWithValidate([
    'vergiTcKimlikNo' => $userId,
    'belgeTuru' => 'EFATURA',
    'belgeNo' => $faturaNo,
    'veri' => base64_encode($ublXml),
    'belgeHash' => hash('sha256', $ublXml),
    'mimeType' => 'application/xml',
    'belgeVersiyon' => 'V_1_3',
  ]);
  echo "✅ BAŞARILI!\n";
  print_r($resp);
} catch (SoapFault $e) {
  echo "❌ " . $e->getMessage() . "\n";
}
try {
  $uc->logout(['userId' => $userId]);
} catch (SoapFault $e) {
}

// ────────────────────────────────────────────────────────
// 4. gidenBelgeParametreleri ile gönder (farklı metod)
// ────────────────────────────────────────────────────────
echo "\n── 4. gidenBelgeParametreleri ile Gönder ──\n";
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);

echo "  → belgeGonder (gidenBelgeParametreleri struct) ... ";
try {
  $resp = $conn->belgeGonder([
    'vergiTcKimlikNo' => $userId,
    'belgeTuru' => 'EFATURA',
    'belgeNo' => $faturaNo,
    'veri' => base64_encode($ublXml),
    'belgeHash' => hash('sha256', $ublXml),
    'mimeType' => 'application/xml',
    'belgeVersiyon' => 'V_1_3',
    'gonderenEtiket' => 'urn:mail:defaultpk@' . $userId,
    'alanEtiket' => 'urn:mail:defaultpk@' . $userId,
  ]);
  echo "✅ BAŞARILI!\n";
  print_r($resp);
} catch (SoapFault $e) {
  echo "❌ " . $e->getMessage() . "\n";
}
try {
  $uc->logout(['userId' => $userId]);
} catch (SoapFault $e) {
}

echo "\n✅ Tüm Testler Tamamlandı\n";
echo '</pre>';
