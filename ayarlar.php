<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('ayarlar');
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ayarlar</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sayfa-wrap { max-width: 900px; margin: 0 auto; padding: 24px 16px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 24px; color: #1e3a8a; }
        .kart { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 24px; }
        .kart h3 { font-size: 16px; font-weight: 700; margin-bottom: 18px; color: #1e3a8a; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid .tam { grid-column: 1/-1; }
        .form-grup label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-grup input, .form-grup select, .form-grup textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #dde3f0;
            border-radius: 8px; font-size: 13px; outline: none; box-sizing: border-box;
        }
        .form-grup input:focus, .form-grup select:focus { border-color: #3b82f6; }
        .btn { padding: 10px 22px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .badge-test { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        .badge-wait { background: #fef3c7; color: #92400e; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-top: 16px; font-size: 13px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .separator { border: none; border-top: 1px solid #f3f4f6; margin: 16px 0; }
    </style>
</head>
<body>
<?php
require_once 'menu.php';

$configFile = __DIR__ . '/config.php';
$config = [];
if (file_exists($configFile)) {
    $config = require $configFile;
}

$kayitMesaj = '';
$kayitTur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet_genel'])) {
    // Banka hesaplarını topla
    $bankaHesaplari = [];
    $bankalar = $_POST['banka_banka'] ?? [];
    $subeler  = $_POST['banka_sube'] ?? [];
    $hesapNolar = $_POST['banka_hesap_no'] ?? [];
    $ibanlar  = $_POST['banka_iban'] ?? [];
    foreach ($bankalar as $i => $b) {
        $b = trim($b);
        if ($b !== '') {
            $bankaHesaplari[] = [
                'banka'    => $b,
                'sube'     => trim($subeler[$i] ?? ''),
                'hesap_no' => trim($hesapNolar[$i] ?? ''),
                'iban'     => trim($ibanlar[$i] ?? ''),
            ];
        }
    }

    $yeniConfig = [
        'username'        => trim($_POST['qnb_username'] ?? ''),
        'password'        => trim($_POST['qnb_password'] ?? ''),
        'wsdl_user'       => trim($_POST['wsdl_user'] ?? 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl'),
        'wsdl_connector'  => trim($_POST['wsdl_connector'] ?? 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl'),
        'firma_vkn'       => trim($_POST['firma_vkn'] ?? ''),
        'firma_unvan'     => trim($_POST['firma_unvan'] ?? ''),
        'firma_adres'     => trim($_POST['firma_adres'] ?? ''),
        'firma_tel'       => trim($_POST['firma_tel'] ?? ''),
        'firma_email'     => trim($_POST['firma_email'] ?? ''),
        'firma_web'       => trim($_POST['firma_web'] ?? ''),
        'banka_hesaplari' => $bankaHesaplari,
        // Paynkolay
        'payn_sx'          => trim($_POST['payn_sx']          ?? ''),
        'payn_secret'      => trim($_POST['payn_secret']       ?? ''),
        'payn_test_modu'   => isset($_POST['payn_test_modu'])  ? true : false,
        'payn_base_url'    => trim($_POST['payn_base_url']     ?? ''),
        'payn_callback_url'=> trim($_POST['payn_callback_url'] ?? ''),
        'payn_max_taksit'  => (int)($_POST['payn_max_taksit']  ?? 1),
        'test_modu'       => isset($_POST['test_modu']) ? true : false,
        'soap_options'    => $config['soap_options'] ?? [],
        'ubl_xml_path'    => $config['ubl_xml_path'] ?? __DIR__ . '/invoice.xml',
    ];

    $icerik = "<?php\nreturn " . var_export($yeniConfig, true) . ";\n";
    if (file_put_contents($configFile, $icerik)) {
        $config = $yeniConfig;
        $kayitMesaj = '✅ Ayarlar başarıyla kaydedildi.';
        $kayitTur = 'success';
    } else {
        $kayitMesaj = '❌ Ayarlar kaydedilemedi. Dosya yazma izni kontrol edin.';
        $kayitTur = 'error';
    }
}
?>

<div class="sayfa-wrap">
    <h1>⚙️ Ayarlar</h1>

    <?php if ($kayitMesaj): ?>
        <div class="alert alert-<?= $kayitTur ?>"><?= htmlspecialchars($kayitMesaj) ?></div>
    <?php endif; ?>

    <form method="POST">

        <!-- QNB Entegrasyonu -->
        <div class="kart">
            <h3>🔗 QNB eSolutions e-Fatura Entegrasyonu</h3>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Kullanıcı Adı (VKN/TCKN) *</label>
                    <input type="text" name="qnb_username" value="<?= htmlspecialchars($config['username'] ?? '') ?>" placeholder="3930311899">
                </div>
                <div class="form-grup">
                    <label>Şifre *</label>
                    <input type="password" name="qnb_password" value="<?= htmlspecialchars($config['password'] ?? '') ?>" placeholder="••••••••">
                </div>
                <div class="form-grup tam">
                    <label>User Service WSDL URL</label>
                    <input type="url" name="wsdl_user" value="<?= htmlspecialchars($config['wsdl_user'] ?? 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl') ?>">
                </div>
                <div class="form-grup tam">
                    <label>Connector Service WSDL URL</label>
                    <input type="url" name="wsdl_connector" value="<?= htmlspecialchars($config['wsdl_connector'] ?? 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl') ?>">
                </div>
                <div class="form-grup tam">
                    <label>
                        <input type="checkbox" name="test_modu" value="1" <?= ($config['test_modu'] ?? true) ? 'checked' : '' ?>>
                        Test Modu (işaretli = test ortamı, işaretsiz = canlı)
                    </label>
                </div>
            </div>
            <hr class="separator">
            <div style="display:flex;align-items:center;gap:16px;margin-top:8px;">
                <button type="button" class="btn btn-warning" onclick="baglantiTest()">🔌 Bağlantı Test Et</button>
                <span id="testSonuc"></span>
            </div>
        </div>

        <!-- Firma Bilgileri -->
        <div class="kart">
            <h3>🏢 Firma Bilgileri</h3>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Firma VKN *</label>
                    <input type="text" name="firma_vkn" value="<?= htmlspecialchars($config['firma_vkn'] ?? $config['username'] ?? '') ?>" placeholder="1234567890">
                </div>
                <div class="form-grup">
                    <label>Firma Ünvanı *</label>
                    <input type="text" name="firma_unvan" value="<?= htmlspecialchars($config['firma_unvan'] ?? '') ?>" placeholder="Firma A.Ş.">
                </div>
                <div class="form-grup tam">
                    <label>Firma Adresi</label>
                    <textarea name="firma_adres" rows="2" placeholder="Açık adres..."><?= htmlspecialchars($config['firma_adres'] ?? '') ?></textarea>
                </div>
                <div class="form-grup">
                    <label>Telefon</label>
                    <input type="text" name="firma_tel" value="<?= htmlspecialchars($config['firma_tel'] ?? '') ?>" placeholder="0212 000 00 00">
                </div>
                <div class="form-grup">
                    <label>E-posta</label>
                    <input type="email" name="firma_email" value="<?= htmlspecialchars($config['firma_email'] ?? '') ?>" placeholder="info@firma.com">
                </div>
                <div class="form-grup">
                    <label>Web Sitesi</label>
                    <input type="text" name="firma_web" value="<?= htmlspecialchars($config['firma_web'] ?? '') ?>" placeholder="www.firma.com">
                </div>
            </div>
        </div>

        <!-- Banka Hesapları -->
        <div class="kart">
            <h3>🏦 Banka Hesapları</h3>
            <p style="font-size:12px;color:#6b7280;margin-bottom:16px;">Birden fazla banka hesabı tanımlayabilirsiniz. Fatura PDF'inde imza altında gösterilir.</p>
            <div id="bankaListesi">
                <?php
                $bankaHesaplari = $config['banka_hesaplari'] ?? [];
                // eski tek hesap geriye uyumluluk
                if (empty($bankaHesaplari) && (!empty($config['firma_banka']) || !empty($config['firma_iban']))) {
                    $bankaHesaplari = [['banka'=>$config['firma_banka']??'','sube'=>'','hesap_no'=>'','iban'=>$config['firma_iban']??'']];
                }
                if (empty($bankaHesaplari)) $bankaHesaplari = [['banka'=>'','sube'=>'','hesap_no'=>'','iban'=>'']];
                foreach ($bankaHesaplari as $bi => $bh): ?>
                <div class="banka-satir" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;margin-bottom:10px;align-items:end;background:#f8faff;padding:12px;border-radius:8px;border:1px solid #e5e7eb;">
                    <div class="form-grup" style="margin:0">
                        <label>Banka Adı</label>
                        <input type="text" name="banka_banka[]" value="<?= htmlspecialchars($bh['banka']??'') ?>" placeholder="Ziraat Bankası">
                    </div>
                    <div class="form-grup" style="margin:0">
                        <label>Şube</label>
                        <input type="text" name="banka_sube[]" value="<?= htmlspecialchars($bh['sube']??'') ?>" placeholder="İstanbul Şubesi">
                    </div>
                    <div class="form-grup" style="margin:0">
                        <label>Hesap No</label>
                        <input type="text" name="banka_hesap_no[]" value="<?= htmlspecialchars($bh['hesap_no']??'') ?>" placeholder="123-456">
                    </div>
                    <div class="form-grup" style="margin:0">
                        <label>IBAN</label>
                        <input type="text" name="banka_iban[]" value="<?= htmlspecialchars($bh['iban']??'') ?>" placeholder="TR00 0000 0000 0000 0000 0000 00">
                    </div>
                    <button type="button" onclick="bankaSatirSil(this)" style="padding:8px 12px;border:none;border-radius:8px;background:#fee2e2;color:#991b1b;cursor:pointer;font-size:16px;height:38px;align-self:end;">🗑</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="bankaEkle()" class="btn" style="background:#e0f2fe;color:#0369a1;margin-top:4px;">➕ Hesap Ekle</button>
        </div>

    <script>
    function bankaEkle() {
        const satir = document.createElement('div');
        satir.className = 'banka-satir';
        satir.style.cssText = 'display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;margin-bottom:10px;align-items:end;background:#f8faff;padding:12px;border-radius:8px;border:1px solid #e5e7eb;';
        satir.innerHTML = `
            <div class="form-grup" style="margin:0"><label>Banka Adı</label><input type="text" name="banka_banka[]" placeholder="Ziraat Bankası"></div>
            <div class="form-grup" style="margin:0"><label>Şube</label><input type="text" name="banka_sube[]" placeholder="İstanbul Şubesi"></div>
            <div class="form-grup" style="margin:0"><label>Hesap No</label><input type="text" name="banka_hesap_no[]" placeholder="123-456"></div>
            <div class="form-grup" style="margin:0"><label>IBAN</label><input type="text" name="banka_iban[]" placeholder="TR00 0000 0000 0000 0000 0000 00"></div>
            <button type="button" onclick="bankaSatirSil(this)" style="padding:8px 12px;border:none;border-radius:8px;background:#fee2e2;color:#991b1b;cursor:pointer;font-size:16px;height:38px;align-self:end;">🗑</button>
        `;
        document.getElementById('bankaListesi').appendChild(satir);
    }
    function bankaSatirSil(btn) {
        const satirlar = document.querySelectorAll('.banka-satir');
        if (satirlar.length <= 1) { alert('En az bir satır bırakmalısınız.'); return; }
        btn.closest('.banka-satir').remove();
    }
    </script>

    <!-- Paynkolay Entegrasyonu -->
    <div class="kart" style="margin-top:24px;">
        <h3>💳 Paynkolay Sanal POS Entegrasyonu</h3>
        <p style="font-size:12px;color:#6b7280;margin-bottom:16px;">
            Fatura ve cari tahsilatları için müşteriye SMS/e-posta ile ödeme linki gönderin.
            <a href="https://paynkolay.com.tr/entegrasyon/index.html" target="_blank" style="color:#3b82f6;">Entegrasyon Dokümantasyonu →</a>
        </p>
        <div class="form-grid">
            <div class="form-grup">
                <label>SX Değeri (TOKEN) *</label>
                <input type="text" name="payn_sx" value="<?= htmlspecialchars($config['payn_sx'] ?? '') ?>" placeholder="Paynkolay SX değeri">
            </div>
            <div class="form-grup">
                <label>Merchant Secret Key *</label>
                <input type="password" name="payn_secret" value="<?= htmlspecialchars($config['payn_secret'] ?? '') ?>" placeholder="••••••••">
            </div>
            <div class="form-grup">
                <label>Sitenin Base URL'i</label>
                <input type="text" name="payn_base_url" value="<?= htmlspecialchars($config['payn_base_url'] ?? 'http://localhost/FaturaApp') ?>" placeholder="http://localhost/FaturaApp">
                <small style="color:#6b7280;font-size:11px;">Callback URL otomatik oluşturulur: &lt;base_url&gt;/odeme_callback.php</small>
            </div>
            <div class="form-grup">
                <label>Maksimum Taksit</label>
                <select name="payn_max_taksit">
                    <?php foreach ([1,2,3,6,9,12] as $t): ?>
                    <option value="<?= $t ?>" <?= ($config['payn_max_taksit'] ?? 1) == $t ? 'selected' : '' ?>><?= $t ?> Taksit</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grup tam">
                <label>
                    <input type="checkbox" name="payn_test_modu" value="1" <?= ($config['payn_test_modu'] ?? true) ? 'checked' : '' ?>>
                    Test Modu (işaretli = test sunucusu, işaretsiz = canlı)
                </label>
            </div>
        </div>
    </div>

    <button type="submit" name="kaydet_genel" class="btn btn-primary" style="margin-top:8px;margin-bottom:24px;">💾 Ayarları Kaydet</button>
    </form>

    <!-- Yetkili Servis Eşleştirme -->
    <div class="kart" style="margin-top:24px;" id="eslestirmeKart">
        <h3>🔧 Yetkili Servis Eşleştirme</h3>
        <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">
            Ürün, marka ve modele göre yetki no / mühür no tanımlayın. Tutanak formları açıldığında eşleşen kayıt otomatik yüklenir.
        </p>

        <!-- Ekleme / Düzenleme Formu -->
        <div id="eslForm" style="background:#f8faff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:20px;">
            <div style="font-size:13px;font-weight:700;color:#1e3a8a;margin-bottom:12px;" id="eslFormBaslik">➕ Yeni Eşleştirme Ekle</div>
            <input type="hidden" id="eslId">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-grup">
                    <label>Ürün (isteğe bağlı)</label>
                    <input type="text" id="eslUrun" placeholder="Ürün adı veya boş">
                </div>
                <div class="form-grup">
                    <label>Marka *</label>
                    <select id="eslMarka" onchange="eslMarkaSecildi()">
                        <option value="">-- Marka --</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Model (isteğe bağlı)</label>
                    <select id="eslModel">
                        <option value="">-- Tüm Modeller --</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                <div class="form-grup">
                    <label>Yetkili Servis Firma Ünvanı *</label>
                    <input type="text" id="eslFirmaUnvan" placeholder="Firma ünvanı">
                </div>
                <div class="form-grup">
                    <label>Yetki No</label>
                    <input type="text" id="eslYetkiNo" placeholder="Yetki numarası">
                </div>
                <div class="form-grup">
                    <label>Mühür No</label>
                    <input type="text" id="eslMuhurNo" placeholder="Mühür numarası">
                </div>
            </div>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px;margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:10px;">💰 Fatura Fiyat Bilgileri (Tutanaktan Fatura oluşturulurken otomatik uygulanır)</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;">
                    <div class="form-grup">
                        <label>Devir Bedeli — KDV Dahil (₺)</label>
                        <input type="number" id="eslDevirBedeli" placeholder="0.00" step="0.01" min="0" style="text-align:right;">
                    </div>
                    <div class="form-grup">
                        <label>Devir KDV (%)</label>
                        <select id="eslDevirKdv">
                            <option value="0">%0</option>
                            <option value="1">%1</option>
                            <option value="10">%10</option>
                            <option value="18" selected>%18</option>
                            <option value="20">%20</option>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label>Hurda/Geçici Bedeli — KDV Dahil (₺)</label>
                        <input type="number" id="eslHurdaBedeli" placeholder="0.00" step="0.01" min="0" style="text-align:right;">
                    </div>
                    <div class="form-grup">
                        <label>Hurda KDV (%)</label>
                        <select id="eslHurdaKdv">
                            <option value="0">%0</option>
                            <option value="1">%1</option>
                            <option value="10">%10</option>
                            <option value="18">%18</option>
                            <option value="20" selected>%20</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-primary" onclick="eslKaydet()">💾 Kaydet</button>
                <button class="btn" style="background:#e5e7eb;color:#374151;" onclick="eslFormSifirla()">İptal</button>
            </div>
        </div>

        <!-- Liste -->
        <div id="eslYukleniyor" style="color:#9ca3af;font-size:13px;">⏳ Yükleniyor...</div>
        <div id="eslListe" style="display:none;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc;color:#374151;">
                        <th style="padding:9px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Ürün</th>
                        <th style="padding:9px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Marka</th>
                        <th style="padding:9px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Model</th>
                        <th style="padding:9px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Firma Ünvanı</th>
                        <th style="padding:9px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Yetki No</th>
                        <th style="padding:9px 12px;text-align:left;border-bottom:1px solid #e5e7eb;">Mühür No</th>
                        <th style="padding:9px 12px;text-align:right;border-bottom:1px solid #e5e7eb;">Devir Bedeli</th>
                        <th style="padding:9px 12px;text-align:center;border-bottom:1px solid #e5e7eb;">Devir KDV</th>
                        <th style="padding:9px 12px;text-align:right;border-bottom:1px solid #e5e7eb;">Hurda Bedeli</th>
                        <th style="padding:9px 12px;text-align:center;border-bottom:1px solid #e5e7eb;">Hurda KDV</th>
                        <th style="padding:9px 12px;border-bottom:1px solid #e5e7eb;"></th>
                    </tr>
                </thead>
                <tbody id="eslBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Veritabanı Bilgileri -->
    <div class="kart" style="margin-top:24px;">
        <h3>🗄️ Veritabanı</h3>
        <div style="font-size:13px;color:#374151;">
            <?php
            try {
                require_once 'db.php';
require_once 'auth.php';
                $versiyon = $pdo->query("SELECT VERSION() AS v")->fetch()['v'];
                echo '<span class="badge-test badge-ok">✅ Bağlantı Aktif</span>';
                echo ' &nbsp; MySQL Versiyon: <strong>' . htmlspecialchars($versiyon) . '</strong>';
                $faturaCount = $pdo->query("SELECT COUNT(*) AS c FROM faturalar")->fetch()['c'];
                $musteriCount = $pdo->query("SELECT COUNT(*) AS c FROM musteriler")->fetch()['c'];
                echo '<br><br>';
                echo "📄 Toplam Fatura: <strong>$faturaCount</strong> &nbsp;&nbsp; ";
                echo "👥 Toplam Müşteri: <strong>$musteriCount</strong>";
            } catch (Exception $e) {
                echo '<span class="badge-test badge-fail">❌ Bağlantı Hatası</span><br>';
                echo '<small style="color:#991b1b;">' . htmlspecialchars($e->getMessage()) . '</small>';
            }
            ?>
        </div>
    </div>

</div>

<script>
function baglantiTest() {
    const btn = document.querySelector('[onclick="baglantiTest()"]');
    const sonuc = document.getElementById('testSonuc');
    btn.disabled = true;
    sonuc.innerHTML = '<span class="badge-test badge-wait">⏳ Test ediliyor...</span>';
    fetch('login_test.php').then(r=>r.text()).then(t=>{
        sonuc.innerHTML = (t.includes('✅')||t.toLowerCase().includes('başarılı')||t.toLowerCase().includes('session'))
            ? '<span class="badge-test badge-ok">✅ Bağlantı Başarılı</span>'
            : '<span class="badge-test badge-fail">❌ Bağlantı Hatası</span>';
    }).catch(()=>{ sonuc.innerHTML='<span class="badge-test badge-fail">❌ Sunucu Hatası</span>'; })
    .finally(()=>{ btn.disabled=false; });
}

// ── Yardımcılar ──────────────────────────────────────────────────────
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
async function api(params, method='GET'){
    const url = method==='GET' ? 'tutanak_kontrol.php?'+new URLSearchParams(params) : 'tutanak_kontrol.php';
    const r = await fetch(url, method==='POST' ? {method:'POST',body:new URLSearchParams(params)} : {});
    return r.json();
}

// ── Yetkili Servis Eşleştirme ────────────────────────────────────────
const FIRMA_UNVAN = <?= json_encode($config['firma_unvan'] ?? '') ?>;
let eslMarkalarCache = [];

async function eslYukle(){
    // Markaları yükle
    const mk = await api({action:'get_markalar_tutanak'});
    if(mk.success){
        eslMarkalarCache = mk.data;
        const sel = document.getElementById('eslMarka');
        mk.data.forEach(m=>{
            const o = document.createElement('option');
            o.value = m.id; o.textContent = m.marka_adi;
            sel.appendChild(o);
        });
    }
    // Firma ünvanını config'den doldur (boşsa)
    const fu = document.getElementById('eslFirmaUnvan');
    if(!fu.value && FIRMA_UNVAN) fu.value = FIRMA_UNVAN;
    // Listeyi yükle
    await eslListeYukle();
}

async function eslMarkaSecildi(){
    const markaId = document.getElementById('eslMarka').value;
    const sel = document.getElementById('eslModel');
    sel.innerHTML = '<option value="">-- Tüm Modeller --</option>';
    if(!markaId) return;
    const r = await api({action:'get_modeller_tutanak', marka_id: markaId});
    if(r.success) r.data.forEach(m=>{
        const o = document.createElement('option');
        o.value = m.id; o.textContent = m.model_adi;
        sel.appendChild(o);
    });
}

async function eslListeYukle(){
    const r = await api({action:'get_eslestirme_listesi'});
    document.getElementById('eslYukleniyor').style.display = 'none';
    const liste = document.getElementById('eslListe');
    liste.style.display = 'block';
    const tbody = document.getElementById('eslBody');
    if(!r.success || !r.data.length){
        tbody.innerHTML = '<tr><td colspan="11" style="padding:20px;text-align:center;color:#9ca3af;">Kayıt bulunamadı</td></tr>';
        return;
    }
    tbody.innerHTML = r.data.map(e=>`
        <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:9px 12px;">${esc(e.urun_adi||'—')}</td>
            <td style="padding:9px 12px;font-weight:600;">${esc(e.marka_adi||'—')}</td>
            <td style="padding:9px 12px;">${esc(e.model_adi||'Tümü')}</td>
            <td style="padding:9px 12px;">${esc(e.firma_unvan)}</td>
            <td style="padding:9px 12px;font-family:monospace;font-size:12px;">${esc(e.yetki_no||'—')}</td>
            <td style="padding:9px 12px;font-family:monospace;font-size:12px;">${esc(e.muhur_no||'—')}</td>
            <td style="padding:9px 12px;text-align:right;">${e.devir_bedeli ? Number(e.devir_bedeli).toLocaleString('tr-TR',{minimumFractionDigits:2})+'₺' : '—'}</td>
            <td style="padding:9px 12px;text-align:center;">${e.devir_kdv!=null?'%'+e.devir_kdv:'—'}</td>
            <td style="padding:9px 12px;text-align:right;">${e.hurda_bedeli ? Number(e.hurda_bedeli).toLocaleString('tr-TR',{minimumFractionDigits:2})+'₺' : '—'}</td>
            <td style="padding:9px 12px;text-align:center;">${e.hurda_kdv!=null?'%'+e.hurda_kdv:'—'}</td>
            <td style="padding:9px 12px;white-space:nowrap;">
                <button onclick="eslDuzenle(${e.id})" style="padding:4px 10px;border:none;border-radius:6px;background:#fef3c7;color:#92400e;cursor:pointer;font-size:12px;">✏️</button>
                <button onclick="eslSil(${e.id})" style="padding:4px 10px;border:none;border-radius:6px;background:#fee2e2;color:#991b1b;cursor:pointer;font-size:12px;margin-left:4px;">🗑️</button>
            </td>
        </tr>
    `).join('');
}

async function eslKaydet(){
    const markaId = document.getElementById('eslMarka').value;
    if(!markaId){ alert('Marka seçiniz'); return; }
    const firmaUnvan = document.getElementById('eslFirmaUnvan').value.trim();
    if(!firmaUnvan){ alert('Firma ünvanı boş olamaz'); return; }
    const params = {
        action: 'save_eslestirme',
        id:           document.getElementById('eslId').value||'',
        urun_adi:     document.getElementById('eslUrun').value.trim(),
        marka_id:     markaId,
        model_id:     document.getElementById('eslModel').value||'',
        firma_unvan:  firmaUnvan,
        yetki_no:     document.getElementById('eslYetkiNo').value.trim(),
        muhur_no:     document.getElementById('eslMuhurNo').value.trim(),
        devir_bedeli: document.getElementById('eslDevirBedeli').value||'',
        devir_kdv:    document.getElementById('eslDevirKdv').value||'18',
        hurda_bedeli: document.getElementById('eslHurdaBedeli').value||'',
        hurda_kdv:    document.getElementById('eslHurdaKdv').value||'20'
    };
    const r = await api(params, 'POST');
    if(r.success){ eslFormSifirla(); eslListeYukle(); }
    else alert(r.message||'Hata');
}

function eslFormSifirla(){
    document.getElementById('eslId').value = '';
    document.getElementById('eslUrun').value = '';
    document.getElementById('eslMarka').value = '';
    document.getElementById('eslModel').innerHTML = '<option value="">-- Tüm Modeller --</option>';
    document.getElementById('eslFirmaUnvan').value = FIRMA_UNVAN||'';
    document.getElementById('eslYetkiNo').value = '';
    document.getElementById('eslMuhurNo').value = '';
    document.getElementById('eslDevirBedeli').value = '';
    document.getElementById('eslDevirKdv').value = '18';
    document.getElementById('eslHurdaBedeli').value = '';
    document.getElementById('eslHurdaKdv').value = '20';
    document.getElementById('eslFormBaslik').textContent = '➕ Yeni Eşleştirme Ekle';
}

async function eslDuzenle(id){
    const r = await api({action:'get_eslestirme', id});
    if(!r.success||!r.data) return;
    const e = r.data;
    document.getElementById('eslId').value = e.id;
    document.getElementById('eslUrun').value = e.urun_adi||'';
    document.getElementById('eslMarka').value = e.marka_id||'';
    await eslMarkaSecildi();
    if(e.model_id) document.getElementById('eslModel').value = e.model_id;
    document.getElementById('eslFirmaUnvan').value = e.firma_unvan||'';
    document.getElementById('eslYetkiNo').value = e.yetki_no||'';
    document.getElementById('eslMuhurNo').value = e.muhur_no||'';
    document.getElementById('eslDevirBedeli').value = e.devir_bedeli||'';
    document.getElementById('eslDevirKdv').value = e.devir_kdv!=null ? e.devir_kdv : '18';
    document.getElementById('eslHurdaBedeli').value = e.hurda_bedeli||'';
    document.getElementById('eslHurdaKdv').value = e.hurda_kdv!=null ? e.hurda_kdv : '20';
    document.getElementById('eslFormBaslik').textContent = '✏️ Eşleştirme Düzenle';
    document.getElementById('eslForm').scrollIntoView({behavior:'smooth'});
}

async function eslSil(id){
    if(!confirm('Bu eşleştirmeyi silmek istediğinizden emin misiniz?')) return;
    const r = await api({action:'delete_eslestirme', id}, 'POST');
    if(r.success) eslListeYukle();
    else alert(r.message);
}

document.addEventListener('DOMContentLoaded', eslYukle);
</script>
</div><!-- /sayfa-icerik -->
</body>
</html>
