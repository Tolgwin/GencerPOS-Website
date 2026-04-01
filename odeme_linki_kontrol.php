<?php
/**
 * Ödeme Linki Kontrol API
 * Fatura veya cari için Paynkolay ödeme linki oluşturur / listeler / siler.
 */
ob_start();
error_reporting(E_ERROR);
ini_set('display_errors', '0');

if (!session_id()) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/PaynkolayService.php';

header('Content-Type: application/json; charset=utf-8');

function cevap(bool $ok, string $mesaj = '', array $extra = []): void
{
    ob_end_clean();
    echo json_encode(array_merge(['basari' => $ok, 'mesaj' => $mesaj], $extra));
    exit;
}

// Beklenmedik hata → JSON olarak döndür
set_exception_handler(function(Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['basari' => false, 'mesaj' => 'Sunucu hatası: ' . $e->getMessage()]);
    exit;
});

// ── Config ─────────────────────────────────────────────────────────
$configFile = __DIR__ . '/config.php';
$config = file_exists($configFile) ? require $configFile : [];

$paynSx        = $config['payn_sx']         ?? '';
$paynSecret    = $config['payn_secret']      ?? '';
$paynTest      = $config['payn_test_modu']   ?? true;
$paynCallback  = $config['payn_callback_url'] ?? '';
$baseUrl       = rtrim($config['payn_base_url'] ?? 'http://localhost/FaturaApp', '/');

if (!$paynSx || !$paynSecret) {
    cevap(false, 'Paynkolay ayarları eksik. Lütfen Ayarlar > Paynkolay bölümünü doldurun.');
}

// ── DB: tablo kontrolü ─────────────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS paynkolay_linkler (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tip ENUM('fatura','cari') NOT NULL,
            referans_id INT NOT NULL,
            client_ref_code VARCHAR(100),
            link_url TEXT,
            tutar DECIMAL(15,2),
            durum ENUM('beklemede','odendi','iptal') DEFAULT 'beklemede',
            paynkolay_ref VARCHAR(100),
            olusturma DATETIME DEFAULT CURRENT_TIMESTAMP,
            odeme_tarihi DATETIME NULL,
            INDEX idx_ref (tip, referans_id),
            INDEX idx_client (client_ref_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) {}

$payn   = new PaynkolayService($paynSx, $paynSecret, $paynTest);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Link Oluştur ───────────────────────────────────────────────
    case 'link_olustur':
        $tip        = $_POST['tip']  ?? ''; // fatura | cari
        $refId      = (int)($_POST['ref_id'] ?? 0);
        $mod        = $_POST['mod']  ?? 'sms'; // sms | ortak
        if (!$tip || !$refId) cevap(false, 'Geçersiz parametre.');

        // Müşteri & tutar bilgilerini çek
        $musteriAd = $musteriEmail = $musteriTel = $faturaNoBilgi = '';
        $tutar     = 0.0;

        if ($tip === 'fatura') {
            $row = $pdo->prepare("
                SELECT f.id, f.fatura_no, f.toplam, f.odeme_durumu, f.notlar,
                       m.ad_soyad, m.email, m.telefon,
                       COALESCE((SELECT SUM(t.tutar) FROM tahsilatlar t WHERE t.fatura_id = f.id), 0) AS odenen,
                       f.toplam - COALESCE((SELECT SUM(t.tutar) FROM tahsilatlar t WHERE t.fatura_id = f.id), 0) AS kalan
                FROM faturalar f
                LEFT JOIN musteriler m ON m.id = f.musteri_id
                WHERE f.id = ?
            ");
            $row->execute([$refId]);
            $f = $row->fetch();
            if (!$f) cevap(false, 'Fatura bulunamadı.');
            $tutar       = (float)$f['kalan'];
            $musteriAd   = $f['ad_soyad']  ?? '';
            $musteriEmail= $f['email']     ?? '';
            $musteriTel  = $f['telefon']   ?? '';
            $faturaNoBilgi = $f['fatura_no'];
        } elseif ($tip === 'cari') {
            $row = $pdo->prepare("SELECT id, ad_soyad, email, telefon FROM musteriler WHERE id = ?");
            $row->execute([$refId]);
            $m = $row->fetch();
            if (!$m) cevap(false, 'Müşteri bulunamadı.');
            $tutar       = (float)($_POST['tutar'] ?? 0);
            $musteriAd   = $m['ad_soyad'];
            $musteriEmail= $m['email']    ?? '';
            $musteriTel  = $m['telefon']  ?? '';
        }

        if ($tutar <= 0) cevap(false, 'Tutar 0 veya negatif olamaz.');

        // Referans kodu: tip + refId + zaman
        $clientRef = strtoupper(substr($tip, 0, 1)) . $refId . '_' . time();

        $successUrl  = $baseUrl . '/odeme_callback.php?sonuc=basarili&ref=' . urlencode($clientRef);
        $failUrl     = $baseUrl . '/odeme_callback.php?sonuc=basarisiz&ref=' . urlencode($clientRef);
        $callbackUrl = $paynCallback ?: ($baseUrl . '/odeme_callback.php');

        $konu    = $tip === 'fatura' ? "Fatura Ödemesi: $faturaNoBilgi" : "Cari Tahsilat: $musteriAd";
        $aciklama= $tip === 'fatura' ? "Fatura No: $faturaNoBilgi — Tutar: " . number_format($tutar, 2, ',', '.') . ' ₺' : "Cari bakiye ödemesi";

        if ($mod === 'sms') {
            if (!$musteriTel) cevap(false, 'Müşteri telefon numarası tanımlı değil. Ortak link modunu deneyin.');
            $sonuc = $payn->linkGonder([
                'ad_soyad'     => $musteriAd,
                'email'        => $musteriEmail,
                'gsm'          => $musteriTel,
                'tutar'        => $tutar,
                'konu'         => $konu,
                'aciklama'     => $aciklama,
                'ref_kod'      => $clientRef,
                'callback_url' => $callbackUrl,
                'max_taksit'   => $_POST['max_taksit'] ?? '1',
                'link_bitis'   => date('Y-m-d', strtotime('+7 days')),
            ]);
        } else {
            $sonuc = $payn->ortakLinkOlustur([
                'ref_kod'     => $clientRef,
                'tutar'       => $tutar,
                'success_url' => $successUrl,
                'fail_url'    => $failUrl,
                'ad_soyad'    => $musteriAd,
                'email'       => $musteriEmail,
                'aciklama'    => $aciklama,
                'max_taksit'  => $_POST['max_taksit'] ?? '1',
            ]);
        }

        if (!$sonuc['basari']) {
            $kod  = $sonuc['hata_kodu']  ?? '';
            $acik = $sonuc['hata_mesaj'] ?? '';
            $raw  = $sonuc['raw']        ?? '';
            $msg  = $kod ? "[$kod] $acik" : json_encode($sonuc['veri'] ?: $raw);
            cevap(false, 'Paynkolay: ' . $msg, ['raw' => $raw]);
        }

        // Link URL'ini çıkar
        $veri    = $sonuc['veri'];
        $linkUrl = $veri['Url'] ?? $veri['url'] ?? $veri['URL'] ?? $veri['paymentUrl'] ?? $veri['PaymentUrl'] ?? '';
        if (!$linkUrl) {
            // Bazı yanıtlarda raw içinde geliyor
            if (preg_match('#https?://[^\s"]+#', $sonuc['raw'], $m)) $linkUrl = $m[0];
        }

        // DB'ye kaydet
        $ins = $pdo->prepare("
            INSERT INTO paynkolay_linkler (tip, referans_id, client_ref_code, link_url, tutar)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([$tip, $refId, $clientRef, $linkUrl, $tutar]);
        $linkId = $pdo->lastInsertId();

        cevap(true, 'Ödeme linki oluşturuldu.', [
            'link_id'      => $linkId,
            'link_url'     => $linkUrl,
            'client_ref'   => $clientRef,
            'musteri_ad'   => $musteriAd,
            'musteri_tel'  => $musteriTel,
            'musteri_email'=> $musteriEmail,
            'tutar'        => $tutar,
            'paynkolay_raw'=> $veri,
        ]);
        break;

    // ── Listele ────────────────────────────────────────────────────
    case 'listele':
        $tip   = $_GET['tip']    ?? $_POST['tip']    ?? '';
        $refId = (int)($_GET['ref_id'] ?? $_POST['ref_id'] ?? 0);
        $stmt  = $pdo->prepare("SELECT * FROM paynkolay_linkler WHERE tip=? AND referans_id=? ORDER BY olusturma DESC");
        $stmt->execute([$tip, $refId]);
        cevap(true, '', ['linkler' => $stmt->fetchAll()]);
        break;

    // ── Link Sil ───────────────────────────────────────────────────
    case 'link_sil':
        $id = (int)($_POST['id'] ?? 0);
        $row = $pdo->prepare("SELECT * FROM paynkolay_linkler WHERE id=?");
        $row->execute([$id]);
        $link = $row->fetch();
        if (!$link) cevap(false, 'Link bulunamadı.');

        // Paynkolay'dan sil (eğer paynkolay ref varsa)
        if ($link['paynkolay_ref']) {
            $payn->linkSil($link['paynkolay_ref']);
        }
        $pdo->prepare("UPDATE paynkolay_linkler SET durum='iptal' WHERE id=?")->execute([$id]);
        cevap(true, 'Link iptal edildi.');
        break;

    default:
        cevap(false, 'Geçersiz action.');
}
