<?php
require_once 'db.php';
require_once 'auth.php';

// Sayfalama
$kategoriFiltre = $_GET['kategori'] ?? '';
$aramaFiltre    = trim($_GET['arama'] ?? '');
$sql = 'SELECT * FROM servis_katalog WHERE aktif=1';
$params = [];
if ($kategoriFiltre) { $sql .= ' AND kategori=?'; $params[] = $kategoriFiltre; }
if ($aramaFiltre)    { $sql .= ' AND (ad LIKE ? OR kod LIKE ?)'; $params[] = "%$aramaFiltre%"; $params[] = "%$aramaFiltre%"; }
$sql .= ' ORDER BY kategori, ad';
$st = $pdo->prepare($sql);
$st->execute($params);
$kayitlar = $st->fetchAll(PDO::FETCH_ASSOC);

// Sayaçlar
$saySt = $pdo->query("SELECT kategori, COUNT(*) as n FROM servis_katalog WHERE aktif=1 GROUP BY kategori");
$sayac = ['yedek_parca'=>0,'iscilik'=>0];
foreach ($saySt->fetchAll(PDO::FETCH_ASSOC) as $r) $sayac[$r['kategori']] = $r['n'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Servis Kataloğu</title>
<?php require_once 'menu.php'; ?>
<style>
.sk-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.sk-header h1{font-size:22px;font-weight:800;color:#1e293b}
.sk-stat-cards{display:flex;gap:12px;margin-bottom:22px;flex-wrap:wrap}
.sk-stat{background:#fff;border-radius:14px;padding:16px 22px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);min-width:160px;flex:1}
.sk-stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.sk-stat-icon.yp{background:#eff6ff}
.sk-stat-icon.is{background:#f0fdf4}
.sk-stat-val{font-size:24px;font-weight:800;color:#1e293b;line-height:1}
.sk-stat-lbl{font-size:12px;color:#6b7280;margin-top:3px}

.sk-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.sk-search{flex:1;min-width:200px;position:relative}
.sk-search input{width:100%;padding:9px 12px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;background:#fff}
.sk-search input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.sk-search-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px}
.sk-tab-grp{display:flex;background:#f1f5f9;border-radius:10px;padding:3px;gap:2px}
.sk-tab{padding:7px 16px;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;color:#6b7280;background:transparent;transition:.15s;white-space:nowrap}
.sk-tab.aktif{background:#fff;color:#3b82f6;box-shadow:0 1px 4px rgba(0,0,0,.1)}

.sk-tablo-wrap{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.07);overflow:hidden}
.sk-tablo{width:100%;border-collapse:collapse}
.sk-tablo thead th{padding:12px 14px;text-align:left;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;background:#f8fafc;border-bottom:1px solid #e5e7eb}
.sk-tablo tbody td{padding:12px 14px;border-bottom:1px solid #f3f4f6;font-size:13px;vertical-align:middle}
.sk-tablo tbody tr:last-child td{border-bottom:none}
.sk-tablo tbody tr:hover td{background:#f8fafc}
.kat-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700}
.kat-yp{background:#dbeafe;color:#1d4ed8}
.kat-is{background:#dcfce7;color:#15803d}
.fiyat-cell{font-weight:700;color:#059669}
.islem-buton{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:none;border-radius:7px;cursor:pointer;font-size:12px;font-weight:600;transition:.15s}
.btn-duzenle{background:#eff6ff;color:#2563eb}.btn-duzenle:hover{background:#dbeafe}
.btn-sil{background:#fef2f2;color:#dc2626}.btn-sil:hover{background:#fee2e2}
.empty-state{text-align:center;padding:60px 20px;color:#9ca3af}
.empty-state .es-ico{font-size:48px;margin-bottom:12px}

/* MODAL */
.sk-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:16px}
.sk-overlay.aktif{display:flex}
.sk-modal{background:#fff;border-radius:20px;width:100%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden}
.sk-modal-hdr{background:linear-gradient(135deg,#1e3a8a,#3b82f6);padding:20px 24px;display:flex;align-items:center;justify-content:space-between}
.sk-modal-hdr h3{color:#fff;font-size:17px;font-weight:800;margin:0}
.sk-modal-kapat{background:rgba(255,255,255,.15);border:none;border-radius:50%;width:30px;height:30px;color:#fff;font-size:17px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.sk-modal-kapat:hover{background:rgba(255,255,255,.3)}
.sk-modal-body{padding:22px 24px;max-height:65vh;overflow-y:auto}
.sk-modal-footer{padding:14px 24px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafbff}
.mfg{margin-bottom:14px}
.mfg label{display:block;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.mfg input,.mfg select,.mfg textarea{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;transition:.15s}
.mfg input:focus,.mfg select:focus,.mfg textarea:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.mfg2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn-pri{padding:10px 22px;border:none;border-radius:10px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;cursor:pointer;font-weight:700;font-size:13px;box-shadow:0 3px 12px rgba(59,130,246,.35)}
.btn-sec{padding:10px 18px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;cursor:pointer;font-weight:600;font-size:13px;color:#374151}
.kat-toggle{display:flex;gap:8px;margin-bottom:4px}
.kt-btn{flex:1;padding:10px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#6b7280;text-align:center;transition:.15s}
.kt-btn.aktif.yp{border-color:#3b82f6;background:#eff6ff;color:#1d4ed8}
.kt-btn.aktif.is{border-color:#10b981;background:#f0fdf4;color:#15803d}
</style>
</head>
<body>

<!-- STAT KARTLARI -->
<div class="sk-header">
  <h1>🔩 Servis Kataloğu</h1>
  <button class="btn-pri" onclick="modalAc()">➕ Yeni Kalem Ekle</button>
</div>

<div class="sk-stat-cards">
  <div class="sk-stat">
    <div class="sk-stat-icon yp">🔩</div>
    <div>
      <div class="sk-stat-val"><?= $sayac['yedek_parca'] ?></div>
      <div class="sk-stat-lbl">Yedek Parça</div>
    </div>
  </div>
  <div class="sk-stat">
    <div class="sk-stat-icon is">🛠️</div>
    <div>
      <div class="sk-stat-val"><?= $sayac['iscilik'] ?></div>
      <div class="sk-stat-lbl">İşçilik</div>
    </div>
  </div>
  <div class="sk-stat" style="background:linear-gradient(135deg,#eff6ff,#dbeafe)">
    <div class="sk-stat-icon" style="background:rgba(255,255,255,.6)">📦</div>
    <div>
      <div class="sk-stat-val"><?= $sayac['yedek_parca'] + $sayac['iscilik'] ?></div>
      <div class="sk-stat-lbl">Toplam Kalem</div>
    </div>
  </div>
</div>

<!-- TOOLBAR -->
<div class="sk-toolbar">
  <div class="sk-search">
    <span class="sk-search-ico">🔍</span>
    <input type="text" id="aramaInput" placeholder="Kalem adı veya kodu ara..." value="<?= htmlspecialchars($aramaFiltre) ?>" oninput="aramaYap()">
  </div>
  <div class="sk-tab-grp">
    <button class="sk-tab <?= !$kategoriFiltre?'aktif':'' ?>" onclick="katFiltre('')">Tümü (<?= $sayac['yedek_parca']+$sayac['iscilik'] ?>)</button>
    <button class="sk-tab <?= $kategoriFiltre==='yedek_parca'?'aktif':'' ?>" onclick="katFiltre('yedek_parca')">🔩 Yedek Parça (<?= $sayac['yedek_parca'] ?>)</button>
    <button class="sk-tab <?= $kategoriFiltre==='iscilik'?'aktif':'' ?>" onclick="katFiltre('iscilik')">🛠️ İşçilik (<?= $sayac['iscilik'] ?>)</button>
  </div>
</div>

<!-- TABLO -->
<div class="sk-tablo-wrap">
  <?php if ($kayitlar): ?>
  <table class="sk-tablo">
    <thead>
      <tr>
        <th>Kalem Adı</th>
        <th>Kategori</th>
        <th>Kod</th>
        <th>Birim Fiyat</th>
        <th>KDV %</th>
        <th>Birim</th>
        <th>Açıklama</th>
        <th style="width:100px">İşlem</th>
      </tr>
    </thead>
    <tbody id="tabloBody">
    <?php foreach ($kayitlar as $k): ?>
      <tr>
        <td><strong><?= htmlspecialchars($k['ad']) ?></strong></td>
        <td>
          <?php if ($k['kategori']==='yedek_parca'): ?>
            <span class="kat-badge kat-yp">🔩 Yedek Parça</span>
          <?php else: ?>
            <span class="kat-badge kat-is">🛠️ İşçilik</span>
          <?php endif; ?>
        </td>
        <td style="color:#6b7280;font-size:12px"><?= htmlspecialchars($k['kod']??'-') ?></td>
        <td class="fiyat-cell"><?= number_format($k['birim_fiyat'],2,',','.') ?> ₺</td>
        <td style="color:#6b7280">%<?= rtrim(rtrim(number_format($k['kdv_orani'],2,',','.'),'0'),',') ?></td>
        <td style="color:#6b7280"><?= htmlspecialchars($k['birim']) ?></td>
        <td style="color:#6b7280;font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($k['aciklama']??'') ?></td>
        <td>
          <div style="display:flex;gap:5px">
            <button class="islem-buton btn-duzenle" onclick="duzenle(<?= $k['id'] ?>)">✏️</button>
            <button class="islem-buton btn-sil" onclick="silKayit(<?= $k['id'] ?>, '<?= htmlspecialchars($k['ad'],ENT_QUOTES) ?>')">🗑️</button>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty-state">
    <div class="es-ico">🔩</div>
    <p style="font-weight:600;color:#374151;margin-bottom:6px">Henüz kalem eklenmedi</p>
    <p style="font-size:13px;margin-bottom:16px">Yedek parça ve işçilik kalemlerini buradan yönetin</p>
    <button class="btn-pri" onclick="modalAc()">➕ İlk Kalemi Ekle</button>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL -->
<div class="sk-overlay" id="skOverlay">
  <div class="sk-modal">
    <div class="sk-modal-hdr">
      <h3 id="modalBaslik">➕ Yeni Kalem</h3>
      <button class="sk-modal-kapat" onclick="modalKapat()">×</button>
    </div>
    <div class="sk-modal-body">
      <input type="hidden" id="fId">
      <div class="mfg">
        <label>Kategori <span style="color:#ef4444">*</span></label>
        <div class="kat-toggle">
          <button type="button" class="kt-btn aktif yp" id="btnYP" onclick="katSec('yedek_parca')">🔩 Yedek Parça</button>
          <button type="button" class="kt-btn is"       id="btnIS" onclick="katSec('iscilik')">🛠️ İşçilik</button>
        </div>
        <input type="hidden" id="fKategori" value="yedek_parca">
      </div>
      <div class="mfg">
        <label>Kalem Adı <span style="color:#ef4444">*</span></label>
        <input type="text" id="fAd" placeholder="Örn: TERMAL YAZICI KAĞIDI veya KOMPLİ TAMIR" style="text-transform:uppercase">
      </div>
      <div class="mfg2">
        <div class="mfg">
          <label>Kod</label>
          <input type="text" id="fKod" placeholder="SKT-001" style="text-transform:uppercase">
        </div>
        <div class="mfg">
          <label>Birim</label>
          <select id="fBirim">
            <option>Adet</option>
            <option>Paket</option>
            <option>Saat</option>
            <option>Dakika</option>
            <option>Metre</option>
            <option>Kg</option>
          </select>
        </div>
        <div class="mfg">
          <label>Birim Fiyat (₺)</label>
          <input type="number" id="fFiyat" placeholder="0.00" step="0.01" min="0">
        </div>
        <div class="mfg">
          <label>KDV Oranı (%)</label>
          <select id="fKdv">
            <option value="0">%0</option>
            <option value="10">%10</option>
            <option value="20" selected>%20</option>
          </select>
        </div>
      </div>
      <div class="mfg">
        <label>Açıklama</label>
        <textarea id="fAciklama" rows="2" placeholder="Opsiyonel açıklama..."></textarea>
      </div>
    </div>
    <div class="sk-modal-footer">
      <button class="btn-sec" onclick="modalKapat()">İptal</button>
      <button class="btn-pri" onclick="kaydet()" id="kaydetBtn">💾 Kaydet</button>
    </div>
  </div>
</div>

<script>
let aramaT;
function aramaYap(){
  clearTimeout(aramaT);
  aramaT=setTimeout(()=>{
    const q=document.getElementById('aramaInput').value;
    const kat=new URLSearchParams(location.search).get('kategori')||'';
    location.href='servis_katalog.php?arama='+encodeURIComponent(q)+(kat?'&kategori='+kat:'');
  },400);
}
function katFiltre(kat){
  const q=new URLSearchParams(location.search).get('arama')||'';
  location.href='servis_katalog.php'+(kat?'?kategori='+kat:'')+(q?(kat?'&':'?')+'arama='+encodeURIComponent(q):'');
}
function modalAc(){
  document.getElementById('fId').value='';
  document.getElementById('fAd').value='';
  document.getElementById('fKod').value='';
  document.getElementById('fFiyat').value='';
  document.getElementById('fAciklama').value='';
  document.getElementById('fKdv').value='20';
  document.getElementById('fBirim').value='Adet';
  katSec('yedek_parca');
  document.getElementById('modalBaslik').textContent='➕ Yeni Kalem';
  document.getElementById('skOverlay').classList.add('aktif');
  setTimeout(()=>document.getElementById('fAd').focus(),100);
}
function modalKapat(){ document.getElementById('skOverlay').classList.remove('aktif'); }
function katSec(k){
  document.getElementById('fKategori').value=k;
  document.getElementById('btnYP').classList.toggle('aktif',k==='yedek_parca');
  document.getElementById('btnIS').classList.toggle('aktif',k==='iscilik');
}
async function duzenle(id){
  const r=await fetch('servis_katalog_kontrol.php?action=get&id='+id).then(x=>x.json());
  if(!r.success) return alert('Kayıt bulunamadı');
  const d=r.data;
  document.getElementById('fId').value=d.id;
  document.getElementById('fAd').value=d.ad;
  document.getElementById('fKod').value=d.kod||'';
  document.getElementById('fFiyat').value=d.birim_fiyat;
  document.getElementById('fKdv').value=d.kdv_orani;
  document.getElementById('fBirim').value=d.birim||'Adet';
  document.getElementById('fAciklama').value=d.aciklama||'';
  katSec(d.kategori);
  document.getElementById('modalBaslik').textContent='✏️ Düzenle: '+d.ad;
  document.getElementById('skOverlay').classList.add('aktif');
}
async function kaydet(){
  const ad=document.getElementById('fAd').value.trim();
  if(!ad){alert('Kalem adı zorunludur');return;}
  const id=document.getElementById('fId').value;
  const btn=document.getElementById('kaydetBtn');
  btn.disabled=true; btn.textContent='⏳...';
  const params=new URLSearchParams({
    action: id?'guncelle':'ekle', id,
    ad, kategori:document.getElementById('fKategori').value,
    kod:document.getElementById('fKod').value,
    birim_fiyat:document.getElementById('fFiyat').value,
    kdv_orani:document.getElementById('fKdv').value,
    birim:document.getElementById('fBirim').value,
    aciklama:document.getElementById('fAciklama').value,
  });
  const r=await fetch('servis_katalog_kontrol.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:params.toString()}).then(x=>x.json());
  btn.disabled=false; btn.textContent='💾 Kaydet';
  if(r.success){ modalKapat(); location.reload(); }
  else alert('Hata: '+r.mesaj);
}
async function silKayit(id, ad){
  if(!confirm(ad+' kalemini silmek istediğinizden emin misiniz?')) return;
  const r=await fetch('servis_katalog_kontrol.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=sil&id='+id}).then(x=>x.json());
  if(r.success) location.reload();
  else alert('Hata: '+r.mesaj);
}
document.getElementById('skOverlay').addEventListener('click',e=>{if(e.target===document.getElementById('skOverlay'))modalKapat();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')modalKapat();});
</script>
</body>
</html>
