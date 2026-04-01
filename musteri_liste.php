<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('musteri');
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müşteri Listesi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sayfa-wrap{max-width:1400px;margin:0 auto;padding:24px 16px}
        h1{font-size:22px;font-weight:700;margin-bottom:20px;color:#1e3a8a}

        /* ── Filtre Bar ── */
        .filtre-bar{display:flex;gap:10px;flex-wrap:wrap;background:#fff;padding:14px 16px;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:20px;align-items:center}
        .filtre-bar input,.filtre-bar select{padding:8px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;outline:none}
        .etiket-filtre-wrap{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
        .etiket-chip{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:2px solid transparent;transition:.15s;user-select:none}
        .etiket-chip:hover{opacity:.85}
        .etiket-chip.secili{border-color:#1e40af;box-shadow:0 0 0 2px rgba(30,64,175,.25)}

        /* ── Butonlar ── */
        .btn{padding:8px 18px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:5px}
        .btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
        .btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626}
        .btn-gray{background:#e5e7eb;color:#374151}.btn-gray:hover{background:#d1d5db}
        .btn-sm{padding:4px 10px;font-size:12px;border-radius:6px}
        .btn-ghost{background:none;border:1px solid #dde3f0;color:#374151}.btn-ghost:hover{background:#f3f4f6}

        /* ── Özet kartlar ── */
        .ozet-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px}
        .ozet-kart{background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
        .ozet-kart .lb{font-size:11px;color:#6b7280;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
        .ozet-kart .vl{font-size:20px;font-weight:700}
        .ozet-kart.mavi .vl{color:#3b82f6}.ozet-kart.yesil .vl{color:#10b981}.ozet-kart.sari .vl{color:#f59e0b}

        /* ── Tablo ── */
        .tablo-wrap{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden}
        table{width:100%;border-collapse:collapse;font-size:13px}
        thead th{background:#f8faff;padding:11px 13px;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;white-space:nowrap}
        tbody td{padding:10px 13px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
        tbody tr:hover{background:#f8faff}
        .bos-mesaj{text-align:center;padding:40px;color:#9ca3af;font-size:14px}
        .sayfalama{display:flex;gap:6px;justify-content:center;padding:14px}
        .sayfa-btn{padding:6px 13px;border:1px solid #dde3f0;border-radius:6px;background:#fff;cursor:pointer;font-size:13px}
        .sayfa-btn.aktif{background:#3b82f6;color:#fff;border-color:#3b82f6}
        .badge-etiket{display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;margin:1px}
        .musteri-tip-bireysel{color:#3b82f6;font-size:11px;font-weight:700;background:#eff6ff;padding:2px 7px;border-radius:4px}
        .musteri-tip-kurumsal{color:#7c3aed;font-size:11px;font-weight:700;background:#f5f3ff;padding:2px 7px;border-radius:4px}

        /* ── Modal (yeni tasarım) ── */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:16px;overflow-y:auto}
        .modal-overlay.aktif{display:flex}
        .mm-kart{background:#fff;border-radius:20px;width:100%;max-width:720px;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;margin:auto}
        .mm-header{background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%);padding:22px 28px 18px;position:relative}
        .mm-header h3{color:#fff;font-size:18px;font-weight:800;margin:0;letter-spacing:.2px}
        .mm-header p{color:#bfdbfe;font-size:12px;margin:4px 0 0}
        .mm-kapat{position:absolute;top:16px;right:18px;background:rgba(255,255,255,.15);border:none;border-radius:50%;width:32px;height:32px;color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s}
        .mm-kapat:hover{background:rgba(255,255,255,.3)}
        .mm-tab-bar{display:flex;border-bottom:1px solid #f1f5f9;padding:0 20px;background:#fafbff;overflow-x:auto}
        .mm-tab{padding:11px 16px;border:none;background:none;cursor:pointer;font-size:12.5px;font-weight:600;color:#9ca3af;border-bottom:3px solid transparent;margin-bottom:-1px;white-space:nowrap;transition:.15s;display:flex;align-items:center;gap:5px}
        .mm-tab:hover{color:#374151}
        .mm-tab.aktif{color:#3b82f6;border-bottom-color:#3b82f6;background:none}
        .mm-body{padding:22px 28px;max-height:60vh;overflow-y:auto}
        .m-panel{display:none}.m-panel.aktif{display:block}
        .mm-footer{padding:14px 28px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafbff}

        /* Form */
        .fg{margin-bottom:14px}
        .fg label{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
        .fg input,.fg select,.fg textarea{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box;background:#fff;transition:all .15s}
        .fg input:focus,.fg select:focus,.fg textarea:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
        .fg2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .fg3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}

        /* Çoklu tel/adres satırı */
        .coklu-satir{display:flex;align-items:center;gap:8px;margin-bottom:8px;background:#f8faff;border-radius:10px;padding:8px 10px;border:1px solid #e8edf8}
        .coklu-satir input,.coklu-satir select{margin-bottom:0;flex:1;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;outline:none}
        .coklu-satir input:focus,.coklu-satir select:focus{border-color:#3b82f6}
        .coklu-satir .sil-btn{background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:7px 11px;cursor:pointer;font-size:12px;font-weight:700;flex-shrink:0}
        .coklu-satir .sil-btn:hover{background:#fca5a5}
        .ekle-btn-link{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:9px;background:#f0f9ff;color:#0369a1;border:1.5px dashed #7dd3fc;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;margin-top:4px;border:1.5px dashed #7dd3fc}
        .ekle-btn-link:hover{background:#e0f2fe}

        /* Etiket seçici */
        .etiket-secici{display:flex;flex-wrap:wrap;gap:6px;padding:12px;border:1.5px solid #e2e8f0;border-radius:10px;min-height:46px;background:#fff;cursor:text}
        .etiket-secici-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer}
        .etiket-secici-chip .x{font-size:14px;line-height:1;margin-left:2px}
        .etiket-secici-chip .x:hover{opacity:.6}
        .etiket-dropdown{border:1.5px solid #e2e8f0;border-radius:10px;max-height:200px;overflow-y:auto;display:none;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.1);margin-top:6px}
        .etiket-opt{display:flex;align-items:center;gap:8px;padding:9px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f3f4f6}
        .etiket-opt:hover{background:#f0f4ff}
        .etiket-opt .dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
        .yeni-etiket-form{padding:10px 12px;border-top:1px solid #e5e7eb;display:flex;gap:8px;align-items:center}
        .yeni-etiket-form input{flex:1;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none}
        .color-picker-mini{width:32px;height:32px;padding:0;border:1px solid #dde3f0;border-radius:6px;cursor:pointer}

        /* Bakiye */
        .bakiye-positive{color:#10b981;font-weight:700}
        .bakiye-negative{color:#ef4444;font-weight:700}
        .mm-info-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:12px 15px;font-size:12px;color:#0369a1;margin-bottom:14px;line-height:1.5}
        .required{color:#ef4444}
    </style>
</head>
<body>
<?php
require_once 'db.php';
require_once 'auth.php';

// Tüm iller ve vergi dairelerini çek
$iller = $pdo->query("SELECT DISTINCT il FROM vergi_daireleri ORDER BY il")->fetchAll(PDO::FETCH_COLUMN);
$vdTum = $pdo->query("SELECT * FROM vergi_daireleri ORDER BY il, ad")->fetchAll();
$etiketlerTum = $pdo->query("SELECT * FROM etiketler ORDER BY ad")->fetchAll();

require_once 'menu.php';
?>

<div class="sayfa-wrap">
    <h1>👥 Müşteri Listesi</h1>

    <!-- Filtre Bar -->
    <div class="filtre-bar">
        <input type="text" id="aramaInput" placeholder="🔍 Ad, VKN, tel, e-posta..." style="min-width:220px;">
        <select id="tipFiltre" style="width:130px;">
            <option value="">Tüm Tipler</option>
            <option value="bireysel">Bireysel</option>
            <option value="kurumsal">Kurumsal</option>
        </select>
        <div class="etiket-filtre-wrap" id="etiketFiltreler">
            <span style="font-size:12px;font-weight:600;color:#374151;">Etiket:</span>
            <?php foreach ($etiketlerTum as $et): ?>
            <span class="etiket-chip" data-eid="<?= $et['id'] ?>" style="background:<?= htmlspecialchars($et['renk']) ?>22;color:<?= htmlspecialchars($et['renk']) ?>;border-color:<?= htmlspecialchars($et['renk']) ?>44;" onclick="etiketFiltrele(this, <?= $et['id'] ?>)">
                <?= htmlspecialchars($et['ad']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-gray" onclick="filtreTemizle()" style="margin-left:auto;">✕ Temizle</button>
        <button class="btn btn-secondary" onclick="etiketYonetimAc()" title="Etiketleri Düzenle">🏷 Etiketler</button>
        <button class="btn btn-primary" onclick="modalAc()">➕ Yeni Müşteri</button>
    </div>

    <!-- Özet -->
    <div class="ozet-grid">
        <div class="ozet-kart mavi"><div class="lb">👥 Toplam</div><div class="vl" id="ozToplam">—</div></div>
        <div class="ozet-kart yesil"><div class="lb">🏢 Kurumsal</div><div class="vl" id="ozKurumsal">—</div></div>
        <div class="ozet-kart mavi"><div class="lb">👤 Bireysel</div><div class="vl" id="ozBireysel">—</div></div>
        <div class="ozet-kart sari"><div class="lb">💰 Toplam Bakiye</div><div class="vl" id="ozBakiye">—</div></div>
    </div>

    <!-- Tablo -->
    <div class="tablo-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ad / Ünvan</th>
                    <th>Tip</th>
                    <th>Telefon</th>
                    <th>E-posta</th>
                    <th>VKN / TC</th>
                    <th>Vergi Dairesi</th>
                    <th>Başlangıç Bakiye</th>
                    <th>Etiketler</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody id="musteriTabloBody">
                <tr><td colspan="10" class="bos-mesaj">⏳ Yükleniyor...</td></tr>
            </tbody>
        </table>
        <div class="sayfalama" id="sayfalamaWrap"></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     MÜŞTERİ EKLEME / DÜZENLEME MODAL
════════════════════════════════════════════════ -->
<div class="modal-overlay" id="musteriModal">
  <div class="mm-kart">
    <div class="mm-header">
      <h3 id="modalBaslik">➕ Yeni Müşteri</h3>
      <p>Müşteri bilgilerini doldurun ve kaydedin</p>
      <button class="mm-kapat" onclick="modalKapat()">×</button>
    </div>

    <div class="mm-tab-bar">
      <button class="mm-tab aktif" onclick="tabGec('genel',this)">📋 Genel Bilgiler</button>
      <button class="mm-tab" onclick="tabGec('telefonlar',this)">📞 Telefonlar</button>
      <button class="mm-tab" onclick="tabGec('adresler',this)">📍 Adresler</button>
      <button class="mm-tab" onclick="tabGec('etiketler',this)">🏷️ Etiketler</button>
      <button class="mm-tab" onclick="tabGec('bakiye',this)">💰 Bakiye</button>
    </div>

    <div class="mm-body">
      <input type="hidden" id="mId">

      <!-- TAB: GENEL -->
      <div class="m-panel aktif" id="tab-genel">
        <div class="fg2">
          <div class="fg" style="grid-column:1/-1">
            <label>Ad Soyad / Ünvan <span class="required">*</span></label>
            <input type="text" id="mAd" placeholder="Örn: AHMET YILMAZ veya ABC TİC. A.Ş." style="text-transform:uppercase">
          </div>
          <div class="fg">
            <label>Müşteri Tipi</label>
            <select id="mTip">
              <option value="bireysel">👤 Bireysel</option>
              <option value="kurumsal">🏢 Kurumsal</option>
            </select>
          </div>
          <div class="fg">
            <label>E-posta</label>
            <input type="email" id="mEmail" placeholder="ornek@email.com">
          </div>
          <div class="fg">
            <label>Vergi No / TC Kimlik</label>
            <input type="text" id="mVergiNo" placeholder="1234567890" maxlength="11">
          </div>
          <div class="fg">
            <label>İl (Fatura İli)</label>
            <select id="mIl" onchange="ilDegisti()">
              <option value="">-- İl Seçin --</option>
              <?php foreach ($iller as $il): ?>
              <option value="<?= htmlspecialchars($il) ?>"><?= htmlspecialchars($il) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label>Vergi Dairesi</label>
            <select id="mVergiDairesi">
              <option value="">-- Önce İl Seçin --</option>
            </select>
          </div>
          <div class="fg" style="grid-column:1/-1">
            <label>Notlar</label>
            <textarea id="mNotlar" rows="2" placeholder="Müşteri hakkında notlar..."></textarea>
          </div>
        </div>
      </div>

      <!-- TAB: TELEFONLAR -->
      <div class="m-panel" id="tab-telefonlar">
        <div class="mm-info-box">📞 Birden fazla telefon numarası ekleyebilirsiniz. İlk numara ana numara olarak atanır.</div>
        <div id="telefonListesi"></div>
        <button type="button" class="ekle-btn-link" onclick="telefonEkle()">+ Telefon Ekle</button>
      </div>

      <!-- TAB: ADRESLER -->
      <div class="m-panel" id="tab-adresler">
        <div class="mm-info-box">📍 Şehir ve ilçe bilgisini açılır listeden seçin.</div>
        <div id="adresListesi"></div>
        <button type="button" class="ekle-btn-link" onclick="adresEkle()">+ Adres Ekle</button>
      </div>

      <!-- TAB: ETİKETLER -->
      <div class="m-panel" id="tab-etiketler">
        <div class="fg">
          <label>Müşteri Etiketleri</label>
          <p style="font-size:12px;color:#6b7280;margin:0 0 10px;">Etiketlere tıklayarak seçin/kaldırın. Yeni etiket oluşturmak için aşağıdaki formu kullanın.</p>
          <div class="etiket-secici" id="seciliEtiketler" onclick="etiketDropdownAc()">
            <span id="etiketPlaceholder" style="color:#9ca3af;font-size:13px;">Etiket seçmek için tıklayın...</span>
          </div>
          <div class="etiket-dropdown" id="etiketDropdown">
            <?php foreach ($etiketlerTum as $et): ?>
            <div class="etiket-opt" data-eid="<?= $et['id'] ?>" data-ad="<?= htmlspecialchars($et['ad']) ?>" data-renk="<?= htmlspecialchars($et['renk']) ?>" onclick="etiketToggle(<?= $et['id'] ?>, '<?= htmlspecialchars($et['ad'],ENT_QUOTES) ?>', '<?= htmlspecialchars($et['renk'],ENT_QUOTES) ?>')">
              <span class="dot" style="background:<?= $et['renk'] ?>"></span>
              <span><?= htmlspecialchars($et['ad']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="yeni-etiket-form">
              <input type="text" id="yeniEtiketAd" placeholder="Yeni etiket adı..." onkeydown="if(event.key==='Enter'){event.preventDefault();yeniEtiketOlustur();}">
              <input type="color" id="yeniEtiketRenk" value="#3b82f6" class="color-picker-mini" title="Renk seç">
              <button type="button" class="btn btn-primary btn-sm" onclick="yeniEtiketOlustur()">+ Oluştur</button>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: BAKİYE -->
      <div class="m-panel" id="tab-bakiye">
        <div class="mm-info-box"><strong>ℹ️ Başlangıç Bakiyesi Nedir?</strong><br>Müşterinin sisteme eklendiği andaki mevcut borç/alacak durumu. Pozitif = müşterinin borcu, Negatif = müşterinin alacağı.</div>
        <div class="fg2">
          <div class="fg">
            <label>Başlangıç Bakiyesi (₺)</label>
            <input type="number" id="mBakiye" placeholder="0.00" step="0.01" value="0">
          </div>
          <div class="fg">
            <label>Bakiye Tarihi</label>
            <input type="date" id="mBakiyeTarih" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div id="bakiyeOzet" style="display:none;margin-top:12px;padding:12px;border-radius:8px;font-size:14px;font-weight:600;text-align:center;"></div>
      </div>
    </div>

    <div class="mm-footer">
      <button type="button" style="padding:10px 22px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;cursor:pointer;font-weight:600;font-size:13px;color:#374151;" onclick="modalKapat()">İptal</button>
      <button type="button" onclick="musteriKaydet()" id="kaydetBtn" style="padding:10px 26px;border-radius:10px;border:none;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;cursor:pointer;font-weight:700;font-size:13px;box-shadow:0 3px 12px rgba(59,130,246,.35);">💾 Kaydet</button>
    </div>
  </div>
</div>

<!-- Vergi dairesi verisi (JS için) -->
<script>
const vdData = <?= json_encode($vdTum, JSON_UNESCAPED_UNICODE) ?>;
const etiketData = <?= json_encode($etiketlerTum, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script>
'use strict';

// ── Global state ──────────────────────────────────────────────────
let mevcutSayfa = 1;
const SAYFA_BOYUTU = 25;
let aramaTimer   = null;
let seciliEtiketFiltre = [];
let modalSeciliEtiketler = {}; // {id: {ad, renk}}
let telSayac = 0;
let adrSayac = 0;

const para = v => {
    const n = parseFloat(v||0);
    return (n < 0 ? '-' : '') + Math.abs(n).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' ₺';
};
function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

// ── LİSTE YÜKLEME ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => listeYukle(1));

function listeYukle(sayfa) {
    mevcutSayfa = sayfa;
    const tbody = document.getElementById('musteriTabloBody');
    tbody.innerHTML = `<tr><td colspan="10" class="bos-mesaj">⏳ Yükleniyor...</td></tr>`;

    const p = new URLSearchParams({
        action:    'liste',
        ad_soyad:  document.getElementById('aramaInput').value.trim(),
        tip:       document.getElementById('tipFiltre').value,
        etiket_id: seciliEtiketFiltre.join(','),
        limit:     SAYFA_BOYUTU,
        offset:    (sayfa-1)*SAYFA_BOYUTU,
        ozet:      '1'
    });

    fetch('musteri_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()})
    .then(r=>r.json())
    .then(v => {
        const liste = v.musteriler || [];
        document.getElementById('ozToplam').textContent    = (v.ozet?.toplam    ?? '—') + ' kişi';
        document.getElementById('ozKurumsal').textContent  = (v.ozet?.kurumsal  ?? '—') + ' firma';
        document.getElementById('ozBireysel').textContent  = (v.ozet?.bireysel  ?? '—') + ' kişi';
        const topBak = parseFloat(v.ozet?.bakiye_top ?? 0);
        const bakEl = document.getElementById('ozBakiye');
        bakEl.textContent = para(topBak);
        bakEl.className = 'vl ' + (topBak > 0 ? 'bakiye-positive' : topBak < 0 ? 'bakiye-negative' : '');

        if (!liste.length) {
            tbody.innerHTML = `<tr><td colspan="10" class="bos-mesaj">📭 Kayıt bulunamadı.</td></tr>`;
            document.getElementById('sayfalamaWrap').innerHTML = '';
            return;
        }
        tbody.innerHTML = liste.map((m, i) => {
            const etiketHtml = (m.etiketler||[]).map(e =>
                `<span class="badge-etiket" style="background:${esc(e.renk)}22;color:${esc(e.renk)}">${esc(e.ad)}</span>`
            ).join('');
            const bakiye = parseFloat(m.baslangic_bakiye||0);
            const bakiyeHtml = bakiye !== 0
                ? `<span class="${bakiye>0?'bakiye-positive':'bakiye-negative'}">${para(bakiye)}</span>`
                : '<span style="color:#9ca3af">—</span>';
            const tipBadge = m.musteri_tipi === 'kurumsal'
                ? `<span class="musteri-tip-kurumsal">🏢 Kurumsal</span>`
                : `<span class="musteri-tip-bireysel">👤 Bireysel</span>`;
            const telefon = (m.telefonlar&&m.telefonlar.length) ? esc(m.telefonlar[0].telefon) + (m.telefonlar.length>1?` <small style="color:#9ca3af">+${m.telefonlar.length-1}</small>`:'') : (esc(m.telefon||'—'));
            return `<tr>
                <td style="color:#9ca3af;font-size:12px;">${(sayfa-1)*SAYFA_BOYUTU+i+1}</td>
                <td><strong>${esc(m.ad_soyad)}</strong></td>
                <td>${tipBadge}</td>
                <td>${telefon}</td>
                <td>${esc(m.email||'—')}</td>
                <td><code style="font-size:12px;">${esc(m.vergi_no||'—')}</code></td>
                <td>${esc(m.vergi_dairesi||'—')}</td>
                <td>${bakiyeHtml}</td>
                <td>${etiketHtml||'<span style="color:#d1d5db;font-size:11px;">—</span>'}</td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-gray btn-sm" onclick='musteriDuzenle(${JSON.stringify(m)})'>✏️</button>
                    <button class="btn btn-danger btn-sm" onclick="musteriSil(${m.id},'${esc(m.ad_soyad)}')">🗑</button>
                </td>
            </tr>`;
        }).join('');

        renderSayfalama(v.toplam || liste.length, sayfa);
    })
    .catch(err => {
        tbody.innerHTML = `<tr><td colspan="10" class="bos-mesaj">❌ ${esc(err.message)}</td></tr>`;
    });
}

function renderSayfalama(toplam, aktif) {
    const wrap = document.getElementById('sayfalamaWrap');
    const total = Math.ceil(toplam/SAYFA_BOYUTU);
    if (total <= 1) { wrap.innerHTML=''; return; }
    let h = '';
    if (aktif>1) h += `<button class="sayfa-btn" onclick="listeYukle(${aktif-1})">‹</button>`;
    for (let i=Math.max(1,aktif-2); i<=Math.min(total,aktif+2); i++)
        h += `<button class="sayfa-btn ${i===aktif?'aktif':''}" onclick="listeYukle(${i})">${i}</button>`;
    if (aktif<total) h += `<button class="sayfa-btn" onclick="listeYukle(${aktif+1})">›</button>`;
    wrap.innerHTML = h;
}

// ── FİLTRE ───────────────────────────────────────────────────────
function etiketFiltrele(el, eid) {
    el.classList.toggle('secili');
    if (seciliEtiketFiltre.includes(eid))
        seciliEtiketFiltre = seciliEtiketFiltre.filter(x=>x!==eid);
    else
        seciliEtiketFiltre.push(eid);
    listeYukle(1);
}
function filtreTemizle() {
    document.getElementById('aramaInput').value = '';
    document.getElementById('tipFiltre').value  = '';
    seciliEtiketFiltre = [];
    document.querySelectorAll('.etiket-chip.secili').forEach(el=>el.classList.remove('secili'));
    listeYukle(1);
}
document.getElementById('aramaInput').addEventListener('input', ()=>{
    clearTimeout(aramaTimer);
    aramaTimer = setTimeout(()=>listeYukle(1), 380);
});
document.getElementById('tipFiltre').addEventListener('change', ()=>listeYukle(1));

// ── MODAL AÇ/KAPAT ───────────────────────────────────────────────
function modalAc() {
    formSifirla();
    document.getElementById('modalBaslik').textContent = '➕ Yeni Müşteri';
    document.getElementById('musteriModal').classList.add('aktif');
    setTimeout(()=>document.getElementById('mAd').focus(), 100);
}
function modalKapat() {
    document.getElementById('musteriModal').classList.remove('aktif');
    document.getElementById('etiketDropdown').style.display = 'none';
}
document.getElementById('musteriModal').addEventListener('click', function(e){
    if (e.target===this) modalKapat();
});

function tabGec(id, btn) {
    document.querySelectorAll('.m-panel').forEach(p=>p.classList.remove('aktif'));
    document.querySelectorAll('.mm-tab').forEach(b=>b.classList.remove('aktif'));
    document.getElementById('tab-'+id).classList.add('aktif');
    btn.classList.add('aktif');
    if (id==='etiketler') {
        document.getElementById('etiketDropdown').style.display = 'none';
    }
}

// ── FORM SIFIRLA ─────────────────────────────────────────────────
function formSifirla() {
    document.getElementById('mId').value = '';
    document.getElementById('mAd').value = '';
    document.getElementById('mTip').value = 'bireysel';
    document.getElementById('mEmail').value = '';
    document.getElementById('mVergiNo').value = '';
    document.getElementById('mIl').value = '';
    document.getElementById('mVergiDairesi').innerHTML = '<option value="">-- Önce İl Seçin --</option>';
    document.getElementById('mNotlar').value = '';
    document.getElementById('mBakiye').value = '0';
    document.getElementById('mBakiyeTarih').value = new Date().toISOString().split('T')[0];
    document.getElementById('bakiyeOzet').style.display = 'none';

    // Telefonlar
    document.getElementById('telefonListesi').innerHTML = '';
    telSayac = 0;
    telefonEkle();

    // Adresler
    document.getElementById('adresListesi').innerHTML = '';
    adrSayac = 0;
    adresEkle();

    // Etiketler
    modalSeciliEtiketler = {};
    renderSeciliEtiketler();

    // İlk tab
    tabGec('genel', document.querySelectorAll('.mm-tab')[0]);
}

// ── İL → VERGİ DAİRESİ CASCADE ────────────────────────────────
function ilDegisti() {
    const il = document.getElementById('mIl').value;
    const vdSel = document.getElementById('mVergiDairesi');
    vdSel.innerHTML = '<option value="">-- Seçin --</option>';
    if (!il) { vdSel.innerHTML = '<option value="">-- Önce İl Seçin --</option>'; return; }
    vdData.filter(d=>d.il===il).forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.ad; opt.textContent = d.ad;
        vdSel.appendChild(opt);
    });
}

// ── ÇOKLU TELEFON ────────────────────────────────────────────────
function telefonEkle(tel, etiket, varsayilan) {
    telSayac++;
    const i = telSayac;
    const wrap = document.createElement('div');
    wrap.className = 'coklu-satir';
    wrap.id = 'tel-' + i;
    wrap.innerHTML = `
        <select id="telEtiket${i}" style="width:100px;flex:0 0 100px;">
            <option value="Cep"   ${etiket==='Cep'  ?'selected':''}>📱 Cep</option>
            <option value="İş"    ${etiket==='İş'   ?'selected':''}>💼 İş</option>
            <option value="Ev"    ${etiket==='Ev'   ?'selected':''}>🏠 Ev</option>
            <option value="Faks"  ${etiket==='Faks' ?'selected':''}>📠 Faks</option>
            <option value="Diğer" ${etiket==='Diğer'?'selected':''}>📞 Diğer</option>
        </select>
        <input type="tel" id="telNo${i}" placeholder="0532 000 00 00" value="${esc(tel||'')}">
        <label style="display:flex;align-items:center;gap:4px;font-size:12px;white-space:nowrap;cursor:pointer;">
            <input type="checkbox" id="telVarsayilan${i}" ${varsayilan?'checked':''} onchange="varsayilanTelGuncelle(${i})"> Ana
        </label>
        <button type="button" class="sil-btn" onclick="document.getElementById('tel-${i}').remove()">🗑</button>
    `;
    document.getElementById('telefonListesi').appendChild(wrap);
}

function varsayilanTelGuncelle(aktifId) {
    document.querySelectorAll('[id^="telVarsayilan"]').forEach(cb => {
        if (cb.id !== 'telVarsayilan'+aktifId) cb.checked = false;
    });
}

function telefonlariTopla() {
    const sonuc = [];
    document.querySelectorAll('#telefonListesi .coklu-satir').forEach(row => {
        const id = row.id.replace('tel-','');
        const no  = document.getElementById('telNo'+id)?.value.trim();
        const et  = document.getElementById('telEtiket'+id)?.value;
        const va  = document.getElementById('telVarsayilan'+id)?.checked ? 1 : 0;
        if (no) sonuc.push({telefon: no, etiket: et, varsayilan: va});
    });
    return sonuc;
}

// ── ÇOKLU ADRES ──────────────────────────────────────────────────
function adresEkle(baslik, adres, sehir, ilce, posta, varsayilan) {
    adrSayac++;
    const i = adrSayac;
    const wrap = document.createElement('div');
    wrap.id = 'adr-' + i;
    wrap.style.cssText = 'border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:10px;background:#fafafa;';
    wrap.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <input type="text" id="adrBaslik${i}" value="${esc(baslik||'')}" placeholder="Adres Başlığı (Merkez, Şube...)" style="width:60%;padding:7px 10px;border:1px solid #dde3f0;border-radius:6px;font-size:13px;font-weight:600;">
            <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;">
                <input type="checkbox" id="adrVarsayilan${i}" ${varsayilan?'checked':''} onchange="varsayilanAdrGuncelle(${i})"> Ana Adres
            </label>
            <button type="button" class="sil-btn" onclick="document.getElementById('adr-${i}').remove()">🗑 Kaldır</button>
        </div>
        <textarea id="adrAdres${i}" rows="2" placeholder="Açık adres..." style="width:100%;padding:8px 10px;border:1px solid #dde3f0;border-radius:6px;font-size:13px;box-sizing:border-box;margin-bottom:8px;">${esc(adres||'')}</textarea>
        <div style="display:grid;grid-template-columns:1fr 1fr 120px;gap:8px;">
            <input type="text" id="adrSehir${i}" placeholder="Şehir" value="${esc(sehir||'')}" style="padding:7px 10px;border:1px solid #dde3f0;border-radius:6px;font-size:13px;">
            <input type="text" id="adrIlce${i}"  placeholder="İlçe"  value="${esc(ilce||'')}"  style="padding:7px 10px;border:1px solid #dde3f0;border-radius:6px;font-size:13px;">
            <input type="text" id="adrPosta${i}" placeholder="Posta Kodu" value="${esc(posta||'')}" style="padding:7px 10px;border:1px solid #dde3f0;border-radius:6px;font-size:13px;">
        </div>
    `;
    document.getElementById('adresListesi').appendChild(wrap);
}

function varsayilanAdrGuncelle(aktifId) {
    document.querySelectorAll('[id^="adrVarsayilan"]').forEach(cb => {
        if (cb.id !== 'adrVarsayilan'+aktifId) cb.checked = false;
    });
}

function adresleriTopla() {
    const sonuc = [];
    document.querySelectorAll('#adresListesi [id^="adr-"]').forEach(row => {
        const id = row.id.replace('adr-','');
        const adres = document.getElementById('adrAdres'+id)?.value.trim();
        if (!adres) return;
        sonuc.push({
            baslik:    document.getElementById('adrBaslik'+id)?.value.trim() || 'Merkez',
            adres,
            sehir:     document.getElementById('adrSehir'+id)?.value.trim(),
            ilce:      document.getElementById('adrIlce'+id)?.value.trim(),
            posta_kodu:document.getElementById('adrPosta'+id)?.value.trim(),
            varsayilan:document.getElementById('adrVarsayilan'+id)?.checked ? 1 : 0,
        });
    });
    return sonuc;
}

// ── ETİKET SEÇİCİ ────────────────────────────────────────────────
function etiketDropdownAc() {
    const dd = document.getElementById('etiketDropdown');
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}
document.addEventListener('click', e => {
    if (!e.target.closest('#tab-etiketler'))
        document.getElementById('etiketDropdown').style.display = 'none';
});

function etiketToggle(eid, ad, renk) {
    if (modalSeciliEtiketler[eid]) {
        delete modalSeciliEtiketler[eid];
    } else {
        modalSeciliEtiketler[eid] = {ad, renk};
    }
    renderSeciliEtiketler();
}

function renderSeciliEtiketler() {
    const wrap = document.getElementById('seciliEtiketler');
    const ph   = document.getElementById('etiketPlaceholder');
    const ids  = Object.keys(modalSeciliEtiketler);
    if (!ids.length) {
        wrap.innerHTML = '';
        wrap.appendChild(ph);
        ph.style.display = 'inline';
        return;
    }
    ph.style.display = 'none';
    wrap.innerHTML = ids.map(eid => {
        const e = modalSeciliEtiketler[eid];
        return `<span class="etiket-secici-chip" style="background:${esc(e.renk)}22;color:${esc(e.renk)};">
            ${esc(e.ad)}
            <span class="x" onclick="event.stopPropagation();etiketToggle(${eid},'${esc(e.ad)}','${esc(e.renk)}')" title="Kaldır">×</span>
        </span>`;
    }).join('') + '<span id="etiketPlaceholder" style="display:none;"></span>';
}

function yeniEtiketOlustur() {
    const ad   = document.getElementById('yeniEtiketAd').value.trim();
    const renk = document.getElementById('yeniEtiketRenk').value;
    if (!ad) { alert('Etiket adı girin.'); return; }

    fetch('musteri_kontrol.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'etiket_olustur', ad, renk}).toString()
    })
    .then(r=>r.json())
    .then(v => {
        if (!v.basari && !v.id) { alert(v.mesaj||'Hata'); return; }
        const yeniId   = v.id || v.etiket_id;
        const yeniAd   = ad;
        const yeniRenk = renk;

        // Dropdown'a ekle
        const dd = document.getElementById('etiketDropdown');
        const form = dd.querySelector('.yeni-etiket-form');
        const div = document.createElement('div');
        div.className = 'etiket-opt';
        div.dataset.eid = yeniId;
        div.innerHTML = `<span class="dot" style="background:${yeniRenk}"></span><span>${esc(yeniAd)}</span>`;
        div.onclick = () => etiketToggle(yeniId, yeniAd, yeniRenk);
        dd.insertBefore(div, form);

        // Filtre barına ekle
        const chip = document.createElement('span');
        chip.className = 'etiket-chip';
        chip.dataset.eid = yeniId;
        chip.style.cssText = `background:${yeniRenk}22;color:${yeniRenk};border-color:${yeniRenk}44;`;
        chip.textContent = yeniAd;
        chip.onclick = function(){ etiketFiltrele(this, yeniId); };
        document.getElementById('etiketFiltreler').appendChild(chip);

        // Otomatik seç
        etiketToggle(yeniId, yeniAd, yeniRenk);
        document.getElementById('yeniEtiketAd').value = '';
        document.getElementById('yeniEtiketRenk').value = '#3b82f6';
    });
}

// ── KAYDET ───────────────────────────────────────────────────────
function musteriKaydet() {
    const btn = document.getElementById('kaydetBtn');
    const ad  = document.getElementById('mAd').value.trim();
    if (!ad) {
        tabGec('genel', document.querySelectorAll('.mtab')[0]);
        document.getElementById('mAd').focus();
        alert('Ad Soyad / Ünvan zorunludur.');
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Kaydediliyor...';

    const id = document.getElementById('mId').value;
    const action = id ? 'guncelle' : 'yeni_kaydet';

    const params = new URLSearchParams({
        action,
        id:               id || '',
        ad_soyad:         ad,
        musteri_tipi:     document.getElementById('mTip').value,
        email:            document.getElementById('mEmail').value.trim(),
        vergi_no:         document.getElementById('mVergiNo').value.trim(),
        vergi_dairesi:    document.getElementById('mVergiDairesi').value,
        notlar:           document.getElementById('mNotlar').value.trim(),
        baslangic_bakiye: document.getElementById('mBakiye').value,
        telefonlar:       JSON.stringify(telefonlariTopla()),
        adresler:         JSON.stringify(adresleriTopla()),
        etiket_idler:     JSON.stringify(Object.keys(modalSeciliEtiketler).map(Number)),
    });

    fetch('musteri_kontrol.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(r=>r.json())
    .then(v => {
        if (v.basari) {
            modalKapat();
            listeYukle(mevcutSayfa);
        } else {
            alert('❌ ' + (v.mesaj || 'Kayıt başarısız.'));
        }
    })
    .catch(() => alert('Sunucu hatası.'))
    .finally(() => { btn.disabled=false; btn.textContent='💾 Kaydet'; });
}

// ── DÜZENLE ──────────────────────────────────────────────────────
function musteriDuzenle(m) {
    formSifirla();
    document.getElementById('modalBaslik').textContent = '✏️ Müşteri Düzenle';
    document.getElementById('mId').value    = m.id;
    document.getElementById('mAd').value    = m.ad_soyad || '';
    document.getElementById('mTip').value   = m.musteri_tipi || 'bireysel';
    document.getElementById('mEmail').value = m.email || '';
    document.getElementById('mVergiNo').value = m.vergi_no || '';
    document.getElementById('mNotlar').value  = m.notlar || '';
    document.getElementById('mBakiye').value  = m.baslangic_bakiye || '0';

    // Vergi dairesi il/VD cascade
    if (m.vergi_dairesi) {
        const vdRow = vdData.find(d => d.ad === m.vergi_dairesi);
        if (vdRow) {
            document.getElementById('mIl').value = vdRow.il;
            ilDegisti();
            setTimeout(() => { document.getElementById('mVergiDairesi').value = m.vergi_dairesi; }, 50);
        } else {
            // Manuel giriş
            const opt = document.createElement('option');
            opt.value = m.vergi_dairesi; opt.textContent = m.vergi_dairesi;
            document.getElementById('mVergiDairesi').appendChild(opt);
            document.getElementById('mVergiDairesi').value = m.vergi_dairesi;
        }
    }

    // Telefonlar
    document.getElementById('telefonListesi').innerHTML = '';
    telSayac = 0;
    if (m.telefonlar && m.telefonlar.length) {
        m.telefonlar.forEach(t => telefonEkle(t.telefon, t.etiket, t.varsayilan));
    } else if (m.telefon) {
        telefonEkle(m.telefon, 'Cep', 1);
    } else {
        telefonEkle();
    }

    // Adresler
    document.getElementById('adresListesi').innerHTML = '';
    adrSayac = 0;
    if (m.adresler && m.adresler.length) {
        m.adresler.forEach(a => adresEkle(a.baslik, a.adres, a.sehir, a.ilce, a.posta_kodu, a.varsayilan));
    } else if (m.adres) {
        adresEkle('Merkez', m.adres, '', '', '', 1);
    } else {
        adresEkle();
    }

    // Etiketler
    modalSeciliEtiketler = {};
    (m.etiketler||[]).forEach(e => { modalSeciliEtiketler[e.id] = {ad: e.ad, renk: e.renk}; });
    renderSeciliEtiketler();

    document.getElementById('musteriModal').classList.add('aktif');
    tabGec('genel', document.querySelectorAll('.mtab')[0]);
}

// ── SİL ──────────────────────────────────────────────────────────
function musteriSil(id, ad) {
    if (!confirm(`"${ad}" silinecek. Emin misiniz?`)) return;
    fetch('musteri_kontrol.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=sil&id=' + id
    })
    .then(r=>r.json())
    .then(v => {
        if (v.basari) listeYukle(mevcutSayfa);
        else alert('❌ ' + (v.mesaj||'Silinemedi.'));
    });
}

// ── BAKİYE ÖNIZLEME ──────────────────────────────────────────────
document.getElementById('mBakiye').addEventListener('input', function() {
    const n = parseFloat(this.value||0);
    const el = document.getElementById('bakiyeOzet');
    if (n === 0) { el.style.display='none'; return; }
    el.style.display = 'block';
    if (n > 0) {
        el.style.cssText = 'display:block;background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;padding:10px;font-size:14px;font-weight:600;text-align:center;color:#92400e;';
        el.textContent = `⚠️ Müşterinin ${para(n)} borcu var`;
    } else {
        el.style.cssText = 'display:block;background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:10px;font-size:14px;font-weight:600;text-align:center;color:#065f46;';
        el.textContent = `✅ Müşterinin ${para(Math.abs(n))} alacağı var`;
    }
});

// ── Etiket Yönetimi ──────────────────────────────────────
function etiketYonetimAc() {
    document.getElementById('etiketYonetimModal').style.display = 'flex';
    eyListeYukle();
}
function etiketYonetimKapat() {
    document.getElementById('etiketYonetimModal').style.display = 'none';
    location.reload();
}
function eyListeYukle() {
    const kap = document.getElementById('eyListe');
    kap.innerHTML = '<div style="text-align:center;color:#9ca3af;padding:12px;">Yükleniyor...</div>';
    fetch('musteri_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=etiket_listele'})
        .then(r=>r.json()).then(v=>{
            const list = v.etiketler || [];
            if(!list.length){ kap.innerHTML='<div style="text-align:center;color:#9ca3af;padding:12px;">Henüz etiket yok.</div>'; return; }
            kap.innerHTML = list.map(e=>`
                <div id="eyRow${e.id}" style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
                    <span style="width:14px;height:14px;border-radius:50%;background:${esc(e.renk)};flex-shrink:0;"></span>
                    <input type="text" value="${esc(e.ad)}" id="eyAd${e.id}" style="flex:1;padding:5px 8px;border:1px solid #dde3f0;border-radius:6px;font-size:13px;">
                    <input type="color" value="${esc(e.renk)}" id="eyRenk${e.id}" style="width:32px;height:30px;border:1px solid #dde3f0;border-radius:6px;cursor:pointer;padding:1px;" oninput="document.querySelector('#eyRow${e.id} span').style.background=this.value">
                    <button onclick="eySil(${e.id})" style="padding:4px 8px;background:#fee2e2;color:#991b1b;border:none;border-radius:6px;cursor:pointer;font-size:12px;">🗑</button>
                    <button onclick="eyKaydet(${e.id})" style="padding:4px 8px;background:#dcfce7;color:#166534;border:none;border-radius:6px;cursor:pointer;font-size:12px;">💾</button>
                </div>`).join('');
        });
}
function eyEkle() {
    const ad = document.getElementById('eyYeniAd').value.trim();
    const renk = document.getElementById('eyYeniRenk').value;
    if(!ad){ alert('Etiket adı gerekli.'); return; }
    fetch('musteri_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'etiket_olustur', ad, renk}).toString()})
        .then(r=>r.json()).then(v=>{ if(v.basari){ document.getElementById('eyYeniAd').value=''; eyListeYukle(); } else alert(v.mesaj); });
}
function eyKaydet(id) {
    const ad = document.getElementById('eyAd'+id).value.trim();
    const renk = document.getElementById('eyRenk'+id).value;
    if(!ad){ alert('Etiket adı boş olamaz.'); return; }
    fetch('musteri_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'etiket_guncelle', id, ad, renk}).toString()})
        .then(r=>r.json()).then(v=>{ if(v.basari){ eyListeYukle(); } else alert('Hata!'); });
}
function eySil(id) {
    if(!confirm('Bu etiketi silmek istediğinizden emin misiniz? Müşterilerden de kaldırılacaktır.')) return;
    fetch('musteri_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'etiket_sil', id}).toString()})
        .then(r=>r.json()).then(v=>{ if(v.basari){ eyListeYukle(); } else alert('Hata!'); });
}
</script>

<!-- ETİKET YÖNETİM MODAL -->
<div class="modal-overlay" id="etiketYonetimModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1100;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:28px 24px;width:460px;max-width:95vw;max-height:80vh;display:flex;flex-direction:column;gap:16px;box-shadow:0 8px 40px rgba(0,0,0,.18);">
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <h3 style="margin:0;font-size:16px;color:#1e3a8a;">🏷 Etiket Yönetimi</h3>
      <button onclick="etiketYonetimKapat()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#666;">✕</button>
    </div>
    <!-- Yeni etiket ekle -->
    <div style="display:flex;gap:8px;align-items:center;">
      <input type="text" id="eyYeniAd" placeholder="Yeni etiket adı..." style="flex:1;padding:8px 12px;border:1px solid #dde3f0;border-radius:7px;font-size:13px;">
      <input type="color" id="eyYeniRenk" value="#3b82f6" style="width:38px;height:36px;border:1px solid #dde3f0;border-radius:7px;cursor:pointer;padding:2px;">
      <button class="btn btn-primary" onclick="eyEkle()" style="white-space:nowrap;">+ Ekle</button>
    </div>
    <!-- Mevcut etiketler listesi -->
    <div id="eyListe" style="overflow-y:auto;max-height:360px;display:flex;flex-direction:column;gap:8px;"></div>
  </div>
</div>
</div><!-- /sayfa-icerik -->
</body>
</html>
