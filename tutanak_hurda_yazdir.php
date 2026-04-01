<?php
require_once 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id){ echo 'Geçersiz ID'; exit; }

$stmt = $pdo->prepare("SELECT h.*, f.company_name AS firma_adi FROM tutanak_hurda h LEFT JOIN tutanak_firmalar f ON h.firma_id=f.id WHERE h.id=?");
$stmt->execute([$id]);
$d = $stmt->fetch();
if(!$d){ echo 'Kayıt bulunamadı'; exit; }

function fmtTarih($t){ return $t ? date('d.m.Y', strtotime($t)) : ''; }
function fmtPara($v){ return $v !== null && $v !== '' ? number_format((float)$v, 2, ',', '.') : ''; }
function h($s){ return htmlspecialchars($s??'', ENT_QUOTES, 'UTF-8'); }

$mudahaleText = [
    'hurdaya_ayirma' => 'Hurdaya Ayırma (Mali Hafıza sökülerek mükellefe verildi ve cihaz çalışmaz hale getirildi.)',
    'gecici_kullanim_disi' => 'Geçici Kullanım Dışı Bırakma (MALİ HAFIZA sökülerek cihaz çalışmaz hale getirildi.)',
    'tekrar_kullanima_alma' => 'Geçici Kullanım Dışı Bırakılan Cihaz Tekrar Kullanıma Alma (takılarak cihaz çalışır hale getirildi.)'
];
$mudahaleLabel = $mudahaleText[$d['mudahale_amaci']] ?? h($d['mudahale_amaci']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>EK-1 Hurda/Geçici Tutanak - Sıra No: <?= h($d['sira_no']) ?></title>
<style>
@page { size: A4; margin: 15mm 15mm 15mm 15mm; }
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #000; background: #fff; }
.yazdir-btn-bar { position: fixed; top: 10px; right: 10px; z-index: 999; display: flex; gap: 8px; }
.yazdir-btn-bar button, .yazdir-btn-bar a { padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
.btn-p { background: #1e3a8a; color: #fff; }
.btn-g { background: #e5e7eb; color: #374151; }
.sayfa { max-width: 210mm; margin: 0 auto; padding: 10mm; }
h1.baslik { text-align: center; font-size: 13pt; font-weight: 700; margin-bottom: 4px; text-transform: uppercase; }
.alt-baslik { text-align: center; font-size: 11pt; margin-bottom: 12px; }
.bolum { margin-bottom: 10px; border: 1px solid #000; }
.bolum-baslik { background: #1e3a8a; color: #fff; padding: 4px 8px; font-weight: 700; font-size: 10.5pt; }
.bolum-icerik { padding: 6px 8px; }
.satir { display: flex; border-bottom: 1px solid #ddd; padding: 3px 0; }
.satir:last-child { border-bottom: none; }
.etiket { min-width: 170px; font-weight: 600; font-size: 10pt; color: #333; flex-shrink: 0; }
.deger { font-size: 10.5pt; flex: 1; }
.grid-2-yazdir { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
.mudahale-kutu { border: 2px solid #dc2626; border-radius: 6px; padding: 10px 14px; font-size: 11pt; font-weight: 700; color: #dc2626; text-align: center; margin: 8px 0; }
.imza-alani { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
.imza-kutu { border: 1px solid #000; padding: 8px; min-height: 80px; }
.imza-baslik { font-weight: 700; font-size: 10pt; border-bottom: 1px solid #000; padding-bottom: 4px; margin-bottom: 6px; }
.imza-isim { font-size: 10pt; }
.sira-no-badge { float: right; background: #1e3a8a; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 11pt; font-weight: 700; }
@media print {
  .yazdir-btn-bar { display: none !important; }
  body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .bolum-baslik { background: #1e3a8a !important; color: #fff !important; }
  .sayfa { padding: 0; }
}
</style>
</head>
<body>
<div class="yazdir-btn-bar">
  <button class="btn-p" onclick="window.print()">Yazdır / PDF Kaydet</button>
  <a href="tutanak_hurda_ekle.php?edit_id=<?= $id ?>" class="btn-g">Düzenle</a>
  <a href="tutanak_hurda_liste.php" class="btn-g">Listeye Dön</a>
</div>

<div class="sayfa">
  <div class="sira-no-badge">Sıra No: <?= h($d['sira_no']) ?></div>
  <h1 class="baslik">EK-1</h1>
  <div class="alt-baslik">Hurdaya Ayrılan ve Geçici Kullanım Dışı Bırakılan Ödeme Kaydedici Cihazlar Tutanağı</div>

  <!-- Tutanak Bilgileri -->
  <div class="bolum">
    <div class="bolum-baslik">Tutanak Bilgileri</div>
    <div class="bolum-icerik grid-2-yazdir">
      <div class="satir"><span class="etiket">Tarih:</span><span class="deger"><?= fmtTarih($d['tarih']) ?></span></div>
    </div>
  </div>

  <!-- Müdahale Amacı -->
  <div class="bolum">
    <div class="bolum-baslik">7 - Müdahalenin Amacı</div>
    <div class="bolum-icerik">
      <div class="mudahale-kutu"><?= $mudahaleLabel ?></div>
    </div>
  </div>

  <!-- Yetkili Servis -->
  <div class="bolum">
    <div class="bolum-baslik">1 - Yetkili Servis Bilgileri</div>
    <div class="bolum-icerik">
      <div class="satir"><span class="etiket">Yetkili Servis Ünvanı:</span><span class="deger"><?= h($d['yetkili_servis_adi']) ?></span></div>
      <div class="satir"><span class="etiket">Adres:</span><span class="deger"><?= h($d['yetkili_servis_adres']) ?></span></div>
      <div class="grid-2-yazdir">
        <div class="satir"><span class="etiket">Yetki Numarası:</span><span class="deger"><?= h($d['yetki_numarasi']) ?></span></div>
        <div class="satir"><span class="etiket">Mühür Numarası:</span><span class="deger"><?= h($d['muhur_numarasi']) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Satıcı / Mükellef -->
  <div class="bolum">
    <div class="bolum-baslik">2 - Cihaz Sahibi Mükellef Bilgileri</div>
    <div class="bolum-icerik">
      <div class="satir"><span class="etiket">Ad / Ünvan:</span><span class="deger"><?= h($d['satici_adi']) ?></span></div>
      <div class="satir"><span class="etiket">Adres:</span><span class="deger"><?= h($d['satici_adres']) ?></span></div>
      <div class="grid-2-yazdir">
        <div class="satir"><span class="etiket">Vergi Dairesi:</span><span class="deger"><?= h($d['satici_vergi_dairesi']) ?></span></div>
        <div class="satir"><span class="etiket">Vergi No:</span><span class="deger"><?= h($d['satici_vergi_no']) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Cihaz Bilgileri -->
  <div class="bolum">
    <div class="bolum-baslik">3-6 - Cihaz Bilgileri</div>
    <div class="bolum-icerik">
      <div class="grid-2-yazdir">
        <div class="satir"><span class="etiket">Marka:</span><span class="deger"><?= h($d['cihaz_marka']) ?></span></div>
        <div class="satir"><span class="etiket">Model:</span><span class="deger"><?= h($d['cihaz_model']) ?></span></div>
        <div class="satir"><span class="etiket">Sicil No:</span><span class="deger"><?= h($d['cihaz_sicil_no']) ?></span></div>
        <div class="satir"><span class="etiket">İlk Z No Tarihi:</span><span class="deger"><?= fmtTarih($d['kullanim_baslangic_tarihi']) ?></span></div>
        <div class="satir"><span class="etiket">Son Z No Tarihi:</span><span class="deger"><?= fmtTarih($d['son_kullanim_tarihi']) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Z Raporu -->
  <div class="bolum">
    <div class="bolum-baslik">Z Raporu Bilgileri</div>
    <div class="bolum-icerik">
      <div class="grid-2-yazdir">
        <div class="satir"><span class="etiket">Z No:</span><span class="deger"><?= h($d['z_raporu_sayisi']) ?></span></div>
        <div class="satir"><span class="etiket">Küm KDV:</span><span class="deger"><?= fmtPara($d['toplam_kdv']) ?> ₺</span></div>
        <div class="satir"><span class="etiket">Küm Top:</span><span class="deger"><?= fmtPara($d['toplam_hasilat']) ?> ₺</span></div>
      </div>
    </div>
  </div>

  <?php if($d['diger_tespitler']): ?>
  <div class="bolum">
    <div class="bolum-baslik">8 - Diğer Tespitler</div>
    <div class="bolum-icerik">
      <div style="font-size:10.5pt;line-height:1.5"><?= nl2br(h($d['diger_tespitler'])) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- İmza Alanları -->
  <div class="imza-alani">
    <div class="imza-kutu">
      <div class="imza-baslik">Yetkili Servis</div>
      <div class="imza-isim"><?= h($d['yetkili_servis_adi']) ?></div>
    </div>
    <div class="imza-kutu">
      <div class="imza-baslik">Mükellef / Satıcı</div>
      <div class="imza-isim"><?= h($d['satici_adi']) ?></div>
    </div>
  </div>

</div><!-- /sayfa -->
</body>
</html>
