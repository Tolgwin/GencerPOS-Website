<?php
// UBLBuilder.php

class UBLBuilder
{
    public static function build(array $f): string
    {
        $uuid        = self::uuid();
        $tarih       = $f['tarih']       ?? date('Y-m-d');
        $saat        = date('H:i:s');
        $paraBirimi  = $f['para_birimi'] ?? 'TRY';
        $tip         = $f['tip']         ?? 'SATIS';
        $satirlar    = $f['satirlar']    ?? [];
        $satirSayisi = count($satirlar);

        $matrah = number_format((float)($f['matrah'] ?? 0), 2, '.', '');
        $kdv    = number_format((float)($f['kdv']    ?? 0), 2, '.', '');
        $toplam = number_format((float)($f['toplam'] ?? 0), 2, '.', '');

        // Satır XML'leri
        $satirXml = '';
        foreach ($satirlar as $i => $s) {
            $sMatrah = number_format((float)($s['matrah']      ?? 0), 2, '.', '');
            $sFiyat  = number_format((float)($s['birim_fiyat'] ?? 0), 2, '.', '');
            $sMiktar = number_format((float)($s['miktar']      ?? 1), 3, '.', '');
            $sKdvT   = number_format((float)($s['kdv_tutar']   ?? 0), 2, '.', '');
            $sKdvO   = (int)($s['kdv_oran'] ?? 20);
            $sBirim  = htmlspecialchars($s['birim'] ?? 'C62');
            $sAdi    = htmlspecialchars($s['aciklama'] ?? '');

            $satirXml .= "
  <cac:InvoiceLine>
    <cbc:ID>" . ($i + 1) . "</cbc:ID>
    <cbc:InvoicedQuantity unitCode=\"{$sBirim}\">{$sMiktar}</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID=\"{$paraBirimi}\">{$sMatrah}</cbc:LineExtensionAmount>
    <cac:TaxTotal>
      <cbc:TaxAmount currencyID=\"{$paraBirimi}\">{$sKdvT}</cbc:TaxAmount>
      <cac:TaxSubtotal>
        <cbc:TaxableAmount currencyID=\"{$paraBirimi}\">{$sMatrah}</cbc:TaxableAmount>
        <cbc:TaxAmount currencyID=\"{$paraBirimi}\">{$sKdvT}</cbc:TaxAmount>
        <cac:TaxCategory>
          <cbc:Percent>{$sKdvO}</cbc:Percent>
          <cac:TaxScheme>
            <cbc:Name>KDV</cbc:Name>
            <cbc:TaxTypeCode>0015</cbc:TaxTypeCode>
          </cac:TaxScheme>
        </cac:TaxCategory>
      </cac:TaxSubtotal>
    </cac:TaxTotal>
    <cac:Item>
      <cbc:Name>{$sAdi}</cbc:Name>
    </cac:Item>
    <cac:Price>
      <cbc:PriceAmount currencyID=\"{$paraBirimi}\">{$sFiyat}</cbc:PriceAmount>
    </cac:Price>
  </cac:InvoiceLine>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
  <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
  <cbc:ProfileID>TICARIFATURA</cbc:ProfileID>
  <cbc:ID>' . htmlspecialchars($f['fatura_no']) . '</cbc:ID>
  <cbc:CopyIndicator>false</cbc:CopyIndicator>
  <cbc:UUID>' . $uuid . '</cbc:UUID>
  <cbc:IssueDate>' . $tarih . '</cbc:IssueDate>
  <cbc:IssueTime>' . $saat . '</cbc:IssueTime>
  <cbc:InvoiceTypeCode>' . $tip . '</cbc:InvoiceTypeCode>
  <cbc:DocumentCurrencyCode>' . $paraBirimi . '</cbc:DocumentCurrencyCode>
  <cbc:LineCountNumeric>' . $satirSayisi . '</cbc:LineCountNumeric>

  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyIdentification>
        <cbc:ID schemeID="VKN">' . htmlspecialchars($f['gonderen_vkn']) . '</cbc:ID>
      </cac:PartyIdentification>
      <cac:PartyName>
        <cbc:Name>' . htmlspecialchars($f['gonderen_unvan']) . '</cbc:Name>
      </cac:PartyName>
    </cac:Party>
  </cac:AccountingSupplierParty>

  <cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyIdentification>
        <cbc:ID schemeID="VKN">' . htmlspecialchars($f['alici_vkn']) . '</cbc:ID>
      </cac:PartyIdentification>
      <cac:PartyName>
        <cbc:Name>' . htmlspecialchars($f['alici_unvan']) . '</cbc:Name>
      </cac:PartyName>


    </cac:Party>

  </cac:AccountingCustomerParty>

  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="' . $paraBirimi . '">' . $kdv . '</cbc:TaxAmount>
  </cac:TaxTotal>

  <cac:LegalMonetaryTotal>
    <cbc:LineExtensionAmount currencyID="' . $paraBirimi . '">' . $matrah . '</cbc:LineExtensionAmount>
    <cbc:TaxExclusiveAmount currencyID="' . $paraBirimi . '">' . $matrah . '</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="' . $paraBirimi . '">' . $toplam . '</cbc:TaxInclusiveAmount>
    <cbc:PayableAmount currencyID="' . $paraBirimi . '">' . $toplam . '</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>
' . $satirXml . '
</Invoice>';
    }

    private static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
