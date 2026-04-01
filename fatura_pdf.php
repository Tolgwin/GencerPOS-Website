<?php
require 'db.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Geçersiz fatura ID');

$stmt = $pdo->prepare("
    SELECT f.*,
           m.ad_soyad, m.email, m.telefon, m.adres,
           m.vergi_no, m.vergi_dairesi,
           p.ad_soyad AS personel_adi
    FROM faturalar f
    JOIN musteriler m ON f.musteri_id = m.id
    LEFT JOIN personeller p ON f.personel_id = p.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$f) die('Fatura bulunamadı');

$f = array_merge([
    'fatura_no'=>'','tarih'=>date('Y-m-d'),'vade_tarihi'=>date('Y-m-d'),
    'durum'=>'','notlar'=>'','odeme_yontemi'=>'',
    'ad_soyad'=>'','email'=>'','telefon'=>'','adres'=>'',
    'vergi_no'=>'','vergi_dairesi'=>'','personel_adi'=>''
], $f);

// Kalemler
$kst = $pdo->prepare("SELECT * FROM fatura_kalemleri WHERE fatura_id = ? ORDER BY id");
$kst->execute([$id]);
$kalemler = $kst->fetchAll(PDO::FETCH_ASSOC);

$araToplam = 0; $kdvToplam = 0;
foreach ($kalemler as $k) {
    $ara = $k['miktar'] * $k['birim_fiyat'];
    $araToplam += $ara;
    $kdvToplam += $ara * ($k['kdv_orani'] / 100);
}
$genelToplam = $araToplam + $kdvToplam;
$kalan = $f['kalan'] ?? $genelToplam;

// Tahsilatlar
$tst = $pdo->prepare("
    SELECT * FROM tahsilatlar
    WHERE fatura_id = ?
    ORDER BY tarih
");
$tst->execute([$id]);
$tahsilatlar = $tst->fetchAll(PDO::FETCH_ASSOC);

// Firma bilgisi
$configFile = __DIR__ . '/config.php';
$config = file_exists($configFile) ? require $configFile : [];
$firma = [
    'unvan'   => $config['firma_unvan']   ?? 'FaturaApp',
    'adres'   => $config['firma_adres']   ?? '',
    'tel'     => $config['firma_tel']     ?? $config['firma_telefon'] ?? '',
    'email'   => $config['firma_email']   ?? '',
    'vkn'     => $config['firma_vkn']     ?? '',
    'web'     => $config['firma_web']     ?? '',
    'logo'    => $config['firma_logo']    ?? '',
    'banka_hesaplari' => $config['banka_hesaplari'] ?? (
        (!empty($config['firma_banka']) || !empty($config['firma_iban']))
        ? [['banka'=>$config['firma_banka']??'','sube'=>'','hesap_no'=>'','iban'=>$config['firma_iban']??'']]
        : []
    ),
];

$durumRenk = ['odendi'=>'#10b981','beklemede'=>'#f59e0b','iptal'=>'#ef4444','kismen_odendi'=>'#3b82f6'];
$durumAd   = ['odendi'=>'✅ ÖDENDİ','beklemede'=>'⏳ BEKLİYOR','iptal'=>'❌ İPTAL','kismen_odendi'=>'💸 KISMİ'];
$dr = $durumRenk[$f['durum']] ?? '#6b7280';
$da = $durumAd[$f['durum']]   ?? strtoupper($f['durum']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fatura <?= htmlspecialchars($f['fatura_no']) ?></title>
<style>
@page { size: A4; margin: 12mm 14mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #1e293b; background: #f1f5f9; }

.sayfa { width: 210mm; min-height: 297mm; background: #fff; margin: 0 auto; padding: 14mm 16mm; position: relative; }

/* ÜSTLE ARAÇ ÇUBUĞU */
.toolbar { display:flex; gap:10px; justify-content:center; padding:14px; position:sticky; top:0; background:rgba(255,255,255,.9); backdrop-filter:blur(6px); box-shadow:0 2px 10px rgba(0,0,0,.1); z-index:100; }
.toolbar button,.toolbar a { padding:9px 22px; border:none; border-radius:9px; cursor:pointer; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-pdf   { background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; box-shadow:0 3px 10px rgba(220,38,38,.3); }
.btn-yazdir{ background:linear-gradient(135deg,#1e3a8a,#3b82f6); color:#fff; box-shadow:0 3px 10px rgba(59,130,246,.3); }
.btn-geri  { background:#f1f5f9; color:#374151; border:1.5px solid #e2e8f0; }
@media print { .toolbar { display:none !important; } body { background:#fff; } .sayfa { padding:0; width:100%; box-shadow:none; } }

/* HEADER */
.fatura-top { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; margin-bottom:8mm; }
.firma-blok { flex: 1; }
.firma-adi  { font-size:17pt; font-weight:800; color:#1e3a8a; line-height:1.2; }
.firma-alt  { font-size:9pt; color:#6b7280; margin-top:2mm; line-height:1.6; }

.fatura-blok { text-align:right; min-width:55mm; }
.fatura-baslik { font-size:26pt; font-weight:900; color:#e2e8f0; letter-spacing:2px; line-height:1; }
.fatura-no     { font-size:12pt; font-weight:800; color:#1e3a8a; margin-top:2mm; }
.fatura-meta   { font-size:9pt; color:#6b7280; margin-top:1mm; line-height:1.7; }
.durum-badge   { display:inline-block; padding:3px 12px; border-radius:20px; font-size:9pt; font-weight:800; color:#fff; margin-top:2mm; }

/* BÖLÜCÜ ÇİZGİ */
.divider { border:none; border-top:2px solid #e2e8f0; margin:5mm 0; }
.divider-accent { border-top-color:#3b82f6; }

/* MÜŞTERİ/FİRMA BLOKLARI */
.iki-kolon { display:grid; grid-template-columns:1fr 1fr; gap:10mm; margin-bottom:6mm; }
.bilgi-blok { background:#f8fafc; border-radius:8px; padding:4mm 5mm; border-left:3px solid #3b82f6; }
.bilgi-blok.sag { border-left-color:#10b981; }
.bilgi-baslik { font-size:7.5pt; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2mm; }
.bilgi-icerik { font-size:9.5pt; color:#1e293b; line-height:1.7; }
.bilgi-icerik strong { font-size:11pt; font-weight:800; display:block; margin-bottom:1mm; }

/* KALEMLER TABLOSU */
.kalem-tablo { width:100%; border-collapse:collapse; margin-bottom:4mm; }
.kalem-tablo thead th { padding:3mm 3.5mm; background:#1e3a8a; color:#fff; font-size:8.5pt; font-weight:700; text-align:left; }
.kalem-tablo thead th:last-child,.kalem-tablo thead th:nth-last-child(-n+3) { text-align:right; }
.kalem-tablo tbody td { padding:3mm 3.5mm; border-bottom:1px solid #f1f5f9; font-size:9pt; vertical-align:middle; }
.kalem-tablo tbody tr:nth-child(even) td { background:#f8fafc; }
.kalem-tablo tbody tr:last-child td { border-bottom:none; }
.kalem-tablo tbody td:last-child,.kalem-tablo tbody td:nth-last-child(-n+2) { text-align:right; }
.kalem-adi { font-weight:600; color:#1e293b; }
.kalem-kod { font-size:7.5pt; color:#94a3b8; }

/* TOPLAM KUTUSU */
.toplam-alan { display:flex; justify-content:flex-end; margin-bottom:6mm; }
.toplam-tablo { min-width:70mm; }
.toplam-satir { display:flex; justify-content:space-between; gap:20mm; padding:2mm 0; font-size:9.5pt; border-bottom:1px solid #f1f5f9; }
.toplam-satir.genel { font-size:13pt; font-weight:900; color:#1e3a8a; border-top:2px solid #1e3a8a; border-bottom:none; padding-top:3mm; }
.toplam-satir.odenen { color:#10b981; font-weight:700; }
.toplam-satir.kalan  { color:#ef4444; font-weight:700; font-size:11pt; }

/* TAHSİLATLAR */
.tahsilat-baslik { font-size:8.5pt; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2mm; }
.tahsilat-satir  { display:flex; gap:6mm; font-size:8.5pt; padding:1.5mm 0; border-bottom:1px dotted #e2e8f0; }
.tahsilat-satir:last-child { border-bottom:none; }

/* NOTLAR */
.notlar-kutu { background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; padding:4mm; margin-bottom:6mm; font-size:9pt; }
.notlar-kutu strong { display:block; margin-bottom:1.5mm; color:#92400e; }

/* BANKA */
.banka-kutu { background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:4mm; font-size:9pt; }
.banka-kutu strong { color:#0369a1; }

/* İMZA ALANI */
.imza-alan { display:flex; gap:15mm; margin-top:8mm; }
.imza-kutu { flex:1; text-align:center; }
.imza-cizgi { border-top:1px solid #94a3b8; padding-top:2mm; font-size:8pt; color:#6b7280; margin-top:12mm; }

/* ALT BİLGİ */
.alt-bilgi { position:absolute; bottom:10mm; left:16mm; right:16mm; border-top:1px solid #e2e8f0; padding-top:3mm; display:flex; justify-content:space-between; font-size:7.5pt; color:#9ca3af; }
</style>
</head>
<body>

<!-- ARAÇ ÇUBUĞU (yazdırmada gizlenir) -->
<div class="toolbar">
  <button class="btn-pdf" onclick="window.print()">🖨️ PDF Olarak Kaydet / Yazdır</button>
  <button class="btn-yazdir" onclick="window.print()">🖨️ Yazdır</button>
  <a class="btn-geri" href="javascript:window.close()">✕ Kapat</a>
</div>

<!-- A4 SAYFA -->
<div class="sayfa">

  <!-- FATURA ÜSTÜ -->
  <div class="fatura-top">
    <div class="firma-blok">
      <div class="firma-adi"><?= htmlspecialchars($firma['unvan']) ?></div>
      <div class="firma-alt">
        <?php if ($firma['adres']): ?><?= htmlspecialchars($firma['adres']) ?><br><?php endif; ?>
        <?php if ($firma['tel']): ?>📞 <?= htmlspecialchars($firma['tel']) ?><?php if($firma['email']): ?>  &nbsp;&bull;&nbsp;  <?php endif; ?><?php endif; ?>
        <?php if ($firma['email']): ?>✉️ <?= htmlspecialchars($firma['email']) ?><?php endif; ?>
        <?php if ($firma['vkn']): ?><br>VKN: <?= htmlspecialchars($firma['vkn']) ?><?php endif; ?>
      </div>
    </div>
    <div class="fatura-blok">
      <div class="fatura-baslik">FATURA</div>
      <div class="fatura-no">#<?= htmlspecialchars($f['fatura_no']) ?></div>
      <div class="fatura-meta">
        <strong>Tarih:</strong> <?= date('d.m.Y', strtotime($f['tarih'])) ?><br>
        <strong>Vade:</strong> <?= date('d.m.Y', strtotime($f['vade_tarihi'])) ?><br>
        <?php if ($f['odeme_yontemi']): ?><strong>Ödeme:</strong> <?= htmlspecialchars($f['odeme_yontemi']) ?><?php endif; ?>
      </div>
    </div>
  </div>

  <hr class="divider divider-accent">

  <!-- MÜŞTERİ / SATIŞ BİLGİLERİ -->
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
        <th style="width:5%">#</th>
        <th style="width:40%">Ürün / Hizmet</th>
        <th style="width:10%;text-align:center">Miktar</th>
        <th style="width:15%;text-align:right">Birim Fiyat</th>
        <th style="width:10%;text-align:center">KDV %</th>
        <th style="width:10%;text-align:right">KDV</th>
        <th style="width:10%;text-align:right">Toplam</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($kalemler as $i => $k):
        $ara = $k['miktar'] * $k['birim_fiyat'];
        $kdv = $ara * ($k['kdv_orani'] / 100);
    ?>
      <tr>
        <td style="color:#94a3b8;font-size:8.5pt"><?= $i+1 ?></td>
        <td>
          <div class="kalem-adi"><?= htmlspecialchars($k['urun_adi']) ?></div>
          <?php if (!empty($k['urun_kodu'])): ?><div class="kalem-kod"><?= htmlspecialchars($k['urun_kodu']) ?></div><?php endif; ?>
          <?php if (!empty($k['seri_no'])): ?>
            <div style="font-size:7.5pt;color:#475569;margin-top:2px;font-family:monospace;">
              Seri No: <?= htmlspecialchars($k['seri_no']) ?>
            </div>
          <?php endif; ?>
        </td>
        <td style="text-align:center"><?= number_format($k['miktar'],2,',','.') ?></td>
        <td style="text-align:right"><?= number_format($k['birim_fiyat'],2,',','.') ?> ₺</td>
        <td style="text-align:center;color:#6b7280">%<?= (int)$k['kdv_orani'] ?></td>
        <td style="text-align:right;color:#6b7280"><?= number_format($kdv,2,',','.') ?> ₺</td>
        <td style="text-align:right;font-weight:700"><?= number_format($ara+$kdv,2,',','.') ?> ₺</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <!-- TOPLAM -->
  <div class="toplam-alan">
    <div class="toplam-tablo">
      <div class="toplam-satir">
        <span>Ara Toplam</span>
        <strong><?= number_format($araToplam,2,',','.') ?> ₺</strong>
      </div>
      <div class="toplam-satir">
        <span>KDV Toplamı</span>
        <strong><?= number_format($kdvToplam,2,',','.') ?> ₺</strong>
      </div>
      <div class="toplam-satir genel">
        <span>GENEL TOPLAM</span>
        <strong><?= number_format($genelToplam,2,',','.') ?> ₺</strong>
      </div>
      <?php if ($tahsilatlar): ?>
      <?php $odenen = array_sum(array_column($tahsilatlar,'tutar')); ?>
      <div class="toplam-satir odenen">
        <span>Ödenen</span>
        <strong><?= number_format($odenen,2,',','.') ?> ₺</strong>
      </div>
      <?php if ($kalan > 0): ?>
      <div class="toplam-satir kalan">
        <span>Kalan</span>
        <strong><?= number_format($kalan,2,',','.') ?> ₺</strong>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($tahsilatlar): ?>
  <!-- TAHSİLATLAR -->
  <div class="tahsilat-baslik">💳 Tahsilat Geçmişi</div>
  <?php foreach ($tahsilatlar as $t): ?>
  <div class="tahsilat-satir">
    <span style="color:#6b7280;min-width:22mm"><?= date('d.m.Y', strtotime($t['tarih'])) ?></span>
    <span style="flex:1"><?= htmlspecialchars($t['odeme_tipi'] ?? $t['aciklama'] ?? '-') ?></span>
    <strong style="color:#10b981"><?= number_format($t['tutar'],2,',','.') ?> ₺</strong>
  </div>
  <?php endforeach; ?>
  <hr class="divider" style="margin:4mm 0">
  <?php endif; ?>

  <?php if ($f['notlar']): ?>
  <div class="notlar-kutu">
    <strong>📝 Fatura Açıklaması</strong>
    <?= nl2br(htmlspecialchars($f['notlar'])) ?>
  </div>
  <?php endif; ?>

  <!-- İMZA ALANI -->
  <div class="imza-alan" style="margin-top:10mm">
    <div class="imza-kutu">
      <div class="imza-cizgi">Düzenleyen / Kaşe - İmza</div>
    </div>
    <div class="imza-kutu">
      <div class="imza-cizgi">Teslim Alan / İmza</div>
    </div>
  </div>

  <?php if (!empty($firma['banka_hesaplari'])): ?>
  <div class="banka-kutu" style="margin-top:6mm;">
    <strong>🏦 Hesap Numaralarımız</strong><br>
    <?php foreach ($firma['banka_hesaplari'] as $bh): ?>
    <div style="margin-top:3mm;padding-top:3mm;border-top:1px dotted #bae6fd;">
      <?php if (!empty($bh['banka'])): ?><strong><?= htmlspecialchars($bh['banka']) ?></strong><?php if(!empty($bh['sube'])): ?> — <?= htmlspecialchars($bh['sube']) ?><?php endif; ?><br><?php endif; ?>
      <?php if (!empty($bh['hesap_no'])): ?>Hesap No: <?= htmlspecialchars($bh['hesap_no']) ?><br><?php endif; ?>
      <?php if (!empty($bh['iban'])): ?>IBAN: <strong><?= htmlspecialchars($bh['iban']) ?></strong><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /sayfa -->

<script>
// Ctrl+P kısayolu
document.addEventListener('keydown', e => { if((e.ctrlKey||e.metaKey) && e.key==='p'){ e.preventDefault(); window.print(); } });
</script>
</body>
</html>
