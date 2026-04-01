<?php
/**
 * Paynkolay Sanal POS Entegrasyonu
 * Dokümantasyon: https://paynkolay.com.tr/entegrasyon/index.html
 */
class PaynkolayService
{
    private string $sx;
    private string $secretKey;
    private bool   $testModu;

    private const URL_SEND_LINK   = 'https://paynkolaytest.nkolayislem.com.tr/Vpos/pay-by-link-create';
    private const URL_ORTAK_LINK  = 'https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link-create';
    private const URL_LINK_SIL    = 'https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link-url-remove';

    private const URL_SEND_LINK_CANLI  = 'https://paynkolay.nkolayislem.com.tr/Vpos/pay-by-link-create';
    private const URL_ORTAK_LINK_CANLI = 'https://paynkolay.nkolayislem.com.tr/Vpos/by-link-create';
    private const URL_LINK_SIL_CANLI   = 'https://paynkolay.nkolayislem.com.tr/Vpos/by-link-url-remove';

    public function __construct(string $sx, string $secretKey, bool $testModu = true)
    {
        $this->sx        = $sx;
        $this->secretKey = $secretKey;
        $this->testModu  = $testModu;
    }

    // ── SHA-512 → Base64 Hash ──────────────────────────────────────
    public function hashHazirla(array $parcalar): string
    {
        $str = implode('|', $parcalar);
        $str = mb_convert_encoding($str, 'UTF-8');
        return base64_encode(hash('sha512', $str, true));
    }

    // ── SMS/E-posta ile Ödeme Linki Gönder ────────────────────────
    // Docs: sx|FULL_NAME|EMAIL|GSM|AMOUNT|LINK_EXPIRATION_TIME|secretKey
    public function linkGonder(array $params): array
    {
        $son = $params['link_bitis'] ?? date('Y-m-d', strtotime('+7 days'));
        $gsm = preg_replace('/\D/', '', $params['gsm'] ?? '');
        // Türkiye kodu ekle: 0 ile başlıyorsa → 90 ile değiştir
        if (strlen($gsm) === 11 && str_starts_with($gsm, '0')) {
            $gsm = '90' . substr($gsm, 1);
        } elseif (strlen($gsm) === 10) {
            $gsm = '90' . $gsm;
        }

        $hash = $this->hashHazirla([
            $this->sx,
            $params['ad_soyad']  ?? '',
            $params['email']     ?? '',
            $gsm,
            number_format((float)($params['tutar'] ?? 0), 2, '.', ''),
            $son,
            $this->secretKey,
        ]);

        $post = [
            'TOKEN'                   => $this->sx,
            'FULL_NAME'               => $params['ad_soyad']   ?? '',
            'EMAIL'                   => $params['email']       ?? '',
            'GSM'                     => $gsm,
            'LINK_AMOUNT_FIXING_TYPE' => 'FIXED',
            'AMOUNT'                  => number_format((float)($params['tutar'] ?? 0), 2, '.', ''),
            'LINK_EXPIRATION_TIME'    => $son,
            'IS_3D_MUST'              => 'true',
            'PAYMENT_SUBJECT'         => $params['konu']        ?? 'Ödeme',
            'EXPLANATION'             => $params['aciklama']    ?? '',
            'CALLBACK_URL'            => $params['callback_url'] ?? '',
            'SEND_SMS'                => !empty($params['gsm'])  ? 'true' : 'false',
            'SEND_EMAIL'              => !empty($params['email']) ? 'true' : 'false',
            'CLIENT_REFERENCE_CODE'   => $params['ref_kod']     ?? '',
            'INSTALLMENT'             => $params['max_taksit']  ?? '1',
            'hashDatav2'              => $hash,
        ];

        $url = $this->testModu ? self::URL_SEND_LINK : self::URL_SEND_LINK_CANLI;
        return $this->istek($url, $post, true);
    }

    // ── Ortak Ödeme Sayfası Linki Oluştur (SMS göndermeden) ────────
    // Docs: sx|clientRefCode|amount|successUrl|failUrl|rnd|customerKey|secretKey
    public function ortakLinkOlustur(array $params): array
    {
        $rnd  = date('YmdHis');
        $hash = $this->hashHazirla([
            $this->sx,
            $params['ref_kod']      ?? '',
            number_format((float)($params['tutar'] ?? 0), 2, '.', ''),
            $params['success_url']  ?? '',
            $params['fail_url']     ?? '',
            $rnd,
            '',
            $this->secretKey,
        ]);

        $post = [
            'sx'              => $this->sx,
            'clientRefCode'   => $params['ref_kod']     ?? '',
            'amount'          => number_format((float)($params['tutar'] ?? 0), 2, '.', ''),
            'successUrl'      => $params['success_url'] ?? '',
            'failUrl'         => $params['fail_url']    ?? '',
            'rnd'             => $rnd,
            'use3D'           => 'true',
            'currencyCode'    => '949',
            'transactionType' => 'SALES',
            'hashDatav2'      => $hash,
            'instalments'     => $params['max_taksit']  ?? '1',
            'detail'          => 'true',
            'inputNamesurname'=> $params['ad_soyad']    ?? '',
            'inputEmail'      => $params['email']       ?? '',
            'inputDescription'=> $params['aciklama']    ?? '',
        ];

        $url = $this->testModu ? self::URL_ORTAK_LINK : self::URL_ORTAK_LINK_CANLI;
        return $this->istek($url, $post, true);
    }

    // ── Link Sil ───────────────────────────────────────────────────
    public function linkSil(string $q): array
    {
        $hash = $this->hashHazirla([$this->sx, $q, $this->secretKey]);
        $post = ['sx' => $this->sx, 'q' => $q, 'hashDatav2' => $hash];
        $url  = $this->testModu ? self::URL_LINK_SIL : self::URL_LINK_SIL_CANLI;
        return $this->istek($url, $post);
    }

    // ── HTTP POST ──────────────────────────────────────────────────
    private function istek(string $url, array $post, bool $jsonBody = false): array
    {
        $ch = curl_init($url);
        if ($jsonBody) {
            $body = json_encode($post, JSON_UNESCAPED_UNICODE);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($post),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
        }
        $rawBody = curl_exec($ch);
        $errno   = curl_errno($ch);
        $err     = curl_error($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return ['basari' => false, 'hata' => $err, 'raw' => '', 'hata_kodu' => 'CURL', 'hata_mesaj' => $err];
        }

        $json = json_decode($rawBody, true);

        // Paynkolay: RESPONSE_CODE=1 başarı, ResponseCode='00' eski format
        $basari = $code === 200 && isset($json) && (
            ($json['RESPONSE_CODE'] ?? null) === 1 ||
            ($json['ResponseCode']  ?? $json['responseCode'] ?? '') === '00'
        );

        return [
            'basari'     => $basari,
            'http_kodu'  => $code,
            'veri'       => $json ?? [],
            'raw'        => $rawBody,
            'hata_kodu'  => $json['ERROR_CODE']           ?? $json['ResponseCode']        ?? '',
            'hata_mesaj' => $json['RESPONSE_DATA']        ?? $json['ResponseDescription'] ?? $json['ERROR_MESSAGE'] ?? '',
        ];
    }
}
