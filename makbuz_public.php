<?php
/**
 * Tahsilat Makbuzu — Benzersiz token ile public erişim
 * URL: makbuz_public.php?t=<makbuz_token>
 */
$token = trim($_GET['t'] ?? '');
if (!$token || strlen($token) < 32) {
    http_response_code(404);
    die('<div style="font-family:sans-serif;text-align:center;padding:80px;color:#6b7280">
        <h2 style="color:#ef4444">Geçersiz Bağlantı</h2>
        <p>Bu makbuz linki geçersiz veya süresi dolmuş.</p>
    </div>');
}

require 'db.php';

$stmt = $pdo->prepare("
    SELECT t.*,
           m.ad_soyad AS musteri_adi, m.telefon AS musteri_tel,
           m.email AS musteri_email, m.adres AS musteri_adres,
           m.vergi_no, m.vergi_dairesi,
           f.fatura_no,
           oh.ad AS hesap_adi, oh.tip AS hesap_tip
    FROM tahsilatlar t
    JOIN musteriler m ON m.id = t.musteri_id
    LEFT JOIN faturalar f ON f.id = t.fatura_id
    LEFT JOIN odeme_hesaplari oh ON oh.id = t.hesap_id
    WHERE t.makbuz_token = ?
");
$stmt->execute([$token]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
    http_response_code(404);
    die('<div style="font-family:sans-serif;text-align:center;padding:80px;color:#6b7280">
        <h2 style="color:#ef4444">Makbuz Bulunamadı</h2>
        <p>Bu link geçersiz veya makbuz silinmiş olabilir.</p>
    </div>');
}

$config = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$firma = [
    'unvan' => $config['firma_unvan']   ?? 'FaturaApp',
    'adres' => $config['firma_adres']   ?? '',
    'tel'   => $config['firma_tel']     ?? $config['firma_telefon'] ?? '',
    'email' => $config['firma_email']   ?? '',
    'vkn'   => $config['firma_vkn']     ?? '',
    'banka' => $config['banka_hesaplari'] ?? [],
];

$turIkonlar = ['nakit'=>'💵','banka'=>'🏦','havale'=>'🏦','eft'=>'🏦','pos'=>'💳','kredi_karti'=>'💳','cek'=>'📝','senet'=>'📄','diger'=>'🔹'];
$turAdlar   = ['nakit'=>'Nakit','banka'=>'Banka/EFT','havale'=>'Havale','eft'=>'EFT','pos'=>'POS','kredi_karti'=>'Kredi Kartı','cek'=>'Çek','senet'=>'Senet','diger'=>'Diğer'];
$turIkon = $turIkonlar[$t['odeme_tipi']] ?? '💳';
$turAd   = $turAdlar[$t['odeme_tipi']]   ?? $t['odeme_tipi'];

$makbuzNo = 'MKB' . str_pad($t['id'], 8, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tahsilat Makbuzu <?= htmlspecialchars($makbuzNo) ?></title>
<style>
* { box-sizing: border-box; margin:0; padding:0; }
body { font-family:'Segoe UI',Arial,sans-serif; background:#f1f5f9; color:#1e293b; }
@media print {
    .toolbar, .alt-footer { display:none !important; }
    body { background:#fff; }
    .sayfa { box-shadow:none !important; max-width:100%; margin:0; border-radius:0; }
}
.toolbar {
    background:linear-gradient(135deg,#065f46,#10b981);
    padding:14px 20px; display:flex; align-items:center;
    justify-content:space-between; gap:12px; flex-wrap:wrap;
    box-shadow:0 3px 12px rgba(6,95,70,.3);
    position:sticky; top:0; z-index:100;
}
.toolbar-brand { color:#fff; font-size:15px; font-weight:700; }
.toolbar-brand span { opacity:.7; font-size:12px; font-weight:400; }
.toolbar-btns { display:flex; gap:8px; flex-wrap:wrap; }
.btn { padding:8px 18px; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all .2s; }
.btn-kopyala { background:#fff; color:#065f46; }
.btn-whatsapp { background:#25d366; color:#fff; }
.btn-yazdir { background:rgba(255,255,255,.2); color:#fff; border:1.5px solid rgba(255,255,255,.4); }

.sayfa { max-width:600px; margin:24px auto; background:#fff; border-radius:16px; padding:40px; box-shadow:0 4px 30px rgba(0,0,0,.1); }
@media (max-width:640px) { .sayfa { margin:0; border-radius:0; padding:20px; } }

/* MAKBUZ BAŞLIK */
.makbuz-top { text-align:center; border-bottom:3px double #10b981; padding-bottom:24px; margin-bottom:24px; }
.makbuz-ikon { font-size:48px; margin-bottom:8px; }
.makbuz-baslik { font-size:26px; font-weight:900; color:#065f46; letter-spacing:1px; }
.makbuz-no { font-size:14px; color:#6b7280; margin-top:4px; }
.firma-adi { font-size:18px; font-weight:800; color:#1e293b; margin-top:10px; }
.firma-alt { font-size:12px; color:#64748b; margin-top:4px; line-height:1.6; }

/* TUTAR KUTU */
.tutar-kutu {
    background:linear-gradient(135deg,#ecfdf5,#d1fae5);
    border:2px solid #6ee7b7;
    border-radius:14px; padding:24px; text-align:center;
    margin:20px 0;
}
.tutar-etiket { font-size:12px; font-weight:700; color:#065f46; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; }
.tutar-rakam { font-size:42px; font-weight:900; color:#065f46; }
.tutar-tur { font-size:15px; color:#059669; margin-top:6px; font-weight:600; }

/* BİLGİ SATIRI */
.bilgi-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; }
@media (max-width:480px) { .bilgi-grid { grid-template-columns:1fr; } }
.bilgi-kart { background:#f8fafc; border-radius:10px; padding:12px 14px; border-left:3px solid #3b82f6; }
.bilgi-kart.yesil { border-left-color:#10b981; }
.bilgi-kart.turuncu { border-left-color:#f59e0b; }
.bilgi-baslik { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; }
.bilgi-deger { font-size:14px; font-weight:700; color:#1e293b; }
.bilgi-alt { font-size:12px; color:#64748b; margin-top:2px; }

/* FATURA REFERANS */
.fatura-ref { background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:12px; }
.fatura-ref-ikon { font-size:24px; }
.fatura-ref-adi { font-size:13px; font-weight:700; color:#1e40af; }
.fatura-ref-alt { font-size:12px; color:#6b7280; }

/* BANKA */
.banka-kutu { background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:16px; margin-top:20px; }
.banka-baslik { font-size:13px; font-weight:700; color:#0369a1; margin-bottom:10px; }
.banka-satir { padding:8px 0; border-bottom:1px dotted #bae6fd; font-size:12px; line-height:1.6; }
.banka-satir:last-child { border-bottom:none; }

/* İMZA */
.imza-alan { display:flex; gap:30px; margin-top:30px; }
.imza-kutu { flex:1; text-align:center; }
.imza-cizgi { border-top:1px solid #94a3b8; padding-top:8px; font-size:12px; color:#6b7280; margin-top:40px; }

.alt-footer { text-align:center; font-size:12px; color:#94a3b8; margin:20px auto 10px; }
.toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(60px); background:#1e293b; color:#fff; padding:10px 22px; border-radius:30px; font-size:14px; font-weight:600; opacity:0; transition:all .3s; z-index:9999; pointer-events:none; }
.toast.goster { opacity:1; transform:translateX(-50%) translateY(0); }
</style>
</head>
<body>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-brand">
        <?= htmlspecialchars($firma['unvan']) ?>
        <span> — Tahsilat Makbuzu</span>
    </div>
    <div class="toolbar-btns">
        <button class="btn btn-kopyala" onclick="linkiKopyala()">📋 Linki Kopyala</button>
        <?php $tel = preg_replace('/\D/','',$t['musteri_tel']??''); if($tel): ?>
        <a class="btn btn-whatsapp"
           href="https://wa.me/<?= $tel ?>?text=<?= rawurlencode("Merhaba " . $t['musteri_adi'] . ", tahsilat makbuzunuzu görüntülemek için: " . "http://" . ($_SERVER['HTTP_HOST']??'localhost') . $_SERVER['REQUEST_URI']) ?>"
           target="_blank">💬 WhatsApp</a>
        <?php endif; ?>
        <button class="btn btn-yazdir" onclick="window.print()">🖨️ Yazdır / PDF</button>
    </div>
</div>

<!-- MAKBUZ -->
<div class="sayfa">
    <!-- BAŞLIK -->
    <div class="makbuz-top">
        <div class="makbuz-ikon">🧾</div>
        <div class="makbuz-baslik">TAHSİLAT MAKBUZU</div>
        <div class="makbuz-no"><?= htmlspecialchars($makbuzNo) ?></div>
        <div class="firma-adi"><?= htmlspecialchars($firma['unvan']) ?></div>
        <div class="firma-alt">
            <?php if($firma['adres']): ?><?= htmlspecialchars($firma['adres']) ?><br><?php endif; ?>
            <?php if($firma['tel']): ?>📞 <?= htmlspecialchars($firma['tel']) ?><?php endif; ?>
            <?php if($firma['email']): ?>  ✉️ <?= htmlspecialchars($firma['email']) ?><?php endif; ?>
            <?php if($firma['vkn']): ?><br>VKN: <?= htmlspecialchars($firma['vkn']) ?><?php endif; ?>
        </div>
    </div>

    <!-- TUTAR -->
    <div class="tutar-kutu">
        <div class="tutar-etiket">Tahsil Edilen Tutar</div>
        <div class="tutar-rakam"><?= number_format($t['tutar'],2,',','.') ?> ₺</div>
        <div class="tutar-tur"><?= $turIkon ?> <?= htmlspecialchars($turAd) ?>
            <?php if($t['hesap_adi']): ?> — <?= htmlspecialchars($t['hesap_adi']) ?><?php endif; ?>
        </div>
    </div>

    <!-- BİLGİ GRID -->
    <div class="bilgi-grid">
        <div class="bilgi-kart">
            <div class="bilgi-baslik">Müşteri</div>
            <div class="bilgi-deger"><?= htmlspecialchars($t['musteri_adi']) ?></div>
            <?php if($t['vergi_dairesi']||$t['vergi_no']): ?>
            <div class="bilgi-alt"><?= htmlspecialchars($t['vergi_dairesi']) ?> / <?= htmlspecialchars($t['vergi_no']) ?></div>
            <?php endif; ?>
            <?php if($t['musteri_tel']): ?><div class="bilgi-alt">📞 <?= htmlspecialchars($t['musteri_tel']) ?></div><?php endif; ?>
        </div>
        <div class="bilgi-kart yesil">
            <div class="bilgi-baslik">Ödeme Tarihi</div>
            <div class="bilgi-deger"><?= date('d.m.Y', strtotime($t['tarih'])) ?></div>
            <div class="bilgi-alt">Kayıt: <?= date('d.m.Y H:i', strtotime($t['olusturma'])) ?></div>
        </div>
        <div class="bilgi-kart turuncu">
            <div class="bilgi-baslik">Ödeme Yöntemi</div>
            <div class="bilgi-deger"><?= $turIkon ?> <?= htmlspecialchars($turAd) ?></div>
            <?php if($t['hesap_adi']): ?><div class="bilgi-alt"><?= htmlspecialchars($t['hesap_adi']) ?></div><?php endif; ?>
        </div>
        <div class="bilgi-kart">
            <div class="bilgi-baslik">Makbuz No</div>
            <div class="bilgi-deger" style="font-family:monospace"><?= htmlspecialchars($makbuzNo) ?></div>
        </div>
    </div>

    <!-- FATURA REF -->
    <?php if($t['fatura_no']): ?>
    <div class="fatura-ref">
        <div class="fatura-ref-ikon">🧾</div>
        <div>
            <div class="fatura-ref-adi">Fatura: <?= htmlspecialchars($t['fatura_no']) ?></div>
            <div class="fatura-ref-alt">Bu tahsilat ilgili faturaya uygulandı</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- AÇIKLAMA -->
    <?php if($t['aciklama']): ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:14px;">
        <strong style="display:block;margin-bottom:4px;color:#92400e;">📝 Açıklama</strong>
        <?= nl2br(htmlspecialchars($t['aciklama'])) ?>
    </div>
    <?php endif; ?>

    <!-- BANKA HESAPLARI -->
    <?php if(!empty($firma['banka'])): ?>
    <div class="banka-kutu">
        <div class="banka-baslik">🏦 Hesap Numaralarımız</div>
        <?php foreach($firma['banka'] as $bh): ?>
        <div class="banka-satir">
            <?php if(!empty($bh['banka'])): ?><strong><?= htmlspecialchars($bh['banka']) ?></strong><?php if(!empty($bh['sube'])): ?> — <?= htmlspecialchars($bh['sube']) ?><?php endif; ?><br><?php endif; ?>
            <?php if(!empty($bh['hesap_no'])): ?>Hesap No: <?= htmlspecialchars($bh['hesap_no']) ?><br><?php endif; ?>
            <?php if(!empty($bh['iban'])): ?>IBAN: <strong style="font-family:monospace"><?= htmlspecialchars($bh['iban']) ?></strong><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- İMZA -->
    <div class="imza-alan">
        <div class="imza-kutu"><div class="imza-cizgi">Tahsilat Eden / İmza</div></div>
        <div class="imza-kutu"><div class="imza-cizgi">Ödeyen / İmza</div></div>
    </div>
</div>

<div class="alt-footer">
    Bu makbuz <?= htmlspecialchars($firma['unvan']) ?> tarafından elektronik olarak düzenlenmiştir.
</div>

<div class="toast" id="toast">✅ Link panoya kopyalandı!</div>

<script>
function linkiKopyala() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        const t = document.getElementById('toast');
        t.classList.add('goster');
        setTimeout(() => t.classList.remove('goster'), 2500);
    }).catch(() => prompt('Linki kopyalayın:', url));
}
</script>
</body>
</html>
