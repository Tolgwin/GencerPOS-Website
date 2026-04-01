<?php
// xsd_oku.php

$certPath = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$xsdUrl   = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?xsd=1';

$ctx = stream_context_create(['ssl' => [
    'verify_peer'      => true,
    'verify_peer_name' => true,
    'cafile'           => $certPath,
]]);

$xsd = file_get_contents($xsdUrl, false, $ctx);
if (!$xsd) { die("❌ XSD alınamadı\n"); }

echo '<pre>';
echo "✅ XSD alındı (" . strlen($xsd) . " byte)\n\n";

$dom = new DOMDocument();
$dom->loadXML($xsd);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

// ── Aranacak type isimleri ──
$hedefler = [
    'belgeGonder',
    'belgeGonderExt',
    'belgeGonderWithValidate',
    'belgeGonderExtWithValidate',
    'temelKontrollerIleBelgeGonder',
];

foreach ($hedefler as $tip) {
    $nodes = $xpath->query("//xs:complexType[@name='$tip']");
    if ($nodes->length === 0) {
        echo "── $tip → ❌ bulunamadı\n\n";
        continue;
    }
    foreach ($nodes as $node) {
        echo "── complexType: $tip ──\n";
        // Tüm element child'ları
        $elems = $xpath->query('.//xs:element', $node);
        foreach ($elems as $el) {
            $name    = $el->getAttribute('name');
            $type    = $el->getAttribute('type');
            $minOcc  = $el->getAttribute('minOccurs') ?: '1';
            $maxOcc  = $el->getAttribute('maxOccurs') ?: '1';
            echo "  element: $name | type: $type | min: $minOcc | max: $maxOcc\n";
        }
        echo "\n";
    }
}

// ── Tüm enum'ları bul (mimeType, belgeVersiyon vb.) ──
echo "── Enum Tipleri ──\n";
$simpleTypes = $xpath->query("//xs:simpleType");
foreach ($simpleTypes as $st) {
    $name = $st->getAttribute('name');
    $enums = $xpath->query('.//xs:enumeration', $st);
    if ($enums->length > 0) {
        echo "  $name:\n";
        foreach ($enums as $e) {
            echo "    - " . $e->getAttribute('value') . "\n";
        }
    }
}

// ── Ham XSD (ilk 6000 karakter) ──
echo "\n── Ham XSD (ilk 6000 karakter) ──\n";
echo htmlspecialchars(substr($xsd, 0, 6000));

echo '</pre>';
