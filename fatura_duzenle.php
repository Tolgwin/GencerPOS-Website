<?php
require_once 'db.php';
require_once 'auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: fatura_liste.php'); exit; }

$stmt = $pdo->prepare("SELECT f.*, m.ad_soyad AS musteri_adi FROM faturalar f JOIN musteriler m ON f.musteri_id=m.id WHERE f.id=?");
$stmt->execute([$id]);
$fatura = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fatura) { header('Location: fatura_liste.php'); exit; }

$stmtK = $pdo->prepare("SELECT * FROM fatura_kalemleri WHERE fatura_id=? ORDER BY id ASC");
$stmtK->execute([$id]);
$kalemleri = $stmtK->fetchAll(PDO::FETCH_ASSOC);

// urun_adi: önce tablodaki kayıtlı ad, yoksa urunler'den çek
foreach ($kalemleri as &$k) {
    $k['urun_adi'] = $k['urun_adi'] ?: ($k['urun_adi_tablo'] ?: '');
}
unset($k);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet'])) {
    $musteriId  = (int)($_POST['musteri_id']  ?? 0);
    $tarih      = $_POST['tarih']      ?? date('Y-m-d');
    $vadeTarihi = $_POST['vade_tarihi'] ?? '';
    $notlar     = trim($_POST['notlar'] ?? '');
    $personelId = (int)($_POST['personel_id'] ?? 0);

    $kalemUrunId   = $_POST['kalem_urun_id']  ?? [];
    $kalemAciklama = $_POST['kalem_aciklama'] ?? [];
    $kalemKod      = $_POST['kalem_kod']      ?? [];
    $kalemMiktar   = $_POST['kalem_miktar']   ?? [];
    $kalemFiyat    = $_POST['kalem_fiyat']    ?? [];
    $kalemKdv      = $_POST['kalem_kdv']      ?? [];
    $kalemSeriNo   = $_POST['kalem_seri_no']  ?? [];

    if (!$musteriId)  $errors[] = 'Müşteri seçimi zorunludur.';
    if (!$personelId) $errors[] = 'Personel seçimi zorunludur.';
    if (empty(array_filter(array_map('trim', $kalemAciklama)))) $errors[] = 'En az bir kalem ekleyin.';

    if (empty($errors)) {
        $stmtM = $pdo->prepare("SELECT * FROM musteriler WHERE id=?");
        $stmtM->execute([$musteriId]);
        $musteri = $stmtM->fetch();
        if (!$musteri) $errors[] = 'Müşteri bulunamadı.';
    }

    if (empty($errors)) {
        // Personel değişimi kontrolü: ödendi statüsünde prim varsa personel değiştirilemez
        $eskiPersonelId = (int)($fatura['personel_id'] ?? 0);
        if ($personelId !== $eskiPersonelId && $eskiPersonelId > 0) {
            $stmtOdendi = $pdo->prepare("SELECT COUNT(*) FROM personel_prim WHERE fatura_id=? AND odeme_durumu='odendi'");
            $stmtOdendi->execute([$id]);
            if ($stmtOdendi->fetchColumn() > 0) {
                $errors[] = 'Bu faturaya ait ödenmiş prim kaydı bulunduğundan personel değiştirilemez. Önce prim ödemesini silin.';
            }
        }
    }

    if (empty($errors)) {
        foreach ($kalemAciklama as $i => $aciklama) {
            if (empty(trim($aciklama))) continue;
            $miktar   = (float)($kalemMiktar[$i] ?? 1);
            $fiyat    = (float)($kalemFiyat[$i]  ?? 0);
            $kdvOran  = (float)($kalemKdv[$i]    ?? 20);
            $satirMat = $miktar * $fiyat;
            $satirKdv = $satirMat * $kdvOran / 100;
            $satirlar[] = [
                'urun_id'   => (int)($kalemUrunId[$i] ?? 0),
                'aciklama'  => trim($aciklama),
                'urun_kodu' => trim($kalemKod[$i] ?? ''),
                'miktar'    => $miktar,
                'fiyat'     => $fiyat,
                'kdv_oran'  => $kdvOran,
                'kdv_tutar' => $satirKdv,
                'toplam'    => $satirMat + $satirKdv,
                'seri_no'   => trim($kalemSeriNo[$i] ?? ''),
            ];
            $matrahTop += $satirMat;
            $kdvTop    += $satirKdv;
        }
        $toplamTutar = $matrahTop + $kdvTop;

        try {
            $pdo->beginTransaction();

            // 1) Fatura güncelle
            $pdo->prepare("UPDATE faturalar SET
                musteri_id=:mid, alici_vkn=:vkn, alici_unvan=:unvan,
                tarih=:tarih, vade_tarihi=:vade,
                matrah=:matrah, kdv_tutari=:kdv, toplam=:toplam,
                notlar=:notlar, personel_id=:pid
                WHERE id=:id")->execute([
                ':mid'=>$musteriId, ':vkn'=>$musteri['vergi_no']??'',
                ':unvan'=>$musteri['ad_soyad'], ':tarih'=>$tarih,
                ':vade'=>$vadeTarihi?:null, ':matrah'=>$matrahTop,
                ':kdv'=>$kdvTop, ':toplam'=>$toplamTutar,
                ':notlar'=>$notlar, ':pid'=>$personelId, ':id'=>$id,
            ]);

            // 2) Eski kalemleri sil
            $pdo->prepare("DELETE FROM fatura_kalemleri WHERE fatura_id=?")->execute([$id]);

            // 3) Yeni kalemleri ekle
            $ins = $pdo->prepare("INSERT INTO fatura_kalemleri (fatura_id, urun_adi, urun_kodu, miktar, birim_fiyat, kdv_orani, kdv_tutar, satir_toplam)
                VALUES (:fid,:ad,:kod,:miktar,:fiyat,:kdv,:kdvt,:top)");
            foreach ($satirlar as $s) {
                $ins->execute([
                    ':fid'=>$id,
                    ':ad'=>$s['aciklama'], ':kod'=>$s['urun_kodu'],
                    ':miktar'=>$s['miktar'], ':fiyat'=>$s['fiyat'],
                    ':kdv'=>$s['kdv_oran'], ':kdvt'=>$s['kdv_tutar'],
                    ':top'=>$s['toplam'],
                ]);
            }
            // 4) Otomatik Prim (ürün bazlı — beklemedeki primleri sil ve yeniden hesapla)
            if ($personelId) {
                // Beklemedeki eski primleri temizle
                $pdo->prepare("DELETE FROM personel_prim WHERE fatura_id=? AND odeme_durumu='beklemede'")->execute([$id]);
                // Ürün prim haritası
                $upStmt = $pdo->prepare("SELECT urun_id, prim_orani, prim_sabit_tutar FROM personel_urun_prim WHERE personel_id=?");
                $upStmt->execute([$personelId]);
                $upMap = [];
                foreach ($upStmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $upMap[(int)$row['urun_id']] = $row; }
                $pRow2 = $pdo->prepare("SELECT prim_orani FROM personeller WHERE id=? AND aktif=1");
                $pRow2->execute([$personelId]);
                $genelOran = floatval(($pRow2->fetch() ?: [])['prim_orani'] ?? 0);
                $primInsert = $pdo->prepare("INSERT INTO personel_prim (personel_id,fatura_id,urun_id,urun_adi,fatura_tutari,kalem_tutari,prim_orani,prim_tutari,odeme_durumu,aciklama) VALUES (?,?,?,?,?,?,?,?,'beklemede',?)");
                $primEklendi = false;
                foreach ($satirlar as $s) {
                    $kalemToplam = round(floatval($s['toplam']), 2);
                    $uid = (int)($s['urun_id'] ?? 0);
                    if ($uid && isset($upMap[$uid])) {
                        $up = $upMap[$uid];
                        if ($up['prim_sabit_tutar'] !== null && $up['prim_sabit_tutar'] !== '') {
                            $primT = round(floatval($up['prim_sabit_tutar']) * floatval($s['miktar']), 2);
                            $oranK = 0;
                        } else {
                            $oranK = floatval($up['prim_orani']);
                            $primT = round($kalemToplam * $oranK / 100, 2);
                        }
                        $primInsert->execute([$personelId, $id, $uid, $s['aciklama'], $toplamTutar, $kalemToplam, $oranK, $primT, 'Otomatik: ' . $s['aciklama']]);
                        $primEklendi = true;
                    }
                }
                if (!$primEklendi && $genelOran > 0) {
                    $primT = round($toplamTutar * $genelOran / 100, 2);
                    $primInsert->execute([$personelId, $id, null, null, $toplamTutar, $toplamTutar, $genelOran, $primT, 'Otomatik: ' . ($fatura['fatura_no'] ?? '')]);
                }
            }

            $pdo->commit();
            header('Location: fatura_liste.php?guncellendi=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $fatura['musteri_id']  = $musteriId;
        $fatura['tarih']       = $tarih;
        $fatura['vade_tarihi'] = $vadeTarihi;
        $fatura['notlar']      = $notlar;
        $fatura['personel_id'] = $personelId;
    }
}

// Ürünler listesi
$urunler = [];  // AJAX ile yüklenecek
$kdvOranlari = [0, 1, 8, 10, 20];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Fatura Düzenle — <?= htmlspecialchars($fatura['fatura_no']) ?></title>
<?php include 'menu.php'; ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter','Segoe UI',sans-serif;background:#eef2f9;color:#1a1f36;}

/* ── SAYFA ── */
.fd-wrap{max-width:1060px;margin:0 auto;padding:26px 20px 48px;}

/* ── HEADER ── */
.fd-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.fd-header-sol{display:flex;align-items:center;gap:16px;}
.fd-ikon{width:52px;height:52px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;
    display:flex;align-items:center;justify-content:center;font-size:24px;
    box-shadow:0 4px 16px rgba(245,158,11,.4);flex-shrink:0;}
.fd-title h2{font-size:22px;font-weight:800;color:#1e3a8a;letter-spacing:-.4px;}
.fd-title p{font-size:12px;color:#6b7280;margin-top:3px;font-weight:500;}

/* ── KARTLAR ── */
.fd-kart{background:#fff;border-radius:16px;padding:24px 26px;
    box-shadow:0 2px 16px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.05);margin-bottom:18px;}
.fd-kart-baslik{font-size:11.5px;font-weight:700;color:#1e3a8a;margin-bottom:20px;
    display:flex;align-items:center;gap:10px;text-transform:uppercase;letter-spacing:.6px;
    padding-bottom:13px;border-bottom:2px solid #f0f4ff;}
.fd-kart-ikon{width:30px;height:30px;border-radius:8px;flex-shrink:0;
    background:linear-gradient(135deg,#eff6ff,#dbeafe);
    display:flex;align-items:center;justify-content:center;font-size:15px;}

/* ── GRID FORM ── */
.fd-grid{display:grid;gap:16px;margin-bottom:16px;}
.fd-grid.c2{grid-template-columns:1fr 1fr;}
.fd-grid.c3{grid-template-columns:1fr 1fr 1fr;}
.fg{display:flex;flex-direction:column;gap:6px;}
.fg label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;}
.fg input,.fg select,.fg textarea{
    padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;
    font-size:13px;width:100%;transition:border .2s,box-shadow .2s;
    font-family:inherit;background:#fff;color:#1a1f36;}
.fg input:focus,.fg select:focus,.fg textarea:focus{
    outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);}
.fg input:disabled{background:#f8faff;color:#9ca3af;cursor:not-allowed;}
.fg-zorunlu label::after{content:' *';color:#ef4444;}

/* ── KALEM TABLOSU ── */
.kalem-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;margin:0 -2px;}
.kalem-tablo{width:100%;border-collapse:collapse;font-size:13px;min-width:680px;}
.kalem-tablo thead tr{background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);}
.kalem-tablo th{color:rgba(255,255,255,.92);padding:11px 10px;text-align:left;
    font-weight:600;font-size:10.5px;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;}
.kalem-tablo td{padding:8px 7px;border-bottom:1px solid #f0f4ff;vertical-align:middle;}
.kalem-tablo tbody tr:hover td{background:#f5f8ff;}
.kalem-tablo tbody tr:last-child td{border-bottom:none;}
.kalem-inp{padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;
    font-size:12.5px;width:100%;font-family:inherit;transition:border .15s,box-shadow .15s;background:#fff;}
.kalem-inp:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 2px rgba(59,130,246,.12);}

/* ── ÜRÜN ARAMA DROPDOWN ── */
.urun-td{position:relative;}
.urun-dropdown{
    position:fixed;z-index:9999;background:#fff;
    border:1.5px solid #3b82f6;border-radius:10px;
    box-shadow:0 12px 32px rgba(0,0,0,.18);
    max-height:240px;overflow-y:auto;min-width:280px;
}
.urun-dd-item{
    padding:10px 14px;cursor:pointer;font-size:13px;
    border-bottom:1px solid #f1f5f9;transition:background .1s;
    display:flex;align-items:center;justify-content:space-between;gap:8px;
}
.urun-dd-item:hover{background:#eff6ff;}
.urun-dd-item .ad{font-weight:600;color:#1a1f36;flex:1;}
.urun-dd-item .fiyat{font-weight:700;color:#10b981;white-space:nowrap;font-size:12px;}
.urun-dd-item .stok{font-size:11px;color:#6b7280;white-space:nowrap;}

/* ── TOPLAMLAR ── */
.toplamlar{
    background:linear-gradient(135deg,#f0f9ff,#eff6ff);
    border-radius:12px;padding:18px 22px;margin-top:18px;
    border:1px solid #bfdbfe;display:flex;flex-direction:column;gap:8px;
}
.top-satir{display:flex;align-items:center;justify-content:flex-end;gap:20px;}
.top-satir .lbl{color:#64748b;font-size:13px;font-weight:500;flex:1;text-align:right;}
.top-satir .val{font-weight:700;font-size:14px;color:#1e3a8a;min-width:140px;text-align:right;}
.top-satir.buyuk{border-top:2px solid #bfdbfe;padding-top:10px;margin-top:4px;}
.top-satir.buyuk .lbl{font-weight:800;color:#1e3a8a;}
.top-satir.buyuk .val{font-size:22px;font-weight:800;color:#1e3a8a;}

/* ── BUTONLAR ── */
.btn{display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border:none;
    border-radius:10px;cursor:pointer;font-size:13px;font-weight:700;
    transition:all .2s;font-family:inherit;text-decoration:none;white-space:nowrap;}
.btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;box-shadow:0 3px 12px rgba(59,130,246,.3);}
.btn-primary:hover{background:linear-gradient(135deg,#2563eb,#1d4ed8);transform:translateY(-1px);box-shadow:0 6px 18px rgba(59,130,246,.4);}
.btn-gray{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;}
.btn-gray:hover{background:#e2e8f0;color:#334155;}
.btn-green{background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:8px 18px;font-size:12.5px;
    border-radius:9px;border:none;cursor:pointer;font-family:inherit;font-weight:700;
    display:inline-flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(16,185,129,.3);transition:all .2s;}
.btn-green:hover{transform:translateY(-1px);}
.btn-sil{background:#fef2f2;color:#ef4444;border:1px solid #fecaca;border-radius:7px;
    padding:6px 10px;cursor:pointer;font-size:13px;transition:all .15s;font-family:inherit;line-height:1;}
.btn-sil:hover{background:#ef4444;color:#fff;border-color:#ef4444;}

/* ── HATA / ALERT ── */
.alert-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;
    border-radius:12px;padding:14px 18px;margin-bottom:18px;font-size:13px;
    display:flex;flex-direction:column;gap:4px;}

/* ── MÜŞTERİ ARAMA ── */
.ms-wrap{position:relative;}
.ms-drop{position:absolute;top:calc(100% + 3px);left:0;right:0;background:#fff;
    border:1.5px solid #3b82f6;border-radius:10px;z-index:500;
    max-height:240px;overflow-y:auto;box-shadow:0 12px 30px rgba(0,0,0,.14);}
.ms-item{padding:11px 14px;cursor:pointer;font-size:13px;
    border-bottom:1px solid #f1f5f9;transition:background .1s;}
.ms-item:hover{background:#eff6ff;color:#1d4ed8;}
.ms-badge{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;
    padding:10px 14px;font-size:12.5px;color:#065f46;margin-top:8px;display:none;}
.ms-badge.show{display:block;}

/* ── PİRİM BİLGİ BANDI ── */
.prim-band{background:#fffbeb;border:1px solid #fde68a;border-radius:9px;
    padding:10px 14px;font-size:12.5px;color:#92400e;margin-top:8px;display:none;}
.prim-band.show{display:flex;align-items:center;gap:8px;}

/* ── KAYDET BAR ── */
.kaydet-bar{display:flex;justify-content:flex-end;gap:12px;
    padding:20px 0 8px;border-top:2px solid #e2e8f0;margin-top:4px;}

/* ── RESPONSIVE ── */
@media(max-width:900px){.fd-wrap{padding:18px 14px 36px;}.fd-kart{padding:20px;}}
@media(max-width:768px){
    .fd-grid.c2,.fd-grid.c3{grid-template-columns:1fr;}
    .fd-header{flex-direction:column;align-items:flex-start;}
    .top-satir .val{min-width:110px;}
    .top-satir.buyuk .val{font-size:18px;}
    .kaydet-bar .btn{flex:1;justify-content:center;}
}
@media(max-width:480px){
    .fd-wrap{padding:12px 10px 28px;}
    .fd-ikon{width:44px;height:44px;font-size:20px;}
    .fd-title h2{font-size:18px;}
    .fd-kart{padding:15px 13px;margin-bottom:14px;}
    .top-satir.buyuk .val{font-size:15px;}
}
</style>
</head>
<body>
<div class="sayfa-icerik">
<div class="fd-wrap">

  <!-- HEADER -->
  <div class="fd-header">
    <div class="fd-header-sol">
      <div class="fd-ikon">✏️</div>
      <div class="fd-title">
        <h2>Fatura Düzenle</h2>
        <p>No: <strong><?= htmlspecialchars($fatura['fatura_no']) ?></strong> &nbsp;·&nbsp; Değişiklikleri kaydetmek için formu doldurun</p>
      </div>
    </div>
    <a href="fatura_liste.php" class="btn btn-gray">← Listeye Dön</a>
  </div>

  <!-- HATALAR -->
  <?php if (!empty($errors)): ?>
  <div class="alert-err">
    <?php foreach ($errors as $e): ?><div>⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST">
  <input type="hidden" name="kaydet" value="1">

  <!-- MÜŞTERİ KARTI -->
  <div class="fd-kart">
    <div class="fd-kart-baslik"><div class="fd-kart-ikon">👤</div>Müşteri Bilgileri</div>
    <input type="hidden" name="musteri_id" id="musteriId" value="<?= (int)$fatura['musteri_id'] ?>">
    <div class="ms-wrap">
      <div class="fg fg-zorunlu">
        <label>Müşteri</label>
        <input type="text" id="musteriAra" autocomplete="off"
               placeholder="Ad veya vergi numarası ile ara..."
               value="<?= htmlspecialchars($fatura['musteri_adi'] ?? $fatura['alici_unvan'] ?? '') ?>">
        <div class="ms-drop" id="msDrop" style="display:none;"></div>
      </div>
    </div>
    <div class="ms-badge" id="msBadge"></div>
  </div>

  <!-- FATURA BİLGİLERİ KARTI -->
  <div class="fd-kart">
    <div class="fd-kart-baslik"><div class="fd-kart-ikon">📋</div>Fatura Bilgileri</div>
    <div class="fd-grid c2">
      <div class="fg">
        <label>Fatura No</label>
        <input type="text" value="<?= htmlspecialchars($fatura['fatura_no']) ?>" disabled>
      </div>
      <div class="fg fg-zorunlu">
        <label>Fatura Tarihi</label>
        <input type="date" name="tarih" value="<?= htmlspecialchars($fatura['tarih']) ?>" required>
      </div>
    </div>
    <div class="fd-grid c2">
      <div class="fg">
        <label>Vade Tarihi</label>
        <input type="date" name="vade_tarihi" value="<?= htmlspecialchars($fatura['vade_tarihi'] ?? '') ?>">
      </div>
      <div class="fg fg-zorunlu">
        <label>Personel (Satış Yetkilisi)</label>
        <select name="personel_id" id="personelSec" required>
          <option value="">— Personel Seçin (Zorunlu) —</option>
        </select>
        <div class="prim-band" id="primBand">💰 <span id="primBandText"></span></div>
      </div>
    </div>
    <div class="fd-grid">
      <div class="fg">
        <label>Notlar</label>
        <textarea name="notlar" rows="2" placeholder="Opsiyonel not..."><?= htmlspecialchars($fatura['notlar'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <!-- KALEMLER KARTI -->
  <div class="fd-kart">
    <div class="fd-kart-baslik">
      <div class="fd-kart-ikon">📦</div>
      Fatura Kalemleri
      <button type="button" class="btn-green" style="margin-left:auto;" onclick="kalemEkle()">+ Kalem Ekle</button>
    </div>
    <div class="kalem-scroll">
      <table class="kalem-tablo">
        <thead>
          <tr>
            <th style="min-width:220px;">Ürün / Açıklama</th>
            <th style="width:95px;">Ürün Kodu</th>
            <th style="width:80px;">Miktar</th>
            <th style="width:130px;">Birim Fiyat (₺)</th>
            <th style="width:85px;">KDV %</th>
            <th style="width:130px;">Satır Toplamı</th>
            <th style="width:44px;"></th>
          </tr>
        </thead>
        <tbody id="kalemBody"></tbody>
      </table>
    </div>

    <!-- TOPLAMLAR -->
    <div class="toplamlar">
      <div class="top-satir"><span class="lbl">Matrah (KDV Hariç):</span><span class="val" id="matrahG">₺0,00</span></div>
      <div class="top-satir"><span class="lbl">Toplam KDV:</span><span class="val" id="kdvG">₺0,00</span></div>
      <div class="top-satir buyuk"><span class="lbl">GENEL TOPLAM:</span><span class="val" id="toplamG">₺0,00</span></div>
    </div>
  </div>

  <!-- KAYDET BAR -->
  <div class="kaydet-bar">
    <a href="fatura_liste.php" class="btn btn-gray">✕ İptal</a>
    <button type="submit" class="btn btn-primary">💾 Faturayı Güncelle</button>
  </div>
  </form>

</div><!-- /fd-wrap -->
</div><!-- /sayfa-icerik -->

<script>
const KDV_ORANLARI    = <?= json_encode($kdvOranlari) ?>;
let URUNLER = []; fetch('urun_kontrol.php?action=liste_json').then(r=>r.json()).then(d=>{ if(d.basari) URUNLER = d.urunler ?? []; });
const MEVCUT_KALEMLER = <?= json_encode($kalemleri, JSON_UNESCAPED_UNICODE) ?>;
const MEVCUT_PERSONEL = <?= (int)($fatura['personel_id'] ?? 0) ?>;

const TRY = n => new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(n||0);
const esc = s => { const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; };

/* ═══════════════════════════════════════════
   KALEM YÖNETIMI
═══════════════════════════════════════════ */
let idx = 0;

function kalemEkle(v = {}) {
    const tbody = document.getElementById('kalemBody');
    const i = idx++;

    const kdvOpts = KDV_ORANLARI.map(k =>
        `<option value="${k}" ${k == (v.kdv_orani ?? 20) ? 'selected' : ''}>${k}%</option>`
    ).join('');

    const tr = document.createElement('tr');
    tr.id = 'kr' + i;
    tr.innerHTML = `
      <td class="urun-td">
        <input type="hidden"  name="kalem_urun_id[]"  id="uid${i}" value="${+(v.urun_id||0)}">
        <input class="kalem-inp" type="text" name="kalem_aciklama[]" id="ac${i}"
               placeholder="Ürün adı veya kodu yazın..."
               value="${esc(v.urun_adi||'')}" autocomplete="off"
               oninput="urunAra(${i},this)"
               onblur="setTimeout(()=>ddKapat(${i}),180)">
        <div id="dd${i}" class="urun-dropdown" style="display:none;"></div>
      </td>
      <td><input class="kalem-inp" type="text"   name="kalem_kod[]"    id="kod${i}"  value="${esc(v.urun_kodu||'')}"  placeholder="—"></td>
      <td><input class="kalem-inp" type="number" name="kalem_miktar[]" id="mik${i}"  value="${+(v.miktar||1)}"        min="0.01" step="0.01" oninput="hesapla()"></td>
      <td><input class="kalem-inp" type="number" name="kalem_fiyat[]"  id="fiy${i}"  value="${+(v.birim_fiyat||0)}"   min="0"    step="0.01" oninput="hesapla()"></td>
      <td>
        <select class="kalem-inp" name="kalem_kdv[]" id="kdv${i}" onchange="hesapla()">${kdvOpts}</select>
        <input type="hidden" name="kalem_seri_no[]" value="${esc(v.seri_no||'')}">
      </td>
      <td><strong id="top${i}" style="font-size:13px;color:#1e3a8a;">₺0,00</strong></td>
      <td><button type="button" class="btn-sil" onclick="kalemSil('kr${i}')">✕</button></td>
    `;
    tbody.appendChild(tr);
    hesapla();
}

function kalemSil(id) { const el=document.getElementById(id); if(el){el.remove();hesapla();} }

function hesapla() {
    let mat=0, kdv=0;
    document.querySelectorAll('#kalemBody tr').forEach(tr => {
        const i = tr.id.slice(2);
        const m = parseFloat(document.getElementById('mik'+i)?.value||0);
        const f = parseFloat(document.getElementById('fiy'+i)?.value||0);
        const k = parseFloat(document.getElementById('kdv'+i)?.value||0);
        const s = m*f, sk = s*k/100;
        mat+=s; kdv+=sk;
        const el=document.getElementById('top'+i); if(el) el.textContent=TRY(s+sk);
    });
    document.getElementById('matrahG').textContent = TRY(mat);
    document.getElementById('kdvG').textContent    = TRY(kdv);
    document.getElementById('toplamG').textContent = TRY(mat+kdv);
}

/* ═══════════════════════════════════════════
   ÜRÜN ARAMA — position:fixed dropdown
═══════════════════════════════════════════ */
function urunAra(i, inp) {
    const q = inp.value.trim().toLowerCase();
    const dd = document.getElementById('dd'+i);
    if (!q) { dd.style.display='none'; return; }

    const hits = URUNLER.filter(u =>
        u.urun_adi.toLowerCase().includes(q) ||
        (u.urun_kodu||'').toLowerCase().includes(q)
    ).slice(0,10);

    if (!hits.length) { dd.style.display='none'; return; }

    // position:fixed — input'un ekran koordinatlarına göre konumlandır
    const r = inp.getBoundingClientRect();
    dd.style.top    = (r.bottom + 2) + 'px';
    dd.style.left   = r.left     + 'px';
    dd.style.width  = Math.max(r.width, 300) + 'px';
    dd.style.display = 'block';

    dd.innerHTML = hits.map(u => `
      <div class="urun-dd-item"
           onmousedown="urunSec(${i},${u.id},'${esc(u.urun_adi)}',${u.satis_fiyati},${u.kdv_orani},'${esc(u.urun_kodu||'')}')">
        <span class="ad">${esc(u.urun_adi)}</span>
        ${u.urun_kodu?`<small style="color:#6b7280;">${esc(u.urun_kodu)}</small>`:''}
        <span class="fiyat">${TRY(u.satis_fiyati)}</span>
        <span class="stok">Stok: ${u.stok_adeti??0}</span>
      </div>`).join('');
}

function urunSec(i, uid, ad, fiyat, kdv, kod) {
    document.getElementById('uid'+i).value  = uid;
    document.getElementById('ac'+i).value   = ad;
    document.getElementById('fiy'+i).value  = fiyat;
    document.getElementById('kod'+i).value  = kod;
    const sel = document.getElementById('kdv'+i);
    for (const o of sel.options) if (o.value==kdv) { o.selected=true; break; }
    ddKapat(i);
    hesapla();
}

function ddKapat(i) { const d=document.getElementById('dd'+i); if(d) d.style.display='none'; }

// Scroll/resize'da açık dropdown'ları güncelle
document.addEventListener('scroll', () => {
    document.querySelectorAll('.urun-dropdown[style*="block"]').forEach(dd => {
        const i = dd.id.replace('dd','');
        const inp = document.getElementById('ac'+i);
        if (inp) {
            const r = inp.getBoundingClientRect();
            dd.style.top    = (r.bottom + 2) + 'px';
            dd.style.left   = r.left + 'px';
        }
    });
}, {passive:true});

/* ═══════════════════════════════════════════
   MÜŞTERİ ARAMA
═══════════════════════════════════════════ */
let msTimer;
document.getElementById('musteriAra').addEventListener('input', function() {
    clearTimeout(msTimer);
    const q = this.value.trim();
    const drop = document.getElementById('msDrop');
    if (q.length < 2) { drop.style.display='none'; return; }
    msTimer = setTimeout(() => {
        fetch('musteri_kontrol.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action:'liste', ad_soyad:q, limit:8}).toString()
        }).then(r=>r.json()).then(d => {
            const list = d.musteriler||[];
            if (!list.length) { drop.style.display='none'; return; }
            drop.style.display='block';
            drop.innerHTML = list.map(m=>`
              <div class="ms-item" onmousedown="musteriSec(${m.id},'${esc(m.ad_soyad)}','${esc(m.vergi_no||'')}','${esc(m.vergi_dairesi||'')}')">
                <strong>${esc(m.ad_soyad)}</strong>
                ${m.vergi_no?`<small style="color:#6b7280;margin-left:6px;">${esc(m.vergi_no)}</small>`:''}
              </div>`).join('');
        });
    }, 250);
});

function musteriSec(id, ad, vkn, vd) {
    document.getElementById('musteriId').value = id;
    document.getElementById('musteriAra').value = ad;
    document.getElementById('msDrop').style.display='none';
    const b = document.getElementById('msBadge');
    if (vkn||vd) {
        b.innerHTML=(vkn?'<strong>VKN:</strong> '+vkn:'')+(vd?'&nbsp;·&nbsp;<strong>Vergi D.:</strong> '+vd:'');
        b.classList.add('show');
    } else b.classList.remove('show');
}

/* ═══════════════════════════════════════════
   PERSONEL LİSTESİ
═══════════════════════════════════════════ */
fetch('personel_kontrol.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=listele'})
.then(r=>r.json()).then(d=>{
    const sel = document.getElementById('personelSec');
    (d.personeller||[]).filter(p=>p.aktif==1).forEach(p=>{
        const o = document.createElement('option');
        o.value = p.id;
        o.dataset.prim = p.prim_orani||0;
        const pLabel = parseFloat(p.prim_orani||0)>0 ? ` — %${p.prim_orani} prim` : '';
        o.textContent = p.ad_soyad + (p.gorev?` (${p.gorev})`:'') + pLabel;
        if (p.id == MEVCUT_PERSONEL) o.selected = true;
        sel.appendChild(o);
    });
    guncelPrimBand();
});

document.getElementById('personelSec').addEventListener('change', guncelPrimBand);

function guncelPrimBand() {
    const sel = document.getElementById('personelSec');
    const opt = sel.options[sel.selectedIndex];
    const band = document.getElementById('primBand');
    const txt  = document.getElementById('primBandText');
    const prim = parseFloat(opt?.dataset?.prim||0);
    if (sel.value && prim > 0) {
        txt.textContent = `Bu personelin prim oranı: %${prim} — Fatura kaydedilince otomatik prim oluşturulacak`;
        band.classList.add('show');
    } else {
        band.classList.remove('show');
    }
}

/* ═══════════════════════════════════════════
   İLK YÜKLEME
═══════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    if (MEVCUT_KALEMLER.length) {
        MEVCUT_KALEMLER.forEach(k => kalemEkle(k));
    } else {
        kalemEkle();
    }
});
</script>
</body>
</html>
