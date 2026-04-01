<?php
require_once 'db.php';
require_once 'auth.php';
$etiketler = $pdo->query("
    SELECT DISTINCT e.id, e.ad, e.renk
    FROM etiketler e
    JOIN musteri_etiketler me ON me.etiket_id = e.id
    JOIN faturalar f ON f.musteri_id = me.musteri_id
    ORDER BY e.ad
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Fatura Listesi & Cari Hesap</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #eef2f9;
            color: #1a1f36;
        }

        .sayfa-wrap {
            max-width: 1500px;
            margin: 0 auto;
            padding: 28px 20px;
        }

        .sayfa-baslik {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .sayfa-baslik-ikon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 14px rgba(59,130,246,.35);
            flex-shrink: 0;
        }

        h1 {
            font-size: 23px;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: -.3px;
        }

        h1 small {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin-top: 2px;
        }

        .filtre-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            background: linear-gradient(135deg, #fff 0%, #f8faff 100%);
            padding: 16px 20px;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(30,58,138,.07);
            margin-bottom: 20px;
            align-items: center;
            border: 1px solid rgba(59,130,246,.1);
        }

        .filtre-bar input,
        .filtre-bar select {
            padding: 9px 13px;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }

        .filtre-bar input:focus,
        .filtre-bar select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }

        .btn {
            padding: 9px 18px;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 2px 8px rgba(59,130,246,.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            box-shadow: 0 4px 14px rgba(59,130,246,.4);
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 2px 8px rgba(16,185,129,.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow: 0 4px 14px rgba(16,185,129,.4);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 2px 8px rgba(239,68,68,.25);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
        }

        .btn-gray {
            background: #f1f5f9;
            color: #475569;
            border: 1.5px solid #e2e8f0;
        }

        .btn-gray:hover {
            background: #e2e8f0;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            box-shadow: 0 2px 8px rgba(245,158,11,.25);
        }

        .btn-warning:hover {
            transform: translateY(-1px);
        }

        .ozet-kartlar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .ozet-kart {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            transition: transform .2s, box-shadow .2s;
            position: relative;
            overflow: hidden;
        }

        .ozet-kart::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 14px 14px 0 0;
        }

        .ozet-kart.fatura::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .ozet-kart.borc::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .ozet-kart.odeme::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .ozet-kart.kalan::before { background: linear-gradient(90deg, #f59e0b, #fcd34d); }

        .ozet-kart:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,.1);
        }

        .ozet-kart .etiket {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .ozet-kart .deger {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.5px;
        }

        .ozet-kart.borc .deger { color: #ef4444; }
        .ozet-kart.odeme .deger { color: #10b981; }
        .ozet-kart.kalan .deger { color: #f59e0b; }
        .ozet-kart.fatura .deger { color: #3b82f6; }

        .tablo-wrap {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            overflow: hidden;
            border: 1px solid rgba(0,0,0,.04);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            padding: 13px 14px;
            text-align: left;
            font-weight: 600;
            color: rgba(255,255,255,.9);
            white-space: nowrap;
            font-size: 12px;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        thead th:first-child { border-radius: 0; }

        tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid #f0f4ff;
            vertical-align: middle;
        }

        tbody tr {
            transition: background .15s;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #f0f7ff, #f8faff);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .fatura-no {
            font-weight: 700;
            color: #1e3a8a;
            font-size: 12.5px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-odenmedi {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .badge-kismi {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .badge-odendi {
            background: #f0fdf4;
            color: #059669;
            border: 1px solid #bbf7d0;
        }

        .badge-iptal {
            background: #f9fafb;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }

        .modal-overlay.aktif {
            display: flex;
        }

        .modal-kart {
            background: #fff;
            border-radius: 18px;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
            animation: modalGir .2s ease;
        }

        @keyframes modalGir {
            from { opacity:0; transform: scale(.95) translateY(10px); }
            to { opacity:1; transform: scale(1) translateY(0); }
        }

        .modal-kart h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1e3a8a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-grup {
            margin-bottom: 15px;
        }

        .form-grup label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .form-grup input,
        .form-grup select,
        .form-grup textarea {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
        }

        .form-grup input:focus,
        .form-grup select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px solid #f1f5f9;
        }

        .oneri-wrap {
            position: absolute;
            z-index: 9999;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            width: 100%;
            max-height: 220px;
            overflow-y: auto;
        }

        .oneri-item {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 13px;
            border-bottom: 1px solid #f3f4f6;
            transition: background .1s;
        }

        .oneri-item:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .cari-panel {
            background: #fff;
            border-radius: 14px;
            margin-top: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            overflow: hidden;
            display: none;
            border: 1px solid rgba(0,0,0,.04);
        }

        .cari-baslik {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
            color: #fff;
        }

        .cari-baslik h2 {
            font-size: 15px;
            font-weight: 700;
        }

        .cari-tablo {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .cari-tablo th {
            background: #f8faff;
            padding: 11px 14px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 12px;
        }

        .cari-tablo td {
            padding: 9px 14px;
            border-bottom: 1px solid #f3f4f6;
        }

        .cari-tablo tr:hover {
            background: #f8faff;
        }

        .cari-tablo .borc-satir td {
            color: #dc2626;
        }

        .cari-tablo .alacak-satir td {
            color: #059669;
        }

        .cari-ozet {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 16px 20px;
            background: #f8faff;
            border-top: 2px solid #e5e7eb;
        }

        .cari-ozet-item {
            text-align: center;
        }

        .cari-ozet-item .etiket {
            font-size: 11px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .cari-ozet-item .deger {
            font-size: 20px;
            font-weight: 700;
            margin-top: 4px;
        }

        .text-right {
            text-align: right;
        }

        .text-red {
            color: #dc2626;
            font-weight: 600;
        }

        .text-green {
            color: #059669;
            font-weight: 600;
        }

        .text-orange {
            color: #d97706;
            font-weight: 600;
        }

        .sayfalama {
            display: flex;
            gap: 6px;
            justify-content: center;
            padding: 16px;
            align-items: center;
            border-top: 1px solid #f0f4ff;
        }

        .sayfa-btn {
            padding: 7px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            font-weight: 500;
            transition: all .15s;
        }

        .sayfa-btn.aktif {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59,130,246,.3);
        }

        .sayfa-btn:hover:not(.aktif) {
            background: #f0f7ff;
            border-color: #3b82f6;
            color: #1d4ed8;
        }

        .bos-mesaj {
            text-align: center;
            padding: 50px 20px;
            color: #9ca3af;
            font-size: 14px;
        }

        .yukle-spin {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 13px;
        }

        .etiket-chip-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 10px 20px 12px;
            background: #fff;
            border-radius: 0 0 14px 14px;
            margin-top: -18px;
            margin-bottom: 6px;
            border: 1px solid rgba(59,130,246,.1);
            border-top: none;
        }

        .etiket-chip {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all .2s;
            box-shadow: 0 1px 4px rgba(0,0,0,.1);
        }

        .etiket-chip:hover {
            opacity: .85;
            transform: translateY(-1px);
        }

        .etiket-chip.aktif {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30,58,138,.2);
        }

        .aksiyon-grup {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .ozet-kartlar { grid-template-columns: repeat(2, 1fr); }
            .filtre-bar { gap: 8px; }
            thead th { font-size: 11px; padding: 10px 10px; }
            tbody td { padding: 9px 10px; font-size: 12px; }
        }
    </style>
</head>

<body>
<?php require_once 'menu.php'; ?>

    <div class="sayfa-wrap">

        <div class="sayfa-baslik-blok">
            <div class="sayfa-baslik-ikon">📄</div>
            <h1>Fatura Listesi & Cari Hesap<small>Faturalar, tahsilatlar ve müşteri cari dökümü</small></h1>
        </div>

        <!-- FİLTRE BARI -->
        <div class="filtre-bar">
            <input type="text" id="aramaInput" placeholder="🔍 Müşteri / Fatura No / Seri No ara..." style="min-width:220px;">
            <select id="durumFiltre">
                <option value="">Tüm Durumlar</option>
                <option value="odenmedi">Ödenmedi</option>
                <option value="kismi">Kısmi Ödendi</option>
                <option value="odendi">Ödendi</option>
            </select>
            <input type="date" id="tarihBas">
            <input type="date" id="tarihBit">
            <button class="btn btn-primary" onclick="listeYukle(1)">🔍 Filtrele</button>
            <button class="btn btn-gray" onclick="filtreTemizle()">✕ Temizle</button>
            <button class="btn btn-success" onclick="tahsilatModalAc(null,null,null)" style="margin-left:auto;">➕
                Tahsilat Ekle</button>
        </div>

        <?php if (!empty($etiketler)): ?>
        <!-- ETİKET FİLTRE CHİPLERİ -->
        <div class="etiket-chip-row">
            <span style="font-size:12px;font-weight:600;color:#374151;align-self:center;">🏷 Etiket:</span>
            <?php foreach ($etiketler as $et): ?>
            <span class="etiket-chip" id="etChip<?= (int)$et['id'] ?>"
                style="background:<?= htmlspecialchars($et['renk'] ?? '#e5e7eb') ?>;color:#fff;"
                onclick="etiketFiltreSec(<?= (int)$et['id'] ?>)">
                <?= htmlspecialchars($et['ad']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ÖZET KARTLAR -->
        <div class="ozet-kartlar">
            <div class="ozet-kart fatura">
                <div class="etiket">📄 Toplam Fatura</div>
                <div class="deger" id="ozetFaturaSayi">—</div>
            </div>
            <div class="ozet-kart borc">
                <div class="etiket">💸 Toplam Borç</div>
                <div class="deger" id="ozetBorc">—</div>
            </div>
            <div class="ozet-kart odeme">
                <div class="etiket">✅ Toplam Tahsilat</div>
                <div class="deger" id="ozetOdeme">—</div>
            </div>
            <div class="ozet-kart kalan">
                <div class="etiket">⏳ Kalan Bakiye</div>
                <div class="deger" id="ozetKalan">—</div>
            </div>
        </div>

        <!-- FATURA TABLOSU -->
        <div class="tablo-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fatura No</th>
                        <th>Tarih</th>
                        <th>Vade</th>
                        <th>Müşteri</th>
                        <th class="text-right">Toplam</th>
                        <th class="text-right">Ödenen</th>
                        <th class="text-right">Kalan</th>
                        <th>Durum</th>
                        <th>Personel</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody id="faturaTabloBody">
                    <tr>
                        <td colspan="9" class="yukle-spin">⏳ Yükleniyor...</td>
                    </tr>
                </tbody>
            </table>
            <div class="sayfalama" id="sayfalamaWrap"></div>
        </div>

        <!-- CARİ HESAP PANEL -->
        <div class="cari-panel" id="cariPanel">
            <div class="cari-baslik">
                <h2 id="cariBaslikAd">📊 Cari Hesap Dökümü</h2>
                <button class="btn btn-gray" onclick="cariKapat()" style="padding:5px 12px;font-size:12px;">✕
                    Kapat</button>
            </div>
            <div class="cari-ozet" id="cariOzet"></div>
            <div style="overflow-x:auto;">
                <table class="cari-tablo">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Tür</th>
                            <th>Referans</th>
                            <th>Açıklama</th>
                            <th class="text-right">Borç</th>
                            <th class="text-right">Alacak</th>
                            <th class="text-right">Bakiye</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="cariTabloBody"></tbody>
                </table>
            </div>
        </div>

    </div><!-- /sayfa-wrap -->

    <!-- TAHSİLAT MODAL -->
    <div class="modal-overlay" id="tahsilatModal">
        <div class="modal-kart" style="max-width:480px;width:100%;">
            <h3>💰 Tahsilat Ekle</h3>
            <div class="form-grup" style="position:relative;">
                <label>Müşteri *</label>
                <input type="text" id="thsMusteriAd" placeholder="Müşteri adı ara..." oninput="musteriAra(this.value)" autocomplete="off">
                <input type="hidden" id="thsMusteriId">
                <div id="thsMusteriOneri"></div>
            </div>
            <div class="form-grup">
                <label>Fatura (opsiyonel)</label>
                <select id="thsFaturaId" onchange="faturaSecTutar()">
                    <option value="">— Genel Ödeme —</option>
                </select>
            </div>
            <div class="form-grup">
                <label>Tutar *</label>
                <input type="number" id="thsTutar" placeholder="0.00" step="0.01" min="0.01">
            </div>

            <!-- 2 AŞAMALI ÖDEME SEÇİMİ -->
            <div class="form-grup">
                <label>Ödeme Türü *</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="thsTurGrid">
                    <button type="button" class="tur-btn" data-tur="nakit" onclick="turSec(this)"
                        style="padding:10px 6px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;text-align:center;transition:all .2s;">
                        💵<br><span>Nakit</span>
                    </button>
                    <button type="button" class="tur-btn" data-tur="banka" onclick="turSec(this)"
                        style="padding:10px 6px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;text-align:center;transition:all .2s;">
                        🏦<br><span>Banka/EFT</span>
                    </button>
                    <button type="button" class="tur-btn" data-tur="pos" onclick="turSec(this)"
                        style="padding:10px 6px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;text-align:center;transition:all .2s;">
                        💳<br><span>POS</span>
                    </button>
                </div>
                <input type="hidden" id="thsOdemeTipi">
            </div>

            <div class="form-grup" id="thsHesapGrup" style="display:none;">
                <label>Hesap *</label>
                <div id="thsHesapGrid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;"></div>
                <input type="hidden" id="thsHesapId">
            </div>

            <div class="form-grup">
                <label>Tarih *</label>
                <input type="date" id="thsTarih">
            </div>
            <div class="form-grup">
                <label>Açıklama</label>
                <textarea id="thsAciklama" rows="2" placeholder="Ödeme açıklaması..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-gray" onclick="modalKapat()">İptal</button>
                <button class="btn btn-success" onclick="tahsilatKaydet()">💾 Kaydet</button>
            </div>
        </div>
    </div>

    <script>
        'use strict';

        // ── STATE ──
        let mevcutSayfa = 1;
        const sayfaBoyutu = 20;
        let aramaTimer = null;
        let thsAramaTimer = null;
        let aktifCariId = null;
        let aktifCariAdi = null;
        let aktifEtiketId = null;

        // ── BAŞLANGIÇ ──
        document.addEventListener('DOMContentLoaded', () => {
            // Güncelleme başarı mesajı
            if (new URLSearchParams(location.search).get('guncellendi') == '1') {
                const d = document.createElement('div');
                d.style.cssText = 'position:fixed;top:20px;right:20px;background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;border-radius:10px;padding:14px 20px;font-weight:600;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.1);';
                d.textContent = '✅ Fatura başarıyla güncellendi!';
                document.body.appendChild(d);
                setTimeout(()=>d.remove(), 4000);
                history.replaceState({}, '', 'fatura_liste.php');
            }
            document.getElementById('thsTarih').value = bugunTarih();
            listeYukle(1);
        });

        function bugunTarih() {
            return new Date().toISOString().split('T')[0];
        }

        // ════════════════════════════════════════════════════════
        // FATURA LİSTESİ
        // ════════════════════════════════════════════════════════
        function listeYukle(sayfa = 1) {
            mevcutSayfa = sayfa;
            const tbody = document.getElementById('faturaTabloBody');
            tbody.innerHTML =
                '<tr><td colspan="9" class="yukle-spin">⏳ Yükleniyor...</td></tr>';

            const body = 'action=fatura_liste_bakiye'
                + '&limit=' + sayfaBoyutu
                + '&offset=' + ((sayfa - 1) * sayfaBoyutu)
                + '&arama=' + encodeURIComponent(document.getElementById('aramaInput').value.trim())
                + '&durum=' + encodeURIComponent(document.getElementById('durumFiltre').value)
                + '&tarih_bas=' + encodeURIComponent(document.getElementById('tarihBas').value)
                + '&tarih_bit=' + encodeURIComponent(document.getElementById('tarihBit').value)
                + '&etiket_id=' + encodeURIComponent(aktifEtiketId || '');

            fetch('tahsilat_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
                .then(r => r.json())
                .then(v => {
                    if (!v.basari || !v.faturalar || !v.faturalar.length) {
                        tbody.innerHTML =
                            '<tr><td colspan="9" class="bos-mesaj">📭 Kayıt bulunamadı.</td></tr>';
                        ozetGuncelle(0, 0, 0, 0);
                        document.getElementById('sayfalamaWrap').innerHTML = '';
                        return;
                    }
                    renderFaturaTablosu(v.faturalar);
                    renderSayfalama(v.toplam, sayfa);
                    ozetGuncelle(v.toplam, v.toplam_borc, v.toplam_alacak, v.net_bakiye);
                })
                .catch(err => {
                    tbody.innerHTML =
                        `<tr><td colspan="9" class="bos-mesaj">❌ Hata: ${err.message}</td></tr>`;
                });
        }


        function renderFaturaTablosu(faturalar) {
            const tbody = document.getElementById('faturaTabloBody');
            tbody.innerHTML = faturalar.map(f => {
                const kalan = parseFloat(f.kalan ?? 0);
                const kalanCls = kalan > 0 ? 'text-red' : 'text-green';
                return `
        <tr>
            <td><a href="fatura_pdf.php?id=${f.id}" target="_blank" title="PDF Görüntüle" style="color:#1e3a8a;font-weight:700;text-decoration:none;border-bottom:1.5px dashed #93c5fd;">${esc(f.fatura_no)}</a></td>
            <td>${tarihFmt(f.tarih)}</td>
            <td>${f.vade_tarihi ? tarihFmt(f.vade_tarihi) : '—'}</td>
            <td>
                <a href="#"
                   onclick="cariAc(${parseInt(f.musteri_id) || 0}, '${esc(f.musteri_adi)}'); return false;"
                   style="color:#3b82f6;text-decoration:none;font-weight:600;">
                    ${esc(f.musteri_adi)}
                </a>
            </td>
            <td class="text-right">${paraBicim(f.toplam)}</td>
            <td class="text-right text-green">${paraBicim(f.odenen)}</td>
            <td class="text-right ${kalanCls}">${paraBicim(kalan)}</td>
            <td>${durumBadge(f.odeme_durumu, f.kalan)}</td>
            <td>${f.personel_adi ? `<span style="background:#ede9fe;color:#6d28d9;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;">${esc(f.personel_adi)}</span>` : '—'}</td>
            <td>
                <button class="btn btn-success"
                        style="padding:4px 10px;font-size:12px;"
                        onclick="tahsilatModalAc(${parseInt(f.id)}, '${esc(f.musteri_adi)}', ${parseInt(f.musteri_id)})">
                    💰 Tahsilat
                </button>
                <a href="fatura_duzenle.php?id=${parseInt(f.id)}"
                   class="btn" style="padding:4px 10px;font-size:12px;background:#fef3c7;color:#92400e;text-decoration:none;">
                    ✏️ Düzenle
                </a>
                ${kalan > 0 ? `<button class="btn" style="padding:4px 10px;font-size:12px;background:#ede9fe;color:#6d28d9;"
                    onclick="odemeLinki(${parseInt(f.id)}, '${esc(f.fatura_no)}', ${parseFloat(f.kalan)})">
                    💳 Link
                </button>` : ''}
                <button class="btn" style="padding:4px 10px;font-size:12px;background:#ecfdf5;color:#065f46;"
                    onclick="faturaLinkPaylasim(${parseInt(f.id)}, '${esc(f.fatura_no)}', '${esc(f.paylasim_token||'')}', '${esc(f.musteri_tel||'')}')">
                    🔗 Paylaş
                </button>
            </td>
        </tr>`;
            }).join('');
        }

        // ✅ Özet kartları sunucudan gelen gerçek verilerle güncelle
        function ozetGuncelle(toplamKayit, toplamBorc, toplamAlacak, netBakiye) {
            document.getElementById('ozetFaturaSayi').textContent = toplamKayit + ' adet';
            document.getElementById('ozetBorc').textContent = paraBicim(toplamBorc);
            document.getElementById('ozetOdeme').textContent = paraBicim(toplamAlacak);
            document.getElementById('ozetKalan').textContent = paraBicim(netBakiye);
        }

        function renderSayfalama(toplam, aktifSayfa) {
            const wrap = document.getElementById('sayfalamaWrap');
            const sayfaSayi = Math.ceil(toplam / sayfaBoyutu);
            if (sayfaSayi <= 1) { wrap.innerHTML = ''; return; }

            let html = '';
            if (aktifSayfa > 1)
                html += `<button class="sayfa-btn"
                         onclick="listeYukle(${aktifSayfa - 1})">‹ Önceki</button>`;

            const bas = Math.max(1, aktifSayfa - 2);
            const son = Math.min(sayfaSayi, aktifSayfa + 2);
            for (let i = bas; i <= son; i++) {
                html += `<button class="sayfa-btn ${i === aktifSayfa ? 'aktif' : ''}"
                         onclick="listeYukle(${i})">${i}</button>`;
            }

            if (aktifSayfa < sayfaSayi)
                html += `<button class="sayfa-btn"
                         onclick="listeYukle(${aktifSayfa + 1})">Sonraki ›</button>`;

            wrap.innerHTML = html;
        }

        function filtreTemizle() {
            document.getElementById('aramaInput').value = '';
            document.getElementById('durumFiltre').value = '';
            document.getElementById('tarihBas').value = '';
            document.getElementById('tarihBit').value = '';
            aktifEtiketId = null;
            document.querySelectorAll('.etiket-chip').forEach(c => c.classList.remove('aktif'));
            listeYukle(1);
        }

        function etiketFiltreSec(id) {
            if (aktifEtiketId === id) {
                aktifEtiketId = null;
                document.getElementById('etChip' + id).classList.remove('aktif');
            } else {
                if (aktifEtiketId) document.getElementById('etChip' + aktifEtiketId).classList.remove('aktif');
                aktifEtiketId = id;
                document.getElementById('etChip' + id).classList.add('aktif');
            }
            listeYukle(1);
        }

        // ════════════════════════════════════════════════════════
        // CARİ HESAP DÖKÜMÜ
        // ════════════════════════════════════════════════════════
        function cariAc(musteriId, musteriAdi) {
            if (!musteriId) return;
            aktifCariId = musteriId;
            aktifCariAdi = musteriAdi;

            document.getElementById('cariBaslikAd').textContent =
                '📊 Cari Hesap Dökümü — ' + musteriAdi;
            document.getElementById('cariTabloBody').innerHTML =
                '<tr><td colspan="8" class="yukle-spin">⏳ Yükleniyor...</td></tr>';
            document.getElementById('cariOzet').innerHTML = '';

            const panel = document.getElementById('cariPanel');
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth' });

            // ✅ action değeri URLSearchParams ile encode ediliyor (ü sorunu yok)
            const params = new URLSearchParams({
                action: 'cari_dokum',   // PHP tarafında da 'cari_dokum' olmalı
                musteri_id: musteriId
            });

            fetch('tahsilat_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(r => r.json())
                .then(v => {
                    if (!v.basari) {
                        document.getElementById('cariTabloBody').innerHTML =
                            '<tr><td colspan="8" class="bos-mesaj">❌ ' + (v.mesaj || 'Hata') + '</td></tr>';
                        return;
                    }
                    renderCariTablo(v.hareketler);
                    renderCariOzet(v.toplam_borc, v.toplam_alacak, v.net_bakiye);
                });
        }

        function renderCariTablo(hareketler) {
            const tbody = document.getElementById('cariTabloBody');
            if (!hareketler || !hareketler.length) {
                tbody.innerHTML =
                    '<tr><td colspan="8" class="bos-mesaj">📭 Hareket yok.</td></tr>';
                return;
            }
            tbody.innerHTML = hareketler.map(h => {
                const isFatura = h.tip === 'fatura';
                const satirCls = isFatura ? 'borc-satir' : 'alacak-satir';
                const tipIcon = isFatura ? '📄 Fatura' : '💰 Tahsilat';
                const silBtn = !isFatura
                    ? `<button class="btn btn-danger"
                       style="padding:3px 8px;font-size:11px;"
                       onclick="tahsilatSil(${parseInt(h.id) || 0})">🗑</button>`
                    : '—';
                const bakiyeAbs = Math.abs(parseFloat(h.bakiye));
                const bakiyeEtk = parseFloat(h.bakiye) > 0
                    ? '(B)' : parseFloat(h.bakiye) < 0 ? '(A)' : '';
                const bakiyeCls = parseFloat(h.bakiye) > 0 ? 'text-red' : 'text-green';
                return `
        <tr class="${satirCls}">
            <td>${tarihFmt(h.tarih)}</td>
            <td>${tipIcon}</td>
            <td><strong>${esc(h.referans)}</strong></td>
            <td>${esc(h.aciklama || '—')}</td>
            <td class="text-right">
                ${parseFloat(h.borc) > 0 ? paraBicim(h.borc) : '—'}
            </td>
            <td class="text-right">
                ${parseFloat(h.alacak) > 0 ? paraBicim(h.alacak) : '—'}
            </td>
            <td class="text-right">
                <strong class="${bakiyeCls}">
                    ${paraBicim(bakiyeAbs)} ${bakiyeEtk}
                </strong>
            </td>
            <td>${silBtn}</td>
        </tr>`;
            }).join('');
        }

        function renderCariOzet(borc, alacak, net) {
            const netCls = parseFloat(net) > 0 ? 'text-red' : 'text-green';
            const netEtk = parseFloat(net) > 0
                ? '(Borçlu)' : parseFloat(net) < 0 ? '(Alacaklı)' : '(Kapalı)';
            document.getElementById('cariOzet').innerHTML = `
        <div class="cari-ozet-item">
            <div class="etiket">💸 Toplam Borç</div>
            <div class="deger text-red">${paraBicim(borc)}</div>
        </div>
        <div class="cari-ozet-item">
            <div class="etiket">✅ Toplam Tahsilat</div>
            <div class="deger text-green">${paraBicim(alacak)}</div>
        </div>
        <div class="cari-ozet-item">
            <div class="etiket">⚖️ Net Bakiye</div>
            <div class="deger ${netCls}">
                ${paraBicim(Math.abs(parseFloat(net)))} ${netEtk}
            </div>
        </div>`;
        }

        function cariKapat() {
            document.getElementById('cariPanel').style.display = 'none';
            aktifCariId = null;
            aktifCariAdi = null;
        }

        // ════════════════════════════════════════════════════════
        // TAHSİLAT MODAL
        // ════════════════════════════════════════════════════════
        // ════════════════════════════════════════════════════════
        // TAHSİLAT MODAL — 2 AŞAMALI (TÜR → HESAP)
        // ════════════════════════════════════════════════════════
        let _tumHesaplar = [];

        async function tahsilatModalAc(faturaId, musteriAdi, musteriId) {
            document.getElementById('thsMusteriId').value = musteriId || '';
            document.getElementById('thsMusteriAd').value = musteriAdi || '';
            document.getElementById('thsTutar').value     = '';
            document.getElementById('thsAciklama').value  = '';
            document.getElementById('thsTarih').value     = bugunTarih();
            document.getElementById('thsOdemeTipi').value = '';
            document.getElementById('thsHesapId').value   = '';
            document.getElementById('thsMusteriOneri').innerHTML = '';
            document.getElementById('thsHesapGrup').style.display = 'none';
            document.getElementById('thsHesapGrid').innerHTML = '';

            // Tür butonları reset
            document.querySelectorAll('.tur-btn').forEach(b => {
                b.style.borderColor = '#e2e8f0';
                b.style.background  = '#fff';
                b.style.color       = '#64748b';
            });

            // Hesapları yükle (cache'le)
            if (_tumHesaplar.length === 0) {
                const v = await fetch('odeme_hesap_kontrol.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: 'action=hesap_liste'
                }).then(r => r.json()).catch(() => ({hesaplar:[]}));
                _tumHesaplar = v.hesaplar || [];
            }

            // Fatura listesini yükle
            const fatSel = document.getElementById('thsFaturaId');
            fatSel.innerHTML = '<option value="">— Genel Ödeme —</option>';
            if (musteriId) {
                const p = new URLSearchParams({action:'fatura_liste_bakiye', musteri_id:musteriId, limit:100, offset:0});
                const v = await fetch('tahsilat_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p.toString()})
                    .then(r => r.json()).catch(() => ({faturalar:[]}));
                (v.faturalar || []).filter(f => f.odeme_durumu !== 'odendi').forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.dataset.kalan = f.kalan;
                    opt.textContent = `${f.fatura_no} — Kalan: ${paraBicim(f.kalan)}`;
                    if (parseInt(f.id) === parseInt(faturaId)) opt.selected = true;
                    fatSel.appendChild(opt);
                });
                setTimeout(faturaSecTutar, 100);
            }

            document.getElementById('tahsilatModal').classList.add('aktif');
        }

        function turSec(btn) {
            // Tüm butonları pasif yap
            document.querySelectorAll('.tur-btn').forEach(b => {
                b.style.borderColor = '#e2e8f0';
                b.style.background  = '#fff';
                b.style.color       = '#64748b';
            });
            // Seçileni aktif yap
            btn.style.borderColor = '#3b82f6';
            btn.style.background  = '#eff6ff';
            btn.style.color       = '#1d4ed8';

            const tur = btn.dataset.tur;
            document.getElementById('thsOdemeTipi').value = tur;

            // Türe göre hesapları filtrele
            const turMap = { nakit: 'nakit', banka: 'banka', pos: 'pos' };
            const phpTur = turMap[tur] || tur;
            const filtreliHesaplar = _tumHesaplar.filter(h => h.tip === phpTur || h.tur === phpTur);

            const grid = document.getElementById('thsHesapGrid');
            document.getElementById('thsHesapId').value = '';

            if (filtreliHesaplar.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1;padding:10px;color:#ef4444;font-size:13px;">⚠️ Bu tür için tanımlı hesap yok. Ayarlardan ekleyin.</div>';
            } else {
                grid.innerHTML = filtreliHesaplar.map(h => `
                    <button type="button" class="hesap-btn" data-id="${h.id}" data-ad="${esc(h.ad)}" onclick="hesapSec(this)"
                        style="padding:10px 8px;border:2px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:12px;font-weight:600;color:#374151;text-align:center;transition:all .2s;line-height:1.4;">
                        ${h.ikon||'💳'}<br>${esc(h.ad)}<br>
                        <span style="font-size:11px;color:#6b7280;font-weight:400;">${paraBicim(h.bakiye||0)}</span>
                    </button>`).join('');
            }
            document.getElementById('thsHesapGrup').style.display = 'block';
        }

        function hesapSec(btn) {
            document.querySelectorAll('.hesap-btn').forEach(b => {
                b.style.borderColor = '#e2e8f0';
                b.style.background  = '#fff';
            });
            btn.style.borderColor = '#10b981';
            btn.style.background  = '#f0fdf4';
            document.getElementById('thsHesapId').value = btn.dataset.id;
        }

        function modalKapat() {
            document.getElementById('tahsilatModal').classList.remove('aktif');
        }

        function faturaSecTutar() {
            const sel = document.getElementById('thsFaturaId');
            const opt = sel.options[sel.selectedIndex];
            const kalan = parseFloat(opt?.dataset?.kalan || 0);
            if (kalan > 0) document.getElementById('thsTutar').value = kalan.toFixed(2);
        }

        document.getElementById('tahsilatModal').addEventListener('click', function(e) {
            if (e.target === this) modalKapat();
        });

        function tahsilatKaydet() {
            const musteriId = document.getElementById('thsMusteriId').value;
            const tutar     = document.getElementById('thsTutar').value;
            const odemeTipi = document.getElementById('thsOdemeTipi').value;

            if (!musteriId)                        { alert('⚠️ Müşteri seçiniz.'); return; }
            if (!tutar || parseFloat(tutar) <= 0)  { alert('⚠️ Geçerli tutar giriniz.'); return; }
            if (!odemeTipi)                        { alert('⚠️ Ödeme türü seçiniz.'); return; }

            const params = new URLSearchParams({
                action:     'tahsilat_ekle',
                musteri_id: musteriId,
                fatura_id:  document.getElementById('thsFaturaId')?.value || '',
                hesap_id:   document.getElementById('thsHesapId')?.value  || '',
                tutar,
                odeme_tipi: odemeTipi,
                aciklama:   document.getElementById('thsAciklama').value,
                tarih:      document.getElementById('thsTarih').value,
            });

            fetch('tahsilat_kontrol.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: params.toString()
            }).then(r => r.json()).then(v => {
                if (v.basari) {
                    modalKapat();
                    listeYukle(mevcutSayfa);
                    if (aktifCariId) cariAc(aktifCariId, aktifCariAdi);
                    // Makbuz modal aç
                    makbuzModalAc(v);
                } else {
                    alert('❌ ' + v.mesaj);
                }
            });
        }


        // ════════════════════════════════════════════════════════
        // TAHSİLAT SİL
        // ════════════════════════════════════════════════════════
        function tahsilatSil(id) {
            if (!id || !confirm('Bu tahsilatı silmek istediğinizden emin misiniz?'))
                return;

            fetch('tahsilat_kontrol.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=tahsilat_sil&id=${id}`
            })
                .then(r => r.json())
                .then(v => {
                    if (v.basari) {
                        listeYukle(mevcutSayfa);
                        if (aktifCariId) cariAc(aktifCariId, aktifCariAdi);
                    } else {
                        alert('❌ ' + v.mesaj);
                    }
                });
        }

        // ════════════════════════════════════════════════════════
        // MÜŞTERİ ARAMA (modal içi)
        // ════════════════════════════════════════════════════════
        function musteriAra(q) {
            clearTimeout(thsAramaTimer);
            const oneri = document.getElementById('thsMusteriOneri');
            if (q.length < 2) { oneri.innerHTML = ''; return; }

            thsAramaTimer = setTimeout(() => {
                fetch('musteri_kontrol.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=ara&ad_soyad=' + encodeURIComponent(q)
                })
                    .then(r => r.json())
                    .then(v => {
                        oneri.innerHTML = '';
                        if (!v.musteriler || !v.musteriler.length) return;

                        const wrap = document.createElement('div');
                        wrap.className = 'oneri-wrap';
                        v.musteriler.forEach(m => {
                            const d = document.createElement('div');
                            d.className = 'oneri-item';
                            d.textContent = m.ad_soyad;
                            d.onclick = () => {
                                document.getElementById('thsMusteriId').value = m.id;
                                document.getElementById('thsMusteriAd').value = m.ad_soyad;
                                oneri.innerHTML = '';
                                // Fatura listesini bu müşteri için yükle
                                tahsilatModalAc(null, m.ad_soyad, m.id);
                            };
                            wrap.appendChild(d);
                        });
                        oneri.appendChild(wrap);
                    });
            }, 300);
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
            const p = tarih.split('-');
            if (p.length !== 3) return tarih;
            return `${p[2]}.${p[1]}.${p[0]}`;
        }

        function durumBadge(durum, kalan) {
            if (typeof kalan !== 'undefined' && parseFloat(kalan) <= 0 && durum !== 'iptal') durum = 'odendi';
            const map = {
                odenmedi: ['badge-odenmedi', '⛔ Ödenmedi'],
                kismi: ['badge-kismi', '⚠️ Kısmi'],
                odendi: ['badge-odendi', '✅ Ödendi'],
            };
            const [cls, lbl] = map[durum] || ['badge-odenmedi', '⛔ Ödenmedi'];
            return `<span class="badge ${cls}">${lbl}</span>`;
        }

        function esc(s) {
            return String(s ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Arama debounce
        document.getElementById('aramaInput').addEventListener('input', () => {
            clearTimeout(aramaTimer);
            aramaTimer = setTimeout(() => listeYukle(1), 400);
        });

        // ── Ödeme Linki Modal ────────────────────────────────────────
        let odemeLinki_faturaId = null;

        function olmGizle() { document.getElementById('odemeLinKModal').style.display = 'none'; }
        function olmGoster() { document.getElementById('odemeLinKModal').style.display = 'flex'; }

        function odemeLinki(faturaId, faturaNo, kalan) {
            odemeLinki_faturaId = faturaId;
            document.getElementById('olmFaturaNo').textContent = faturaNo;
            document.getElementById('olmTutar').textContent    = kalan.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
            document.getElementById('olmDurum').innerHTML      = '';
            document.getElementById('olmLinkSonuc').innerHTML  = '';
            olmGoster();
        }
    </script>

<!-- Ödeme Linki Modal -->
<div class="modal-overlay" id="odemeLinKModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:460px;max-width:95%;box-shadow:0 8px 40px rgba(0,0,0,.2);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="margin:0;font-size:16px;color:#1e3a8a;">💳 Ödeme Linki Oluştur</h3>
            <button id="olmKapat" style="border:none;background:none;font-size:20px;cursor:pointer;color:#6b7280;">✕</button>
        </div>
        <div style="background:#f8faff;border-radius:10px;padding:14px;margin-bottom:18px;font-size:13px;">
            <div>Fatura: <strong id="olmFaturaNo"></strong></div>
            <div style="margin-top:4px;">Kalan Tutar: <strong id="olmTutar" style="color:#dc2626;"></strong></div>
        </div>
        <div style="margin-bottom:14px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">Gönderim Modu</label>
            <div style="display:flex;gap:16px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="olmMod" value="sms" checked> SMS / E-posta ile Gönder
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                    <input type="radio" name="olmMod" value="ortak"> Sadece Link Oluştur
                </label>
            </div>
        </div>
        <div style="margin-bottom:18px;">
            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Maksimum Taksit</label>
            <select id="olmTaksit" style="padding:8px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                <option value="1">Taksitsiz</option>
                <option value="2">2 Taksit</option>
                <option value="3">3 Taksit</option>
                <option value="6">6 Taksit</option>
                <option value="9">9 Taksit</option>
                <option value="12">12 Taksit</option>
            </select>
        </div>
        <div id="olmDurum" style="min-height:20px;font-size:13px;margin-bottom:8px;"></div>
        <button id="olmGonder" style="width:100%;padding:12px;border:none;border-radius:10px;background:#6d28d9;color:#fff;font-size:14px;font-weight:600;cursor:pointer;">
            💳 Ödeme Linki Oluştur &amp; Gönder
        </button>
        <div id="olmLinkSonuc" style="margin-top:12px;"></div>
    </div>
</div>
<script>
// ── Ödeme Linki Buton Eventleri (modal HTML render edildikten sonra) ──
document.getElementById('olmKapat').addEventListener('click', olmGizle);
document.getElementById('odemeLinKModal').addEventListener('click', function(e) {
    if (e.target === this) olmGizle();
});
document.getElementById('olmGonder').addEventListener('click', async function () {
    if (!odemeLinki_faturaId) return;
    const mod       = document.querySelector('input[name="olmMod"]:checked').value;
    const maxTaksit = document.getElementById('olmTaksit').value;
    const btn       = this;
    btn.disabled    = true;
    document.getElementById('olmDurum').innerHTML = '<span style="color:#6b7280;">⏳ Link oluşturuluyor...</span>';

    const form = new FormData();
    form.append('action',     'link_olustur');
    form.append('tip',        'fatura');
    form.append('ref_id',     odemeLinki_faturaId);
    form.append('mod',        mod);
    form.append('max_taksit', maxTaksit);

    const r = await fetch('odeme_linki_kontrol.php', {method:'POST', body: form})
        .then(x => x.json())
        .catch(e => ({basari: false, mesaj: String(e)}));
    btn.disabled = false;

    if (!r.basari) {
        document.getElementById('olmDurum').innerHTML = `<span style="color:#dc2626;">❌ ${esc(r.mesaj)}</span>`;
        if (r.raw) {
            document.getElementById('olmLinkSonuc').innerHTML =
                `<pre style="margin-top:10px;padding:10px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:11px;overflow-x:auto;white-space:pre-wrap;color:#7f1d1d;">${esc(typeof r.raw === 'string' ? r.raw : JSON.stringify(r.raw, null, 2))}</pre>`;
        }
        return;
    }
    document.getElementById('olmDurum').innerHTML = '<span style="color:#16a34a;">✅ Link oluşturuldu!</span>';

    let html = '';
    if (r.link_url) {
        const linkUrl = r.link_url.replace(/'/g, "\\'");
        html += `<div style="margin-top:10px;padding:10px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;">
            <div style="font-size:12px;color:#15803d;margin-bottom:6px;font-weight:600;">Ödeme Linki:</div>
            <a href="${r.link_url}" target="_blank" style="word-break:break-all;font-size:12px;color:#1d4ed8;">${esc(r.link_url)}</a>
            <button onclick="navigator.clipboard.writeText('${linkUrl}');this.textContent='✅ Kopyalandı!';setTimeout(()=>this.textContent='📋 Kopyala',2000)"
                style="display:block;margin-top:8px;padding:6px 14px;border:none;border-radius:6px;background:#1d4ed8;color:#fff;cursor:pointer;font-size:12px;">📋 Kopyala</button>
        </div>`;
    }
    if (r.musteri_tel)   html += `<div style="margin-top:8px;font-size:12px;color:#6b7280;">📱 SMS: ${esc(r.musteri_tel)}</div>`;
    if (r.musteri_email) html += `<div style="font-size:12px;color:#6b7280;">✉️ E-posta: ${esc(r.musteri_email)}</div>`;
    document.getElementById('olmLinkSonuc').innerHTML = html;
});
</script>

<!-- MAKBUZ MODAL (tahsilat sonrası) -->
<div id="makbuzModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="text-align:center;padding:10px 0 18px;">
            <div style="font-size:42px">🧾</div>
            <div style="font-size:18px;font-weight:800;color:#065f46;margin-top:6px;">Tahsilat Kaydedildi!</div>
            <div style="font-size:14px;color:#6b7280;margin-top:4px;" id="mkbMusteriAdi"></div>
        </div>
        <div style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:2px solid #6ee7b7;border-radius:12px;padding:16px;text-align:center;margin-bottom:18px;">
            <div style="font-size:12px;font-weight:700;color:#065f46;letter-spacing:.5px;text-transform:uppercase;">Tahsil Edilen</div>
            <div style="font-size:32px;font-weight:900;color:#065f46;" id="mkbTutar"></div>
            <div style="font-size:13px;color:#059669;margin-top:4px;" id="mkbOdeme"></div>
        </div>
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <a id="mkbMakbuzAc" href="#" target="_blank"
               style="flex:1;padding:11px;background:#ecfdf5;color:#065f46;border-radius:10px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;border:2px solid #6ee7b7;">
                👁️ Makbuzu Görüntüle
            </a>
            <button onclick="mkbKopyala()" id="mkbKopyalaBtn"
                style="flex:1;padding:11px;background:#1e3a8a;color:#fff;border:none;border-radius:10px;cursor:pointer;font-size:13px;font-weight:700;">
                📋 Linki Kopyala
            </button>
        </div>
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <a id="mkbWhatsapp" href="#" target="_blank"
               style="flex:1;padding:11px;background:#dcfce7;color:#166534;border-radius:10px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;">
                💬 WhatsApp ile Gönder
            </a>
            <button onclick="window.open(document.getElementById('mkbMakbuzAc').href,'_blank'); window.print && false"
                style="flex:1;padding:11px;background:#f1f5f9;color:#374151;border:none;border-radius:10px;cursor:pointer;font-size:13px;font-weight:700;">
                🖨️ Yazdır
            </button>
        </div>
        <button onclick="makbuzModalKapat()" style="width:100%;padding:11px;background:#f1f5f9;color:#374151;border:none;border-radius:10px;cursor:pointer;font-size:14px;font-weight:600;">
            Kapat
        </button>
    </div>
</div>

<script>
let _makbuzUrl = '';

function makbuzModalAc(v) {
    if (!v || !v.makbuz_token) return;
    const url = window.location.origin + '/FaturaApp/makbuz_public.php?t=' + v.makbuz_token;
    _makbuzUrl = url;

    document.getElementById('mkbMusteriAdi').textContent  = v.musteri_adi || '';
    document.getElementById('mkbTutar').textContent        = parseFloat(v.tutar).toLocaleString('tr-TR',{minimumFractionDigits:2}) + ' ₺';
    document.getElementById('mkbOdeme').textContent        = (v.odeme_tipi||'') + (v.hesap_adi ? ' — ' + v.hesap_adi : '') + (v.fatura_no ? ' | ' + v.fatura_no : '');
    document.getElementById('mkbMakbuzAc').href            = url;

    const tel = (v.musteri_tel||'').replace(/\D/g,'');
    const waMsg = encodeURIComponent('Sayın ' + (v.musteri_adi||'Müşteri') + ', tahsilat makbuzunuzu görüntülemek için: ' + url);
    const waEl  = document.getElementById('mkbWhatsapp');
    waEl.href   = tel ? 'https://wa.me/' + tel + '?text=' + waMsg : 'https://wa.me/?text=' + waMsg;
    waEl.style.opacity = tel ? '1' : '0.6';

    document.getElementById('makbuzModal').style.display = 'flex';
}

function makbuzModalKapat() {
    document.getElementById('makbuzModal').style.display = 'none';
}

document.getElementById('makbuzModal').addEventListener('click', function(e) {
    if (e.target === this) makbuzModalKapat();
});

function mkbKopyala() {
    navigator.clipboard.writeText(_makbuzUrl).then(() => {
        const btn = document.getElementById('mkbKopyalaBtn');
        btn.textContent = '✅ Kopyalandı!';
        btn.style.background = '#10b981';
        setTimeout(() => { btn.textContent = '📋 Linki Kopyala'; btn.style.background = '#1e3a8a'; }, 2000);
    }).catch(() => prompt('Linki kopyalayın:', _makbuzUrl));
}
</script>

<!-- FATURA PAYLAŞIM MODAL
═══════════════════════════════════════════════════════════════ -->
<div id="flpModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:18px;font-weight:800;color:#1e293b;">🔗 Fatura Paylaşım Linki</h3>
            <button onclick="flpGizle()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;line-height:1;">✕</button>
        </div>

        <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:16px;">
            <div style="font-size:12px;color:#64748b;margin-bottom:6px;font-weight:600;">FATURA NO</div>
            <div style="font-size:16px;font-weight:800;color:#1e3a8a;" id="flpFaturaNo"></div>
        </div>

        <div id="flpYukleniyor" style="display:none;text-align:center;padding:16px;color:#6b7280;">⏳ Link oluşturuluyor...</div>

        <div id="flpLinkAlani" style="display:none;">
            <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Paylaşım Linki</div>
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:14px;">
                <input id="flpLinkInput" type="text" readonly
                    style="flex:1;padding:10px 14px;border:2px solid #e2e8f0;border-radius:8px;font-size:12px;font-family:monospace;background:#f8fafc;color:#1e293b;"
                    onclick="this.select()">
                <button onclick="flpKopyala()" id="flpKopyalaBtn"
                    style="padding:10px 14px;background:#1e3a8a;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:700;white-space:nowrap;">
                    📋 Kopyala
                </button>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a id="flpAcBtn" href="#" target="_blank"
                    style="flex:1;padding:10px;background:#eff6ff;color:#1e40af;border-radius:8px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;">
                    👁️ Önizle
                </a>
                <a id="flpWaBtn" href="#" target="_blank"
                    style="flex:1;padding:10px;background:#dcfce7;color:#166534;border-radius:8px;text-align:center;text-decoration:none;font-size:13px;font-weight:700;">
                    💬 WhatsApp
                </a>
                <button onclick="flpYenile()" title="Yeni link oluştur"
                    style="padding:10px 14px;background:#fef3c7;color:#92400e;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:700;">
                    🔄 Yenile
                </button>
            </div>
            <div style="margin-top:12px;padding:10px;background:#fef2f2;border-radius:8px;font-size:12px;color:#6b7280;">
                ⚠️ Bu link ile faturanızı müşteriniz giriş yapmadan görüntüleyebilir. Linki güvenilir kişilerle paylaşın.
            </div>
        </div>
    </div>
</div>

<script>
let flp_faturaId   = null;
let flp_faturaNo   = '';
let flp_token      = '';
let flp_musteri_tel = '';

function flpGoster() { document.getElementById('flpModal').style.display = 'flex'; }
function flpGizle()  { document.getElementById('flpModal').style.display = 'none'; }

document.getElementById('flpModal').addEventListener('click', function(e) {
    if (e.target === this) flpGizle();
});

async function faturaLinkPaylasim(faturaId, faturaNo, mevcutToken, musteriTel) {
    flp_faturaId    = faturaId;
    flp_faturaNo    = faturaNo;
    flpFaturaNo     = faturaNo;
    flp_token       = mevcutToken;
    flp_musteri_tel = musteriTel;

    document.getElementById('flpFaturaNo').textContent = faturaNo;
    document.getElementById('flpLinkAlani').style.display = 'none';
    document.getElementById('flpYukleniyor').style.display = 'none';
    flpGoster();

    if (mevcutToken) {
        flpLinkGoster(mevcutToken);
    } else {
        await flpTokenOlustur();
    }
}

async function flpTokenOlustur() {
    document.getElementById('flpYukleniyor').style.display = 'block';
    document.getElementById('flpLinkAlani').style.display  = 'none';

    const fd = new FormData();
    fd.append('action', 'fatura_paylasim_token');
    fd.append('fatura_id', flp_faturaId);

    const r = await fetch('tahsilat_kontrol.php', { method: 'POST', body: fd })
        .then(x => x.json()).catch(e => ({ basari: false, mesaj: String(e) }));

    document.getElementById('flpYukleniyor').style.display = 'none';

    if (!r.basari) {
        alert('Hata: ' + r.mesaj);
        return;
    }
    flp_token = r.token;
    flpLinkGoster(r.token);
}

async function flpYenile() {
    flp_token = '';
    await flpTokenOlustur();
}

function flpLinkGoster(token) {
    const url = window.location.origin + '/FaturaApp/fatura_public.php?t=' + token;
    document.getElementById('flpLinkInput').value = url;
    document.getElementById('flpAcBtn').href = url;

    const tel = flp_musteri_tel ? flp_musteri_tel.replace(/\D/g,'') : '';
    const waMsg = encodeURIComponent('Sayın müşterimiz, ' + flpFaturaNo + ' numaralı faturanızı görüntülemek için: ' + url);
    document.getElementById('flpWaBtn').href = tel
        ? 'https://wa.me/' + tel + '?text=' + waMsg
        : 'https://wa.me/?text=' + waMsg;
    if (!tel) document.getElementById('flpWaBtn').style.opacity = '0.6';

    document.getElementById('flpLinkAlani').style.display = 'block';
}

function flpKopyala() {
    const url = document.getElementById('flpLinkInput').value;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('flpKopyalaBtn');
        btn.textContent = '✅ Kopyalandı!';
        btn.style.background = '#10b981';
        setTimeout(() => { btn.textContent = '📋 Kopyala'; btn.style.background = '#1e3a8a'; }, 2000);
    }).catch(() => {
        document.getElementById('flpLinkInput').select();
        document.execCommand('copy');
    });
}

// flpFaturaNo yerel değişkeni
let flpFaturaNo = '';
</script>
</body>
</html>