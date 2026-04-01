<?php
require_once 'db.php';
require_once 'auth.php';
$hesaplar = $pdo->query("SELECT id, ad FROM odeme_hesaplari ORDER BY ad")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Personel Yönetimi</title>
<?php include 'menu.php'; ?>
<style>
/* ── Layout ── */
.per-wrap{max-width:1400px;margin:0 auto;padding:0 8px;}
.per-grid{display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start;}
@media(max-width:1100px){.per-grid{grid-template-columns:240px 1fr;}}
@media(max-width:768px){.per-grid{grid-template-columns:1fr;}}

/* ── Cards ── */
.per-kart{background:#fff;border-radius:14px;padding:18px;box-shadow:0 1px 8px rgba(0,0,0,.07);border:1px solid #e5e7eb;min-width:0;}

/* ── Personel listesi ── */
.per-sidebar{position:sticky;top:80px;}
.per-liste-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.per-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;border:2px solid transparent;transition:all .18s;}
.per-item:hover{background:#f0f9ff;border-color:#bae6fd;}
.per-item.aktif{background:#eff6ff;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.08);}
.per-avatar{width:38px;height:38px;border-radius:50%;background:#6d28d9;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;}
.per-isim{font-weight:600;font-size:13px;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.per-gorev{font-size:11px;color:#6b7280;margin-top:1px;}
.per-prim-badge{font-size:11px;color:#7c3aed;font-weight:700;white-space:nowrap;background:#f5f3ff;padding:2px 7px;border-radius:99px;}

/* ── Detay panel ── */
.detay-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;padding-bottom:14px;border-bottom:1px solid #f1f5f9;}
.detay-kimlik{display:flex;align-items:center;gap:12px;}
.detay-ad{font-size:17px;font-weight:800;color:#1e3a8a;}
.detay-alt{font-size:12px;color:#6b7280;margin-top:2px;}

/* ── Özet kartları ── */
.ozet-kutu{display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:16px;}
.ozet-kart{background:#f8faff;border-radius:10px;padding:12px;text-align:center;border:1px solid #e0e7ff;}
.ozet-kart .sayi{font-size:15px;font-weight:800;color:#1e40af;line-height:1.2;}
.ozet-kart .etiket{font-size:10px;color:#6b7280;margin-top:3px;white-space:nowrap;}
@media(max-width:960px){.ozet-kutu{grid-template-columns:repeat(3,1fr);}}
@media(max-width:500px){.ozet-kutu{grid-template-columns:repeat(2,1fr);}}

/* ── Sekmeler ── */
.sekme-bar{display:flex;gap:2px;border-bottom:2px solid #e5e7eb;margin-bottom:16px;overflow-x:auto;}
.sekme-btn{padding:8px 14px;border:none;background:none;cursor:pointer;font-size:12px;font-weight:600;color:#6b7280;border-bottom:3px solid transparent;margin-bottom:-2px;white-space:nowrap;transition:all .15s;}
.sekme-btn.aktif{color:#3b82f6;border-bottom-color:#3b82f6;}
.sekme-icerik{display:none;}.sekme-icerik.aktif{display:block;}

/* ── Tablo ── */
.tablo-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
table.per-tablo{width:100%;border-collapse:collapse;font-size:12.5px;min-width:500px;}
table.per-tablo th{background:#f8fafc;padding:9px 11px;text-align:left;font-weight:700;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.per-tablo td{padding:8px 11px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
table.per-tablo tr:last-child td{border-bottom:none;}
table.per-tablo tr:hover td{background:#fafbff;}

/* ── Formlar ── */
.form-satir{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px;}
.form-satir.tek{grid-template-columns:1fr;}
.fg{display:flex;flex-direction:column;gap:4px;}
.fg label{font-size:12px;font-weight:600;color:#374151;}
.fg input,.fg select,.fg textarea{padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;width:100%;box-sizing:border-box;transition:border .15s;}
.fg input:focus,.fg select:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}

/* ── Badges ── */
.badge-beklemede{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;}
.badge-odendi{background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;}
.badge-avans{background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:99px;font-size:11px;}
.badge-borc{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:99px;font-size:11px;}

/* ── Modals ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1200;align-items:center;justify-content:center;padding:16px;}
.modal-bg.ac{display:flex;}
.modal-kap{background:#fff;border-radius:16px;padding:26px 22px;width:500px;max-width:100%;box-shadow:0 8px 40px rgba(0,0,0,.18);max-height:90vh;overflow-y:auto;}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 15px;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:all .15s;}
.btn-primary{background:#3b82f6;color:#fff;}.btn-primary:hover{background:#2563eb;}
.btn-success{background:#10b981;color:#fff;}.btn-success:hover{background:#059669;}
.btn-danger{background:#ef4444;color:#fff;}.btn-danger:hover{background:#dc2626;}
.btn-warning{background:#f59e0b;color:#fff;}.btn-warning:hover{background:#d97706;}
.btn-gray{background:#f1f5f9;color:#374151;}.btn-gray:hover{background:#e2e8f0;}
.btn-sm{padding:3px 9px;font-size:11px;border-radius:6px;}
.text-right{text-align:right;}

/* ── Boş panel ── */
.bos-panel{text-align:center;padding:50px 20px;color:#9ca3af;}
.bos-panel .ikon{font-size:48px;margin-bottom:12px;}
.bos-panel p{font-size:14px;}

/* ── Info bar ── */
.info-bar{padding:11px 15px;border-radius:8px;font-size:12.5px;font-weight:600;margin-top:10px;}
</style>
</head>
<body>
<div class="sayfa-icerik">
<div class="per-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <h2 style="margin:0;font-size:20px;color:#1e3a8a;font-weight:800;">👥 Personel Yönetimi</h2>
    <button class="btn btn-primary" onclick="personelEkleModalAc()">➕ Yeni Personel</button>
  </div>

  <div class="per-grid">
    <!-- SOL: Personel Listesi -->
    <div class="per-sidebar">
      <div class="per-kart">
        <div class="per-liste-header">
          <span style="font-weight:700;font-size:14px;color:#374151;">Personeller</span>
          <span id="perSayi" style="font-size:11px;color:#9ca3af;background:#f1f5f9;padding:2px 8px;border-radius:99px;"></span>
        </div>
        <div id="personelListeKap">
          <div style="text-align:center;color:#9ca3af;padding:20px;font-size:13px;">Yükleniyor...</div>
        </div>
      </div>
    </div>

    <!-- SAĞ: Detay + Boş -->
    <div style="min-width:0;">
      <!-- Detay Paneli -->
      <div class="per-kart" id="detayPanel" style="display:none;">
        <div class="detay-header">
          <div class="detay-kimlik">
            <div class="per-avatar" id="detayAvatar">P</div>
            <div>
              <div class="detay-ad" id="detayAdi"></div>
              <div class="detay-alt" id="detayGorev"></div>
            </div>
          </div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-gray btn-sm" onclick="personelDuzenle()">✏️ Düzenle</button>
            <button class="btn btn-danger btn-sm" onclick="personelSil()">🗑 Sil</button>
          </div>
        </div>

        <!-- Özet -->
        <div class="ozet-kutu" id="detayOzet"></div>

        <!-- Sekmeler -->
        <div class="sekme-bar">
          <button class="sekme-btn aktif" onclick="sekmeAc('faturalar',this)">📄 Faturalar</button>
          <button class="sekme-btn" onclick="sekmeAc('primler',this)">💰 Primler</button>
          <button class="sekme-btn" onclick="sekmeAc('urunPrimler',this)">🏷️ Ürün Primleri</button>
          <button class="sekme-btn" onclick="sekmeAc('avanslar',this)">💳 Avans/Borç</button>
        </div>

        <!-- Faturalar -->
        <div class="sekme-icerik aktif" id="sekFaturalar">
          <div class="tablo-wrap">
            <table class="per-tablo">
              <thead><tr><th>Fatura No</th><th>Tarih</th><th>Müşteri</th><th class="text-right">Tutar</th><th>Durum</th></tr></thead>
              <tbody id="faturalarBody"><tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:20px;">Seçili personelin faturası yok.</td></tr></tbody>
            </table>
          </div>
        </div>

        <!-- Primler -->
        <div class="sekme-icerik" id="sekPrimler">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
            <span style="font-weight:600;color:#1e3a8a;font-size:13px;">Otomatik Hesaplanan Primler</span>
            <button class="btn btn-primary btn-sm" onclick="primEkleModalAc()">➕ Manuel Prim Ekle</button>
          </div>
          <div class="tablo-wrap">
            <table class="per-tablo">
              <thead><tr><th>Fatura</th><th>Ürün</th><th>Açıklama</th><th class="text-right">Kalem</th><th class="text-right">Oran/Sabit</th><th class="text-right">Prim</th><th>Durum</th><th></th></tr></thead>
              <tbody id="primlerBody"><tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Prim kaydı yok.</td></tr></tbody>
            </table>
          </div>
          <div id="primToplamBar" class="info-bar" style="display:none;background:#f0fdf4;color:#166534;"></div>
        </div>

        <!-- Ürün Primleri -->
        <div class="sekme-icerik" id="sekUrunPrimler">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <span style="font-weight:600;color:#1e3a8a;font-size:13px;">Ürün Bazlı Prim Tanımları</span>
            <button class="btn btn-primary btn-sm" onclick="urunPrimEkleModalAc()">➕ Ürün Prim Ekle</button>
          </div>
          <div class="tablo-wrap">
            <table class="per-tablo">
              <thead><tr><th>Ürün</th><th>Kod</th><th class="text-right">Satış Fiyatı</th><th class="text-right">Prim Oranı %</th><th class="text-right">Sabit Tutar ₺</th><th style="width:80px;"></th></tr></thead>
              <tbody id="urunPrimlerBody"><tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:20px;">Ürün prim tanımı yok.</td></tr></tbody>
            </table>
          </div>
          <div class="info-bar" style="background:#eff6ff;color:#1e40af;margin-top:10px;">
            💡 Fatura kaydedilirken bu listedeki ürünler için tanımlı prim/tutar otomatik uygulanır. Listede olmayan ürünler için genel prim oranı kullanılır.
          </div>
        </div>

        <!-- Avans/Borç -->
        <div class="sekme-icerik" id="sekAvanslar">
          <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:10px;">
            <button class="btn btn-primary btn-sm" onclick="avansModalAc('avans')">💸 Avans Ver</button>
            <button class="btn btn-danger btn-sm" onclick="avansModalAc('borc')">📌 Borçlandır</button>
          </div>
          <div class="tablo-wrap">
            <table class="per-tablo">
              <thead><tr><th>Tarih</th><th>Tip</th><th class="text-right">Tutar</th><th>Hesap</th><th>Açıklama</th></tr></thead>
              <tbody id="avanslarBody"><tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:20px;">Kayıt yok.</td></tr></tbody>
            </table>
          </div>
          <div id="avansToplamBar" class="info-bar" style="display:none;background:#fef3c7;color:#92400e;"></div>
        </div>
      </div>

      <!-- Boş panel -->
      <div class="per-kart bos-panel" id="bosDetay">
        <div class="ikon">👤</div>
        <p>Soldan bir personel seçin</p>
      </div>
    </div>
  </div>
</div>
</div>

<!-- PERSONEL EKLE/DÜZENLE MODAL -->
<div class="modal-bg" id="personelModal">
  <div class="modal-kap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <h3 style="margin:0;font-size:16px;color:#1e3a8a;" id="personelModalBaslik">Yeni Personel</h3>
      <button onclick="modalKapat('personelModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
    </div>
    <input type="hidden" id="pmId" value="">
    <div class="form-satir">
      <div class="fg"><label>Ad Soyad *</label><input type="text" id="pmAdSoyad" placeholder="Ad Soyad"></div>
      <div class="fg"><label>Görev</label><input type="text" id="pmGorev" placeholder="Örn: Satış, Teknisyen"></div>
    </div>
    <div class="form-satir">
      <div class="fg"><label>Telefon</label><input type="tel" id="pmTel" placeholder="0555..."></div>
      <div class="fg"><label>E-posta</label><input type="email" id="pmEmail" placeholder="ornek@mail.com"></div>
    </div>
    <div class="form-satir">
      <div class="fg"><label>Prim Oranı (%)</label><input type="number" id="pmPrimOrani" placeholder="0" step="0.01" min="0" max="100"></div>
      <div class="fg"><label>Durum</label>
        <select id="pmAktif"><option value="1">Aktif</option><option value="0">Pasif</option></select>
      </div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
      <button class="btn btn-gray" onclick="modalKapat('personelModal')">İptal</button>
      <button class="btn btn-primary" onclick="personelKaydet()">💾 Kaydet</button>
    </div>
  </div>
</div>

<!-- ÜRÜN PRİM MODAL -->
<div class="modal-bg" id="urunPrimModal">
  <div class="modal-kap" style="width:460px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <h3 style="margin:0;font-size:16px;color:#1e3a8a;">🏷️ Ürün Prim Tanımı</h3>
      <button onclick="modalKapat('urunPrimModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
    </div>
    <div class="form-satir tek">
      <div class="fg">
        <label>Ürün *</label>
        <input type="text" id="upUrunAra" placeholder="Ürün adı veya kodu yazın..." autocomplete="off" oninput="upAra(this)">
        <div id="upDrop" style="background:#fff;border:1.5px solid #3b82f6;border-radius:8px;max-height:200px;overflow-y:auto;display:none;"></div>
        <input type="hidden" id="upUrunId">
      </div>
    </div>
    <div class="form-satir">
      <div class="fg">
        <label>Prim Oranı %</label>
        <input type="number" id="upPrimOrani" placeholder="0.00" step="0.01" min="0" max="100"
               oninput="document.getElementById('upPrimSabit').value=''">
        <small style="color:#6b7280;">Ürün satış tutarının yüzdesi</small>
      </div>
      <div class="fg">
        <label>veya Sabit Tutar ₺</label>
        <input type="number" id="upPrimSabit" placeholder="0.00" step="0.01" min="0"
               oninput="document.getElementById('upPrimOrani').value=''">
        <small style="color:#6b7280;">Her satışta sabit prim tutarı</small>
      </div>
    </div>
    <div style="background:#fef3c7;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:14px;">
      ⚠️ Oran veya sabit tutar girin — ikisini birden doldurmayın. Sabit tutar girilirse oran göz ardı edilir.
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;">
      <button class="btn btn-gray" onclick="modalKapat('urunPrimModal')">İptal</button>
      <button class="btn btn-primary" onclick="urunPrimKaydet()">💾 Kaydet</button>
    </div>
  </div>
</div>

<!-- PRİM EKLE MODAL -->
<div class="modal-bg" id="primModal">
  <div class="modal-kap" style="width:420px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <h3 style="margin:0;font-size:16px;color:#1e3a8a;">💰 Prim Ekle</h3>
      <button onclick="modalKapat('primModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
    </div>
    <div class="form-satir tek">
      <div class="fg"><label>Fatura (opsiyonel)</label>
        <select id="primFaturaId"><option value="">— Faturasız Manuel Prim —</option></select>
      </div>
    </div>
    <div class="form-satir">
      <div class="fg"><label>Prim Oranı %</label><input type="number" id="primOrani" placeholder="0" step="0.01" min="0"></div>
      <div class="fg"><label>veya Sabit Tutar ₺</label><input type="number" id="primTutar" placeholder="0.00" step="0.01" min="0"></div>
    </div>
    <div class="form-satir tek">
      <div class="fg"><label>Açıklama</label><input type="text" id="primAciklama" placeholder="Opsiyonel açıklama"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
      <button class="btn btn-gray" onclick="modalKapat('primModal')">İptal</button>
      <button class="btn btn-success" onclick="primKaydet()">💾 Prim Kaydet</button>
    </div>
  </div>
</div>

<!-- PRİM ÖDE MODAL -->
<div class="modal-bg" id="primOdeModal">
  <div class="modal-kap" style="width:380px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <h3 style="margin:0;font-size:16px;color:#1e3a8a;">💸 Prim Öde</h3>
      <button onclick="modalKapat('primOdeModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
    </div>
    <input type="hidden" id="odePrimId">
    <div class="form-satir tek">
      <div class="fg"><label>Kasa / Banka</label>
        <select id="odeHesapId">
          <option value="">— Hesap Seçin (opsiyonel) —</option>
          <?php foreach ($hesaplar as $h): ?>
          <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['ad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-satir tek">
      <div class="fg"><label>Ödeme Tarihi</label><input type="date" id="odeTarih" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
      <button class="btn btn-gray" onclick="modalKapat('primOdeModal')">İptal</button>
      <button class="btn btn-success" onclick="primOdemeYap()">✅ Ödendi Olarak İşle</button>
    </div>
  </div>
</div>

<!-- AVANS/BORÇ MODAL -->
<div class="modal-bg" id="avansModal">
  <div class="modal-kap" style="width:420px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <h3 style="margin:0;font-size:16px;color:#1e3a8a;" id="avansModalBaslik">💸 Avans Ver</h3>
      <button onclick="modalKapat('avansModal')" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
    </div>
    <input type="hidden" id="avansTip" value="avans">
    <div class="form-satir">
      <div class="fg"><label>Tutar ₺ *</label><input type="number" id="avansTutar" placeholder="0.00" step="0.01" min="0.01"></div>
      <div class="fg"><label>Tarih *</label><input type="date" id="avansTarih" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="form-satir tek">
      <div class="fg"><label>Kasa / Banka</label>
        <select id="avansHesapId">
          <option value="">— Hesap Seçin (opsiyonel) —</option>
          <?php foreach ($hesaplar as $h): ?>
          <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['ad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-satir tek">
      <div class="fg"><label>Açıklama</label><input type="text" id="avansAciklama" placeholder="Opsiyonel"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
      <button class="btn btn-gray" onclick="modalKapat('avansModal')">İptal</button>
      <button class="btn btn-primary" onclick="avansKaydet()">💾 Kaydet</button>
    </div>
  </div>
</div>

<script>
let aktifPersonelId = null;
let personellerCache = [];

function para(n){ return new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(n||0); }
function tarih(d){ if(!d) return '—'; const p=d.split('-'); return p[2]+'.'+p[1]+'.'+p[0]; }
function esc(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

function modalKapat(id){ document.getElementById(id).classList.remove('ac'); }

// ── Personel Listesi ────────────────────────────────
function personelListeYukle() {
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=listele'})
        .then(r=>r.json()).then(v=>{
            personellerCache = v.personeller || [];
            const kap = document.getElementById('personelListeKap');
            if (!personellerCache.length) {
                kap.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:20px;">Henüz personel yok.<br><br><button class="btn btn-primary" onclick="personelEkleModalAc()">➕ İlk Personeli Ekle</button></div>';
                return;
            }
            kap.innerHTML = personellerCache.map(p=>`
                <div class="per-item" id="perItem${p.id}" onclick="personelSec(${p.id})">
                    <div class="per-avatar" style="background:${renk(p.ad_soyad)}">${p.ad_soyad.charAt(0).toUpperCase()}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:14px;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(p.ad_soyad)}</div>
                        <div style="font-size:11px;color:#6b7280;">${esc(p.gorev||'—')} ${p.aktif==1?'<span style="color:#10b981;">●</span>':'<span style="color:#ef4444;">● Pasif</span>'}</div>
                    </div>
                    <div style="font-size:12px;color:#6d28d9;font-weight:700;">%${parseFloat(p.prim_orani||0).toFixed(1)}</div>
                </div>`).join('');
        });
}

function renk(s){ const clrs=['#6d28d9','#0369a1','#065f46','#9a3412','#1e40af','#7c3aed']; let h=0; for(let c of (s||'')) h=(h*31+c.charCodeAt(0))%clrs.length; return clrs[h]; }

function personelSec(id) {
    aktifPersonelId = id;
    document.querySelectorAll('.per-item').forEach(el=>el.classList.remove('aktif'));
    const el = document.getElementById('perItem'+id);
    if (el) el.classList.add('aktif');
    const p = personellerCache.find(x=>x.id==id);
    if (!p) return;
    document.getElementById('detayAdi').textContent = p.ad_soyad;
    document.getElementById('detayGorev').textContent = (p.gorev||'') + (p.email ? ' • '+p.email : '') + (p.telefon ? ' • '+p.telefon : '');
    document.getElementById('detayAvatar').textContent = p.ad_soyad.charAt(0).toUpperCase();
    document.getElementById('detayAvatar').style.background = renk(p.ad_soyad);
    document.getElementById('detayPanel').style.display = 'block';
    document.getElementById('bosDetay').style.display = 'none';
    detayOzetYukle(id);
    faturaTabloYukle(id);
}

function detayOzetYukle(id) {
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'ozet',personel_id:id}).toString()})
        .then(r=>r.json()).then(v=>{
            document.getElementById('detayOzet').innerHTML = `
                <div class="ozet-kart"><div class="sayi">${v.fatura_adet||0}</div><div class="etiket">Toplam Fatura</div></div>
                <div class="ozet-kart"><div class="sayi" style="font-size:16px;">${para(v.toplam_ciro)}</div><div class="etiket">Toplam Ciro</div></div>
                <div class="ozet-kart"><div class="sayi" style="color:#f59e0b;font-size:16px;">${para(v.bekleyen_prim)}</div><div class="etiket">Bekleyen Prim</div></div>
                <div class="ozet-kart"><div class="sayi" style="color:#10b981;font-size:16px;">${para(v.odenen_prim)}</div><div class="etiket">Ödenen Prim</div></div>
                <div class="ozet-kart"><div class="sayi" style="color:#3b82f6;font-size:16px;">${para(v.toplam_avans)}</div><div class="etiket">Toplam Avans</div></div>
                <div class="ozet-kart"><div class="sayi" style="color:#ef4444;font-size:16px;">${para(v.toplam_borc)}</div><div class="etiket">Toplam Borç</div></div>
            `;
        });
}

function faturaTabloYukle(id) {
    fetch('personel_kontrol.php?action=faturalar&personel_id='+id)
        .then(r=>r.json()).then(v=>{
            const rows = v.faturalar || [];
            const tb = document.getElementById('faturalarBody');
            if (!rows.length) { tb.innerHTML='<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:20px;">Fatura yok.</td></tr>'; return; }
            tb.innerHTML = rows.map(f=>`<tr>
                <td><a href="fatura_liste.php" style="color:#3b82f6;font-weight:600;">${esc(f.fatura_no)}</a></td>
                <td>${tarih(f.tarih)}</td>
                <td>${esc(f.musteri_adi)}</td>
                <td class="text-right">${para(f.toplam)}</td>
                <td>${f.odeme_durumu==='odendi'?'<span class="badge-odendi">✅ Ödendi</span>':f.odeme_durumu==='kismi'?'<span class="badge-beklemede">⚠️ Kısmi</span>':'<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;">⛔ Ödenmedi</span>'}</td>
            </tr>`).join('');
        });
}

function primTabloYukle(id) {
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'prim_listele',personel_id:id}).toString()})
        .then(r=>r.json()).then(v=>{
            const rows = v.primler || [];
            const tb = document.getElementById('primlerBody');
            if (!rows.length) { tb.innerHTML='<tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:20px;">Prim kaydı yok.</td></tr>'; document.getElementById('primToplamBar').style.display='none'; return; }
            let bekl=0, ode=0;
            tb.innerHTML = rows.map(p=>{
                const bk=p.odeme_durumu==='beklemede'; if(bk)bekl+=parseFloat(p.prim_tutari); else ode+=parseFloat(p.prim_tutari);
                const oranBilgi = p.urun_adi ? (p.prim_orani && p.prim_orani>0 ? `%${parseFloat(p.prim_orani).toFixed(1)}` : `₺${parseFloat(p.prim_tutari).toFixed(2)} sabit`) : `%${parseFloat(p.prim_orani||0).toFixed(1)}`;
                const kalemTutar = p.kalem_tutari ? para(p.kalem_tutari) : '—';
                return `<tr>
                    <td>${p.fatura_no?esc(p.fatura_no):'<span style="color:#9ca3af;">Manuel</span>'}</td>
                    <td>${p.urun_adi?`<span style="font-size:12px;background:#eff6ff;color:#1e40af;padding:2px 6px;border-radius:4px;">${esc(p.urun_adi)}</span>`:'<span style="color:#9ca3af;font-size:12px;">Genel</span>'}</td>
                    <td>${esc(p.aciklama||'—')}</td>
                    <td class="text-right" style="font-size:12px;color:#6b7280;">${kalemTutar}</td>
                    <td class="text-right">${oranBilgi}</td>
                    <td class="text-right" style="font-weight:700;">${para(p.prim_tutari)}</td>
                    <td>${bk?'<span class="badge-beklemede">⏳ Beklemede</span>':'<span class="badge-odendi">✅ Ödendi</span>'}</td>
                    <td>${bk?`<button class="btn btn-success btn-sm" onclick="primOdeModalAc(${p.id})">Öde</button>`:'—'}</td>
                </tr>`;
            }).join('');
            const bar = document.getElementById('primToplamBar');
            bar.style.display='block';
            bar.innerHTML=`Bekleyen: <span style="color:#b45309;">${para(bekl)}</span> &nbsp;|&nbsp; Ödenen: <span style="color:#065f46;">${para(ode)}</span>`;
        });
}

function avansTabloYukle(id) {
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'avans_listele',personel_id:id}).toString()})
        .then(r=>r.json()).then(v=>{
            const rows = v.avanslar || [];
            const tb = document.getElementById('avanslarBody');
            if (!rows.length) { tb.innerHTML='<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:20px;">Kayıt yok.</td></tr>'; document.getElementById('avansToplamBar').style.display='none'; return; }
            let tavans=0, tborc=0;
            tb.innerHTML = rows.map(a=>{
                if(a.tip==='avans')tavans+=parseFloat(a.tutar); else tborc+=parseFloat(a.tutar);
                return `<tr>
                    <td>${tarih(a.tarih)}</td>
                    <td>${a.tip==='avans'?'<span class="badge-avans">💸 Avans</span>':'<span class="badge-borc">📌 Borç</span>'}</td>
                    <td class="text-right" style="font-weight:700;">${para(a.tutar)}</td>
                    <td>${esc(a.hesap_adi||'—')}</td>
                    <td>${esc(a.aciklama||'—')}</td>
                </tr>`;
            }).join('');
            const bar = document.getElementById('avansToplamBar');
            bar.style.display='block';
            bar.innerHTML=`Toplam Avans: <span style="color:#1e40af;">${para(tavans)}</span> &nbsp;|&nbsp; Toplam Borç: <span style="color:#991b1b;">${para(tborc)}</span>`;
        });
}

function sekmeAc(isim, btn) {
    document.querySelectorAll('.sekme-btn').forEach(b=>b.classList.remove('aktif'));
    document.querySelectorAll('.sekme-icerik').forEach(el=>el.classList.remove('aktif'));
    btn.classList.add('aktif');
    document.getElementById('sek'+isim.charAt(0).toUpperCase()+isim.slice(1)).classList.add('aktif');
    if (isim==='primler' && aktifPersonelId) primTabloYukle(aktifPersonelId);
    if (isim==='avanslar' && aktifPersonelId) avansTabloYukle(aktifPersonelId);
    if (isim==='urunPrimler' && aktifPersonelId) urunPrimTabloYukle(aktifPersonelId);
}

// ── Personel EKLE/GÜNCELLE ─────────────────────────
function personelEkleModalAc() {
    document.getElementById('personelModalBaslik').textContent = 'Yeni Personel';
    document.getElementById('pmId').value = '';
    ['pmAdSoyad','pmGorev','pmTel','pmEmail'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('pmPrimOrani').value = '0';
    document.getElementById('pmAktif').value = '1';
    document.getElementById('personelModal').classList.add('ac');
}

function personelDuzenle() {
    const p = personellerCache.find(x=>x.id==aktifPersonelId);
    if (!p) return;
    document.getElementById('personelModalBaslik').textContent = 'Personeli Düzenle';
    document.getElementById('pmId').value = p.id;
    document.getElementById('pmAdSoyad').value = p.ad_soyad;
    document.getElementById('pmGorev').value = p.gorev||'';
    document.getElementById('pmTel').value = p.telefon||'';
    document.getElementById('pmEmail').value = p.email||'';
    document.getElementById('pmPrimOrani').value = p.prim_orani||0;
    document.getElementById('pmAktif').value = p.aktif;
    document.getElementById('personelModal').classList.add('ac');
}

function personelKaydet() {
    const id = document.getElementById('pmId').value;
    const body = new URLSearchParams({
        action: id ? 'guncelle' : 'ekle',
        id, ad_soyad: document.getElementById('pmAdSoyad').value,
        gorev: document.getElementById('pmGorev').value,
        telefon: document.getElementById('pmTel').value,
        email: document.getElementById('pmEmail').value,
        prim_orani: document.getElementById('pmPrimOrani').value,
        aktif: document.getElementById('pmAktif').value,
    });
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString()})
        .then(r=>r.json()).then(v=>{
            if (v.basari) { modalKapat('personelModal'); personelListeYukle(); if(aktifPersonelId) setTimeout(()=>personelSec(aktifPersonelId),500); }
            else alert(v.mesaj||'Hata!');
        });
}

function personelSil() {
    if (!aktifPersonelId || !confirm('Bu personeli silmek istediğinizden emin misiniz?')) return;
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'sil',id:aktifPersonelId}).toString()})
        .then(r=>r.json()).then(v=>{
            if (v.basari) { aktifPersonelId=null; document.getElementById('detayPanel').style.display='none'; document.getElementById('bosDetay').style.display='block'; personelListeYukle(); }
            else alert(v.mesaj||'Hata!');
        });
}

// ── Prim ────────────────────────────────────────────
function primEkleModalAc() {
    document.getElementById('primFaturaId').innerHTML = '<option value="">— Faturasız Manuel Prim —</option>';
    document.getElementById('primOrani').value = '';
    document.getElementById('primTutar').value = '';
    document.getElementById('primAciklama').value = '';
    const p = personellerCache.find(x=>x.id==aktifPersonelId);
    if (p && p.prim_orani) document.getElementById('primOrani').value = p.prim_orani;
    // Personelin faturalarını yükle
    fetch('personel_kontrol.php?action=faturalar&personel_id='+aktifPersonelId)
        .then(r=>r.json()).then(v=>{
            const sel = document.getElementById('primFaturaId');
            (v.faturalar||[]).forEach(f=>{
                const o = document.createElement('option');
                o.value = f.id; o.dataset.tutar = f.toplam;
                o.textContent = f.fatura_no + ' — ' + new Intl.NumberFormat('tr-TR',{style:'currency',currency:'TRY'}).format(f.toplam);
                sel.appendChild(o);
            });
        });
    document.getElementById('primModal').classList.add('ac');
}

function primKaydet() {
    const fatSel = document.getElementById('primFaturaId');
    const fatId = fatSel.value;
    const fatTutar = fatId ? parseFloat(fatSel.options[fatSel.selectedIndex].dataset.tutar||0) : 0;
    const oran = parseFloat(document.getElementById('primOrani').value||0);
    const sabitTutar = parseFloat(document.getElementById('primTutar').value||0);
    const body = new URLSearchParams({
        action:'prim_hesapla', personel_id:aktifPersonelId,
        fatura_id:fatId, prim_orani:oran,
        prim_tutari:sabitTutar||0, aciklama:document.getElementById('primAciklama').value
    });
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString()})
        .then(r=>r.json()).then(v=>{
            if (v.basari) { modalKapat('primModal'); primTabloYukle(aktifPersonelId); detayOzetYukle(aktifPersonelId); }
            else alert(v.mesaj||'Hata!');
        });
}

function primOdeModalAc(primId) {
    document.getElementById('odePrimId').value = primId;
    document.getElementById('odeTarih').value = new Date().toISOString().split('T')[0];
    document.getElementById('primOdeModal').classList.add('ac');
}

function primOdemeYap() {
    const body = new URLSearchParams({
        action:'prim_ode', prim_id:document.getElementById('odePrimId').value,
        hesap_id:document.getElementById('odeHesapId').value,
        tarih:document.getElementById('odeTarih').value
    });
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString()})
        .then(r=>r.json()).then(v=>{
            if (v.basari) { modalKapat('primOdeModal'); primTabloYukle(aktifPersonelId); detayOzetYukle(aktifPersonelId); }
            else alert(v.mesaj||'Hata!');
        });
}

// ── Avans/Borç ──────────────────────────────────────
function avansModalAc(tip) {
    document.getElementById('avansTip').value = tip;
    document.getElementById('avansModalBaslik').textContent = tip==='avans' ? '💸 Avans Ver' : '📌 Borçlandır';
    document.getElementById('avansTutar').value = '';
    document.getElementById('avansAciklama').value = '';
    document.getElementById('avansTarih').value = new Date().toISOString().split('T')[0];
    document.getElementById('avansModal').classList.add('ac');
}

function avansKaydet() {
    const body = new URLSearchParams({
        action:'avans_ekle', personel_id:aktifPersonelId,
        tip:document.getElementById('avansTip').value,
        tutar:document.getElementById('avansTutar').value,
        tarih:document.getElementById('avansTarih').value,
        hesap_id:document.getElementById('avansHesapId').value,
        aciklama:document.getElementById('avansAciklama').value
    });
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString()})
        .then(r=>r.json()).then(v=>{
            if (v.basari) { modalKapat('avansModal'); avansTabloYukle(aktifPersonelId); detayOzetYukle(aktifPersonelId); }
            else alert(v.mesaj||'Hata!');
        });
}

// ── Init ────────────────────────────────────────────
personelListeYukle();

// ── Ürün Prim ───────────────────────────────────────
let upUrunlerCache = [];
fetch('urun_kontrol.php?action=liste_json').then(r=>r.json()).then(d=>{ if(d.basari) upUrunlerCache = d.urunler||[]; });

function urunPrimTabloYukle(pid) {
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'urun_prim_listele', personel_id:pid}).toString()})
    .then(r=>r.json()).then(v=>{
        const rows = v.primler||[];
        const tb = document.getElementById('urunPrimlerBody');
        if (!rows.length) { tb.innerHTML='<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:20px;">Ürün prim tanımı yok.</td></tr>'; return; }
        tb.innerHTML = rows.map(p=>`<tr>
            <td style="font-weight:600;">${esc(p.urun_adi)}</td>
            <td style="color:#6b7280;font-size:12px;">${esc(p.urun_kodu||'—')}</td>
            <td class="text-right">${para(p.satis_fiyati)}</td>
            <td class="text-right">${p.prim_orani!=null ? `<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:99px;font-size:12px;font-weight:700;">%${parseFloat(p.prim_orani).toFixed(2)}</span>` : '—'}</td>
            <td class="text-right">${p.prim_sabit_tutar!=null ? `<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:99px;font-size:12px;font-weight:700;">${para(p.prim_sabit_tutar)}</span>` : '—'}</td>
            <td style="text-align:right;">
              <button class="btn btn-warning btn-sm" onclick="urunPrimDuzenle(${p.id},'${esc(p.urun_adi)}',${p.urun_id},${p.prim_orani??''},${p.prim_sabit_tutar??''})">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="urunPrimSil(${p.id})">🗑️</button>
            </td>
        </tr>`).join('');
    });
}

function urunPrimEkleModalAc() {
    document.getElementById('upUrunAra').value = '';
    document.getElementById('upUrunId').value = '';
    document.getElementById('upPrimOrani').value = '';
    document.getElementById('upPrimSabit').value = '';
    document.getElementById('urunPrimModal').classList.add('ac');
}

function urunPrimDuzenle(id, ad, uid, oran, sabit) {
    document.getElementById('upUrunAra').value = ad;
    document.getElementById('upUrunId').value = uid;
    document.getElementById('upPrimOrani').value = oran||'';
    document.getElementById('upPrimSabit').value = sabit||'';
    document.getElementById('urunPrimModal').classList.add('ac');
}

function urunPrimSil(id) {
    if (!confirm('Bu ürün prim tanımını silmek istiyor musunuz?')) return;
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'urun_prim_sil', id}).toString()})
    .then(r=>r.json()).then(v=>{ if(v.basari) urunPrimTabloYukle(aktifPersonelId); else alert(v.mesaj); });
}

function urunPrimKaydet() {
    const uid = document.getElementById('upUrunId').value;
    if (!uid) { alert('Lütfen bir ürün seçin.'); return; }
    const oran = document.getElementById('upPrimOrani').value.trim();
    const sabit = document.getElementById('upPrimSabit').value.trim();
    if (!oran && !sabit) { alert('Prim oranı veya sabit tutar girilmeli.'); return; }
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'urun_prim_kaydet', personel_id:aktifPersonelId, urun_id:uid, prim_orani:oran, prim_sabit_tutar:sabit}).toString()})
    .then(r=>r.json()).then(v=>{
        if (v.basari) { modalKapat('urunPrimModal'); urunPrimTabloYukle(aktifPersonelId); }
        else alert(v.mesaj);
    });
}

function upAra(inp) {
    const q = inp.value.trim().toLowerCase();
    const drop = document.getElementById('upDrop');
    if (q.length < 1) { drop.style.display='none'; return; }
    const hits = upUrunlerCache.filter(u=> (u.urun_adi||u.ad||'').toLowerCase().includes(q) || (u.urun_kodu||'').toLowerCase().includes(q)).slice(0,8);
    if (!hits.length) { drop.style.display='none'; return; }
    drop.style.display = 'block';
    drop.innerHTML = hits.map(u=>`<div style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;"
        onmousedown="upSec(${u.id},'${(u.urun_adi||u.ad||'').replace(/'/g,'').replace(/"/g,'')}')">
        <span>${esc(u.urun_adi||u.ad)}</span>
        <small style="color:#6b7280;">${esc(u.urun_kodu||'')}</small>
    </div>`).join('');
}

function upSec(id, ad) {
    document.getElementById('upUrunId').value = id;
    document.getElementById('upUrunAra').value = ad;
    document.getElementById('upDrop').style.display = 'none';
}
</script>
</body>
</html>
