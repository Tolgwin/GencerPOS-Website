<?php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ══════════════════════════════════════════════════════
// YARDIMCI: Müşteri + ilişkili verileri getir
// ══════════════════════════════════════════════════════
function musteriDetay(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM musteriler WHERE id = ?");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m)
        return null;

    $stmt = $pdo->prepare(
        "SELECT * FROM musteri_telefonlar
         WHERE musteri_id = ?
         ORDER BY varsayilan DESC, id ASC"
    );
    $stmt->execute([$id]);
    $m['telefonlar'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT * FROM musteri_adresler
         WHERE musteri_id = ?
         ORDER BY varsayilan DESC, id ASC"
    );
    $stmt->execute([$id]);
    $m['adresler'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT e.*
         FROM etiketler e
         JOIN musteri_etiketler me ON e.id = me.etiket_id
         WHERE me.musteri_id = ?
         ORDER BY e.ad"
    );
    $stmt->execute([$id]);
    $m['etiketler'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $m;
}

// ══════════════════════════════════════════════════════
// VERGİ DAİRELERİ GETİR  ← YUKARI TAŞINDI
// ══════════════════════════════════════════════════════
if ($action === 'vergi_daireleri') {
    $il = trim($_POST['il'] ?? '');

    if ($il) {
        $stmt = $pdo->prepare(
            "SELECT id, il, ad FROM vergi_daireleri
             WHERE il = ?
             ORDER BY ad"
        );
        $stmt->execute([$il]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, il, ad FROM vergi_daireleri
             ORDER BY il, ad"
        );
    }

    $liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $gruplu = [];
    foreach ($liste as $row) {
        $gruplu[$row['il']][] = $row;
    }

    echo json_encode([
        'basari' => true,
        'liste' => $liste,
        'gruplu' => $gruplu,
        'iller' => array_keys($gruplu)
    ]);
    exit;
}

// ══════════════════════════════════════════════════════
// ETİKETLERİ GETİR
// ══════════════════════════════════════════════════════
if ($action === 'etiketler_getir') {
    $etiketler = $pdo->query(
        "SELECT * FROM etiketler ORDER BY ad"
    )->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['basari' => true, 'etiketler' => $etiketler]);
    exit;
}

// ══════════════════════════════════════════════════════
// YENİ ETİKET OLUŞTUR
// ══════════════════════════════════════════════════════
if ($action === 'etiket_olustur') {
    $ad = trim($_POST['ad'] ?? '');
    $renk = trim($_POST['renk'] ?? '#3498db');
    if (empty($ad)) {
        echo json_encode(['basari' => false, 'mesaj' => 'Etiket adı boş olamaz.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO etiketler (ad, renk) VALUES (?, ?)");
        $stmt->execute([$ad, $renk]);
        $yeniId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM etiketler WHERE id = ?");
        $stmt->execute([$yeniId]);
        echo json_encode([
            'basari' => true,
            'etiket' => $stmt->fetch(PDO::FETCH_ASSOC)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['basari' => false, 'mesaj' => 'Bu etiket zaten mevcut.']);
    }
    exit;
}

if ($action === 'etiket_listele') {
    $stmt = $pdo->query("SELECT * FROM etiketler ORDER BY ad ASC");
    echo json_encode(['basari' => true, 'etiketler' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'etiket_guncelle') {
    $id   = (int)($_POST['id'] ?? 0);
    $ad   = trim($_POST['ad'] ?? '');
    $renk = trim($_POST['renk'] ?? '#3498db');
    if (!$id || empty($ad)) { echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz veri.']); exit; }
    $pdo->prepare("UPDATE etiketler SET ad=?, renk=? WHERE id=?")->execute([$ad, $renk, $id]);
    echo json_encode(['basari' => true]);
    exit;
}

if ($action === 'etiket_sil') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['basari' => false, 'mesaj' => 'ID gerekli.']); exit; }
    $pdo->prepare("DELETE FROM musteri_etiketler WHERE etiket_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM etiketler WHERE id=?")->execute([$id]);
    echo json_encode(['basari' => true]);
    exit;
}

// ══════════════════════════════════════════════════════
// MÜŞTERİ ARA
// ══════════════════════════════════════════════════════
// ── LİSTE (sayfalama destekli, basit) ────────────────────────────
if ($action === 'liste') {
    $q         = trim($_POST['ad_soyad'] ?? '');
    $tip       = trim($_POST['tip'] ?? '');
    $etiketIds = array_filter(array_map('intval', explode(',', $_POST['etiket_id'] ?? '')));
    $withOzet  = !empty($_POST['ozet']);
    $limit     = max(1, min(200, (int)($_POST['limit'] ?? 25)));
    $offset    = max(0, (int)($_POST['offset'] ?? 0));

    $where  = [];
    $params = [];

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "(m.ad_soyad LIKE :q1 OR m.vergi_no LIKE :q2 OR m.email LIKE :q3)";
        $params += [':q1'=>$like, ':q2'=>$like, ':q3'=>$like];
    }
    if ($tip !== '') {
        $where[] = "m.musteri_tipi = :tip";
        $params[':tip'] = $tip;
    }
    if (!empty($etiketIds)) {
        $in = implode(',', $etiketIds);
        $where[] = "EXISTS (SELECT 1 FROM musteri_etiketler me WHERE me.musteri_id = m.id AND me.etiket_id IN ($in))";
    }

    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Özet (ilk yüklemede)
    $ozet = null;
    if ($withOzet) {
        $ozRow = $pdo->prepare("
            SELECT
                COUNT(*) AS toplam,
                SUM(musteri_tipi='kurumsal') AS kurumsal,
                SUM(musteri_tipi='bireysel') AS bireysel,
                COALESCE(SUM(baslangic_bakiye),0) AS bakiye_top
            FROM musteriler m $whereStr
        ");
        $ozRow->execute($params);
        $ozet = $ozRow->fetch();
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM musteriler m $whereStr");
    $countStmt->execute($params);
    $toplam = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT m.* FROM musteriler m $whereStr ORDER BY m.ad_soyad ASC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $musteriler = $stmt->fetchAll();

    // Her müşteriye telefonlar, adresler ve etiketleri ekle
    foreach ($musteriler as &$m) {
        $s1 = $pdo->prepare("SELECT * FROM musteri_telefonlar WHERE musteri_id=? ORDER BY varsayilan DESC, id ASC");
        $s1->execute([$m['id']]); $m['telefonlar'] = $s1->fetchAll();
        $s2 = $pdo->prepare("SELECT * FROM musteri_adresler WHERE musteri_id=? ORDER BY varsayilan DESC, id ASC");
        $s2->execute([$m['id']]); $m['adresler'] = $s2->fetchAll();
        $s3 = $pdo->prepare("SELECT e.* FROM etiketler e JOIN musteri_etiketler me ON e.id=me.etiket_id WHERE me.musteri_id=? ORDER BY e.ad");
        $s3->execute([$m['id']]); $m['etiketler'] = $s3->fetchAll();
    }
    unset($m);

    echo json_encode(['basari'=>true, 'musteriler'=>$musteriler, 'toplam'=>$toplam, 'ozet'=>$ozet]);
    exit;
}

if ($action === 'ara') {
    $q = trim($_POST['ad_soyad'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['durum' => 'yok', 'musteriler' => []]);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT
            m.*,
            GROUP_CONCAT(
                DISTINCT CONCAT(t.etiket, ': ', t.telefon)
                ORDER BY t.varsayilan DESC
                SEPARATOR ' | '
            ) AS tel_ozet,
            GROUP_CONCAT(
                DISTINCT e.ad
                ORDER BY e.ad
                SEPARATOR ','
            ) AS etiket_ozet,
            GROUP_CONCAT(
                DISTINCT e.renk
                ORDER BY e.ad
                SEPARATOR ','
            ) AS etiket_renkler
        FROM musteriler m
        LEFT JOIN musteri_telefonlar t  ON t.musteri_id = m.id
        LEFT JOIN musteri_etiketler  me ON me.musteri_id = m.id
        LEFT JOIN etiketler          e  ON e.id = me.etiket_id
        WHERE m.ad_soyad LIKE ?
           OR m.vergi_no  LIKE ?
        GROUP BY m.id
        ORDER BY m.ad_soyad
        LIMIT 10
    ");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    $musteriler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'durum' => count($musteriler) ? 'bulundu' : 'yok',
        'musteriler' => $musteriler
    ]);
    exit;
}

// ══════════════════════════════════════════════════════
// MÜŞTERİ DETAY
// ══════════════════════════════════════════════════════
if ($action === 'detay') {
    $id = (int) ($_POST['id'] ?? 0);
    $m = musteriDetay($pdo, $id);
    if (!$m) {
        echo json_encode(['basari' => false, 'mesaj' => 'Müşteri bulunamadı.']);
        exit;
    }
    echo json_encode(['basari' => true, 'musteri' => $m]);
    exit;
}

// ══════════════════════════════════════════════════════
// YENİ MÜŞTERİ KAYDET
// ══════════════════════════════════════════════════════
if ($action === 'yeni_kaydet') {

    $ad = mb_strtoupper(trim($_POST['ad_soyad'] ?? ''), 'UTF-8');
    $email = trim($_POST['email'] ?? '');
    $vergi_dairesi = mb_strtoupper(trim($_POST['vergi_dairesi'] ?? ''), 'UTF-8');
    $vergi_no = trim($_POST['vergi_no'] ?? '');
    $musteri_tipi = in_array($_POST['musteri_tipi'] ?? '', ['bireysel', 'kurumsal'])
        ? $_POST['musteri_tipi'] : 'bireysel';
    $notlar = trim($_POST['notlar'] ?? '');

    $telefonlar = json_decode($_POST['telefonlar'] ?? '[]', true) ?: [];
    $adresler = json_decode($_POST['adresler'] ?? '[]', true) ?: [];
    $etiket_idler = json_decode($_POST['etiket_idler'] ?? '[]', true) ?: [];

    if (empty($ad)) {
        echo json_encode(['basari' => false, 'mesaj' => 'Ad Soyad zorunludur.']);
        exit;
    }

    // Aynı kayıt var mı?
    $stmt = $pdo->prepare(
        "SELECT id FROM musteriler
         WHERE ad_soyad = ?
           AND COALESCE(vergi_no,'') = ?"
    );
    $stmt->execute([$ad, $vergi_no]);
    $varMi = $stmt->fetch();

    if ($varMi) {
        $m = musteriDetay($pdo, $varMi['id']);
        echo json_encode([
            'basari' => false,
            'ayni' => true,
            'mesaj' => 'Bu müşteri zaten kayıtlı, mevcut kayıt kullanılacak.',
            'musteri' => $m
        ]);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $baslangic_bakiye = floatval($_POST['baslangic_bakiye'] ?? 0);
        $stmt = $pdo->prepare("
            INSERT INTO musteriler
                (ad_soyad, email, vergi_dairesi, vergi_no, musteri_tipi, notlar, baslangic_bakiye)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ad,
            $email,
            $vergi_dairesi ?: null,
            $vergi_no ?: null,
            $musteri_tipi,
            $notlar ?: null,
            $baslangic_bakiye
        ]);
        $musteri_id = (int) $pdo->lastInsertId();

        foreach ($telefonlar as $i => $t) {
            $tel = trim($t['telefon'] ?? '');
            if (empty($tel))
                continue;
            $stmt = $pdo->prepare("
                INSERT INTO musteri_telefonlar
                    (musteri_id, telefon, etiket, varsayilan)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $musteri_id,
                $tel,
                $t['etiket'] ?? 'Cep',
                $i === 0 ? 1 : 0
            ]);
        }

        foreach ($adresler as $i => $a) {
            $adres = trim($a['adres'] ?? '');
            if (empty($adres))
                continue;
            $stmt = $pdo->prepare("
                INSERT INTO musteri_adresler
                    (musteri_id, baslik, adres, sehir, ilce, posta_kodu, varsayilan)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $musteri_id,
                $a['baslik'] ?? 'Merkez',
                $adres,
                $a['sehir'] ?? '',
                $a['ilce'] ?? '',
                $a['posta_kodu'] ?? '',
                $i === 0 ? 1 : 0
            ]);
        }

        foreach ($etiket_idler as $eid) {
            $eid = (int) $eid;
            if ($eid <= 0)
                continue;
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO musteri_etiketler (musteri_id, etiket_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$musteri_id, $eid]);
        }

        $pdo->commit();
        $m = musteriDetay($pdo, $musteri_id);
        echo json_encode([
            'basari' => true,
            'mesaj' => 'Yeni müşteri kaydedildi.',
            'musteri' => $m
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'basari' => false,
            'mesaj' => 'Kayıt hatası: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ══════════════════════════════════════════════════════
// MÜŞTERİ GÜNCELLE
// ══════════════════════════════════════════════════════
if ($action === 'guncelle') {

    $id = (int) ($_POST['id'] ?? 0);
    $ad = mb_strtoupper(trim($_POST['ad_soyad'] ?? ''), 'UTF-8');
    $email = trim($_POST['email'] ?? '');
    $vergi_dairesi = mb_strtoupper(trim($_POST['vergi_dairesi'] ?? ''), 'UTF-8');
    $vergi_no = trim($_POST['vergi_no'] ?? '');
    $musteri_tipi = in_array($_POST['musteri_tipi'] ?? '', ['bireysel', 'kurumsal'])
        ? $_POST['musteri_tipi'] : 'bireysel';
    $notlar = trim($_POST['notlar'] ?? '');

    $telefonlar = json_decode($_POST['telefonlar'] ?? '[]', true) ?: [];
    $adresler = json_decode($_POST['adresler'] ?? '[]', true) ?: [];
    $etiket_idler = json_decode($_POST['etiket_idler'] ?? '[]', true) ?: [];

    if (!$id) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz müşteri ID.']);
        exit;
    }
    if (empty($ad)) {
        echo json_encode(['basari' => false, 'mesaj' => 'Ad Soyad zorunludur.']);
        exit;
    }

    $mevcut = musteriDetay($pdo, $id);
    if (!$mevcut) {
        echo json_encode(['basari' => false, 'mesaj' => 'Müşteri bulunamadı.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $baslangic_bakiye2 = floatval($_POST['baslangic_bakiye'] ?? $mevcut['baslangic_bakiye'] ?? 0);
        $stmt = $pdo->prepare("
            UPDATE musteriler
            SET ad_soyad         = ?,
                email            = ?,
                vergi_dairesi    = ?,
                vergi_no         = ?,
                musteri_tipi     = ?,
                notlar           = ?,
                baslangic_bakiye = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $ad,
            $email,
            $vergi_dairesi ?: null,
            $vergi_no ?: null,
            $musteri_tipi,
            $notlar ?: null,
            $baslangic_bakiye2,
            $id
        ]);

        $pdo->prepare("DELETE FROM musteri_telefonlar WHERE musteri_id = ?")->execute([$id]);
        foreach ($telefonlar as $i => $t) {
            $tel = trim($t['telefon'] ?? '');
            if (empty($tel))
                continue;
            $stmt = $pdo->prepare("
                INSERT INTO musteri_telefonlar
                    (musteri_id, telefon, etiket, varsayilan)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $tel,
                $t['etiket'] ?? 'Cep',
                $i === 0 ? 1 : 0
            ]);
        }

        $pdo->prepare("DELETE FROM musteri_adresler WHERE musteri_id = ?")->execute([$id]);
        foreach ($adresler as $i => $a) {
            $adres = trim($a['adres'] ?? '');
            if (empty($adres))
                continue;
            $stmt = $pdo->prepare("
                INSERT INTO musteri_adresler
                    (musteri_id, baslik, adres, sehir, ilce, posta_kodu, varsayilan)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $a['baslik'] ?? 'Merkez',
                $adres,
                $a['sehir'] ?? '',
                $a['ilce'] ?? '',
                $a['posta_kodu'] ?? '',
                $i === 0 ? 1 : 0
            ]);
        }

        $pdo->prepare("DELETE FROM musteri_etiketler WHERE musteri_id = ?")->execute([$id]);
        foreach ($etiket_idler as $eid) {
            $eid = (int) $eid;
            if ($eid <= 0)
                continue;
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO musteri_etiketler (musteri_id, etiket_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$id, $eid]);
        }

        $pdo->commit();
        $guncel = musteriDetay($pdo, $id);
        echo json_encode([
            'basari' => true,
            'mesaj' => 'Müşteri güncellendi.',
            'musteri' => $guncel
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'basari' => false,
            'mesaj' => 'Güncelleme hatası: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ── SİL ──────────────────────────────────────────────────────────
if ($action === 'sil') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz müşteri ID.']);
        exit;
    }
    $check = $pdo->prepare("SELECT COUNT(*) FROM faturalar WHERE musteri_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['basari' => false, 'mesaj' => 'Bu müşteriye ait faturalar mevcut. Önce faturaları silin.']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM musteriler WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['basari' => true, 'mesaj' => 'Müşteri silindi.']);
    exit;
}

echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz işlem.']);
