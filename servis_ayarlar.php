<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('servis');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Servis Ayarları</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#222}
.sayfa-wrap{max-width:1100px;margin:0 auto;padding:24px 16px}
h1{font-size:22px;font-weight:700;margin-bottom:20px;color:#1e3a8a}
.btn{padding:8px 18px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-success{background:#10b981;color:#fff}.btn-success:hover{background:#059669}
.btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626}
.btn-gray{background:#e5e7eb;color:#374151}.btn-gray:hover{background:#d1d5db}
.btn-sm{padding:5px 12px;font-size:12px}.btn-xs{padding:3px 9px;font-size:11px}
/* Tabs */
.tab-bar{display:flex;gap:4px;background:#fff;padding:8px;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:20px;flex-wrap:wrap}
.tab-btn{padding:9px 18px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;background:transparent;color:#6b7280;transition:.15s}
.tab-btn:hover{background:#f3f4f6;color:#374151}
.tab-btn.aktif{background:#3b82f6;color:#fff}
.tab-panel{display:none}
.tab-panel.aktif{display:block}
/* Kart */
.kart{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;margin-bottom:20px}
.kart-header{padding:14px 18px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}
.kart-header h3{font-size:15px;font-weight:700;color:#1e3a8a}
.kart-body{padding:18px}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{background:#f8faff;padding:10px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;white-space:nowrap}
tbody td{padding:9px 14px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
tbody tr:hover{background:#f8faff}
/* Inline edit form */
.ekle-form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:#f8faff;border-radius:8px;padding:12px 14px;margin-bottom:16px}
.ekle-form .fg{display:flex;flex-direction:column;gap:4px;flex:1;min-width:120px}
.ekle-form label{font-size:11px;font-weight:600;color:#374151}
.ekle-form input,.ekle-form select{padding:8px 10px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none}
.ekle-form input:focus,.ekle-form select:focus{border-color:#3b82f6}
.renk-preview{width:28px;height:28px;border-radius:6px;border:1px solid #dde3f0;cursor:pointer;flex-shrink:0}
.alert{padding:10px 16px;border-radius:7px;font-size:13px;margin-bottom:16px;display:none}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff}
.renk-dot{width:14px;height:14px;border-radius:50%;display:inline-block;vertical-align:middle;margin-right:5px}
</style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="sayfa-wrap">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <a href="servis.php" class="btn btn-gray btn-sm">← Servis Listesi</a>
    <h1 style="margin:0">&#9881; Servis Ayarları</h1>
  </div>

  <div id="alertBox" class="alert"></div>

  <div class="tab-bar">
    <button class="tab-btn aktif" onclick="tabAc('durumlar')">&#9679; Durumlar</button>
    <button class="tab-btn" onclick="tabAc('cihaz')">&#128241; Cihaz Türleri</button>
    <button class="tab-btn" onclick="tabAc('markalar')">&#127981; Markalar</button>
    <button class="tab-btn" onclick="tabAc('modeller')">&#128196; Modeller</button>
  </div>

  <!-- ── DURUMLAR ── -->
  <div class="tab-panel aktif" id="tab-durumlar">
    <div class="kart">
      <div class="kart-header"><h3>Servis Durumları</h3></div>
      <div class="kart-body">
        <div class="ekle-form" id="durumEkleForm">
          <div class="fg"><label>Durum Adı *</label><input type="text" id="dAd" placeholder="Örn: Beklemede"></div>
          <div class="fg" style="max-width:110px"><label>Renk</label>
            <div style="display:flex;gap:6px;align-items:center">
              <input type="color" id="dRenk" value="#667eea" style="width:42px;height:36px;padding:2px;border-radius:6px;border:1px solid #dde3f0;cursor:pointer">
            </div>
          </div>
          <div class="fg" style="max-width:80px"><label>Sıra</label><input type="number" id="dSira" value="1" min="1" style="width:70px"></div>
          <div class="fg" style="max-width:120px;justify-content:flex-end">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
              <input type="checkbox" id="dDashboard" checked> Dashboard
            </label>
          </div>
          <input type="hidden" id="dId">
          <button class="btn btn-success" onclick="durumKaydet()">Kaydet</button>
          <button class="btn btn-gray btn-sm" id="dIptalBtn" onclick="durumIptal()" style="display:none">İptal</button>
        </div>
        <table>
          <thead><tr><th>Renk</th><th>Durum Adı</th><th>Sıra</th><th>Dashboard</th><th>İşlem</th></tr></thead>
          <tbody id="durumlarBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── CİHAZ TÜRLERİ ── -->
  <div class="tab-panel" id="tab-cihaz">
    <div class="kart">
      <div class="kart-header"><h3>Cihaz Türleri</h3></div>
      <div class="kart-body">
        <div class="ekle-form">
          <div class="fg"><label>Cihaz Türü Adı *</label><input type="text" id="ctAd" placeholder="Örn: Yazarkasa POS"></div>
          <input type="hidden" id="ctId">
          <button class="btn btn-success" onclick="cihazKaydet()">Kaydet</button>
          <button class="btn btn-gray btn-sm" id="ctIptalBtn" onclick="cihazIptal()" style="display:none">İptal</button>
        </div>
        <table>
          <thead><tr><th>Cihaz Türü</th><th>İşlem</th></tr></thead>
          <tbody id="cihazBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── MARKALAR ── -->
  <div class="tab-panel" id="tab-markalar">
    <div class="kart">
      <div class="kart-header"><h3>Markalar</h3></div>
      <div class="kart-body">
        <div class="ekle-form">
          <div class="fg"><label>Marka Adı *</label><input type="text" id="mkAd" placeholder="Örn: inpos"></div>
          <input type="hidden" id="mkId">
          <button class="btn btn-success" onclick="markaKaydet()">Kaydet</button>
          <button class="btn btn-gray btn-sm" id="mkIptalBtn" onclick="markaIptal()" style="display:none">İptal</button>
        </div>
        <table>
          <thead><tr><th>Marka Adı</th><th>İşlem</th></tr></thead>
          <tbody id="markalarBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── MODELLER ── -->
  <div class="tab-panel" id="tab-modeller">
    <div class="kart">
      <div class="kart-header"><h3>Modeller</h3></div>
      <div class="kart-body">
        <div class="ekle-form">
          <div class="fg" style="max-width:200px"><label>Marka *</label><select id="mdMarka"></select></div>
          <div class="fg"><label>Model Adı *</label><input type="text" id="mdAd" placeholder="Örn: iP-3315"></div>
          <input type="hidden" id="mdId">
          <button class="btn btn-success" onclick="modelKaydet()">Kaydet</button>
          <button class="btn btn-gray btn-sm" id="mdIptalBtn" onclick="modelIptal()" style="display:none">İptal</button>
        </div>
        <div style="margin-bottom:12px">
          <label style="font-size:12px;font-weight:600;margin-right:8px">Marka Filtrele:</label>
          <select id="mdFiltre" onchange="modelleriYukle()" style="padding:7px 10px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none">
            <option value="">Tüm Markalar</option>
          </select>
        </div>
        <table>
          <thead><tr><th>Marka</th><th>Model Adı</th><th>İşlem</th></tr></thead>
          <tbody id="modellerBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const BASE = 'servis_kontrol.php';

async function api(params, method='GET'){
  const url = method==='GET' ? BASE+'?'+new URLSearchParams(params) : BASE;
  const r = await fetch(url, method==='POST'?{method:'POST',body:new URLSearchParams(params)}:{});
  return r.json();
}
function alertBox(tip,msg){ const b=document.getElementById('alertBox'); b.className='alert alert-'+tip; b.textContent=msg; b.style.display='block'; setTimeout(()=>b.style.display='none',3000); }
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

function tabAc(id){
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('aktif'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('aktif'));
  document.getElementById('tab-'+id).classList.add('aktif');
  event.target.classList.add('aktif');
}

// ── DURUMLAR ──────────────────────────────────────────────────────────
async function durumlarYukle(){
  const r=await api({action:'get_durumlar'});
  if(!r.success) return;
  document.getElementById('durumlarBody').innerHTML=r.data.map(d=>`
    <tr>
      <td><span class="renk-dot" style="background:${d.renk}"></span>${d.renk}</td>
      <td><span class="badge" style="background:${d.renk}">${esc(d.durum_adi)}</span></td>
      <td>${d.sira}</td>
      <td>${d.dashboard_goster?'<span style="color:#10b981;font-weight:600">✓ Evet</span>':'<span style="color:#9ca3af">Hayır</span>'}</td>
      <td>
        <button class="btn btn-primary btn-xs" onclick='durumDuzenle(${JSON.stringify(d)})'>✏ Düzenle</button>
        <button class="btn btn-danger btn-xs" onclick="durumSil(${d.id})">✕ Sil</button>
      </td>
    </tr>`).join('');
}

function durumDuzenle(d){
  document.getElementById('dId').value=d.id;
  document.getElementById('dAd').value=d.durum_adi;
  document.getElementById('dRenk').value=d.renk;
  document.getElementById('dSira').value=d.sira;
  document.getElementById('dDashboard').checked=d.dashboard_goster==1;
  document.getElementById('dIptalBtn').style.display='inline-flex';
  document.getElementById('dAd').focus();
}

function durumIptal(){
  document.getElementById('dId').value='';
  document.getElementById('dAd').value='';
  document.getElementById('dRenk').value='#667eea';
  document.getElementById('dSira').value='1';
  document.getElementById('dDashboard').checked=true;
  document.getElementById('dIptalBtn').style.display='none';
}

async function durumKaydet(){
  const id=document.getElementById('dId').value;
  const ad=document.getElementById('dAd').value.trim();
  if(!ad){alertBox('error','Durum adı boş olamaz');return;}
  const params={
    action:id?'update_durum':'add_durum',
    durum_adi:ad, renk:document.getElementById('dRenk').value,
    sira:document.getElementById('dSira').value,
    dashboard_goster:document.getElementById('dDashboard').checked?'1':''
  };
  if(id) params.id=id;
  const r=await api(params,'POST');
  if(r.success){alertBox('success',r.message);durumIptal();durumlarYukle();}
  else alertBox('error',r.message);
}

async function durumSil(id){
  if(!confirm('Bu durum silinsin mi?')) return;
  const r=await api({action:'delete_durum',id},'POST');
  if(r.success){alertBox('success',r.message);durumlarYukle();}
  else alertBox('error',r.message);
}

// ── CİHAZ TÜRLERİ ────────────────────────────────────────────────────
async function cihazYukle(){
  const r=await api({action:'get_cihaz_turleri'});
  if(!r.success) return;
  document.getElementById('cihazBody').innerHTML=r.data.map(c=>`
    <tr>
      <td>${esc(c.tur_adi)}</td>
      <td>
        <button class="btn btn-primary btn-xs" onclick="cihazDuzenle(${c.id},${JSON.stringify(c.tur_adi)})">✏</button>
        <button class="btn btn-danger btn-xs" onclick="cihazSil(${c.id})">✕</button>
      </td>
    </tr>`).join('');
}

function cihazDuzenle(id,ad){ document.getElementById('ctId').value=id; document.getElementById('ctAd').value=ad; document.getElementById('ctIptalBtn').style.display='inline-flex'; document.getElementById('ctAd').focus(); }
function cihazIptal(){ document.getElementById('ctId').value=''; document.getElementById('ctAd').value=''; document.getElementById('ctIptalBtn').style.display='none'; }

async function cihazKaydet(){
  const id=document.getElementById('ctId').value;
  const ad=document.getElementById('ctAd').value.trim();
  if(!ad){alertBox('error','Cihaz türü adı boş olamaz');return;}
  const params={action:id?'update_cihaz_turu':'add_cihaz_turu',tur_adi:ad};
  if(id) params.id=id;
  const r=await api(params,'POST');
  if(r.success){alertBox('success',r.message);cihazIptal();cihazYukle();}
  else alertBox('error',r.message);
}

async function cihazSil(id){
  if(!confirm('Silinsin mi?')) return;
  const r=await api({action:'delete_cihaz_turu',id},'POST');
  if(r.success){alertBox('success',r.message);cihazYukle();}
  else alertBox('error',r.message);
}

// ── MARKALAR ──────────────────────────────────────────────────────────
async function markalarYukle(){
  const r=await api({action:'get_markalar'});
  if(!r.success) return;
  // Marka seçicileri güncelle
  ['mdMarka','mdFiltre'].forEach(selId=>{
    const sel=document.getElementById(selId);
    const prevVal=sel.value;
    sel.innerHTML=selId==='mdFiltre'?'<option value="">Tüm Markalar</option>':'<option value="">-- Marka --</option>';
    r.data.forEach(m=>sel.innerHTML+=`<option value="${m.id}">${esc(m.marka_adi)}</option>`);
    sel.value=prevVal;
  });
  document.getElementById('markalarBody').innerHTML=r.data.map(m=>`
    <tr>
      <td>${esc(m.marka_adi)}</td>
      <td>
        <button class="btn btn-primary btn-xs" onclick="markaDuzenle(${m.id},${JSON.stringify(m.marka_adi)})">✏</button>
        <button class="btn btn-danger btn-xs" onclick="markaSil(${m.id})">✕</button>
      </td>
    </tr>`).join('');
}

function markaDuzenle(id,ad){ document.getElementById('mkId').value=id; document.getElementById('mkAd').value=ad; document.getElementById('mkIptalBtn').style.display='inline-flex'; document.getElementById('mkAd').focus(); }
function markaIptal(){ document.getElementById('mkId').value=''; document.getElementById('mkAd').value=''; document.getElementById('mkIptalBtn').style.display='none'; }

async function markaKaydet(){
  const id=document.getElementById('mkId').value;
  const ad=document.getElementById('mkAd').value.trim();
  if(!ad){alertBox('error','Marka adı boş olamaz');return;}
  const params={action:id?'update_marka':'add_marka',marka_adi:ad};
  if(id) params.id=id;
  const r=await api(params,'POST');
  if(r.success){alertBox('success',r.message);markaIptal();markalarYukle();}
  else alertBox('error',r.message);
}

async function markaSil(id){
  if(!confirm('Silinsin mi?')) return;
  const r=await api({action:'delete_marka',id},'POST');
  if(r.success){alertBox('success',r.message);markalarYukle();}
  else alertBox('error',r.message);
}

// ── MODELLER ──────────────────────────────────────────────────────────
async function modelleriYukle(){
  const filtre=document.getElementById('mdFiltre').value;
  const params={action:'get_modeller'};
  if(filtre) params.marka_id=filtre;
  const r=await api(params);
  if(!r.success) return;
  document.getElementById('modellerBody').innerHTML=r.data.length
    ? r.data.map(m=>`<tr>
        <td>${esc(m.marka_adi)}</td><td>${esc(m.model_adi)}</td>
        <td>
          <button class="btn btn-primary btn-xs" onclick="modelDuzenle(${m.id},${m.marka_id},${JSON.stringify(m.model_adi)})">✏</button>
          <button class="btn btn-danger btn-xs" onclick="modelSil(${m.id})">✕</button>
        </td>
      </tr>`).join('')
    : '<tr><td colspan="3" style="text-align:center;padding:20px;color:#9ca3af">Kayıt bulunamadı</td></tr>';
}

function modelDuzenle(id,markaId,ad){ document.getElementById('mdId').value=id; document.getElementById('mdMarka').value=markaId; document.getElementById('mdAd').value=ad; document.getElementById('mdIptalBtn').style.display='inline-flex'; document.getElementById('mdAd').focus(); }
function modelIptal(){ document.getElementById('mdId').value=''; document.getElementById('mdAd').value=''; document.getElementById('mdIptalBtn').style.display='none'; }

async function modelKaydet(){
  const id=document.getElementById('mdId').value;
  const markaId=document.getElementById('mdMarka').value;
  const ad=document.getElementById('mdAd').value.trim();
  if(!markaId){alertBox('error','Marka seçin');return;}
  if(!ad){alertBox('error','Model adı boş olamaz');return;}
  const params={action:id?'update_model':'add_model',marka_id:markaId,model_adi:ad};
  if(id) params.id=id;
  const r=await api(params,'POST');
  if(r.success){alertBox('success',r.message);modelIptal();modelleriYukle();}
  else alertBox('error',r.message);
}

async function modelSil(id){
  if(!confirm('Silinsin mi?')) return;
  const r=await api({action:'delete_model',id},'POST');
  if(r.success){alertBox('success',r.message);modelleriYukle();}
  else alertBox('error',r.message);
}

// ── INIT ──────────────────────────────────────────────────────────────
Promise.all([durumlarYukle(),cihazYukle(),markalarYukle()]).then(()=>modelleriYukle());
</script>
</body>
</html>
