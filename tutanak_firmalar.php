<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Tutanak Firmaları</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#1e293b;display:flex;min-height:100vh}
.sayfa-wrap{max-width:1000px;margin:0 auto;padding:24px 16px;width:100%}
h2{font-size:20px;font-weight:700;color:#1e3a8a;margin-bottom:20px}
.kart{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:20px;margin-bottom:20px}
.kart-baslik{font-size:14px;font-weight:700;color:#1e3a8a;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #e5e7eb}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.form-grup{display:flex;flex-direction:column;gap:4px}
.form-grup label{font-size:12px;font-weight:600;color:#374151}
.form-grup input,.form-grup select,.form-grup textarea{padding:9px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none;width:100%}
.form-grup input:focus,.form-grup select:focus,.form-grup textarea:focus{border-color:#3b82f6}
.btn{padding:9px 20px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;text-decoration:none;display:inline-block}
.btn-primary{background:#3b82f6;color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-success{background:#10b981;color:#fff}
.btn-success:hover{background:#059669}
.btn-warning{background:#f59e0b;color:#fff}
.btn-warning:hover{background:#d97706}
.btn-danger{background:#ef4444;color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-gray{background:#e5e7eb;color:#374151}
.btn-gray:hover{background:#d1d5db}
.btn-sm{padding:5px 12px;font-size:12px}
.tablo-wrap{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{background:#f8faff;padding:11px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;white-space:nowrap}
tbody td{padding:10px 14px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
tbody tr:hover{background:#f8faff}
tbody tr:last-child td{border-bottom:none}
.bos-mesaj{text-align:center;padding:40px;color:#9ca3af;font-size:14px}
.yukle-spin{text-align:center;padding:30px;color:#6b7280}
.alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:10px 14px;border-radius:7px;margin-bottom:14px;font-size:13px}
.alert-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:7px;margin-bottom:14px;font-size:13px}
@media(max-width:640px){.grid-2,.grid-3{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="sayfa-icerik">
<div class="sayfa-wrap">

<h2>Tutanak Firmaları Yönetimi</h2>
<div id="mesajAlani"></div>

<!-- FORM -->
<div class="kart">
  <div class="kart-baslik" id="formBaslik">Yeni Firma Ekle</div>
  <input type="hidden" id="edit_id" value="">
  <div class="grid-2" style="margin-bottom:12px">
    <div class="form-grup">
      <label>Firma Adı *</label>
      <input type="text" id="firma_adi" placeholder="Yetkili servis firma adı">
    </div>
    <div class="form-grup">
      <label>Yetki Numarası</label>
      <input type="text" id="yetki_numarasi" placeholder="Yetki belgesi numarası">
    </div>
    <div class="form-grup">
      <label>Mühür Numarası</label>
      <input type="text" id="muhur_numarasi" placeholder="Mühür numarası">
    </div>
    <div class="form-grup">
      <label>Telefon</label>
      <input type="text" id="telefon" placeholder="Telefon numarası">
    </div>
  </div>
  <div class="form-grup" style="margin-bottom:12px">
    <label>Adres</label>
    <input type="text" id="adres" placeholder="Firma adresi">
  </div>
  <div style="display:flex;gap:10px">
    <button class="btn btn-primary" onclick="firmaSave()">Kaydet</button>
    <button class="btn btn-gray" id="iptalBtn" onclick="formuTemizle()" style="display:none">İptal</button>
  </div>
</div>

<!-- LİSTE -->
<div class="tablo-wrap">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Firma Adı</th>
        <th>Yetki Numarası</th>
        <th>Mühür Numarası</th>
        <th>Telefon</th>
        <th>Adres</th>
        <th>İşlemler</th>
      </tr>
    </thead>
    <tbody id="tabloBody">
      <tr><td colspan="7" class="yukle-spin">Yükleniyor...</td></tr>
    </tbody>
  </table>
</div>

</div><!-- /sayfa-wrap -->
</div><!-- /sayfa-icerik -->

<script>
'use strict';
const BASE = 'tutanak_kontrol.php';

async function api(params, method='GET'){
  if(method==='GET'){
    const r = await fetch(BASE+'?'+new URLSearchParams(params));
    return r.json();
  }
  const r = await fetch(BASE,{method:'POST',body:new URLSearchParams(params)});
  return r.json();
}

async function listeYukle(){
  const tbody = document.getElementById('tabloBody');
  tbody.innerHTML = '<tr><td colspan="7" class="yukle-spin">Yukleniyor...</td></tr>';
  const r = await api({action:'get_firmalar'});
  if(!r.success||!r.data||!r.data.length){
    tbody.innerHTML='<tr><td colspan="7" class="bos-mesaj">Henuz firma eklenmemis.</td></tr>';
    return;
  }
  tbody.innerHTML = r.data.map((f,i)=>`
    <tr>
      <td>${i+1}</td>
      <td><strong>${esc(f.firma_adi||'—')}</strong></td>
      <td>${esc(f.yetki_numarasi||'—')}</td>
      <td>${esc(f.muhur_numarasi||'—')}</td>
      <td>${esc(f.telefon||'—')}</td>
      <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(f.adres||'')}">${esc(f.adres||'—')}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-warning btn-sm" onclick="duzenle(${parseInt(f.id)})">Duzenle</button>
        <button class="btn btn-danger btn-sm" style="margin-left:6px" onclick="sil(${parseInt(f.id)})">Sil</button>
      </td>
    </tr>`).join('');
}

async function firmaSave(){
  const firmAdi = document.getElementById('firma_adi').value.trim();
  if(!firmAdi){alert('Firma adi zorunludur');return;}
  const editId = document.getElementById('edit_id').value;
  const params = {
    action: editId ? 'update_firma' : 'add_firma',
    ...(editId ? {id:editId} : {}),
    firma_adi: firmAdi,
    yetki_numarasi: document.getElementById('yetki_numarasi').value,
    muhur_numarasi: document.getElementById('muhur_numarasi').value,
    telefon: document.getElementById('telefon').value,
    adres: document.getElementById('adres').value
  };
  const r = await api(params,'POST');
  const el = document.getElementById('mesajAlani');
  if(r.success){
    el.innerHTML='<div class="alert-success">Firma basariyla '+(editId?'guncellendi':'kaydedildi')+'.</div>';
    setTimeout(()=>el.innerHTML='',3000);
    formuTemizle();
    listeYukle();
  } else {
    el.innerHTML='<div class="alert-error">'+(r.message||'Hata olustu')+'</div>';
  }
}

async function duzenle(id){
  const r = await api({action:'get_firma',id});
  if(!r.success||!r.data){alert('Firma yuklenemedi');return;}
  const d = r.data;
  document.getElementById('edit_id').value = d.id;
  document.getElementById('firma_adi').value = d.firma_adi||'';
  document.getElementById('yetki_numarasi').value = d.yetki_numarasi||'';
  document.getElementById('muhur_numarasi').value = d.muhur_numarasi||'';
  document.getElementById('telefon').value = d.telefon||'';
  document.getElementById('adres').value = d.adres||'';
  document.getElementById('formBaslik').textContent = 'Firma Duzenle';
  document.getElementById('iptalBtn').style.display='inline-block';
  document.getElementById('firma_adi').focus();
  window.scrollTo({top:0,behavior:'smooth'});
}

async function sil(id){
  if(!confirm('Bu firmay\u0131 silmek istedi\u011finizden emin misiniz?')) return;
  const r = await api({action:'delete_firma',id},'POST');
  if(r.success){
    document.getElementById('mesajAlani').innerHTML='<div class="alert-success">Firma silindi.</div>';
    setTimeout(()=>document.getElementById('mesajAlani').innerHTML='',3000);
    listeYukle();
  } else alert(r.message||'Silme islemi basarisiz');
}

function formuTemizle(){
  document.getElementById('edit_id').value='';
  document.getElementById('firma_adi').value='';
  document.getElementById('yetki_numarasi').value='';
  document.getElementById('muhur_numarasi').value='';
  document.getElementById('telefon').value='';
  document.getElementById('adres').value='';
  document.getElementById('formBaslik').textContent='Yeni Firma Ekle';
  document.getElementById('iptalBtn').style.display='none';
}

function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

document.addEventListener('DOMContentLoaded',()=>listeYukle());
</script>
</body>
</html>
