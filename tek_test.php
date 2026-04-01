<?php
// duzeltilmis_test.php

$certPath      = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$userId        = '3930311899';
$password      = 'vi39mmkgww0301';
$lang          = 'tr';
$userWsdl      = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/userService?wsdl';
$connectorWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?wsdl';

set_time_limit(120);
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

// ── DOMDocument ile temiz XML ──
$dom     = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = false;
$cbc_ns  = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
$cac_ns  = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
$inv_ns  = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

$invoice = $dom->createElementNS($inv_ns, 'Invoice');
$invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', $cac_ns);
$invoice->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', $cbc_ns);
$dom->appendChild($invoice);

function cbc(DOMDocument $d, string $ns, string $tag, string $val): DOMElement {
    $e = $d->createElementNS($ns, 'cbc:'.$tag);
    $e->appendChild($d->createTextNode($val));
    return $e;
}
function cac(DOMDocument $d, string $ns, string $tag): DOMElement {
    return $d->createElementNS($ns, 'cac:'.$tag);
}
function amt(DOMDocument $d, string $ns, string $tag, string $val): DOMElement {
    $e = $d->createElementNS($ns, 'cbc:'.$tag);
    $e->setAttribute('currencyID', 'TRY');
    $e->appendChild($d->createTextNode($val));
    return $e;
}

$invoice->appendChild(cbc($dom,$cbc_ns,'UBLVersionID',        '2.1'));
$invoice->appendChild(cbc($dom,$cbc_ns,'CustomizationID',     'TR1.2'));
$invoice->appendChild(cbc($dom,$cbc_ns,'ProfileID',           'TICARIFATURA'));
$invoice->appendChild(cbc($dom,$cbc_ns,'ID',                  $faturaNo));
$invoice->appendChild(cbc($dom,$cbc_ns,'CopyIndicator',       'false'));
$invoice->appendChild(cbc($dom,$cbc_ns,'UUID',                $uuid));
$invoice->appendChild(cbc($dom,$cbc_ns,'IssueDate',           $faturaDate));
$invoice->appendChild(cbc($dom,$cbc_ns,'IssueTime',           $faturaTime));
$invoice->appendChild(cbc($dom,$cbc_ns,'InvoiceTypeCode',     'SATIS'));
$invoice->appendChild(cbc($dom,$cbc_ns,'DocumentCurrencyCode','TRY'));
$invoice->appendChild(cbc($dom,$cbc_ns,'LineCountNumeric',    '1'));

// Supplier
$asp = cac($dom,$cac_ns,'AccountingSupplierParty'); $invoice->appendChild($asp);
$p1  = cac($dom,$cac_ns,'Party');                   $asp->appendChild($p1);
$pi1 = cac($dom,$cac_ns,'PartyIdentification');     $p1->appendChild($pi1);
$i1  = $dom->createElementNS($cbc_ns,'cbc:ID');
$i1->setAttribute('schemeID','VKN');
$i1->appendChild($dom->createTextNode($userId)); $pi1->appendChild($i1);
$pn1 = cac($dom,$cac_ns,'PartyName');               $p1->appendChild($pn1);
$pn1->appendChild(cbc($dom,$cbc_ns,'Name','TEST SATICI'));
$pts1 = cac($dom,$cac_ns,'PartyTaxScheme');          $p1->appendChild($pts1);
$ci1  = $dom->createElementNS($cbc_ns,'cbc:CompanyID');
$ci1->appendChild($dom->createTextNode($userId));   $pts1->appendChild($ci1);
$ts1  = cac($dom,$cac_ns,'TaxScheme');               $pts1->appendChild($ts1);
$ts1->appendChild(cbc($dom,$cbc_ns,'Name','VKN'));

// Customer
$acp = cac($dom,$cac_ns,'AccountingCustomerParty'); $invoice->appendChild($acp);
$p2  = cac($dom,$cac_ns,'Party');                   $acp->appendChild($p2);
$pi2 = cac($dom,$cac_ns,'PartyIdentification');     $p2->appendChild($pi2);
$i2  = $dom->createElementNS($cbc_ns,'cbc:ID');
$i2->setAttribute('schemeID','VKN');
$i2->appendChild($dom->createTextNode($userId));    $pi2->appendChild($i2);
$pn2 = cac($dom,$cac_ns,'PartyName');               $p2->appendChild($pn2);
$pn2->appendChild(cbc($dom,$cbc_ns,'Name','TEST ALICI'));
$pts2 = cac($dom,$cac_ns,'PartyTaxScheme');          $p2->appendChild($pts2);
$ci2  = $dom->createElementNS($cbc_ns,'cbc:CompanyID');
$ci2->appendChild($dom->createTextNode($userId));   $pts2->appendChild($ci2);
$ts2  = cac($dom,$cac_ns,'TaxScheme');               $pts2->appendChild($ts2);
$ts2->appendChild(cbc($dom,$cbc_ns,'Name','VKN'));

// TaxTotal
$tt  = cac($dom,$cac_ns,'TaxTotal');    $invoice->appendChild($tt);
$tt->appendChild(amt($dom,$cbc_ns,'TaxAmount','18.00'));
$tst = cac($dom,$cac_ns,'TaxSubtotal'); $tt->appendChild($tst);
$tst->appendChild(amt($dom,$cbc_ns,'TaxableAmount','100.00'));
$tst->appendChild(amt($dom,$cbc_ns,'TaxAmount','18.00'));
$tc  = cac($dom,$cac_ns,'TaxCategory'); $tst->appendChild($tc);
$tc->appendChild(cbc($dom,$cbc_ns,'Percent','18'));
$tsc = cac($dom,$cac_ns,'TaxScheme');   $tc->appendChild($tsc);
$tsc->appendChild(cbc($dom,$cbc_ns,'Name','KDV'));
$tsc->appendChild(cbc($dom,$cbc_ns,'TaxTypeCode','0015'));

// LegalMonetaryTotal
$lmt = cac($dom,$cac_ns,'LegalMonetaryTotal'); $invoice->appendChild($lmt);
$lmt->appendChild(amt($dom,$cbc_ns,'LineExtensionAmount','100.00'));
$lmt->appendChild(amt($dom,$cbc_ns,'TaxExclusiveAmount', '100.00'));
$lmt->appendChild(amt($dom,$cbc_ns,'TaxInclusiveAmount', '118.00'));
$lmt->appendChild(amt($dom,$cbc_ns,'PayableAmount',      '118.00'));

// InvoiceLine
$il  = cac($dom,$cac_ns,'InvoiceLine'); $invoice->appendChild($il);
$il->appendChild(cbc($dom,$cbc_ns,'ID','1'));
$iq  = $dom->createElementNS($cbc_ns,'cbc:InvoicedQuantity');
$iq->setAttribute('unitCode','C62');
$iq->appendChild($dom->createTextNode('1')); $il->appendChild($iq);
$il->appendChild(amt($dom,$cbc_ns,'LineExtensionAmount','100.00'));
$tt2 = cac($dom,$cac_ns,'TaxTotal');    $il->appendChild($tt2);
$tt2->appendChild(amt($dom,$cbc_ns,'TaxAmount','18.00'));
$tst2 = cac($dom,$cac_ns,'TaxSubtotal'); $tt2->appendChild($tst2);
$tst2->appendChild(amt($dom,$cbc_ns,'TaxableAmount','100.00'));
$tst2->appendChild(amt($dom,$cbc_ns,'TaxAmount','18.00'));
$tc2  = cac($dom,$cac_ns,'TaxCategory'); $tst2->appendChild($tc2);
$tc2->appendChild(cbc($dom,$cbc_ns,'Percent','18'));
$tsc2 = cac($dom,$cac_ns,'TaxScheme');   $tc2->appendChild($tsc2);
$tsc2->appendChild(cbc($dom,$cbc_ns,'Name','KDV'));
$tsc2->appendChild(cbc($dom,$cbc_ns,'TaxTypeCode','0015'));
$itm  = cac($dom,$cac_ns,'Item');        $il->appendChild($itm);
$itm->appendChild(cbc($dom,$cbc_ns,'Description','Test urun'));
$itm->appendChild(cbc($dom,$cbc_ns,'Name','Test Urun'));
$pr   = cac($dom,$cac_ns,'Price');       $il->appendChild($pr);
$pr->appendChild(amt($dom,$cbc_ns,'PriceAmount','100.00'));

// ── XML üret — saveXML() düz metin döndürür ──
$ublXml = $dom->saveXML();

// ── ÇİFT ENCODE KONTROLÜ ──
// PHP SoapClient base64Binary tipini OTOMATİK encode eder
// Bu yüzden ham XML'i geçiyoruz, base64_encode() ÇAĞIRMIYORUZ
$xmlBytes = $ublXml; // ham XML string

echo "XML uzunluk : " . strlen($xmlBytes) . " byte\n";
echo "İlk 38 byte: " . bin2hex(substr($xmlBytes, 0, 38)) . "\n";
// Beklenen: 3c3f786d6c... (<?xml)
echo "SHA256      : " . hash('sha256', $xmlBytes) . "\n\n";
flush();

// ── TEST 1: temelKontrollerIleBelgeGonder — ham XML ──
echo "── TEST 1: temelKontrollerIleBelgeGonder (ham XML) ──\n";
flush();
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);
try {
    $resp = $conn->temelKontrollerIleBelgeGonder([
        'vergiTcKimlikNo' => $userId,
        'belgeNo'         => $faturaNo,
        'veri'            => $xmlBytes,   // ← base64_encode YOK
        'belgeHash'       => hash('sha256', $xmlBytes),
        'mimeType'        => 'application/xml',
        'belgeVersiyon'   => 'V_1_3',
    ]);
    echo "✅ BAŞARILI! belgeOid: " . ($resp->belgeOid ?? print_r($resp,true)) . "\n";
} catch (SoapFault $e) {
    echo "❌ " . $e->getMessage() . "\n";
}
try { $uc->logout(['userId' => $userId]); } catch (Throwable $e) {}
flush();

// ── TEST 2: belgeGonder EFATURA — ham XML ──
echo "\n── TEST 2: belgeGonder EFATURA (ham XML) ──\n";
flush();
[$uc, $sid] = freshLogin($userWsdl, $soapOpts, $userId, $password, $lang);
$conn = freshConn($connectorWsdl, $soapOpts, $certPath, $sid);
try {
    $resp = $conn->belgeGonder([
        'vergiTcKimlikNo' => $userId,
        'belgeTuru'       => 'EFATURA',
        'belgeNo'         => $faturaNo,
        'veri'            => $xmlBytes,   // ← base64_encode YOK
        'belgeHash'       => hash('sha256', $xmlBytes),
        'mimeType'        => 'application/xml',
        'belgeVersiyon'   => 'V_1_3',
    ]);
    echo "✅ BAŞARILI! belgeOid: " . ($resp->belgeOid ?? print_r($resp,true)) . "\n";
} catch (SoapFault $e) {
    echo "❌ " . $e->getMessage() . "\n";
}
try { $uc->logout(['userId' => $userId]); } catch (Throwable $e) {}

echo "\n✅ Bitti\n";
echo '</pre>';

