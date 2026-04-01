<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('kullanici');
header('Content-Type: application/json; charset=utf-8');

$action = trim($_POST['action'] ?? '');

// ── Liste ───────────────────────────────────────────────
if ($action === 'liste') {
    $stmt = $pdo->query("
        SELECT k.id, k.ad_soyad, k.kullanici_adi, k.email,
               k.aktif, k.son_giris, k.olusturma,
               r.ad AS rol_adi, r.id AS rol_id
        FROM kullanicilar k
        JOIN roller r ON r.id = k.rol_id
        ORDER BY k.olusturma ASC
    ");
    echo json_encode(['basari' => true, 'kullanicilar' => $stmt->fetchAll()]);
    exit;
}

// ── Roller ──────────────────────────────────────────────
if ($action === 'roller') {
    $stmt = $pdo->query("SELECT id, ad, aciklama FROM roller ORDER BY id");
    echo json_encode(['basari' => true, 'roller' => $stmt->fetchAll()]);
    exit;
}

// ── Ekle ────────────────────────────────────────────────
if ($action === 'ekle') {
    $ad_soyad      = mb_strtoupper(trim($_POST['ad_soyad'] ?? ''), 'UTF-8');
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $sifre         = $_POST['sifre'] ?? '';
    $rol_id        = (int)($_POST['rol_id'] ?? 4);

    if (!$ad_soyad || !$kullanici_adi || !$sifre) {
        echo json_encode(['basari' => false, 'mesaj' => 'Ad, kullanıcı adı ve şifre zorunludur.']);
        exit;
    }
    if (strlen($sifre) < 6) {
        echo json_encode(['basari' => false, 'mesaj' => 'Şifre en az 6 karakter olmalıdır.']);
        exit;
    }

    $kontrol = $pdo->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ?");
    $kontrol->execute([$kullanici_adi]);
    if ($kontrol->fetch()) {
        echo json_encode(['basari' => false, 'mesaj' => 'Bu kullanıcı adı zaten kullanımda.']);
        exit;
    }

    $pdo->prepare("
        INSERT INTO kullanicilar (ad_soyad, kullanici_adi, email, sifre_hash, rol_id)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$ad_soyad, $kullanici_adi, $email, password_hash($sifre, PASSWORD_DEFAULT), $rol_id]);

    echo json_encode(['basari' => true, 'mesaj' => 'Kullanıcı oluşturuldu.']);
    exit;
}

// ── Güncelle ────────────────────────────────────────────
if ($action === 'guncelle') {
    $id            = (int)($_POST['id'] ?? 0);
    $ad_soyad      = mb_strtoupper(trim($_POST['ad_soyad'] ?? ''), 'UTF-8');
    $email         = trim($_POST['email'] ?? '');
    $rol_id        = (int)($_POST['rol_id'] ?? 4);
    $aktif         = (int)($_POST['aktif'] ?? 1);
    $sifre         = $_POST['sifre'] ?? '';

    if (!$id || !$ad_soyad) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz istek.']);
        exit;
    }

    // Kendi admin hesabını pasif yapmasını engelle
    if ($id === (int)$_SESSION['kullanici_id'] && !$aktif) {
        echo json_encode(['basari' => false, 'mesaj' => 'Kendi hesabınızı pasif yapamazsınız.']);
        exit;
    }

    if ($sifre) {
        if (strlen($sifre) < 6) {
            echo json_encode(['basari' => false, 'mesaj' => 'Şifre en az 6 karakter olmalıdır.']);
            exit;
        }
        $pdo->prepare("
            UPDATE kullanicilar SET ad_soyad=?, email=?, rol_id=?, aktif=?, sifre_hash=? WHERE id=?
        ")->execute([$ad_soyad, $email, $rol_id, $aktif, password_hash($sifre, PASSWORD_DEFAULT), $id]);
    } else {
        $pdo->prepare("
            UPDATE kullanicilar SET ad_soyad=?, email=?, rol_id=?, aktif=? WHERE id=?
        ")->execute([$ad_soyad, $email, $rol_id, $aktif, $id]);
    }

    echo json_encode(['basari' => true, 'mesaj' => 'Kullanıcı güncellendi.']);
    exit;
}

// ── Sil ─────────────────────────────────────────────────
if ($action === 'sil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['kullanici_id']) {
        echo json_encode(['basari' => false, 'mesaj' => 'Kendi hesabınızı silemezsiniz.']);
        exit;
    }
    $pdo->prepare("DELETE FROM kullanicilar WHERE id = ?")->execute([$id]);
    echo json_encode(['basari' => true, 'mesaj' => 'Kullanıcı silindi.']);
    exit;
}

// ── Rol izinleri güncelle ───────────────────────────────
if ($action === 'rol_guncelle') {
    if (!adminMi()) {
        echo json_encode(['basari' => false, 'mesaj' => 'Sadece admin rol düzenleyebilir.']);
        exit;
    }
    $rol_id  = (int)($_POST['rol_id'] ?? 0);
    $izinler = $_POST['izinler'] ?? '{}';
    if (!$rol_id || $rol_id === 1) { // admin rolü değiştirilemez
        echo json_encode(['basari' => false, 'mesaj' => 'Admin rolü değiştirilemez.']);
        exit;
    }
    $decoded = json_decode($izinler, true);
    if (!is_array($decoded)) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz izin verisi.']);
        exit;
    }
    $pdo->prepare("UPDATE roller SET izinler = ? WHERE id = ?")
        ->execute([json_encode($decoded), $rol_id]);

    // Aktif kullanıcının rolü değiştiyse session'ı güncelle
    if ((int)$_SESSION['rol_id'] === $rol_id) {
        $_SESSION['izinler'] = $decoded;
    }

    echo json_encode(['basari' => true, 'mesaj' => 'Rol güncellendi.']);
    exit;
}

echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz işlem.']);
