<?php
require_once 'db.php';
require_once 'auth.php';
$iller        = $pdo->query("SELECT DISTINCT il FROM vergi_daireleri ORDER BY il")->fetchAll(PDO::FETCH_COLUMN);
$vdTum        = $pdo->query("SELECT * FROM vergi_daireleri ORDER BY il, ad")->fetchAll();
$etiketlerTum = $pdo->query("SELECT * FROM etiketler ORDER BY ad")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Servis Takip</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#222}
.sayfa-wrap{max-width:1400px;margin:0 auto;padding:24px 16px}
h1{font-size:22px;font-weight:700;margin-bottom:20px;color:#1e3a8a}
.btn{padding:8px 18px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-success{background:#10b981;color:#fff}.btn-success:hover{background:#059669}
.btn-warning{background:#f59e0b;color:#fff}.btn-warning:hover{background:#d97706}
.btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626}
.btn-gray{background:#e5e7eb;color:#374151}.btn-gray:hover{background:#d1d5db}
.btn-sm{padding:5px 12px;font-size:12px}.btn-xs{padding:3px 9px;font-size:11px}
.filtre-bar{display:flex;gap:10px;flex-wrap:wrap;background:#fff;padding:14px 16px;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:20px;align-items:center}
.filtre-bar input,.filtre-bar select{padding:8px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none}
.filtre-bar input:focus,.filtre-bar select:focus{border-color:#3b82f6}
.dashboard-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:20px}
.dash-kart{background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.08);border-left:4px solid #667eea;cursor:pointer;transition:.15s}
.dash-kart:hover{transform:translateY(-2px)}
.dash-kart.aktif-filtre{box-shadow:0 0 0 2px #3b82f6}
.dash-kart .sayi{font-size:22px;font-weight:700}
.dash-kart .ad{font-size:11px;color:#6b7280;margin-top:3px}
.tablo-wrap{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{background:#f8faff;padding:11px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;white-space:nowrap}
tbody td{padding:10px 14px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
tbody tr:hover{background:#f8faff}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px}
.modal-bg.ac{display:flex}
.modal{background:#fff;border-radius:14px;width:700px;max-width:96vw;box-shadow:0 20px 60px rgba(0,0,0,.25);margin:auto}
.modal-sm{width:480px}
.modal-header{padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center}
.modal-header h2{font-size:16px;font-weight:700;color:#1e3a8a}
.modal-body{padding:20px}
.modal-footer{padding:14px 20px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.form-row.tek{grid-template-columns:1fr}
.form-row.uc{grid-template-columns:1fr 1fr 1fr}
.form-grup{display:flex;flex-direction:column;gap:5px}
.form-grup label{font-size:12px;font-weight:600;color:#374151}
.form-grup input,.form-grup select,.form-grup textarea{padding:9px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none;font-family:inherit}
.form-grup input:focus,.form-grup select:focus,.form-grup textarea:focus{border-color:#3b82f6}
.form-grup textarea{resize:vertical;min-height:65px}
/* Müşteri arama */
.musteri-arama-kart{background:linear-gradient(135deg,#eff6ff,#f0f9ff);border:2px solid #bfdbfe;border-radius:12px;padding:18px 20px;margin-bottom:12px;}
.musteri-arama-label{font-size:12px;font-weight:700;color:#1e3a8a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.musteri-arama-hint{font-size:11px;color:#6b7280;margin-top:5px;}
.musteri-arama-hint span{background:#e0e7ff;color:#3730a3;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700;margin-right:4px;}
.autocomplete-wrap{position:relative}
.autocomplete-wrap input{width:100%}
.musteriAramaInputBig{width:100%;padding:14px 16px 14px 44px;border:2px solid #c7d2fe;border-radius:10px;font-size:15px;font-weight:500;outline:none;background:#fff;transition:.2s;color:#1e293b;}
.musteriAramaInputBig:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
.musteriAramaInputBig::placeholder{color:#9ca3af;font-size:14px;}
.musteri-ara-ikon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:18px;pointer-events:none;}
.ac-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1.5px solid #dde3f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);max-height:280px;overflow-y:auto;z-index:2000;display:none;}
.ac-dropdown .ac-item{padding:11px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;}
.ac-dropdown .ac-item:last-child{border-bottom:none;}
.ac-dropdown .ac-item:hover,.ac-dropdown .ac-item.hover{background:#eff6ff;}
.ac-dropdown .ac-item .ac-ad{font-weight:600;color:#1e293b;}
.ac-dropdown .ac-item .ac-meta{display:flex;flex-direction:column;align-items:flex-end;gap:2px;}
.ac-dropdown .ac-sub{font-size:11px;color:#9ca3af;}
.ac-dropdown .ac-vkn{font-size:11px;color:#6366f1;background:#eef2ff;border-radius:4px;padding:1px 5px;}
.musteri-secili-kutu{display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1.5px solid #6ee7b7;border-radius:10px;padding:12px 16px;font-size:13px;display:none;}
.musteri-secili-kutu .ms-adi{font-weight:700;color:#065f46;font-size:14px;}
.musteri-secili-kutu .ms-tel{font-size:12px;color:#047857;margin-left:8px;}
.musteri-secili-kutu .ms-vkn{font-size:11px;color:#059669;background:#d1fae5;border-radius:4px;padding:1px 6px;margin-left:6px;}
.musteri-secili-kutu .ms-kapat{cursor:pointer;color:#ef4444;font-size:18px;font-weight:700;padding:0 4px;margin-left:8px;transition:.15s;}.ms-kapat:hover{color:#dc2626;}
/* Ürün arama */
.stok-dusuk{color:#f59e0b}
.stok-yok{color:#ef4444}
/* Parça tablosu */
.parca-tablo{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}
.parca-tablo th{background:#f8faff;padding:7px 8px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb}
.parca-tablo td{padding:6px 8px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
.parca-tablo input[type=number]{width:65px;padding:4px 6px;border:1px solid #dde3f0;border-radius:5px;font-size:12px;outline:none}
.parca-tablo input[type=number]:focus{border-color:#3b82f6}
.parca-toplam-satir{display:flex;justify-content:flex-end;padding:8px 0 0;gap:8px;align-items:center}
.bos-mesaj{text-align:center;padding:40px;color:#9ca3af;font-size:14px}
.pagination{display:flex;gap:8px;justify-content:flex-end;padding:14px 16px;align-items:center}
.pagination button{padding:6px 14px;border:1px solid #dde3f0;border-radius:6px;background:#fff;cursor:pointer;font-size:13px}
.pagination button.aktif{background:#3b82f6;color:#fff;border-color:#3b82f6}
.pagination button:disabled{opacity:.4;cursor:default}
.alert{padding:10px 16px;border-radius:7px;font-size:13px;margin-bottom:16px;display:none}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
.alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.bolum-baslik{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 8px;padding-bottom:4px;border-bottom:1px solid #f3f4f6}
</style>
</head>
<body>
<?php include 'menu.php'; ?>

<div class="sayfa-wrap">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1>&#128295; Servis Takip</h1>
  <div style="display:flex;gap:8px">
    <a href="servis_ayarlar.php" class="btn btn-gray btn-sm">&#9881; Servis Ayarları</a>
    <button class="btn btn-primary" onclick="servisEkleAc()">+ Yeni Servis</button>
  </div>
</div>

<div id="alertBox" class="alert"></div>

<div class="dashboard-grid" id="dashboardGrid"></div>

<div class="filtre-bar">
  <input type="text" id="aramaInput" placeholder="Müşteri, seri no, marka, model..." style="flex:1;min-width:200px" oninput="aramaYap()">
  <select id="durumFiltre" onchange="aramaYap()"><option value="">Tüm Durumlar</option></select>
  <button class="btn btn-gray" onclick="filtreTemizle()">Temizle</button>
</div>

<div class="tablo-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>Müşteri</th><th>Cihaz</th><th>Marka / Model</th><th>Seri No</th><th>Şikayet</th><th>Durum</th><th>Tutar</th><th>Kayıt</th><th>İşlem</th></tr>
    </thead>
    <tbody id="servislerBody"><tr><td colspan="10" class="bos-mesaj">Yükleniyor...</td></tr></tbody>
  </table>
  <div class="pagination" id="pagination"></div>
</div>
</div>

<!-- ═══ SERVİS EKLE/DÜZENLE ═══════════════════════════════════════════ -->
<div class="modal-bg" id="servisModal">
<div class="modal">
  <div class="modal-header">
    <h2 id="modalBaslik">Yeni Servis</h2>
    <button class="btn btn-gray btn-sm" onclick="modalKapat('servisModal')">✕</button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="servisId">

    <div class="bolum-baslik">Müşteri</div>
    <div class="musteri-arama-kart">
      <div class="musteri-arama-label">👤 Müşteri Seçimi</div>
      <div class="autocomplete-wrap" id="musteriAramaWrap">
        <span class="musteri-ara-ikon">🔍</span>
        <input type="text" id="musteriAramaInput" class="musteriAramaInputBig"
               placeholder="Ad, telefon, vergi no veya cihaz seri no ile ara..." autocomplete="off">
        <div class="ac-dropdown" id="musteriDd"></div>
      </div>
      <div class="musteri-arama-hint">
        <span>Ad/Soyad</span><span>Telefon</span><span>Vergi No</span><span>Seri No</span>
        ile arama yapabilirsiniz
      </div>
      <div style="margin-top:8px;display:flex;align-items:center;gap:8px" id="musteriAltRow">
        <small style="color:#9ca3af">Bulunamadı?</small>
        <button class="btn btn-success btn-xs" type="button" onclick="yeniMusteriAc()">+ Yeni Müşteri Ekle</button>
      </div>
    </div>
    <div class="musteri-secili-kutu" id="musteriSeciliKutu"></div>
    <input type="hidden" id="musteriId">

    <div class="bolum-baslik">Cihaz</div>
    <div class="form-row uc">
      <div class="form-grup"><label>Cihaz Türü</label><select id="cihazTuru"></select></div>
      <div class="form-grup"><label>Marka</label><select id="markaSelect" onchange="modelleriYukle()"></select></div>
      <div class="form-grup"><label>Model</label><select id="modelSelect"></select></div>
    </div>
    <div class="form-row">
      <div class="form-grup"><label>Seri No</label><input type="text" id="seriNo" placeholder="Cihaz seri numarası"></div>
      <div class="form-grup"><label>Durum</label>
        <select id="durumId" onchange="durumDegisti()"></select>
      </div>
    </div>
    <div id="tutanakUyari" style="display:none;background:#fef3c7;border:1px solid #f59e0b;border-radius:7px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:14px"></div>

    <div class="bolum-baslik">Servis Detayı</div>
    <div class="form-row tek"><div class="form-grup"><label>Şikayet / Arıza</label><textarea id="sikayet" placeholder="Müşterinin bildirdiği şikayet..."></textarea></div></div>
    <div class="form-row tek"><div class="form-grup"><label>Yapılan İşlem</label><textarea id="yapilanIslem" placeholder="Yapılan teknik işlem..."></textarea></div></div>
    <div class="form-row">
      <div class="form-grup"><label>Teslim Tarihi</label><input type="date" id="teslimTarihi"></div>
      <div class="form-grup"><label>Notlar</label><input type="text" id="notlar" placeholder="Ek notlar"></div>
    </div>

    <div class="bolum-baslik">Kullanılan Parçalar / Malzemeler</div>
    <div class="autocomplete-wrap">
      <input type="text" id="urunAramaInput" placeholder="Katalogdan parça veya işçilik ara ve ekle..." autocomplete="off"
             style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none">
      <div class="ac-dropdown" id="urunDd"></div>
    </div>
    <table class="parca-tablo">
      <thead><tr><th style="width:40%">Kalem (Parça / İşçilik)</th><th style="width:10%">Miktar</th><th style="width:17%">B.Fiyat ₺</th><th style="width:10%">KDV%</th><th style="width:13%">Toplam</th><th style="width:5%"></th></tr></thead>
      <tbody id="parcaBody"><tr id="parcaBos"><td colspan="6" style="text-align:center;padding:12px;color:#9ca3af;font-size:12px">Parça/İşçilik eklenmedi — yukarıdan katalogdan seçin</td></tr></tbody>
    </table>
    <div class="parca-toplam-satir">
      <span style="font-size:12px;color:#6b7280">Parçalar Toplamı:</span>
      <strong id="parcaGenel" style="color:#10b981">0,00 ₺</strong>
      <button class="btn btn-gray btn-xs" onclick="tutarAktar()" title="Toplamı servis ücretine aktar">↓ Ücrete aktar</button>
    </div>

    <div class="bolum-baslik">Ücret</div>
    <div class="form-row" style="margin-bottom:0">
      <div class="form-grup"><label>Toplam Tutar (₺)</label><input type="number" id="tutar" min="0" step="0.01" placeholder="0.00"></div>
      <div></div>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-gray" onclick="modalKapat('servisModal')">İptal</button>
    <button class="btn btn-primary" onclick="servisKaydet()">Kaydet</button>
  </div>
</div>
</div>

<!-- ═══ YENİ MÜŞTERİ TAM MODAL ═══════════════════════════════════════ -->
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1100;align-items:center;justify-content:center;}
.modal-overlay.aktif{display:flex;}
#musteriModal{backdrop-filter:blur(4px);align-items:center;padding:16px;overflow-y:auto;}
.mm-kart{background:#fff;border-radius:20px;width:100%;max-width:700px;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;margin:auto;}
.mm-header{background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%);padding:22px 28px 18px;position:relative;}
.mm-header h3{color:#fff;font-size:18px;font-weight:800;margin:0;letter-spacing:.2px;}
.mm-header p{color:#bfdbfe;font-size:12px;margin:4px 0 0;}
.mm-kapat{position:absolute;top:16px;right:18px;background:rgba(255,255,255,.15);border:none;border-radius:50%;width:32px;height:32px;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;}
.mm-kapat:hover{background:rgba(255,255,255,.3);}
.mm-tab-bar{display:flex;border-bottom:1px solid #f1f5f9;padding:0 20px;background:#fafbff;overflow-x:auto;}
.mm-tab{padding:11px 16px;border:none;background:none;cursor:pointer;font-size:12.5px;font-weight:600;color:#9ca3af;border-bottom:3px solid transparent;margin-bottom:-1px;white-space:nowrap;transition:.15s;display:flex;align-items:center;gap:5px;}
.mm-tab:hover{color:#374151;}
.mm-tab.aktif{color:#3b82f6;border-bottom-color:#3b82f6;background:none;}
.mm-body{padding:22px 28px;max-height:58vh;overflow-y:auto;}
.m-panel{display:none;}.m-panel.aktif{display:block;}
.mm-footer{padding:14px 28px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafbff;}
.mfg{margin-bottom:14px;}
.mfg label{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;}
.mfg label span.req{color:#ef4444;}
.mfg input,.mfg select,.mfg textarea{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;transition:all .15s;}
.mfg input:focus,.mfg select:focus,.mfg textarea:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
.mfg input.ust{text-transform:uppercase;}
.mfg2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.mfg-full{grid-column:1/-1;}
.tip-grup{display:flex;gap:8px;}
.tip-btn{flex:1;padding:9px 12px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#6b7280;transition:.15s;text-align:center;}
.tip-btn.aktif{border-color:#3b82f6;background:#eff6ff;color:#1d4ed8;}
.coklu-satir{display:flex;align-items:center;gap:8px;margin-bottom:8px;background:#f8faff;border-radius:10px;padding:8px 10px;border:1px solid #e8edf8;}
.coklu-satir input,.coklu-satir select{flex:1;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;outline:none;min-width:0;}
.coklu-satir input:focus,.coklu-satir select:focus{border-color:#3b82f6;}
.coklu-satir .btn-sil{background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:7px 11px;cursor:pointer;font-size:12px;font-weight:700;flex-shrink:0;}
.btn-satir-ekle{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:9px;background:#f0f9ff;color:#0369a1;border:1.5px dashed #7dd3fc;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;margin-top:4px;}
.btn-satir-ekle:hover{background:#e0f2fe;}
.etk-wrap{display:flex;flex-wrap:wrap;gap:8px;padding:14px;background:#f8faff;border-radius:12px;border:1.5px solid #e2e8f0;min-height:52px;}
.etk-chip{padding:5px 13px;border-radius:99px;font-size:12px;font-weight:700;cursor:pointer;border:2px solid transparent;transition:all .15s;opacity:.55;transform:scale(.97);}
.etk-chip:hover{opacity:.85;transform:scale(1);}
.etk-chip.secili{opacity:1;border-color:currentColor!important;box-shadow:0 2px 8px rgba(0,0,0,.12);transform:scale(1);}
.adr-kart{background:#f8faff;border:1.5px solid #e0e7ff;border-radius:12px;padding:14px;margin-bottom:12px;}
.adr-kart-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.mm-info-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 15px;font-size:12px;color:#0369a1;margin-bottom:14px;line-height:1.5;}
</style>

<div class="modal-overlay" id="musteriModal">
  <div class="mm-kart">
    <div class="mm-header">
      <h3>➕ Yeni Müşteri</h3>
      <p>Müşteri bilgilerini doldurun, kaydedin ve otomatik seçilsin</p>
      <button class="mm-kapat" onclick="document.getElementById('musteriModal').classList.remove('aktif')">×</button>
    </div>
    <div class="mm-tab-bar">
      <button class="mm-tab aktif" onclick="mTabGec('genel',this)">📋 Genel Bilgiler</button>
      <button class="mm-tab" onclick="mTabGec('telefonlar',this)">📞 Telefonlar</button>
      <button class="mm-tab" onclick="mTabGec('adresler',this)">📍 Adresler</button>
      <button class="mm-tab" onclick="mTabGec('etiketler',this)">🏷️ Etiketler</button>
      <button class="mm-tab" onclick="mTabGec('bakiye',this)">💰 Bakiye</button>
    </div>
    <div class="mm-body">
      <!-- GENEL -->
      <div class="m-panel aktif" id="mpGenel">
        <div class="mfg mfg-full">
          <label>Ad Soyad / Ünvan <span class="req">*</span></label>
          <input type="text" id="mAd" class="ust" placeholder="AHMET YILMAZ veya ABC TİC. A.Ş.">
        </div>
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Müşteri Tipi</label>
          <div class="tip-grup">
            <button type="button" class="tip-btn aktif" id="tipBireysel" onclick="mTipSec('bireysel')">👤 Bireysel</button>
            <button type="button" class="tip-btn" id="tipKurumsal" onclick="mTipSec('kurumsal')">🏢 Kurumsal</button>
          </div>
          <input type="hidden" id="mTip" value="bireysel">
        </div>
        <div class="mfg2">
          <div class="mfg"><label>E-posta</label><input type="email" id="mEmail" placeholder="ornek@email.com"></div>
          <div class="mfg"><label>Vergi No / TC Kimlik</label><input type="text" id="mVergiNo" placeholder="1234567890" maxlength="11"></div>
          <div class="mfg">
            <label>İl (Fatura İli)</label>
            <select id="mIl" onchange="mIlDegisti()">
              <option value="">-- İl Seçin --</option>
              <?php foreach ($iller as $il): ?>
              <option value="<?= htmlspecialchars($il) ?>"><?= htmlspecialchars($il) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mfg"><label>Vergi Dairesi</label><select id="mVergiDairesi"><option value="">-- Önce İl Seçin --</option></select></div>
        </div>
        <div class="mfg"><label>Notlar</label><textarea id="mNotlar" rows="2" placeholder="Müşteri hakkında notlar..."></textarea></div>
      </div>
      <!-- TELEFONLAR -->
      <div class="m-panel" id="mpTelefonlar">
        <div class="mm-info-box">📞 Birden fazla telefon numarası ekleyebilirsiniz. İlk numara ana numara olarak atanır.</div>
        <div id="mTelefonListesi"></div>
        <button type="button" class="btn-satir-ekle" onclick="mTelefonEkle()">+ Telefon Ekle</button>
      </div>
      <!-- ADRESLER -->
      <div class="m-panel" id="mpAdresler">
        <div class="mm-info-box">📍 Şehir ve ilçe bilgisini açılır listeden seçin.</div>
        <div id="mAdresListesi"></div>
        <button type="button" class="btn-satir-ekle" onclick="mAdresEkle()">+ Adres Ekle</button>
      </div>
      <!-- ETİKETLER -->
      <div class="m-panel" id="mpEtiketler">
        <div class="mm-info-box">🏷️ Müşteriyi sınıflandırmak için etiketlere tıklayın.</div>
        <div class="etk-wrap" id="mEtiketSecici">
          <?php foreach ($etiketlerTum as $et): ?>
          <span class="etk-chip" data-eid="<?= $et['id'] ?>"
            style="background:<?= htmlspecialchars($et['renk']) ?>22;color:<?= htmlspecialchars($et['renk']) ?>;"
            onclick="mEtiketToggle(<?= $et['id'] ?>)"><?= htmlspecialchars($et['ad']) ?></span>
          <?php endforeach; ?>
          <?php if (empty($etiketlerTum)): ?>
          <span style="color:#9ca3af;font-size:13px;padding:6px;">Henüz etiket yok.</span>
          <?php endif; ?>
        </div>
      </div>
      <!-- BAKİYE -->
      <div class="m-panel" id="mpBakiye">
        <div class="mm-info-box"><strong>ℹ️ Başlangıç Bakiyesi:</strong> Sisteme eklendiği andaki borç/alacak.</div>
        <div class="mfg2">
          <div class="mfg"><label>Başlangıç Bakiyesi (₺)</label><input type="number" id="mBakiye" placeholder="0.00" step="0.01" value="0"></div>
          <div class="mfg"><label>Bakiye Tarihi</label><input type="date" id="mBakiyeTarih" value="<?= date('Y-m-d') ?>"></div>
        </div>
      </div>
    </div>
    <div class="mm-footer">
      <button type="button" style="padding:10px 22px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;cursor:pointer;font-weight:600;font-size:13px;color:#374151;" onclick="document.getElementById('musteriModal').classList.remove('aktif')">İptal</button>
      <button type="button" onclick="musteriKaydet()" id="mKaydetBtn" style="padding:10px 26px;border-radius:10px;border:none;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;cursor:pointer;font-weight:700;font-size:13px;box-shadow:0 3px 12px rgba(59,130,246,.35);">💾 Kaydet ve Seç</button>
    </div>
  </div>
</div>

<script>
const mVdData = <?= json_encode($vdTum, JSON_UNESCAPED_UNICODE) ?>;
const TR_ILCELER = {"Adana":["Aladağ","Ceyhan","Çukurova","Feke","İmamoğlu","Karaisalı","Karataş","Kozan","Pozantı","Saimbeyli","Sarıçam","Seyhan","Tufanbeyli","Yumurtalık","Yüreğir"],"Adıyaman":["Adıyaman Merkez","Besni","Çelikhan","Gerger","Gölbaşı","Kahta","Samsat","Sincik","Tut"],"Afyonkarahisar":["Afyon Merkez","Başmakçı","Bayat","Bolvadin","Çay","Çobanlar","Dazkırı","Dinar","Emirdağ","Evciler","Hocalar","İhsaniye","İscehisar","Kızılören","Sandıklı","Sinanpaşa","Sultandağı","Şuhut"],"Ağrı":["Ağrı Merkez","Diyadin","Doğubayazıt","Eleşkirt","Hamur","Patnos","Taşlıçay","Tutak"],"Aksaray":["Ağaçören","Aksaray Merkez","Eskil","Gülağaç","Güzelyurt","Ortaköy","Sarıyahşi"],"Amasya":["Amasya Merkez","Göynücek","Gümüşhacıköy","Hamamözü","Merzifon","Suluova","Taşova"],"Ankara":["Altındağ","Ayaş","Bala","Beypazarı","Çamlıdere","Çankaya","Çubuk","Elmadağ","Etimesgut","Evren","Gölbaşı","Güdül","Haymana","Kalecik","Kahramankazan","Keçiören","Kızılcahamam","Mamak","Nallıhan","Polatlı","Pursaklar","Sincan","Şereflikoçhisar","Yenimahalle"],"Antalya":["Akseki","Aksu","Alanya","Demre","Döşemealtı","Elmalı","Finike","Gazipaşa","Gündoğmuş","İbradı","Kaş","Kemer","Kepez","Konyaaltı","Korkuteli","Kumluca","Manavgat","Muratpaşa","Serik"],"Ardahan":["Ardahan Merkez","Çıldır","Damal","Göle","Hanak","Posof"],"Artvin":["Ardanuç","Arhavi","Artvin Merkez","Borçka","Hopa","Kemalpaşa","Murgul","Şavşat","Yusufeli"],"Aydın":["Bozdoğan","Buharkent","Çine","Didim","Efeler","Germencik","İncirliova","Karacasu","Karpuzlu","Koçarlı","Köşk","Kuşadası","Kuyucak","Nazilli","Söke","Sultanhisar","Yenipazar"],"Balıkesir":["Altıeylül","Ayvalık","Balya","Bandırma","Bigadiç","Burhaniye","Dursunbey","Edremit","Erdek","Gömeç","Gönen","Havran","İvrindi","Karesi","Kepsut","Manyas","Marmara","Savaştepe","Sındırgı","Susurluk"],"Bartın":["Arit","Bartın Merkez","Kurucaşile","Ulus"],"Batman":["Batman Merkez","Beşiri","Gercüş","Hasankeyf","Kozluk","Sason"],"Bayburt":["Aydıntepe","Bayburt Merkez","Demirözü"],"Bilecik":["Bozüyük","Gölpazarı","İnhisar","Merkez","Osmaneli","Pazaryeri","Söğüt","Yenipazar"],"Bingöl":["Adaklı","Bingöl Merkez","Genç","Karlıova","Kiğı","Solhan","Yayladere","Yedisu"],"Bitlis":["Adilcevaz","Ahlat","Bitlis Merkez","Güroymak","Hizan","Mutki","Tatvan"],"Bolu":["Bolu Merkez","Dörtdivan","Gerede","Göynük","Kıbrıscık","Mengen","Mudurnu","Seben","Yeniçağa"],"Burdur":["Ağlasun","Altınyayla","Burdur Merkez","Bucak","Çavdır","Çeltikçi","Gölhisar","Karamanlı","Kemer","Tefenni","Yeşilova"],"Bursa":["Büyükorhan","Gemlik","Gürsu","Harmancık","İnegöl","İznik","Karacabey","Keles","Kestel","Mudanya","Mustafakemalpaşa","Nilüfer","Orhaneli","Orhangazi","Osmangazi","Yenişehir","Yıldırım"],"Çanakkale":["Ayvacık","Bayramiç","Biga","Bozcaada","Çan","Çanakkale Merkez","Eceabat","Ezine","Gelibolu","Gökçeada","Lapseki","Yenice"],"Çankırı":["Atkaracalar","Bayramören","Çankırı Merkez","Çerkeş","Eldivan","Ilgaz","Kızılırmak","Korgun","Kurşunlu","Orta","Şabanözü","Yapraklı"],"Çorum":["Alaca","Bayat","Boğazkale","Dodurga","İskilip","Kargı","Laçin","Mecitözü","Merkez","Oğuzlar","Ortaköy","Osmancık","Sungurlu","Uğurludağ"],"Denizli":["Acıpayam","Babadağ","Baklan","Bekilli","Beyağaç","Bozkurt","Buldan","Çal","Çameli","Çardak","Çivril","Güney","Honaz","Kale","Merkezefendi","Pamukkale","Sarayköy","Serinhisar","Tavas"],"Diyarbakır":["Bağlar","Bismil","Çermik","Çınar","Çüngüş","Dicle","Eğil","Ergani","Hani","Hazro","Kayapınar","Kocaköy","Kulp","Lice","Silvan","Sur","Yenişehir"],"Düzce":["Akçakoca","Cumayeri","Çilimli","Düzce Merkez","Gölyaka","Gümüşova","Kaynaşlı","Yığılca"],"Edirne":["Edirne Merkez","Enez","Havsa","İpsala","Keşan","Lalapaşa","Meriç","Süloğlu","Uzunköprü"],"Elazığ":["Ağın","Alacakaya","Arıcak","Baskil","Elazığ Merkez","Karakoçan","Keban","Kovancılar","Maden","Palu","Sivrice"],"Erzincan":["Çayırlı","Erzincan Merkez","İliç","Kemah","Kemaliye","Otlukbeli","Refahiye","Tercan","Üzümlü"],"Erzurum":["Aşkale","Aziziye","Çat","Hınıs","Horasan","İspir","Karakoçan","Karayazı","Köprüköy","Narman","Oltu","Olur","Palandöken","Pasinler","Pazaryolu","Şenkaya","Tekman","Tortum","Uzundere","Yakutiye"],"Eskişehir":["Alpu","Beylikova","Çifteler","Günyüzü","Han","İnönü","Mahmudiye","Mihalgazi","Mihalıççık","Odunpazarı","Sarıcakaya","Seyitgazi","Sivrihisar","Tepebaşı"],"Gaziantep":["Araban","İslahiye","Karkamış","Nurdağı","Nizip","Oğuzeli","Şahinbey","Şehitkamil","Yavuzeli"],"Giresun":["Alucra","Bulancak","Çamoluk","Çanakçı","Dereli","Doğankent","Espiye","Eynesil","Giresun Merkez","Görele","Güce","Keşap","Piraziz","Şebinkarahisar","Tirebolu","Yağlıdere"],"Gümüşhane":["Gümüşhane Merkez","Kelkit","Köse","Kürtün","Şiran","Torul"],"Hakkari":["Çukurca","Derecik","Hakkari Merkez","Şemdinli","Yüksekova"],"Hatay":["Altınözü","Antakya","Arsuz","Belen","Defne","Dörtyol","Erzin","Hassa","İskenderun","Kırıkhan","Kumlu","Payas","Reyhanlı","Samandağ","Serinyol","Yayladağı"],"Iğdır":["Aralık","Iğdır Merkez","Karakoyunlu","Tuzluca"],"Isparta":["Aksu","Atabey","Eğirdir","Gelendost","Gönen","Isparta Merkez","Keçiborlu","Senirkent","Sütçüler","Şarkikaraağaç","Uluborlu","Yalvaç","Yenişarbademli"],"İstanbul":["Adalar","Arnavutköy","Ataşehir","Avcılar","Bağcılar","Bahçelievler","Bakırköy","Başakşehir","Bayrampaşa","Beşiktaş","Beykoz","Beylikdüzü","Beyoğlu","Büyükçekmece","Çatalca","Çekmeköy","Esenler","Esenyurt","Eyüpsultan","Fatih","Gaziosmanpaşa","Güngören","Kadıköy","Kağıthane","Kartal","Küçükçekmece","Maltepe","Pendik","Sancaktepe","Sarıyer","Silivri","Sultanbeyli","Sultangazi","Şile","Şişli","Tuzla","Ümraniye","Üsküdar","Zeytinburnu"],"İzmir":["Aliağa","Balçova","Bayındır","Bayraklı","Bergama","Beydağ","Bornova","Buca","Çeşme","Çiğli","Dikili","Foça","Gaziemir","Güzelbahçe","Karabağlar","Karaburun","Karşıyaka","Kemalpaşa","Kınık","Kiraz","Konak","Menderes","Menemen","Narlıdere","Ödemiş","Seferihisar","Selçuk","Tire","Torbalı","Urla"],"Kahramanmaraş":["Afşin","Andırın","Çağlayancerit","Dulkadiroğlu","Ekinözü","Elbistan","Göksun","Nurhak","Onikişubat","Pazarcık","Türkoğlu"],"Karabük":["Eflani","Eskipazar","Karabük Merkez","Ovacık","Safranbolu","Yenice"],"Karaman":["Ayrancı","Başyayla","Ermenek","Karaman Merkez","Kazımkarabekir","Sarıveliler"],"Kars":["Akyaka","Arpaçay","Digor","Kars Merkez","Kağızman","Sarıkamış","Selim","Susuz"],"Kastamonu":["Abana","Ağlı","Araç","Azdavay","Bozkurt","Cide","Çatalzeytin","Daday","Devrekani","Doğanyurt","Hanönü","İhsangazi","İnebolu","Kastamonu Merkez","Küre","Pınarbaşı","Seydiler","Şenpazar","Taşköprü","Tosya"],"Kayseri":["Akkışla","Bünyan","Develi","Felahiye","Hacılar","İncesu","Kocasinan","Melikgazi","Özvatan","Pınarbaşı","Sarıoğlan","Sarız","Talas","Tomarza","Yahyalı","Yeşilhisar"],"Kırıkkale":["Bahşılı","Balışeyh","Çelebi","Delice","Karakeçili","Keskin","Kırıkkale Merkez","Sulakyurt","Yahşihan"],"Kırklareli":["Babaeski","Demirköy","Kırklareli Merkez","Kofçaz","Lüleburgaz","Pehlivanköy","Pınarhisar","Vize"],"Kırşehir":["Akçakent","Akpınar","Boztepe","Çiçekdağı","Kaman","Kırşehir Merkez","Mucur"],"Kilis":["Elbeyli","Kilis Merkez","Musabeyli","Polateli"],"Kocaeli":["Başiskele","Çayırova","Darıca","Derince","Dilovası","Gebze","Gölcük","İzmit","Kandıra","Karamürsel","Kartepe","Körfez"],"Konya":["Ahırlı","Akören","Akşehir","Altınekin","Beyşehir","Bozkır","Cihanbeyli","Çeltik","Çumra","Derbent","Derebucak","Doğanhisar","Emirgazi","Ereğli","Güneysınır","Hadim","Halkapınar","Hüyük","Ilgın","Kadınhanı","Karapınar","Karatay","Kulu","Meram","Sarayönü","Selçuklu","Seydişehir","Taşkent","Tuzlukçu","Yalıhüyük","Yunak"],"Kütahya":["Altıntaş","Aslanapa","Çavdarhisar","Domaniç","Dumlupınar","Emet","Gediz","Hisarcık","Kütahya Merkez","Pazarlar","Simav","Şaphane","Tavşanlı"],"Malatya":["Akçadağ","Arapgir","Arguvan","Battalgazi","Darende","Doğanşehir","Doğanyol","Hekimhan","Kale","Kuluncak","Pütürge","Yazıhan","Yeşilyurt"],"Manisa":["Ahmetli","Akhisar","Alaşehir","Demirci","Gölmarmara","Gördes","Kırkağaç","Köprübaşı","Kula","Merkez","Salihli","Sarıgöl","Saruhanlı","Selendi","Soma","Turgutlu","Yunusemre"],"Mardin":["Artuklu","Dargeçit","Derik","Kızıltepe","Mazıdağı","Midyat","Nusaybin","Ömerli","Savur","Yeşilli"],"Mersin":["Akdeniz","Anamur","Aydıncık","Bozyazı","Çamlıyayla","Erdemli","Gülnar","Mezitli","Mut","Silifke","Tarsus","Toroslar","Yenişehir"],"Muğla":["Bodrum","Dalaman","Datça","Fethiye","Kavaklıdere","Köyceğiz","Marmaris","Menteşe","Milas","Ortaca","Seydikemer","Ula","Yatağan"],"Muş":["Bulanık","Hasköy","Korkut","Malazgirt","Muş Merkez","Varto"],"Nevşehir":["Acıgöl","Avanos","Derinkuyu","Gülşehir","Hacıbektaş","Kozaklı","Nevşehir Merkez","Ürgüp"],"Niğde":["Altunhisar","Bor","Çamardı","Çiftlik","Niğde Merkez","Ulukışla"],"Ordu":["Akkuş","Altınordu","Aybastı","Çamaş","Çatalpınar","Çaybaşı","Fatsa","Gölköy","Gülyalı","Gürgentepe","İkizce","Kabadüz","Kabataş","Korgan","Kumru","Mesudiye","Perşembe","Ulubey","Ünye"],"Osmaniye":["Bahçe","Düziçi","Hasanbeyli","Kadirli","Osmaniye Merkez","Sumbas","Toprakkale"],"Rize":["Ardeşen","Çamlıhemşin","Çayeli","Derepazarı","Fındıklı","Güneysu","Hemşin","İkizdere","İyidere","Kalkandere","Pazar","Rize Merkez"],"Sakarya":["Adapazarı","Akyazı","Arifiye","Erenler","Ferizli","Geyve","Hendek","Karapürçek","Karasu","Kaynarca","Kocaali","Mithatpaşa","Pamukova","Sapanca","Serdivan","Söğütlü","Taraklı"],"Samsun":["19 Mayıs","Alaçam","Asarcık","Atakum","Ayvacık","Bafra","Canik","Çarşamba","İlkadım","Kavak","Ladik","Salıpazarı","Tekkeköy","Terme","Vezirköprü","Yakakent"],"Siirt":["Baykan","Eruh","Kurtalan","Pervari","Siirt Merkez","Şirvan","Tillo"],"Sinop":["Ayancık","Boyabat","Dikmen","Durağan","Erfelek","Gerze","Saraydüzü","Sinop Merkez","Türkeli"],"Sivas":["Akıncılar","Altınyayla","Divriği","Doğanşar","Gemerek","Gölova","Gürun","Hafik","İmranlı","Kangal","Koyulhisar","Merkez","Suşehri","Şarkışla","Ulaş","Yıldızeli","Zara"],"Şanlıurfa":["Akçakale","Birecik","Bozova","Ceylanpınar","Eyyübiye","Halfeti","Haliliye","Harran","Hilvan","Karaköprü","Siverek","Suruç","Viranşehir"],"Şırnak":["Beytüşşebap","Cizre","Güçlükonak","İdil","Silopi","Şırnak Merkez","Uludere"],"Tekirdağ":["Çerkezköy","Çorlu","Ergene","Hayrabolu","Kapaklı","Malkara","Marmara Ereğlisi","Muratlı","Saray","Süleymanpaşa","Şarköy"],"Tokat":["Almus","Artova","Başçiftlik","Erbaa","Niksar","Pazar","Reşadiye","Sulusaray","Tokat Merkez","Turhal","Yeşilyurt","Zile"],"Trabzon":["Akçaabat","Araklı","Arsin","Beşikdüzü","Çarşıbaşı","Çaykara","Dernekpazarı","Düzköy","Hayrat","Köprübaşı","Maçka","Of","Ortahisar","Sürmene","Şalpazarı","Tonya","Vakfıkebir","Yomra"],"Tunceli":["Çemişgezek","Hozat","Mazgirt","Merkez","Nazımiye","Ovacık","Pertek","Pülümür"],"Uşak":["Banaz","Eşme","Karahallı","Merkez","Sivaslı","Ulubey"],"Van":["Bahçesaray","Başkale","Çaldıran","Çatak","Edremit","Erciş","Gevaş","Gürpınar","İpekyolu","Muradiye","Özalp","Saray","Tuşba"],"Yalova":["Altınova","Armutlu","Çınarcık","Çiftlikköy","Merkez","Termal"],"Yozgat":["Akdağmadeni","Aydıncık","Boğazlıyan","Çandır","Çayıralan","Çekerek","Kadışehri","Merkez","Saraykent","Sarıkaya","Şefaatli","Sorgun","Yenifakılı","Yerköy"],"Zonguldak":["Alaplı","Çaycuma","Devrek","Ereğli","Gökçebey","Kilimli","Kozlu","Merkez"]};
</script>

<!-- ═══ DETAY ════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="detayModal">
<div class="modal" style="width:750px">
  <div class="modal-header"><h2>Servis Detayı</h2><button class="btn btn-gray btn-sm" onclick="modalKapat('detayModal')">✕</button></div>
  <div class="modal-body" id="detayIcerik"></div>
  <div class="modal-footer">
    <button class="btn btn-gray" onclick="modalKapat('detayModal')">Kapat</button>
    <button class="btn btn-warning btn-sm" id="detayTutanakBtn" style="display:none">📋 Tutanak</button>
    <button class="btn btn-success" id="detayYazdirBtn">&#128424; Yazdır</button>
    <button class="btn btn-primary" id="detayDuzenleBtn">Düzenle</button>
  </div>
</div>
</div>

<script>
const BASE = 'servis_kontrol.php';
let sayfaNo=1, toplamSayfa=1;
let aramaT, musteriT, urunT;
const LPP = 25;
let durumlarCache=[], markalarCache=[];
let parcalar=[]; // {id,ad,stok,miktar,birim_fiyat,kdv}

async function api(params, method='GET') {
  const url = method==='GET' ? BASE+'?'+new URLSearchParams(params) : BASE;
  const r = await fetch(url, method==='POST' ? {method:'POST',body:new URLSearchParams(params)} : {});
  return r.json();
}
function alertBox(tip,msg){ const b=document.getElementById('alertBox'); b.className='alert alert-'+tip; b.textContent=msg; b.style.display='block'; setTimeout(()=>b.style.display='none',3500); }
function modalAc(id){ document.getElementById(id).classList.add('ac'); }
function modalKapat(id){ document.getElementById(id).classList.remove('ac'); }

// ── INIT ──────────────────────────────────────────────────────────────
async function init(){
  const [d,mk,ct] = await Promise.all([api({action:'get_durumlar'}),api({action:'get_markalar'}),api({action:'get_cihaz_turleri'})]);
  if(d.success){
    durumlarCache=d.data;
    d.data.forEach(r=>{
      document.getElementById('durumFiltre').innerHTML += `<option value="${r.id}">${r.durum_adi}</option>`;
      document.getElementById('durumId').innerHTML += `<option value="${r.id}">${r.durum_adi}</option>`;
    });
    renderDashboard();
  }
  if(mk.success){
    markalarCache=mk.data;
    const s=document.getElementById('markaSelect');
    s.innerHTML='<option value="">-- Marka --</option>';
    mk.data.forEach(r=>s.innerHTML+=`<option value="${r.id}">${r.marka_adi}</option>`);
  }
  if(ct.success){
    const s=document.getElementById('cihazTuru');
    s.innerHTML='<option value="">-- Cihaz Türü --</option>';
    ct.data.forEach(r=>s.innerHTML+=`<option value="${r.id}">${r.tur_adi}</option>`);
  }
  initMusteriAC();
  initUrunAC();
  servislerYukle();
}

// ── DASHBOARD ─────────────────────────────────────────────────────────
async function renderDashboard(){
  const st=await api({action:'get_istatistikler'});
  if(!st.success) return;
  const aktif=document.getElementById('durumFiltre').value;
  document.getElementById('dashboardGrid').innerHTML=st.data.map(r=>
    `<div class="dash-kart ${aktif==r.id?'aktif-filtre':''}" style="border-left-color:${r.renk||'#667eea'}" onclick="durumFiltrele(${r.id})">
      <div class="sayi" style="color:${r.renk||'#667eea'}">${r.sayi}</div>
      <div class="ad">${esc(r.durum_adi)}</div>
    </div>`
  ).join('');
}
function durumFiltrele(id){ const df=document.getElementById('durumFiltre'); df.value=df.value==id?'':id; sayfaNo=1; servislerYukle(); }

// ── SERVİSLER LİSTESİ ─────────────────────────────────────────────────
async function servislerYukle(){
  const r=await api({action:'get_servisler',arama:document.getElementById('aramaInput').value.trim(),durum_id:document.getElementById('durumFiltre').value,limit:LPP,offset:(sayfaNo-1)*LPP});
  if(!r.success){ document.getElementById('servislerBody').innerHTML=`<tr><td colspan="10" class="bos-mesaj">${r.message}</td></tr>`; return; }
  toplamSayfa=Math.max(1,Math.ceil(r.toplam/LPP));
  const tbody=document.getElementById('servislerBody');
  if(!r.data.length){ tbody.innerHTML='<tr><td colspan="10" class="bos-mesaj">Kayıt bulunamadı</td></tr>'; }
  else tbody.innerHTML=r.data.map(s=>`
    <tr>
      <td><strong>#${s.id}</strong></td>
      <td>${esc(s.musteri_adi||'-')}<br><small style="color:#6b7280">${esc(s.musteri_telefon||'')}</small></td>
      <td>${esc(s.cihaz_turu||'-')}</td>
      <td>${esc(s.marka||'-')} / ${esc(s.model||'-')}</td>
      <td><code>${esc(s.seri_no||'-')}</code></td>
      <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(s.sikayet||'')}">${esc(s.sikayet||'-')}</td>
      <td><span class="badge" style="background:${s.durum_renk||'#6b7280'}">${esc(s.durum_adi||'?')}</span></td>
      <td>${s.tutar>0?'<strong>'+parseFloat(s.tutar).toFixed(2)+' ₺</strong>':'-'}</td>
      <td>${tarihFmt(s.kayit_tarihi)}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-gray btn-xs" onclick="detayGoster(${s.id})">Detay</button>
        <button class="btn btn-primary btn-xs" onclick="servisDuzenle(${s.id})">✏</button>
        <button class="btn btn-danger btn-xs" onclick="servisSil(${s.id})">✕</button>
      </td>
    </tr>`).join('');
  renderPagination();
  renderDashboard();
}

function renderPagination(){
  const p=document.getElementById('pagination');
  if(toplamSayfa<=1){p.innerHTML='';return;}
  let h=`<span style="color:#6b7280;font-size:12px">Sayfa ${sayfaNo}/${toplamSayfa}</span>`;
  h+=`<button onclick="sayfaDegis(1)" ${sayfaNo==1?'disabled':''}>«</button>`;
  h+=`<button onclick="sayfaDegis(${sayfaNo-1})" ${sayfaNo==1?'disabled':''}>‹</button>`;
  for(let i=Math.max(1,sayfaNo-2);i<=Math.min(toplamSayfa,sayfaNo+2);i++)
    h+=`<button class="${i==sayfaNo?'aktif':''}" onclick="sayfaDegis(${i})">${i}</button>`;
  h+=`<button onclick="sayfaDegis(${sayfaNo+1})" ${sayfaNo==toplamSayfa?'disabled':''}>›</button>`;
  h+=`<button onclick="sayfaDegis(${toplamSayfa})" ${sayfaNo==toplamSayfa?'disabled':''}>»</button>`;
  p.innerHTML=h;
}
function sayfaDegis(n){sayfaNo=n;servislerYukle();}
function aramaYap(){clearTimeout(aramaT);aramaT=setTimeout(()=>{sayfaNo=1;servislerYukle();},350);}
function filtreTemizle(){document.getElementById('aramaInput').value='';document.getElementById('durumFiltre').value='';sayfaNo=1;servislerYukle();}

// ── MÜŞTERİ AUTOCOMPLETE ─────────────────────────────────────────────
function initMusteriAC(){
  const input=document.getElementById('musteriAramaInput');
  const dd=document.getElementById('musteriDd');
  input.addEventListener('input',()=>{
    clearTimeout(musteriT);
    musteriT=setTimeout(()=>musteriAra(input.value),250);
  });
  input.addEventListener('focus',()=>musteriAra(input.value));
  dd.addEventListener('mousedown', e=>e.preventDefault());
  dd.addEventListener('click', e=>{
    const item=e.target.closest('.ac-item');
    if(!item) return;
    musteriSec(parseInt(item.dataset.id), item.dataset.ad, item.dataset.vkn||'', item.dataset.tel||'');
  });
}

async function musteriAra(q){
  const dd=document.getElementById('musteriDd');
  const r=await api({action:'get_musteriler',arama:q.trim()});
  if(!r.success||!r.data.length){
    dd.innerHTML='<div style="padding:12px;text-align:center;color:#9ca3af;font-size:13px">Müşteri bulunamadı — <strong>+ Yeni Müşteri Ekle</strong> butonunu kullanın</div>';
    dd.style.display='block'; return;
  }
  dd.innerHTML=r.data.map(m=>
    `<div class="ac-item" data-id="${m.id}" data-ad="${htmlAttr(m.ad)}" data-vkn="${htmlAttr(m.vergi_no||'')}" data-tel="${htmlAttr(m.telefon||'')}">
      <span class="ac-ad">${esc(m.ad)}</span>
      <span class="ac-meta">
        ${m.vergi_no?'<span class="ac-vkn">'+esc(m.vergi_no)+'</span>':''}
        <span class="ac-sub">${esc(m.telefon||'')}</span>
      </span>
    </div>`
  ).join('');
  dd.style.display='block';
}

function musteriSec(id,ad,vkn,tel){
  document.getElementById('musteriId').value=id;
  document.getElementById('musteriDd').style.display='none';
  document.getElementById('musteriAramaWrap').style.display='none';
  document.getElementById('musteriAltRow').style.display='none';
  const k=document.getElementById('musteriSeciliKutu');
  k.style.display='flex';
  k.innerHTML=`<div><span class="ms-adi">${esc(ad)}</span>${vkn?'<span class="ms-vkn">'+esc(vkn)+'</span>':''}<span class="ms-tel">${esc(tel)}</span></div><span class="ms-kapat" onclick="musteriTemizle()">✕</span>`;
}

function musteriTemizle(){
  document.getElementById('musteriId').value='';
  document.getElementById('musteriAramaInput').value='';
  document.getElementById('musteriDd').style.display='none';
  document.getElementById('musteriAramaWrap').style.display='block';
  document.getElementById('musteriAltRow').style.display='flex';
  document.getElementById('musteriSeciliKutu').style.display='none';
}

function yeniMusteriAc(){
  document.getElementById('mAd').value='';
  document.getElementById('mEmail').value='';
  document.getElementById('mVergiNo').value='';
  document.getElementById('mTip').value='bireysel';
  document.getElementById('tipBireysel').classList.add('aktif');
  document.getElementById('tipKurumsal').classList.remove('aktif');
  document.getElementById('mIl').value='';
  document.getElementById('mVergiDairesi').innerHTML='<option value="">-- Önce İl Seçin --</option>';
  document.getElementById('mNotlar').value='';
  document.getElementById('mBakiye').value='0';
  document.getElementById('mBakiyeTarih').value=new Date().toISOString().split('T')[0];
  document.getElementById('mTelefonListesi').innerHTML='';
  mTelSayac=0; mTelefonEkle();
  document.getElementById('mAdresListesi').innerHTML='';
  mAdrSayac=0; mAdresEkle();
  mSeciliEtiketler.clear();
  document.querySelectorAll('#mEtiketSecici .etk-chip').forEach(c=>c.classList.remove('secili'));
  // Arama kutusundaki metni ad alanına taşı
  const q=document.getElementById('musteriAramaInput').value.trim();
  if(q) document.getElementById('mAd').value=q.toUpperCase();
  mTabGec('genel', document.querySelector('.mm-tab'));
  document.getElementById('musteriModal').classList.add('aktif');
  setTimeout(()=>document.getElementById('mAd').focus(),100);
}

// ── YENİ MÜŞTERİ MODAL FONKSİYONLARI ────────────────────────────────
let mTelSayac=0, mAdrSayac=0;
const mSeciliEtiketler=new Set();

function mTabGec(id, btn){
  document.querySelectorAll('.m-panel').forEach(p=>p.classList.remove('aktif'));
  document.querySelectorAll('.mm-tab').forEach(b=>b.classList.remove('aktif'));
  document.getElementById('mp'+id.charAt(0).toUpperCase()+id.slice(1)).classList.add('aktif');
  if(btn) btn.classList.add('aktif');
}
function mTipSec(tip){
  document.getElementById('mTip').value=tip;
  document.getElementById('tipBireysel').classList.toggle('aktif',tip==='bireysel');
  document.getElementById('tipKurumsal').classList.toggle('aktif',tip==='kurumsal');
}
function mIlDegisti(){
  const il=document.getElementById('mIl').value;
  const sel=document.getElementById('mVergiDairesi');
  sel.innerHTML='<option value="">-- Seçin --</option>';
  if(!il){sel.innerHTML='<option value="">-- Önce İl Seçin --</option>';return;}
  mVdData.filter(d=>d.il===il).forEach(d=>{const o=document.createElement('option');o.value=d.ad;o.textContent=d.ad;sel.appendChild(o);});
}
function mTelefonEkle(tel,etiket){
  mTelSayac++;const i=mTelSayac;
  const div=document.createElement('div');div.className='coklu-satir';div.id='mTel'+i;
  div.innerHTML=`<select id="mTelEtk${i}" style="width:90px;flex:0 0 90px;"><option ${etiket==='Cep'||!etiket?'selected':''}>📱 Cep</option><option ${etiket==='İş'?'selected':''}>💼 İş</option><option ${etiket==='Ev'?'selected':''}>🏠 Ev</option><option ${etiket==='Faks'?'selected':''}>📠 Faks</option></select><input type="tel" id="mTelNo${i}" placeholder="0532 000 00 00" value="${esc(tel||'')}"><button type="button" class="btn-sil" onclick="document.getElementById('mTel${i}').remove()">🗑</button>`;
  document.getElementById('mTelefonListesi').appendChild(div);
}
function mTelefonlariTopla(){
  const sonuc=[];
  document.querySelectorAll('#mTelefonListesi .coklu-satir').forEach(row=>{
    const id=row.id.replace('mTel','');
    const no=document.getElementById('mTelNo'+id)?.value.trim();
    const et=document.getElementById('mTelEtk'+id)?.value.replace(/[^\w\sİışğüöçĞÜÖÇİŞ]/gu,'').trim();
    if(no) sonuc.push({telefon:no,etiket:et||'Cep',varsayilan:sonuc.length===0?1:0});
  });
  return sonuc;
}
function mAdresEkle(adresVal,sehirVal,ilceVal){
  mAdrSayac++;const i=mAdrSayac;
  const ilOptions=Object.keys(TR_ILCELER).map(il=>`<option value="${il}" ${sehirVal===il?'selected':''}>${il}</option>`).join('');
  const ilceOptions=sehirVal&&TR_ILCELER[sehirVal]?TR_ILCELER[sehirVal].map(ilce=>`<option value="${ilce}" ${ilceVal===ilce?'selected':''}>${ilce}</option>`).join(''):'';
  const div=document.createElement('div');div.id='mAdr'+i;div.className='adr-kart';
  div.innerHTML=`<div class="adr-kart-header"><input type="text" id="mAdrBaslik${i}" placeholder="📍 Adres başlığı (Merkez, Şube...)" style="flex:1;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;outline:none;"><button type="button" onclick="document.getElementById('mAdr${i}').remove()" style="margin-left:10px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:12px;font-weight:700;">🗑 Kaldır</button></div><textarea id="mAdrAdres${i}" rows="2" placeholder="Açık adres (Sokak, Bina No...)" style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;margin-bottom:10px;outline:none;">${esc(adresVal||'')}</textarea><div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;"><div><label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">İL</label><select id="mAdrIl${i}" onchange="mAdrIlDegisti(${i})" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;outline:none;"><option value="">-- İl Seçin --</option>${ilOptions}</select></div><div><label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">İLÇE</label><select id="mAdrIlce${i}" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;outline:none;">${ilceOptions?'<option value="">-- İlçe Seçin --</option>'+ilceOptions:'<option value="">-- Önce İl Seçin --</option>'}</select></div></div>`;
  document.getElementById('mAdresListesi').appendChild(div);
}
function mAdrIlDegisti(i){
  const il=document.getElementById('mAdrIl'+i).value;
  const sel=document.getElementById('mAdrIlce'+i);
  sel.innerHTML='<option value="">-- İlçe Seçin --</option>';
  if(il&&TR_ILCELER[il]){TR_ILCELER[il].forEach(ilce=>{const o=document.createElement('option');o.value=ilce;o.textContent=ilce;sel.appendChild(o);});}
  else sel.innerHTML='<option value="">-- Önce İl Seçin --</option>';
}
function mAdresleriTopla(){
  const sonuc=[];
  document.querySelectorAll('#mAdresListesi .adr-kart').forEach(row=>{
    const id=row.id.replace('mAdr','');
    const adres=document.getElementById('mAdrAdres'+id)?.value.trim();
    if(!adres) return;
    sonuc.push({baslik:document.getElementById('mAdrBaslik'+id)?.value.trim()||'Merkez',adres,sehir:document.getElementById('mAdrIl'+id)?.value||'',ilce:document.getElementById('mAdrIlce'+id)?.value||'',posta_kodu:'',varsayilan:sonuc.length===0?1:0});
  });
  return sonuc;
}
function mEtiketToggle(eid){
  const chip=document.querySelector('#mEtiketSecici [data-eid="'+eid+'"]');
  if(mSeciliEtiketler.has(eid)){mSeciliEtiketler.delete(eid);if(chip)chip.classList.remove('secili');}
  else{mSeciliEtiketler.add(eid);if(chip)chip.classList.add('secili');}
}
function musteriKaydet(){
  const adRaw=document.getElementById('mAd').value.trim();
  if(!adRaw){alert('Ad Soyad / Ünvan zorunludur.');mTabGec('genel',document.querySelector('.mm-tab'));return;}
  const btn=document.getElementById('mKaydetBtn');
  btn.disabled=true;btn.textContent='⏳ Kaydediliyor...';
  const params=new URLSearchParams({
    action:'yeni_kaydet',
    ad_soyad:adRaw,
    musteri_tipi:document.getElementById('mTip').value,
    email:document.getElementById('mEmail').value.trim(),
    vergi_no:document.getElementById('mVergiNo').value.trim(),
    vergi_dairesi:document.getElementById('mVergiDairesi').value,
    notlar:document.getElementById('mNotlar').value.trim(),
    baslangic_bakiye:document.getElementById('mBakiye').value,
    telefonlar:JSON.stringify(mTelefonlariTopla()),
    adresler:JSON.stringify(mAdresleriTopla()),
    etiket_idler:JSON.stringify([...mSeciliEtiketler]),
  });
  fetch('musteri_kontrol.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:params.toString()})
  .then(r=>r.json())
  .then(v=>{
    const yeniId=v.musteri?.id||v.id||v.musteri_id;
    const kayitliAd=v.musteri?.ad_soyad||adRaw.toUpperCase();
    if(v.basari||v.ayni){
      const teller=mTelefonlariTopla();
      musteriSec(yeniId,kayitliAd,document.getElementById('mVergiNo').value.trim(),teller.length?teller[0].telefon:'');
      document.getElementById('musteriModal').classList.remove('aktif');
      alertBox('success','Müşteri kaydedildi ve seçildi.');
    }else{alert('Hata: '+(v.mesaj||'Kayıt başarısız.'));}
  })
  .catch(()=>alert('Sunucu hatası.'))
  .finally(()=>{btn.disabled=false;btn.textContent='💾 Kaydet ve Seç';});
}

// ── TUTANAK DURUM KONTROLÜ ────────────────────────────────────────────
const TUTANAK_DEVIR   = ['devir'];
const TUTANAK_HURDA   = ['hurda', 'geçici kapanış', 'gecici kapanis', 'yeniden açılış', 'yeniden acilis'];
const HURDA_AMAÇ_MAP  = {
  'hurda': 'Hurdaya Ayırma',
  'geçici kapanış': 'Geçici Kullanım Dışı Bırakma',
  'gecici kapanis': 'Geçici Kullanım Dışı Bırakma',
  'yeniden açılış': 'Tekrar Kullanıma Alma',
  'yeniden acilis': 'Tekrar Kullanıma Alma'
};

function durumDegisti(){
  const sel  = document.getElementById('durumId');
  const ad   = (sel.options[sel.selectedIndex]?.text||'').toLowerCase().trim();
  const uyari= document.getElementById('tutanakUyari');
  if(TUTANAK_DEVIR.includes(ad)){
    uyari.style.display='block';
    uyari.innerHTML='&#128276; <strong>Devir</strong> seçildi. Kayıt tamamlanınca <strong>EK-2 Devir Satış Tutanağı</strong> formu otomatik açılacak.';
  } else if(TUTANAK_HURDA.some(k=>ad.includes(k))){
    uyari.style.display='block';
    uyari.innerHTML='&#128276; <strong>'+sel.options[sel.selectedIndex]?.text+'</strong> seçildi. Kayıt tamamlanınca <strong>EK-1 Tutanağı</strong> formu otomatik açılacak.';
  } else {
    uyari.style.display='none';
  }
}

function tutanakAc(servisData){
  const durumAd = (servisData.durum_adi||'').toLowerCase().trim();
  const p = new URLSearchParams({
    satici_adi:    servisData.musteri_adi||'',
    satici_adres:  servisData.musteri_adres||'',
    satici_vergi_dairesi: servisData.musteri_vergi_dairesi||'',
    satici_vergi_no:      servisData.musteri_vergi_no||'',
    satici_tel:    servisData.musteri_telefon||'',
    cihaz_marka:   servisData.marka||'',
    cihaz_model:   servisData.model||'',
    cihaz_sicil_no:servisData.seri_no||'',
    servis_id:     servisData.id||''
  });
  if(TUTANAK_DEVIR.includes(durumAd)){
    window.location.href = 'tutanak_devir_ekle.php?'+p;
  } else if(TUTANAK_HURDA.some(k=>durumAd.includes(k))){
    p.set('mudahale_amaci', HURDA_AMAÇ_MAP[Object.keys(HURDA_AMAÇ_MAP).find(k=>durumAd.includes(k))]||'');
    window.location.href = 'tutanak_hurda_ekle.php?'+p;
  }
}
async function modelleriYukle(){
  const markaId=document.getElementById('markaSelect').value;
  const ms=document.getElementById('modelSelect');
  ms.innerHTML='<option value="">-- Model --</option>';
  if(!markaId) return;
  const r=await api({action:'get_modeller',marka_id:markaId});
  if(r.success) r.data.forEach(m=>ms.innerHTML+=`<option value="${m.model_adi}">${m.model_adi}</option>`);
}

// ── KATALOG AUTOCOMPLETE (Yedek Parça + İşçilik) ─────────────────────
function initUrunAC(){
  const input=document.getElementById('urunAramaInput');
  const dd=document.getElementById('urunDd');
  input.addEventListener('input',()=>{clearTimeout(urunT);urunT=setTimeout(()=>urunAra(input.value),250);});
  input.addEventListener('focus',()=>urunAra(input.value));
  dd.addEventListener('click',e=>{
    const item=e.target.closest('.ac-item');
    if(!item) return;
    parcaEkle({id:parseInt(item.dataset.id),ad:item.dataset.ad,birim_fiyat:parseFloat(item.dataset.fiyat),kdv:parseFloat(item.dataset.kdv),birim:item.dataset.birim,kategori:item.dataset.kat});
    input.value='';
    dd.style.display='none';
  });
}

async function urunAra(q){
  const dd=document.getElementById('urunDd');
  const r=await fetch('servis_katalog_kontrol.php?action=ara&q='+encodeURIComponent(q.trim())).then(x=>x.json());
  if(!r.success||!r.data.length){
    dd.innerHTML='<div style="padding:12px;text-align:center;color:#9ca3af;font-size:13px">Katalogda bulunamadı — <a href="servis_katalog.php" target="_blank" style="color:#3b82f6;font-weight:600">Katalog Yönetimi</a></div>';
    dd.style.display='block'; return;
  }
  // Yedek parça ve işçilikleri grupla
  const yp=r.data.filter(u=>u.kategori==='yedek_parca');
  const is=r.data.filter(u=>u.kategori==='iscilik');
  let html='';
  if(yp.length){
    html+=`<div style="padding:6px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc;border-bottom:1px solid #f3f4f6">🔩 Yedek Parça</div>`;
    html+=yp.map(u=>`<div class="ac-item" data-id="${u.id}" data-ad="${htmlAttr(u.ad)}" data-fiyat="${u.birim_fiyat}" data-kdv="${u.kdv_orani}" data-birim="${htmlAttr(u.birim)}" data-kat="yedek_parca">
      <div><div style="font-weight:600">${esc(u.ad)}</div><div class="ac-sub">${u.kod?esc(u.kod)+' · ':''}<span style="color:#6b7280">${esc(u.birim)}</span></div></div>
      <div style="text-align:right;flex-shrink:0"><div style="font-weight:700;color:#10b981">${parseFloat(u.birim_fiyat).toFixed(2)} ₺</div><div class="ac-sub">KDV %${u.kdv_orani}</div></div>
    </div>`).join('');
  }
  if(is.length){
    html+=`<div style="padding:6px 12px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;background:#f8fafc;border-bottom:1px solid #f3f4f6">🛠️ İşçilik</div>`;
    html+=is.map(u=>`<div class="ac-item" data-id="${u.id}" data-ad="${htmlAttr(u.ad)}" data-fiyat="${u.birim_fiyat}" data-kdv="${u.kdv_orani}" data-birim="${htmlAttr(u.birim)}" data-kat="iscilik">
      <div><div style="font-weight:600">${esc(u.ad)}</div><div class="ac-sub">${u.kod?esc(u.kod)+' · ':''}<span style="color:#6b7280">${esc(u.birim)}</span></div></div>
      <div style="text-align:right;flex-shrink:0"><div style="font-weight:700;color:#8b5cf6">${parseFloat(u.birim_fiyat).toFixed(2)} ₺</div><div class="ac-sub">KDV %${u.kdv_orani}</div></div>
    </div>`).join('');
  }
  dd.innerHTML=html;
  dd.style.display='block';
}

// ── PARÇA TABLOSU ─────────────────────────────────────────────────────
function parcaEkle(u){
  const mevcut=parcalar.find(p=>p.id===u.id);
  if(mevcut){mevcut.miktar++;parcaCiz();return;}
  parcalar.push({id:u.id,ad:u.ad,miktar:1,birim_fiyat:u.birim_fiyat||0,kdv:u.kdv||0,birim:u.birim||'Adet',kategori:u.kategori||'yedek_parca'});
  parcaCiz();
}

function parcaKaldir(i){parcalar.splice(i,1);parcaCiz();}

function parcaCiz(){
  const tbody=document.getElementById('parcaBody');
  if(!parcalar.length){
    tbody.innerHTML='<tr id="parcaBos"><td colspan="7" style="text-align:center;padding:12px;color:#9ca3af;font-size:12px">Parça/İşçilik eklenmedi — yukarıdan katalogdan seçin</td></tr>';
    document.getElementById('parcaGenel').textContent='0,00 ₺'; return;
  }
  let toplam=0;
  tbody.innerHTML=parcalar.map((p,i)=>{
    const top=p.miktar*p.birim_fiyat*(1+p.kdv/100);
    toplam+=top;
    const katBadge=p.kategori==='iscilik'
      ? '<span style="font-size:10px;padding:1px 6px;border-radius:10px;background:#f0fdf4;color:#15803d;font-weight:700">🛠️ İşçilik</span>'
      : '<span style="font-size:10px;padding:1px 6px;border-radius:10px;background:#eff6ff;color:#1d4ed8;font-weight:700">🔩 Parça</span>';
    return `<tr>
      <td>${katBadge}<br><span style="font-weight:600">${esc(p.ad)}</span><br><small style="color:#9ca3af">${esc(p.birim||'Adet')}</small></td>
      <td><input type="number" min="0.01" step="0.01" value="${p.miktar}" style="width:70px" onchange="parcaGuncelle(${i},'miktar',this.value)"></td>
      <td><input type="number" min="0" step="0.01" value="${p.birim_fiyat}" style="width:90px" onchange="parcaGuncelle(${i},'birim_fiyat',this.value)"></td>
      <td><input type="number" min="0" step="1" value="${p.kdv}" style="width:55px" onchange="parcaGuncelle(${i},'kdv',this.value)"></td>
      <td><strong>${top.toFixed(2)} ₺</strong></td>
      <td><button class="btn btn-danger btn-xs" onclick="parcaKaldir(${i})">✕</button></td>
    </tr>`;
  }).join('');
  document.getElementById('parcaGenel').textContent=toplam.toLocaleString('tr-TR',{minimumFractionDigits:2})+' ₺';
}

function parcaGuncelle(i,alan,v){parcalar[i][alan]=parseFloat(v)||0;parcaCiz();}
function tutarAktar(){
  const t=parcalar.reduce((s,p)=>s+p.miktar*p.birim_fiyat*(1+p.kdv/100),0);
  document.getElementById('tutar').value=t.toFixed(2);
}

// ── FORM RESET ────────────────────────────────────────────────────────
function formSifirla(){
  document.getElementById('servisId').value='';
  musteriTemizle();
  parcalar=[]; parcaCiz();
  ['cihazTuru','markaSelect','modelSelect','durumId'].forEach(id=>{ const e=document.getElementById(id); if(e) e.selectedIndex=0; });
  ['seriNo','sikayet','yapilanIslem','tutar','notlar','teslimTarihi','urunAramaInput'].forEach(id=>{ const e=document.getElementById(id); if(e) e.value=''; });
}

function servisEkleAc(){ formSifirla(); document.getElementById('modalBaslik').textContent='Yeni Servis'; modalAc('servisModal'); }

async function servisDuzenle(id){
  const r=await api({action:'get_servis',id});
  if(!r.success){alertBox('error',r.message);return;}
  const s=r.data;
  formSifirla();
  document.getElementById('modalBaslik').textContent='#'+id+' Düzenle';
  document.getElementById('servisId').value=id;
  if(s.musteri_id) musteriSec(s.musteri_id,s.musteri_adi||'',s.musteri_telefon||'');
  document.getElementById('seriNo').value=s.seri_no||'';
  document.getElementById('durumId').value=s.durum_id||'';
  document.getElementById('sikayet').value=s.sikayet||'';
  document.getElementById('yapilanIslem').value=s.yapilan_islem||'';
  document.getElementById('tutar').value=s.tutar||'';
  document.getElementById('teslimTarihi').value=s.teslim_tarihi?s.teslim_tarihi.substring(0,10):'';
  document.getElementById('notlar').value=s.notlar||'';
  // Cihaz türü
  const ct=document.getElementById('cihazTuru');
  const cOpt=[...ct.options].find(o=>o.text===s.cihaz_turu); if(cOpt) ct.value=cOpt.value;
  // Marka
  const mk=document.getElementById('markaSelect');
  const mOpt=[...mk.options].find(o=>o.text===s.marka); if(mOpt){mk.value=mOpt.value;await modelleriYukle();}
  const md=document.getElementById('modelSelect');
  const dOpt=[...md.options].find(o=>o.value===s.model||o.text===s.model); if(dOpt) md.value=dOpt.value;
  // Parçalar
  if(s.parcalar&&s.parcalar.length){
    parcalar=s.parcalar.map(p=>({id:p.urun_id,ad:p.urun_adi,stok:0,miktar:parseFloat(p.miktar),birim_fiyat:parseFloat(p.birim_fiyat),kdv:parseFloat(p.kdv_orani)}));
    parcaCiz();
  }
  modalAc('servisModal');
}

async function servisKaydet(){
  const id=document.getElementById('servisId').value;
  const musteriId=document.getElementById('musteriId').value;
  if(!musteriId){alertBox('error','Lütfen müşteri seçin');return;}
  const ct=document.getElementById('cihazTuru'); const mk=document.getElementById('markaSelect'); const md=document.getElementById('modelSelect');
  const durumSel=document.getElementById('durumId');
  const params={
    action:id?'update_servis':'add_servis',
    musteri_id:musteriId,
    cihaz_turu:ct.selectedIndex>0?ct.options[ct.selectedIndex].text:'',
    marka:mk.selectedIndex>0?mk.options[mk.selectedIndex].text:'',
    model:md.value||'',
    seri_no:document.getElementById('seriNo').value,
    durum_id:durumSel.value,
    sikayet:document.getElementById('sikayet').value,
    yapilan_islem:document.getElementById('yapilanIslem').value,
    tutar:document.getElementById('tutar').value||0,
    teslim_tarihi:document.getElementById('teslimTarihi').value,
    notlar:document.getElementById('notlar').value,
    parcalar:JSON.stringify(parcalar)
  };
  if(id) params.id=id;
  const r=await api(params,'POST');
  if(r.success){
    // Tutanak kontrolü — yönlendirme gerekiyorsa önce kontrol et
    const durumAd=(durumSel.options[durumSel.selectedIndex]?.text||'').toLowerCase().trim();
    const tutanakGerekli = TUTANAK_DEVIR.includes(durumAd)||TUTANAK_HURDA.some(k=>durumAd.includes(k));
    if(tutanakGerekli){
      const sid=id||r.id;
      if(sid){
        const sr=await api({action:'get_servis',id:sid});
        if(sr.success){ tutanakAc(sr.data); return; }
      }
    }
    alertBox('success',r.message);
    modalKapat('servisModal');
    servislerYukle();
  } else alertBox('error',r.message);
}

async function servisSil(id){
  if(!confirm(`#${id} numaralı servis silinsin mi? Kullanılan parçalar stoğa iade edilir.`)) return;
  const r=await api({action:'delete_servis',id},'POST');
  if(r.success){alertBox('success',r.message);servislerYukle();}
  else alertBox('error',r.message);
}

// ── DETAY ─────────────────────────────────────────────────────────────
async function detayGoster(id){
  const r=await api({action:'get_servis',id});
  if(!r.success){alertBox('error',r.message);return;}
  const s=r.data;
  let parcaHtml='';
  if(s.parcalar&&s.parcalar.length){
    parcaHtml=`<div style="margin-top:10px"><strong>Kullanılan Parçalar:</strong><table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:6px">
      <thead><tr style="background:#f8faff"><th style="padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:left">Ürün</th><th style="padding:6px 8px;border-bottom:1px solid #e5e7eb">Miktar</th><th style="padding:6px 8px;border-bottom:1px solid #e5e7eb">B.Fiyat</th><th style="padding:6px 8px;border-bottom:1px solid #e5e7eb">KDV%</th><th style="padding:6px 8px;border-bottom:1px solid #e5e7eb">Toplam</th></tr></thead>
      <tbody>${s.parcalar.map(p=>`<tr><td style="padding:5px 8px;border-bottom:1px solid #f3f4f6">${esc(p.urun_adi)}</td><td style="padding:5px 8px;border-bottom:1px solid #f3f4f6;text-align:center">${p.miktar}</td><td style="padding:5px 8px;border-bottom:1px solid #f3f4f6;text-align:right">${parseFloat(p.birim_fiyat).toFixed(2)} ₺</td><td style="padding:5px 8px;border-bottom:1px solid #f3f4f6;text-align:center">%${p.kdv_orani}</td><td style="padding:5px 8px;border-bottom:1px solid #f3f4f6;text-align:right;font-weight:600">${parseFloat(p.toplam||0).toFixed(2)} ₺</td></tr>`).join('')}</tbody>
    </table></div>`;
  }
  document.getElementById('detayIcerik').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
      <div><strong>Müşteri:</strong><br>${esc(s.musteri_adi||'-')} ${s.musteri_telefon?'<small style="color:#6b7280">— '+esc(s.musteri_telefon)+'</small>':''}</div>
      <div><strong>Durum:</strong><br><span class="badge" style="background:${s.durum_renk||'#6b7280'}">${esc(s.durum_adi||'-')}</span></div>
      <div><strong>Cihaz / Marka / Model:</strong><br>${esc(s.cihaz_turu||'-')} — ${esc(s.marka||'')} ${esc(s.model||'')}</div>
      <div><strong>Seri No:</strong><br><code>${esc(s.seri_no||'-')}</code></div>
      <div><strong>Tutar:</strong><br>${s.tutar>0?'<strong>'+parseFloat(s.tutar).toFixed(2)+' ₺</strong>':'-'}</div>
      <div><strong>Kayıt / Teslim:</strong><br>${tarihFmt(s.kayit_tarihi)} ${s.teslim_tarihi?'→ '+tarihFmt(s.teslim_tarihi):''}</div>
    </div>
    <div style="margin-bottom:10px"><strong>Şikayet:</strong><div style="margin-top:5px;padding:10px;background:#f8faff;border-radius:7px;font-size:13px">${esc(s.sikayet||'-').replace(/\n/g,'<br>')}</div></div>
    <div style="margin-bottom:10px"><strong>Yapılan İşlem:</strong><div style="margin-top:5px;padding:10px;background:#f8faff;border-radius:7px;font-size:13px">${esc(s.yapilan_islem||'-').replace(/\n/g,'<br>')}</div></div>
    ${s.notlar?`<div style="margin-bottom:10px"><strong>Notlar:</strong><div style="margin-top:5px;padding:10px;background:#fef3c7;border-radius:7px;font-size:13px">${esc(s.notlar).replace(/\n/g,'<br>')}</div></div>`:''}
    ${parcaHtml}`;
  document.getElementById('detayDuzenleBtn').onclick=()=>{modalKapat('detayModal');servisDuzenle(id);};
  document.getElementById('detayYazdirBtn').onclick=()=>window.open('servis_print.php?id='+id,'_blank','width=420,height=700');
  // Tutanak butonu
  const tBtn=document.getElementById('detayTutanakBtn');
  const durumAd=(s.durum_adi||'').toLowerCase().trim();
  if(TUTANAK_DEVIR.includes(durumAd)||TUTANAK_HURDA.some(k=>durumAd.includes(k))){
    tBtn.style.display='inline-flex';
    tBtn.textContent='📋 Tutanak Oluştur';
    tBtn.onclick=()=>tutanakAc(s);
  } else {
    tBtn.style.display='none';
  }
  modalAc('detayModal');
}

// ── UTILS ─────────────────────────────────────────────────────────────
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function htmlAttr(s){return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');}
function tarihFmt(t){if(!t) return '-'; const d=new Date(t); return d.toLocaleDateString('tr-TR')+' '+d.toLocaleTimeString('tr-TR',{hour:'2-digit',minute:'2-digit'});}

// Dropdown'ları dışarı tıklayınca kapat
document.addEventListener('click',e=>{
  if(!e.target.closest('#musteriAramaWrap')) document.getElementById('musteriDd').style.display='none';
  if(!e.target.closest('.autocomplete-wrap:has(#urunAramaInput)')) document.getElementById('urunDd').style.display='none';
});
document.addEventListener('keydown',e=>{ if(e.key==='Escape') { ['servisModal','detayModal'].forEach(modalKapat); document.getElementById('musteriModal').classList.remove('aktif'); } });
document.querySelectorAll('.modal-bg').forEach(bg=>bg.addEventListener('click',e=>{ if(e.target===bg) bg.classList.remove('ac'); }));
document.getElementById('musteriModal').addEventListener('click',e=>{ if(e.target===document.getElementById('musteriModal')) document.getElementById('musteriModal').classList.remove('aktif'); });

init();
</script>
</body>
</html>
