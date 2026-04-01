<?php
require_once 'db.php';
require_once 'auth.php';
yetkiKontrol('rapor');
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Raporlar</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sayfa-wrap { max-width: 1300px; margin: 0 auto; padding: 24px 16px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 20px; color: #1e3a8a; }
        .tab-bar { display: flex; gap: 4px; border-bottom: 2px solid #eee; margin-bottom: 24px; }
        .tab-btn {
            padding: 10px 22px; border: none; background: none;
            cursor: pointer; font-size: 13px; font-weight: 600;
            border-bottom: 2px solid transparent; margin-bottom: -2px; color: #6b7280;
        }
        .tab-btn.aktif { border-bottom-color: #3b82f6; color: #3b82f6; }
        .tab-icerik { display: none; }
        .tab-icerik.aktif { display: block; }
        .filtre-bar {
            display: flex; gap: 10px; flex-wrap: wrap;
            background: #fff; padding: 14px 16px;
            border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08);
            margin-bottom: 20px; align-items: center;
        }
        .filtre-bar input, .filtre-bar select {
            padding: 8px 12px; border: 1px solid #dde3f0;
            border-radius: 7px; font-size: 13px;
        }
        .btn { padding: 8px 18px; border: none; border-radius: 7px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-gray { background: #e5e7eb; color: #374151; }
        .ozet-kartlar { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 14px; margin-bottom: 20px; }
        .ozet-kart { background: #fff; border-radius: 10px; padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .ozet-kart .etiket { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
        .ozet-kart .deger { font-size: 20px; font-weight: 700; }
        .ozet-kart.mavi .deger { color: #3b82f6; }
        .ozet-kart.yesil .deger { color: #10b981; }
        .ozet-kart.sari .deger { color: #f59e0b; }
        .ozet-kart.kirmizi .deger { color: #ef4444; }
        .tablo-wrap { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th { background: #f8faff; padding: 11px 14px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
        tbody td { padding: 10px 14px; border-bottom: 1px solid #f3f4f6; }
        tbody tr:hover { background: #f8faff; }
        .text-right { text-align: right; }
        .bos-mesaj { text-align: center; padding: 40px; color: #9ca3af; }
        .bar-chart { margin-top: 16px; }
        .bar-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 13px; }
        .bar-label { width: 120px; text-align: right; color: #374151; flex-shrink: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bar-track { flex: 1; background: #f3f4f6; border-radius: 4px; height: 22px; overflow: hidden; }
        .bar-fill { height: 100%; background: #3b82f6; border-radius: 4px; transition: width .4s; display: flex; align-items: center; padding-left: 6px; font-size: 11px; color: #fff; font-weight: 600; white-space: nowrap; }
    </style>
</head>
<body>
<?php require_once 'menu.php'; ?>

<div class="sayfa-wrap">
    <h1>📈 Raporlar</h1>

    <div class="filtre-bar">
        <label style="font-size:13px;font-weight:600;">Tarih Aralığı:</label>
        <input type="date" id="tarihBas">
        <input type="date" id="tarihBit">
        <button class="btn btn-primary" onclick="raporlariYukle()">🔄 Yenile</button>
    </div>

    <div class="tab-bar">
        <button class="tab-btn aktif" onclick="tabAc('gelir',this)">💰 Gelir Özeti</button>
        <button class="tab-btn" onclick="tabAc('musteri',this)">👥 Müşteri Analizi</button>
        <button class="tab-btn" onclick="tabAc('urun',this)">📦 Ürün Analizi</button>
        <button class="tab-btn" onclick="tabAc('efatura',this)">📄 e-Fatura Durumu</button>
    </div>

    <div id="tab-gelir" class="tab-icerik aktif">
        <div class="ozet-kartlar">
            <div class="ozet-kart mavi"><div class="etiket">📄 Toplam Fatura</div><div class="deger" id="ozFaturaSayi">—</div></div>
            <div class="ozet-kart yesil"><div class="etiket">💵 Toplam Ciro</div><div class="deger" id="ozCiro">—</div></div>
            <div class="ozet-kart yesil"><div class="etiket">✅ Tahsil Edilen</div><div class="deger" id="ozTahsil">—</div></div>
            <div class="ozet-kart sari"><div class="etiket">⏳ Bekleyen</div><div class="deger" id="ozBekleyen">—</div></div>
            <div class="ozet-kart kirmizi"><div class="etiket">❌ İptal</div><div class="deger" id="ozIptal">—</div></div>
        </div>
        <div class="tablo-wrap">
            <table>
                <thead><tr>
                    <th>Ay</th>
                    <th class="text-right">Fatura Adedi</th>
                    <th class="text-right">Toplam Ciro</th>
                    <th class="text-right">Tahsil Edilen</th>
                    <th class="text-right">Kalan</th>
                </tr></thead>
                <tbody id="gelirTablo"><tr><td colspan="5" class="bos-mesaj">⏳ Yükleniyor...</td></tr></tbody>
            </table>
        </div>
    </div>

    <div id="tab-musteri" class="tab-icerik">
        <div class="tablo-wrap">
            <table>
                <thead><tr>
                    <th>#</th><th>Müşteri</th>
                    <th class="text-right">Fatura Adedi</th>
                    <th class="text-right">Toplam Tutar</th>
                    <th class="text-right">Tahsil Edilen</th>
                    <th class="text-right">Kalan Bakiye</th>
                </tr></thead>
                <tbody id="musteriRaporTablo"><tr><td colspan="6" class="bos-mesaj">⏳ Yükleniyor...</td></tr></tbody>
            </table>
        </div>
        <div class="bar-chart" id="musteriBarChart"></div>
    </div>

    <div id="tab-urun" class="tab-icerik">
        <div class="tablo-wrap">
            <table>
                <thead><tr>
                    <th>#</th><th>Ürün / Hizmet</th>
                    <th class="text-right">Satış Adedi</th>
                    <th class="text-right">Toplam Tutar</th>
                    <th class="text-right">KDV Tutarı</th>
                </tr></thead>
                <tbody id="urunRaporTablo"><tr><td colspan="5" class="bos-mesaj">⏳ Yükleniyor...</td></tr></tbody>
            </table>
        </div>
    </div>

    <div id="tab-efatura" class="tab-icerik">
        <div class="ozet-kartlar">
            <div class="ozet-kart yesil"><div class="etiket">✅ Gönderildi</div><div class="deger" id="efGonderildi">—</div></div>
            <div class="ozet-kart sari"><div class="etiket">⏳ Beklemede</div><div class="deger" id="efBeklemede">—</div></div>
            <div class="ozet-kart kirmizi"><div class="etiket">❌ Hata</div><div class="deger" id="efHata">—</div></div>
        </div>
        <div class="tablo-wrap">
            <table>
                <thead><tr>
                    <th>Fatura No</th><th>Müşteri</th><th>Tarih</th>
                    <th class="text-right">Tutar</th><th>ETTN</th><th>Durum</th>
                </tr></thead>
                <tbody id="efaturaTablo"><tr><td colspan="6" class="bos-mesaj">⏳ Yükleniyor...</td></tr></tbody>
            </table>
        </div>
    </div>

</div>

<script>
'use strict';
const para = v => parseFloat(v||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' ₺';
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function tabAc(id, btn) {
    document.querySelectorAll('.tab-icerik').forEach(t => t.classList.remove('aktif'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('aktif'));
    document.getElementById('tab-'+id).classList.add('aktif');
    btn.classList.add('aktif');
}

document.addEventListener('DOMContentLoaded', () => {
    const bugun = new Date().toISOString().split('T')[0];
    document.getElementById('tarihBas').value = bugun.substring(0,5)+'01-01';
    document.getElementById('tarihBit').value = bugun;
    raporlariYukle();
});

function raporlariYukle() {
    gelirRaporuYukle();
    musteriRaporuYukle();
    urunRaporuYukle();
    efaturaRaporuYukle();
}

function gelirRaporuYukle() {
    const bas = document.getElementById('tarihBas').value;
    const bit = document.getElementById('tarihBit').value;
    fetch('tahsilat_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'rapor_gelir',tarih_bas:bas,tarih_bit:bit}).toString()})
    .then(r=>r.json()).then(v=>{
        document.getElementById('ozFaturaSayi').textContent=(v.toplam_fatura||0)+' adet';
        document.getElementById('ozCiro').textContent=para(v.toplam_ciro||0);
        document.getElementById('ozTahsil').textContent=para(v.toplam_tahsil||0);
        document.getElementById('ozBekleyen').textContent=para(v.bekleyen||0);
        document.getElementById('ozIptal').textContent=(v.iptal_sayi||0)+' adet';
        const s=v.aylik||[];
        document.getElementById('gelirTablo').innerHTML=s.length?s.map(a=>`<tr><td><strong>${esc(a.ay)}</strong></td><td class="text-right">${a.adet} adet</td><td class="text-right">${para(a.ciro)}</td><td class="text-right" style="color:#10b981">${para(a.tahsil)}</td><td class="text-right" style="color:${parseFloat(a.kalan)>0?'#ef4444':'#10b981'}">${para(a.kalan)}</td></tr>`).join(''):'<tr><td colspan="5" class="bos-mesaj">📭 Bu dönemde veri yok.</td></tr>';
    }).catch(()=>{ document.getElementById('gelirTablo').innerHTML='<tr><td colspan="5" class="bos-mesaj">📭 Rapor verisi yüklenemedi.</td></tr>'; });
}

function musteriRaporuYukle() {
    const bas = document.getElementById('tarihBas').value;
    const bit = document.getElementById('tarihBit').value;
    fetch('tahsilat_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'rapor_musteri',tarih_bas:bas,tarih_bit:bit}).toString()})
    .then(r=>r.json()).then(v=>{
        const l=v.musteriler||[];
        document.getElementById('musteriRaporTablo').innerHTML=l.length?l.map((m,i)=>`<tr><td>${i+1}</td><td><strong>${esc(m.ad_soyad)}</strong></td><td class="text-right">${m.fatura_sayi} adet</td><td class="text-right">${para(m.toplam)}</td><td class="text-right" style="color:#10b981">${para(m.odenen)}</td><td class="text-right" style="color:${parseFloat(m.kalan)>0?'#ef4444':'#10b981'}">${para(m.kalan)}</td></tr>`).join(''):'<tr><td colspan="6" class="bos-mesaj">📭 Veri bulunamadı.</td></tr>';
        if(l.length){
            const max=Math.max(...l.map(m=>parseFloat(m.toplam||0)));
            document.getElementById('musteriBarChart').innerHTML='<h3 style="margin:20px 0 10px;font-size:15px;color:#374151;">En Çok Ciro Yapan Müşteriler</h3>'+l.slice(0,10).map(m=>{const p=max>0?(parseFloat(m.toplam)/max*100).toFixed(1):0;return`<div class="bar-row"><div class="bar-label" title="${esc(m.ad_soyad)}">${esc(m.ad_soyad)}</div><div class="bar-track"><div class="bar-fill" style="width:${p}%">${para(m.toplam)}</div></div></div>`}).join('');
        }
    }).catch(()=>{ document.getElementById('musteriRaporTablo').innerHTML='<tr><td colspan="6" class="bos-mesaj">📭 Rapor verisi yüklenemedi.</td></tr>'; });
}

function urunRaporuYukle() {
    const bas = document.getElementById('tarihBas').value;
    const bit = document.getElementById('tarihBit').value;
    fetch('tahsilat_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({action:'rapor_urun',tarih_bas:bas,tarih_bit:bit}).toString()})
    .then(r=>r.json()).then(v=>{
        const l=v.urunler||[];
        document.getElementById('urunRaporTablo').innerHTML=l.length?l.map((u,i)=>`<tr><td>${i+1}</td><td><strong>${esc(u.urun_adi)}</strong></td><td class="text-right">${parseFloat(u.toplam_miktar||0).toLocaleString('tr-TR')} adet</td><td class="text-right">${para(u.toplam_tutar)}</td><td class="text-right" style="color:#f59e0b">${para(u.kdv_tutar)}</td></tr>`).join(''):'<tr><td colspan="5" class="bos-mesaj">📭 Veri bulunamadı.</td></tr>';
    }).catch(()=>{ document.getElementById('urunRaporTablo').innerHTML='<tr><td colspan="5" class="bos-mesaj">📭 Rapor verisi yüklenemedi.</td></tr>'; });
}

function efaturaRaporuYukle() {
    fetch('tahsilat_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=rapor_efatura'})
    .then(r=>r.json()).then(v=>{
        document.getElementById('efGonderildi').textContent=(v.gonderildi||0)+' adet';
        document.getElementById('efBeklemede').textContent=(v.beklemede||0)+' adet';
        document.getElementById('efHata').textContent=(v.hata||0)+' adet';
        const l=v.faturalar||[];
        document.getElementById('efaturaTablo').innerHTML=l.length?l.map(f=>`<tr><td><strong>${esc(f.fatura_no)}</strong></td><td>${esc(f.musteri_adi||f.alici_unvan||'—')}</td><td>${f.tarih?f.tarih.split('-').reverse().join('.'):'—'}</td><td class="text-right">${para(f.toplam||0)}</td><td><code style="font-size:11px;">${esc(f.ettn||'—')}</code></td><td><span style="background:${f.durum==='GONDERILDI'?'#d1fae5':f.durum==='HATA'?'#fee2e2':'#fef3c7'};color:${f.durum==='GONDERILDI'?'#065f46':f.durum==='HATA'?'#991b1b':'#92400e'};padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">${esc(f.durum||'BEKLEMEDE')}</span></td></tr>`).join(''):'<tr><td colspan="6" class="bos-mesaj">📭 e-Fatura kaydı bulunamadı.</td></tr>';
    }).catch(()=>{ document.getElementById('efaturaTablo').innerHTML='<tr><td colspan="6" class="bos-mesaj">📭 Rapor verisi yüklenemedi.</td></tr>'; });
}
</script>
</div><!-- /sayfa-icerik -->
</body>
</html>
