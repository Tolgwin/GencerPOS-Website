<?php
require 'db.php';
$id = (int) $_GET['id'];

// ✅ vergi_no ve vergi_dairesi sorguna eklendi
$fatura = $pdo->prepare("
    SELECT f.*,
           m.ad_soyad, m.email, m.telefon, m.adres,
           m.vergi_no, m.vergi_dairesi
    FROM faturalar f
    JOIN musteriler m ON f.musteri_id = m.id
    WHERE f.id = ?
");
$fatura->execute([$id]);
$f = $fatura->fetch();

// ✅ Null güvenliği — hangi alan eksik olursa boş string döner
$f = array_merge([
    'fatura_no' => '',
    'tarih' => date('Y-m-d'),
    'vade_tarihi' => date('Y-m-d'),
    'durum' => '',
    'notlar' => '',
    'ad_soyad' => '',
    'email' => '',
    'telefon' => '',
    'adres' => '',
    'vergi_no' => '',
    'vergi_dairesi' => '',
], $f ?: []);

$kalemler = $pdo->prepare("
    SELECT * FROM fatura_kalemleri WHERE fatura_id = ?
");
$kalemler->execute([$id]);
$kalemler = $kalemler->fetchAll();

$araToplam = 0;
$kdvToplam = 0;
foreach ($kalemler as $k) {
    $ara = $k['miktar'] * $k['birim_fiyat'];
    $araToplam += $ara;
    $kdvToplam += $ara * $k['kdv_orani'] / 100;
}
$genelToplam = $araToplam + $kdvToplam;
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Fatura <?= htmlspecialchars($f['fatura_no']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container fatura-kagit">

        <!-- Başlık -->
        <div class="fatura-header">
            <div>
                <h1>🏢 Şirket Adınız</h1>
                <p>Adres: İstanbul, Türkiye</p>
                <p>Tel: 0212 000 00 00</p>
                <p>E-posta: info@sirket.com</p>
            </div>
            <div class="fatura-bilgi">
                <h2>FATURA</h2>
                <p><strong>No:</strong>
                    <?= htmlspecialchars($f['fatura_no']) ?>
                </p>
                <p><strong>Tarih:</strong>
                    <?= date('d.m.Y', strtotime($f['tarih'])) ?>
                </p>
                <p><strong>Vade:</strong>
                    <?= date('d.m.Y', strtotime($f['vade_tarihi'])) ?>
                </p>
                <p>
                    <span class="badge badge-<?= htmlspecialchars($f['durum']) ?>">
                        <?= strtoupper(htmlspecialchars($f['durum'])) ?>
                    </span>
                </p>
            </div>
        </div>

        <hr>

        <!-- Müşteri -->
        <div class="musteri-bilgi">
            <h3>Fatura Kesilen:</h3>
            <p><strong><?= htmlspecialchars($f['ad_soyad']) ?></strong></p>
            <p>Adres: <?= htmlspecialchars($f['adres']) ?></p>
            <p>Tel: <?= htmlspecialchars($f['telefon']) ?></p>
            <p>Email: <?= htmlspecialchars($f['email']) ?></p>

            <?php if ($f['vergi_no']): ?>
                <p>Vergi No: <?= htmlspecialchars($f['vergi_no']) ?></p>
            <?php endif; ?>

            <?php if ($f['vergi_dairesi']): ?>
                <p>Vergi Dairesi: <?= htmlspecialchars($f['vergi_dairesi']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Kalemler -->
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ürün / Hizmet</th>
                    <th>Miktar</th>
                    <th>Birim Fiyat</th>
                    <th>KDV %</th>
                    <th>KDV Tutarı</th>
                    <th>Toplam</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kalemler as $i => $k):
                    $ara = $k['miktar'] * $k['birim_fiyat'];
                    $kdv = $ara * $k['kdv_orani'] / 100;
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($k['urun_adi']) ?></td>
                        <td><?= number_format($k['miktar'], 2, ',', '.') ?></td>
                        <td><?= number_format($k['birim_fiyat'], 2, ',', '.') ?> ₺</td>
                        <td>%<?= (int) $k['kdv_orani'] ?></td>
                        <td><?= number_format($kdv, 2, ',', '.') ?> ₺</td>
                        <td><?= number_format($ara + $kdv, 2, ',', '.') ?> ₺</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Toplam -->
        <div class="toplam-kutu">
            <div>Ara Toplam:
                <strong><?= number_format($araToplam, 2, ',', '.') ?> ₺</strong>
            </div>
            <div>KDV Toplamı:
                <strong><?= number_format($kdvToplam, 2, ',', '.') ?> ₺</strong>
            </div>
            <div class="genel-toplam">GENEL TOPLAM:
                <strong><?= number_format($genelToplam, 2, ',', '.') ?> ₺</strong>
            </div>
        </div>

        <?php if ($f['notlar']): ?>
            <div class="notlar">
                <strong>Notlar:</strong>
                <?= nl2br(htmlspecialchars($f['notlar'])) ?>
            </div>
        <?php endif; ?>

        <!-- Butonlar -->
        <div class="no-print" style="margin-top:20px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Yazdır / PDF
            </button>
            <a href="index.php" class="btn">← Listeye Dön</a>
        </div>

    </div>
</body>

</html>