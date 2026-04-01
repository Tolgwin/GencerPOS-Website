<?php
// temiz_xml_test.php

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

// ── XML'i DOMDocument ile üret — encoding sorunu olmaz ──
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = false;

// Root
$invoice = $dom->createElementNS(
    'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', 'Invoice'
);
$invoice->setAttributeNS(
    'http://www.w3.org/2000/xmlns/',
    'xmlns:cac',
    'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2'
);
$invoice->setAttributeNS(
    'http://www.w3.org/2000/xmlns/',
    'xmlns:cbc',
    'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
);
$dom->appendChild($invoice);

$cbc = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
$cac = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

function addCbc(DOMDocument $dom, DOMElement $parent, string $ns, string $name, string $value): void {
    $el = $dom->createElementNS($ns, 'cbc:' . $name);
    $el->appendChild($dom->createTextNode($value));
    $parent->appendChild($el);
}

addCbc($dom, $invoice, $cbc, 'UBLVersionID',        '2.1');
addCbc($dom, $invoice, $cbc, 'CustomizationID',     'TR1.2');
addCbc($dom, $invoice, $cbc, 'ProfileID',           'TICARIFATURA');
addCbc($dom, $invoice, $cbc, 'ID',                  $faturaNo);
addCbc($dom, $invoice, $cbc, 'CopyIndicator',       'false');
addCbc($dom, $invoice, $cbc, 'UUID',                $uuid);
addCbc($dom, $invoice, $cbc, 'IssueDate',           $faturaDate);
addCbc($dom, $invoice, $cbc, 'IssueTime',           $faturaTime);
addCbc($dom, $invoice, $cbc, 'InvoiceTypeCode',     'SATIS');
addCbc($dom, $invoice, $cbc, 'DocumentCurrencyCode','TRY');
addCbc($dom, $invoice, $cbc, 'LineCountNumeric',    '1');

// AccountingSupplierParty
$asp  = $dom->createElementNS($cac, 'cac:AccountingSupplierParty'); $invoice->appendChild($asp);
$p1   = $dom->createElementNS($cac, 'cac:Party');                   $asp->appendChild($p1);
$pi1  = $dom->createElementNS($cac, 'cac:PartyIdentification');     $p1->appendChild($pi1);
$id1  = $dom->createElementNS($cbc, 'cbc:ID');
$id1->setAttribute('schemeID', 'VKN');
$id1->appendChild($dom->createTextNode($userId));
$pi1->appendChild($id1);
$pn1  = $dom->createElementNS($cac, 'cac:PartyName');               $p1->appendChild($pn1);
addCbc($dom, $pn1, $cbc, 'Name', 'TEST SATICI A.S.');
$pa1  = $dom->createElementNS($cac, 'cac:PostalAddress');           $p1->appendChild($pa1);
addCbc($dom, $pa1, $cbc, 'StreetName',           'Test Sokak No:1');
addCbc($dom, $pa1, $cbc, 'CitySubdivisionName',  'Sisli');
addCbc($dom, $pa1, $cbc, 'CityName',             'Istanbul');
$co1  = $dom->createElementNS($cac, 'cac:Country');                 $pa1->appendChild($co1);
addCbc($dom, $co1, $cbc, 'Name', 'Turkiye');
$pts1 = $dom->createElementNS($cac, 'cac:PartyTaxScheme');          $p1->appendChild($pts1);
$cid1 = $dom->createElementNS($cbc, 'cbc:CompanyID');
$cid1->appendChild($dom->createTextNode($userId));
$pts1->appendChild($cid1);
$ts1  = $dom->createElementNS($cac, 'cac:TaxScheme');               $pts1->appendChild($ts1);
addCbc($dom, $ts1, $cbc, 'Name', 'VKN');

// AccountingCustomerParty
$acp  = $dom->createElementNS($cac, 'cac:AccountingCustomerParty'); $invoice->appendChild($acp);
$p2   = $dom->createElementNS($cac, 'cac:Party');                   $acp->appendChild($p2);
$pi2  = $dom->createElementNS($cac, 'cac:PartyIdentification');     $p2->appendChild($pi2);
$id2  = $dom->createElementNS($cbc, 'cbc:ID');
$id2->setAttribute('schemeID', 'VKN');
$id2->appendChild($dom->createTextNode($userId));
$pi2->appendChild($id2);
$pn2  = $dom->createElementNS($cac, 'cac:PartyName');               $p2->appendChild($pn2);
addCbc($dom, $pn2, $cbc, 'Name', 'TEST ALICI A.S.');
$pa2  = $dom->createElementNS($cac, 'cac:PostalAddress');           $p2->appendChild($pa2);
addCbc($dom, $pa2, $cbc, 'StreetName',          'Alici Sokak No:2');
addCbc($dom, $pa2, $cbc, 'CitySubdivisionName', 'Kadikoy');
addCbc($dom, $pa2, $cbc, 'CityName',            'Istanbul');
$co2  = $dom->createElementNS($cac, 'cac:Country');                 $pa2->appendChild($co2);
addCbc($dom, $co2, $cbc, 'Name', 'Turkiye');
$pts2 = $dom->createElementNS($cac, 'cac:PartyTaxScheme');          $p2->appendChild($pts2);
$cid2 = $dom->createElementNS($cbc, 'cbc:CompanyID');
$cid2->appendChild($dom->createTextNode($userId));
$pts2->appendChild($cid2);
$ts2  = $dom->createElementNS($cac, 'cac:TaxScheme');               $pts2->appendChild($ts2);
addCbc($dom, $ts2, $cbc, 'Name', 'VKN');

// TaxTotal
$tt   = $dom->createElementNS($cac, 'cac:TaxTotal');                $invoice->appendChild($tt);
$ta   = $dom->createElementNS($cbc, 'cbc:TaxAmount');
$ta->setAttribute('currencyID', 'TRY');
$ta->appendChild($dom->createTextNode('18.00'));
$tt->appendChild($ta);
$tst  = $dom->createElementNS($cac, 'cac:TaxSubtotal');             $tt->appendChild($tst);
$tba  = $dom->createElementNS($cbc, 'cbc:TaxableAmount');
$tba->setAttribute('currencyID', 'TRY');
$tba->appendChild($dom->createTextNode('100.00'));
$tst->appendChild($tba);
$ta2  = $dom->createElementNS($cbc, 'cbc:TaxAmount');
$ta2->setAttribute('currencyID', 'TRY');
$ta2->appendChild($dom->createTextNode('18.00'));
$tst->appendChild($ta2);
$tc   = $dom->createElementNS($cac, 'cac:TaxCategory');             $tst->appendChild($tc);
addCbc($dom, $tc, $cbc, 'Percent', '18');
$tsc  = $dom->createElementNS($cac, 'cac:TaxScheme');               $tc->appendChild($tsc);
addCbc($dom, $tsc, $cbc, 'Name',        'KDV');
addCbc($dom, $tsc, $cbc, 'TaxTypeCode', '0015');

// LegalMonetaryTotal
$lmt  = $dom->createElementNS($cac, 'cac:LegalMonetaryTotal');      $invoice->appendChild($lmt);
foreach ([
    'LineExtensionAmount' => '100.00',
    'TaxExclusiveAmount'  => '100.00',
    'TaxInclusiveAmount'  => '118.00',
    'PayableAmount'       => '118.00',
] as $tag => $val) {
    $el = $dom->createElementNS($cbc, 'cbc:' . $tag);
    $el->setAttribute('currencyID', 'TRY');
    $el->appendChild($dom->createTextNode($val));
    $lmt->appendChild($el);
}

// InvoiceLine
$il   = $dom->createElementNS($cac, 'cac:InvoiceLine');             $invoice->appendChild($il);
addCbc($dom, $il, $cbc, 'ID', '1');
$iq   = $dom->createElementNS($cbc, 'cbc:InvoicedQuantity');
$iq->setAttribute('unitCode', 'C62');
$iq->appendChild($dom->createTextNode('1'));
$il->appendChild($iq);
$lea  = $dom->createElementNS($cbc, 'cbc:LineExtensionAmount');
$lea->setAttribute('currencyID', 'TRY');
$lea->appendChild($dom->createTextNode('100.00'));
$il->appendChild($lea);
$tt2  = $dom->createElementNS($cac, 'cac:TaxTotal');                $il->appendChild($tt2);
$ta3  = $dom->createElementNS($cbc, 'cbc:TaxAmount');
$ta3->setAttribute('currencyID', 'TRY');
$ta3->appendChild($dom->createTextNode('18.00'));
$tt2->appendChild($ta3);
$tst2 = $dom->createElementNS($cac, 'cac:TaxSubtotal');             $tt2->appendChild($tst2);
$tba2 = $dom->createElementNS($cbc, 'cbc:TaxableAmount');
$tba2->setAttribute('currencyID', 'TRY');
$tba2->appendChild($dom->createTextNode('100.00'));
$tst2->appendChild($tba2);
$ta4  = $dom->createElementNS($cbc, 'cbc:TaxAmount');
$ta4->setAttribute('currencyID', 'TRY');
$ta4->appendChild($dom->createTextNode('18.00'));
$tst2->appendChild($ta4);
$tc2  = $dom->createElementNS($cac, 'cac:TaxCategory');             $tst2->appendChild($tc2);
addCbc($dom, $tc2, $cbc, 'Percent', '18');
$tsc2 = $dom->createElementNS($cac, 'cac:TaxScheme');               $tc2->appendChild($tsc2);
addCbc($dom, $tsc2, $cbc, 'Name',        'KDV');
addCbc($dom, $tsc2, $cbc, 'TaxTypeCode', '0015');
$item = $dom->createElementNS($cac, 'cac:Item');                    $il->appendChild($item);
addCbc($dom, $item, $cbc, 'Description', 'Test urun');
addCbc($dom, $item, $cbc, 'Name',        'Test Urun');
$pr   = $dom->createElementNS($cac, 'cac:Price');                   $il->appendChild($pr);
$pa   = $dom->createElementNS($cbc, 'cbc:PriceAmount');
$pa->setAttribute('currencyID', 'TRY');
$pa->appendChild($dom->createTextNode('100.00'));
$pr->appendChild($pa);

// ── saveXML ile üret — kesinlikle BOM yok ──
$ublXml = $dom->saveXML();

// Güvenlik: BOM + whitespace temizle
$ublXml = preg_replace('/^\xEF\xBB\xBF/', '', $ublXml);
$ublXml = ltrim($ublXml);

// Doğrulama
echo "XML ilk 100 karakter (hex):\n";
for ($i = 0; $i < min(100, strlen($ublXml)); $i++) {
    echo sprintf('%02X ', ord($ublXml[$i]));
}
echo "\n\nXML ilk 100 karakter (text): " . substr($ublXml, 0, 100) . "\n";
echo "XML uzunluk: " . strlen($ublXml) . " byte\n\n";

// ────────────────────────────────────────────────────────
// TEST 1 — temelKontrollerIleBelgeGonder
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
// TEST 2 — belgeGonder EFATURA + V_1_3
// ────────────────────────────────────────────────────────
echo "\n── TEST 2: belgeGonder EFATURA + V_1_3 ──\n";
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);
try {
    $resp = $conn->belgeGonder([
        'vergiTcKimlikNo' => $userId,
        'belgeTuru'       => 'EFATURA',
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
// TEST 3 — belgeGonderExt EFATURA + V_1_3
// ────────────────────────────────────────────────────────
echo "\n── TEST 3: belgeGonderExt EFATURA + V_1_3 ──\n";
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
