<?php
// wsdl_oku.php

$certPath      = 'C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\cacert.pem';
$connectorWsdl = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws/connectorService?wsdl';

set_time_limit(60);

$ctx = stream_context_create(['ssl' => [
    'verify_peer'      => true,
    'verify_peer_name' => true,
    'cafile'           => $certPath,
]]);

echo '<pre>';

// ── WSDL içeriğini çek ──
$wsdlContent = file_get_contents($connectorWsdl, false, $ctx);
if (!$wsdlContent) {
    echo "❌ WSDL alınamadı\n";
    exit;
}

echo "✅ WSDL alındı (" . strlen($wsdlContent) . " byte)\n\n";

// ── temelKontrollerIleBelgeGonder parametrelerini bul ──
$dom = new DOMDocument();
$dom->loadXML($wsdlContent);
$xpath = new DOMXPath($dom);

// Tüm namespace'leri kaydet
echo "── WSDL Namespace'leri ──\n";
$xpath->registerNamespace('wsdl',  'http://schemas.xmlsoap.org/wsdl/');
$xpath->registerNamespace('xs',    'http://www.w3.org/2001/XMLSchema');
$xpath->registerNamespace('tns',   'http://service.connector.uut.cs.com.tr/');

// Import edilen şemalar
echo "\n── Import/Include edilen XSD'ler ──\n";
$imports = $xpath->query('//*[local-name()="import" or local-name()="include"]');
foreach ($imports as $imp) {
    $ns  = $imp->getAttribute('namespace');
    $loc = $imp->getAttribute('schemaLocation') ?: $imp->getAttribute('location');
    if ($ns || $loc) {
        echo "  NS : $ns\n";
        echo "  LOC: $loc\n\n";
    }
}

// temelKontrollerIleBelgeGonder ile ilgili tüm elementler
echo "── temelKontrollerIleBelgeGonder Şeması ──\n";
$nodes = $xpath->query('//*[contains(local-name(), "temelKontrol") or contains(local-name(), "BelgeGonder")]');
foreach ($nodes as $node) {
    echo "  <" . $node->nodeName . ">\n";
    foreach ($node->attributes as $attr) {
        echo "    @{$attr->name} = {$attr->value}\n";
    }
    // Alt elementler
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE) {
            echo "    <" . $child->nodeName . ">\n";
            foreach ($child->attributes as $a) {
                echo "      @{$a->name} = {$a->value}\n";
            }
        }
    }
}

// veri alanının tipi
echo "\n── 'veri' alanı tipi ──\n";
$veriNodes = $xpath->query('//*[@name="veri"]');
foreach ($veriNodes as $v) {
    echo "  <" . $v->nodeName . ">\n";
    foreach ($v->attributes as $attr) {
        echo "    @{$attr->name} = {$attr->value}\n";
    }
}

// belgeVersiyon enum değerleri
echo "\n── belgeVersiyon enum değerleri ──\n";
$verNodes = $xpath->query('//*[@name="belgeVersiyon" or @name="BelgeVersiyon"]');
foreach ($verNodes as $v) {
    echo "  <" . $v->nodeName . ">\n";
    foreach ($v->attributes as $attr) {
        echo "    @{$attr->name} = {$attr->value}\n";
    }
    // enum değerleri
    $enums = $xpath->query('.//*[local-name()="enumeration"]', $v);
    foreach ($enums as $e) {
        echo "    enum: " . $e->getAttribute('value') . "\n";
    }
}

// mimeType enum değerleri
echo "\n── mimeType enum değerleri ──\n";
$mimeNodes = $xpath->query('//*[@name="mimeType" or @name="MimeType"]');
foreach ($mimeNodes as $v) {
    echo "  <" . $v->nodeName . ">\n";
    foreach ($v->attributes as $attr) {
        echo "    @{$attr->name} = {$attr->value}\n";
    }
    $enums = $xpath->query('.//*[local-name()="enumeration"]', $v);
    foreach ($enums as $e) {
        echo "    enum: " . $e->getAttribute('value') . "\n";
    }
}

// Tüm WSDL'i de göster (şema kısmı)
echo "\n── Ham WSDL (ilk 8000 karakter) ──\n";
echo htmlspecialchars(substr($wsdlContent, 0, 8000));

echo "\n</pre>";
