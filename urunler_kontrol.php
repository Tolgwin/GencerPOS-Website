<?php
// ════════════════════════════════════════════════════════
// HATA YAKALAMA — JSON response bozmadan yakala
// ════════════════════════════════════════════════════════
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ob_start(); // PHP hatalarını tampon al — JSON'a karışmasın

require 'db.php';

header('Content-Type: application/json; charset=utf-8');

function cevap(bool $basari, string $mesaj, array $data = []): void
{
    ob_end_clean(); // Tampondaki PHP hatalarını temizle
    echo json_encode(
        array_merge(['basari' => $basari, 'mesaj' => $mesaj], $data),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// Beklenmedik hataları da yakala
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    cevap(false, "PHP Hatası [$errno]: $errstr — $errfile:$errline");
});

set_exception_handler(function (Throwable $e) {
    cevap(false, 'İstisna: ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
});

// ════════════════════════════════════════════════════════
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'urun_ara':
            $ara = '%' . trim($_GET['q'] ?? '') . '%';

            $stmt = $pdo->prepare("
                SELECT
                    u.id,
                    u.urun_kodu,
                    u.ad            AS urun_adi,
                    u.birim_fiyat,
                    u.kdv_orani,
                    u.birim,
                    u.stok_miktari,
                    k.ad            AS kategori_adi
                FROM urunler u
                LEFT JOIN kategoriler k ON u.kategori_id = k.id
                WHERE u.durum = 'aktif'
                  AND (
                      u.ad        LIKE ?
                   OR u.urun_kodu LIKE ?
                  )
                ORDER BY u.ad
                LIMIT 20
            ");
            $stmt->execute([$ara, $ara]);
            cevap(true, '', ['urunler' => $stmt->fetchAll()]);

        case 'urun_getir':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id)
                cevap(false, 'Geçersiz ID.');

            $stmt = $pdo->prepare("
                SELECT
                    u.id,
                    u.urun_kodu,
                    u.ad            AS urun_adi,
                    u.birim_fiyat,
                    u.kdv_orani,
                    u.birim,
                    u.stok_miktari,
                    k.ad            AS kategori_adi
                FROM urunler u
                LEFT JOIN kategoriler k ON u.kategori_id = k.id
                WHERE u.id = ?
                  AND u.durum = 'aktif'
            ");
            $stmt->execute([$id]);
            $urun = $stmt->fetch();

            if (!$urun)
                cevap(false, 'Ürün bulunamadı.');
            cevap(true, '', ['urun' => $urun]);

        default:
            cevap(false, 'Geçersiz işlem: ' . htmlspecialchars($action));
    }

} catch (Throwable $e) {
    cevap(false, 'Sunucu hatası: ' . $e->getMessage());
}
