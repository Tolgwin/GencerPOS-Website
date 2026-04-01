<?php
/**
 * Paynkolay Ödeme Callback
 * Paynkolay başarılı/başarısız ödeme sonucunu buraya POST eder.
 * Ayrıca successUrl / failUrl yönlendirmeleri de buraya gelir.
 */
require_once __DIR__ . '/db.php';

$configFile = __DIR__ . '/config.php';
$config     = file_exists($configFile) ? require $configFile : [];

// GET veya POST'tan referans kodu al
$ref    = $_GET['ref']    ?? $_POST['CLIENT_REFERENCE_CODE'] ?? $_POST['clientRefCode'] ?? '';
$sonuc  = $_GET['sonuc']  ?? ''; // 'basarili' | 'basarisiz' (GET yönlendirmesinden)

// Paynkolay'ın POST ettiği tüm veriyi logla
$postVeri = $_POST;
$logDosya = __DIR__ . '/paynkolay_log.txt';
$logSatir = date('Y-m-d H:i:s') . ' | GET: ' . json_encode($_GET) . ' | POST: ' . json_encode($postVeri) . "\n";
file_put_contents($logDosya, $logSatir, FILE_APPEND | LOCK_EX);

// DB: tablo kontrolü
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS paynkolay_linkler (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tip ENUM('fatura','cari') NOT NULL,
            referans_id INT NOT NULL,
            client_ref_code VARCHAR(100),
            link_url TEXT,
            tutar DECIMAL(15,2),
            durum ENUM('beklemede','odendi','iptal') DEFAULT 'beklemede',
            paynkolay_ref VARCHAR(100),
            olusturma DATETIME DEFAULT CURRENT_TIMESTAMP,
            odeme_tarihi DATETIME NULL,
            INDEX idx_ref (tip, referans_id),
            INDEX idx_client (client_ref_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {}

// Paynkolay ödeme durumunu tespit et
// POST'tan gelen başarı alanları farklı olabilir
$odemeBasarili = false;
if (
    isset($postVeri['status'])       && strtolower($postVeri['status'])       === 'success' ||
    isset($postVeri['Status'])       && strtolower($postVeri['Status'])       === 'success' ||
    isset($postVeri['isSuccess'])    && $postVeri['isSuccess']                 === 'true'   ||
    isset($postVeri['IsSuccess'])    && $postVeri['IsSuccess']                 === 'true'   ||
    isset($postVeri['responseCode']) && $postVeri['responseCode']              === '00'      ||
    $sonuc === 'basarili'
) {
    $odemeBasarili = true;
}

if (!$ref) {
    // Sadece yönlendirme geldi, referans yok
    echo '<script>window.close(); window.opener && window.opener.location.reload();</script>';
    echo '<p>İşlem tamamlandı. Bu pencereyi kapatabilirsiniz.</p>';
    exit;
}

// Linki bul
$stmt = $pdo->prepare("SELECT * FROM paynkolay_linkler WHERE client_ref_code = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$ref]);
$link = $stmt->fetch();

if (!$link) {
    echo '<p>Referans bulunamadı: ' . htmlspecialchars($ref) . '</p>';
    exit;
}

// Paynkolay referans kodu (IKSIR...)
$paynRef = $postVeri['referenceCode'] ?? $postVeri['ReferenceCode'] ?? $postVeri['paymentReferenceCode'] ?? '';

// Zaten işlendiyse tekrar işleme
if ($link['durum'] === 'odendi') {
    echo '<p>Bu ödeme zaten işlenmiş.</p>';
    exit;
}

if ($odemeBasarili) {
    $pdo->prepare("UPDATE paynkolay_linkler SET durum='odendi', paynkolay_ref=?, odeme_tarihi=NOW() WHERE id=?")
        ->execute([$paynRef, $link['id']]);

    // Tahsilat kaydı oluştur
    $tutar = (float)$link['tutar'];
    $tip   = $link['tip'];
    $refId = (int)$link['referans_id'];

    if ($tip === 'fatura') {
        // Müşteri ID'sini çek
        $fRow = $pdo->prepare("SELECT musteri_id, toplam_tutar, kalan FROM faturalar WHERE id=?");
        $fRow->execute([$refId]);
        $fatura = $fRow->fetch();

        if ($fatura) {
            // Tahsilat ekle
            $pdo->prepare("
                INSERT INTO tahsilatlar (fatura_id, musteri_id, tutar, odeme_tipi, tarih, aciklama)
                VALUES (?, ?, ?, 'kredi_karti', NOW(), ?)
            ")->execute([
                $refId,
                $fatura['musteri_id'],
                $tutar,
                'Paynkolay Online Ödeme — Ref: ' . $paynRef
            ]);

            // Kalanı güncelle
            $yeniKalan = max(0, (float)$fatura['kalan'] - $tutar);
            $yeniDurum = $yeniKalan <= 0 ? 'odendi' : 'kismi_odendi';
            $pdo->prepare("UPDATE faturalar SET kalan=?, odeme_durumu=? WHERE id=?")
                ->execute([$yeniKalan, $yeniDurum, $refId]);
        }

    } elseif ($tip === 'cari') {
        // Cari tahsilat: musteriler tablosuna kayıt
        $pdo->prepare("
            INSERT INTO tahsilatlar (musteri_id, tutar, odeme_tipi, tarih, aciklama)
            VALUES (?, ?, 'kredi_karti', NOW(), ?)
        ")->execute([
            $refId,
            $tutar,
            'Paynkolay Online Ödeme — Ref: ' . $paynRef
        ]);
    }

    // Müşteri yönlendirmesine uygun sayfa
    $baseUrl = rtrim($config['payn_base_url'] ?? 'http://localhost/FaturaApp', '/');
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ödeme Başarılı</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f0fdf4; }
        .kutu { background: #fff; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.1); max-width: 440px; width: 90%; }
        .ikon { font-size: 60px; margin-bottom: 16px; }
        h2 { color: #166534; margin: 0 0 12px; }
        p  { color: #6b7280; margin: 0 0 24px; }
        a  { display: inline-block; padding: 12px 28px; background: #16a34a; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; }
    </style>
    </head>
    <body>
    <div class="kutu">
        <div class="ikon">✅</div>
        <h2>Ödemeniz Alındı!</h2>
        <p>Tutarı: <strong><?= number_format($tutar, 2, ',', '.') ?> ₺</strong><br>
           Referans: <code><?= htmlspecialchars($paynRef ?: $ref) ?></code></p>
        <a href="<?= $baseUrl ?>/fatura_liste.php">Fatura Listesine Dön</a>
    </div>
    <script>if(window.opener) { window.opener.location.reload(); setTimeout(()=>window.close(),3000); }</script>
    </body></html>
    <?php

} else {
    $pdo->prepare("UPDATE paynkolay_linkler SET durum='iptal' WHERE id=?")->execute([$link['id']]);
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ödeme Başarısız</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #fef2f2; }
        .kutu { background: #fff; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.1); max-width: 440px; width: 90%; }
        .ikon { font-size: 60px; margin-bottom: 16px; }
        h2 { color: #991b1b; margin: 0 0 12px; }
        p  { color: #6b7280; margin: 0 0 24px; }
        a  { display: inline-block; padding: 12px 28px; background: #dc2626; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; }
    </style>
    </head>
    <body>
    <div class="kutu">
        <div class="ikon">❌</div>
        <h2>Ödeme Başarısız</h2>
        <p>İşlem tamamlanamadı. Lütfen tekrar deneyin.</p>
        <a href="javascript:history.back()">Geri Dön</a>
    </div>
    </body></html>
    <?php
}
