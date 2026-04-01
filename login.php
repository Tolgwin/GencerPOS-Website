<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['kullanici_id'])) {
    header("Location: fatura_liste.php");
    exit;
}
$hata = $_SESSION['login_hata'] ?? '';
unset($_SESSION['login_hata']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giriş — FaturaApp</title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --accent:   #1f6feb;
        --accent2:  #388bfd;
        --danger:   #ef4444;
        --radius:   12px;
        --shadow:   0 8px 32px rgba(0,0,0,.10);
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
        display: flex;
        background: #f0f4f8;
    }

    /* Sol panel */
    .left-panel {
        flex: 1;
        background: linear-gradient(135deg, #0d1117 0%, #0d419d 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        position: relative;
        overflow: hidden;
    }

    .left-panel::before {
        content: '';
        position: absolute;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(56,139,253,.15), transparent 70%);
        top: -100px; right: -100px;
        border-radius: 50%;
    }

    .left-panel::after {
        content: '';
        position: absolute;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(56,139,253,.10), transparent 70%);
        bottom: -50px; left: -50px;
        border-radius: 50%;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 48px;
        position: relative;
        z-index: 1;
    }

    .brand-icon {
        width: 60px; height: 60px;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 28px;
        box-shadow: 0 8px 24px rgba(31,111,235,.4);
    }

    .brand-name { font-size: 32px; font-weight: 800; color: #fff; }
    .brand-sub  { font-size: 14px; color: #8b949e; margin-top: 2px; }

    .feature-list {
        list-style: none;
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 320px;
    }

    .feature-list li {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 0;
        border-bottom: 1px solid rgba(255,255,255,.06);
        color: #8b949e;
        font-size: 14px;
    }

    .feature-list li:last-child { border: none; }

    .feature-icon {
        width: 36px; height: 36px;
        background: rgba(31,111,235,.15);
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .feature-list li strong { color: #c9d1d9; display: block; font-size: 13.5px; }

    /* Sağ panel — form */
    .right-panel {
        width: 460px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 32px;
        background: #fff;
    }

    .login-box { width: 100%; max-width: 380px; }

    .login-title {
        font-size: 26px;
        font-weight: 800;
        color: #0d1117;
        margin-bottom: 6px;
    }

    .login-sub {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 36px;
    }

    .form-group { margin-bottom: 18px; }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }

    .input-wrap {
        position: relative;
    }

    .input-wrap svg {
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        width: 17px; height: 17px;
        stroke: #9ca3af;
        pointer-events: none;
    }

    .form-input {
        width: 100%;
        padding: 11px 14px 11px 42px;
        border: 1.5px solid #e5e7eb;
        border-radius: var(--radius);
        font-size: 14px;
        color: #1c2333;
        background: #f9fafb;
        transition: border-color .15s, background .15s;
        outline: none;
    }

    .form-input:focus {
        border-color: var(--accent);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(31,111,235,.10);
    }

    .toggle-pw {
        position: absolute;
        right: 12px; top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        color: #9ca3af;
    }

    .toggle-pw:hover { color: #374151; }
    .toggle-pw svg { width: 16px; height: 16px; display: block; }

    .hata-mesaj {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 9px;
        padding: 10px 14px;
        font-size: 13px;
        color: #dc2626;
        margin-bottom: 18px;
    }

    .btn-giris {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        color: #fff;
        border: none;
        border-radius: var(--radius);
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .15s, transform .1s;
        margin-top: 8px;
        letter-spacing: .2px;
    }

    .btn-giris:hover   { opacity: .92; }
    .btn-giris:active  { transform: scale(.98); }
    .btn-giris:disabled{ opacity: .6; cursor: not-allowed; }

    .login-footer {
        margin-top: 28px;
        text-align: center;
        font-size: 12px;
        color: #9ca3af;
    }

    @media (max-width: 860px) {
        .left-panel { display: none; }
        .right-panel { width: 100%; background: #f0f4f8; }
        .login-box {
            background: #fff;
            border-radius: 16px;
            padding: 36px 28px;
            box-shadow: var(--shadow);
        }
    }
</style>
</head>
<body>

<!-- Sol tanıtım paneli -->
<div class="left-panel">
    <div class="brand">
        <div class="brand-icon">🧾</div>
        <div>
            <div class="brand-name">FaturaApp</div>
            <div class="brand-sub">İşletme Yönetim Sistemi</div>
        </div>
    </div>
    <ul class="feature-list">
        <li>
            <div class="feature-icon">📄</div>
            <div><strong>Fatura & Tahsilat</strong>Hızlı fatura oluştur, takibini yap</div>
        </li>
        <li>
            <div class="feature-icon">👥</div>
            <div><strong>Müşteri Yönetimi</strong>Cari hesap & etiket sistemi</div>
        </li>
        <li>
            <div class="feature-icon">💰</div>
            <div><strong>Kasa & Hesaplar</strong>Çoklu kasa, transfer ve cari ödeme</div>
        </li>
        <li>
            <div class="feature-icon">🔧</div>
            <div><strong>Servis Takibi</strong>Tutanak ve servis yönetimi</div>
        </li>
    </ul>
</div>

<!-- Giriş formu -->
<div class="right-panel">
    <div class="login-box">
        <div class="login-title">Hoş Geldiniz</div>
        <div class="login-sub">Devam etmek için giriş yapın</div>

        <?php if ($hata): ?>
            <div class="hata-mesaj">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($hata) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login_kontrol.php" id="loginForm">
            <?php if (!empty($_GET['r'])): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['r']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="kullanici_adi">Kullanıcı Adı</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text" id="kullanici_adi" name="kullanici_adi"
                           class="form-input" placeholder="kullanici_adi"
                           value="<?= htmlspecialchars($_POST['kullanici_adi'] ?? '') ?>"
                           autocomplete="username" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="sifre">Şifre</label>
                <div class="input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password" id="sifre" name="sifre"
                           class="form-input" placeholder="••••••••"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw()" title="Şifreyi Göster">
                        <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-giris" id="btnGiris">Giriş Yap</button>
        </form>

        <div class="login-footer">
            FaturaApp v1.0 &copy; <?= date('Y') ?>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const inp  = document.getElementById('sifre');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        inp.type = 'password';
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    }
}

document.getElementById('loginForm').addEventListener('submit', function () {
    document.getElementById('btnGiris').disabled = true;
    document.getElementById('btnGiris').textContent = 'Giriş yapılıyor...';
});
</script>
</body>
</html>
