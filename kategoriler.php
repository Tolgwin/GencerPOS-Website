<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('urun');
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Kategori & Tedarikçi Yönetimi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Kart Grid ── */
        .kt-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .kt-kart {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0001;
            padding: 20px;
            transition: .2s;
            border-left: 4px solid #4361ee;
        }

        .kt-kart:hover {
            box-shadow: 0 6px 20px #0002;
            transform: translateY(-2px);
        }

        .kt-kart.tedarikci {
            border-left-color: #10b981;
        }

        .kt-kart .kart-baslik {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .kt-kart h4 {
            margin: 0;
            font-size: 15px;
        }

        .kt-kart .meta {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .kt-kart .badge-urun {
            background: #e0e7ff;
            color: #3730a3;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .kt-kart .iletisim {
            margin-top: 10px;
            font-size: 12px;
            color: #555;
        }

        .kt-kart .iletisim span {
            display: block;
            margin-bottom: 3px;
        }

        .kt-kart .kart-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        /* ── Tab ── */
        .tab-bar {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 10px 24px;
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
            width: min(560px, 95vw);
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
            grid-column: 1 / -1;
        }

        .form-grup label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .form-grup input,
        .form-grup textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #dde3f0;
            border-radius: 8px;
            font-size: 13px;
            box-sizing: border-box;
        }

        .form-grup textarea {
            resize: vertical;
        }

        /* ── Hareket Panel ── */
        .hareket-panel {
            display: none;
            position: fixed;
            inset: 0;
            background: #0006;
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .hareket-panel.aktif {
            display: flex;
        }

        .hareket-kutu {
            background: #fff;
            border-radius: 16px;
            width: min(860px, 95vw);
            max-height: 90vh;
            overflow-y: auto;
            padding: 28px;
        }

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

        /* ── Arama ── */
        .arama-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .arama-bar input {
            padding: 9px 14px;
            border: 1px solid #dde3f0;
            border-radius: 8px;
            font-size: 13px;
            flex: 1;
            min-width: 200px;
        }

        /* ── Boş durum ── */
        .bos-durum {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }

        .bos-durum .ikon {
            font-size: 48px;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
<?php require_once 'menu.php'; ?>
    <div class="container">

        <!-- Üst Bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h2 style="margin:0">🗂️ Kategori & Tedarikçi</h2>
            <a href="index.php" class="btn">← Ana Sayfa</a>
        </div>

        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn aktif" onclick="tabAc('kategoriler', this)">
                🏷️ Kategoriler
            </button>
            <button class="tab-btn" onclick="tabAc('tedarikciler', this)">
                🏭 Tedarikçiler
            </button>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: KATEGORİLER                                      -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="tab-kategoriler" class="tab-icerik aktif">
            <div class="arama-bar">
                <input type="text" id="katAra" placeholder="🔍 Kategori ara..." oninput="kategoriListeYukle()">
                <button class="btn btn-primary" onclick="katModalAc()">+ Yeni Kategori</button>
            </div>
            <div id="katGrid" class="kt-grid"></div>
        </div>

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- TAB: TEDARİKÇİLER — Üst Bar                              -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div id="tab-tedarikciler" class="tab-icerik">
            <div class="arama-bar">
                <input type="text" id="tedarikcAraInput" placeholder="🔍 Tedarikçi ara..."
                    oninput="tedarikcileriYukle()">
                <button class="btn btn-primary" onclick="tedModalAc()">
                    + Yeni Tedarikçi
                </button>
            </div>
            <div id="tedGrid" class="kt-grid"></div>
        </div>


        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- MODAL: KATEGORİ EKLE / DÜZENLE                           -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div class="modal-overlay" id="katModal">
            <div class="modal-kutu" style="max-width:420px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h3 id="katModalBaslik" style="margin:0">Yeni Kategori</h3>
                    <button onclick="katModalKapat()"
                        style="background:none;border:none;font-size:22px;cursor:pointer;">✕</button>
                </div>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <input type="hidden" id="katId">
                    <div class="form-grup">
                        <label>Kategori Adı *</label>
                        <input type="text" id="katAd" placeholder="Örn: Elektronik">
                    </div>
                    <div class="form-grup">
                        <label>Açıklama</label>
                        <textarea id="katAciklama" rows="3" placeholder="Kategori hakkında kısa açıklama..."></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button class="btn" onclick="katModalKapat()">İptal</button>
                    <button class="btn btn-primary" onclick="kategoriKaydet()">💾 Kaydet</button>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: e-FATURA                                         -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="tab-efatura" class="tab-icerik">
            <div class="arama-bar">
                <h3 style="margin:0">📄 e-Fatura Yönetimi</h3>
                <button class="btn btn-primary" onclick="faturaListeYukle()">
                    🔄 Yenile
                </button>
            </div>
            <div id="faturaGrid"></div>
        </div>

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- MODAL: FATURA OLUŞTUR                                      -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div class="modal-overlay" id="faturaModal">
            <div class="modal-kutu" style="max-width:900px;width:95%;">

                <!-- Başlık -->
                <div style="display:flex;justify-content:space-between;
                    align-items:center;margin-bottom:20px;">
                    <h3 style="margin:0">📄 Yeni e-Fatura</h3>
                    <button onclick="faturaModalKapat()" style="background:none;border:none;
                           font-size:22px;cursor:pointer;">✕</button>
                </div>

                <!-- Hata Alanı -->
                <div id="faturaHata" style="display:none;background:#fee2e2;color:#991b1b;
                    padding:10px 14px;border-radius:8px;
                    margin-bottom:14px;font-size:13px;">
                </div>

                <!-- Üst Bilgiler -->
                <div class="form-grid" style="margin-bottom:20px;">
                    <div class="form-grup">
                        <label>Fatura No *</label>
                        <input type="text" id="fatNo" placeholder="FAT2024000001" style="font-family:monospace;">
                    </div>
                    <div class="form-grup">
                        <label>Fatura Tarihi *</label>
                        <input type="date" id="fatTarih">
                    </div>
                    <div class="form-grup">
                        <label>Alıcı VKN / TCKN *</label>
                        <div style="display:flex;gap:6px;">
                            <input type="text" id="fatAliciVkn" placeholder="1234567890" style="flex:1;"
                                oninput="vknKontrolEt(this.value)">
                            <button class="btn" style="white-space:nowrap;" onclick="vknSorgula()">
                                🔍 Sorgula
                            </button>
                        </div>
                        <!-- GİB kayıt durumu -->
                        <div id="vknDurum" style="font-size:11px;margin-top:4px;"></div>
                    </div>
                    <div class="form-grup">
                        <label>Alıcı Ünvanı *</label>
                        <input type="text" id="fatAliciUnvan" placeholder="Firma Adı">
                    </div>
                    <div class="form-grup tam">
                        <label>Alıcı Adresi</label>
                        <input type="text" id="fatAliciAdres" placeholder="Açık adres">
                    </div>
                </div>

                <!-- ── ÜRÜN EKLEME ALANI ── -->
                <div style="background:#f8fafc;border-radius:12px;
                    padding:16px;margin-bottom:16px;">

                    <label style="font-weight:700;font-size:13px;
                          display:block;margin-bottom:10px;">
                        ➕ Ürün / Hizmet Ekle
                    </label>

                    <!-- Ürün Arama -->
                    <div style="position:relative;margin-bottom:12px;">
                        <input type="text" id="urunAraInput" placeholder="🔍 Ürün adı veya kodu ile ara..."
                            oninput="urunAra(this.value)" autocomplete="off" style="width:100%;padding:10px 14px;
                              border:1px solid #e2e8f0;border-radius:8px;
                              font-size:13px;box-sizing:border-box;">

                        <!-- Autocomplete Dropdown -->
                        <div id="urunDropdown" style="display:none;position:absolute;top:100%;left:0;
                            right:0;background:white;border:1px solid #e2e8f0;
                            border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.1);
                            z-index:9999;max-height:280px;overflow-y:auto;">
                        </div>
                    </div>

                    <!-- Seçilen Ürün Satırı — Düzenleme -->
                    <div id="urunEkleSatir" style="display:none;background:white;border:1px solid #e2e8f0;
                        border-radius:8px;padding:14px;">
                        <div style="display:grid;
                            grid-template-columns:2fr 1fr 1fr 1fr auto;
                            gap:10px;align-items:end;">

                            <div>
                                <label style="font-size:11px;color:#888;">
                                    Ürün Adı
                                </label>
                                <input type="text" id="satirUrunAdi" style="width:100%;padding:8px;
                                      border:1px solid #e2e8f0;
                                      border-radius:6px;font-size:13px;" readonly>
                            </div>

                            <div>
                                <label style="font-size:11px;color:#888;">
                                    Miktar
                                </label>
                                <input type="number" id="satirMiktar" value="1" min="0.01" step="0.01"
                                    oninput="satirHesapla()" style="width:100%;padding:8px;
                                      border:1px solid #e2e8f0;
                                      border-radius:6px;font-size:13px;">
                            </div>

                            <div>
                                <label style="font-size:11px;color:#888;">
                                    Birim Fiyat (₺)
                                </label>
                                <input type="number" id="satirBirimFiyat" step="0.01" min="0" oninput="satirHesapla()"
                                    style="width:100%;padding:8px;
                                      border:1px solid #e2e8f0;
                                      border-radius:6px;font-size:13px;">
                            </div>

                            <div>
                                <label style="font-size:11px;color:#888;">
                                    KDV %
                                </label>
                                <select id="satirKdv" onchange="satirHesapla()" style="width:100%;padding:8px;
                                       border:1px solid #e2e8f0;
                                       border-radius:6px;font-size:13px;">
                                    <option value="0">%0</option>
                                    <option value="1">%1</option>
                                    <option value="10">%10</option>
                                    <option value="20" selected>%20</option>
                                </select>
                            </div>

                            <div>
                                <label style="font-size:11px;color:#888;">
                                    &nbsp;
                                </label>
                                <button class="btn btn-primary" onclick="satirEkle()"
                                    style="padding:8px 16px;white-space:nowrap;">
                                    ✚ Ekle
                                </button>
                            </div>
                        </div>

                        <!-- Satır Önizleme -->
                        <div id="satirOnizleme" style="margin-top:10px;padding:8px 12px;
                            background:#f0fdf4;border-radius:6px;
                            font-size:12px;color:#065f46;display:none;">
                        </div>
                    </div>
                </div>

                <!-- ── FATURA SATIRLARI TABLOSU ── -->
                <div style="margin-bottom:16px;">
                    <table id="fatSatirTable" style="width:100%;border-collapse:collapse;
                          font-size:13px;display:none;">
                        <thead>
                            <tr style="background:#f8fafc;font-weight:700;">
                                <th style="padding:10px;text-align:left;">#</th>
                                <th style="padding:10px;text-align:left;">Ürün / Hizmet</th>
                                <th style="padding:10px;text-align:left;">Kod</th>
                                <th style="padding:10px;text-align:right;">Miktar</th>
                                <th style="padding:10px;text-align:right;">Birim Fiyat</th>
                                <th style="padding:10px;text-align:right;">KDV %</th>
                                <th style="padding:10px;text-align:right;">KDV Tutar</th>
                                <th style="padding:10px;text-align:right;">Toplam</th>
                                <th style="padding:10px;text-align:center;">Sil</th>
                            </tr>
                        </thead>
                        <tbody id="fatSatirBody"></tbody>
                        <tfoot>
                            <tr style="background:#f8fafc;font-weight:700;">
                                <td colspan="6" style="padding:10px;text-align:right;">
                                    Ara Toplam:
                                </td>
                                <td id="totKdv" style="padding:10px;text-align:right;
                                   color:#f59e0b;">
                                    0,00 ₺
                                </td>
                                <td id="totMatrah" style="padding:10px;text-align:right;
                                   color:#10b981;font-size:15px;">
                                    0,00 ₺
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" style="padding:10px;text-align:right;
                                   font-weight:700;">
                                    Genel Toplam (KDV Dahil):
                                </td>
                                <td id="totGenel" style="padding:10px;text-align:right;
                                   font-weight:800;font-size:16px;
                                   color:#1e40af;">
                                    0,00 ₺
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Boş durum -->
                    <div id="fatSatirBos" style="text-align:center;padding:30px;
                        color:#aaa;border:2px dashed #e2e8f0;
                        border-radius:8px;">
                        Yukarıdan ürün arayarak satır ekleyin
                    </div>
                </div>

                <!-- Alt Butonlar -->
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button class="btn" onclick="faturaModalKapat()">İptal</button>
                    <button class="btn" id="fatKaydetBtn" onclick="faturaKaydet(false)"
                        style="background:#f59e0b;color:white;border:none;">
                        💾 Taslak Kaydet
                    </button>
                    <button class="btn btn-primary" id="fatGonderBtn" onclick="faturaKaydet(true)">
                        📤 Kaydet & Gönder
                    </button>
                </div>

            </div>
        </div>



        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- MODAL: TEDARİKÇİ EKLE / DÜZENLE                          -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div class="modal-overlay" id="tedModal">
            <div class="modal-kutu">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h3 id="tedModalBaslik" style="margin:0">Yeni Tedarikçi</h3>
                    <button onclick="tedModalKapat()"
                        style="background:none;border:none;font-size:22px;cursor:pointer;">✕</button>
                </div>
                <input type="hidden" id="tedId">
                <div class="form-grid">
                    <div class="form-grup tam">
                        <label>Tedarikçi Adı *</label>
                        <input type="text" id="tedAd" placeholder="Firma / Kişi adı">
                    </div>
                    <div class="form-grup">
                        <label>Telefon</label>
                        <input type="tel" id="tedTelefon" placeholder="0212 000 00 00">
                    </div>
                    <div class="form-grup">
                        <label>E-posta</label>
                        <input type="email" id="tedEmail" placeholder="info@firma.com">
                    </div>
                    <div class="form-grup">
                        <label>Vergi No</label>
                        <input type="text" id="tedVergiNo" placeholder="1234567890">
                    </div>
                    <div class="form-grup">
                        <label>Vergi Dairesi</label>
                        <input type="text" id="tedVergiDairesi" placeholder="Kadıköy V.D.">
                    </div>
                    <div class="form-grup tam">
                        <label>Adres</label>
                        <textarea id="tedAdres" rows="2" placeholder="Açık adres..."></textarea>
                    </div>
                    <div class="form-grup tam">
                        <label>Notlar</label>
                        <textarea id="tedNotlar" rows="2" placeholder="Ödeme vadesi, özel koşullar..."></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button class="btn" onclick="tedModalKapat()">İptal</button>
                    <button class="btn btn-primary" onclick="tedarikciKaydet()">💾 Kaydet</button>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- PANEL: TEDARİKÇİ HAREKETLERİ                             -->
        <!-- ══════════════════════════════════════════════════════════ -->
        <div class="hareket-panel" id="hareketPanel">
            <div class="hareket-kutu">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h3 id="hareketPanelBaslik" style="margin:0">Tedarikçi Hareketleri</h3>
                    <button onclick="hareketPanelKapat()"
                        style="background:none;border:none;font-size:22px;cursor:pointer;">✕</button>
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
                                <th>Açıklama</th>
                            </tr>
                        </thead>
                        <tbody id="hareketTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            const para = v => parseFloat(v || 0).toLocaleString('tr-TR',
                { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // ── Tab ──────────────────────────────────────────────────────────────────────
            function tabAc(id, btn) {
                document.querySelectorAll('.tab-icerik').forEach(t => t.classList.remove('aktif'));
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('aktif'));
                document.getElementById('tab-' + id).classList.add('aktif');
                btn.classList.add('aktif');

                if (id === 'kategoriler') {
                    setTimeout(() => kategoriListeYukle(), 50);
                }
                if (id === 'tedarikciler') {
                    setTimeout(() => tedarikcileriYukle(), 50);
                }
            }

            // ── Sayfa Yüklenince ─────────────────────────────────────────────────────────
            document.addEventListener('DOMContentLoaded', () => {
                kategoriListeYukle();
            });

            // ════════════════════════════════════════════════════════════
            // KATEGORİ
            // ════════════════════════════════════════════════════════════
            function kategoriListeYukle() {
                const ara = document.getElementById('katAra').value;
                fetch(`kategori_tedarikci_kontrol.php?action=kategori_liste&ara=${encodeURIComponent(ara)}`)
                    .then(r => r.json()).then(v => {
                        const kap = document.getElementById('katGrid');
                        if (!v.kategoriler.length) {
                            kap.innerHTML = `
                <div class="bos-durum" style="grid-column:1/-1">
                    <div class="ikon">🏷️</div>
                    <p>Henüz kategori eklenmemiş.</p>
                </div>`;
                            return;
                        }
                        kap.innerHTML = v.kategoriler.map(k => `
            <div class="kt-kart">
                <div class="kart-baslik">
                    <div>
                        <h4>🏷️ ${k.ad}</h4>
                        <div class="meta">${k.aciklama || '<i>Açıklama yok</i>'}</div>
                    </div>
                    <span class="badge-urun">${k.urun_sayisi} ürün</span>
                </div>
                <div class="kart-footer">
                    <button class="btn" style="padding:5px 12px;font-size:12px;"
                            onclick='katDuzenle(${JSON.stringify(k)})'>✏️ Düzenle</button>
                    <button class="btn" style="padding:5px 12px;font-size:12px;color:#ef4444;"
                            onclick="kategoriSil(${k.id}, '${k.ad}')">🗑️ Sil</button>
                </div>
            </div>`).join('');
                    });
            }

            function katModalAc() {
                document.getElementById('katId').value = '';
                document.getElementById('katAd').value = '';
                document.getElementById('katAciklama').value = '';
                document.getElementById('katModalBaslik').textContent = 'Yeni Kategori';
                document.getElementById('katModal').classList.add('aktif');
                setTimeout(() => document.getElementById('katAd').focus(), 100);
            }

            function katModalKapat() {
                document.getElementById('katModal').classList.remove('aktif');
            }

            function katDuzenle(k) {
                document.getElementById('katId').value = k.id;
                document.getElementById('katAd').value = k.ad;
                document.getElementById('katAciklama').value = k.aciklama || '';
                document.getElementById('katModalBaslik').textContent = 'Kategori Düzenle';
                document.getElementById('katModal').classList.add('aktif');
            }

            function kategoriKaydet() {
                const id = document.getElementById('katId').value;
                const ad = document.getElementById('katAd').value.trim();
                if (!ad) { alert('⚠️ Kategori adı zorunludur.'); return; }

                fetch('kategori_tedarikci_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'kategori_kaydet',
                        id,
                        ad,
                        aciklama: document.getElementById('katAciklama').value
                    }).toString()
                }).then(r => r.json()).then(v => {
                    if (v.basari) {
                        katModalKapat();
                        kategoriListeYukle();
                        alert('✅ ' + v.mesaj);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
            }

            function kategoriSil(id, ad) {
                if (!confirm(`"${ad}" kategorisini silmek istediğinizden emin misiniz?`)) return;
                fetch('kategori_tedarikci_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=kategori_sil&id=${id}`
                }).then(r => r.json()).then(v => {
                    if (v.basari) {
                        kategoriListeYukle();
                        alert('✅ ' + v.mesaj);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
            }

            // ════════════════════════════════════════════════════════
            // e-FATURA — JS FONKSİYONLARI
            // ════════════════════════════════════════════════════════

            function faturaListeYukle() {
                fetch('fatura_gonder_kontrol.php?action=fatura_liste')
                    .then(r => r.json())
                    .then(v => {
                        const kap = document.getElementById('faturaGrid');

                        if (!v.faturalar?.length) {
                            kap.innerHTML = `
                <div class="bos-durum">
                    <div class="ikon">📄</div>
                    <p>Gönderilecek fatura bulunamadı.</p>
                </div>`;
                            return;
                        }

                        kap.innerHTML = `
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;font-weight:700;">
                    <th style="padding:12px;text-align:left;">Fatura No</th>
                    <th style="padding:12px;text-align:left;">Alıcı</th>
                    <th style="padding:12px;text-align:right;">Tutar</th>
                    <th style="padding:12px;text-align:center;">Durum</th>
                    <th style="padding:12px;text-align:center;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                ${v.faturalar.map(f => `
                <tr style="border-top:1px solid #f0f0f0;">
                    <td style="padding:12px;">
                        <strong>${escHtml(f.fatura_no)}</strong><br>
                        <small style="color:#888">${f.tarih}</small>
                    </td>
                    <td style="padding:12px;">${escHtml(f.alici_unvan ?? '—')}</td>
                    <td style="padding:12px;text-align:right;font-weight:700;color:#10b981;">
                        ${para(f.toplam)} ₺
                    </td>
                    <td style="padding:12px;text-align:center;">
                        ${durumBadge(f.efatura_durum)}
                    </td>
                    <td style="padding:12px;text-align:center;">
                        ${f.ettn
                                ? `<button class="btn" style="font-size:11px;"
                                       onclick="durumSorgula('${f.ettn}')">
                                   🔍 Durum
                               </button>`
                                : `<button class="btn btn-primary" style="font-size:11px;"
                                       onclick="faturaGonder(${f.id}, '${escHtml(f.fatura_no)}')">
                                   📤 Gönder
                               </button>`
                            }
                    </td>
                </tr>`).join('')}
            </tbody>
        </table>`;
                    });
            }

            function durumBadge(durum) {
                const renkler = {
                    'gonderildi': 'background:#d1fae5;color:#065f46',
                    'beklemede': 'background:#fef3c7;color:#92400e',
                    'hata': 'background:#fee2e2;color:#991b1b',
                    'WAITING': 'background:#dbeafe;color:#1e40af',
                    'SUCCESS': 'background:#d1fae5;color:#065f46',
                    'ERROR': 'background:#fee2e2;color:#991b1b',
                };
                const stil = renkler[durum] || 'background:#f3f4f6;color:#374151';
                return `<span style="${stil};padding:3px 10px;
                   border-radius:12px;font-size:11px;font-weight:600;">
                ${durum ?? 'beklemede'}
            </span>`;
            }

            function faturaGonder(id, no) {
                if (!confirm(`"${no}" numaralı faturayı QNB üzerinden göndermek istiyor musunuz?`)) return;

                fetch('fatura_gonder_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=fatura_gonder&fatura_id=${id}`
                })
                    .then(r => r.json())
                    .then(v => {
                        if (v.basari) {
                            alert(`✅ Gönderildi!\nETTN: ${v.ettn}`);
                            faturaListeYukle();
                        } else {
                            alert('❌ ' + v.mesaj);
                        }
                    });
            }

            function durumSorgula(ettn) {
                fetch('fatura_gonder_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=durum_sorgula&ettn=${ettn}`
                })
                    .then(r => r.json())
                    .then(v => alert(`📊 Durum: ${v.durum}\n${v.mesaj}`));
            }


            // ════════════════════════════════════════════════════════════
            // TEDARİKÇİ
            // ════════════════════════════════════════════════════════════
            function tedarikcileriYukle() {
                // getElementById yerine querySelector ile güvenli al
                const araEl = document.getElementById('tedarikcAraInput');
                const ara = araEl ? araEl.value : '';   // null ise boş string kullan

                fetch(`kategori_tedarikci_kontrol.php?action=tedarikci_liste&ara=${encodeURIComponent(ara)}`)
                    .then(r => r.json())
                    .then(v => {
                        const kap = document.getElementById('tedGrid');
                        if (!kap) return;

                        if (!v.basari) {
                            kap.innerHTML = `
                <div class="bos-durum" style="grid-column:1/-1">
                    <div class="ikon">⚠️</div>
                    <p style="color:#ef4444">${v.mesaj}</p>
                </div>`;
                            return;
                        }

                        if (!v.tedarikciler || !v.tedarikciler.length) {
                            kap.innerHTML = `
                <div class="bos-durum" style="grid-column:1/-1">
                    <div class="ikon">🏭</div>
                    <p>Henüz tedarikçi eklenmemiş.</p>
                    <button class="btn btn-primary"
                            style="margin-top:12px;"
                            onclick="tedModalAc()">
                        + İlk Tedarikçiyi Ekle
                    </button>
                </div>`;
                            return;
                        }

                        kap.innerHTML = v.tedarikciler.map(t => `
            <div class="kt-kart tedarikci">
                <div class="kart-baslik">
                    <div>
                        <h4>🏭 ${escHtml(t.ad)}</h4>
                        <div class="meta">
                            ${t.vergi_no
                                ? `Vergi No: ${escHtml(t.vergi_no)}`
                                : '<i>Vergi no girilmemiş</i>'}
                            ${t.vergi_dairesi
                                ? ` · ${escHtml(t.vergi_dairesi)}`
                                : ''}
                        </div>
                    </div>
                    <span class="badge-urun"
                          style="background:#d1fae5;color:#065f46;">
                        ${t.urun_sayisi} ürün
                    </span>
                </div>
                <div class="iletisim">
                    ${t.telefon ? `<span>📞 ${escHtml(t.telefon)}</span>` : ''}
                    ${t.email ? `<span>✉️ ${escHtml(t.email)}</span>` : ''}
                    ${t.adres ? `<span>📍 ${escHtml(t.adres)}</span>` : ''}
                </div>
                <div style="margin-top:10px;padding:8px 12px;
                            background:#f0fdf4;border-radius:8px;font-size:12px;">
                    <strong>Toplam Alış:</strong>
                    <span style="color:#10b981;font-weight:700;">
                        ${para(t.toplam_alis)} ₺
                    </span>
                </div>
                ${t.notlar ? `
                <div style="margin-top:8px;font-size:11px;color:#888;
                             font-style:italic;border-top:1px solid #f0f0f0;
                             padding-top:8px;">
                    📝 ${escHtml(t.notlar)}
                </div>` : ''}
                <div class="kart-footer">
                    <button class="btn"
                            style="padding:5px 12px;font-size:12px;"
                            onclick="hareketleriGoster(${t.id},'${escHtml(t.ad)}')">
                        📋 Hareketler
                    </button>
                    <button class="btn"
                            style="padding:5px 12px;font-size:12px;"
                            onclick='tedDuzenle(${JSON.stringify(t)})'>
                        ✏️ Düzenle
                    </button>
                    <button class="btn"
                            style="padding:5px 12px;font-size:12px;color:#ef4444;"
                            onclick="tedarikciSil(${t.id},'${escHtml(t.ad)}')">
                        🗑️ Sil
                    </button>
                </div>
            </div>`).join('');
                    })
                    .catch(err => {
                        console.error('Tedarikçi yükleme hatası:', err);
                    });
            }



            function tedModalAc() {
                document.getElementById('tedId').value = '';
                document.getElementById('tedAd').value = '';
                document.getElementById('tedTelefon').value = '';
                document.getElementById('tedEmail').value = '';
                document.getElementById('tedVergiNo').value = '';
                document.getElementById('tedVergiDairesi').value = '';
                document.getElementById('tedAdres').value = '';
                document.getElementById('tedNotlar').value = '';
                document.getElementById('tedModalBaslik').textContent = 'Yeni Tedarikçi';
                document.getElementById('tedModal').classList.add('aktif');
                setTimeout(() => document.getElementById('tedAd').focus(), 100);
            }

            function tedModalKapat() {
                document.getElementById('tedModal').classList.remove('aktif');
            }

            function tedDuzenle(t) {
                document.getElementById('tedId').value = t.id;
                document.getElementById('tedAd').value = t.ad;
                document.getElementById('tedTelefon').value = t.telefon || '';
                document.getElementById('tedEmail').value = t.email || '';
                document.getElementById('tedVergiNo').value = t.vergi_no || '';
                document.getElementById('tedVergiDairesi').value = t.vergi_dairesi || '';
                document.getElementById('tedAdres').value = t.adres || '';
                document.getElementById('tedNotlar').value = t.notlar || '';
                document.getElementById('tedModalBaslik').textContent = 'Tedarikçi Düzenle';
                document.getElementById('tedModal').classList.add('aktif');
            }

            function tedarikciKaydet() {
                const id = document.getElementById('tedId').value;
                const ad = document.getElementById('tedAd').value.trim();
                if (!ad) { alert('⚠️ Tedarikçi adı zorunludur.'); return; }

                fetch('kategori_tedarikci_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'tedarikci_kaydet',
                        id,
                        ad,
                        telefon: document.getElementById('tedTelefon').value,
                        email: document.getElementById('tedEmail').value,
                        vergi_no: document.getElementById('tedVergiNo').value,
                        vergi_dairesi: document.getElementById('tedVergiDairesi').value,
                        adres: document.getElementById('tedAdres').value,
                        notlar: document.getElementById('tedNotlar').value,
                    }).toString()
                }).then(r => r.json()).then(v => {
                    if (v.basari) {
                        tedModalKapat();
                        tedarikcileriYukle();
                        alert('✅ ' + v.mesaj);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
            }

            function tedarikciSil(id, ad) {
                if (!confirm(`"${ad}" tedarikçisini silmek istediğinizden emin misiniz?`)) return;
                fetch('kategori_tedarikci_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=tedarikci_sil&id=${id}`
                }).then(r => r.json()).then(v => {
                    if (v.basari) {
                        tedarikcileriYukle();
                        alert('✅ ' + v.mesaj);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
            }

            // ── Tedarikçi Hareket Paneli ─────────────────────────────────────────────────
            function hareketleriGoster(id, ad) {
                document.getElementById('hareketPanelBaslik').textContent =
                    `📋 ${ad} — Alış Hareketleri`;
                document.getElementById('hareketTbody').innerHTML =
                    '<tr><td colspan="7" style="text-align:center;padding:20px;color:#aaa">Yükleniyor...</td></tr>';
                document.getElementById('hareketPanel').classList.add('aktif');

                fetch(`kategori_tedarikci_kontrol.php?action=tedarikci_hareketleri&id=${id}`)
                    .then(r => r.json()).then(v => {
                        if (!v.hareketler.length) {
                            document.getElementById('hareketTbody').innerHTML =
                                '<tr><td colspan="7" style="text-align:center;padding:30px;color:#aaa">Hareket bulunamadı</td></tr>';
                            return;
                        }
                        document.getElementById('hareketTbody').innerHTML =
                            v.hareketler.map(h => `
            <tr>
                <td style="white-space:nowrap">
                    ${new Date(h.tarih).toLocaleString('tr-TR')}
                </td>
                <td>
                    <strong>${h.urun_adi}</strong><br>
                    <small style="color:#888">${h.urun_kodu}</small>
                </td>
                <td>
                    <span style="background:#d1fae5;color:#065f46;
                                 padding:3px 8px;border-radius:12px;
                                 font-size:11px;font-weight:600;">
                        ${h.hareket_tipi.toUpperCase()}
                    </span>
                </td>
                <td>${parseFloat(h.miktar).toLocaleString('tr-TR')}</td>
                <td>${para(h.birim_fiyat)} ₺</td>
                <td><strong>${para(h.toplam_tutar)} ₺</strong></td>
                <td style="font-size:12px;color:#666">${h.aciklama || '—'}</td>
            </tr>`).join('');
                    });
            }

            function hareketPanelKapat() {
                document.getElementById('hareketPanel').classList.remove('aktif');
            }

            // ════════════════════════════════════════════════════════════
            // FATURA — GLOBAL DEĞİŞKENLER
            // ════════════════════════════════════════════════════════════
            let fatSatirlar = [];   // Fatura satır dizisi
            let secilenUrun = null; // Şu an seçili ürün objesi
            let araTimeout = null; // Debounce timer

            // ════════════════════════════════════════════════════════════
            // MODAL AÇ / KAPAT
            // ════════════════════════════════════════════════════════════
            function faturaModalAc() {
                fatSatirlar = [];
                secilenUrun = null;

                // Formu sıfırla
                ['fatNo', 'fatAliciVkn', 'fatAliciUnvan', 'fatAliciAdres'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });

                // Tarihi bugün yap
                document.getElementById('fatTarih').value = bugunTarih();

                // Otomatik fatura no üret
                document.getElementById('fatNo').value =
                    'FAT' + new Date().getFullYear() +
                    String(Date.now()).slice(-6);

                // Hata ve VKN durumunu temizle
                const hata = document.getElementById('faturaHata');
                hata.style.display = 'none';
                document.getElementById('vknDurum').innerHTML = '';

                // Satır alanlarını sıfırla
                document.getElementById('urunAraInput').value = '';
                document.getElementById('urunDropdown').style.display = 'none';
                document.getElementById('urunEkleSatir').style.display = 'none';

                tabloGuncelle();
                document.getElementById('faturaModal').classList.add('aktif');
            }

            function faturaModalKapat() {
                document.getElementById('faturaModal').classList.remove('aktif');
                document.getElementById('urunDropdown').style.display = 'none';
            }

            // ════════════════════════════════════════════════════════════
            // ÜRÜN ARAMA — Debounce ile
            // ════════════════════════════════════════════════════════════
            function urunAra(q) {
                clearTimeout(araTimeout);
                const dd = document.getElementById('urunDropdown');

                if (q.length < 2) {
                    dd.style.display = 'none';
                    return;
                }

                // 300ms bekle — her tuşta istek atma
                araTimeout = setTimeout(() => {
                    fetch(`urunler_kontrol.php?action=urun_ara&q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(v => {
                            if (!v.basari || !v.urunler?.length) {
                                dd.innerHTML = `
                    <div style="padding:16px;text-align:center;color:#aaa;">
                        Ürün bulunamadı
                    </div>`;
                                dd.style.display = 'block';
                                return;
                            }

                            dd.innerHTML = v.urunler.map(u => `
                <div onclick='urunSec(${JSON.stringify(u)})'
                     style="padding:12px 16px;cursor:pointer;
                            border-bottom:1px solid #f0f0f0;
                            transition:background .15s;"
                     onmouseover="this.style.background='#f0fdf4'"
                     onmouseout="this.style.background='white'">
                    <div style="display:flex;
                                justify-content:space-between;
                                align-items:center;">
                        <div>
                            <strong style="font-size:13px;">
                                ${escHtml(u.urun_adi)}
                            </strong>
                            <span style="font-size:11px;color:#888;
                                         margin-left:8px;">
                                ${escHtml(u.urun_kodu ?? '')}
                            </span>
                            ${u.kategori_adi ? `
                            <span style="font-size:10px;
                                         background:#e0e7ff;color:#3730a3;
                                         padding:2px 6px;border-radius:8px;
                                         margin-left:6px;">
                                ${escHtml(u.kategori_adi)}
                            </span>` : ''}
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:700;color:#10b981;">
                                ${para(u.birim_fiyat)} ₺
                            </div>
                            <div style="font-size:11px;color:#888;">
                                KDV %${u.kdv_orani} · Stok: ${u.stok_miktari}
                            </div>
                        </div>
                    </div>
                </div>`).join('');

                            dd.style.display = 'block';
                        });
                }, 300);
            }

            // ════════════════════════════════════════════════════════════
            // ÜRÜN SEÇ — Dropdown'dan tıklanınca
            // ════════════════════════════════════════════════════════════
            function urunSec(u) {
                secilenUrun = u;

                // Input'u ürün adıyla doldur, dropdown'ı kapat
                document.getElementById('urunAraInput').value = u.urun_adi;
                document.getElementById('urunDropdown').style.display = 'none';

                // Satır düzenleme alanını doldur
                document.getElementById('satirUrunAdi').value = u.urun_adi;
                document.getElementById('satirBirimFiyat').value = parseFloat(u.birim_fiyat).toFixed(2);
                document.getElementById('satirMiktar').value = 1;

                // KDV select'ini ayarla
                const kdvSel = document.getElementById('satirKdv');
                const kdvVal = String(u.kdv_orani ?? 20);
                // Option yoksa ekle
                let found = false;
                for (let opt of kdvSel.options) {
                    if (opt.value === kdvVal) { opt.selected = true; found = true; break; }
                }
                if (!found) {
                    const opt = new Option(`%${kdvVal}`, kdvVal, true, true);
                    kdvSel.add(opt);
                }

                // Satır alanını göster
                document.getElementById('urunEkleSatir').style.display = 'block';
                document.getElementById('satirMiktar').focus();

                satirHesapla();
            }

            // ════════════════════════════════════════════════════════════
            // SATIR HESAPLA — Anlık önizleme
            // ════════════════════════════════════════════════════════════
            function satirHesapla() {
                const miktar = parseFloat(document.getElementById('satirMiktar').value) || 0;
                const birimFiyat = parseFloat(document.getElementById('satirBirimFiyat').value) || 0;
                const kdvOran = parseFloat(document.getElementById('satirKdv').value) || 0;

                const matrah = miktar * birimFiyat;
                const kdvTutar = matrah * kdvOran / 100;
                const toplam = matrah + kdvTutar;

                const onizleme = document.getElementById('satirOnizleme');
                if (miktar > 0 && birimFiyat > 0) {
                    onizleme.style.display = 'block';
                    onizleme.innerHTML = `
            <strong>${miktar} × ${para(birimFiyat)} ₺</strong>
            = Matrah: <strong>${para(matrah)} ₺</strong>
            + KDV(%${kdvOran}): <strong>${para(kdvTutar)} ₺</strong>
            = <strong style="font-size:14px;">Toplam: ${para(toplam)} ₺</strong>`;
                } else {
                    onizleme.style.display = 'none';
                }
            }

            // ════════════════════════════════════════════════════════════
            // SATIR EKLE — Tabloya ekle
            // ════════════════════════════════════════════════════════════
            function satirEkle() {
                if (!secilenUrun) {
                    alert('Lütfen önce bir ürün seçin.');
                    return;
                }

                const miktar = parseFloat(document.getElementById('satirMiktar').value) || 0;
                const birimFiyat = parseFloat(document.getElementById('satirBirimFiyat').value) || 0;
                const kdvOran = parseFloat(document.getElementById('satirKdv').value) || 0;

                if (miktar <= 0) { alert('Miktar 0\'dan büyük olmalı.'); return; }
                if (birimFiyat <= 0) { alert('Birim fiyat 0\'dan büyük olmalı.'); return; }

                const matrah = miktar * birimFiyat;
                const kdvTutar = matrah * kdvOran / 100;
                const toplam = matrah + kdvTutar;

                fatSatirlar.push({
                    urun_id: secilenUrun.id,
                    urun_kodu: secilenUrun.urun_kodu ?? '',
                    aciklama: secilenUrun.urun_adi,
                    birim: secilenUrun.birim ?? 'C62',
                    miktar: miktar,
                    birim_fiyat: birimFiyat,
                    kdv_orani: kdvOran,
                    kdv_tutar: kdvTutar,
                    matrah: matrah,
                    toplam: toplam,
                });

                // Alanları sıfırla
                secilenUrun = null;
                document.getElementById('urunAraInput').value = '';
                document.getElementById('urunEkleSatir').style.display = 'none';
                document.getElementById('satirOnizleme').style.display = 'none';

                tabloGuncelle();
            }

            // ════════════════════════════════════════════════════════════
            // SATIR SİL
            // ════════════════════════════════════════════════════════════
            function satirSil(idx) {
                fatSatirlar.splice(idx, 1);
                tabloGuncelle();
            }

            // ════════════════════════════════════════════════════════════
            // TABLO GÜNCELLE — Satırları ve toplamları yeniden çiz
            // ════════════════════════════════════════════════════════════
            function tabloGuncelle() {
                const tbody = document.getElementById('fatSatirBody');
                const tablo = document.getElementById('fatSatirTable');
                const bos = document.getElementById('fatSatirBos');

                if (!fatSatirlar.length) {
                    tablo.style.display = 'none';
                    bos.style.display = 'block';
                    document.getElementById('totKdv').textContent = '0,00 ₺';
                    document.getElementById('totMatrah').textContent = '0,00 ₺';
                    document.getElementById('totGenel').textContent = '0,00 ₺';
                    return;
                }

                tablo.style.display = 'table';
                bos.style.display = 'none';

                // Toplamları hesapla
                let topMatrah = 0, topKdv = 0, topGenel = 0;
                fatSatirlar.forEach(s => {
                    topMatrah += s.matrah;
                    topKdv += s.kdv_tutar;
                    topGenel += s.toplam;
                });

                // Satırları çiz
                tbody.innerHTML = fatSatirlar.map((s, i) => `
        <tr style="border-top:1px solid #f0f0f0;">
            <td style="padding:10px;color:#888;">${i + 1}</td>
            <td style="padding:10px;">
                <strong>${escHtml(s.aciklama)}</strong>
            </td>
            <td style="padding:10px;font-family:monospace;
                       font-size:11px;color:#888;">
                ${escHtml(s.urun_kodu)}
            </td>
            <td style="padding:10px;text-align:right;">
                ${s.miktar} ${escHtml(s.birim)}
            </td>
            <td style="padding:10px;text-align:right;">
                ${para(s.birim_fiyat)} ₺
            </td>
            <td style="padding:10px;text-align:right;">
                %${s.kdv_orani}
            </td>
            <td style="padding:10px;text-align:right;color:#f59e0b;">
                ${para(s.kdv_tutar)} ₺
            </td>
            <td style="padding:10px;text-align:right;
                       font-weight:700;color:#10b981;">
                ${para(s.toplam)} ₺
            </td>
            <td style="padding:10px;text-align:center;">
                <button onclick="satirSil(${i})"
                        style="background:none;border:none;
                               cursor:pointer;font-size:16px;
                               color:#ef4444;">🗑️</button>
            </td>
        </tr>`).join('');

                // Toplamları güncelle
                document.getElementById('totKdv').textContent = para(topKdv) + ' ₺';
                document.getElementById('totMatrah').textContent = para(topMatrah) + ' ₺';
                document.getElementById('totGenel').textContent = para(topGenel) + ' ₺';
            }

            // ════════════════════════════════════════════════════════════
            // VKN SORGULA — GİB kayıt kontrolü
            // ════════════════════════════════════════════════════════════
            function vknKontrolEt(vkn) {
                // Temizle
                if (vkn.length < 10) {
                    document.getElementById('vknDurum').innerHTML = '';
                }
            }

            function vknSorgula() {
                const vkn = document.getElementById('fatAliciVkn').value.trim();
                if (vkn.length < 10) {
                    alert('Geçerli bir VKN / TCKN girin (min. 10 hane).');
                    return;
                }

                document.getElementById('vknDurum').innerHTML =
                    '<span style="color:#888">⏳ Sorgulanıyor...</span>';

                fetch('fatura_gonder_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=alici_kontrol&vkn=${vkn}`
                })
                    .then(r => r.json())
                    .then(v => {
                        const el = document.getElementById('vknDurum');
                        if (v.kayitli) {
                            el.innerHTML = `
                <span style="color:#065f46;background:#d1fae5;
                             padding:2px 8px;border-radius:8px;">
                    ✅ e-Fatura mükellefi · ${escHtml(v.unvan)}
                </span>`;
                            // Ünvanı otomatik doldur
                            if (v.unvan) {
                                document.getElementById('fatAliciUnvan').value = v.unvan;
                            }
                        } else {
                            el.innerHTML = `
                <span style="color:#92400e;background:#fef3c7;
                             padding:2px 8px;border-radius:8px;">
                    ⚠️ e-Fatura mükellefi değil — e-Arşiv olarak gönderilecek
                </span>`;
                        }
                    });
            }

            // ════════════════════════════════════════════════════════════
            // FATURA KAYDET / GÖNDER
            // ════════════════════════════════════════════════════════════
            function faturaKaydet(gonder = false) {
                // Validasyon
                const fatNo = document.getElementById('fatNo').value.trim();
                const tarih = document.getElementById('fatTarih').value;
                const aliciVkn = document.getElementById('fatAliciVkn').value.trim();
                const aliciUnvan = document.getElementById('fatAliciUnvan').value.trim();
                const aliciAdres = document.getElementById('fatAliciAdres').value.trim();

                const hataEl = document.getElementById('faturaHata');
                const hatalar = [];

                if (!fatNo) hatalar.push('Fatura No zorunludur.');
                if (!tarih) hatalar.push('Tarih zorunludur.');
                if (!aliciVkn) hatalar.push('Alıcı VKN / TCKN zorunludur.');
                if (!aliciUnvan) hatalar.push('Alıcı ünvanı zorunludur.');
                if (!fatSatirlar.length) hatalar.push('En az bir ürün satırı ekleyin.');

                if (hatalar.length) {
                    hataEl.innerHTML = hatalar.map(h => `• ${h}`).join('<br>');
                    hataEl.style.display = 'block';
                    return;
                }

                hataEl.style.display = 'none';

                // Toplamları hesapla
                const matrah = fatSatirlar.reduce((t, s) => t + s.matrah, 0);
                const kdv = fatSatirlar.reduce((t, s) => t + s.kdv_tutar, 0);
                const toplam = matrah + kdv;

                // Butonları devre dışı bırak
                const kaydetBtn = document.getElementById('fatKaydetBtn');
                const gonderBtn = document.getElementById('fatGonderBtn');
                kaydetBtn.disabled = true;
                gonderBtn.disabled = true;
                gonderBtn.textContent = '⏳ İşleniyor...';

                const params = new URLSearchParams({
                    action: gonder ? 'fatura_kaydet_gonder' : 'fatura_kaydet',
                    fatura_no: fatNo,
                    tarih: tarih,
                    alici_vkn: aliciVkn,
                    alici_unvan: aliciUnvan,
                    alici_adres: aliciAdres,
                    matrah: matrah.toFixed(2),
                    kdv: kdv.toFixed(2),
                    toplam: toplam.toFixed(2),
                    satirlar: JSON.stringify(fatSatirlar),
                });

                fetch('fatura_gonder_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                    .then(r => r.json())
                    .then(v => {
                        if (v.basari) {
                            faturaModalKapat();
                            faturaListeYukle();
                            alert(gonder
                                ? `✅ Fatura gönderildi!\nETTN: ${v.ettn ?? '—'}`
                                : `✅ Taslak kaydedildi. Fatura No: ${v.fatura_no}`
                            );
                        } else {
                            hataEl.textContent = '❌ ' + v.mesaj;
                            hataEl.style.display = 'block';
                        }
                    })
                    .catch(err => alert('🚨 Hata: ' + err.message))
                    .finally(() => {
                        kaydetBtn.disabled = false;
                        gonderBtn.disabled = false;
                        gonderBtn.textContent = '📤 Kaydet & Gönder';
                    });
            }

            // ════════════════════════════════════════════════════════════
            // YARDIMCI — Bugünün tarihi (YYYY-MM-DD)
            // ════════════════════════════════════════════════════════════
            function bugunTarih() {
                return new Date().toISOString().split('T')[0];
            }

            // Dropdown dışına tıklayınca kapat
            document.addEventListener('click', e => {
                if (!e.target.closest('#urunAraInput') &&
                    !e.target.closest('#urunDropdown')) {
                    const dd = document.getElementById('urunDropdown');
                    if (dd) dd.style.display = 'none';
                }
            });

        </script>
</div><!-- /sayfa-icerik -->
</body>

</html>