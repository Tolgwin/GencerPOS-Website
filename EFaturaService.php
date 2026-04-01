<?php
// EFaturaService.php

class EFaturaService
{
    private array $config;
    private string $cookieHeader = '';
    private $userClient;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    // ── Login ────────────────────────────────────────────────────
    public function login(): void
    {
        $soapOptions = [
            'trace'              => true,
            'exceptions'         => true,
            'cache_wsdl'         => WSDL_CACHE_NONE,
            'connection_timeout' => 30,
            'encoding'           => 'UTF-8',
        ];

        $this->userClient = new SoapClient($this->config['user_wsdl'], $soapOptions);

        $this->userClient->wsLogin([
            'userId'   => $this->config['username'],
            'password' => $this->config['password'],
            'lang'     => 'tr',
        ]);

        // Cookie al
        if (preg_match_all(
            '/Set-Cookie:\s*([^;]+);/i',
            $this->userClient->__getLastResponseHeaders(),
            $matches
        )) {
            $this->cookieHeader = implode('; ', array_map('trim', $matches[1]));
        } else {
            throw new RuntimeException('Session cookie alınamadı.');
        }
    }

    // ── Belge Gönder ─────────────────────────────────────────────
    public function belgeGonder(string $xmlContent, string $belgeNo): string
    {
        $base64Xml = base64_encode($xmlContent);
        $belgeHash = strtoupper(md5($xmlContent));

        $envelope = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:con="http://service.connector.uut.cs.com.tr/">
  <soapenv:Header/>
  <soapenv:Body>
    <con:belgeGonder>
      <vergiTcKimlikNo>' . $this->config['username'] . '</vergiTcKimlikNo>
      <belgeTuru>FATURA_UBL</belgeTuru>
      <belgeNo>' . htmlspecialchars($belgeNo) . '</belgeNo>
      <veri>' . $base64Xml . '</veri>
      <belgeHash>' . $belgeHash . '</belgeHash>
      <mimeType>application/xml</mimeType>
      <belgeVersiyon>1.0</belgeVersiyon>
      <erpKodu></erpKodu>
      <alanEtiket></alanEtiket>
      <gonderenEtiket></gonderenEtiket>
      <xsltAdi></xsltAdi>
      <xsltVeri></xsltVeri>
      <subeKodu></subeKodu>
    </con:belgeGonder>
  </soapenv:Body>
</soapenv:Envelope>';

        $ch = curl_init($this->config['connector_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $envelope,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=UTF-8',
                'SOAPAction: "http://service.connector.uut.cs.com.tr/belgeGonder"',
                'Cookie: ' . $this->cookieHeader,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("cURL hatası: $curlError");
        }
        if ($httpCode !== 200) {
            throw new RuntimeException("HTTP $httpCode — Yanıt: $response");
        }

        // ETTN parse
        $dom = new DOMDocument();
        $dom->loadXML($response);
        $xpath = new DOMXPath($dom);

        $fault = $xpath->query('//faultstring');
        if ($fault->length > 0) {
            throw new RuntimeException("SOAP Fault: " . $fault->item(0)->nodeValue);
        }

        $result = $xpath->query('//*[local-name()="return"] | //*[local-name()="ettn"]');
        if ($result->length > 0) {
            return trim($result->item(0)->nodeValue);
        }

        // Herhangi bir leaf node değeri döndür
        $any = $xpath->query('//*[local-name()="Body"]//*[not(*)]');
        if ($any->length > 0) {
            return trim($any->item(0)->nodeValue);
        }

        throw new RuntimeException("ETTN parse edilemedi. Ham yanıt: $response");
    }

    // ── Logout ───────────────────────────────────────────────────
    public function logout(): void
    {
        try {
            $this->userClient->wsLogout();
        } catch (SoapFault) {
            // Kritik değil, sessizce geç
        }
    }
    // EFaturaService.php'ye bu metodu ekleyin:

    public function kayitliKullaniciSorgula(string $vkn): array
    {
        $envelope = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:con="http://service.connector.uut.cs.com.tr/">
  <soapenv:Header/>
  <soapenv:Body>
    <con:kayitliKullaniciListele>
      <vknList>' . htmlspecialchars($vkn) . '</vknList>
    </con:kayitliKullaniciListele>
  </soapenv:Body>
</soapenv:Envelope>';

        $ch = curl_init($this->config['connector_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $envelope,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=UTF-8',
                'SOAPAction: ""',
                'Cookie: ' . $this->cookieHeader,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $dom = new DOMDocument();
        @$dom->loadXML($response);
        $xpath = new DOMXPath($dom);

        // Kayıtlı kullanıcı varsa unvan döner
        $unvanNode = $xpath->query('//*[local-name()="unvan"] | //*[local-name()="title"]');
        if ($unvanNode->length > 0) {
            $unvan = trim($unvanNode->item(0)->nodeValue);
            return ['kayitli' => true, 'unvan' => $unvan];
        }

        return ['kayitli' => false, 'unvan' => ''];
    }
}

