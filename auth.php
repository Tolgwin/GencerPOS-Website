<?php
/**
 * auth.php — Her sayfanın başına require edilir.
 * Oturum kontrolü + yetki doğrulama.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// Giriş yapılmamışsa login sayfasına yönlendir
if (empty($_SESSION['kullanici_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
    $redirect = urlencode(preg_replace('/\.php($|\?)/', '$1', urldecode($redirect)));
    header("Location: login.php" . ($redirect ? "?r=$redirect" : ""));
    exit;
}

// Her istekte rol izinlerini DB'den tazele (rol değişikliği anında yansısın)
if (empty($_SESSION['_izin_ts']) || (time() - $_SESSION['_izin_ts']) > 60) {
    require_once __DIR__ . '/db.php';
    $stmt = $pdo->prepare("SELECT r.izinler, r.ad FROM roller r JOIN kullanicilar k ON k.rol_id = r.id WHERE k.id = ? AND k.aktif = 1");
    $stmt->execute([$_SESSION['kullanici_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $_SESSION['izinler']  = json_decode($row['izinler'] ?? '{}', true);
        $_SESSION['rol_adi']  = $row['ad'];
    }
    $_SESSION['_izin_ts'] = time();
}

// ── Yardımcı fonksiyonlar ──────────────────────────────
function aktifKullanici(): array {
    return $_SESSION['kullanici'] ?? [];
}

function izinVar(string $izin): bool {
    $izinler = $_SESSION['izinler'] ?? [];
    return !empty($izinler[$izin]);
}

function rolAdi(): string {
    return $_SESSION['rol_adi'] ?? 'standart';
}

function adminMi(): bool {
    return rolAdi() === 'admin';
}

/**
 * Sayfa başında çağır: izinVar('kasa') gibi.
 * Yetkisiz ise hata sayfasına yönlendir.
 */
function yetkiKontrol(string $izin): void {
    if (!izinVar($izin)) {
        http_response_code(403);
        $sayfa = basename($_SERVER['PHP_SELF']);
        $lbl   = htmlspecialchars($izin);
        die("<!DOCTYPE html><html lang='tr'><head><meta charset='UTF-8'>
            <title>Yetkisiz Erişim</title>
            <style>
                body{font-family:system-ui,sans-serif;background:#f0f4f8;display:flex;
                     align-items:center;justify-content:center;min-height:100vh;margin:0}
                .box{background:#fff;border-radius:16px;padding:48px 40px;text-align:center;
                     box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:400px}
                .icon{font-size:48px;margin-bottom:16px}
                h2{color:#1e293b;margin-bottom:8px}
                p{color:#64748b;font-size:14px;margin-bottom:24px}
                a{display:inline-block;padding:10px 24px;background:#1f6feb;color:#fff;
                  border-radius:8px;text-decoration:none;font-weight:600;font-size:14px}
                a:hover{background:#388bfd}
            </style></head><body>
            <div class='box'>
              <div class='icon'>🔒</div>
              <h2>Yetkisiz Erişim</h2>
              <p>Bu sayfaya erişim için <strong>$lbl</strong> yetkisi gereklidir.</p>
              <a href='fatura_liste.php'>← Ana Sayfaya Dön</a>
            </div></body></html>");
    }
}
