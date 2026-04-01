<?php require_once 'db.php';
require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Hesapları</title>
</head>

<body>

    <?php require_once 'menu.php'; ?>

    <h1 style="font-size:22px;font-weight:700;color:#1e3a8a;margin-bottom:20px;">
        💳 Ödeme Hesapları
    </h1>

    <!-- SEKME BARI -->
    <div style="display:flex;gap:4px;margin-bottom:20px;background:#fff;
            padding:6px;border-radius:10px;
            box-shadow:0 1px 4px rgba(0,0,0,.08);width:fit-content;">
        <button class="sekme-btn aktif" onclick="sekmeAc('hesaplar',this)">
            💳 Hesaplar
        </button>
        <button class="sekme-btn" onclick="sekmeAc('hareketler',this)">
            📋 Hareketler
        </button>
        <button class="sekme-btn" onclick="sekmeAc('transfer',this)">
            🔄 Transfer
        </button>
        <button class="sekme-btn" onclick="sekmeAc('cari',this)">
            👤 Cari Ödeme
        </button>
    </div>

    <!-- ══════════════════════════════════════
     SEKME 1 — HESAPLAR
══════════════════════════════════════ -->
    <div id="sekme-hesaplar" class="sekme-icerik">

        <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">
            <button class="btn btn-success" onclick="hesapModalAc()">
                ➕ Yeni Hesap
            </button>
        </div>

        <!-- Hesap Kartları -->
        <div id="hesapKartlar" style="display:grid;
                grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
                gap:16px;margin-bottom:28px;">
            <div style="text-align:center;padding:40px;color:#9ca3af;">
                ⏳ Yükleniyor...
            </div>
        </div>

        <!-- Genel Özet -->
        <div id="genelOzet" style="display:none;background:#fff;border-radius:10px;
                padding:18px 22px;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <div style="font-size:13px;font-weight:600;
                    color:#374151;margin-bottom:12px;">
                📊 Genel Bakiye Özeti
            </div>
            <div style="display:flex;gap:32px;flex-wrap:wrap;align-items:center;">
                <div>
                    <div style="font-size:11px;color:#6b7280;">
                        Toplam Varlık
                    </div>
                    <div id="genelToplam" style="font-size:26px;font-weight:700;color:#10b981;">
                        —
                    </div>
                </div>
                <div id="genelHesapDetay" style="display:flex;gap:20px;flex-wrap:wrap;">
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
     SEKME 2 — HAREKETLERi
══════════════════════════════════════ -->
    <div id="sekme-hareketler" class="sekme-icerik" style="display:none;">

        <!-- Filtre Barı -->
        <div style="background:#fff;padding:14px 16px;border-radius:10px;
                box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:16px;
                display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <select id="hrHesapSec" style="padding:8px 12px;border:1px solid #dde3f0;
                       border-radius:7px;font-size:13px;min-width:200px;">
                <option value="">— Hesap Seç —</option>
            </select>
            <input type="date" id="hrTarihBas" style="padding:8px 12px;border:1px solid #dde3f0;
                      border-radius:7px;font-size:13px;">
            <input type="date" id="hrTarihBit" style="padding:8px 12px;border:1px solid #dde3f0;
                      border-radius:7px;font-size:13px;">
            <button class="btn btn-primary" onclick="hareketYukle()">
                🔍 Göster
            </button>
            <button class="btn btn-gray" onclick="hrFiltreTemizle()">
                ✕ Temizle
            </button>
            <button class="btn btn-success" style="margin-left:auto;" onclick="hareketModalAc()">
                ➕ Manuel Hareket
            </button>
        </div>

        <!-- Özet Kartlar -->
        <div id="hrOzet" style="display:none;
                grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
                gap:12px;margin-bottom:16px;">
            <div style="background:#fff;border-radius:10px;padding:14px 16px;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);">
                <div style="font-size:11px;color:#6b7280;">💰 Açılış Bakiyesi</div>
                <div id="hrAcilis" style="font-size:18px;font-weight:700;color:#374151;">—</div>
            </div>
            <div style="background:#fff;border-radius:10px;padding:14px 16px;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);">
                <div style="font-size:11px;color:#6b7280;">📥 Toplam Giriş</div>
                <div id="hrGiris" style="font-size:18px;font-weight:700;color:#10b981;">—</div>
            </div>
            <div style="background:#fff;border-radius:10px;padding:14px 16px;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);">
                <div style="font-size:11px;color:#6b7280;">📤 Toplam Çıkış</div>
                <div id="hrCikis" style="font-size:18px;font-weight:700;color:#ef4444;">—</div>
            </div>
            <div style="background:#fff;border-radius:10px;padding:14px 16px;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);">
                <div style="font-size:11px;color:#6b7280;">⚖️ Güncel Bakiye</div>
                <div id="hrBakiye" style="font-size:18px;font-weight:700;color:#3b82f6;">—</div>
            </div>
        </div>

        <!-- Hareket Tablosu -->
        <div style="background:#fff;border-radius:10px;
                box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8faff;">
                        <th style="padding:11px 14px;text-align:left;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;
                               white-space:nowrap;">Tarih</th>
                        <th style="padding:11px 14px;text-align:left;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;">Tür</th>
                        <th style="padding:11px 14px;text-align:left;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;">Açıklama</th>
                        <th style="padding:11px 14px;text-align:right;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;">Giriş</th>
                        <th style="padding:11px 14px;text-align:right;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;">Çıkış</th>
                        <th style="padding:11px 14px;text-align:right;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;">Bakiye</th>
                        <th style="padding:11px 14px;text-align:center;font-weight:600;
                               color:#374151;border-bottom:2px solid #e5e7eb;">İşlem</th>
                    </tr>
                </thead>
                <tbody id="hrTabloBody">
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#9ca3af;">
                            Hesap seçip "Göster" butonuna tıklayın.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══════════════════════════════════════
     SEKME 3 — TRANSFER
══════════════════════════════════════ -->
    <div id="sekme-transfer" class="sekme-icerik" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1.4fr;
                gap:20px;align-items:start;">

            <!-- Sol: Transfer Formu -->
            <div style="background:#fff;border-radius:12px;padding:28px;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);">
                <h3 style="font-size:16px;font-weight:700;
                       color:#1e3a8a;margin-bottom:20px;">
                    🔄 Hesaplar Arası Transfer
                </h3>

                <div class="form-grup">
                    <label>Kaynak Hesap *</label>
                    <select id="trKaynak" onchange="transferBakiyeGoster()">
                        <option value="">— Seçin —</option>
                    </select>
                    <div id="trKaynakBak" style="font-size:12px;color:#6b7280;margin-top:5px;
                            min-height:16px;"></div>
                </div>

                <div style="text-align:center;margin:4px 0;">
                    <div style="display:inline-flex;align-items:center;
                            justify-content:center;width:36px;height:36px;
                            background:#eff6ff;border-radius:50%;
                            font-size:18px;color:#3b82f6;">
                        ⬇️
                    </div>
                </div>

                <div class="form-grup">
                    <label>Hedef Hesap *</label>
                    <select id="trHedef" onchange="transferBakiyeGoster()">
                        <option value="">— Seçin —</option>
                    </select>
                    <div id="trHedefBak" style="font-size:12px;color:#6b7280;margin-top:5px;
                            min-height:16px;"></div>
                </div>

                <div class="form-grup">
                    <label>Tutar *</label>
                    <input type="number" id="trTutar" placeholder="0.00" step="0.01" min="0.01">
                </div>

                <div class="form-grup">
                    <label>Tarih *</label>
                    <input type="date" id="trTarih">
                </div>

                <div class="form-grup">
                    <label>Açıklama</label>
                    <input type="text" id="trAciklama" placeholder="Transfer açıklaması...">
                </div>

                <!-- Önizleme -->
                <div id="trOnizleme" style="display:none;background:#f0f9ff;
                        border:1px solid #bae6fd;border-radius:8px;
                        padding:12px 14px;margin-bottom:16px;
                        font-size:13px;color:#0369a1;line-height:1.6;">
                </div>

                <button class="btn btn-primary" style="width:100%;padding:12px;font-size:14px;" onclick="transferYap()">
                    🔄 Transferi Gerçekleştir
                </button>
            </div>

            <!-- Sağ: Son Transferler -->
            <div style="background:#fff;border-radius:12px;
                    box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;">
                <div style="padding:14px 18px;font-size:14px;font-weight:700;
                        color:#1e3a8a;border-bottom:2px solid #e5e7eb;
                        display:flex;align-items:center;
                        justify-content:space-between;">
                    <span>🕓 Son Transferler</span>
                    <button class="btn btn-gray" style="padding:4px 10px;font-size:11px;"
                        onclick="sonTransferleriYukle()">
                        🔄 Yenile
                    </button>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <thead>
                            <tr style="background:#f8faff;">
                                <th style="padding:9px 12px;text-align:left;
                                       color:#6b7280;font-weight:600;
                                       border-bottom:1px solid #e5e7eb;">
                                    Tarih
                                </th>
                                <th style="padding:9px 12px;text-align:left;
                                       color:#6b7280;font-weight:600;
                                       border-bottom:1px solid #e5e7eb;">
                                    Kaynak
                                </th>
                                <th style="padding:9px 12px;text-align:left;
                                       color:#6b7280;font-weight:600;
                                       border-bottom:1px solid #e5e7eb;">
                                    Hedef
                                </th>
                                <th style="padding:9px 12px;text-align:right;
                                       color:#6b7280;font-weight:600;
                                       border-bottom:1px solid #e5e7eb;">
                                    Tutar
                                </th>
                                <th style="padding:9px 12px;text-align:center;
                                       color:#6b7280;font-weight:600;
                                       border-bottom:1px solid #e5e7eb;">
                                    İşlem
                                </th>
                            </tr>
                        </thead>
                        <tbody id="sonTransferler">
                            <tr>
                                <td colspan="5" style="text-align:center;padding:30px;
                                       color:#9ca3af;">
                                    ⏳ Yükleniyor...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
     MODAL — HESAP EKLE / DÜZENLE
══════════════════════════════════════ -->
    <div class="modal-overlay" id="hesapModal">
        <div class="modal-kart">
            <h3>💳 Hesap Ekle / Düzenle</h3>
            <input type="hidden" id="hmId">

            <div class="form-grup">
                <label>Hesap Adı *</label>
                <input type="text" id="hmAd" placeholder="Örn: Ana Kasa">
            </div>
            <div class="form-grup">
                <label>Tip</label>
                <select id="hmTip">
                    <option value="nakit">💵 Nakit</option>
                    <option value="banka">🏦 Banka</option>
                    <option value="pos">💳 POS</option>
                    <option value="diger">🔹 Diğer</option>
                </select>
            </div>
            <div class="form-grup" id="hmBaslangicWrap">
                <label>Başlangıç Bakiyesi</label>
                <input type="number" id="hmBaslangic" placeholder="0.00" step="0.01" value="0">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-grup">
                    <label>Renk</label>
                    <input type="color" id="hmRenk" value="#3b82f6" style="height:38px;padding:2px 6px;width:100%;">
                </div>
                <div class="form-grup">
                    <label>İkon</label>
                    <select id="hmIkon">
                        <option value="💵">💵 Nakit</option>
                        <option value="🏦">🏦 Banka</option>
                        <option value="💳">💳 Kart/POS</option>
                        <option value="🏧">🏧 ATM</option>
                        <option value="💰">💰 Para</option>
                        <option value="🔹">🔹 Diğer</option>
                    </select>
                </div>
            </div>
            <div class="form-grup">
                <label>Açıklama</label>
                <input type="text" id="hmAciklama" placeholder="Opsiyonel açıklama">
            </div>
            <div class="modal-footer">
                <button class="btn btn-gray" onclick="modalKapat('hesapModal')">İptal</button>
                <button class="btn btn-success" onclick="hesapKaydet()">💾 Kaydet</button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
     MODAL — MANUEL HAREKET
══════════════════════════════════════ -->
    <div class="modal-overlay" id="hareketModal">
        <div class="modal-kart">
            <h3>➕ Manuel Hareket Ekle</h3>

            <div class="form-grup">
                <label>Hesap *</label>
                <select id="mhHesap"></select>
            </div>

            <div class="form-grup">
                <label>Tür *</label>
                <div style="display:flex;gap:12px;">
                    <label style="flex:1;cursor:pointer;display:flex;
                              align-items:center;gap:6px;padding:10px 14px;
                              border:2px solid #e5e7eb;border-radius:8px;
                              transition:.15s;" id="lblGiris">
                        <input type="radio" name="mhTip" value="giris" checked onchange="tipRadioStyle()">
                        <span style="color:#10b981;font-weight:600;">
                            📥 Giriş
                        </span>
                    </label>
                    <label style="flex:1;cursor:pointer;display:flex;
                              align-items:center;gap:6px;padding:10px 14px;
                              border:2px solid #e5e7eb;border-radius:8px;
                              transition:.15s;" id="lblCikis">
                        <input type="radio" name="mhTip" value="cikis" onchange="tipRadioStyle()">
                        <span style="color:#ef4444;font-weight:600;">
                            📤 Çıkış
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-grup">
                <label>Tutar *</label>
                <input type="number" id="mhTutar" placeholder="0.00" step="0.01" min="0.01">
            </div>
            <div class="form-grup">
                <label>Tarih *</label>
                <input type="date" id="mhTarih">
            </div>
            <div class="form-grup">
                <label>Açıklama</label>
                <input type="text" id="mhAciklama" placeholder="Hareket açıklaması...">
            </div>
            <div class="modal-footer">
                <button class="btn btn-gray" onclick="modalKapat('hareketModal')">İptal</button>
                <button class="btn btn-success" onclick="hareketKaydet()">💾 Kaydet</button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
     CARİ ÖDEME SEKMESİ
    ══════════════════════════════════════ -->
    <div id="sekme-cari" class="sekme-icerik" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

            <!-- FORM -->
            <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
                <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:18px;">👤 Cari Ödeme Yap</h3>

                <div class="form-grup">
                    <label>Ödeme Yapılacak Hesap *</label>
                    <select id="coHesapId" onchange="coHesapSec()">
                        <option value="">— Hesap Seçin —</option>
                    </select>
                    <div id="coBakiyeGoster" style="margin-top:6px;font-size:13px;color:#6b7280;"></div>
                </div>

                <div class="form-grup">
                    <label>Müşteri / Tedarikçi *</label>
                    <input type="text" id="coMusteriAd" placeholder="İsim yazın..." oninput="coMusteriAra(this.value)" autocomplete="off">
                    <input type="hidden" id="coMusteriId">
                    <div id="coMusteriOneri" style="position:relative;z-index:100;"></div>
                </div>

                <div class="form-grup">
                    <label>Tutar *</label>
                    <input type="number" id="coTutar" placeholder="0.00" step="0.01" min="0.01">
                </div>

                <div class="form-grup">
                    <label>Tarih *</label>
                    <input type="date" id="coTarih">
                </div>

                <div class="form-grup">
                    <label>Referans / Belge No</label>
                    <input type="text" id="coReferans" placeholder="Fatura no, irsaliye no...">
                </div>

                <div class="form-grup">
                    <label>Açıklama</label>
                    <textarea id="coAciklama" rows="2" placeholder="Ödeme açıklaması..."></textarea>
                </div>

                <button class="btn btn-success" style="width:100%;padding:12px;" onclick="coKaydet()">
                    💸 Ödemeyi Yap
                </button>
                <div id="coSonuc" style="margin-top:12px;"></div>
            </div>

            <!-- LISTE -->
            <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
                <h3 style="font-size:16px;font-weight:800;color:#1e293b;margin-bottom:14px;">📋 Son Cari Ödemeler</h3>
                <div id="coListe" style="font-size:13px;"></div>
            </div>
        </div>
    </div>
    <!-- ══════════════════════════════════════
     STİLLER
    ══════════════════════════════════════ -->
    <style>
        .sekme-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            background: transparent;
            color: #6b7280;
            transition: .15s;
        }

        .sekme-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .sekme-btn.aktif {
            background: #3b82f6;
            color: #fff;
        }

        .hesap-kart {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
            border-top: 4px solid #3b82f6;
            transition: box-shadow .2s, transform .2s;
        }

        .hesap-kart:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, .12);
            transform: translateY(-2px);
        }

        .hesap-kart .kart-ikon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .hesap-kart .kart-ad {
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .hesap-kart .kart-tip {
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .hesap-kart .kart-bakiye {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .hesap-kart .kart-aksiyonlar {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: .15s;
        }

        .btn-primary {
            background: #3b82f6;
            color: #fff;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: #fff;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-gray {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-gray:hover {
            background: #d1d5db;
        }

        .btn-danger {
            background: #ef4444;
            color: #fff;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 11px;
        }

        .form-grup {
            margin-bottom: 14px;
        }

        .form-grup label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }

        .form-grup input,
        .form-grup select,
        .form-grup textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #dde3f0;
            border-radius: 7px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
        }

        .form-grup input:focus,
        .form-grup select:focus {
            border-color: #3b82f6;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.aktif {
            display: flex;
        }

        .modal-kart {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 460px;
            padding: 28px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-kart h3 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 18px;
            color: #1e3a8a;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .badge-giris {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #d1fae5;
            color: #059669;
        }

        .badge-cikis {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #fee2e2;
            color: #dc2626;
        }

        .badge-transfer {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #ede9fe;
            color: #7c3aed;
        }

        @media (max-width: 768px) {
            #sekme-transfer>div {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- ══════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════ -->
    <script>
        'use strict';

        let tumHesaplar = [];

        // ════════════════════════════════════════════════════════
        // BAŞLANGIÇ
        // ════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('trTarih').value = bugun();
            document.getElementById('mhTarih').value = bugun();
            hesaplariYukle();
            tipRadioStyle();
        });

        function bugun() {
            return new Date().toISOString().split('T')[0];
        }

        // ════════════════════════════════════════════════════════
        // SEKME
        // ════════════════════════════════════════════════════════
        function sekmeAc(id, btn) {
            document.querySelectorAll('.sekme-icerik')
                .forEach(el => el.style.display = 'none');
            document.querySelectorAll('.sekme-btn')
                .forEach(el => el.classList.remove('aktif'));
            document.getElementById('sekme-' + id).style.display = 'block';
            btn.classList.add('aktif');
        }

        // ════════════════════════════════════════════════════════
        // HESAPLAR YÜKLEi
        // ════════════════════════════════════════════════════════
        function hesaplariYukle() {
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=hesap_liste'
            })
                .then(r => r.json())
                .then(v => {
                    if (!v.basari) return;
                    tumHesaplar = v.hesaplar;
                    renderHesapKartlar(v.hesaplar);
                    hesapSelectDoldur(v.hesaplar);
                    sonTransferleriYukle();
                });
        }

        function renderHesapKartlar(hesaplar) {
            const wrap = document.getElementById('hesapKartlar');

            if (!hesaplar.length) {
                wrap.innerHTML =
                    '<div style="text-align:center;padding:60px;color:#9ca3af;' +
                    'grid-column:1/-1;">📭 Henüz hesap eklenmemiş.</div>';
                document.getElementById('genelOzet').style.display = 'none';
                return;
            }

            const toplamBakiye = hesaplar.reduce((t, h) => t + h.bakiye, 0);

            wrap.innerHTML = hesaplar.map(h => {
                const bakiyeRenk = h.bakiye >= 0 ? '#10b981' : '#ef4444';
                return `
        <div class="hesap-kart" style="border-top-color:${esc(h.renk)}">
            <div class="kart-ikon">${h.ikon}</div>
            <div class="kart-ad">${esc(h.ad)}</div>
            <div class="kart-tip">${tipEtiket(h.tip)}</div>
            <div class="kart-bakiye" style="color:${bakiyeRenk}">
                ${paraBicim(h.bakiye)}
            </div>
            <div style="font-size:11px;color:#9ca3af;margin-bottom:14px;">
                Açılış: ${paraBicim(h.baslangic_bak)}
            </div>
            <div class="kart-aksiyonlar">
                <button class="btn btn-primary btn-sm"
                        onclick="hareketlereGit(${h.id})">
                    📋 Hareketler
                </button>
                <button class="btn btn-gray btn-sm"
                        onclick="hesapDuzenle(${h.id})"
                        title="Düzenle">✏️</button>
                <button class="btn btn-danger btn-sm"
                        onclick="hesapSil(${h.id})"
                        title="Sil">🗑</button>
            </div>
        </div>`;
            }).join('');

            // Genel özet
            const ozet = document.getElementById('genelOzet');
            ozet.style.display = 'block';
            document.getElementById('genelToplam').textContent =
                paraBicim(toplamBakiye);

            // Hesap bazlı detay
            document.getElementById('genelHesapDetay').innerHTML =
                hesaplar.map(h => `
        <div style="text-align:center;padding:8px 16px;
                    background:#f8faff;border-radius:8px;">
            <div style="font-size:18px;">${h.ikon}</div>
            <div style="font-size:11px;color:#6b7280;
                        margin:2px 0;">${esc(h.ad)}</div>
            <div style="font-size:14px;font-weight:700;
                        color:${h.bakiye >= 0 ? '#10b981' : '#ef4444'};">
                ${paraBicim(h.bakiye)}
            </div>
        </div>`).join('');
        }

        function hesapSelectDoldur(hesaplar) {
            ['hrHesapSec', 'trKaynak', 'trHedef', 'mhHesap'].forEach(id => {
                const sel = document.getElementById(id);
                const eskiDeger = sel.value;
                sel.innerHTML = id !== 'mhHesap'
                    ? '<option value="">— Seçin —</option>'
                    : '';
                hesaplar.forEach(h => {
                    const opt = document.createElement('option');
                    opt.value = h.id;
                    opt.textContent = `${h.ikon} ${h.ad} — ${paraBicim(h.bakiye)}`;
                    opt.dataset.bakiye = h.bakiye;
                    opt.dataset.ad = h.ad;
                    sel.appendChild(opt);
                });
                if (eskiDeger) sel.value = eskiDeger;
            });
        }

        // ════════════════════════════════════════════════════════
        // HESAP EKLE / DÜZENLE
        // ════════════════════════════════════════════════════════
        function hesapModalAc(id = null) {
            document.getElementById('hmId').value = id || '';
            document.getElementById('hmAd').value = '';
            document.getElementById('hmTip').value = 'nakit';
            document.getElementById('hmBaslangic').value = '0';
            document.getElementById('hmRenk').value = '#3b82f6';
            document.getElementById('hmIkon').value = '💵';
            document.getElementById('hmAciklama').value = '';
            // Başlangıç bakiyesi sadece yeni hesapta göster
            document.getElementById('hmBaslangicWrap').style.display =
                id ? 'none' : 'block';

            if (id) {
                const h = tumHesaplar.find(x => x.id == id);
                if (h) {
                    document.getElementById('hmAd').value = h.ad;
                    document.getElementById('hmTip').value = h.tip;
                    document.getElementById('hmRenk').value = h.renk;
                    document.getElementById('hmIkon').value = h.ikon;
                    document.getElementById('hmAciklama').value = h.aciklama || '';
                }
            }
            document.getElementById('hesapModal').classList.add('aktif');
        }

        function hesapDuzenle(id) { hesapModalAc(id); }

        function hesapKaydet() {
            const ad = document.getElementById('hmAd').value.trim();
            if (!ad) { alert('⚠️ Hesap adı gerekli.'); return; }

            const params = new URLSearchParams({
                action: 'hesap_kaydet',
                id: document.getElementById('hmId').value,
                ad,
                tip: document.getElementById('hmTip').value,
                aciklama: document.getElementById('hmAciklama').value,
                baslangic_bak: document.getElementById('hmBaslangic').value,
                renk: document.getElementById('hmRenk').value,
                ikon: document.getElementById('hmIkon').value,
            });

            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        modalKapat('hesapModal');
                        hesaplariYukle();
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        function hesapSil(id) {
            if (!confirm('Bu hesabı silmek istediğinizden emin misiniz?\n' +
                'Tüm hareketler de silinecektir.')) return;
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=hesap_sil&id=${id}`
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) hesaplariYukle();
                    else alert('❌ ' + v.mesaj);
                });
        }

        // ════════════════════════════════════════════════════════
        // HAREKETLERi
        // ════════════════════════════════════════════════════════
        function hareketlereGit(hesapId) {
            document.querySelectorAll('.sekme-icerik')
                .forEach(el => el.style.display = 'none');
            document.querySelectorAll('.sekme-btn')
                .forEach(el => el.classList.remove('aktif'));
            document.getElementById('sekme-hareketler').style.display = 'block';
            document.querySelectorAll('.sekme-btn')[1].classList.add('aktif');
            document.getElementById('hrHesapSec').value = hesapId;
            hareketYukle();
        }

        function hrFiltreTemizle() {
            document.getElementById('hrTarihBas').value = '';
            document.getElementById('hrTarihBit').value = '';
            const secili = document.getElementById('hrHesapSec').value;
            if (secili) hareketYukle();
        }

        function hareketYukle() {
            const hesapId = document.getElementById('hrHesapSec').value;
            if (!hesapId) { alert('⚠️ Lütfen hesap seçin.'); return; }

            const tbody = document.getElementById('hrTabloBody');
            tbody.innerHTML =
                '<tr><td colspan="7" style="text-align:center;padding:30px;' +
                'color:#6b7280;">⏳ Yükleniyor...</td></tr>';

            document.getElementById('hrOzet').style.display = 'none';

            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'hesap_hareketler',
                    hesap_id: hesapId,
                    tarih_bas: document.getElementById('hrTarihBas').value,
                    tarih_bit: document.getElementById('hrTarihBit').value,
                }).toString()
            })
                .then(r => r.json())
                .then(v => {
                    if (!v.basari) {
                        tbody.innerHTML =
                            `<tr><td colspan="7" style="text-align:center;padding:30px;
                color:#ef4444;">❌ ${esc(v.mesaj)}</td></tr>`;
                        return;
                    }

                    renderHareketTablo(v.hareketler, v.baslangic_bak);

                    const ozet = document.getElementById('hrOzet');
                    ozet.style.display = 'grid';
                    document.getElementById('hrAcilis').textContent =
                        paraBicim(v.baslangic_bak);
                    document.getElementById('hrGiris').textContent =
                        paraBicim(v.toplam_giris);
                    document.getElementById('hrCikis').textContent =
                        paraBicim(v.toplam_cikis);
                    document.getElementById('hrBakiye').textContent =
                        paraBicim(v.net_bakiye);
                });
        }

        function renderHareketTablo(hareketler, acilisBak) {
            const tbody = document.getElementById('hrTabloBody');

            if (!hareketler.length) {
                tbody.innerHTML =
                    '<tr><td colspan="7" style="text-align:center;padding:40px;' +
                    'color:#9ca3af;">📭 Bu hesapta hareket bulunamadı.</td></tr>';
                return;
            }

            let html = `
    <tr style="background:#f0f9ff;">
        <td colspan="3"
            style="padding:9px 14px;color:#0369a1;
                   font-size:12px;font-style:italic;">
            📌 Açılış Bakiyesi
        </td>
        <td style="padding:9px 14px;text-align:right;"></td>
        <td style="padding:9px 14px;text-align:right;"></td>
        <td style="padding:9px 14px;text-align:right;
                   font-weight:700;color:#374151;">
            ${paraBicim(acilisBak)}
        </td>
        <td style="padding:9px 14px;"></td>
    </tr>`;

            html += hareketler.map(h => {
                const isGiris = ['giris', 'transfer_giris'].includes(h.tip);
                const isManuel = h.referans_tip === 'manuel';
                const bakiyeRnk = h.bakiye >= 0 ? '#10b981' : '#ef4444';

                return `
        <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:9px 14px;white-space:nowrap;">
                ${tarihFmt(h.tarih)}
            </td>
            <td style="padding:9px 14px;">
                ${tipHareketBadge(h.tip)}
            </td>
            <td style="padding:9px 14px;color:#6b7280;font-size:12px;
                       max-width:200px;">
                ${esc(h.aciklama_detay || h.aciklama || '—')}
            </td>
            <td style="padding:9px 14px;text-align:right;
                       color:#10b981;font-weight:600;">
                ${isGiris ? paraBicim(h.tutar) : '—'}
            </td>
            <td style="padding:9px 14px;text-align:right;
                       color:#ef4444;font-weight:600;">
                ${!isGiris ? paraBicim(h.tutar) : '—'}
            </td>
            <td style="padding:9px 14px;text-align:right;
                       font-weight:700;color:${bakiyeRnk};">
                ${paraBicim(h.bakiye)}
            </td>
            <td style="padding:9px 14px;text-align:center;">
                ${isManuel
                        ? `<button class="btn btn-danger btn-sm"
                               onclick="hareketSil(${h.id})"
                               title="Sil">🗑</button>`
                        : '<span style="color:#d1d5db;font-size:11px;">—</span>'
                    }
            </td>
        </tr>`;
            }).join('');

            tbody.innerHTML = html;
        }

        function hareketModalAc() {
            document.getElementById('mhTutar').value = '';
            document.getElementById('mhAciklama').value = '';
            document.getElementById('mhTarih').value = bugun();
            document.querySelector(
                'input[name="mhTip"][value="giris"]').checked = true;
            tipRadioStyle();

            const secili = document.getElementById('hrHesapSec').value;
            if (secili) document.getElementById('mhHesap').value = secili;

            document.getElementById('hareketModal').classList.add('aktif');
        }

        function hareketKaydet() {
            const hesapId = document.getElementById('mhHesap').value;
            const tutar = document.getElementById('mhTutar').value;

            if (!hesapId) { alert('⚠️ Hesap seçin.'); return; }
            if (!tutar || parseFloat(tutar) <= 0) {
                alert('⚠️ Geçerli tutar girin.'); return;
            }

            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'hareket_ekle',
                    hesap_id: hesapId,
                    tip: document.querySelector(
                        'input[name="mhTip"]:checked').value,
                    tutar,
                    aciklama: document.getElementById('mhAciklama').value,
                    tarih: document.getElementById('mhTarih').value,
                }).toString()
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        modalKapat('hareketModal');
                        hesaplariYukle();
                        const secili = document.getElementById('hrHesapSec').value;
                        if (secili) hareketYukle();
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        function hareketSil(id) {
            if (!confirm('Bu hareketi silmek istediğinizden emin misiniz?'))
                return;
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=hareket_sil&id=${id}`
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        hesaplariYukle();
                        hareketYukle();
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        // ════════════════════════════════════════════════════════
        // TRANSFER
        // ════════════════════════════════════════════════════════
        function transferBakiyeGoster() {
            const kSel = document.getElementById('trKaynak');
            const hSel = document.getElementById('trHedef');
            const kOpt = kSel.options[kSel.selectedIndex];
            const hOpt = hSel.options[hSel.selectedIndex];

            document.getElementById('trKaynakBak').textContent =
                kOpt?.dataset?.bakiye !== undefined
                    ? `Mevcut bakiye: ${paraBicim(kOpt.dataset.bakiye)}`
                    : '';
            document.getElementById('trHedefBak').textContent =
                hOpt?.dataset?.bakiye !== undefined
                    ? `Mevcut bakiye: ${paraBicim(hOpt.dataset.bakiye)}`
                    : '';

            transferOnizlemeGuncelle();
        }

        document.getElementById('trTutar')
            .addEventListener('input', transferOnizlemeGuncelle);

        function transferOnizlemeGuncelle() {
            const kSel = document.getElementById('trKaynak');
            const hSel = document.getElementById('trHedef');
            const tutar = parseFloat(
                document.getElementById('trTutar').value) || 0;
            const onizleme = document.getElementById('trOnizleme');

            const kAd = kSel.options[kSel.selectedIndex]?.dataset?.ad;
            const hAd = hSel.options[hSel.selectedIndex]?.dataset?.ad;

            if (!kAd || !hAd || !tutar) {
                onizleme.style.display = 'none';
                return;
            }

            onizleme.style.display = 'block';
            onizleme.innerHTML =
                `<strong>${esc(kAd)}</strong> hesabından ` +
                `<strong style="color:#ef4444;">${paraBicim(tutar)}</strong> ` +
                `çıkacak → ` +
                `<strong>${esc(hAd)}</strong> hesabına ` +
                `<strong style="color:#10b981;">${paraBicim(tutar)}</strong> ` +
                `girecek.`;
        }

        function transferYap() {
            const kaynakId = document.getElementById('trKaynak').value;
            const hedefId = document.getElementById('trHedef').value;
            const tutar = document.getElementById('trTutar').value;
            const tarih = document.getElementById('trTarih').value;
            const aciklama = document.getElementById('trAciklama').value;

            if (!kaynakId || !hedefId) {
                alert('⚠️ Kaynak ve hedef hesap seçin.'); return;
            }
            if (kaynakId === hedefId) {
                alert('⚠️ Kaynak ve hedef hesap aynı olamaz.'); return;
            }
            if (!tutar || parseFloat(tutar) <= 0) {
                alert('⚠️ Geçerli tutar girin.'); return;
            }
            if (!tarih) {
                alert('⚠️ Tarih seçin.'); return;
            }

            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'transfer',
                    kaynak_id: kaynakId,
                    hedef_id: hedefId,
                    tutar, tarih, aciklama
                }).toString()
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        document.getElementById('trTutar').value = '';
                        document.getElementById('trAciklama').value = '';
                        document.getElementById('trOnizleme').style.display = 'none';
                        hesaplariYukle();
                        sonTransferleriYukle();
                        alert('✅ ' + v.mesaj);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        function sonTransferleriYukle() {
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=son_transferler'
            })
                .then(r => r.json())
                .then(v => {
                    const tbody = document.getElementById('sonTransferler');
                    if (!v.basari || !v.transferler?.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="5" style="text-align:center;' +
                            'padding:24px;color:#9ca3af;">📭 Henüz transfer yok.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = v.transferler.map(t => `
        <tr style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:9px 12px;white-space:nowrap;color:#6b7280;">
                ${tarihFmt(t.tarih)}
            </td>
            <td style="padding:9px 12px;">
                <span style="color:#ef4444;font-weight:600;">
                    ${esc(t.kaynak_ikon)} ${esc(t.kaynak_ad)}
                </span>
            </td>
            <td style="padding:9px 12px;">
                <span style="color:#10b981;font-weight:600;">
                    ${esc(t.hedef_ikon)} ${esc(t.hedef_ad)}
                </span>
            </td>
            <td style="padding:9px 12px;text-align:right;
                       font-weight:700;color:#3b82f6;">
                ${paraBicim(t.tutar)}
            </td>
            <td style="padding:9px 12px;text-align:center;">
                <button class="btn btn-danger btn-sm"
                        onclick="transferSil(${t.id})"
                        title="İptal Et">🗑</button>
            </td>
        </tr>`).join('');
                });
        }

        function transferSil(id) {
            if (!confirm('Bu transferi iptal etmek istediğinizden emin misiniz?\n' +
                'Her iki hesabın bakiyesi geri alınacaktır.')) return;
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=transfer_sil&id=${id}`
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        hesaplariYukle();
                        sonTransferleriYukle();
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        // ════════════════════════════════════════════════════════
        // MODAL
        // ════════════════════════════════════════════════════════
        function modalKapat(id) {
            document.getElementById(id).classList.remove('aktif');
        }

        // Overlay tıklamasıyla kapat
        ['hesapModal', 'hareketModal'].forEach(id => {
            document.getElementById(id).addEventListener('click', function (e) {
                if (e.target === this) modalKapat(id);
            });
        });

        // Radio buton stil güncelle
        function tipRadioStyle() {
            const secili = document.querySelector(
                'input[name="mhTip"]:checked')?.value;
            document.getElementById('lblGiris').style.borderColor =
                secili === 'giris' ? '#10b981' : '#e5e7eb';
            document.getElementById('lblGiris').style.background =
                secili === 'giris' ? '#f0fdf4' : '#fff';
            document.getElementById('lblCikis').style.borderColor =
                secili === 'cikis' ? '#ef4444' : '#e5e7eb';
            document.getElementById('lblCikis').style.background =
                secili === 'cikis' ? '#fef2f2' : '#fff';
        }

        // ════════════════════════════════════════════════════════
        // YARDIMCI FONKSİYONLAR
        // ════════════════════════════════════════════════════════
        function paraBicim(sayi) {
            return parseFloat(sayi || 0).toLocaleString('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ₺';
        }

        function tarihFmt(tarih) {
            if (!tarih) return '—';
            const p = String(tarih).split('-');
            if (p.length !== 3) return tarih;
            return `${p[2]}.${p[1]}.${p[0]}`;
        }

        function esc(s) {
            return String(s ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function tipEtiket(tip) {
            const map = {
                nakit: 'Nakit',
                banka: 'Banka',
                pos: 'POS / Kart',
                diger: 'Diğer'
            };
            return map[tip] || tip;
        }

        function tipHareketBadge(tip) {
            const map = {
                giris: ['badge-giris', '📥 Giriş'],
                cikis: ['badge-cikis', '📤 Çıkış'],
                transfer_giris: ['badge-transfer', '🔄 Transfer Giriş'],
                transfer_cikis: ['badge-transfer', '🔄 Transfer Çıkış'],
            };
            const [cls, lbl] = map[tip] || ['badge-giris', tip];
            return `<span class="${cls}">${lbl}</span>`;
        }

        // ════════════════════════════════════════════════════════
        // CARİ ÖDEME
        // ════════════════════════════════════════════════════════
        let coMusteriTimer = null;

        function coHesaplariDoldur() {
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=hesap_liste'
            })
                .then(r => r.json())
                .then(v => {
                    if (!v.basari) return;
                    const sel = document.getElementById('coHesapId');
                    sel.innerHTML = '<option value="">— Hesap Seçin —</option>';
                    (v.hesaplar || []).forEach(h => {
                        const opt = document.createElement('option');
                        opt.value = h.id;
                        const ad = h.hesap_adi || h.ad || 'Hesap #' + h.id;
                        const bak = parseFloat(h.bakiye ?? 0);
                        opt.textContent = `${ad} (${paraBicim(bak)})`;
                        opt.dataset.bakiye = bak;
                        opt.dataset.ad = ad;
                        sel.appendChild(opt);
                    });
                });
        }

        function coHesapSec() {
            const opt = document.getElementById('coHesapId').selectedOptions[0];
            const div = document.getElementById('coBakiyeGoster');
            if (opt && opt.value) {
                div.textContent = `Mevcut Bakiye: ${paraBicim(opt.dataset.bakiye)}`;
            } else {
                div.textContent = '';
            }
        }

        function coMusteriAra(q) {
            clearTimeout(coMusteriTimer);
            const div = document.getElementById('coMusteriOneri');
            if (q.length < 2) { div.innerHTML = ''; return; }
            coMusteriTimer = setTimeout(() => {
                fetch('musteri_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=ara&ad_soyad=${encodeURIComponent(q)}`
                })
                    .then(r => r.json())
                    .then(v => {
                        if (!v.musteriler?.length) { div.innerHTML = ''; return; }
                        div.innerHTML = `<div style="position:absolute;background:#fff;border:1px solid #e5e7eb;
                            border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);
                            max-height:220px;overflow-y:auto;z-index:999;min-width:300px;">
                            ${v.musteriler.map(m => `
                                <div onclick="coMusteriSec(${m.id},'${esc(m.ad_soyad)}')"
                                    style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f3f4f6;"
                                    onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='#fff'">
                                    <strong>${esc(m.ad_soyad)}</strong>
                                    ${m.tel_ozet ? `<span style="color:#6b7280;margin-left:8px;">${esc(m.tel_ozet.split('|')[0])}</span>` : ''}
                                </div>`).join('')}
                        </div>`;
                    });
            }, 280);
        }

        function coMusteriSec(id, ad) {
            document.getElementById('coMusteriId').value = id;
            document.getElementById('coMusteriAd').value = ad;
            document.getElementById('coMusteriOneri').innerHTML = '';
        }

        function coKaydet() {
            const hesapId = document.getElementById('coHesapId').value;
            const musteriId = document.getElementById('coMusteriId').value;
            const tutar = document.getElementById('coTutar').value;
            const tarih = document.getElementById('coTarih').value;
            const referans = document.getElementById('coReferans').value;
            const aciklama = document.getElementById('coAciklama').value;

            if (!hesapId || !musteriId || !tutar || !tarih) {
                document.getElementById('coSonuc').innerHTML =
                    '<div style="color:#ef4444;font-size:13px;">⚠ Hesap, müşteri, tutar ve tarih zorunludur.</div>';
                return;
            }

            const body = new URLSearchParams({
                action: 'cari_odeme',
                hesap_id: hesapId,
                musteri_id: musteriId,
                tutar: tutar,
                tarih: tarih,
                referans: referans,
                aciklama: aciklama
            });

            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        document.getElementById('coSonuc').innerHTML =
                            '<div style="color:#10b981;font-size:13px;">✅ Cari ödeme kaydedildi.</div>';
                        document.getElementById('coTutar').value = '';
                        document.getElementById('coReferans').value = '';
                        document.getElementById('coAciklama').value = '';
                        document.getElementById('coMusteriAd').value = '';
                        document.getElementById('coMusteriId').value = '';
                        coHesaplariDoldur();
                        coListeYukle();
                        hesaplariYukle();
                    } else {
                        document.getElementById('coSonuc').innerHTML =
                            `<div style="color:#ef4444;font-size:13px;">❌ ${esc(v.mesaj)}</div>`;
                    }
                });
        }

        function coListeYukle() {
            fetch('odeme_hesap_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=cari_odeme_listesi&limit=20'
            })
                .then(r => r.json())
                .then(v => {
                    const div = document.getElementById('coListe');
                    if (!v.basari || !v.liste?.length) {
                        div.innerHTML = '<p style="color:#9ca3af;font-size:13px;">Kayıt bulunamadı.</p>';
                        return;
                    }
                    div.innerHTML = v.liste.map(k => `
                        <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">
                            <div>
                                <strong>${esc(k.musteri_adi || k.musteri_ad)}</strong>
                                <div style="color:#6b7280;font-size:11px;">${esc(k.hesap_adi)} · ${tarihFmt(k.tarih)}</div>
                                ${k.referans ? `<div style="color:#6b7280;font-size:11px;">Ref: ${esc(k.referans)}</div>` : ''}
                            </div>
                            <strong style="color:#ef4444;">-${paraBicim(k.tutar)}</strong>
                        </div>`).join('');
                });
        }

        // Cari sekmesi açılınca yükle — sekmeAc'ı genişlet
        (function () {
            const _orig = sekmeAc;
            sekmeAc = function (id, btn) {
                _orig(id, btn);
                if (id === 'cari') {
                    coHesaplariDoldur();
                    coListeYukle();
                    const t = document.getElementById('coTarih');
                    if (!t.value) t.value = new Date().toISOString().slice(0, 10);
                }
            };
        })();
    </script>


</body>

</html>