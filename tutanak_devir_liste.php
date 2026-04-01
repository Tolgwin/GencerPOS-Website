<?php require_once 'db.php';
require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>EK-2 Devir Satış Tutanakları</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#1e293b;display:flex;min-height:100vh}
.tablo-wrap{overflow-x:auto}
h2{font-size:20px;font-weight:700;color:#1e3a8a;margin-bottom:18px}
.filtre-kart{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);padding:16px;margin-bottom:18px}
.filtre-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px;align-items:end}
.f-grup label{font-size:11px;font-weight:600;color:#6b7280;display:block;margin-bottom:3px}
.f-grup input,.f-grup select{width:100%;padding:8px 10px;border:1px solid #dde3f0;border-radius:7px;font-size:13px}
.btn{padding:8px 16px;border:none;border-radius:7px;cursor:pointer;font-size:12px;font-weight:600;transition:.15s;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn-blue{background:#3b82f6;color:#fff}.btn-blue:hover{background:#2563eb}
.btn-green{background:#10b981;color:#fff}.btn-green:hover{background:#059669}
.btn-orange{background:#f59e0b;color:#fff}.btn-orange:hover{background:#d97706}
.btn-red{background:#ef4444;color:#fff}.btn-red:hover{background:#dc2626}
.btn-gray{background:#e5e7eb;color:#374151}.btn-gray:hover{background:#d1d5db}
.btn-wa{background:#25d366;color:#fff}.btn-wa:hover{background:#1da851}
.btn-sm{padding:5px 10px;font-size:11px;border-radius:5px}
.ozet-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.ozet-kart{background:#fff;border-radius:10px;padding:14px;box-shadow:0 1px 4px rgba(0,0,0,.07);text-align:center}
.ozet-sayi{font-size:24px;font-weight:800;color:#1e3a8a}
.ozet-etiket{font-size:11px;color:#6b7280;margin-top:2px}
.tablo-wrap{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden}
table{width:100%;border-collapse:collapse}
thead th{background:#1e3a8a;color:#fff;padding:10px 12px;text-align:left;font-size:12px;font-weight:600;white-space:nowrap}
tbody tr:hover{background:#f8faff}
tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;font-size:12.5px;vertical-align:middle}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;white-space:nowrap}
.badge-tamamlandi{background:#d1fae5;color:#065f46}
.badge-taslak{background:#fef3c7;color:#92400e}
.badge-iptal{background:#fee2e2;color:#991b1b}
.islem-grup{display:flex;gap:4px;flex-wrap:wrap}
.modal-arka{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px;display:none}
.modal-arka.acik{display:flex}
.modal-kutu{background:#fff;border-radius:12px;max-width:900px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 8px 40px rgba(0,0,0,.2)}
.modal-kutu.sm{max-width:500px}
.modal-baslik{padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}
.modal-baslik h3{font-size:16px;font-weight:700;color:#1e3a8a}
.modal-kapat{background:none;border:none;font-size:20px;cursor:pointer;color:#6b7280;padding:4px}
.modal-govde{overflow-y:auto;padding:20px;flex:1}
.detay-section{margin-bottom:16px}
.detay-section-baslik{font-size:12px;font-weight:700;color:#1e3a8a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;padding-bottom:5px;border-bottom:2px solid #e5e7eb}
.detay-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.detay-alan{display:flex;flex-direction:column;gap:2px}
.detay-etiket{font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase}
.detay-deger{font-size:13px;color:#111827;font-weight:500}
.modal-altlik{padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.sms-tel-kutu{background:#f0fdf4;border:2px solid #d1fae5;border-radius:8px;padding:12px;margin-bottom:10px;cursor:pointer;transition:.15s}
.sms-tel-kutu:hover,.sms-tel-kutu.secili{background:#d1fae5;border-color:#10b981}
.sms-tel-kutu .tel{font-size:16px;font-weight:700;color:#065f46}
.sms-tel-kutu .isim{font-size:12px;color:#374151;margin-bottom:3px}
.bos-state{text-align:center;padding:50px 20px;color:#9ca3af}
.sayfalama{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid #f1f5f9}
@media(max-width:900px){.filtre-grid{grid-template-columns:1fr 1fr}.ozet-grid{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.filtre-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'menu.php'; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
  <h2>EK-2: Devir Satış Tutanakları</h2>
  <a href="tutanak_devir_ekle.php" class="btn btn-blue">+ Yeni Tutanak</a>
</div>

<div class="filtre-kart">
  <div class="filtre-grid">
    <div class="f-grup"><label>Arama</label><input type="text" id="aramaInp" placeholder="Satıcı, alıcı, sicil, marka..."></div>
    <div class="f-grup"><label>Başlangıç Tarihi</label><input type="date" id="tarihBasInp"></div>
    <div class="f-grup"><label>Bitiş Tarihi</label><input type="date" id="tarihBitInp"></div>
    <div class="f-grup"><label>Durum</label><select id="durumInp"><option value="">Tümü</option><option value="taslak">Taslak</option><option value="tamamlandi">Tamamlandı</option><option value="iptal">İptal</option></select></div>
    <div class="f-grup"><label>&nbsp;</label><button class="btn btn-blue" onclick="listeyiYukle()">Ara</button></div>
  </div>
</div>

<div class="ozet-grid">
  <div class="ozet-kart"><div class="ozet-sayi" id="ozetToplam">-</div><div class="ozet-etiket">Toplam Tutanak</div></div>
  <div class="ozet-kart"><div class="ozet-sayi" id="ozetBuAy">-</div><div class="ozet-etiket">Bu Ay</div></div>
  <div class="ozet-kart"><div class="ozet-sayi" id="ozetTamamlandi">-</div><div class="ozet-etiket">Tamamlandı</div></div>
  <div class="ozet-kart"><div class="ozet-sayi" id="ozetTaslak">-</div><div class="ozet-etiket">Taslak</div></div>
</div>

<div class="tablo-wrap">
  <table>
    <thead>
      <tr>
        <th>Sıra No</th><th>Tarih</th><th>Satıcı</th><th>Alıcı</th>
        <th>Cihaz</th><th>Sicil No</th><th>Z No / Küm Top</th><th>Durum</th>
        <th style="min-width:300px">İşlemler</th>
      </tr>
    </thead>
    <tbody id="tabloBeden"><tr><td colspan="9" class="bos-state">⏳ Yükleniyor...</td></tr></tbody>
  </table>
  <div class="sayfalama">
    <div id="sayfalamaBilgi" style="font-size:12px;color:#6b7280"></div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-gray btn-sm" id="prevBtn" onclick="sayfa('prev')">◀ Önceki</button>
      <button class="btn btn-gray btn-sm" id="nextBtn" onclick="sayfa('sonra')">Sonraki ▶</button>
    </div>
  </div>
</div>
</div>

<!-- DETAY MODAL -->
<div class="modal-arka" id="detayModal">
  <div class="modal-kutu">
    <div class="modal-baslik"><h3>EK-2 Tutanak — <span id="detaySiraNo"></span></h3><button class="modal-kapat" onclick="kapat('detayModal')">✕</button></div>
    <div class="modal-govde" id="detayIcerik"></div>
    <div class="modal-altlik" id="detayAltlik"></div>
  </div>
</div>

<!-- SMS MODAL -->
<div class="modal-arka" id="smsModal">
  <div class="modal-kutu sm">
    <div class="modal-baslik"><h3>💬 WhatsApp / SMS Bildirim</h3><button class="modal-kapat" onclick="kapat('smsModal')">✕</button></div>
    <div class="modal-govde">
      <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:8px">Alıcıyı Seç (Alıcı veya Satıcı):</div>
      <div id="smsAliciKutu" class="sms-tel-kutu" onclick="smsTelSec(this,'alici')">
        <div class="isim">Alıcı</div><div class="tel" id="smsAliciTel">—</div>
      </div>
      <div id="smsSaticiKutu" class="sms-tel-kutu" onclick="smsTelSec(this,'satici')">
        <div class="isim">Satıcı</div><div class="tel" id="smsSaticiTel">—</div>
      </div>
      <div style="margin-top:12px">
        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px">Mesaj (Düzenlenebilir)</label>
        <textarea id="smsMesajText" rows="8" style="width:100%;padding:9px;border:1px solid #dde3f0;border-radius:7px;font-size:12px;resize:vertical"></textarea>
      </div>
      <div style="margin-top:8px;padding:8px 10px;background:#f9fafb;border-radius:6px;font-size:11px;color:#6b7280">
        Gönderilecek numara: <strong id="smsSeciliNumara" style="color:#065f46">Seçilmedi</strong>
      </div>
    </div>
    <div class="modal-altlik">
      <button class="btn btn-gray" onclick="kapat('smsModal')">İptal</button>
      <button class="btn btn-gray" onclick="mesajiKopyala()">📋 Kopyala</button>
      <button class="btn btn-wa" onclick="whatsappGonder()">💬 WhatsApp</button>
    </div>
  </div>
</div>

<!-- SİL MODAL -->
<div class="modal-arka" id="silModal">
  <div class="modal-kutu sm">
    <div class="modal-baslik"><h3>Tutanak Sil</h3><button class="modal-kapat" onclick="kapat('silModal')">✕</button></div>
    <div class="modal-govde"><p style="font-size:14px">Bu tutanağı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.</p></div>
    <div class="modal-altlik"><button class="btn btn-gray" onclick="kapat('silModal')">İptal</button><button class="btn btn-red" id="silOnayBtn">Evet, Sil</button></div>
  </div>
</div>

<script>
'use strict';
const BASE = 'tutanak_kontrol.php';
const BASE_URL = window.location.origin + window.location.pathname.replace(/[^/]+$/, '');
let smsAktifTutanak = null;
let smsTelTipi = 'alici';
let offsetVal = 0;
const LIMIT = 50;

const SMS_SABLON = `Sayın {KISI_ADI},

{SIRA_NO} numaralı EK-2 Devir Satış Tutanağı düzenlenmiştir.

📅 Tarih: {TARIH}
🔧 Cihaz: {MARKA} {MODEL}
🔢 Sicil No: {SICIL_NO}

Tutanağınızı görüntülemek için:
{LINK}

Saygılarımızla,
{SERVIS_ADI}`;

async function api(p){ const r=await fetch(BASE+'?'+new URLSearchParams(p)); return r.json(); }

async function listeyiYukle(sifirlaOffset=true){
  if(sifirlaOffset) offsetVal=0;
  const p={action:'get_devir_liste',arama:document.getElementById('aramaInp').value.trim(),tarih_bas:document.getElementById('tarihBasInp').value,tarih_bit:document.getElementById('tarihBitInp').value,durum:document.getElementById('durumInp').value,limit:LIMIT,offset:offsetVal};
  document.getElementById('tabloBeden').innerHTML='<tr><td colspan="9" class="bos-state">⏳ Yükleniyor...</td></tr>';
  const r=await api(p);
  if(!r.success){ document.getElementById('tabloBeden').innerHTML='<tr><td colspan="9" style="text-align:center;color:red;padding:20px">Hata oluştu.</td></tr>'; return; }
  renderTablo(r.data||[]);
  const t=r.toplam||0;
  document.getElementById('sayfalamaBilgi').textContent=`${offsetVal+1}–${Math.min(offsetVal+(r.data||[]).length,t)} / ${t} kayıt`;
  document.getElementById('prevBtn').disabled=offsetVal<=0;
  document.getElementById('nextBtn').disabled=offsetVal+LIMIT>=t;
  ozetYukle();
}

async function ozetYukle(){
  const ay=new Date().toISOString().slice(0,7)+'-01';
  const [rT,rB,rTam,rTas]=await Promise.all([api({action:'get_devir_liste',limit:1,offset:0}),api({action:'get_devir_liste',limit:1,offset:0,tarih_bas:ay}),api({action:'get_devir_liste',limit:1,offset:0,durum:'tamamlandi'}),api({action:'get_devir_liste',limit:1,offset:0,durum:'taslak'})]);
  document.getElementById('ozetToplam').textContent=rT.toplam||0;
  document.getElementById('ozetBuAy').textContent=rB.toplam||0;
  document.getElementById('ozetTamamlandi').textContent=rTam.toplam||0;
  document.getElementById('ozetTaslak').textContent=rTas.toplam||0;
}

function sayfa(y){ if(y==='prev'&&offsetVal>0) offsetVal=Math.max(0,offsetVal-LIMIT); if(y==='sonra') offsetVal+=LIMIT; listeyiYukle(false); }

const durumBadge={tamamlandi:'badge-tamamlandi',taslak:'badge-taslak',iptal:'badge-iptal'};
const durumLabel={tamamlandi:'Tamamlandı',taslak:'Taslak',iptal:'İptal'};
function fT(t){ if(!t) return '—'; try{return new Date(t+'T12:00:00').toLocaleDateString('tr-TR');}catch(e){return t;} }
function fP(v){ if(!v&&v!==0) return '—'; return Number(v).toLocaleString('tr-TR',{minimumFractionDigits:2})+' ₺'; }
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function renderTablo(data){
  const tb=document.getElementById('tabloBeden');
  if(!data.length){ tb.innerHTML='<tr><td colspan="9" class="bos-state"><div style="font-size:36px">📋</div><div>Kayıt bulunamadı.</div></td></tr>'; return; }
  tb.innerHTML=data.map(d=>`<tr>
    <td><strong>${esc(d.sira_no)}</strong></td>
    <td>${fT(d.tarih)}</td>
    <td title="${esc(d.satici_adi)}" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(d.satici_adi)||'—'}</td>
    <td title="${esc(d.alici_adi)}" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(d.alici_adi)||'—'}</td>
    <td><strong>${esc(d.cihaz_marka||'—')}</strong><br><small style="color:#6b7280">${esc(d.cihaz_model||'')}</small></td>
    <td>${esc(d.cihaz_sicil_no)||'—'}</td>
    <td><small style="font-weight:600">${esc(d.z_raporu_sayisi||'—')}</small><br><small style="color:#10b981">${fP(d.toplam_hasilat)}</small></td>
    <td><span class="badge ${durumBadge[d.durum||'taslak']||'badge-taslak'}">${durumLabel[d.durum||'taslak']||d.durum||'Taslak'}</span></td>
    <td><div class="islem-grup">
      <button class="btn btn-gray btn-sm" onclick="detayGoster(${d.id})" title="Detay">🔍</button>
      <a href="tutanak_devir_yazdir.php?id=${d.id}" target="_blank" class="btn btn-blue btn-sm" title="Yazdır/PDF">🖨️</a>
      <button class="btn btn-wa btn-sm" onclick="smsAc(${d.id})" title="WhatsApp/SMS">💬</button>
      ${d.fatura_id
        ? `<span class="btn btn-sm" style="background:#d1fae5;color:#065f46;cursor:default;opacity:.7" title="Fatura oluşturuldu: #${d.fatura_id}">✅ Faturalı</span>`
        : `<a href="fatura_ekle.php?from_tutanak=devir&tutanak_id=${d.id}" class="btn btn-green btn-sm" title="Fatura Oluştur">🧾 Fatura</a>`}
      <a href="tutanak_devir_ekle.php?edit_id=${d.id}" class="btn btn-gray btn-sm" title="Düzenle">✏️</a>
      <button class="btn btn-red btn-sm" onclick="silOnayla(${d.id})" title="Sil">🗑️</button>
    </div></td>
  </tr>`).join('');
}

async function detayGoster(id){
  const r=await api({action:'get_devir',id}); if(!r.success) return;
  const d=r.data;
  document.getElementById('detaySiraNo').textContent=d.sira_no||'';
  const marka=d.cihaz_marka||d.cihaz_marka_adi||''; const model=d.cihaz_model||d.cihaz_model_adi||'';
  const zNo=d.z_raporu_sayisi||d.z_raporu_no||''; const kumKDV=d.toplam_kdv||d.kdv_toplam||''; const kumTop=d.toplam_hasilat||d.gt_tutari||'';
  const fNo=d.satis_fatura_no||d.fatura_no||''; const fTarih=d.satis_fatura_tarihi||d.fatura_tarih||'';
  const gibNo=d.gib_onay_kodu||d.gib_onay_no||''; const servisAdi=d.yetkili_servis_adi||d.servis_adi||'';
  const ilkZ=d.kullanim_baslangic_tarihi||d.ilk_z_tarihi||''; const sonZ=d.son_kullanim_tarihi||d.son_z_tarihi||'';
  document.getElementById('detayIcerik').innerHTML=`
    <div class="detay-section"><div class="detay-section-baslik">Tutanak Bilgileri</div><div class="detay-grid">
      <div class="detay-alan"><span class="detay-etiket">Sıra No</span><span class="detay-deger">${esc(d.sira_no||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Tarih</span><span class="detay-deger">${fT(d.tarih)||'—'}</span></div>
      <div class="detay-alan"><span class="detay-etiket">GİB Onay Kodu</span><span class="detay-deger">${esc(gibNo)||'—'}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Durum</span><span class="detay-deger"><span class="badge ${durumBadge[d.durum||'taslak']||'badge-taslak'}">${durumLabel[d.durum||'taslak']||d.durum||'Taslak'}</span></span></div>
    </div></div>
    <div class="detay-section"><div class="detay-section-baslik">Yetkili Servis</div><div class="detay-grid">
      <div class="detay-alan" style="grid-column:1/-1"><span class="detay-etiket">Ünvan</span><span class="detay-deger">${esc(servisAdi)||'—'}</span></div>
      <div class="detay-alan" style="grid-column:1/-1"><span class="detay-etiket">Adres</span><span class="detay-deger">${esc(d.yetkili_servis_adres||d.servis_adresi||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Yetki No</span><span class="detay-deger">${esc(d.yetki_numarasi||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Mühür No</span><span class="detay-deger">${esc(d.muhur_numarasi||'—')}</span></div>
    </div></div>
    <div class="detay-section"><div class="detay-section-baslik">Satıcı Bilgileri</div><div class="detay-grid">
      <div class="detay-alan"><span class="detay-etiket">Ad / Ünvan</span><span class="detay-deger">${esc(d.satici_adi||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Telefon</span><span class="detay-deger">${esc(d.satici_tel||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Vergi Dairesi</span><span class="detay-deger">${esc(d.satici_vergi_dairesi||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Vergi No</span><span class="detay-deger">${esc(d.satici_vergi_no||'—')}</span></div>
      <div class="detay-alan" style="grid-column:1/-1"><span class="detay-etiket">Adres</span><span class="detay-deger">${esc(d.satici_adres||'—')}</span></div>
    </div></div>
    <div class="detay-section"><div class="detay-section-baslik">Alıcı Bilgileri</div><div class="detay-grid">
      <div class="detay-alan"><span class="detay-etiket">Ad / Ünvan</span><span class="detay-deger">${esc(d.alici_adi||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Telefon</span><span class="detay-deger">${esc(d.alici_tel||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Vergi Dairesi</span><span class="detay-deger">${esc(d.alici_vergi_dairesi||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Vergi No</span><span class="detay-deger">${esc(d.alici_vergi_no||'—')}</span></div>
      <div class="detay-alan" style="grid-column:1/-1"><span class="detay-etiket">Adres</span><span class="detay-deger">${esc(d.alici_adres||'—')}</span></div>
    </div></div>
    <div class="detay-section"><div class="detay-section-baslik">Cihaz Bilgileri</div><div class="detay-grid">
      <div class="detay-alan"><span class="detay-etiket">Marka</span><span class="detay-deger">${esc(marka||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Model</span><span class="detay-deger">${esc(model||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Sicil No</span><span class="detay-deger">${esc(d.cihaz_sicil_no||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">İlk Z No Tarihi</span><span class="detay-deger">${fT(ilkZ)||'—'}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Son Z No Tarihi</span><span class="detay-deger">${fT(sonZ)||'—'}</span></div>
    </div></div>
    <div class="detay-section"><div class="detay-section-baslik">Z Raporu</div><div class="detay-grid">
      <div class="detay-alan"><span class="detay-etiket">Z No</span><span class="detay-deger">${esc(zNo||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Küm KDV</span><span class="detay-deger">${fP(kumKDV)}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Küm Top</span><span class="detay-deger">${fP(kumTop)}</span></div>
    </div></div>
    <div class="detay-section"><div class="detay-section-baslik">Satış Fatura</div><div class="detay-grid">
      <div class="detay-alan"><span class="detay-etiket">Fatura No</span><span class="detay-deger">${esc(fNo||'—')}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Fatura Tarihi</span><span class="detay-deger">${fT(fTarih)||'—'}</span></div>
      <div class="detay-alan"><span class="detay-etiket">Satış Bedeli</span><span class="detay-deger">${fP(d.satis_bedeli)}</span></div>
    </div></div>
    ${d.diger_tespitler?`<div class="detay-section"><div class="detay-section-baslik">Diğer Tespitler</div><div style="font-size:13px;white-space:pre-wrap">${esc(d.diger_tespitler)}</div></div>`:''}
  `;
  document.getElementById('detayAltlik').innerHTML=`
    <button class="btn btn-gray" onclick="kapat('detayModal')">Kapat</button>
    <button class="btn btn-wa" onclick="smsAc(${d.id});kapat('detayModal')">💬 WhatsApp/SMS</button>
    <a href="tutanak_devir_yazdir.php?id=${d.id}" target="_blank" class="btn btn-blue">🖨️ Yazdır</a>
    <a href="tutanak_devir_ekle.php?edit_id=${d.id}" class="btn btn-gray">✏️ Düzenle</a>
    ${d.fatura_id
      ? `<span class="btn" style="background:#d1fae5;color:#065f46;cursor:default" title="Fatura No: #${d.fatura_id}">✅ Fatura Oluşturuldu</span>`
      : `<a href="fatura_ekle.php?from_tutanak=devir&tutanak_id=${d.id}" class="btn btn-green">🧾 Fatura Oluştur</a>`}
  `;
  document.getElementById('detayModal').classList.add('acik');
}

async function smsAc(id){
  const r=await api({action:'get_devir',id}); if(!r.success) return;
  const d=r.data; smsAktifTutanak=d; smsTelTipi='alici';
  const aliciTel=d.alici_tel||''; const saticiTel=d.satici_tel||'';
  document.getElementById('smsAliciTel').textContent=aliciTel||'(Telefon girilmemiş)';
  document.getElementById('smsSaticiTel').textContent=saticiTel||'(Telefon girilmemiş)';
  document.getElementById('smsAliciKutu').classList.add('secili');
  document.getElementById('smsSaticiKutu').classList.remove('secili');
  document.getElementById('smsSeciliNumara').textContent=aliciTel||'Seçilmedi';
  const marka=d.cihaz_marka||d.cihaz_marka_adi||''; const model=d.cihaz_model||d.cihaz_model_adi||'';
  const link=BASE_URL+'tutanak_devir_yazdir.php?id='+id;
  const servisAdi=d.yetkili_servis_adi||d.servis_adi||'Yetkili Servis';
  const tarihStr=d.tarih?new Date(d.tarih+'T12:00:00').toLocaleDateString('tr-TR'):'';
  const mesaj=SMS_SABLON.replace('{KISI_ADI}',d.alici_adi||'Müşterimiz').replace('{SIRA_NO}',d.sira_no||'').replace('{TARIH}',tarihStr).replace('{MARKA}',marka).replace('{MODEL}',model).replace('{SICIL_NO}',d.cihaz_sicil_no||'').replace('{LINK}',link).replace('{SERVIS_ADI}',servisAdi);
  document.getElementById('smsMesajText').value=mesaj;
  document.getElementById('smsModal').classList.add('acik');
}

function smsTelSec(el,tip){
  smsTelTipi=tip;
  document.querySelectorAll('.sms-tel-kutu').forEach(k=>k.classList.remove('secili')); el.classList.add('secili');
  const tel=tip==='alici'?(smsAktifTutanak?.alici_tel||''):(smsAktifTutanak?.satici_tel||'');
  document.getElementById('smsSeciliNumara').textContent=tel||'Telefon yok';
  if(smsAktifTutanak){
    const link=BASE_URL+'tutanak_devir_yazdir.php?id='+smsAktifTutanak.id;
    const marka=smsAktifTutanak.cihaz_marka||smsAktifTutanak.cihaz_marka_adi||''; const model=smsAktifTutanak.cihaz_model||smsAktifTutanak.cihaz_model_adi||'';
    const servisAdi=smsAktifTutanak.yetkili_servis_adi||smsAktifTutanak.servis_adi||'Yetkili Servis';
    const kisiAdi=tip==='alici'?(smsAktifTutanak.alici_adi||'Müşterimiz'):(smsAktifTutanak.satici_adi||'Satıcı');
    const tarihStr=smsAktifTutanak.tarih?new Date(smsAktifTutanak.tarih+'T12:00:00').toLocaleDateString('tr-TR'):'';
    document.getElementById('smsMesajText').value=SMS_SABLON.replace('{KISI_ADI}',kisiAdi).replace('{SIRA_NO}',smsAktifTutanak.sira_no||'').replace('{TARIH}',tarihStr).replace('{MARKA}',marka).replace('{MODEL}',model).replace('{SICIL_NO}',smsAktifTutanak.cihaz_sicil_no||'').replace('{LINK}',link).replace('{SERVIS_ADI}',servisAdi);
  }
}

function whatsappGonder(){
  const tel=smsTelTipi==='alici'?(smsAktifTutanak?.alici_tel||''):(smsAktifTutanak?.satici_tel||'');
  if(!tel){alert('Telefon numarası bulunamadı!');return;}
  const temizTel=tel.replace(/[^0-9+]/g,'').replace(/^0/,'90');
  window.open('https://wa.me/'+temizTel+'?text='+encodeURIComponent(document.getElementById('smsMesajText').value),'_blank');
}

function mesajiKopyala(){
  const m=document.getElementById('smsMesajText').value;
  navigator.clipboard.writeText(m).then(()=>alert('Mesaj panoya kopyalandı!')).catch(()=>{document.getElementById('smsMesajText').select();document.execCommand('copy');alert('Kopyalandı!');});
}

function silOnayla(id){
  document.getElementById('silOnayBtn').onclick=async()=>{
    const r=await fetch(BASE,{method:'POST',body:new URLSearchParams({action:'delete_devir',id})}).then(r=>r.json());
    kapat('silModal'); if(r.success) listeyiYukle(); else alert('Hata: '+(r.message||''));
  };
  document.getElementById('silModal').classList.add('acik');
}

function kapat(id){ document.getElementById(id).classList.remove('acik'); }
document.querySelectorAll('.modal-arka').forEach(m=>m.addEventListener('click',e=>{if(e.target===m) m.classList.remove('acik');}));
document.addEventListener('keydown',e=>{if(e.key==='Escape') document.querySelectorAll('.modal-arka.acik').forEach(m=>m.classList.remove('acik'));});
document.getElementById('aramaInp').addEventListener('keydown',e=>{if(e.key==='Enter') listeyiYukle();});
document.addEventListener('DOMContentLoaded',()=>listeyiYukle());
</script>
</body>
</html>
