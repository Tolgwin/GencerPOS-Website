<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('urun');
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Ürün Yönetimi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Genel ── */
        .urun-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .urun-kart {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            overflow: hidden;
            transition: .2s;
        }

        .urun-kart:hover {
            box-shadow: 0 6px 20px #0002;
            transform: translateY(-2px);
        }

        .urun-kart img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #f5f7ff;
        }

        .urun-kart .kart-icerik {
            padding: 14px;
        }

        .urun-kart .kart-icerik h4 {
            margin: 0 0 4px;
            font-size: 14px;
        }

        .urun-kart .kart-icerik .kod {
            font-size: 11px;
            color: #888;
        }

        .urun-kart .fiyatlar {
            display: flex;
            gap: 8px;
            margin: 10px 0;
            flex-wrap: wrap;
        }

        .fiyat-etiket {
            background: #f0f4ff;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
        }

        .fiyat-etiket span {
            font-weight: 700;
            color: #4361ee;
        }

        .stok-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .stok-ok {
            background: #d1fae5;
            color: #065f46;
        }

        .stok-az {
            background: #fef3c7;
            color: #92400e;
        }

        .stok-yok {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ── Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: #0006;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.aktif {
            display: flex;
        }

        .modal-kutu {
            background: #fff;
            border-radius: 16px;
            width: min(780px, 95vw);
            max-height: 90vh;
            overflow-y: auto;
            padding: 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-grid .tam {
            grid-column: 1/-1;
        }

        .form-grup label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .form-grup input,
        .form-grup select,
        .form-grup textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #dde3f0;
            border-radius: 8px;
            font-size: 13px;
            box-sizing: border-box;
        }

        /* ── Tabs ── */
        .tab-bar {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }

        .tab-btn.aktif {
            border-bottom-color: #4361ee;
            color: #4361ee;
            font-weight: 700;
        }

        .tab-icerik {
            display: none;
        }

        .tab-icerik.aktif {
            display: block;
        }

        /* ── Tablo ── */
        .tablo-kap {
            overflow-x: auto;
        }

        table.veri-tablo {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table.veri-tablo th {
            background: #f8f9ff;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            color: #666;
        }

        table.veri-tablo td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        table.veri-tablo tr:hover td {
            background: #fafbff;
        }

        /* ── Rapor ── */
        .rapor-ozet {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .rapor-kart {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 2px 8px #0001;
        }

        .rapor-kart .baslik {
            font-size: 12px;
            color: #888;
        }

        .rapor-kart .deger {
            font-size: 22px;
            font-weight: 700;
            color: #4361ee;
            margin-top: 6px;
        }
    </style>
</head>

<body>
<?php require_once 'menu.php'; ?>
    <div class="container">

        <!-- Üst Bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h2 style="margin:0">📦 Ürün Yönetimi</h2>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-primary" onclick="modalAc()">+ Yeni Ürün</button>
                <a href="index.php" class="btn">← Ana Sayfa</a>
            </div>
        </div>

        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn aktif" onclick="tabAc('urunler',this)">📦 Ürünler</button>
            <button class="tab-btn" onclick="tabAc('hareketler',this)">🔄 Hareketler</button>
            <button class="tab-btn" onclick="tabAc('rapor',this)">📊 Raporlar</button>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: ÜRÜNLER                                          -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="tab-urunler" class="tab-icerik aktif">
            <!-- Filtreler -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                <input id="araInput" type="text" placeholder="🔍 Ürün adı veya kodu..."
                    style="padding:9px 14px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;flex:1;min-width:200px;"
                    oninput="urunListeYukle()">
                <select id="kategoriFiltre"
                    style="padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;"
                    onchange="urunListeYukle()">
                    <option value="">Tüm Kategoriler</option>
                </select>
                <select id="gorselMod"
                    style="padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;"
                    onchange="urunListeYukle()">
                    <option value="kart">🃏 Kart Görünüm</option>
                    <option value="tablo">📋 Tablo Görünüm</option>
                </select>
            </div>

            <div id="urunListeKap"></div>
            <div id="sayfalamaKap" style="text-align:center;margin-top:16px;"></div>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: HAREKETLER                                       -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="tab-hareketler" class="tab-icerik">
            <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                <select id="hareketUrunFiltre"
                    style="padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;flex:1;min-width:200px;"
                    onchange="hareketListeYukle()">
                    <option value="0">Tüm Ürünler</option>
                </select>
                <button class="btn btn-primary" onclick="hareketModalAc()">+ Hareket Ekle</button>
            </div>
            <div class="tablo-kap">
                <table class="veri-tablo">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Ürün</th>
                            <th>Tip</th>
                            <th>Miktar</th>
                            <th>Birim Fiyat</th>
                            <th>Toplam</th>
                            <th>Kimden</th>
                            <th>Kime</th>
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody id="hareketTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: RAPORLAR                                         -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="tab-rapor" class="tab-icerik">
            <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
                <select id="raporTip" onchange="raporYukle()"
                    style="padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                    <option value="stok">📦 Stok Değer Raporu</option>
                    <option value="hareket_ozet">📈 Hareket Özet Raporu</option>
                </select>
                <input type="date" id="raporBas"
                    style="padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                <input type="date" id="raporBit"
                    style="padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                <button class="btn btn-primary" onclick="raporYukle()">🔄 Yenile</button>
                <button class="btn" onclick="raporExcel()">⬇️ Excel</button>
            </div>
            <div id="raporOzetKap" class="rapor-ozet"></div>
            <div class="tablo-kap">
                <table class="veri-tablo">
                    <thead>
                        <tr id="raporBaslikTr"></tr>
                    </thead>
                    <tbody id="raporTbody"></tbody>
                </table>
            </div>
        </div>

    </div><!-- /container -->

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL: ÜRÜN EKLE / DÜZENLE                               -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="urunModal">
        <div class="modal-kutu">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 id="modalBaslik" style="margin:0">Yeni Ürün</h3>
                <button onclick="modalKapat()"
                    style="background:none;border:none;font-size:22px;cursor:pointer;">✕</button>
            </div>
            <form id="urunForm" enctype="multipart/form-data" onsubmit="urunKaydet(event)">
                <input type="hidden" id="urunId" name="id">
                <input type="hidden" id="mevcutResim" name="mevcut_resim">
                <div class="form-grid">
                    <div class="form-grup">
                        <label>Ürün Kodu *</label>
                        <input type="text" name="urun_kodu" id="fUrunKodu" required>
                    </div>
                    <div class="form-grup">
                        <label>Ürün Adı *</label>
                        <input type="text" name="ad" id="fAd" required>
                    </div>
                    <div class="form-grup">
                        <label>Kategori</label>
                        <select name="kategori_id" id="fKategori">
                            <option value="">— Seçiniz —</option>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label>Tedarikçi</label>
                        <select name="tedarikci_id" id="fTedarikci">
                            <option value="">— Seçiniz —</option>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label>Alış Fiyatı (₺)</label>
                        <input type="number" step="0.01" name="alis_fiyati" id="fAlis" value="0">
                    </div>
                    <div class="form-grup">
                        <label>Satış Fiyatı (₺)</label>
                        <input type="number" step="0.01" name="satis_fiyati" id="fSatis" value="0">
                    </div>
                    <div class="form-grup">
                        <label>Bayi Fiyatı (₺)</label>
                        <input type="number" step="0.01" name="bayi_fiyati" id="fBayi" value="0">
                    </div>
                    <div class="form-grup">
                        <label>KDV Oranı (%)</label>
                        <select name="kdv_orani" id="fKdv">
                            <option value="0">%0</option>
                            <option value="1">%1</option>
                            <option value="10">%10</option>
                            <option value="20" selected>%20</option>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label>Seri No Takibi</label>
                        <select name="seri_no_takip" id="fSeriTakip">
                            <option value="0">Hayır</option>
                            <option value="1">Evet</option>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label>Ürün Resmi</label>
                        <input type="file" name="resim" accept="image/*">
                    </div>
                    <div class="form-grup tam">
                        <label>Açıklama</label>
                        <textarea name="aciklama" id="fAciklama" rows="3"></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" class="btn" onclick="modalKapat()">İptal</button>
                    <button type="submit" class="btn btn-primary">💾 Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODAL: HAREKET EKLE                                      -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="hareketModal">
        <div class="modal-kutu" style="max-width:520px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h3 style="margin:0">Stok Hareketi Ekle</h3>
                <button onclick="hareketModalKapat()"
                    style="background:none;border:none;font-size:22px;cursor:pointer;">✕</button>
            </div>
            <div class="form-grid">
                <div class="form-grup tam">
                    <label>Ürün *</label>
                    <select id="hUrunId"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                        <option value="">— Ürün Seçiniz —</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Hareket Tipi *</label>
                    <select id="hTip"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                        <option value="giris">📥 Giriş</option>
                        <option value="cikis">📤 Çıkış</option>
                        <option value="iade">↩️ İade</option>
                        <option value="sayim">📋 Sayım</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Miktar *</label>
                    <input type="number" step="0.001" id="hMiktar" min="0.001"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div class="form-grup">
                    <label>Birim Fiyat (₺)</label>
                    <input type="number" step="0.01" id="hBirimFiyat" value="0"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div class="form-grup">
                    <label>Kaynak Tipi</label>
                    <select id="hKaynakTip"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                        <option value="tedarikci">Tedarikçi</option>
                        <option value="musteri">Müşteri</option>
                        <option value="depo">Depo</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Kimden (Ad)</label>
                    <input type="text" id="hKaynakAd"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div class="form-grup">
                    <label>Hedef Tipi</label>
                    <select id="hHedefTip"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                        <option value="musteri">Müşteri</option>
                        <option value="tedarikci">Tedarikçi</option>
                        <option value="depo">Depo</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Kime (Ad)</label>
                    <input type="text" id="hHedefAd"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div class="form-grup">
                    <label>Seri No</label>
                    <input type="text" id="hSeriNo"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div class="form-grup">
                    <label>Tarih</label>
                    <input type="datetime-local" id="hTarih"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div class="form-grup tam">
                    <label>Açıklama</label>
                    <textarea id="hAciklama" rows="2"
                        style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button class="btn" onclick="hareketModalKapat()">İptal</button>
                <button class="btn btn-primary" onclick="hareketKaydet()">💾 Kaydet</button>
            </div>
        </div>
    </div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL: SERİ NO YÖNETİMİ                                   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="seriNoModal">
    <div class="modal-kutu" style="max-width:560px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 id="seriNoModalBaslik" style="margin:0;font-size:16px;color:#1e3a8a;">🔢 Seri No Yönetimi</h3>
            <button onclick="seriNoModalKapat()" style="background:none;border:none;font-size:22px;cursor:pointer;">✕</button>
        </div>
        <!-- Tekli/Toplu ekleme -->
        <div style="background:#f8faff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:14px;">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">➕ Seri No Ekle</div>
            <div style="display:flex;gap:8px;margin-bottom:8px;">
                <input type="text" id="yeniSeriNoInput" placeholder="Başlangıç seri no (ör: SN2024001)" style="flex:1;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;" oninput="seriOnizleGuncelle()">
                <input type="number" id="yeniSeriAdet" value="1" min="1" max="500" style="width:80px;padding:9px 10px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;text-align:center;" title="Kaç adet eklenecek" oninput="seriOnizleGuncelle()">
                <button class="btn btn-primary" onclick="seriNoEkle()">+ Ekle</button>
            </div>
            <div id="seriOnizleme" style="font-size:11px;color:#6b7280;min-height:16px;"></div>
        </div>
        <div style="margin-bottom:8px;font-size:12px;font-weight:600;color:#374151;">Stokta Kayıtlı Seri Numaraları:</div>
        <div id="seriNoListesi" style="max-height:320px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;"></div>
        <input type="hidden" id="seriNoAktifUrunId">
    </div>
</div>

    <script>
        // ── Yardımcılar ──────────────────────────────────────────────────────────────
        const para = v => parseFloat(v || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
        let mevcutSayfa = 1;

        // ── Tab ──────────────────────────────────────────────────────────────────────
        function tabAc(id, btn) {
            document.querySelectorAll('.tab-icerik').forEach(t => t.classList.remove('aktif'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('aktif'));
            document.getElementById('tab-' + id).classList.add('aktif');
            btn.classList.add('aktif');
            if (id === 'hareketler') hareketListeYukle();
            if (id === 'rapor') raporYukle();
        }

        // ── Sayfa Yüklenince ─────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            const bugun = new Date().toISOString().split('T')[0];
            const ayBas = bugun.substring(0, 8) + '01';
            document.getElementById('raporBas').value = ayBas;
            document.getElementById('raporBit').value = bugun;
            document.getElementById('hTarih').value = new Date().toISOString().slice(0, 16);
            kategoriListeYukle();
            urunListeYukle();
        });

        // ── Kategori & Tedarikçi Yükle ───────────────────────────────────────────────
        function kategoriListeYukle() {
            fetch('urun_kontrol.php?action=kategori_liste')
                .then(r => r.json()).then(v => {
                    const opts = v.kategoriler.map(k =>
                        `<option value="${k.id}">${k.ad}</option>`).join('');
                    document.getElementById('kategoriFiltre').innerHTML =
                        '<option value="">Tüm Kategoriler</option>' + opts;
                    document.getElementById('fKategori').innerHTML =
                        '<option value="">— Seçiniz —</option>' + opts;
                });

            fetch('urun_kontrol.php?action=tedarikci_liste')
                .then(r => r.json()).then(v => {
                    const opts = v.tedarikciler.map(t =>
                        `<option value="${t.id}">${t.ad}</option>`).join('');
                    document.getElementById('fTedarikci').innerHTML =
                        '<option value="">— Seçiniz —</option>' + opts;
                });
        }

        // ── Ürün Listesi ─────────────────────────────────────────────────────────────
        function urunListeYukle(sayfa = 1) {
            mevcutSayfa = sayfa;
            const ara = document.getElementById('araInput').value;
            const kategori = document.getElementById('kategoriFiltre').value;
            const mod = document.getElementById('gorselMod').value;

            fetch(`urun_kontrol.php?action=urun_liste&ara=${encodeURIComponent(ara)}&kategori_id=${kategori}&sayfa=${sayfa}`)
                .then(r => r.json()).then(v => {
                    const kap = document.getElementById('urunListeKap');

                    if (mod === 'kart') {
                        kap.innerHTML = `<div class="urun-grid">
                ${v.urunler.map(u => urunKartHtml(u)).join('')}
            </div>`;
                    } else {
                        kap.innerHTML = `
            <div class="tablo-kap">
            <table class="veri-tablo">
                <thead><tr>
                    <th>Resim</th><th>Kod</th><th>Ürün Adı</th><th>Kategori</th>
                    <th>Alış</th><th>Satış</th><th>Bayi</th>
                    <th>KDV</th><th>Stok</th><th>İşlem</th>
                </tr></thead>
                <tbody>
                ${v.urunler.map(u => `
                <tr>
                    <td><img src="${u.resim || 'uploads/urunler/default.png'}"
                             style="width:40px;height:40px;object-fit:cover;border-radius:6px;"></td>
                    <td><code>${u.urun_kodu}</code></td>
                    <td><strong>${u.ad}</strong></td>
                    <td>${u.kategori_adi || '—'}</td>
                    <td>${para(u.alis_fiyati)} ₺</td>
                    <td>${para(u.satis_fiyati)} ₺</td>
                    <td>${para(u.bayi_fiyati)} ₺</td>
                    <td>%${u.kdv_orani}</td>
                    <td>${stokBadge(u.stok_adeti)}</td>
                    <td>
                        <button class="btn" style="padding:4px 10px;font-size:12px;"
                                onclick='urunDuzenle(${JSON.stringify(u)})'>✏️</button>
                        <button class="btn" style="padding:4px 10px;font-size:12px;"
                                onclick="urunSil(${u.id})">🗑️</button>
                    </td>
                </tr>`).join('')}
                </tbody>
            </table></div>`;
                    }

                    // Sayfalama
                    const sp = document.getElementById('sayfalamaKap');
                    sp.innerHTML = '';
                    for (let i = 1; i <= v.toplam_sayfa; i++) {
                        sp.innerHTML += `<button class="btn${i === sayfa ? ' btn-primary' : ''}"
                style="margin:2px;padding:6px 12px;"
                onclick="urunListeYukle(${i})">${i}</button>`;
                    }
                });
        }

        function urunKartHtml(u) {
            const resim = u.resim ? u.resim : 'https://via.placeholder.com/260x160?text=📦';
            return `
    <div class="urun-kart">
        <img src="${resim}" alt="${u.ad}">
        <div class="kart-icerik">
            <div class="kod">📦 ${u.urun_kodu} ${u.seri_no_takip == '1' ? '<span style="color:#4361ee">· Seri No</span>' : ''}</div>
            <h4>${u.ad}</h4>
            <div style="font-size:11px;color:#888;margin-bottom:6px;">
                ${u.kategori_adi || ''} ${u.tedarikci_adi ? '· ' + u.tedarikci_adi : ''}
            </div>
            <div class="fiyatlar">
                <div class="fiyat-etiket">Alış <span>${para(u.alis_fiyati)}₺</span></div>
                <div class="fiyat-etiket">Satış <span>${para(u.satis_fiyati)}₺</span></div>
                <div class="fiyat-etiket">Bayi <span>${para(u.bayi_fiyati)}₺</span></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                ${stokBadge(u.stok_adeti)}
                <div style="display:flex;gap:6px;">
                    <button class="btn" style="padding:4px 10px;font-size:12px;"
                            onclick='urunDuzenle(${JSON.stringify(u)})'>✏️</button>
                    <button class="btn" style="padding:4px 10px;font-size:12px;"
                            onclick="urunSil(${u.id})">🗑️</button>
                    ${u.seri_no_takip == '1' ? `<button class="btn btn-primary" style="padding:4px 10px;font-size:12px;" onclick="seriNoModalAc(${u.id},'${esc(u.ad)}')">🔢 Seri</button>` : ''}
                </div>
            </div>
        </div>
    </div>`;
        }

        function stokBadge(adet) {
            const a = parseFloat(adet);
            if (a <= 0) return `<span class="stok-badge stok-yok">Stok Yok (${a})</span>`;
            if (a <= 5) return `<span class="stok-badge stok-az">Az Stok (${a})</span>`;
            return `<span class="stok-badge stok-ok">Stokta (${a})</span>`;
        }

        // ── Ürün Modal ───────────────────────────────────────────────────────────────
        function modalAc() {
            document.getElementById('urunId').value = '';
            document.getElementById('urunForm').reset();
            document.getElementById('modalBaslik').textContent = 'Yeni Ürün';
            document.getElementById('urunModal').classList.add('aktif');
        }

        function modalKapat() {
            document.getElementById('urunModal').classList.remove('aktif');
        }

        function urunDuzenle(u) {
            document.getElementById('modalBaslik').textContent = 'Ürün Düzenle';
            document.getElementById('urunId').value = u.id;
            document.getElementById('mevcutResim').value = u.resim || '';
            document.getElementById('fUrunKodu').value = u.urun_kodu;
            document.getElementById('fAd').value = u.ad;
            document.getElementById('fKategori').value = u.kategori_id || '';
            document.getElementById('fTedarikci').value = u.tedarikci_id || '';
            document.getElementById('fAlis').value = u.alis_fiyati;
            document.getElementById('fSatis').value = u.satis_fiyati;
            document.getElementById('fBayi').value = u.bayi_fiyati;
            document.getElementById('fKdv').value = u.kdv_orani;
            document.getElementById('fSeriTakip').value = u.seri_no_takip;
            document.getElementById('fAciklama').value = u.aciklama || '';
            document.getElementById('urunModal').classList.add('aktif');
        }

        function urunKaydet(e) {
            e.preventDefault();
            const fd = new FormData(document.getElementById('urunForm'));
            fd.append('action', 'urun_kaydet');
            fetch('urun_kontrol.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(v => {
                    if (v.basari) {
                        modalKapat();
                        urunListeYukle(mevcutSayfa);
                        alert('✅ ' + v.mesaj);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        function urunSil(id) {
            if (!confirm('Bu ürünü pasife almak istediğinizden emin misiniz?')) return;
            fetch('urun_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=urun_sil&id=${id}`
            }).then(r => r.json()).then(v => {
                if (v.basari) urunListeYukle(mevcutSayfa);
            });
        }

        // ── Hareket Modal ────────────────────────────────────────────────────────────
        function hareketModalAc(urunId = '') {
            // Ürün select'i doldur
            fetch('urun_kontrol.php?action=urun_liste&durum=aktif&sayfa=1')
                .then(r => r.json()).then(v => {
                    document.getElementById('hUrunId').innerHTML =
                        '<option value="">— Ürün Seçiniz —</option>' +
                        v.urunler.map(u =>
                            `<option value="${u.id}" ${u.id == urunId ? 'selected' : ''}>${u.urun_kodu} — ${u.ad}</option>`
                        ).join('');
                    // Hareket select'ini de doldur
                    document.getElementById('hareketUrunFiltre').innerHTML =
                        '<option value="0">Tüm Ürünler</option>' +
                        v.urunler.map(u =>
                            `<option value="${u.id}">${u.urun_kodu} — ${u.ad}</option>`
                        ).join('');
                });
            document.getElementById('hareketModal').classList.add('aktif');
        }

        function hareketModalKapat() {
            document.getElementById('hareketModal').classList.remove('aktif');
        }

        function hareketKaydet() {
            const params = new URLSearchParams({
                action: 'hareket_ekle',
                urun_id: document.getElementById('hUrunId').value,
                hareket_tipi: document.getElementById('hTip').value,
                miktar: document.getElementById('hMiktar').value,
                birim_fiyat: document.getElementById('hBirimFiyat').value,
                kaynak_tip: document.getElementById('hKaynakTip').value,
                kaynak_ad: document.getElementById('hKaynakAd').value,
                hedef_tip: document.getElementById('hHedefTip').value,
                hedef_ad: document.getElementById('hHedefAd').value,
                seri_no: document.getElementById('hSeriNo').value,
                aciklama: document.getElementById('hAciklama').value,
                tarih: document.getElementById('hTarih').value,
            });

            fetch('urun_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            }).then(r => r.json()).then(v => {
                if (v.basari) {
                    hareketModalKapat();
                    hareketListeYukle();
                    urunListeYukle(mevcutSayfa);
                    alert('✅ ' + v.mesaj);
                } else {
                    alert('❌ ' + v.mesaj);
                }
            });
        }

        // ── Hareket Listesi ──────────────────────────────────────────────────────────
        function hareketListeYukle() {
            const urunId = document.getElementById('hareketUrunFiltre')?.value || 0;
            fetch(`urun_kontrol.php?action=hareket_liste&urun_id=${urunId}`)
                .then(r => r.json()).then(v => {
                    const renkler = {
                        giris: '#d1fae5', cikis: '#fee2e2',
                        iade: '#fef3c7', sayim: '#e0e7ff', transfer: '#f3e8ff'
                    };
                    document.getElementById('hareketTbody').innerHTML =
                        v.hareketler.map(h => `
            <tr style="background:${renkler[h.hareket_tipi] || '#fff'}20">
                <td style="white-space:nowrap">${new Date(h.tarih).toLocaleString('tr-TR')}</td>
                <td><strong>${h.urun_adi}</strong><br>
                    <small style="color:#888">${h.urun_kodu}</small></td>
                <td><span style="background:${renkler[h.hareket_tipi]};
                          padding:3px 8px;border-radius:12px;font-size:11px;font-weight:600;">
                    ${h.hareket_tipi.toUpperCase()}</span></td>
                <td>${parseFloat(h.miktar).toLocaleString('tr-TR')}</td>
                <td>${para(h.birim_fiyat)} ₺</td>
                <td><strong>${para(h.toplam_tutar)} ₺</strong></td>
                <td>${h.kaynak_ad || '—'}<br>
                    <small style="color:#888">${h.kaynak_tip}</small></td>
                <td>${h.hedef_ad || '—'}<br>
                    <small style="color:#888">${h.hedef_tip}</small></td>
                <td style="color:#666;font-size:12px;">${h.aciklama || ''}</td>
            </tr>`).join('');
                });
        }

        // ── Raporlar ─────────────────────────────────────────────────────────────────
        function raporYukle() {
            const tip = document.getElementById('raporTip').value;
            const bas = document.getElementById('raporBas').value;
            const bit = document.getElementById('raporBit').value;

            fetch(`urun_kontrol.php?action=rapor&tip=${tip}&bas=${bas}&bit=${bit}`)
                .then(r => r.json()).then(v => {
                    const rows = v.rapor;
                    if (!rows || !rows.length) {
                        document.getElementById('raporTbody').innerHTML =
                            '<tr><td colspan="10" style="text-align:center;color:#aaa;padding:30px">Veri bulunamadı</td></tr>';
                        return;
                    }

                    if (tip === 'stok') {
                        // Özet kartlar
                        const topAlıs = rows.reduce((s, r) => s + parseFloat(r.stok_alis_degeri || 0), 0);
                        const topSatis = rows.reduce((s, r) => s + parseFloat(r.stok_satis_degeri || 0), 0);
                        const topKar = topSatis - topAlıs;
                        document.getElementById('raporOzetKap').innerHTML = `
                <div class="rapor-kart">
                    <div class="baslik">📦 Toplam Ürün Çeşidi</div>
                    <div class="deger">${rows.length}</div>
                </div>
                <div class="rapor-kart">
                    <div class="baslik">💰 Stok Alış Değeri</div>
                    <div class="deger">${para(topAlıs)} ₺</div>
                </div>
                <div class="rapor-kart">
                    <div class="baslik">💵 Stok Satış Değeri</div>
                    <div class="deger">${para(topSatis)} ₺</div>
                </div>
                <div class="rapor-kart">
                    <div class="baslik">📈 Potansiyel Kâr</div>
                    <div class="deger" style="color:#10b981">${para(topKar)} ₺</div>
                </div>`;

                        document.getElementById('raporBaslikTr').innerHTML = `
                <th>Kod</th><th>Ürün</th><th>Kategori</th>
                <th>Stok</th><th>Alış</th><th>Satış</th><th>Bayi</th>
                <th>Stok Alış Değ.</th><th>Stok Satış Değ.</th><th>Kâr Marjı</th>`;

                        document.getElementById('raporTbody').innerHTML = rows.map(r => `
                <tr>
                    <td><code>${r.urun_kodu}</code></td>
                    <td>${r.ad}</td>
                    <td>${r.kategori || '—'}</td>
                    <td>${parseFloat(r.stok_adeti).toLocaleString('tr-TR')}</td>
                    <td>${para(r.alis_fiyati)} ₺</td>
                    <td>${para(r.satis_fiyati)} ₺</td>
                    <td>${para(r.bayi_fiyati)} ₺</td>
                    <td>${para(r.stok_alis_degeri)} ₺</td>
                    <td>${para(r.stok_satis_degeri)} ₺</td>
                    <td style="color:${r.kar_marji >= 0 ? '#10b981' : '#ef4444'}">
                        ${para(r.kar_marji)} ₺</td>
                </tr>`).join('');
                    } else if (tip === 'hareket_ozet') {
                        document.getElementById('raporOzetKap').innerHTML = '';
                        document.getElementById('raporBaslikTr').innerHTML = `
                <th>Ürün</th><th>Giriş Miktarı</th><th>Çıkış Miktarı</th>
                <th>İade Miktarı</th><th>Sayım Miktarı</th><th>Net Değişim</th>`;

                        document.getElementById('raporTbody').innerHTML = rows.map(r => `
                <tr>
                    <td><strong>${r.ad}</strong><br><small style="color:#888">${r.urun_kodu}</small></td>
                    <td>${parseFloat(r.giris_miktar).toLocaleString('tr-TR')}</td>
                    <td>${parseFloat(r.cikis_miktar).toLocaleString('tr-TR')}</td>
                    <td>${parseFloat(r.iade_miktar).toLocaleString('tr-TR')}</td>
                    <td>${parseFloat(r.sayim_miktar).toLocaleString('tr-TR')}</td>
                    <td style="color:${r.net_degisiklik >= 0 ? '#10b981' : '#ef4444'}">
                        ${para(r.net_degisiklik)} </td>
                </tr>`).join('');
                    }
                });
        }
        function raporExcel() {
            const tip = document.getElementById('raporTip').value;
            const bas = document.getElementById('raporBas').value;
            const bit = document.getElementById('raporBit').value;
            window.open(`urun_kontrol.php?action=rapor_excel&tip=${tip}&bas=${bas}&bit=${bit}`, '_blank');
        }

        // ── SERİ NO YÖNETİMİ ─────────────────────────────────────────────────────
        function seriNoModalAc(urunId, urunAd) {
            document.getElementById('seriNoAktifUrunId').value = urunId;
            document.getElementById('seriNoModalBaslik').textContent = '🔢 Seri No Yönetimi — ' + urunAd;
            document.getElementById('yeniSeriNoInput').value = '';
            document.getElementById('seriNoModal').classList.add('aktif');
            seriNoListeYukle(urunId);
        }

        function seriNoModalKapat() {
            document.getElementById('seriNoModal').classList.remove('aktif');
            urunListeYukle(mevcutSayfa);
        }

        function seriNoListeYukle(urunId) {
            const kap = document.getElementById('seriNoListesi');
            kap.innerHTML = '<div style="padding:16px;text-align:center;color:#9ca3af;">⏳ Yükleniyor...</div>';
            fetch('urun_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=seri_listele_tumu&urun_id=' + urunId
            }).then(r => r.json()).then(v => {
                const liste = v.seriler || [];
                if (!liste.length) {
                    kap.innerHTML = '<div style="padding:16px;text-align:center;color:#9ca3af;">📭 Seri numarası kaydı yok.</div>';
                    return;
                }
                const durumRenk = { stokta: '#10b981', satildi: '#ef4444', iade: '#f59e0b' };
                kap.innerHTML = liste.map(s => `
                    <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid #f3f4f6;">
                        <span style="font-family:monospace;font-weight:600;flex:1;">${esc(s.seri_no)}</span>
                        <span style="font-size:11px;font-weight:700;color:${durumRenk[s.durum]||'#888'};background:#f9fafb;border-radius:5px;padding:2px 8px;">${s.durum}</span>
                        <button onclick="seriNoSil(${s.id})" style="padding:3px 8px;font-size:11px;border:none;background:#fee2e2;color:#991b1b;border-radius:5px;cursor:pointer;">🗑</button>
                    </div>`).join('');
            });
        }

        function seriUretListesi(basSeriNo, adet) {
            if (!basSeriNo || adet < 1) return [];
            const match = basSeriNo.match(/^(.*?)(\d+)([^\d]*)$/);
            if (!match) {
                return adet === 1 ? [basSeriNo] : [];
            }
            const prefix = match[1], numStr = match[2], suffix = match[3];
            const padLen = numStr.length;
            const basNum = parseInt(numStr, 10);
            const liste = [];
            for (let i = 0; i < adet; i++) {
                liste.push(prefix + String(basNum + i).padStart(padLen, '0') + suffix);
            }
            return liste;
        }

        function seriOnizleGuncelle() {
            const seriNo = document.getElementById('yeniSeriNoInput').value.trim();
            const adet = parseInt(document.getElementById('yeniSeriAdet').value) || 1;
            const div = document.getElementById('seriOnizleme');
            if (!seriNo) { div.textContent = ''; return; }
            const liste = seriUretListesi(seriNo, Math.min(adet, 5));
            if (adet === 1) {
                div.textContent = '→ ' + seriNo;
            } else if (liste.length > 0) {
                const goster = liste.slice(0, 3).join(', ') + (adet > 3 ? ` ... ${liste[liste.length-1]} (toplam ${adet} adet)` : '');
                div.textContent = '→ ' + goster;
            } else {
                div.textContent = `→ ${adet} adet (sabit seri no kopyası)`;
            }
        }

        function seriNoEkle() {
            const urunId = document.getElementById('seriNoAktifUrunId').value;
            const seriNo = document.getElementById('yeniSeriNoInput').value.trim();
            const adet = parseInt(document.getElementById('yeniSeriAdet').value) || 1;
            if (!seriNo) { alert('Seri no boş olamaz.'); return; }
            const liste = seriUretListesi(seriNo, adet);
            const seriListeJson = liste.length > 0 ? JSON.stringify(liste) : JSON.stringify([seriNo]);
            fetch('urun_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=seri_ekle_toplu&urun_id=${encodeURIComponent(urunId)}&seri_liste=${encodeURIComponent(seriListeJson)}`
            }).then(r => r.json()).then(v => {
                if (v.basari) {
                    document.getElementById('yeniSeriNoInput').value = '';
                    document.getElementById('yeniSeriAdet').value = '1';
                    document.getElementById('seriOnizleme').textContent = '';
                    seriNoListeYukle(urunId);
                    if (v.atlanan > 0) alert(`✅ ${v.eklenen} seri no eklendi. ${v.atlanan} zaten kayıtlıydı, atlandı.`);
                } else {
                    alert('❌ ' + v.mesaj);
                }
            });
        }

        function seriNoSil(id) {
            if (!confirm('Bu seri numarasını silmek istediğinizden emin misiniz?')) return;
            const urunId = document.getElementById('seriNoAktifUrunId').value;
            fetch('urun_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=seri_sil&id=${id}`
            }).then(r => r.json()).then(v => {
                if (v.basari) seriNoListeYukle(urunId);
                else alert('❌ ' + v.mesaj);
            });
        }
    </script>
</div><!-- /sayfa-icerik -->
</body>

</html>