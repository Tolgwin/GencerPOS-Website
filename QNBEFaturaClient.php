<?php
// ─────────────────────────────────────────────
// QNBEFaturaClient.php  —  Gerçek WSDL'e göre
// ─────────────────────────────────────────────

class QNBEFaturaClient
{
    // ── Sabitler ──────────────────────────────
    private const BASE_URL = 'https://erpefaturatest1.qnbesolutions.com.tr:443/efatura/ws';
    private const WSDL_USER = self::BASE_URL . '/userService?wsdl';
    private const NS = 'http://service.csap.cs.com.tr/';

    private SoapClient $userClient;
    private string $certPath;
    private bool $loggedIn = false;

    // ── Kurucu ────────────────────────────────
    public function __construct()
    {
        // Dinamik sertifika yolu — php.ini'ye göre otomatik
        $this->certPath = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'cacert.pem';

        $this->userClient = $this->createClient(self::WSDL_USER);
    }

    // ════════════════════════════════════════════
    // SOAP CLIENT FACTORY
    // ════════════════════════════════════════════
    private function createClient(string $wsdl): SoapClient
    {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'cafile' => $this->certPath,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
            ],
            'http' => ['timeout' => 30],
        ]);

        return new SoapClient($wsdl, [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => $ctx,
            'connection_timeout' => 30,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'encoding' => 'UTF-8',
        ]);
    }

    // ════════════════════════════════════════════
    // LOGIN
    // ════════════════════════════════════════════
    public function login(string $userId, string $password, string $lang = 'tr'): array
    {
        try {
            $response = $this->userClient->wsLogin([
                'userId' => $userId,
                'password' => $password,
                'lang' => $lang,
            ]);

            $this->loggedIn = true;

            // Session cookie'yi yakala (JAX-WS session tabanlı)
            $lastHeaders = $this->userClient->__getLastResponseHeaders();
            $sessionId = $this->parseSessionCookie($lastHeaders);

            // Cookie'yi sonraki isteklere ekle
            if ($sessionId) {
                $this->userClient->__setCookie('JSESSIONID', $sessionId);
            }

            return [
                'basari' => true,
                'session_id' => $sessionId,
                'response' => $response,
                'headers' => $lastHeaders,
            ];

        } catch (SoapFault $e) {
            return [
                'basari' => false,
                'hata' => $e->getMessage(),
                'kod' => $e->faultcode ?? 'unknown',
            ];
        }
    }

    // ════════════════════════════════════════════
    // LOGOUT
    // ════════════════════════════════════════════
    public function logout(): array
    {
        if (!$this->loggedIn) {
            return ['basari' => false, 'hata' => 'Aktif oturum yok'];
        }

        try {
            $response = $this->userClient->logout([]);
            $this->loggedIn = false;

            return ['basari' => true, 'response' => $response];

        } catch (SoapFault $e) {
            return ['basari' => false, 'hata' => $e->getMessage()];
        }
    }

    // ════════════════════════════════════════════
    // YARDIMCI: Session Cookie Parse
    // ════════════════════════════════════════════
    private function parseSessionCookie(string $headers): ?string
    {
        // Set-Cookie: JSESSIONID=abc123; Path=/efatura
        if (preg_match('/Set-Cookie:\s*JSESSIONID=([^;]+)/i', $headers, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // ════════════════════════════════════════════
    // DEBUG: Son İstek / Yanıt
    // ════════════════════════════════════════════
    public function debug(): array
    {
        return [
            'son_istek_header' => $this->userClient->__getLastRequestHeaders(),
            'son_istek_body' => $this->userClient->__getLastRequest(),
            'son_yanit_header' => $this->userClient->__getLastResponseHeaders(),
            'son_yanit_body' => $this->userClient->__getLastResponse(),
        ];
    }

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }
}
