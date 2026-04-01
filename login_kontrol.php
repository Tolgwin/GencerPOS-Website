<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';

$kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
$sifre         = $_POST['sifre'] ?? '';
$redirect      = $_POST['redirect'] ?? '';

function loginHata(string $msg, string $ku): void {
    $_SESSION['login_hata'] = $msg;
    header("Location: login.php");
    exit;
}

if (!$kullanici_adi || !$sifre) {
    loginHata('Kullanıcı adı ve şifre gereklidir.', '');
}

// Kaba kuvvet koruması — aynı IP'den 5 başarısız giriş → 10 dakika bekleme
$ip = $_SERVER['REMOTE_ADDR'];
$key = 'login_fail_' . md5($ip);
$_SESSION[$key] = $_SESSION[$key] ?? ['count' => 0, 'time' => 0];
if ($_SESSION[$key]['count'] >= 5 && (time() - $_SESSION[$key]['time']) < 600) {
    $kalan = 600 - (time() - $_SESSION[$key]['time']);
    loginHata("Çok fazla başarısız giriş. $kalan saniye bekleyin.", '');
}

$stmt = $pdo->prepare("
    SELECT k.*, r.ad AS rol_adi, r.izinler
    FROM kullanicilar k
    JOIN roller r ON r.id = k.rol_id
    WHERE k.kullanici_adi = ? AND k.aktif = 1
    LIMIT 1
");
$stmt->execute([$kullanici_adi]);
$kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kullanici || !password_verify($sifre, $kullanici['sifre_hash'])) {
    $_SESSION[$key]['count']++;
    $_SESSION[$key]['time'] = time();
    loginHata('Kullanıcı adı veya şifre hatalı.', $kullanici_adi);
}

// Başarılı giriş
$_SESSION[$key] = ['count' => 0, 'time' => 0];

session_regenerate_id(true);

$izinler = json_decode($kullanici['izinler'] ?? '{}', true);

$_SESSION['kullanici_id']  = $kullanici['id'];
$_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
$_SESSION['ad_soyad']      = $kullanici['ad_soyad'];
$_SESSION['rol_id']        = $kullanici['rol_id'];
$_SESSION['rol_adi']       = $kullanici['rol_adi'];
$_SESSION['izinler']       = $izinler;
$_SESSION['kullanici']     = [
    'id'       => $kullanici['id'],
    'ad_soyad' => $kullanici['ad_soyad'],
    'adi'      => $kullanici['kullanici_adi'],
    'rol'      => $kullanici['rol_adi'],
];

// Son giriş güncelle
$pdo->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?")
    ->execute([$kullanici['id']]);

// Yönlendir
if ($redirect) {
    $safe = ltrim(urldecode($redirect), '/');
    if (!str_starts_with($safe, 'http') && preg_match('/^[a-zA-Z0-9_\-\.\/\?=&]+$/', $safe)) {
        header("Location: $safe");
        exit;
    }
}
header("Location: fatura_liste.php");
exit;
