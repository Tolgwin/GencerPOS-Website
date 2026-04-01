<?php
require_once 'db.php';
require_once 'auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

// ════════════════════════════════════════════════════════
// HESAP LİSTESİ + BAKİYE
// ════════════════════════════════════════════════════════
if ($action === 'hesap_liste') {
    $stmt = $pdo->query("
        SELECT
            h.*,
            h.baslangic_bak + COALESCE((
                SELECT
                    SUM(CASE WHEN tip IN ('giris','transfer_giris') THEN tutar ELSE 0 END)
                    - SUM(CASE WHEN tip IN ('cikis','transfer_cikis','cari_odeme') THEN tutar ELSE 0 END)
                FROM hesap_hareketleri hh
                WHERE hh.hesap_id = h.id
            ), 0) AS bakiye
        FROM odeme_hesaplari h
        WHERE h.aktif = 1
        ORDER BY h.id ASC
    ");
    $hesaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($hesaplar as &$h) {
        $h['bakiye'] = (float) $h['bakiye'];
        $h['baslangic_bak'] = (float) $h['baslangic_bak'];
    }
    unset($h);

    echo json_encode(
        ['basari' => true, 'hesaplar' => $hesaplar],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// ════════════════════════════════════════════════════════
// HESAP HAREKETLERİ
// ════════════════════════════════════════════════════════
if ($action === 'hesap_hareketler') {
    $hesap_id = (int) ($_POST['hesap_id'] ?? 0);
    $tarih_bas = trim($_POST['tarih_bas'] ?? '');
    $tarih_bit = trim($_POST['tarih_bit'] ?? '');

    if (!$hesap_id) {
        echo json_encode(['basari' => false, 'mesaj' => 'Hesap ID gerekli.']);
        exit;
    }

    // Başlangıç bakiyesi
    $stmt = $pdo->prepare("SELECT baslangic_bak FROM odeme_hesaplari WHERE id = ?");
    $stmt->execute([$hesap_id]);
    $baslangicBak = (float) ($stmt->fetchColumn() ?? 0);

    $where = ['hh.hesap_id = ?'];
    $params = [$hesap_id];

    if ($tarih_bas) {
        $where[] = 'hh.tarih >= ?';
        $params[] = $tarih_bas;
    }
    if ($tarih_bit) {
        $where[] = 'hh.tarih <= ?';
        $params[] = $tarih_bit;
    }

    $whereSQL = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT
            hh.*,
            CASE
                WHEN hh.referans_tip = 'transfer' AND hh.tip = 'transfer_giris' THEN
                    CONCAT('Transfer Geldi ← ',
                        (SELECT ad FROM odeme_hesaplari
                         WHERE id = (
                             SELECT kaynak_id FROM hesap_transferler
                             WHERE id = hh.referans_id
                         )))
                WHEN hh.referans_tip = 'transfer' AND hh.tip = 'transfer_cikis' THEN
                    CONCAT('Transfer Gitti → ',
                        (SELECT ad FROM odeme_hesaplari
                         WHERE id = (
                             SELECT hedef_id FROM hesap_transferler
                             WHERE id = hh.referans_id
                         )))
                ELSE hh.aciklama
            END AS aciklama_detay
        FROM hesap_hareketleri hh
        WHERE {$whereSQL}
        ORDER BY hh.tarih ASC, hh.id ASC
    ");
    $stmt->execute($params);
    $hareketler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kümülatif bakiye hesapla
    $bakiye = $baslangicBak;
    foreach ($hareketler as &$h) {
        $h['tutar'] = (float) $h['tutar'];
        if (in_array($h['tip'], ['giris', 'transfer_giris'])) {
            $bakiye += $h['tutar'];
        } else {
            $bakiye -= $h['tutar'];
        }
        $h['bakiye'] = $bakiye;
    }
    unset($h);

    // Özet
    $stmtOzet = $pdo->prepare("
        SELECT
            SUM(CASE WHEN tip IN ('giris','transfer_giris')  THEN tutar ELSE 0 END) AS toplam_giris,
            SUM(CASE WHEN tip IN ('cikis','transfer_cikis') THEN tutar ELSE 0 END) AS toplam_cikis
        FROM hesap_hareketleri
        WHERE hesap_id = ?
    ");
    $stmtOzet->execute([$hesap_id]);
    $ozet = $stmtOzet->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'basari' => true,
        'hareketler' => $hareketler,
        'baslangic_bak' => $baslangicBak,
        'toplam_giris' => (float) $ozet['toplam_giris'],
        'toplam_cikis' => (float) $ozet['toplam_cikis'],
        'net_bakiye' => $baslangicBak
            + (float) $ozet['toplam_giris']
            - (float) $ozet['toplam_cikis'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════
// HESAP EKLE / GÜNCELLE
// ════════════════════════════════════════════════════════
if ($action === 'hesap_kaydet') {
    $id = (int) ($_POST['id'] ?? 0);
    $ad = trim($_POST['ad'] ?? '');
    $tip = trim($_POST['tip'] ?? 'nakit');
    $aciklama = trim($_POST['aciklama'] ?? '');
    $baslangic = (float) ($_POST['baslangic_bak'] ?? 0);
    $renk = trim($_POST['renk'] ?? '#3b82f6');
    $ikon = trim($_POST['ikon'] ?? '💵');

    if (!$ad) {
        echo json_encode(['basari' => false, 'mesaj' => 'Hesap adı gerekli.']);
        exit;
    }

    $izinliTip = ['nakit', 'banka', 'pos', 'diger'];
    if (!in_array($tip, $izinliTip))
        $tip = 'nakit';

    if ($id) {
        $pdo->prepare("
            UPDATE odeme_hesaplari
            SET ad=?, tip=?, aciklama=?, renk=?, ikon=?
            WHERE id=?
        ")->execute([$ad, $tip, $aciklama, $renk, $ikon, $id]);
        echo json_encode(
            ['basari' => true, 'mesaj' => 'Hesap güncellendi.'],
            JSON_UNESCAPED_UNICODE
        );
    } else {
        $pdo->prepare("
            INSERT INTO odeme_hesaplari (ad, tip, aciklama, baslangic_bak, renk, ikon)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$ad, $tip, $aciklama, $baslangic, $renk, $ikon]);
        echo json_encode([
            'basari' => true,
            'mesaj' => 'Hesap oluşturuldu.',
            'id' => $pdo->lastInsertId()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ════════════════════════════════════════════════════════
// HESAP SİL
// ════════════════════════════════════════════════════════
if ($action === 'hesap_sil') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['basari' => false, 'mesaj' => 'ID gerekli.']);
        exit;
    }
    $pdo->prepare("UPDATE odeme_hesaplari SET aktif=0 WHERE id=?")
        ->execute([$id]);
    echo json_encode(
        ['basari' => true, 'mesaj' => 'Hesap silindi.'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// ════════════════════════════════════════════════════════
// MANUEL HAREKET EKLE
// ════════════════════════════════════════════════════════
if ($action === 'hareket_ekle') {
    $hesap_id = (int) ($_POST['hesap_id'] ?? 0);
    $tip = trim($_POST['tip'] ?? '');
    $tutar = (float) ($_POST['tutar'] ?? 0);
    $aciklama = trim($_POST['aciklama'] ?? '');
    $tarih = $_POST['tarih'] ?? date('Y-m-d');

    if (!$hesap_id || !in_array($tip, ['giris', 'cikis']) || $tutar <= 0) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz veri.']);
        exit;
    }

    $pdo->prepare("
        INSERT INTO hesap_hareketleri
            (hesap_id, tip, tutar, referans_tip, aciklama, tarih)
        VALUES (?, ?, ?, 'manuel', ?, ?)
    ")->execute([$hesap_id, $tip, $tutar, $aciklama, $tarih]);

    echo json_encode(
        ['basari' => true, 'mesaj' => 'Hareket eklendi.'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// ════════════════════════════════════════════════════════
// TRANSFER
// ════════════════════════════════════════════════════════
if ($action === 'transfer') {
    $kaynak_id = (int) ($_POST['kaynak_id'] ?? 0);
    $hedef_id = (int) ($_POST['hedef_id'] ?? 0);
    $tutar = (float) ($_POST['tutar'] ?? 0);
    $aciklama = trim($_POST['aciklama'] ?? '');
    $tarih = $_POST['tarih'] ?? date('Y-m-d');

    if (!$kaynak_id || !$hedef_id || $kaynak_id === $hedef_id || $tutar <= 0) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz transfer verisi.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Transfer kaydı
        $pdo->prepare("
            INSERT INTO hesap_transferler
                (kaynak_id, hedef_id, tutar, aciklama, tarih)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$kaynak_id, $hedef_id, $tutar, $aciklama, $tarih]);
        $transferId = (int) $pdo->lastInsertId();

        // Kaynak → çıkış
        $pdo->prepare("
            INSERT INTO hesap_hareketleri
                (hesap_id, tip, tutar, referans_tip, referans_id, aciklama, tarih)
            VALUES (?, 'transfer_cikis', ?, 'transfer', ?, ?, ?)
        ")->execute([$kaynak_id, $tutar, $transferId, $aciklama, $tarih]);

        // Hedef → giriş
        $pdo->prepare("
            INSERT INTO hesap_hareketleri
                (hesap_id, tip, tutar, referans_tip, referans_id, aciklama, tarih)
            VALUES (?, 'transfer_giris', ?, 'transfer', ?, ?, ?)
        ")->execute([$hedef_id, $tutar, $transferId, $aciklama, $tarih]);

        $pdo->commit();
        echo json_encode(
            ['basari' => true, 'mesaj' => 'Transfer tamamlandı.'],
            JSON_UNESCAPED_UNICODE
        );

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => $e->getMessage()]);
    }
    exit;
}

// ════════════════════════════════════════════════════════
// SON TRANSFERLER
// ════════════════════════════════════════════════════════
if ($action === 'son_transferler') {
    $stmt = $pdo->query("
        SELECT
            t.id,
            t.tarih,
            t.tutar,
            t.aciklama,
            k.ad AS kaynak_ad,
            k.ikon AS kaynak_ikon,
            h.ad AS hedef_ad,
            h.ikon AS hedef_ikon
        FROM hesap_transferler t
        JOIN odeme_hesaplari k ON k.id = t.kaynak_id
        JOIN odeme_hesaplari h ON h.id = t.hedef_id
        ORDER BY t.tarih DESC, t.id DESC
        LIMIT 20
    ");
    $transferler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($transferler as &$t) {
        $t['tutar'] = (float) $t['tutar'];
    }
    unset($t);

    echo json_encode([
        'basari' => true,
        'transferler' => $transferler
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════
// TRANSFER SİL
// ════════════════════════════════════════════════════════
if ($action === 'transfer_sil') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['basari' => false, 'mesaj' => 'ID gerekli.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Bağlı hareketleri sil
        $pdo->prepare("
            DELETE FROM hesap_hareketleri
            WHERE referans_tip = 'transfer' AND referans_id = ?
        ")->execute([$id]);

        // Transfer kaydını sil
        $pdo->prepare("DELETE FROM hesap_transferler WHERE id = ?")
            ->execute([$id]);

        $pdo->commit();
        echo json_encode(
            ['basari' => true, 'mesaj' => 'Transfer silindi.'],
            JSON_UNESCAPED_UNICODE
        );

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => $e->getMessage()]);
    }
    exit;
}

// ════════════════════════════════════════════════════════
// HAREKET SİL (manuel)
// ════════════════════════════════════════════════════════
if ($action === 'hareket_sil') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['basari' => false, 'mesaj' => 'ID gerekli.']);
        exit;
    }

    // Sadece manuel hareketler silinebilir
    $stmt = $pdo->prepare("
        SELECT referans_tip FROM hesap_hareketleri WHERE id = ?
    ");
    $stmt->execute([$id]);
    $ref = $stmt->fetchColumn();

    if ($ref !== 'manuel') {
        echo json_encode([
            'basari' => false,
            'mesaj' => 'Sadece manuel hareketler silinebilir.'
        ]);
        exit;
    }

    $pdo->prepare("DELETE FROM hesap_hareketleri WHERE id = ?")
        ->execute([$id]);

    echo json_encode(['basari' => true, 'mesaj' => 'Hareket silindi.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════
// CARİ ÖDEME — Kasadan müşteri/tedarikçiye ödeme
// ════════════════════════════════════════════════════════
if ($action === 'cari_odeme') {
    $hesap_id   = (int)  ($_POST['hesap_id']   ?? 0);
    $musteri_id = (int)  ($_POST['musteri_id'] ?? 0);
    $tutar      = (float)($_POST['tutar']      ?? 0);
    $aciklama   = trim(  $_POST['aciklama']    ?? '');
    $tarih      = $_POST['tarih'] ?? date('Y-m-d');
    $referans   = trim(  $_POST['referans']    ?? '');

    if (!$hesap_id || !$musteri_id || $tutar <= 0) {
        echo json_encode(['basari' => false, 'mesaj' => 'Hesap, müşteri ve tutar zorunlu.']);
        exit;
    }

    $bakiyeRow = $pdo->prepare("
        SELECT baslangic_bak + COALESCE((
            SELECT SUM(CASE WHEN tip IN ('giris','transfer_giris') THEN tutar ELSE 0 END)
                 - SUM(CASE WHEN tip IN ('cikis','transfer_cikis','cari_odeme') THEN tutar ELSE 0 END)
            FROM hesap_hareketleri WHERE hesap_id = ?
        ), 0) FROM odeme_hesaplari WHERE id=?
    ");
    $bakiyeRow->execute([$hesap_id, $hesap_id]);
    $bakiye = (float)($bakiyeRow->fetchColumn() ?: 0);

    if ($bakiye < $tutar) {
        echo json_encode(['basari' => false, 'mesaj' => "Yetersiz bakiye. Mevcut: " . number_format($bakiye, 2, ',', '.') . " ₺"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO hesap_transferler
                (kaynak_id, hedef_id, tutar, aciklama, tarih, tur, musteri_id, referans)
            VALUES (?, ?, ?, ?, ?, 'cari_odeme', ?, ?)
        ")->execute([$hesap_id, $hesap_id, $tutar, $aciklama, $tarih, $musteri_id, $referans]);
        $transferId = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO hesap_hareketleri
                (hesap_id, tip, tutar, referans_tip, referans_id, aciklama, tarih)
            VALUES (?, 'cikis', ?, 'cari_odeme', ?, ?, ?)
        ")->execute([$hesap_id, $tutar, $transferId, $aciklama, $tarih]);

        $aciklamaFull = "Cari Ödeme" . ($referans ? " — $referans" : '') . ($aciklama ? " ($aciklama)" : '');
        $pdo->prepare("
            INSERT INTO tahsilatlar
                (musteri_id, fatura_id, tutar, odeme_tipi, aciklama, tarih, hesap_id)
            VALUES (?, NULL, ?, 'cari_odeme', ?, ?, ?)
        ")->execute([$musteri_id, $tutar, $aciklamaFull, $tarih, $hesap_id]);

        $pdo->commit();

        $musteriAdi = $pdo->prepare("SELECT ad_soyad FROM musteriler WHERE id=?");
        $musteriAdi->execute([$musteri_id]);
        $musteriAdi = $musteriAdi->fetchColumn() ?: '';

        echo json_encode([
            'basari'      => true,
            'mesaj'       => "$musteriAdi adına " . number_format($tutar, 2, ',', '.') . " ₺ ödeme yapıldı.",
            'transfer_id' => $transferId,
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => $e->getMessage()]);
    }
    exit;
}

// ════════════════════════════════════════════════════════
// CARİ ÖDEMELERİ LİSTELE
// ════════════════════════════════════════════════════════
if ($action === 'cari_odeme_listesi') {
    $limit  = min((int)($_POST['limit'] ?? 50), 200);
    $offset = (int)($_POST['offset'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT ht.id, ht.tutar, ht.aciklama, ht.tarih, ht.referans,
               oh.ad AS hesap_adi, oh.ikon AS hesap_ikon,
               m.ad_soyad AS musteri_adi
        FROM hesap_transferler ht
        JOIN odeme_hesaplari oh ON oh.id = ht.kaynak_id
        JOIN musteriler m ON m.id = ht.musteri_id
        WHERE ht.tur = 'cari_odeme'
        ORDER BY ht.tarih DESC, ht.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $toplam = $pdo->query("SELECT COUNT(*) FROM hesap_transferler WHERE tur='cari_odeme'")->fetchColumn();

    echo json_encode(['basari' => true, 'liste' => $liste, 'toplam' => $toplam], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz işlem.']);
