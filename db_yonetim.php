<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('db');

header('X-Frame-Options: SAMEORIGIN');

function jsonCevap($ok, $msg, $data = []) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['basari' => $ok, 'mesaj' => $msg] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'yedekle') {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sql  = "-- FaturaApp DB Yedeği | " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Veritabanı: $dbName\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $tbl) {
        $create = $pdo->query("SHOW CREATE TABLE `$tbl`")->fetch(PDO::FETCH_NUM);
        $sql .= "DROP TABLE IF EXISTS `$tbl`;\n{$create[1]};\n\n";
        $rows = $pdo->query("SELECT * FROM `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
            $sql .= "INSERT INTO `$tbl` ($cols) VALUES\n";
            $vals = [];
            foreach ($rows as $row) {
                $esc = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($row));
                $vals[] = '(' . implode(',', $esc) . ')';
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $dir = __DIR__ . '/yedekler/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dosya = 'faturaapp_' . date('Ymd_His') . '.sql';
    file_put_contents($dir . $dosya, $sql);
    jsonCevap(true, 'Yedek alındı.', ['dosya' => $dosya, 'boyut' => number_format(strlen($sql)/1024,1).' KB']);
}

if ($action === 'yedek_indir') {
    $dosya = basename($_POST['dosya'] ?? '');
    $yol = __DIR__ . '/yedekler/' . $dosya;
    if (!$dosya || !file_exists($yol)) jsonCevap(false, 'Dosya bulunamadı.');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $dosya . '"');
    header('Content-Length: ' . filesize($yol));
    readfile($yol); exit;
}

if ($action === 'yedek_sil') {
    $dosya = basename($_POST['dosya'] ?? '');
    $yol = __DIR__ . '/yedekler/' . $dosya;
    if ($dosya && file_exists($yol)) { unlink($yol); jsonCevap(true, 'Silindi.'); }
    jsonCevap(false, 'Bulunamadı.');
}

if ($action === 'optimize') {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sonuc = [];
    foreach ($tables as $t) { $r = $pdo->query("OPTIMIZE TABLE `$t`")->fetch(PDO::FETCH_ASSOC); $sonuc[$t] = $r['Msg_text'] ?? 'OK'; }
    jsonCevap(true, count($tables).' tablo optimize edildi.', ['sonuclar' => $sonuc]);
}

if ($action === 'tamir') {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sonuc = [];
    foreach ($tables as $t) {
        try { $r = $pdo->query("REPAIR TABLE `$t`")->fetch(PDO::FETCH_ASSOC); $sonuc[$t] = $r['Msg_text'] ?? 'OK'; }
        catch (Exception $e) { $sonuc[$t] = 'InnoDB (otomatik)'; }
    }
    jsonCevap(true, 'Tamamlandı.', ['sonuclar' => $sonuc]);
}

if ($action === 'tablo_temizle') {
    $tablo = $_POST['tablo'] ?? '';
    $izinli = ['efaturalar','hesap_transferler','stok_hareketler','urun_hareketleri','cariler'];
    if (!in_array($tablo, $izinli)) jsonCevap(false, 'İzin verilmeyen tablo.');
    $pdo->exec("TRUNCATE TABLE `$tablo`");
    jsonCevap(true, "`$tablo` temizlendi.");
}

if ($action === 'eski_kayit_sil') {
    $tip  = $_POST['tip'] ?? '';
    $gun  = max(7, (int)($_POST['gun'] ?? 30));
    $tarih = date('Y-m-d', strtotime("-{$gun} days"));
    if ($tip === 'taslak_faturalar') {
        $stmt = $pdo->prepare("DELETE FROM faturalar WHERE durum='taslak' AND tarih < ? AND id NOT IN (SELECT DISTINCT fatura_id FROM tahsilatlar WHERE fatura_id IS NOT NULL)");
        $stmt->execute([$tarih]); jsonCevap(true, $stmt->rowCount().' taslak fatura silindi.');
    }
    if ($tip === 'temp_dosyalar') {
        $dir = __DIR__ . '/yedekler/'; $sayac = 0;
        foreach (glob($dir . '*.sql') as $f) { if (filemtime($f) < strtotime("-{$gun} days")) { unlink($f); $sayac++; } }
        jsonCevap(true, "$sayac eski yedek dosyası silindi.");
    }
    jsonCevap(false, 'Bilinmeyen tip.');
}

if ($action === 'istatistik') {
    $tables = $pdo->query("SELECT TABLE_NAME as tablo, IFNULL(TABLE_ROWS,0) as satirlar, ROUND((DATA_LENGTH+INDEX_LENGTH)/1024,1) as boyut_kb FROM information_schema.tables WHERE TABLE_SCHEMA=DATABASE() ORDER BY (DATA_LENGTH+INDEX_LENGTH) DESC")->fetchAll(PDO::FETCH_ASSOC);
    $toplamKb = array_sum(array_column($tables,'boyut_kb'));
    $ozet = [
        'fatura'   => $pdo->query("SELECT COUNT(*) FROM faturalar")->fetchColumn(),
        'musteri'  => $pdo->query("SELECT COUNT(*) FROM musteriler")->fetchColumn(),
        'tahsilat' => $pdo->query("SELECT COUNT(*) FROM tahsilatlar")->fetchColumn(),
        'personel' => $pdo->query("SELECT COUNT(*) FROM personeller")->fetchColumn(),
        'urun'     => $pdo->query("SELECT COUNT(*) FROM urunler")->fetchColumn(),
        'servis'   => $pdo->query("SELECT COUNT(*) FROM servisler")->fetchColumn(),
    ];
    $dir = __DIR__ . '/yedekler/'; $yedekler = [];
    if (is_dir($dir)) {
        foreach (array_reverse(glob($dir . '*.sql')) as $f) {
            $yedekler[] = ['dosya'=>basename($f),'boyut'=>round(filesize($f)/1024,1).' KB','tarih'=>date('d.m.Y H:i',filemtime($f))];
        }
    }
    // DB sunucu bilgisi
    $dbInfo = [
        'versiyon' => $pdo->query("SELECT VERSION()")->fetchColumn(),
        'charset'  => $pdo->query("SELECT @@character_set_database")->fetchColumn(),
        'engine'   => 'InnoDB',
        'db_adi'   => $pdo->query("SELECT DATABASE()")->fetchColumn(),
    ];
    jsonCevap(true, '', ['tablolar'=>$tables,'toplam_kb'=>$toplamKb,'ozet'=>$ozet,'yedekler'=>$yedekler,'db_info'=>$dbInfo]);
}

if ($action === 'oturum_kapat') { unset($_SESSION['db_admin_ok']); jsonCevap(true,'OK'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>🗄️ Veritabanı Yönetim</title>
<link rel="stylesheet" href="style.css">
<style>
:root{--blue:#1e3a8a;--blue2:#3b82f6;--green:#10b981;--red:#ef4444;--orange:#f59e0b;--gray:#6b7280;}
body{background:#f0f4ff;font-family:'Segoe UI',sans-serif;}
.sayfa{max-width:1100px;margin:0 auto;padding:24px 16px;}
h1{font-size:22px;font-weight:800;color:var(--blue);margin-bottom:4px;display:flex;align-items:center;gap:8px;}
.sub{color:var(--gray);font-size:13px;margin-bottom:24px;}
.ozet-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:22px;}
.ozet-kart{background:#fff;border-radius:12px;padding:16px 12px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.07);}
.ozet-kart .ikon{font-size:26px;margin-bottom:6px;}
.ozet-kart .sayi{font-size:22px;font-weight:800;color:var(--blue);}
.ozet-kart .etiket{font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase;letter-spacing:.3px;}
.panel{background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;}
.panel-baslik{padding:14px 20px;border-bottom:2px solid #f1f5f9;font-weight:800;font-size:15px;color:var(--blue);display:flex;align-items:center;gap:8px;justify-content:space-between;}
.panel-icerik{padding:20px;}
.btn{padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:700;transition:.15s;display:inline-flex;align-items:center;gap:6px;}
.btn:disabled{opacity:.5;cursor:not-allowed;}
.btn-blue{background:var(--blue);color:#fff;}.btn-blue:not(:disabled):hover{background:#2563eb;}
.btn-green{background:var(--green);color:#fff;}.btn-green:not(:disabled):hover{background:#059669;}
.btn-orange{background:var(--orange);color:#fff;}.btn-orange:not(:disabled):hover{background:#d97706;}
.btn-red{background:var(--red);color:#fff;}.btn-red:not(:disabled):hover{background:#dc2626;}
.btn-gray{background:#f3f4f6;color:#374151;border:1px solid #e2e8f0;}.btn-gray:hover{background:#e5e7eb;}
.btn-sm{padding:6px 12px;font-size:12px;}
.islem-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;}
.islem-kart{border:1.5px solid #e5e7eb;border-radius:12px;padding:18px 16px;transition:.15s;}
.islem-kart:hover{border-color:var(--blue2);box-shadow:0 2px 12px rgba(59,130,246,.1);}
.islem-kart h4{font-size:14px;font-weight:700;color:#1f2937;margin:0 0 6px;display:flex;align-items:center;gap:6px;}
.islem-kart p{font-size:12px;color:var(--gray);margin:0 0 14px;line-height:1.6;}
.vt-tablo{width:100%;border-collapse:collapse;font-size:13px;}
.vt-tablo th{background:#f8faff;padding:9px 14px;text-align:left;font-weight:700;color:#374151;border-bottom:2px solid #e5e7eb;}
.vt-tablo td{padding:8px 14px;border-bottom:1px solid #f3f4f6;vertical-align:middle;}
.vt-tablo tr:last-child td{border-bottom:none;}
.vt-tablo tbody tr:hover td{background:#f8faff;}
.bar{height:6px;background:#e5e7eb;border-radius:3px;min-width:80px;}
.bar-fill{height:100%;border-radius:3px;transition:.4s;}
.yedek-item{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#f8faff;border-radius:8px;margin-bottom:8px;border:1px solid #e5e7eb;gap:12px;}
.yedek-ad{font-size:12px;font-weight:600;color:#1e3a8a;font-family:monospace;word-break:break-all;}
.yedek-meta{font-size:11px;color:var(--gray);margin-top:2px;}
.temizle-kart{background:#fff7ed;border:1.5px solid #fed7aa;border-radius:10px;padding:16px;margin-bottom:10px;}
.temizle-kart.danger{background:#fff5f5;border-color:#fca5a5;}
.temizle-kart h5{color:#92400e;font-size:13px;font-weight:700;margin:0 0 4px;}
.temizle-kart.danger h5{color:#991b1b;}
.temizle-kart p{font-size:12px;color:#78350f;margin:0 0 10px;line-height:1.5;}
.temizle-kart.danger p{color:#7f1d1d;}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:14px;display:flex;align-items:flex-start;gap:8px;}
.alert-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a;}
#logKutu{background:#1e293b;color:#94a3b8;font-family:monospace;font-size:12px;padding:14px;border-radius:8px;max-height:220px;overflow-y:auto;margin-top:14px;display:none;line-height:1.6;}
#logKutu .ok{color:#4ade80;}#logKutu .err{color:#f87171;}#logKutu .info{color:#60a5fa;}
.db-info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;}
.db-info-kart{background:#f8faff;border-radius:8px;padding:12px 14px;}
.db-info-kart .label{font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px;}
.db-info-kart .val{font-size:14px;font-weight:700;color:#1e3a8a;font-family:monospace;}
.spin{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;margin-right:4px;}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<?php require_once 'menu.php'; ?>
<div class="sayfa">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:6px;">
    <h1>🗄️ Veritabanı Yönetim</h1>
    <button class="btn btn-gray btn-sm" onclick="oturumKapat()">🔒 Güvenli Çıkış</button>
  </div>
  <div class="sub">Yedekleme · Optimizasyon · Temizlik · Bakım</div>

  <!-- ÖZET -->
  <div class="ozet-grid" id="ozetGrid">
    <?php foreach(['📄'=>'...','👤'=>'...','💰'=>'...','👥'=>'...','📦'=>'...','🔧'=>'...'] as $ikon=>$val): ?>
    <div class="ozet-kart"><div class="ikon"><?=$ikon?></div><div class="sayi"><?=$val?></div><div class="etiket">Yükleniyor</div></div>
    <?php endforeach; ?>
  </div>

  <!-- HIZLI İŞLEMLER -->
  <div class="panel">
    <div class="panel-baslik">⚡ Hızlı İşlemler</div>
    <div class="panel-icerik">
      <div class="islem-grid">
        <div class="islem-kart">
          <h4>💾 Yedek Al</h4>
          <p>Tüm tablo yapıları ve verileri SQL formatında yedeklenir. Sunucuya kaydedilir, daha sonra indirebilirsiniz.</p>
          <button class="btn btn-blue" id="yedekBtn" onclick="yedekAl(this)">💾 Yedeği Al</button>
        </div>
        <div class="islem-kart">
          <h4>🔧 Optimize Et</h4>
          <p>Silinmiş kayıtların bıraktığı boşlukları giderir. Sorgu hızını artırır. Güvenli işlem.</p>
          <button class="btn btn-green" onclick="optimizeEt(this)">🔧 Optimize Et</button>
        </div>
        <div class="islem-kart">
          <h4>🩺 Tabloları Onar</h4>
          <p>Bozulmuş tablo yapılarını onarır. InnoDB tabloları zaten otomatik kurtarma yapar.</p>
          <button class="btn btn-orange" onclick="tamirEt(this)">🩺 Onar</button>
        </div>
        <div class="islem-kart">
          <h4>🔄 Yenile</h4>
          <p>Tablo istatistiklerini, yedek listesini ve özet sayıları güncel bilgilerle yeniden yükler.</p>
          <button class="btn btn-gray" onclick="istatistikYukle()">🔄 Yenile</button>
        </div>
      </div>
      <div id="logKutu"></div>
    </div>
  </div>

  <!-- TABLO İSTATİSTİKLERİ -->
  <div class="panel">
    <div class="panel-baslik">
      <span>📊 Tablo İstatistikleri</span>
      <span id="toplamBoyut" style="font-size:12px;color:var(--gray);font-weight:600;"></span>
    </div>
    <div class="panel-icerik" style="padding:0;overflow-x:auto;">
      <table class="vt-tablo">
        <thead><tr><th>#</th><th>Tablo</th><th>Kayıt</th><th>Boyut (KB)</th><th style="min-width:120px;">Doluluk</th></tr></thead>
        <tbody id="tabloBody"><tr><td colspan="5" style="text-align:center;padding:30px;color:#9ca3af;">⏳ Yükleniyor...</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- YEDEKLER -->
  <div class="panel">
    <div class="panel-baslik">
      <span>📁 Alınan Yedekler</span>
      <button class="btn btn-blue btn-sm" onclick="yedekAl(this)">💾 Yeni Yedek</button>
    </div>
    <div class="panel-icerik" id="yedekListesi">
      <div style="text-align:center;padding:20px;color:#9ca3af;font-size:13px;">⏳ Yükleniyor...</div>
    </div>
  </div>

  <!-- TEMİZLİK -->
  <div class="panel">
    <div class="panel-baslik">🧹 Veri Temizleme</div>
    <div class="panel-icerik">
      <div class="alert alert-warn">⚠️ <span>Silme işlemleri <strong>geri alınamaz</strong>! İşlem yapmadan önce mutlaka yedek alın.</span></div>

      <div class="temizle-kart">
        <h5>🗂️ Taslak Faturalar</h5>
        <p>Tahsilatı olmayan ve belirtilen süreden eski taslak faturalar silinir.</p>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <select id="taslakGun" style="padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;background:#fff;">
            <option value="30">30 günden eski</option>
            <option value="60">60 günden eski</option>
            <option value="90" selected>90 günden eski</option>
            <option value="180">180 günden eski</option>
          </select>
          <button class="btn btn-red btn-sm" onclick="eskiKayitSil('taslak_faturalar','taslakGun')">🗑️ Sil</button>
        </div>
      </div>

      <div class="temizle-kart">
        <h5>💾 Eski Yedek Dosyaları</h5>
        <p>Sunucudaki eski SQL yedek dosyalarını siler. Önce indirip saklamanızı öneririz.</p>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <select id="yedekGun" style="padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;background:#fff;">
            <option value="7">7 günden eski</option>
            <option value="30" selected>30 günden eski</option>
            <option value="60">60 günden eski</option>
          </select>
          <button class="btn btn-red btn-sm" onclick="eskiKayitSil('temp_dosyalar','yedekGun')">🗑️ Sil</button>
        </div>
      </div>

      <div class="temizle-kart danger">
        <h5>⚠️ Tablo Temizle (TRUNCATE)</h5>
        <p>Seçilen tablodaki <strong>tüm kayıtlar</strong> kalıcı olarak silinir. Yalnızca boş/gerekisiz tablolarda uygulayın.</p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php
          $gt = ['efaturalar'=>'e-Fatura Taslaklar','hesap_transferler'=>'Hesap Transferleri','stok_hareketler'=>'Stok Hareketleri','urun_hareketleri'=>'Ürün Hareketleri','cariler'=>'Cari Kayıtlar'];
          foreach ($gt as $t=>$l): ?>
          <button class="btn btn-red btn-sm" onclick="tabloTemizle('<?=$t?>','<?=$l?>')">🗑️ <?=$l?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- DB BİLGİ -->
  <div class="panel">
    <div class="panel-baslik">ℹ️ Sunucu Bilgileri</div>
    <div class="panel-icerik">
      <div class="db-info-grid" id="dbBilgi">
        <div style="color:#9ca3af;font-size:13px;">⏳ Yükleniyor...</div>
      </div>
    </div>
  </div>

  <div style="text-align:center;margin-top:8px;">
    <a href="index.php" class="btn btn-gray btn-sm">← Ana Sayfa</a>
  </div>
</div>

<script>
'use strict';

async function api(params) {
    const r = await fetch('db_yonetim.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(params).toString()});
    return r.json();
}

function log(msg,tip='info'){
    const box=document.getElementById('logKutu');
    box.style.display='block';
    const d=document.createElement('div');
    d.className=tip;
    d.textContent='['+new Date().toLocaleTimeString('tr-TR')+'] '+msg;
    box.appendChild(d); box.scrollTop=box.scrollHeight;
}

function setBtnLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
        btn._origHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spin"></span> İşleniyor...';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn._origHTML || btn.innerHTML;
    }
}

async function istatistikYukle() {
    const v = await api({action:'istatistik'});
    if (!v.basari) return;

    // Özet
    const ikonMap={fatura:'📄',musteri:'👤',tahsilat:'💰',personel:'👥',urun:'📦',servis:'🔧'};
    const lblMap={fatura:'Fatura',musteri:'Müşteri',tahsilat:'Tahsilat',personel:'Personel',urun:'Ürün',servis:'Servis'};
    document.getElementById('ozetGrid').innerHTML = Object.entries(v.ozet).map(([k,val])=>
        `<div class="ozet-kart"><div class="ikon">${ikonMap[k]}</div><div class="sayi">${val}</div><div class="etiket">${lblMap[k]}</div></div>`
    ).join('');

    // Tablo tablosu
    const maxKb = Math.max(...v.tablolar.map(t=>parseFloat(t.boyut_kb||0)),1);
    document.getElementById('toplamBoyut').textContent = 'Toplam: '+parseFloat(v.toplam_kb).toLocaleString('tr-TR',{minimumFractionDigits:1})+' KB · '+v.tablolar.length+' tablo';
    document.getElementById('tabloBody').innerHTML = v.tablolar.map((t,i)=>{
        const kb = parseFloat(t.boyut_kb||0);
        const pct = Math.max(3, (kb/maxKb*100)).toFixed(0);
        const renk = pct>70?'#ef4444':pct>40?'#f59e0b':'#3b82f6';
        return `<tr>
            <td style="color:#9ca3af;font-size:12px;">${i+1}</td>
            <td><strong style="font-family:monospace;font-size:12px;">${t.tablo}</strong></td>
            <td>${parseInt(t.satirlar||0).toLocaleString('tr-TR')}</td>
            <td>${kb}</td>
            <td><div class="bar"><div class="bar-fill" style="width:${pct}%;background:${renk};"></div></div></td>
        </tr>`;
    }).join('');

    // Yedek listesi
    const yl = document.getElementById('yedekListesi');
    if (!v.yedekler.length) {
        yl.innerHTML = '<div style="text-align:center;padding:24px;color:#9ca3af;font-size:13px;">📭 Henüz yedek alınmamış. Yukarıdan yedek alabilirsiniz.</div>';
    } else {
        yl.innerHTML = v.yedekler.map(y=>`
            <div class="yedek-item">
                <div style="flex:1;min-width:0;">
                    <div class="yedek-ad">📄 ${y.dosya}</div>
                    <div class="yedek-meta">${y.tarih} · ${y.boyut}</div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <button class="btn btn-green btn-sm" onclick="yedekIndir('${y.dosya}')">⬇️ İndir</button>
                    <button class="btn btn-red btn-sm" onclick="yedekSil('${y.dosya}')">🗑️</button>
                </div>
            </div>`).join('');
    }

    // DB bilgi
    const di = v.db_info || {};
    document.getElementById('dbBilgi').innerHTML = `
        <div class="db-info-kart"><div class="label">Veritabanı</div><div class="val">${di.db_adi||'—'}</div></div>
        <div class="db-info-kart"><div class="label">MySQL Sürüm</div><div class="val">${di.versiyon||'—'}</div></div>
        <div class="db-info-kart"><div class="label">Karakter Seti</div><div class="val">${di.charset||'—'}</div></div>
        <div class="db-info-kart"><div class="label">Motor</div><div class="val">${di.engine||'InnoDB'}</div></div>
        <div class="db-info-kart"><div class="label">Tablo Sayısı</div><div class="val">${v.tablolar.length}</div></div>
        <div class="db-info-kart"><div class="label">Toplam Boyut</div><div class="val">${parseFloat(v.toplam_kb).toLocaleString('tr-TR',{minimumFractionDigits:1})} KB</div></div>`;
}

async function yedekAl(btn) {
    setBtnLoading(btn, true);
    log('Yedek alınıyor — lütfen bekleyin...','info');
    const v = await api({action:'yedekle'});
    setBtnLoading(btn, false);
    if (v.basari) { log('✅ Yedek alındı: '+v.dosya+' ('+v.boyut+')','ok'); istatistikYukle(); }
    else { log('❌ Hata: '+v.mesaj,'err'); }
}

async function optimizeEt(btn) {
    setBtnLoading(btn,true);
    log('Optimizasyon başlıyor...','info');
    const v = await api({action:'optimize'});
    setBtnLoading(btn,false);
    if (v.basari) {
        log('✅ '+v.mesaj,'ok');
        if(v.sonuclar) Object.entries(v.sonuclar).forEach(([t,s])=>log('  '+t+': '+s));
    } else log('❌ '+v.mesaj,'err');
}

async function tamirEt(btn) {
    setBtnLoading(btn,true);
    log('Tablo onarımı başlıyor...','info');
    const v = await api({action:'tamir'});
    setBtnLoading(btn,false);
    if (v.basari) {
        log('✅ '+v.mesaj,'ok');
        if(v.sonuclar) Object.entries(v.sonuclar).forEach(([t,s])=>log('  '+t+': '+s));
    } else log('❌ '+v.mesaj,'err');
}

function yedekIndir(dosya) {
    const f=document.createElement('form');f.method='POST';f.action='db_yonetim.php';
    ['action','dosya'].forEach((k,i)=>{const inp=document.createElement('input');inp.type='hidden';inp.name=k;inp.value=i===0?'yedek_indir':dosya;f.appendChild(inp);});
    document.body.appendChild(f);f.submit();document.body.removeChild(f);
}

async function yedekSil(dosya) {
    if(!confirm('Bu yedeği silmek istediğinizden emin misiniz?\n'+dosya)) return;
    const v=await api({action:'yedek_sil',dosya});
    if(v.basari){log('🗑️ Silindi: '+dosya,'ok');istatistikYukle();}
    else alert('Hata: '+v.mesaj);
}

async function tabloTemizle(tablo,etiket) {
    if(!confirm(`"${etiket}" tablosundaki TÜM kayıtlar kalıcı silinecek!\nBu işlem geri alınamaz. Devam?`)) return;
    const v=await api({action:'tablo_temizle',tablo});
    if(v.basari){log('🧹 '+v.mesaj,'ok');istatistikYukle();}
    else{log('❌ '+v.mesaj,'err');alert(v.mesaj);}
}

async function eskiKayitSil(tip,gunId) {
    const gun=document.getElementById(gunId)?.value||30;
    if(!confirm(`${gun} günden eski kayıtlar silinecek. Devam?`)) return;
    const v=await api({action:'eski_kayit_sil',tip,gun});
    if(v.basari){log('🧹 '+v.mesaj,'ok');istatistikYukle();}
    else{log('❌ '+v.mesaj,'err');alert(v.mesaj);}
}

async function oturumKapat() {
    await api({action:'oturum_kapat'});
    location.reload();
}

istatistikYukle();
</script>
</body>
</html>
