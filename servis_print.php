<?php
require_once 'db.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Geçersiz servis ID');

$stmt = $pdo->prepare("
    SELECT s.*, m.ad_soyad AS musteri_adi, m.telefon AS musteri_telefon,
           m.email AS musteri_email, m.adres AS musteri_adres,
           d.durum_adi, d.renk AS durum_renk
    FROM servisler s
    LEFT JOIN musteriler m ON s.musteri_id=m.id
    LEFT JOIN servis_durumlar d ON s.durum_id=d.id
    WHERE s.id=?
");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) die('Servis bulunamadı');

// Firma bilgisi config.php'den
$configFile = __DIR__ . '/config.php';
$config = file_exists($configFile) ? require $configFile : [];
$firma = [
    'unvan'   => $config['firma_unvan'] ?? 'FaturaApp',
    'adres'   => $config['firma_adres'] ?? '',
    'telefon' => $config['firma_tel']   ?? $config['firma_telefon'] ?? '',
    'vkn'     => $config['firma_vkn']   ?? '',
    'web'     => $config['firma_web']   ?? '',
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Servis Fişi #<?= $id ?></title>
<style>
@page { margin: 2mm; size: 80mm auto; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Courier New', monospace;
  font-size: 9.5pt;
  width: 76mm;
  margin: 0 auto;
  padding: 2mm 1mm;
  color: #000;
  background: #fff;
}
.center { text-align: center; }
.bold   { font-weight: bold; }
.firma-adi  { font-size: 12pt; font-weight: bold; line-height: 1.2; }
.firma-alt  { font-size: 7.5pt; color: #444; margin-top: 0.8mm; line-height: 1.3; }
hr { border: none; border-top: 1px dashed #999; margin: 2.5mm 0; }
hr.solid { border-top: 1px solid #000; }
.baslik { font-size: 10.5pt; font-weight: bold; text-align: center; margin: 2mm 0 1mm; letter-spacing: 1px; }
.sno   { font-size: 13pt; font-weight: bold; }
.durum-badge {
  display: inline-block;
  padding: 0.5mm 2.5mm;
  border-radius: 2px;
  color: #fff;
  font-weight: bold;
  font-size: 8.5pt;
  vertical-align: middle;
}
.satir { display: flex; justify-content: space-between; margin-bottom: 1.2mm; font-size: 8.5pt; }
.satir .etiket { color: #000; font-weight: bold; min-width: 22mm; flex-shrink: 0; }
.satir .deger  { font-weight: 600; text-align: right; flex: 1; word-break: break-word; }
.blok { margin-bottom: 2mm; }
.blok .blok-baslik  { font-size: 7.5pt; color: #000; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.8mm; }
.blok .blok-icerik  { font-size: 8.5pt; border: 1px dotted #aaa; padding: 1mm 1.5mm; border-radius: 1px; min-height: 7mm; white-space: pre-wrap; word-break: break-word; }
.toplam-satir { display: flex; justify-content: space-between; font-size: 11pt; font-weight: bold; margin: 1.5mm 0; }
.imza-alan  { display: flex; gap: 5mm; margin-top: 5mm; }
.imza-kutu  { flex: 1; text-align: center; }
.imza-cizgi { border-top: 1px solid #000; margin-top: 10mm; }
.imza-adi   { font-size: 7.5pt; margin-top: 1mm; }
.footer-txt { font-size: 7.5pt; color: #555; margin-top: 2mm; }
.no-print {
  display: flex; gap: 8px; justify-content: center;
  padding: 10px; position: fixed; top: 10px; right: 10px;
  background: rgba(255,255,255,.9); border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,.15);
}
.no-print button {
  padding: 8px 18px; border: none; border-radius: 6px;
  cursor: pointer; font-size: 13px; font-weight: 600;
}
.btn-yazdir { background: #3b82f6; color: #fff; }
.btn-kapat  { background: #e5e7eb; color: #374151; }
@media print { .no-print { display: none !important; } }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-yazdir" onclick="window.print()">🖨 Yazdır</button>
  <button class="btn-kapat"  onclick="window.close()">✕ Kapat</button>
</div>

<!-- ══ FIRMA BAŞLIĞI ════════════════════════════════════════════════ -->
<div class="center">
  <div class="firma-adi"><?= htmlspecialchars($firma['unvan']) ?></div>
  <?php if ($firma['adres']): ?>
  <div class="firma-alt"><?= htmlspecialchars($firma['adres']) ?></div>
  <?php endif; ?>
  <?php if ($firma['telefon']): ?>
  <div class="firma-alt">Tel: <?= htmlspecialchars($firma['telefon']) ?></div>
  <?php endif; ?>
  <?php if ($firma['vkn']): ?>
  <div class="firma-alt">VKN: <?= htmlspecialchars($firma['vkn']) ?></div>
  <?php endif; ?>
</div>

<hr class="solid">

<div class="baslik">SERVİS FİŞİ</div>

<div class="center" style="margin-bottom:1.5mm">
  <span class="sno">#<?= str_pad($s['id'],5,'0',STR_PAD_LEFT) ?></span>
  <span class="durum-badge" style="background:<?= htmlspecialchars($s['durum_renk']??'#6b7280') ?>;margin-left:2mm">
    <?= htmlspecialchars($s['durum_adi']??'?') ?>
  </span>
</div>

<hr>

<!-- ══ TARİHLER ═════════════════════════════════════════════════════ -->
<div class="satir">
  <span class="etiket">Kayıt Tarihi</span>
  <span class="deger"><?= date('d.m.Y H:i', strtotime($s['kayit_tarihi'])) ?></span>
</div>
<?php if ($s['teslim_tarihi']): ?>
<div class="satir">
  <span class="etiket">Teslim Tarihi</span>
  <span class="deger"><?= date('d.m.Y', strtotime($s['teslim_tarihi'])) ?></span>
</div>
<?php endif; ?>

<hr>

<!-- ══ MÜŞTERİ ══════════════════════════════════════════════════════ -->
<div class="satir"><span class="etiket">Müşteri</span><span class="deger"><?= htmlspecialchars($s['musteri_adi']??'-') ?></span></div>
<?php if ($s['musteri_telefon']): ?>
<div class="satir"><span class="etiket">Telefon</span><span class="deger"><?= htmlspecialchars($s['musteri_telefon']) ?></span></div>
<?php endif; ?>
<?php if ($s['musteri_email']): ?>
<div class="satir"><span class="etiket">E-posta</span><span class="deger"><?= htmlspecialchars($s['musteri_email']) ?></span></div>
<?php endif; ?>

<hr>

<!-- ══ CİHAZ ════════════════════════════════════════════════════════ -->
<div class="satir"><span class="etiket">Cihaz Türü</span><span class="deger"><?= htmlspecialchars($s['cihaz_turu']??'-') ?></span></div>
<?php if ($s['marka'] || $s['model']): ?>
<div class="satir"><span class="etiket">Marka / Model</span><span class="deger"><?= htmlspecialchars(trim(($s['marka']??'').' '.($s['model']??''))) ?></span></div>
<?php endif; ?>
<?php if ($s['seri_no']): ?>
<div class="satir"><span class="etiket">Seri No</span><span class="deger"><?= htmlspecialchars($s['seri_no']) ?></span></div>
<?php endif; ?>

<?php if ($s['sikayet'] || $s['yapilan_islem'] || $s['notlar'] ?? ''): ?>
<hr>
<?php endif; ?>

<!-- ══ ŞİKAYET ══════════════════════════════════════════════════════ -->
<?php if ($s['sikayet']): ?>
<div class="blok">
  <div class="blok-baslik">Şikayet / Arıza Tanımı</div>
  <div class="blok-icerik"><?= htmlspecialchars($s['sikayet']) ?></div>
</div>
<?php endif; ?>

<!-- ══ YAPILAN İŞLEM ════════════════════════════════════════════════ -->
<?php if ($s['yapilan_islem']): ?>
<div class="blok">
  <div class="blok-baslik">Yapılan İşlem / Açıklama</div>
  <div class="blok-icerik"><?= htmlspecialchars($s['yapilan_islem']) ?></div>
</div>
<?php endif; ?>

<!-- ══ PARÇALAR ═════════════════════════════════════════════════════ -->
<?php
$parcalar = $pdo->prepare("SELECT * FROM servis_parcalar WHERE servis_id=?");
$parcalar->execute([$id]);
$pList = $parcalar->fetchAll(PDO::FETCH_ASSOC);
if ($pList):
?>
<hr>
<div class="blok-baslik" style="margin-bottom:1mm">Kullanılan Parçalar</div>
<?php foreach($pList as $p): ?>
<div class="satir" style="font-size:8pt">
  <span class="etiket"><?= htmlspecialchars($p['urun_adi'] ?? '') ?></span>
  <span class="deger"><?= number_format($p['birim_fiyat']??0,2,',','.') ?> ₺ × <?= (int)($p['miktar']??1) ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ══ TUTAR ════════════════════════════════════════════════════════ -->
<?php if ($s['tutar'] > 0): ?>
<hr class="solid">
<div class="toplam-satir">
  <span>TOPLAM</span>
  <span><?= number_format($s['tutar'],2,',','.') ?> ₺</span>
</div>
<?php endif; ?>

<hr>

<!-- ══ İMZA ════════════════════════════════════════════════════════ -->
<div class="imza-alan">
  <div class="imza-kutu">
    <div class="imza-cizgi"></div>
    <div class="imza-adi">Yetkili İmzası</div>
  </div>
  <div class="imza-kutu">
    <div class="imza-cizgi"></div>
    <div class="imza-adi">Müşteri İmzası</div>
  </div>
</div>

<hr>

<!-- ══ ALT BİLGİ ════════════════════════════════════════════════════ -->
<div class="center footer-txt">
  Bizi tercih ettiğiniz için teşekkür ederiz.<br>
  <?php if ($firma['web']): ?><?= htmlspecialchars($firma['web']) ?><br><?php endif; ?>
  <small style="color:#aaa">Servis #<?= str_pad($id,5,'0',STR_PAD_LEFT) ?> · <?= date('d.m.Y H:i') ?></small>
</div>

</body>
</html>
