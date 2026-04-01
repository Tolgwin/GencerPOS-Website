<?php
require_once 'db.php';
require_once 'auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

function cevap($ok, $msg = '', $extra = []) {
    echo json_encode(array_merge(['basari' => $ok, 'mesaj' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// ── LİSTE ─────────────────────────────────────────────
if ($action === 'listele') {
    $stmt = $pdo->query("SELECT * FROM personeller ORDER BY ad_soyad ASC");
    cevap(true, '', ['personeller' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── EKLE ──────────────────────────────────────────────
if ($action === 'ekle') {
    $ad      = trim($_POST['ad_soyad'] ?? '');
    $tel     = trim($_POST['telefon'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $gorev   = trim($_POST['gorev'] ?? '');
    $prim    = floatval($_POST['prim_orani'] ?? 0);
    if (!$ad) cevap(false, 'Ad Soyad zorunlu.');
    $pdo->prepare("INSERT INTO personeller (ad_soyad,telefon,email,gorev,prim_orani) VALUES (?,?,?,?,?)")
        ->execute([$ad, $tel, $email, $gorev, $prim]);
    cevap(true, 'Personel eklendi.', ['id' => $pdo->lastInsertId()]);
}

// ── GÜNCELLE ──────────────────────────────────────────
if ($action === 'guncelle') {
    $id    = (int)($_POST['id'] ?? 0);
    $ad    = trim($_POST['ad_soyad'] ?? '');
    $tel   = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gorev = trim($_POST['gorev'] ?? '');
    $prim  = floatval($_POST['prim_orani'] ?? 0);
    $aktif = (int)($_POST['aktif'] ?? 1);
    if (!$id || !$ad) cevap(false, 'Geçersiz veri.');
    $pdo->prepare("UPDATE personeller SET ad_soyad=?,telefon=?,email=?,gorev=?,prim_orani=?,aktif=? WHERE id=?")
        ->execute([$ad, $tel, $email, $gorev, $prim, $aktif, $id]);
    cevap(true, 'Personel güncellendi.');
}

// ── SİL ───────────────────────────────────────────────
if ($action === 'sil') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) cevap(false, 'ID gerekli.');
    $pdo->prepare("DELETE FROM personeller WHERE id=?")->execute([$id]);
    cevap(true, 'Personel silindi.');
}

// ── PERSONELİN FATURALARI ─────────────────────────────
if ($action === 'faturalar') {
    $personelId = (int)($_GET['personel_id'] ?? $_POST['personel_id'] ?? 0);
    if (!$personelId) cevap(false, 'Personel ID gerekli.');
    $stmt = $pdo->prepare("
        SELECT f.id, f.fatura_no, f.tarih, f.toplam, f.odeme_durumu,
               m.ad_soyad AS musteri_adi,
               COALESCE((SELECT SUM(t.tutar) FROM tahsilatlar t WHERE t.fatura_id=f.id),0) AS odenen
        FROM faturalar f
        JOIN musteriler m ON f.musteri_id = m.id
        WHERE f.personel_id = ?
        ORDER BY f.tarih DESC LIMIT 100
    ");
    $stmt->execute([$personelId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['toplam'] = (float)$r['toplam'];
        $r['odenen'] = (float)$r['odenen'];
        $r['kalan']  = round($r['toplam'] - $r['odenen'], 2);
    }
    cevap(true, '', ['faturalar' => $rows]);
}

// ── PRİMLER ───────────────────────────────────────────
if ($action === 'prim_listele') {
    $personelId = (int)($_POST['personel_id'] ?? 0);
    if (!$personelId) cevap(false, 'Personel ID gerekli.');
    $stmt = $pdo->prepare("
        SELECT pp.*, f.fatura_no
        FROM personel_prim pp
        LEFT JOIN faturalar f ON pp.fatura_id = f.id
        WHERE pp.personel_id = ?
        ORDER BY pp.olusturma DESC
    ");
    $stmt->execute([$personelId]);
    cevap(true, '', ['primler' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'prim_hesapla') {
    $personelId   = (int)($_POST['personel_id'] ?? 0);
    $faturaId     = (int)($_POST['fatura_id'] ?? 0);
    $primOrani    = floatval($_POST['prim_orani'] ?? 0);
    $aciklama     = trim($_POST['aciklama'] ?? '');
    if (!$personelId) cevap(false, 'Personel ID gerekli.');

    $faturaTutari = 0;
    if ($faturaId) {
        $row = $pdo->prepare("SELECT toplam FROM faturalar WHERE id=?");
        $row->execute([$faturaId]);
        $fRow = $row->fetch();
        $faturaTutari = $fRow ? floatval($fRow['toplam']) : 0;
    }
    if ($primOrani <= 0) {
        $pRow = $pdo->prepare("SELECT prim_orani FROM personeller WHERE id=?");
        $pRow->execute([$personelId]);
        $pData = $pRow->fetch();
        $primOrani = $pData ? floatval($pData['prim_orani']) : 0;
    }
    $primTutari = round($faturaTutari * $primOrani / 100, 2);
    if (!$faturaId && isset($_POST['prim_tutari'])) {
        $primTutari = floatval($_POST['prim_tutari']);
    }
    $pdo->prepare("INSERT INTO personel_prim (personel_id,fatura_id,fatura_tutari,prim_orani,prim_tutari,aciklama) VALUES (?,?,?,?,?,?)")
        ->execute([$personelId, $faturaId ?: null, $faturaTutari ?: null, $primOrani, $primTutari, $aciklama]);
    cevap(true, 'Prim kaydedildi.', ['prim_tutari' => $primTutari]);
}

if ($action === 'prim_ode') {
    $primId   = (int)($_POST['prim_id'] ?? 0);
    $hesapId  = (int)($_POST['hesap_id'] ?? 0);
    $tarih    = $_POST['tarih'] ?? date('Y-m-d');
    if (!$primId) cevap(false, 'Prim ID gerekli.');

    $stmt = $pdo->prepare("SELECT prim_tutari, odeme_durumu FROM personel_prim WHERE id=?");
    $stmt->execute([$primId]);
    $prim = $stmt->fetch();
    if (!$prim) cevap(false, 'Prim bulunamadı.');
    if ($prim['odeme_durumu'] === 'odendi') cevap(false, 'Bu prim zaten ödendi.');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE personel_prim SET odeme_durumu='odendi', odeme_tarihi=? WHERE id=?")
            ->execute([$tarih, $primId]);
        if ($hesapId) {
            $pdo->prepare("INSERT INTO hesap_hareketleri (hesap_id, tip, tutar, aciklama, tarih) VALUES (?,?,?,?,?)")
                ->execute([$hesapId, 'cikis', $prim['prim_tutari'], 'Personel prim ödemesi', $tarih]);
        }
        $pdo->commit();
        cevap(true, 'Prim ödendi.');
    } catch (Exception $e) {
        $pdo->rollBack();
        cevap(false, 'Hata: ' . $e->getMessage());
    }
}

// ── AVANS / BORÇ ──────────────────────────────────────
if ($action === 'avans_listele') {
    $personelId = (int)($_POST['personel_id'] ?? 0);
    if (!$personelId) cevap(false, 'Personel ID gerekli.');
    $stmt = $pdo->prepare("SELECT pa.*, oh.ad AS hesap_adi FROM personel_avans pa LEFT JOIN odeme_hesaplari oh ON pa.hesap_id=oh.id WHERE pa.personel_id=? ORDER BY pa.tarih DESC");
    $stmt->execute([$personelId]);
    cevap(true, '', ['avanslar' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'avans_ekle') {
    $personelId = (int)($_POST['personel_id'] ?? 0);
    $tip        = $_POST['tip'] ?? 'avans';
    $tutar      = floatval($_POST['tutar'] ?? 0);
    $tarih      = $_POST['tarih'] ?? date('Y-m-d');
    $aciklama   = trim($_POST['aciklama'] ?? '');
    $hesapId    = (int)($_POST['hesap_id'] ?? 0) ?: null;
    if (!$personelId || $tutar <= 0) cevap(false, 'Geçersiz veri.');

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO personel_avans (personel_id,tip,tutar,tarih,aciklama,hesap_id) VALUES (?,?,?,?,?,?)")
            ->execute([$personelId, $tip, $tutar, $tarih, $aciklama, $hesapId]);
        if ($hesapId) {
            $kasaTip = ($tip === 'avans') ? 'cikis' : 'giris';
            $pdo->prepare("INSERT INTO hesap_hareketleri (hesap_id, tip, tutar, aciklama, tarih) VALUES (?,?,?,?,?)")
                ->execute([$hesapId, $kasaTip, $tutar, ($tip === 'avans' ? 'Personel avans' : 'Personel borç tahsilatı'), $tarih]);
        }
        $pdo->commit();
        cevap(true, ($tip === 'avans' ? 'Avans' : 'Borç') . ' kaydedildi.');
    } catch (Exception $e) {
        $pdo->rollBack();
        cevap(false, 'Hata: ' . $e->getMessage());
    }
}

// ── ÖZET ──────────────────────────────────────────────
if ($action === 'ozet') {
    $personelId = (int)($_POST['personel_id'] ?? 0);
    if (!$personelId) cevap(false, 'Personel ID gerekli.');

    $stmt = $pdo->prepare("SELECT COUNT(*) AS fatura_adet, COALESCE(SUM(toplam),0) AS toplam_ciro FROM faturalar WHERE personel_id=? AND durum!='iptal'");
    $stmt->execute([$personelId]);
    $ciro = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN odeme_durumu='beklemede' THEN prim_tutari END),0) AS bekleyen_prim, COALESCE(SUM(CASE WHEN odeme_durumu='odendi' THEN prim_tutari END),0) AS odenen_prim FROM personel_prim WHERE personel_id=?");
    $stmt2->execute([$personelId]);
    $prim = $stmt2->fetch(PDO::FETCH_ASSOC);

    $stmt3 = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tip='avans' THEN tutar END),0) AS toplam_avans, COALESCE(SUM(CASE WHEN tip='borc' THEN tutar END),0) AS toplam_borc FROM personel_avans WHERE personel_id=?");
    $stmt3->execute([$personelId]);
    $avans = $stmt3->fetch(PDO::FETCH_ASSOC);

    cevap(true, '', [
        'fatura_adet'    => (int)$ciro['fatura_adet'],
        'toplam_ciro'    => (float)$ciro['toplam_ciro'],
        'bekleyen_prim'  => (float)$prim['bekleyen_prim'],
        'odenen_prim'    => (float)$prim['odenen_prim'],
        'toplam_avans'   => (float)$avans['toplam_avans'],
        'toplam_borc'    => (float)$avans['toplam_borc'],
    ]);
}

// ── ÜRÜN PRİM LİSTESİ ─────────────────────────────────
if ($action === 'urun_prim_listele') {
    $pid = (int)($_POST['personel_id'] ?? $_GET['personel_id'] ?? 0);
    if (!$pid) cevap(false, 'Personel ID gerekli.');
    $rows = $pdo->prepare("
        SELECT p.*, u.ad AS urun_adi, u.urun_kodu, u.satis_fiyati
        FROM personel_urun_prim p
        JOIN urunler u ON p.urun_id = u.id
        WHERE p.personel_id = ?
        ORDER BY u.ad ASC
    ");
    $rows->execute([$pid]);
    cevap(true, '', ['primler' => $rows->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── ÜRÜN PRİM EKLE / GÜNCELLE ─────────────────────────
if ($action === 'urun_prim_kaydet') {
    $pid   = (int)($_POST['personel_id'] ?? 0);
    $uid   = (int)($_POST['urun_id'] ?? 0);
    $oranRaw  = trim($_POST['prim_orani'] ?? '');
    $sabitRaw = trim($_POST['prim_sabit_tutar'] ?? '');
    $oran  = ($oranRaw  !== '') ? floatval($oranRaw)  : null;
    $sabit = ($sabitRaw !== '') ? floatval($sabitRaw) : null;
    if (!$pid || !$uid) cevap(false, 'Personel ve ürün zorunlu.');
    if ($oran === null && $sabit === null) cevap(false, 'Oran veya sabit tutar girilmeli.');
    $pdo->prepare("
        INSERT INTO personel_urun_prim (personel_id, urun_id, prim_orani, prim_sabit_tutar)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE prim_orani=VALUES(prim_orani), prim_sabit_tutar=VALUES(prim_sabit_tutar)
    ")->execute([$pid, $uid, $oran, $sabit]);
    cevap(true, 'Kaydedildi.');
}

// ── ÜRÜN PRİM SİL ─────────────────────────────────────
if ($action === 'urun_prim_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) cevap(false, 'ID gerekli.');
    $pdo->prepare("DELETE FROM personel_urun_prim WHERE id=?")->execute([$id]);
    cevap(true, 'Silindi.');
}

cevap(false, 'Geçersiz işlem.');
