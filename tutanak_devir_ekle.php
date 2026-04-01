<?php
require_once 'db.php';
require_once 'auth.php';
$edit_id              = isset($_GET['edit_id'])              ? (int)$_GET['edit_id']              : 0;
$satici_adi           = isset($_GET['satici_adi'])           ? htmlspecialchars($_GET['satici_adi'])           : '';
$satici_adres         = isset($_GET['satici_adres'])         ? htmlspecialchars($_GET['satici_adres'])         : '';
$satici_vergi_dairesi = isset($_GET['satici_vergi_dairesi']) ? htmlspecialchars($_GET['satici_vergi_dairesi']) : '';
$satici_vergi_no      = isset($_GET['satici_vergi_no'])      ? htmlspecialchars($_GET['satici_vergi_no'])      : '';
$satici_tel           = isset($_GET['satici_tel'])           ? htmlspecialchars($_GET['satici_tel'])           : '';
$cihaz_marka          = isset($_GET['cihaz_marka'])          ? htmlspecialchars($_GET['cihaz_marka'])          : '';
$cihaz_model          = isset($_GET['cihaz_model'])          ? htmlspecialchars($_GET['cihaz_model'])          : '';
$cihaz_sicil_no       = isset($_GET['cihaz_sicil_no'])       ? htmlspecialchars($_GET['cihaz_sicil_no'])       : '';
$servis_id            = isset($_GET['servis_id'])            ? (int)$_GET['servis_id']            : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?= $edit_id ? 'EK-2 Devir Tutanağı Düzenle' : 'EK-2 Devir Satış Tutanağı Ekle' ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#1e293b;display:flex;min-height:100vh}
.sayfa-wrap{max-width:900px;margin:0 auto;padding:24px 16px;width:100%}
h2{font-size:20px;font-weight:700;color:#1e3a8a;margin-bottom:20px}
.kart{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:20px;margin-bottom:18px}
.kart-baslik{font-size:14px;font-weight:700;color:#1e3a8a;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #e5e7eb;display:flex;align-items:center;gap:6px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.form-grup{display:flex;flex-direction:column;gap:4px}
.form-grup label{font-size:12px;font-weight:600;color:#374151}
.form-grup input,.form-grup select,.form-grup textarea{padding:9px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none;width:100%}
.form-grup input:focus,.form-grup select:focus,.form-grup textarea:focus{border-color:#3b82f6}
.form-grup input[readonly]{background:#f9fafb;color:#6b7280}
.btn{padding:9px 20px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s}
.btn-primary{background:#3b82f6;color:#fff}
.btn-primary:hover{background:#2563eb}
.btn-green{background:#10b981;color:#fff}
.btn-green:hover{background:#059669}
.btn-gray{background:#e5e7eb;color:#374151}
.btn-gray:hover{background:#d1d5db}
.ac-wrap{position:relative}
.alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}
.alert-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}
@media(max-width:640px){.grid-2,.grid-3{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="sayfa-icerik">
<div class="sayfa-wrap">

<h2><?= $edit_id ? 'EK-2 Devir Satış Tutanağı Düzenle' : 'EK-2 Devir Satış Tutanağı Ekle' ?></h2>
<div id="mesajAlani"></div>

<!-- TUTANAK BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Tutanak Bilgileri</div>
  <div class="grid-3">
    <div class="form-grup">
      <label>Tarih *</label>
      <input type="date" id="tutanak_tarih" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-grup">
      <label>Sıra No</label>
      <input type="text" id="sira_no" placeholder="Otomatik" readonly>
    </div>
    <div class="form-grup">
      <label>GİB Onay Kodu</label>
      <input type="text" id="gib_onay_no" placeholder="GİB onay numarası">
    </div>
  </div>
</div>

<!-- YETKİLİ SERVİS -->
<div class="kart">
  <div class="kart-baslik">Yetkili Servis Bilgileri</div>
  <div class="grid-2">
    <div class="form-grup">
      <label>Yetkili Servis Ünvanı</label>
      <input type="text" id="servis_adi" placeholder="Marka seçince otomatik dolar..." readonly style="background:#f9fafb">
    </div>
    <div class="form-grup">
      <label>Servis Adresi</label>
      <input type="text" id="servis_adresi" placeholder="Servis adresi">
    </div>
  </div>
  <input type="hidden" id="firma_id">
  <input type="hidden" id="yetki_numarasi">
  <input type="hidden" id="muhur_numarasi">
</div>

<!-- SATICI BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Satıcı Bilgileri</div>
  <div class="grid-2">
    <div class="form-grup">
      <label>Satıcı Adı *</label>
      <input type="text" id="satici_adi" value="<?= $satici_adi ?>">
    </div>
    <div class="form-grup">
      <label>Satıcı Telefon</label>
      <input type="text" id="satici_tel" value="<?= $satici_tel ?>">
    </div>
    <div class="form-grup">
      <label>Vergi Dairesi</label>
      <input type="text" id="satici_vergi_dairesi" value="<?= $satici_vergi_dairesi ?>">
    </div>
    <div class="form-grup">
      <label>Vergi No</label>
      <input type="text" id="satici_vergi_no" value="<?= $satici_vergi_no ?>">
    </div>
    <div class="form-grup" style="grid-column:1/-1">
      <label>Satıcı Adresi</label>
      <input type="text" id="satici_adres" value="<?= $satici_adres ?>">
    </div>
  </div>
</div>

<!-- ALICI BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Alıcı Bilgileri</div>
  <div style="background:#f0fdf4;border:1px solid #6ee7b7;border-radius:8px;padding:12px;margin-bottom:12px">
    <div style="font-size:12px;font-weight:600;color:#065f46;margin-bottom:6px">FaturaApp Müşterilerinden Alıcı Seç</div>
    <div class="ac-wrap" id="aliciWrap">
      <input type="text" id="aliciAramaInput" placeholder="Alıcı adı veya telefon..." autocomplete="off"
             style="width:100%;padding:8px 12px;border:1px solid #d1fae5;border-radius:6px;font-size:13px">
      <div id="aliciDd" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #dee2e6;border-radius:0 0 6px 6px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:200px;overflow-y:auto;z-index:999;display:none"></div>
    </div>
    <div id="aliciBilgi" style="display:none;background:#d1fae5;border:1px solid #6ee7b7;border-radius:5px;padding:7px 10px;font-size:12px;color:#065f46;margin-top:5px"></div>
    <button type="button" onclick="yeniAliciAc()" style="margin-top:6px;background:#10b981;color:#fff;border:none;border-radius:6px;padding:5px 14px;cursor:pointer;font-size:12px;font-weight:600">+ Yeni Müşteri Tanımla</button>
    <div id="yeniAliciForm" style="display:none;background:#f9f9f9;border:1px solid #dee2e6;border-radius:7px;padding:12px;margin-top:8px">
      <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px">Yeni Alıcı Müşteri</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
        <div><label style="font-size:11px;font-weight:600">Ad Soyad *</label><input type="text" id="yaAd" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"></div>
        <div><label style="font-size:11px;font-weight:600">Telefon</label><input type="text" id="yaTel" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"></div>
        <div><label style="font-size:11px;font-weight:600">Vergi Dairesi</label><input type="text" id="yaVD" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"></div>
        <div><label style="font-size:11px;font-weight:600">Vergi No</label><input type="text" id="yaVN" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"></div>
        <div><label style="font-size:11px;font-weight:600">E-posta</label><input type="email" id="yaEmail" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"></div>
        <div><label style="font-size:11px;font-weight:600">Tip</label><select id="yaTip" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"><option value="bireysel">Bireysel</option><option value="kurumsal">Kurumsal</option></select></div>
      </div>
      <div style="margin-bottom:8px"><label style="font-size:11px;font-weight:600">Adres</label><input type="text" id="yaAdres" style="width:100%;padding:7px;border:1px solid #ddd;border-radius:5px;font-size:13px"></div>
      <button type="button" onclick="yeniAliciKaydet()" style="background:#3b82f6;color:#fff;border:none;border-radius:6px;padding:7px 16px;cursor:pointer;font-size:13px;font-weight:600">FaturaApp'e Kaydet ve Forma Aktar</button>
      <button type="button" onclick="document.getElementById('yeniAliciForm').style.display='none'" style="background:#e5e7eb;color:#374151;border:none;border-radius:6px;padding:7px 14px;cursor:pointer;font-size:13px;margin-left:6px">İptal</button>
    </div>
  </div>
  <div class="grid-2">
    <div class="form-grup">
      <label>Alıcı Adı *</label>
      <input type="text" id="alici_adi" placeholder="Alıcı ad soyad / firma">
    </div>
    <div class="form-grup">
      <label>Alıcı Telefon</label>
      <input type="text" id="alici_tel" placeholder="Telefon">
    </div>
    <div class="form-grup">
      <label>Vergi Dairesi</label>
      <input type="text" id="alici_vergi_dairesi" placeholder="Vergi dairesi">
    </div>
    <div class="form-grup">
      <label>Vergi No</label>
      <input type="text" id="alici_vergi_no" placeholder="Vergi no / TC">
    </div>
    <div class="form-grup" style="grid-column:1/-1">
      <label>Alıcı Adresi</label>
      <input type="text" id="alici_adres" placeholder="Adres">
    </div>
  </div>
</div>

<!-- CİHAZ BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Cihaz Bilgileri</div>
  <div class="grid-3">
    <div class="form-grup">
      <label>Marka *</label>
      <select id="cihaz_marka_id" onchange="modellerYukle(this.value);markaDegisMD(this)">
        <option value="">-- Marka Seç --</option>
      </select>
    </div>
    <div class="form-grup">
      <label>Model *</label>
      <select id="cihaz_model_id">
        <option value="">-- Model Seç --</option>
      </select>
    </div>
    <div class="form-grup">
      <label>Sicil No *</label>
      <input type="text" id="cihaz_sicil_no" value="<?= $cihaz_sicil_no ?>">
    </div>
    <div class="form-grup">
      <label>İlk Z No Tarihi</label>
      <input type="date" id="ilk_z_tarihi">
    </div>
    <div class="form-grup">
      <label>Son Z No Tarihi</label>
      <input type="date" id="son_z_tarihi">
    </div>
  </div>
</div>

<!-- Z RAPORU BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Z Raporu Bilgileri</div>
  <div class="grid-3">
    <div class="form-grup">
      <label>Z No</label>
      <input type="text" id="z_no" placeholder="Z raporu numarası">
    </div>
    <div class="form-grup">
      <label>Küm KDV (₺)</label>
      <input type="number" id="kum_kdv" step="0.01" placeholder="0.00">
    </div>
    <div class="form-grup">
      <label>Küm Top (₺)</label>
      <input type="number" id="kum_top" step="0.01" placeholder="0.00">
    </div>
  </div>
</div>

<!-- SATIŞ FATURA -->
<div class="kart">
  <div class="kart-baslik">Satış Fatura Bilgileri</div>
  <div class="grid-3">
    <div class="form-grup">
      <label>Fatura Tarihi</label>
      <input type="date" id="fatura_tarih">
    </div>
    <div class="form-grup">
      <label>Fatura No</label>
      <input type="text" id="fatura_no" placeholder="Fatura numarası">
    </div>
    <div class="form-grup">
      <label>Satış Bedeli (₺)</label>
      <input type="number" id="satis_bedeli" step="0.01" placeholder="0.00">
    </div>
  </div>
</div>

<!-- DİĞER TESPİTLER -->
<div class="kart">
  <div class="kart-baslik">Diğer Tespitler ve Notlar</div>
  <div class="form-grup" style="margin-bottom:12px">
    <label>Tespitler / Açıklamalar</label>
    <textarea id="diger_tespitler" rows="4" placeholder="Tutanakta belirtmek istediğiniz diğer hususlar..."></textarea>
  </div>
  <div class="form-grup">
    <label>Durum</label>
    <select id="durum">
      <option value="taslak">Taslak</option>
      <option value="tamamlandi">Tamamlandı</option>
      <option value="iptal">İptal</option>
    </select>
  </div>
</div>

<!-- BUTONLAR -->
<div style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:30px">
  <a href="tutanak_devir_liste.php" class="btn btn-gray">İptal</a>
  <button type="button" class="btn btn-primary" onclick="formKaydet(false)"><?= $edit_id ? 'Güncelle' : 'Kaydet' ?></button>
  <button type="button" class="btn btn-green" onclick="formKaydet(true)">Kaydet ve Yazdır</button>
</div>

</div><!-- /sayfa-wrap -->
</div><!-- /sayfa-icerik -->

<script>
'use strict';
const BASE = 'tutanak_kontrol.php';
const EDIT_ID = <?= $edit_id ?>;
const GET_MARKA = <?= json_encode($cihaz_marka) ?>;
const GET_MODEL = <?= json_encode($cihaz_model) ?>;

async function api(params, method='GET'){
  if(method==='GET'){
    const r = await fetch(BASE+'?'+new URLSearchParams(params));
    return r.json();
  }
  const r = await fetch(BASE,{method:'POST',body:new URLSearchParams(params)});
  return r.json();
}

// --- Firma eşleştirme (markaya göre otomatik) ---
async function firmaByMarkaYukle(markaAdi, modelAdi){
  if(!markaAdi) return;
  const r = await api({action:'get_eslestirme_by_marka_model', marka_adi: markaAdi, model_adi: modelAdi||''});
  if(r.success && r.data){
    const d = r.data;
    document.getElementById('yetki_numarasi').value = d.yetki_no||'';
    document.getElementById('muhur_numarasi').value  = d.muhur_no||'';
    document.getElementById('servis_adi').value      = d.firma_unvan||'';
  }
}
async function markaDegisMD(sel){
  const markaAdi = sel.options[sel.selectedIndex]?.text||'';
  // model henüz seçilmedi, sadece marka ile ara
  await firmaByMarkaYukle(markaAdi, '');
}

// --- Marka / Model ---
async function markalarYukle(){
  const r = await api({action:'get_markalar_tutanak'});
  const sel = document.getElementById('cihaz_marka_id');
  if(r.success && r.data){
    r.data.forEach(m=>{
      const o = document.createElement('option');
      o.value = m.id;
      o.textContent = m.marka_adi||m.ad||m.name||'';
      sel.appendChild(o);
    });
    if(GET_MARKA){
      for(const o of sel.options){
        if(o.textContent.toLowerCase()===GET_MARKA.toLowerCase()){
          sel.value=o.value;
          await modellerYukle(o.value, GET_MODEL);
          await firmaByMarkaYukle(GET_MARKA, GET_MODEL);
          break;
        }
      }
    }
  }
}
async function modellerYukle(markaId, autoSelect){
  const sel = document.getElementById('cihaz_model_id');
  sel.innerHTML = '<option value="">-- Model Seç --</option>';
  if(!markaId) return;
  const r = await api({action:'get_modeller_tutanak',marka_id:markaId});
  if(r.success && r.data){
    r.data.forEach(m=>{
      const o = document.createElement('option');
      o.value = m.id;
      o.textContent = m.model_adi||m.ad||m.name||'';
      sel.appendChild(o);
    });
    if(autoSelect){
      for(const o of sel.options){
        if(o.textContent.toLowerCase()===(autoSelect+'').toLowerCase()){
          sel.value=o.value;
          break;
        }
      }
    }
  }
}

// --- Alıcı dropdown ---
let aliciT;
document.getElementById('aliciAramaInput').addEventListener('input',function(){
  clearTimeout(aliciT);
  aliciT = setTimeout(()=>aliciAra(this.value),280);
});
document.getElementById('aliciAramaInput').addEventListener('focus',function(){aliciAra(this.value);});
document.getElementById('aliciDd').addEventListener('mousedown',function(e){ e.preventDefault(); });
document.getElementById('aliciDd').addEventListener('click',function(e){
  const item = e.target.closest('[data-alici-id]');
  if(!item) return;
  aliciSec({
    id: item.dataset.aliciId,
    ad: item.dataset.aliciAd,
    tel: item.dataset.aliciTel||'',
    adres: item.dataset.aliciAdres||'',
    vergi_dairesi: item.dataset.aliciVd||'',
    vergi_no: item.dataset.aliciVn||''
  });
});
async function aliciAra(q){
  const dd = document.getElementById('aliciDd');
  const r = await api({action:'get_musteriler',arama:q.trim()});
  if(!r.success||!r.data||!r.data.length){
    dd.innerHTML='<div style="padding:10px;text-align:center;color:#9ca3af;font-size:12px">Müşteri bulunamadı</div>';
    dd.style.display='block';return;
  }
  dd.innerHTML = r.data.map(m=>
    `<div style="padding:9px 12px;cursor:pointer;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;font-size:13px"
       data-alici-id="${esc(m.id)}"
       data-alici-ad="${esc(m.ad||m.ad_soyad||'')}"
       data-alici-tel="${esc(m.telefon||'')}"
       data-alici-adres="${esc(m.adres||'')}"
       data-alici-vd="${esc(m.vergi_dairesi||'')}"
       data-alici-vn="${esc(m.vergi_no||'')}"
       onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
      <span>${escHtml(m.ad||m.ad_soyad||'')}</span>
      <span style="font-size:11px;color:#9ca3af">${escHtml(m.telefon||'')}</span>
    </div>`
  ).join('');
  dd.style.display='block';
}
function aliciSec(m){
  document.getElementById('alici_adi').value = m.ad||'';
  document.getElementById('alici_tel').value = m.tel||'';
  document.getElementById('alici_adres').value = m.adres||'';
  document.getElementById('alici_vergi_dairesi').value = m.vergi_dairesi||'';
  document.getElementById('alici_vergi_no').value = m.vergi_no||'';
  document.getElementById('aliciAramaInput').value = m.ad;
  document.getElementById('aliciDd').style.display='none';
  const b = document.getElementById('aliciBilgi');
  b.style.display='block';
  b.textContent = 'Yüklendi: '+(m.ad||'')+(m.vergi_dairesi?' | VD: '+m.vergi_dairesi:'')+(m.vergi_no?' | VN: '+m.vergi_no:'');
}
function yeniAliciAc(){
  document.getElementById('yeniAliciForm').style.display='block';
  const q=document.getElementById('aliciAramaInput').value.trim();
  if(q) document.getElementById('yaAd').value=q;
}
async function yeniAliciKaydet(){
  const ad=document.getElementById('yaAd').value.trim();
  if(!ad){alert('Ad Soyad boş olamaz');return;}
  const r=await api({action:'add_musteri',ad_soyad:ad,telefon:document.getElementById('yaTel').value,email:document.getElementById('yaEmail').value,adres:document.getElementById('yaAdres').value,vergi_dairesi:document.getElementById('yaVD').value,vergi_no:document.getElementById('yaVN').value,musteri_tipi:document.getElementById('yaTip').value},'POST');
  if(r.success){
    aliciSec({id:r.id,ad:r.ad||ad,tel:r.telefon||document.getElementById('yaTel').value,adres:r.adres||document.getElementById('yaAdres').value,vergi_dairesi:r.vergi_dairesi||document.getElementById('yaVD').value,vergi_no:r.vergi_no||document.getElementById('yaVN').value});
    document.getElementById('yeniAliciForm').style.display='none';
  } else alert(r.message||'Hata oluştu');
}
document.addEventListener('click',e=>{
  if(!e.target.closest('#aliciWrap')) document.getElementById('aliciDd').style.display='none';
});

// --- Edit mode ---
async function editModYukle(){
  if(!EDIT_ID) return;
  const r = await api({action:'get_devir',id:EDIT_ID});
  if(!r.success||!r.data){alert('Kayıt yüklenemedi');return;}
  const d = r.data;
  document.getElementById('tutanak_tarih').value = d.tarih||d.tutanak_tarih||'';
  document.getElementById('sira_no').value = d.sira_no||'';
  document.getElementById('gib_onay_no').value = d.gib_onay_kodu||d.gib_onay_no||'';
  document.getElementById('servis_adi').value = d.yetkili_servis_adi||d.servis_adi||'';
  document.getElementById('servis_adresi').value = d.yetkili_servis_adres||d.servis_adresi||'';
  document.getElementById('yetki_numarasi').value = d.yetki_numarasi||'';
  document.getElementById('muhur_numarasi').value = d.muhur_numarasi||'';
  document.getElementById('satici_adi').value = d.satici_adi||'';
  document.getElementById('satici_tel').value = d.satici_tel||'';
  document.getElementById('satici_vergi_dairesi').value = d.satici_vergi_dairesi||'';
  document.getElementById('satici_vergi_no').value = d.satici_vergi_no||'';
  document.getElementById('satici_adres').value = d.satici_adres||'';
  document.getElementById('alici_adi').value = d.alici_adi||'';
  document.getElementById('alici_tel').value = d.alici_tel||'';
  document.getElementById('alici_vergi_dairesi').value = d.alici_vergi_dairesi||'';
  document.getElementById('alici_vergi_no').value = d.alici_vergi_no||'';
  document.getElementById('alici_adres').value = d.alici_adres||'';
  // Cihaz marka/model: DB'de text olarak saklı
  const markaText = d.cihaz_marka||'';
  const modelText = d.cihaz_model||'';
  if(markaText){
    const mSel = document.getElementById('cihaz_marka_id');
    for(const o of mSel.options){
      if(o.textContent.toLowerCase()===markaText.toLowerCase()){
        mSel.value=o.value;
        await modellerYukle(o.value, modelText);
        break;
      }
    }
    await firmaByMarkaYukle(markaText, modelText);
  }
  document.getElementById('cihaz_sicil_no').value = d.cihaz_sicil_no||'';
  document.getElementById('ilk_z_tarihi').value = d.kullanim_baslangic_tarihi||'';
  document.getElementById('son_z_tarihi').value = d.son_kullanim_tarihi||'';
  document.getElementById('z_no').value = d.z_raporu_sayisi||'';
  document.getElementById('kum_kdv').value = d.toplam_kdv||'';
  document.getElementById('kum_top').value = d.toplam_hasilat||'';
  document.getElementById('fatura_tarih').value = d.satis_fatura_tarihi||'';
  document.getElementById('fatura_no').value = d.satis_fatura_no||'';
  document.getElementById('satis_bedeli').value = d.satis_bedeli||'';
  document.getElementById('diger_tespitler').value = d.diger_tespitler||'';
  document.getElementById('durum').value = d.durum||'taslak';
}

// --- Form kaydet ---
async function formKaydet(yazdir){
  const markaEl = document.getElementById('cihaz_marka_id');
  const modelEl = document.getElementById('cihaz_model_id');
  const params = {
    action: EDIT_ID ? 'update_devir' : 'add_devir',
    ...(EDIT_ID ? {id:EDIT_ID} : {}),
    tarih: document.getElementById('tutanak_tarih').value,
    gib_onay_kodu: document.getElementById('gib_onay_no').value,
    yetkili_servis_adi: document.getElementById('servis_adi').value,
    yetkili_servis_adres: document.getElementById('servis_adresi').value,
    yetki_numarasi: document.getElementById('yetki_numarasi').value,
    muhur_numarasi: document.getElementById('muhur_numarasi').value,
    satici_adi: document.getElementById('satici_adi').value,
    satici_tel: document.getElementById('satici_tel').value,
    satici_vergi_dairesi: document.getElementById('satici_vergi_dairesi').value,
    satici_vergi_no: document.getElementById('satici_vergi_no').value,
    satici_adres: document.getElementById('satici_adres').value,
    alici_adi: document.getElementById('alici_adi').value,
    alici_tel: document.getElementById('alici_tel').value,
    alici_vergi_dairesi: document.getElementById('alici_vergi_dairesi').value,
    alici_vergi_no: document.getElementById('alici_vergi_no').value,
    alici_adres: document.getElementById('alici_adres').value,
    cihaz_marka: markaEl.options[markaEl.selectedIndex]?.text||'',
    cihaz_model: modelEl.options[modelEl.selectedIndex]?.text||'',
    cihaz_sicil_no: document.getElementById('cihaz_sicil_no').value,
    kullanim_baslangic_tarihi: document.getElementById('ilk_z_tarihi').value,
    son_kullanim_tarihi: document.getElementById('son_z_tarihi').value,
    z_raporu_sayisi: document.getElementById('z_no').value,
    toplam_kdv: document.getElementById('kum_kdv').value,
    toplam_hasilat: document.getElementById('kum_top').value,
    satis_fatura_tarihi: document.getElementById('fatura_tarih').value,
    satis_fatura_no: document.getElementById('fatura_no').value,
    satis_bedeli: document.getElementById('satis_bedeli').value,
    diger_tespitler: document.getElementById('diger_tespitler').value,
    durum: document.getElementById('durum').value,
    servis_id: <?= $servis_id ?>
  };
  if(!params.satici_adi){alert('Satıcı adı zorunludur');return;}
  const r = await api(params,'POST');
  const el = document.getElementById('mesajAlani');
  if(r.success){
    const savedId = r.id || EDIT_ID;
    if(yazdir && savedId){
      window.location.href='tutanak_devir_yazdir.php?id='+savedId;
    } else {
      el.innerHTML='<div class="alert-success">Kayıt başarıyla '+(EDIT_ID?'güncellendi':'kaydedildi')+'. Yönlendiriliyor...</div>';
      setTimeout(()=>{ window.location.href='tutanak_devir_liste.php'; },1200);
    }
  } else {
    el.innerHTML='<div class="alert-error">'+(r.message||'Hata oluştu')+'</div>';
    window.scrollTo({top:0,behavior:'smooth'});
  }
}

// Helpers
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function escHtml(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Init
document.addEventListener('DOMContentLoaded',async ()=>{
  await markalarYukle();
  if(EDIT_ID) await editModYukle();
});
</script>
</body>
</html>
