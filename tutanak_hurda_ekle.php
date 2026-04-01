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
$mudahale_amaci_get   = isset($_GET['mudahale_amaci'])       ? htmlspecialchars($_GET['mudahale_amaci'])       : 'hurdaya_ayirma';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?= $edit_id ? 'EK-1 Hurda Tutanağı Düzenle' : 'EK-1 Hurda / Geçici Tutanak Ekle' ?></title>
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
.radio-grup{display:flex;flex-direction:column;gap:10px}
.radio-option{display:flex;align-items:flex-start;gap:10px;padding:12px;border:2px solid #e5e7eb;border-radius:8px;cursor:pointer;transition:.15s}
.radio-option:hover{border-color:#93c5fd;background:#eff6ff}
.radio-option.secili{border-color:#3b82f6;background:#eff6ff}
.radio-option input[type=radio]{margin-top:2px;accent-color:#3b82f6;width:16px;height:16px;flex-shrink:0}
.radio-option .r-label{font-size:13px;font-weight:600;color:#1e293b}
.radio-option .r-desc{font-size:11px;color:#6b7280;margin-top:2px}
.alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}
.alert-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}
@media(max-width:640px){.grid-2,.grid-3{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="sayfa-icerik">
<div class="sayfa-wrap">

<h2><?= $edit_id ? 'EK-1 Tutanağı Düzenle' : 'EK-1 Yeni Tutanak Ekle' ?></h2>
<div id="mesajAlani"></div>

<!-- TUTANAK BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Tutanak Bilgileri</div>
  <div class="grid-2">
    <div class="form-grup">
      <label>Tarih *</label>
      <input type="date" id="tutanak_tarih" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-grup">
      <label>Sıra No</label>
      <input type="text" id="sira_no" placeholder="Otomatik" readonly>
    </div>
  </div>
</div>

<!-- MÜDAHALe AMACI -->
<div class="kart">
  <div class="kart-baslik">Müdahale Amacı *</div>
  <div class="radio-grup" id="mudahaleRadioGrup">
    <label class="radio-option" onclick="radioSec(this,'hurdaya_ayirma')">
      <input type="radio" name="mudahale_amaci" value="hurdaya_ayirma"
        <?= $mudahale_amaci_get==='hurdaya_ayirma' ? 'checked' : '' ?>>
      <div>
        <div class="r-label">Hurdaya Ayırma</div>
        <div class="r-desc">Cihaz kullanım dışı bırakılarak hurdaya ayrılacak</div>
      </div>
    </label>
    <label class="radio-option" onclick="radioSec(this,'gecici_kullanim_disi')">
      <input type="radio" name="mudahale_amaci" value="gecici_kullanim_disi"
        <?= $mudahale_amaci_get==='gecici_kullanim_disi' ? 'checked' : '' ?>>
      <div>
        <div class="r-label">Geçici Kullanım Dışı Bırakma</div>
        <div class="r-desc">Cihaz geçici olarak kullanım dışı bırakılacak</div>
      </div>
    </label>
    <label class="radio-option" onclick="radioSec(this,'tekrar_kullanima_alma')">
      <input type="radio" name="mudahale_amaci" value="tekrar_kullanima_alma"
        <?= $mudahale_amaci_get==='tekrar_kullanima_alma' ? 'checked' : '' ?>>
      <div>
        <div class="r-label">Tekrar Kullanıma Alma</div>
        <div class="r-desc">Geçici olarak bekletilen cihaz tekrar aktif edilecek</div>
      </div>
    </label>
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

<!-- SATICI / MÜKELLEf BİLGİLERİ -->
<div class="kart">
  <div class="kart-baslik">Satıcı / Mükellef Bilgileri</div>
  <div class="grid-2">
    <div class="form-grup">
      <label>Ad / Firma Adı *</label>
      <input type="text" id="satici_adi" value="<?= $satici_adi ?>">
    </div>
    <div class="form-grup">
      <label>Telefon</label>
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
      <label>Adres</label>
      <input type="text" id="satici_adres" value="<?= $satici_adres ?>">
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
  <a href="tutanak_hurda_liste.php" class="btn btn-gray">İptal</a>
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

// Radio seçimi
function radioSec(el, val){
  document.querySelectorAll('.radio-option').forEach(r=>r.classList.remove('secili'));
  el.classList.add('secili');
  const inp = el.querySelector('input[type=radio]');
  if(inp) inp.checked=true;
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.radio-option').forEach(el=>{
    const inp = el.querySelector('input[type=radio]');
    if(inp && inp.checked) el.classList.add('secili');
  });
});

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
          sel.value=o.value;break;
        }
      }
    }
  }
}

// --- Edit mode ---
async function editModYukle(){
  if(!EDIT_ID) return;
  const r = await api({action:'get_hurda',id:EDIT_ID});
  if(!r.success||!r.data){alert('Kayıt yüklenemedi');return;}
  const d = r.data;
  document.getElementById('tutanak_tarih').value = d.tarih||d.tutanak_tarih||'';
  document.getElementById('sira_no').value = d.sira_no||'';
  document.querySelectorAll('input[name=mudahale_amaci]').forEach(inp=>{
    if(inp.value===d.mudahale_amaci){
      inp.checked=true;
      inp.closest('.radio-option')?.classList.add('secili');
    } else {
      inp.closest('.radio-option')?.classList.remove('secili');
    }
  });
  document.getElementById('servis_adi').value = d.yetkili_servis_adi||d.servis_adi||'';
  document.getElementById('servis_adresi').value = d.yetkili_servis_adres||d.servis_adresi||'';
  document.getElementById('yetki_numarasi').value = d.yetki_numarasi||'';
  document.getElementById('muhur_numarasi').value = d.muhur_numarasi||'';
  document.getElementById('satici_adi').value = d.satici_adi||'';
  document.getElementById('satici_tel').value = d.satici_tel||'';
  document.getElementById('satici_vergi_dairesi').value = d.satici_vergi_dairesi||'';
  document.getElementById('satici_vergi_no').value = d.satici_vergi_no||'';
  document.getElementById('satici_adres').value = d.satici_adres||'';
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
  document.getElementById('diger_tespitler').value = d.diger_tespitler||'';
  document.getElementById('durum').value = d.durum||'taslak';
}

// --- Form kaydet ---
async function formKaydet(yazdir){
  const markaEl = document.getElementById('cihaz_marka_id');
  const modelEl = document.getElementById('cihaz_model_id');
  const mudahaleInp = document.querySelector('input[name=mudahale_amaci]:checked');
  if(!mudahaleInp){alert('Müdahale amacı seçmelisiniz');return;}
  const params = {
    action: EDIT_ID ? 'update_hurda' : 'add_hurda',
    ...(EDIT_ID ? {id:EDIT_ID} : {}),
    mudahale_amaci: mudahaleInp.value,
    tarih: document.getElementById('tutanak_tarih').value,
    yetkili_servis_adi: document.getElementById('servis_adi').value,
    yetkili_servis_adres: document.getElementById('servis_adresi').value,
    yetki_numarasi: document.getElementById('yetki_numarasi').value,
    muhur_numarasi: document.getElementById('muhur_numarasi').value,
    satici_adi: document.getElementById('satici_adi').value,
    satici_tel: document.getElementById('satici_tel').value,
    satici_vergi_dairesi: document.getElementById('satici_vergi_dairesi').value,
    satici_vergi_no: document.getElementById('satici_vergi_no').value,
    satici_adres: document.getElementById('satici_adres').value,
    cihaz_marka: markaEl.options[markaEl.selectedIndex]?.text||'',
    cihaz_model: modelEl.options[modelEl.selectedIndex]?.text||'',
    cihaz_sicil_no: document.getElementById('cihaz_sicil_no').value,
    kullanim_baslangic_tarihi: document.getElementById('ilk_z_tarihi').value,
    son_kullanim_tarihi: document.getElementById('son_z_tarihi').value,
    z_raporu_sayisi: document.getElementById('z_no').value,
    toplam_kdv: document.getElementById('kum_kdv').value,
    toplam_hasilat: document.getElementById('kum_top').value,
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
      window.location.href='tutanak_hurda_yazdir.php?id='+savedId;
    } else {
      el.innerHTML='<div class="alert-success">Kayıt başarıyla '+(EDIT_ID?'güncellendi':'kaydedildi')+'. Yönlendiriliyor...</div>';
      setTimeout(()=>{ window.location.href='tutanak_hurda_liste.php'; },1200);
    }
  } else {
    el.innerHTML='<div class="alert-error">'+(r.message||'Hata oluştu')+'</div>';
    window.scrollTo({top:0,behavior:'smooth'});
  }
}

document.addEventListener('DOMContentLoaded',async ()=>{
  await markalarYukle();
  if(EDIT_ID) await editModYukle();
});
</script>
</body>
</html>
