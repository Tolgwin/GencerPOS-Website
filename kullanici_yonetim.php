<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('kullanici');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kullanıcı Yönetimi — FaturaApp</title>
</head>
<body>
<?php require_once 'menu.php'; ?>

<style>
.ky-wrap  { max-width: 1100px; }
.page-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-hdr h1 { font-size:22px; font-weight:800; color:#1e293b; }

.kart { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06); overflow:hidden; margin-bottom:24px; }
.kart-hdr { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #f1f5f9; }
.kart-hdr h2 { font-size:15px; font-weight:700; color:#1e293b; }
.kart-body { padding:0; }

table { width:100%; border-collapse:collapse; font-size:13.5px; }
thead th { background:#f8fafc; padding:11px 16px; text-align:left; font-weight:700; color:#64748b; font-size:12px; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e2e8f0; }
tbody td { padding:12px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody tr:last-child td { border:none; }
tbody tr:hover td { background:#fafbfc; }

.rol-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; }
.rol-admin     { background:#dbeafe; color:#1d4ed8; }
.rol-muhasebe  { background:#d1fae5; color:#065f46; }
.rol-teknisyen { background:#fef3c7; color:#92400e; }
.rol-standart  { background:#f3f4f6; color:#374151; }

.aktif-badge   { padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; }
.badge-aktif   { background:#d1fae5; color:#065f46; }
.badge-pasif   { background:#fee2e2; color:#991b1b; }

.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; border:none; cursor:pointer; font-size:13px; font-weight:600; text-decoration:none; transition:.15s; }
.btn-primary { background:#1f6feb; color:#fff; }
.btn-primary:hover { background:#388bfd; }
.btn-sm { padding:5px 11px; font-size:12px; border-radius:7px; }
.btn-warning { background:#fef3c7; color:#92400e; }
.btn-warning:hover { background:#fde68a; }
.btn-danger  { background:#fee2e2; color:#991b1b; }
.btn-danger:hover  { background:#fecaca; }
.btn-gray    { background:#f1f5f9; color:#475569; }
.btn-gray:hover    { background:#e2e8f0; }

/* Modal */
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:900; align-items:center; justify-content:center; backdrop-filter:blur(3px); }
.modal-bg.acik { display:flex; }
.modal { background:#fff; border-radius:16px; width:520px; max-width:calc(100vw - 32px); max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.modal-hdr { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 16px; border-bottom:1px solid #f1f5f9; }
.modal-hdr h3 { font-size:17px; font-weight:800; color:#1e293b; }
.modal-close { background:none; border:none; cursor:pointer; font-size:22px; color:#9ca3af; line-height:1; }
.modal-close:hover { color:#374151; }
.modal-body { padding:20px 24px; }
.modal-footer { display:flex; justify-content:flex-end; gap:10px; padding:16px 24px; border-top:1px solid #f1f5f9; }

.form-grup { margin-bottom:16px; }
.form-grup label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-grup input, .form-grup select { width:100%; padding:9px 13px; border:1.5px solid #e5e7eb; border-radius:9px; font-size:13.5px; color:#1c2333; background:#f9fafb; outline:none; transition:.15s; }
.form-grup input:focus, .form-grup select:focus { border-color:#1f6feb; background:#fff; box-shadow:0 0 0 3px rgba(31,111,235,.10); }
.form-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

/* Rol izin tablosu */
.izin-tablo { width:100%; border-collapse:collapse; font-size:13px; margin-top:8px; }
.izin-tablo th { background:#f8fafc; padding:8px 12px; text-align:left; font-weight:700; color:#64748b; font-size:11px; text-transform:uppercase; }
.izin-tablo td { padding:8px 12px; border-bottom:1px solid #f1f5f9; }
.izin-tablo tr:last-child td { border:none; }

.switch { position:relative; display:inline-block; width:38px; height:22px; }
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; inset:0; background:#d1d5db; border-radius:22px; transition:.2s; }
.slider::before { content:''; position:absolute; width:16px; height:16px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; }
input:checked + .slider { background:#1f6feb; }
input:checked + .slider::before { transform:translateX(16px); }

.alert { padding:10px 16px; border-radius:9px; font-size:13px; font-weight:600; margin-bottom:14px; display:none; }
.alert-success { background:#d1fae5; color:#065f46; }
.alert-danger  { background:#fee2e2; color:#991b1b; }
</style>

<div class="ky-wrap">
    <div class="page-hdr">
        <h1>👤 Kullanıcı Yönetimi</h1>
        <button class="btn btn-primary" onclick="modalAc('ekle')">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Yeni Kullanıcı
        </button>
    </div>

    <!-- Kullanıcı Listesi -->
    <div class="kart">
        <div class="kart-hdr">
            <h2>Kullanıcılar</h2>
            <button class="btn btn-gray btn-sm" onclick="rolModalAc()">⚙️ Rol İzinleri</button>
        </div>
        <div class="kart-body">
            <table id="kulTablo">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ad Soyad</th>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody id="kulBody">
                    <tr><td colspan="8" style="text-align:center;padding:28px;color:#9ca3af;">Yükleniyor...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ekle/Düzenle Modal -->
<div class="modal-bg" id="kulModal">
    <div class="modal">
        <div class="modal-hdr">
            <h3 id="modalBaslik">Yeni Kullanıcı</h3>
            <button class="modal-close" onclick="modalKapat()">×</button>
        </div>
        <div class="modal-body">
            <div class="alert" id="modalAlert"></div>
            <input type="hidden" id="kulId">
            <div class="form-grid2">
                <div class="form-grup" style="grid-column:1/-1">
                    <label>Ad Soyad *</label>
                    <input type="text" id="kulAdSoyad" placeholder="Adı Soyadı">
                </div>
                <div class="form-grup">
                    <label>Kullanıcı Adı *</label>
                    <input type="text" id="kulKullaniciAdi" placeholder="kullanici_adi">
                </div>
                <div class="form-grup">
                    <label>E-posta</label>
                    <input type="email" id="kulEmail" placeholder="email@domain.com">
                </div>
                <div class="form-grup">
                    <label>Şifre <span id="sifreLabel" style="color:#9ca3af;font-weight:400">(yeni için zorunlu)</span></label>
                    <input type="password" id="kulSifre" placeholder="En az 6 karakter">
                </div>
                <div class="form-grup">
                    <label>Rol *</label>
                    <select id="kulRol"></select>
                </div>
                <div class="form-grup" id="aktifGroup" style="display:none">
                    <label>Durum</label>
                    <select id="kulAktif">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-gray" onclick="modalKapat()">İptal</button>
            <button class="btn btn-primary" onclick="kulKaydet()">💾 Kaydet</button>
        </div>
    </div>
</div>

<!-- Rol İzinleri Modal -->
<div class="modal-bg" id="rolModal">
    <div class="modal" style="width:640px">
        <div class="modal-hdr">
            <h3>⚙️ Rol İzin Yönetimi</h3>
            <button class="modal-close" onclick="document.getElementById('rolModal').classList.remove('acik')">×</button>
        </div>
        <div class="modal-body">
            <div class="form-grup">
                <label>Rol Seçin</label>
                <select id="rolSecim" onchange="rolIzinleriGoster()"></select>
            </div>
            <div id="rolIzinBody"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-gray" onclick="document.getElementById('rolModal').classList.remove('acik')">Kapat</button>
            <button class="btn btn-primary" onclick="rolKaydet()">💾 Kaydet</button>
        </div>
    </div>
</div>

<script>
const IZIN_ETIKETLER = {
    fatura:       'Fatura',
    musteri:      'Müşteriler',
    urun:         'Ürünler',
    kasa:         'Kasa & Hesaplar',
    rapor:        'Raporlar',
    personel:     'Personel',
    servis:       'Servis',
    tutanak:      'Tutanak',
    ayarlar:      'Ayarlar',
    kullanici:    'Kullanıcı Yönetimi',
    db:           'DB Yönetim',
    import_export:'Import / Export',
};

let roller = [];
let kullanicilar = [];

async function post(data) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    const r = await fetch('kullanici_kontrol.php', { method: 'POST', body: fd });
    return r.json();
}

function rolBadge(rol) {
    const map = { admin:'rol-admin', muhasebe:'rol-muhasebe', teknisyen:'rol-teknisyen', standart:'rol-standart' };
    const cls = map[rol] || 'rol-standart';
    return `<span class="rol-badge ${cls}">${rol}</span>`;
}

function tarih(dt) {
    if (!dt) return '<span style="color:#d1d5db">—</span>';
    return new Date(dt).toLocaleString('tr-TR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

async function listeYukle() {
    const v = await post({ action: 'liste' });
    kullanicilar = v.kullanicilar || [];
    const tbody = document.getElementById('kulBody');
    if (!kullanicilar.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:28px;color:#9ca3af">Kullanıcı bulunamadı.</td></tr>';
        return;
    }
    tbody.innerHTML = kullanicilar.map(k => `
        <tr>
            <td style="color:#9ca3af">${k.id}</td>
            <td style="font-weight:600">${esc(k.ad_soyad)}</td>
            <td><code style="background:#f1f5f9;padding:2px 7px;border-radius:5px;font-size:12px">${esc(k.kullanici_adi)}</code></td>
            <td style="color:#64748b">${esc(k.email || '—')}</td>
            <td>${rolBadge(k.rol_adi)}</td>
            <td><span class="aktif-badge ${k.aktif == 1 ? 'badge-aktif' : 'badge-pasif'}">${k.aktif == 1 ? 'Aktif' : 'Pasif'}</span></td>
            <td style="color:#64748b;font-size:12px">${tarih(k.son_giris)}</td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="duzenleMod(${k.id})">✏️</button>
                <button class="btn btn-danger btn-sm" onclick="kulSil(${k.id},'${esc(k.ad_soyad)}')"
                    ${k.id == <?= $_SESSION['kullanici_id'] ?> ? 'disabled title="Kendi hesabınız"' : ''}>🗑</button>
            </td>
        </tr>`).join('');
}

async function rollerYukle() {
    const v = await post({ action: 'roller' });
    roller = v.roller || [];
    const sel = document.getElementById('kulRol');
    sel.innerHTML = roller.map(r => `<option value="${r.id}">${r.ad}</option>`).join('');
    const rolSec = document.getElementById('rolSecim');
    rolSec.innerHTML = roller.filter(r => r.ad !== 'admin').map(r => `<option value="${r.id}">${r.ad}</option>`).join('');
}

function modalAc(tip) {
    document.getElementById('kulId').value = '';
    document.getElementById('kulAdSoyad').value = '';
    document.getElementById('kulKullaniciAdi').value = '';
    document.getElementById('kulEmail').value = '';
    document.getElementById('kulSifre').value = '';
    document.getElementById('kulKullaniciAdi').disabled = false;
    document.getElementById('sifreLabel').textContent = '(zorunlu)';
    document.getElementById('aktifGroup').style.display = 'none';
    document.getElementById('modalBaslik').textContent = 'Yeni Kullanıcı';
    alertGizle();
    document.getElementById('kulModal').classList.add('acik');
}

function modalKapat() {
    document.getElementById('kulModal').classList.remove('acik');
}

function duzenleMod(id) {
    const k = kullanicilar.find(x => x.id == id);
    if (!k) return;
    document.getElementById('kulId').value = k.id;
    document.getElementById('kulAdSoyad').value = k.ad_soyad;
    document.getElementById('kulKullaniciAdi').value = k.kullanici_adi;
    document.getElementById('kulKullaniciAdi').disabled = true;
    document.getElementById('kulEmail').value = k.email || '';
    document.getElementById('kulSifre').value = '';
    document.getElementById('kulRol').value = k.rol_id;
    document.getElementById('kulAktif').value = k.aktif;
    document.getElementById('sifreLabel').textContent = '(boş bırakırsanız değişmez)';
    document.getElementById('aktifGroup').style.display = 'block';
    document.getElementById('modalBaslik').textContent = 'Kullanıcı Düzenle';
    alertGizle();
    document.getElementById('kulModal').classList.add('acik');
}

async function kulKaydet() {
    const id = document.getElementById('kulId').value;
    const data = {
        action: id ? 'guncelle' : 'ekle',
        id, ad_soyad: document.getElementById('kulAdSoyad').value,
        kullanici_adi: document.getElementById('kulKullaniciAdi').value,
        email: document.getElementById('kulEmail').value,
        sifre: document.getElementById('kulSifre').value,
        rol_id: document.getElementById('kulRol').value,
        aktif: document.getElementById('kulAktif').value,
    };
    const v = await post(data);
    if (v.basari) { modalKapat(); listeYukle(); } 
    else { alertGoster(v.mesaj, 'danger'); }
}

async function kulSil(id, ad) {
    if (!confirm(`"${ad}" adlı kullanıcıyı silmek istediğinizden emin misiniz?`)) return;
    const v = await post({ action: 'sil', id });
    if (v.basari) listeYukle();
    else alert(v.mesaj);
}

// Rol izinleri
var rolIzinleri = {};

async function rolModalAc() {
    // Güncel izinleri yükle
    const v = await post({ action: 'liste' });
    // roller zaten yüklü
    rolIzinleriGoster();
    document.getElementById('rolModal').classList.add('acik');
}

function rolIzinleriGoster() {
    const rolId = document.getElementById('rolSecim').value;
    // Mevcut rol izinlerini bul (önce roller listesini çek)
    post({ action: 'roller' }).then(v => {
        const rol = v.roller?.find(r => r.id == rolId);
        if (!rol) return;
        let izinler = {};
        try { izinler = JSON.parse(rol.izinler || '{}'); } catch(e) {}
        rolIzinleri = izinler;

        document.getElementById('rolIzinBody').innerHTML = `
            <table class="izin-tablo">
                <thead><tr><th>Yetki</th><th>Açık/Kapalı</th></tr></thead>
                <tbody>
                ${Object.entries(IZIN_ETIKETLER).map(([k, lbl]) => `
                    <tr>
                        <td style="font-weight:500">${lbl}</td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" data-key="${k}" ${izinler[k] ? 'checked' : ''}
                                       onchange="rolIzinleri['${k}']=this.checked">
                                <span class="slider"></span>
                            </label>
                        </td>
                    </tr>`).join('')}
                </tbody>
            </table>`;
    });
}

async function rolKaydet() {
    const rolId = document.getElementById('rolSecim').value;
    const v = await post({ action: 'rol_guncelle', rol_id: rolId, izinler: JSON.stringify(rolIzinleri) });
    if (v.basari) {
        document.getElementById('rolModal').classList.remove('acik');
        alert('Rol izinleri kaydedildi.');
    } else alert(v.mesaj);
}

function alertGoster(msg, tip) {
    const el = document.getElementById('modalAlert');
    el.className = `alert alert-${tip}`;
    el.textContent = msg;
    el.style.display = 'block';
}
function alertGizle() {
    const el = document.getElementById('modalAlert');
    el.style.display = 'none';
}

function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
rollerYukle();
listeYukle();
</script>

<?php require_once 'menu_kapat.php'; ?>
</body>
</html>
