<?php
// fatura_gonder_kontrol.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/EFaturaService.php';
require_once __DIR__ . '/UBLBuilder.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Hata yakalayıcı ─────────────────────────────────────────────
set_exception_handler(function (Throwable $e) {
    echo json_encode(['basari' => false, 'mesaj' => $e->getMessage()]);
    exit;
});

// ════════════════════════════════════════════════════════════════
// ACTION: alici_kontrol — VKN/TCKN e-Fatura mükellefi mi?
// ════════════════════════════════════════════════════════════════
if ($action === 'alici_kontrol') {
    $vkn = trim($_POST['vkn'] ?? '');

    if (strlen($vkn) < 10) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz VKN/TCKN.']);
        exit;
    }

    $config = efatura_config();

    try {
        $service = new EFaturaService($config);
        $service->login();
        $sonuc = $service->kayitliKullaniciSorgula($vkn);
        $service->logout();

        echo json_encode([
            'basari'  => true,
            'kayitli' => $sonuc['kayitli'],
            'unvan'   => $sonuc['unvan'] ?? '',
        ]);
    } catch (Throwable $e) {
        // Servis hatası → yine de devam et, sadece bilgi ver
        echo json_encode([
            'basari'  => true,
            'kayitli' => false,
            'unvan'   => '',
        ]);
    }
    exit;
}

// ════════════════════════════════════════════════════════════════
// ACTION: fatura_kaydet — Sadece DB'ye kaydet (taslak)
// ════════════════════════════════════════════════════════════════
if ($action === 'fatura_kaydet') {
    $data    = postVeri();
    $satirlar = json_decode($_POST['satirlar'] ?? '[]', true);

    validasyonKontrol($data, $satirlar);

    $id = faturaDBKaydet($pdo, $data, $satirlar, 'TASLAK', null);

    echo json_encode([
        'basari'    => true,
        'fatura_no' => $data['fatura_no'],
        'id'        => $id,
    ]);
    exit;
}

// ════════════════════════════════════════════════════════════════
// ACTION: fatura_kaydet_gonder — DB'ye kaydet + QNB'ye gönder
// ════════════════════════════════════════════════════════════════
if ($action === 'fatura_kaydet_gonder') {
    $data    = postVeri();
    $satirlar = json_decode($_POST['satirlar'] ?? '[]', true);

    validasyonKontrol($data, $satirlar);

    $config = efatura_config();

    // UBL XML üret
    $ublData = [
        'belge_no'       => $data['fatura_no'],
        'tarih'          => $data['tarih'],
        'tip'            => $data['tip'],
        'para_birimi'    => $data['para_birimi'],
        'gonderen_vkn'   => $config['username'],
        'gonderen_unvan' => $config['gonderen_unvan'],
        'alici_vkn'      => $data['alici_vkn'],
        'alici_unvan'    => $data['alici_unvan'],
        'alici_adres'    => $data['alici_adres'],
        'not'            => $data['not'],
        'matrah'         => $data['matrah'],
        'kdv'            => $data['kdv'],
        'toplam'         => $data['toplam'],
        'satirlar'       => $satirlar,
    ];

    $xmlContent = UBLBuilder::build($ublData);

    // E-Fatura gönder
    $service = new EFaturaService($config);
    $service->login();
    $ettn = $service->belgeGonder($xmlContent, $data['fatura_no']);
    $service->logout();

    // DB'ye kaydet
    $id = faturaDBKaydet($pdo, $data, $satirlar, 'GONDERILDI', $ettn);

    echo json_encode([
        'basari'    => true,
        'fatura_no' => $data['fatura_no'],
        'ettn'      => $ettn,
        'id'        => $id,
    ]);
    exit;
}

// Bilinmeyen action
echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz işlem.']);
exit;

// ════════════════════════════════════════════════════════════════
// YARDIMCI FONKSİYONLAR
// ════════════════════════════════════════════════════════════════

function postVeri(): array
{
    return [
        'fatura_no'   => trim($_POST['fatura_no']   ?? ''),
        'tarih'       => trim($_POST['tarih']        ?? ''),
        'tip'         => trim($_POST['tip']          ?? 'SATIS'),
        'para_birimi' => trim($_POST['para_birimi']  ?? 'TRY'),
        'alici_vkn'   => trim($_POST['alici_vkn']   ?? ''),
        'alici_unvan' => trim($_POST['alici_unvan']  ?? ''),
        'alici_email' => trim($_POST['alici_email']  ?? ''),
        'alici_tel'   => trim($_POST['alici_tel']    ?? ''),
        'alici_adres' => trim($_POST['alici_adres']  ?? ''),
        'not'         => trim($_POST['not']          ?? ''),
        'matrah'      => (float) ($_POST['matrah']   ?? 0),
        'kdv'         => (float) ($_POST['kdv']      ?? 0),
        'toplam'      => (float) ($_POST['toplam']   ?? 0),
    ];
}

function validasyonKontrol(array $data, array $satirlar): void
{
    $hatalar = [];
    if (!$data['fatura_no'])   $hatalar[] = 'Fatura No zorunludur.';
    if (!$data['tarih'])       $hatalar[] = 'Tarih zorunludur.';
    if (!$data['alici_vkn'])   $hatalar[] = 'Alıcı VKN/TCKN zorunludur.';
    if (!$data['alici_unvan']) $hatalar[] = 'Alıcı ünvanı zorunludur.';
    if (empty($satirlar))      $hatalar[] = 'En az bir ürün satırı gereklidir.';

    if ($hatalar) {
        echo json_encode(['basari' => false, 'mesaj' => implode(' | ', $hatalar)]);
        exit;
    }
}

function faturaDBKaydet(
    PDO $pdo,
    array $data,
    array $satirlar,
    string $durum,
    ?string $ettn
): int {
    $pdo->beginTransaction();

    try {
        // Ana fatura kaydı
        $stmt = $pdo->prepare("
            INSERT INTO faturalar
                (fatura_no, tarih, tip, para_birimi,
                 alici_vkn, alici_unvan, alici_email, alici_tel, alici_adres,
                 not_alani, matrah, kdv, toplam, ettn, durum, olusturma_tarihi)
            VALUES
                (:fatura_no, :tarih, :tip, :para_birimi,
                 :alici_vkn, :alici_unvan, :alici_email, :alici_tel, :alici_adres,
                 :not_alani, :matrah, :kdv, :toplam, :ettn, :durum, NOW())
        ");

        $stmt->execute([
            ':fatura_no'   => $data['fatura_no'],
            ':tarih'       => $data['tarih'],
            ':tip'         => $data['tip'],
            ':para_birimi' => $data['para_birimi'],
            ':alici_vkn'   => $data['alici_vkn'],
            ':alici_unvan' => $data['alici_unvan'],
            ':alici_email' => $data['alici_email'],
            ':alici_tel'   => $data['alici_tel'],
            ':alici_adres' => $data['alici_adres'],
            ':not_alani'   => $data['not'],
            ':matrah'      => $data['matrah'],
            ':kdv'         => $data['kdv'],
            ':toplam'      => $data['toplam'],
            ':ettn'        => $ettn,
            ':durum'       => $durum,
        ]);

        $faturaId = (int) $pdo->lastInsertId();

        // Satır kayıtları
        $satirStmt = $pdo->prepare("
            INSERT INTO fatura_satirlari
                (fatura_id, urun_id, urun_kodu, aciklama, birim,
                 miktar, birim_fiyat, kdv_orani, kdv_tutar, matrah, toplam)
            VALUES
                (:fatura_id, :urun_id, :urun_kodu, :aciklama, :birim,
                 :miktar, :birim_fiyat, :kdv_orani, :kdv_tutar, :matrah, :toplam)
        ");

        foreach ($satirlar as $s) {
            $satirStmt->execute([
                ':fatura_id'   => $faturaId,
                ':urun_id'     => $s['urun_id']     ?? null,
                ':urun_kodu'   => $s['urun_kodu']   ?? '',
                ':aciklama'    => $s['aciklama']     ?? '',
                ':birim'       => $s['birim']        ?? 'C62',
                ':miktar'      => $s['miktar']       ?? 0,
                ':birim_fiyat' => $s['birim_fiyat']  ?? 0,
                ':kdv_orani'   => $s['kdv_orani']    ?? 0,
                ':kdv_tutar'   => $s['kdv_tutar']    ?? 0,
                ':matrah'      => $s['matrah']        ?? 0,
                ':toplam'      => $s['toplam']        ?? 0,
            ]);
        }

        $pdo->commit();
        return $faturaId;

    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function efatura_config(): array
{
    return [
        'username'        => '3930311899',
        'password'        => 'Tolga3583.',
        'gonderen_unvan'  => 'Gönderen Firma A.Ş.',
        'user_wsdl'       => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl',
        'connector_url'   => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService',
    ];
}
