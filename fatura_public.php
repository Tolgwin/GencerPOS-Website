<?php
/**
 * Fatura Paylaşım Sayfası — Login gerektirmez, token ile erişilir
 * URL: fatura_public.php?t=<token>
 */
$token = trim($_GET['t'] ?? '');
if (!$token || strlen($token) < 32) {
    http_response_code(404);
    die('<div style="font-family:sans-serif;text-align:center;padding:80px;color:#6b7280">
        <h2 style="color:#ef4444">Geçersiz Bağlantı</h2>
        <p>Bu fatura linki geçersiz veya süresi dolmuş.</p>
    </div>');
}

require 'db.php';

$stmt = $pdo->prepare("
    SELECT f.*,
           m.ad_soyad, m.email, m.telefon, m.adres,
           m.vergi_no, m.vergi_dairesi
    FROM faturalar f
    JOIN musteriler m ON f.musteri_id = m.id
    WHERE f.paylasim_token = ?
");
$stmt->execute([$token]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$f) {
    http_response_code(404);
    die('<div style="font-family:sans-serif;text-align:center;padding:80px;color:#6b7280">
        <h2 style="color:#ef4444">Fatura Bulunamadı</h2>
        <p>Bu link geçersiz veya fatura silinmiş olabilir.</p>
    </div>');
}

$f = array_merge([
    'fatura_no'=>'','tarih'=>date('Y-m-d'),'vade_tarihi'=>date('Y-m-d'),
    'durum'=>'','notlar'=>'','odeme_yontemi'=>'',
    'ad_soyad'=>'','email'=>'','telefon'=>'','adres'=>'',
    'vergi_no'=>'','vergi_dairesi'=>''
], $f);

// Kalemler
$kst = $pdo->prepare("SELECT * FROM fatura_kalemleri WHERE fatura_id = ? ORDER BY id");
$kst->execute([$f['id']]);
$kalemler = $kst->fetchAll(PDO::FETCH_ASSOC);

$araToplam = 0; $kdvToplam = 0;
foreach ($kalemler as $k) {
    $ara = $k['miktar'] * $k['birim_fiyat'];
    $araToplam += $ara;
    $kdvToplam += $ara * ($k['kdv_orani'] / 100);
}
$genelToplam = $araToplam + $kdvToplam;

// Tahsilatlar
$tst = $pdo->prepare("SELECT * FROM tahsilatlar WHERE fatura_id = ? ORDER BY tarih");
$tst->execute([$f['id']]);
$tahsilatlar = $tst->fetchAll(PDO::FETCH_ASSOC);
$odenen = array_sum(array_column($tahsilatlar, 'tutar'));
$kalan  = max(0, $genelToplam - $odenen);

// Firma
$config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$firma = [
    'unvan'          => $config['firma_unvan']   ?? 'FaturaApp',
    'adres'          => $config['firma_adres']   ?? '',
    'tel'            => $config['firma_tel']     ?? $config['firma_telefon'] ?? '',
    'email'          => $config['firma_email']   ?? '',
    'vkn'            => $config['firma_vkn']     ?? '',
    'web'            => $config['firma_web']     ?? '',
    'banka_hesaplari'=> $config['banka_hesaplari'] ?? [],
];

$durum = $f['durum'];
$durumRenk = ['odendi'=>'#10b981','beklemede'=>'#f59e0b','iptal'=>'#ef4444','kismen_odendi'=>'#3b82f6'];
$durumAd   = ['odendi'=>'ÖDENDİ','beklemede'=>'BEKLİYOR','iptal'=>'İPTAL','kismen_odendi'=>'KISMİ ÖDENDİ'];
$dr = $durumRenk[$durum] ?? '#6b7280';
$da = $durumAd[$durum]   ?? strtoupper($durum);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fatura <?= htmlspecialchars($f['fatura_no']) ?> — <?= htmlspecialchars($firma['unvan']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; color: #1e293b; }

/* ÜSTTE ARAÇ ÇUBUĞU */
.toolbar {
    background: linear-gradient(135deg, #1e3a8a, #3b82f6);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    box-shadow: 0 3px 12px rgba(30,58,138,.25);
    position: sticky; top: 0; z-index: 100;
}
.toolbar-brand { color: #fff; font-size: 15px; font-weight: 700; }
.toolbar-brand span { opacity: .7; font-size: 12px; font-weight: 400; }
.toolbar-btns { display: flex; gap: 8px; flex-wrap: wrap; }
.btn { padding: 8px 18px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .2s; }
.btn-yazdir { background: rgba(255,255,255,.2); color: #fff; border: 1.5px solid rgba(255,255,255,.4); }
.btn-yazdir:hover { background: rgba(255,255,255,.35); }
.btn-kopyala { background: #fff; color: #1e3a8a; }
.btn-kopyala:hover { background: #e0e7ff; }
.btn-whatsapp { background: #25d366; color: #fff; }
.btn-whatsapp:hover { background: #1ebe5c; }
@media print {
    .toolbar { display: none !important; }
    body { background: #fff; }
    .sayfa { box-shadow: none !important; max-width: 100%; padding: 10mm; }
}

/* SAYFA */
.sayfa {
    max-width: 800px;
    margin: 24px auto;
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 30px rgba(0,0,0,.1);
}
@media (max-width: 640px) { .sayfa { margin: 0; border-radius: 0; padding: 20px; } }

/* ÜST ALAN */
.fatura-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 28px; flex-wrap: wrap; }
.firma-adi { font-size: 22px; font-weight: 800; color: #1e3a8a; }
.firma-alt { font-size: 13px; color: #64748b; margin-top: 6px; line-height: 1.7; }
.fatura-blok { text-align: right; }
.fatura-baslik { font-size: 32px; font-weight: 900; color: #e2e8f0; letter-spacing: 2px; }
.fatura-no { font-size: 16px; font-weight: 800; color: #1e3a8a; }
.fatura-meta { font-size: 13px; color: #64748b; margin-top: 4px; line-height: 1.7; }
.durum-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; color: #fff; margin-top: 6px; }

/* BÖLÜCÜ */
.divider { border: none; border-top: 2px solid #e2e8f0; margin: 20px 0; }
.divider-accent { border-top-color: #3b82f6; }

/* İKİ KOLON */
.iki-kolon { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
@media (max-width: 500px) { .iki-kolon { grid-template-columns: 1fr; } }
.bilgi-blok { background: #f8fafc; border-radius: 10px; padding: 14px 16px; border-left: 4px solid #3b82f6; }
.bilgi-blok.sag { border-left-color: #10b981; }
.bilgi-baslik { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.bilgi-icerik { font-size: 14px; color: #1e293b; line-height: 1.7; }
.bilgi-icerik strong { font-size: 15px; font-weight: 800; display: block; margin-bottom: 2px; }

/* TABLO */
.kalem-tablo { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
.kalem-tablo thead th { padding: 12px 14px; background: #1e3a8a; color: #fff; font-size: 12px; font-weight: 700; text-align: left; }
.kalem-tablo thead th:not(:nth-child(2)) { text-align: right; }
.kalem-tablo thead th:nth-child(2) { text-align: left; }
.kalem-tablo tbody td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.kalem-tablo tbody td:not(:nth-child(2)) { text-align: right; }
.kalem-tablo tbody td:nth-child(2) { text-align: left; }
.kalem-tablo tbody tr:nth-child(even) td { background: #f8fafc; }
.kalem-adi { font-weight: 600; }
.kalem-kod { font-size: 11px; color: #94a3b8; }
@media (max-width: 640px) {
    .kalem-tablo thead th:nth-child(n+4), .kalem-tablo tbody td:nth-child(n+4) { display: none; }
}

/* TOPLAM */
.toplam-alan { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.toplam-tablo { min-width: 260px; }
.toplam-satir { display: flex; justify-content: space-between; gap: 40px; padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
.toplam-satir.genel { font-size: 18px; font-weight: 900; color: #1e3a8a; border-top: 2px solid #1e3a8a; border-bottom: none; padding-top: 10px; }
.toplam-satir.odenen { color: #10b981; font-weight: 700; }
.toplam-satir.kalan  { color: #ef4444; font-weight: 700; font-size: 16px; }

/* TAHSİLATLAR */
.tahsilat-kutu { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; margin-bottom: 20px; }
.tahsilat-baslik { font-size: 12px; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
.tahsilat-satir { display: flex; gap: 12px; font-size: 13px; padding: 5px 0; border-bottom: 1px dotted #d1fae5; }
.tahsilat-satir:last-child { border-bottom: none; }

/* NOTLAR */
.notlar-kutu { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px; margin-bottom: 20px; font-size: 14px; }
.notlar-kutu strong { display: block; margin-bottom: 6px; color: #92400e; }

/* BANKA */
.banka-kutu { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 16px; margin-top: 20px; }
.banka-kutu h4 { color: #0369a1; font-size: 14px; margin-bottom: 10px; }
.banka-satir { padding: 8px 0; border-bottom: 1px dotted #bae6fd; font-size: 13px; line-height: 1.6; }
.banka-satir:last-child { border-bottom: none; }

/* İMZA */
.imza-alan { display: flex; gap: 30px; margin-top: 30px; }
.imza-kutu { flex: 1; text-align: center; }
.imza-cizgi { border-top: 1px solid #94a3b8; padding-top: 8px; font-size: 12px; color: #6b7280; margin-top: 40px; }

/* ALT BİLGİ */
.alt-footer { text-align: center; font-size: 12px; color: #94a3b8; margin: 30px auto 10px; }

/* KOPYALA TOAST */
.toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(60px); background: #1e293b; color: #fff; padding: 10px 22px; border-radius: 30px; font-size: 14px; font-weight: 600; opacity: 0; transition: all .3s; z-index: 9999; pointer-events: none; }
.toast.goster { opacity: 1; transform: translateX(-50%) translateY(0); }
</style>
</head>
<body>

<!-- ARAÇ ÇUBUĞU -->
<div class="toolbar">
    <div class="toolbar-brand">
        <?= htmlspecialchars($firma['unvan']) ?>
        <span> — Fatura Görüntüleme</span>
    </div>
    <div class="toolbar-btns">
        <button class="btn btn-kopyala" onclick="linkiKopyala()">📋 Linki Kopyala</button>
        <?php if ($f['telefon']): ?>
        <a class="btn btn-whatsapp" href="https://wa.me/<?= preg_replace('/\D/','',$f['telefon']) ?>?text=<?= rawurlencode("Merhaba " . $f['ad_soyad'] . ", " . $f['fatura_no'] . " numaralı faturanızı görüntülemek için: " . "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI']) ?>" target="_blank">
            💬 WhatsApp ile Gönder
        </a>
        <?php endif; ?>
        <button class="btn btn-yazdir" onclick="window.print()">🖨️ Yazdır / PDF</button>
    </div>
</div>

<!-- A4 SAYFA -->
<div class="sayfa">

    <!-- ÜST -->
    <div class="fatura-top">
        <div>
            <div class="firma-adi"><?= htmlspecialchars($firma['unvan']) ?></div>
            <div class="firma-alt">
                <?php if ($firma['adres']): ?><?= htmlspecialchars($firma['adres']) ?><br><?php endif; ?>
                <?php if ($firma['tel']): ?>📞 <?= htmlspecialchars($firma['tel']) ?><?php endif; ?>
                <?php if ($firma['email']): ?>  ✉️ <?= htmlspecialchars($firma['email']) ?><?php endif; ?>
                <?php if ($firma['vkn']): ?><br>VKN: <?= htmlspecialchars($firma['vkn']) ?><?php endif; ?>
            </div>
        </div>
        <div class="fatura-blok">
            <div class="fatura-baslik">FATURA</div>
            <div class="fatura-no">#<?= htmlspecialchars($f['fatura_no']) ?></div>
            <div class="fatura-meta">
                Tarih: <?= date('d.m.Y', strtotime($f['tarih'])) ?><br>
                Vade: <?= date('d.m.Y', strtotime($f['vade_tarihi'])) ?>
            </div>
            <div class="durum-badge" style="background:<?= $dr ?>"><?= $da ?></div>
        </div>
    </div>

    <hr class="divider divider-accent">

    <!-- MÜŞTERİ / FATURA BİLGİLERİ -->
    <div class="iki-kolon">
        <div class="bilgi-blok">
            <div class="bilgi-baslik">Fatura Kesilen</div>
            <div class="bilgi-icerik">
                <strong><?= htmlspecialchars($f['ad_soyad']) ?></strong>
                <?php if ($f['vergi_dairesi'] || $f['vergi_no']): ?>
                    <?= htmlspecialchars($f['vergi_dairesi']) ?> V.D. / VKN: <?= htmlspecialchars($f['vergi_no']) ?><br>
                <?php endif; ?>
                <?php if ($f['adres']): ?><?= nl2br(htmlspecialchars($f['adres'])) ?><br><?php endif; ?>
                <?php if ($f['telefon']): ?>📞 <?= htmlspecialchars($f['telefon']) ?><br><?php endif; ?>
                <?php if ($f['email']): ?>✉️ <?= htmlspecialchars($f['email']) ?><?php endif; ?>
            </div>
        </div>
        <div class="bilgi-blok sag">
            <div class="bilgi-baslik">Fatura Bilgileri</div>
            <div class="bilgi-icerik">
                <strong><?= htmlspecialchars($f['fatura_no']) ?></strong>
                Düzenleme: <?= date('d.m.Y', strtotime($f['tarih'])) ?><br>
                Vade: <?= date('d.m.Y', strtotime($f['vade_tarihi'])) ?><br>
                <?php if ($f['odeme_yontemi']): ?>Ödeme: <?= htmlspecialchars($f['odeme_yontemi']) ?><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- KALEMLER -->
    <table class="kalem-tablo">
        <thead>
            <tr>
                <th style="text-align:left;width:5%">#</th>
                <th style="text-align:left;width:42%">Ürün / Hizmet</th>
                <th style="width:10%">Miktar</th>
                <th style="width:15%">Birim Fiyat</th>
                <th style="width:10%">KDV %</th>
                <th style="width:10%">KDV</th>
                <th style="width:13%">Toplam</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($kalemler as $i => $k):
            $ara = $k['miktar'] * $k['birim_fiyat'];
            $kdv = $ara * ($k['kdv_orani'] / 100);
        ?>
            <tr>
                <td style="color:#94a3b8;font-size:12px"><?= $i+1 ?></td>
                <td style="text-align:left">
                    <div class="kalem-adi"><?= htmlspecialchars($k['urun_adi']) ?></div>
                    <?php if (!empty($k['urun_kodu'])): ?><div class="kalem-kod"><?= htmlspecialchars($k['urun_kodu']) ?></div><?php endif; ?>
                    <?php if (!empty($k['seri_no'])): ?>
                        <div style="font-size:11px;color:#475569;font-family:monospace">Seri No: <?= htmlspecialchars($k['seri_no']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= number_format($k['miktar'],2,',','.') ?></td>
                <td><?= number_format($k['birim_fiyat'],2,',','.') ?> ₺</td>
                <td style="color:#6b7280">%<?= (int)$k['kdv_orani'] ?></td>
                <td style="color:#6b7280"><?= number_format($kdv,2,',','.') ?> ₺</td>
                <td style="font-weight:700"><?= number_format($ara+$kdv,2,',','.') ?> ₺</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOPLAM -->
    <div class="toplam-alan">
        <div class="toplam-tablo">
            <div class="toplam-satir"><span>Ara Toplam</span><strong><?= number_format($araToplam,2,',','.') ?> ₺</strong></div>
            <div class="toplam-satir"><span>KDV</span><strong><?= number_format($kdvToplam,2,',','.') ?> ₺</strong></div>
            <div class="toplam-satir genel"><span>GENEL TOPLAM</span><strong><?= number_format($genelToplam,2,',','.') ?> ₺</strong></div>
            <?php if ($odenen > 0): ?>
            <div class="toplam-satir odenen"><span>Ödenen</span><strong><?= number_format($odenen,2,',','.') ?> ₺</strong></div>
            <?php endif; ?>
            <?php if ($kalan > 0): ?>
            <div class="toplam-satir kalan"><span>Kalan</span><strong><?= number_format($kalan,2,',','.') ?> ₺</strong></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($tahsilatlar): ?>
    <div class="tahsilat-kutu">
        <div class="tahsilat-baslik">💳 Ödeme Geçmişi</div>
        <?php foreach ($tahsilatlar as $t): ?>
        <div class="tahsilat-satir">
            <span style="color:#6b7280;min-width:80px"><?= date('d.m.Y', strtotime($t['tarih'])) ?></span>
            <span style="flex:1"><?= htmlspecialchars($t['odeme_tipi'] ?? $t['aciklama'] ?? '-') ?></span>
            <strong style="color:#10b981"><?= number_format($t['tutar'],2,',','.') ?> ₺</strong>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($f['notlar']): ?>
    <div class="notlar-kutu">
        <strong>📝 Açıklama</strong>
        <?= nl2br(htmlspecialchars($f['notlar'])) ?>
    </div>
    <?php endif; ?>

    <!-- İMZA -->
    <div class="imza-alan">
        <div class="imza-kutu"><div class="imza-cizgi">Düzenleyen / Kaşe - İmza</div></div>
        <div class="imza-kutu"><div class="imza-cizgi">Teslim Alan / İmza</div></div>
    </div>

    <?php if (!empty($firma['banka_hesaplari'])): ?>
    <div class="banka-kutu">
        <h4>🏦 Hesap Numaralarımız</h4>
        <?php foreach ($firma['banka_hesaplari'] as $bh): ?>
        <div class="banka-satir">
            <?php if (!empty($bh['banka'])): ?><strong><?= htmlspecialchars($bh['banka']) ?></strong><?php if(!empty($bh['sube'])): ?> — <?= htmlspecialchars($bh['sube']) ?><?php endif; ?><br><?php endif; ?>
            <?php if (!empty($bh['hesap_no'])): ?>Hesap No: <?= htmlspecialchars($bh['hesap_no']) ?><br><?php endif; ?>
            <?php if (!empty($bh['iban'])): ?>IBAN: <strong style="font-family:monospace"><?= htmlspecialchars($bh['iban']) ?></strong><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<div class="alt-footer">
    Bu fatura <?= htmlspecialchars($firma['unvan']) ?> tarafından düzenlenmiştir.
    <?php if ($firma['web']): ?> · <?= htmlspecialchars($firma['web']) ?><?php endif; ?>
</div>

<div class="toast" id="toast">✅ Link panoya kopyalandı!</div>

<script>
function linkiKopyala() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        const t = document.getElementById('toast');
        t.classList.add('goster');
        setTimeout(() => t.classList.remove('goster'), 2500);
    }).catch(() => {
        prompt('Linki kopyalayın:', url);
    });
}
</script>
</body>
</html>
