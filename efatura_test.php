<?php
// efatura_test.php
require_once 'QNBEFaturaClient.php';

echo '<pre>';

try {
    $client = new QNBEFaturaClient();
    echo "✅ SOAP bağlantısı kuruldu\n\n";

    // ── Login ──────────────────────────────────
    echo "── wsLogin testi ──\n";
    $loginSonuc = $client->login(
        userId: 'TEST_KULLANICI',   // ← QNB'den aldığın kullanıcı adı
        password: 'TEST_SIFRE',       // ← QNB'den aldığın şifre
        lang: 'tr'
    );

    if ($loginSonuc['basari']) {
        echo "✅ Login başarılı!\n";
        echo "🔑 Session ID : " . ($loginSonuc['session_id'] ?? 'cookie yok') . "\n";
        echo "📋 Response   : ";
        print_r($loginSonuc['response']);
    } else {
        echo "❌ Login hatası: " . $loginSonuc['hata'] . "\n";
        echo "📋 Kod: " . $loginSonuc['kod'] . "\n";
    }

    // ── Debug: Ham SOAP mesajları ──────────────
    echo "\n── SOAP Debug ──\n";
    $debug = $client->debug();
    echo "📤 Gönderilen XML:\n";
    echo htmlspecialchars($debug['son_istek_body']) . "\n\n";
    echo "📥 Gelen XML:\n";
    echo htmlspecialchars($debug['son_yanit_body']) . "\n\n";
    echo "📥 Response Headers:\n";
    echo $debug['son_yanit_header'] . "\n";

    // ── Logout ─────────────────────────────────
    if ($client->isLoggedIn()) {
        $logoutSonuc = $client->logout();
        echo "\n── Logout ──\n";
        echo $logoutSonuc['basari'] ? "✅ Logout başarılı\n" : "❌ " . $logoutSonuc['hata'] . "\n";
    }

} catch (Throwable $e) {
    echo "❌ Kritik Hata: " . $e->getMessage() . "\n";
    echo "📍 Dosya: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo '</pre>';
