<?php
require_once 'db.php';
require_once 'auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = trim($_POST['action'] ?? '');

// ════════════════════════════════════════════════════════
// FATURA LİSTESİ + BAKİYE
// ════════════════════════════════════════════════════════
if ($action === 'fatura_liste_bakiye') {

    $musteri_id = (int) ($_POST['musteri_id'] ?? 0);
    $limit = max(1, (int) ($_POST['limit'] ?? 20));
    $offset = max(0, (int) ($_POST['offset'] ?? 0));
    $arama = trim($_POST['arama'] ?? '');
    $durum = trim($_POST['durum'] ?? '');
    $tarih_bas = trim($_POST['tarih_bas'] ?? '');
    $tarih_bit = trim($_POST['tarih_bit'] ?? '');
    $etiket_id = (int) ($_POST['etiket_id'] ?? 0);

    $where = ['1=1'];
    $params = [];

    if ($musteri_id) {
        $where[] = 'f.musteri_id = ?';
        $params[] = $musteri_id;
    }
    if ($arama !== '') {
        $where[] = '(f.fatura_no LIKE ? OR m.ad_soyad LIKE ?)';
        $params[] = "%{$arama}%";
        $params[] = "%{$arama}%";
    }
    if ($durum !== '') {
        $where[] = 'f.odeme_durumu = ?';
        $params[] = $durum;
    }
    if ($tarih_bas !== '') {
        $where[] = 'f.tarih >= ?';
        $params[] = $tarih_bas;
    }
    if ($tarih_bit !== '') {
        $where[] = 'f.tarih <= ?';
        $params[] = $tarih_bit;
    }
    if ($etiket_id) {
        $where[] = 'EXISTS (SELECT 1 FROM musteri_etiketler me WHERE me.musteri_id = f.musteri_id AND me.etiket_id = ?)';
        $params[] = $etiket_id;
    }

    $whereSQL = implode(' AND ', $where);

    // Toplam kayıt sayısı
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*)
        FROM faturalar f
        JOIN musteriler m ON f.musteri_id = m.id
        WHERE {$whereSQL}
    ");
    $stmtCount->execute($params);
    $toplamKayit = (int) $stmtCount->fetchColumn();

    // Fatura listesi — named param karışıklığını önlemek için
    // limit/offset'i doğrudan SQL'e göm (integer olduğu doğrulandı)
    $sql = "
        SELECT
            f.id,
            f.fatura_no,
            f.tarih,
            f.vade_tarihi,
            f.odeme_durumu,
            f.notlar,
            f.paylasim_token,
            m.id        AS musteri_id,
            m.ad_soyad  AS musteri_adi,
            m.telefon   AS musteri_tel,
            p.ad_soyad  AS personel_adi,
            f.personel_id,
            COALESCE(
                (SELECT SUM(fk.miktar * fk.birim_fiyat * (1 + fk.kdv_orani / 100))
                 FROM fatura_kalemleri fk
                 WHERE fk.fatura_id = f.id), 0
            ) AS toplam,
            COALESCE(
                (SELECT SUM(t.tutar)
                 FROM tahsilatlar t
                 WHERE t.fatura_id = f.id), 0
            ) AS odenen
        FROM faturalar f
        JOIN musteriler m ON f.musteri_id = m.id
        LEFT JOIN personeller p ON f.personel_id = p.id
        WHERE {$whereSQL}
        ORDER BY f.tarih DESC, f.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $faturalar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($faturalar as &$f) {
        $f['toplam'] = (float) $f['toplam'];
        $f['odenen'] = (float) $f['odenen'];
        $f['kalan'] = $f['toplam'] - $f['odenen'];
    }
    unset($f);

    // Genel özet (tüm filtreli kayıtlar için)
    $stmt2 = $pdo->prepare("
        SELECT
            COALESCE(SUM(
                (SELECT SUM(fk.miktar * fk.birim_fiyat * (1 + fk.kdv_orani / 100))
                 FROM fatura_kalemleri fk
                 WHERE fk.fatura_id = f.id)
            ), 0) AS toplam,
            COALESCE(SUM(
                (SELECT SUM(t.tutar)
                 FROM tahsilatlar t
                 WHERE t.fatura_id = f.id)
            ), 0) AS odenen
        FROM faturalar f
        JOIN musteriler m ON f.musteri_id = m.id
        WHERE {$whereSQL}
    ");
    $stmt2->execute($params);
    $ozet = $stmt2->fetch(PDO::FETCH_ASSOC);
    $toplamBorc = (float) $ozet['toplam'];
    $toplamAlacak = (float) $ozet['odenen'];

    echo json_encode([
        'basari' => true,
        'faturalar' => $faturalar,
        'toplam' => $toplamKayit,
        'toplam_borc' => $toplamBorc,
        'toplam_alacak' => $toplamAlacak,
        'net_bakiye' => $toplamBorc - $toplamAlacak
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════
// CARİ HESAP DÖKÜMÜ
// ════════════════════════════════════════════════════════
if ($action === 'cari_dokum') {
    $musteri_id = (int) ($_POST['musteri_id'] ?? 0);
    if (!$musteri_id) {
        echo json_encode(['basari' => false, 'mesaj' => 'Müşteri ID gerekli.']);
        exit;
    }

    $stmtF = $pdo->prepare("
        SELECT
            f.tarih,
            'fatura'    AS tip,
            f.fatura_no AS referans,
            COALESCE(
                (SELECT SUM(fk.miktar * fk.birim_fiyat * (1 + fk.kdv_orani / 100))
                 FROM fatura_kalemleri fk
                 WHERE fk.fatura_id = f.id), 0
            )           AS borc,
            0           AS alacak,
            f.notlar    AS aciklama,
            f.id        AS fatura_id,
            NULL        AS id
        FROM faturalar f
        WHERE f.musteri_id = ?
        ORDER BY f.tarih ASC, f.id ASC
    ");
    $stmtF->execute([$musteri_id]);
    $faturaHareketler = $stmtF->fetchAll(PDO::FETCH_ASSOC);

    $stmtT = $pdo->prepare("
        SELECT
            t.tarih,
            t.odeme_tipi                          AS tip,
            COALESCE(f.fatura_no, 'Genel Ödeme') AS referans,
            CASE WHEN t.odeme_tipi = 'cari_odeme' THEN t.tutar ELSE 0 END AS borc,
            CASE WHEN t.odeme_tipi != 'cari_odeme' THEN t.tutar ELSE 0 END AS alacak,
            t.aciklama,
            t.fatura_id,
            t.id
        FROM tahsilatlar t
        LEFT JOIN faturalar f ON f.id = t.fatura_id
        WHERE t.musteri_id = ?
        ORDER BY t.tarih ASC, t.id ASC
    ");
    $stmtT->execute([$musteri_id]);
    $tahsilatHareketler = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    $hareketler = array_merge($faturaHareketler, $tahsilatHareketler);
    usort($hareketler, function ($a, $b) {
        $t = strcmp($a['tarih'], $b['tarih']);
        if ($t !== 0)
            return $t;
        return strcmp($a['tip'], $b['tip']);
    });

    $bakiye = 0.0;
    foreach ($hareketler as &$h) {
        $h['borc'] = (float) $h['borc'];
        $h['alacak'] = (float) $h['alacak'];
        $bakiye += $h['borc'] - $h['alacak'];
        $h['bakiye'] = $bakiye;
    }
    unset($h);

    $topBorc   = array_sum(array_column($faturaHareketler, 'borc'))
               + array_sum(array_column(
                   array_filter($tahsilatHareketler, fn($h) => $h['tip'] === 'cari_odeme'),
                   'borc'
               ));
    $topAlacak = array_sum(array_column(
        array_filter($tahsilatHareketler, fn($h) => $h['tip'] !== 'cari_odeme'),
        'alacak'
    ));

    echo json_encode([
        'basari' => true,
        'hareketler' => $hareketler,
        'toplam_borc' => (float) $topBorc,
        'toplam_alacak' => (float) $topAlacak,
        'net_bakiye' => (float) ($topBorc - $topAlacak)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════
// TAHSİLAT EKLE
// ════════════════════════════════════════════════════════
// ════════════════════════════════════════════════════════
// TAHSİLAT EKLE
// ════════════════════════════════════════════════════════
if ($action === 'tahsilat_ekle') {
    $musteri_id = (int) ($_POST['musteri_id'] ?? 0);
    $fatura_id_raw = trim($_POST['fatura_id'] ?? '');
    $fatura_id = ($fatura_id_raw !== '' && $fatura_id_raw !== '0'
        && $fatura_id_raw !== 'null')
        ? (int) $fatura_id_raw : null;

    $tutar = (float) ($_POST['tutar'] ?? 0);
    $odeme_tipi = trim($_POST['odeme_tipi'] ?? 'nakit');
    $aciklama = trim($_POST['aciklama'] ?? '');
    $tarih = trim($_POST['tarih'] ?? date('Y-m-d'));

    // ✅ Hangi ödeme hesabına işlenecek
    $hesap_id_raw = trim($_POST['hesap_id'] ?? '');
    $hesap_id = ($hesap_id_raw !== '' && $hesap_id_raw !== '0')
        ? (int) $hesap_id_raw : null;

    if (!$musteri_id || $tutar <= 0) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz veri.']);
        exit;
    }

    $izinli = ['nakit', 'havale', 'eft', 'kredi_karti', 'cek', 'senet', 'diger', 'banka', 'pos'];
    if (!in_array($odeme_tipi, $izinli))
        $odeme_tipi = 'nakit';

    try {
        $pdo->beginTransaction();

        // Benzersiz makbuz token üret
        do {
            $makbuz_token = bin2hex(random_bytes(20));
            $chk = $pdo->prepare("SELECT id FROM tahsilatlar WHERE makbuz_token=?");
            $chk->execute([$makbuz_token]);
        } while ($chk->fetch());

        // 1) Tahsilat kaydı
        $pdo->prepare("
            INSERT INTO tahsilatlar
                (musteri_id, fatura_id, tutar, odeme_tipi, aciklama, tarih, hesap_id, makbuz_token)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $musteri_id, $fatura_id, $tutar,
            $odeme_tipi, $aciklama, $tarih,
            $hesap_id, $makbuz_token
        ]);
        $tahsilatId = (int) $pdo->lastInsertId();

        // 2) Fatura ödeme durumunu güncelle
        if ($fatura_id) {
            odemeDurumGuncelle($pdo, $fatura_id);
        }

        // 3) ✅ Ödeme hesabına hareket ekle
        if ($hesap_id) {
            // Açıklama oluştur
            $hareketAciklama = 'Tahsilat';
            if ($fatura_id) {
                $stmtFno = $pdo->prepare(
                    "SELECT fatura_no FROM faturalar WHERE id = ?"
                );
                $stmtFno->execute([$fatura_id]);
                $faturaNo = $stmtFno->fetchColumn();
                if ($faturaNo) {
                    $hareketAciklama = "Tahsilat — Fatura: {$faturaNo}";
                }
            }
            if ($aciklama) {
                $hareketAciklama .= " / {$aciklama}";
            }

            $pdo->prepare("
                INSERT INTO hesap_hareketleri
                    (hesap_id, tip, tutar, referans_tip,
                     referans_id, aciklama, tarih)
                VALUES (?, 'giris', ?, 'tahsilat', ?, ?, ?)
            ")->execute([
                        $hesap_id,
                        $tutar,
                        $tahsilatId,
                        $hareketAciklama,
                        $tarih
                    ]);
        }

        $pdo->commit();

        // Makbuz için gerekli bilgileri döndür
        $musteriAdi = $pdo->prepare("SELECT ad_soyad, telefon FROM musteriler WHERE id=?");
        $musteriAdi->execute([$musteri_id]);
        $musteriRow = $musteriAdi->fetch(PDO::FETCH_ASSOC) ?: [];

        $hesapAdi = '';
        if ($hesap_id) {
            $hesapAdi = $pdo->prepare("SELECT ad FROM odeme_hesaplari WHERE id=?");
            $hesapAdi->execute([$hesap_id]);
            $hesapAdi = $hesapAdi->fetchColumn() ?: '';
        }

        $faturaNo = '';
        if ($fatura_id) {
            $fnRow = $pdo->prepare("SELECT fatura_no FROM faturalar WHERE id=?");
            $fnRow->execute([$fatura_id]);
            $faturaNo = $fnRow->fetchColumn() ?: '';
        }

        echo json_encode([
            'basari'       => true,
            'mesaj'        => 'Tahsilat başarıyla kaydedildi.',
            'tahsilat_id'  => $tahsilatId,
            'makbuz_token' => $makbuz_token,
            'tutar'        => $tutar,
            'tarih'        => $tarih,
            'odeme_tipi'   => $odeme_tipi,
            'hesap_adi'    => $hesapAdi,
            'fatura_no'    => $faturaNo,
            'musteri_adi'  => $musteriRow['ad_soyad'] ?? '',
            'musteri_tel'  => $musteriRow['telefon']  ?? '',
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => $e->getMessage()]);
    }
    exit;
}


// ════════════════════════════════════════════════════════
// TAHSİLAT SİL
// ════════════════════════════════════════════════════════
if ($action === 'tahsilat_sil') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['basari' => false, 'mesaj' => 'ID gerekli.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT fatura_id, odeme_tipi, tutar, tarih, hesap_id FROM tahsilatlar WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $fatura_id  = $row['fatura_id'] ?? null;
        $odeme_tipi = $row['odeme_tipi'] ?? '';
        $th_tutar   = (float)($row['tutar'] ?? 0);
        $th_tarih   = $row['tarih'] ?? '';
        $th_hesap   = (int)($row['hesap_id'] ?? 0);

        // Bağlı hesap hareketini sil (normal tahsilat tipi için)
        $pdo->prepare("
            DELETE FROM hesap_hareketleri
            WHERE referans_tip = 'tahsilat' AND referans_id = ?
        ")->execute([$id]);

        // cari_odeme ise: hesap_transferler + hesap_hareketleri temizle
        if ($odeme_tipi === 'cari_odeme' && $th_hesap) {
            // Transfer kaydını bul
            $htStmt = $pdo->prepare("
                SELECT id FROM hesap_transferler
                WHERE tur = 'cari_odeme'
                  AND kaynak_id = ?
                  AND ABS(tutar - ?) < 0.01
                  AND tarih = ?
                ORDER BY id DESC LIMIT 1
            ");
            $htStmt->execute([$th_hesap, $th_tutar, $th_tarih]);
            $transferId = $htStmt->fetchColumn();

            if ($transferId) {
                $pdo->prepare("
                    DELETE FROM hesap_hareketleri
                    WHERE referans_tip = 'cari_odeme' AND referans_id = ?
                ")->execute([$transferId]);
                $pdo->prepare("DELETE FROM hesap_transferler WHERE id = ?")
                    ->execute([$transferId]);
            } else {
                // Fallback: direkt hesap+tutar+tarih ile sil
                $pdo->prepare("
                    DELETE FROM hesap_hareketleri
                    WHERE hesap_id = ? AND ABS(tutar - ?) < 0.01
                      AND tarih = ? AND referans_tip = 'cari_odeme'
                ")->execute([$th_hesap, $th_tutar, $th_tarih]);
            }
        }

        // Tahsilatı sil
        $pdo->prepare("DELETE FROM tahsilatlar WHERE id = ?")->execute([$id]);

        // Fatura durumunu güncelle
        if ($fatura_id) {
            odemeDurumGuncelle($pdo, (int) $fatura_id);
        }

        $pdo->commit();
        echo json_encode(['basari' => true, 'mesaj' => 'Kayıt silindi.'], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => $e->getMessage()]);
    }
    exit;
}


// ════════════════════════════════════════════════════════
// YARDIMCI FONKSİYON
// ════════════════════════════════════════════════════════
function odemeDurumGuncelle(PDO $pdo, int $fatura_id): void
{
    $stmt = $pdo->prepare("
        SELECT
            f.toplam AS toplam,
            COALESCE(
                (SELECT SUM(t.tutar)
                 FROM tahsilatlar t
                 WHERE t.fatura_id = :fid2), 0
            ) AS odenen
        FROM faturalar f
        WHERE f.id = :fid1
    ");
    $stmt->execute([':fid1' => $fatura_id, ':fid2' => $fatura_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return;

    $toplam = (float) $row['toplam'];
    $odenen = (float) $row['odenen'];
    $kalan  = round($toplam - $odenen, 2);

    if ($kalan <= 0) {
        $durum = 'odendi';
    } elseif ($odenen > 0) {
        $durum = 'kismi';
    } else {
        $durum = 'odenmedi';
    }

    $pdo->prepare("UPDATE faturalar SET odeme_durumu = ? WHERE id = ?")
        ->execute([$durum, $fatura_id]);
}

// ── RAPOR: GELİR ─────────────────────────────────────────────────
if ($action === 'rapor_gelir') {
    $bas = $_POST['tarih_bas'] ?? date('Y-01-01');
    $bit = $_POST['tarih_bit'] ?? date('Y-m-d');

    $ozet = $pdo->prepare("
        SELECT
            COUNT(*) AS toplam_fatura,
            COALESCE(SUM(toplam), 0) AS toplam_ciro,
            COALESCE(SUM(CASE WHEN odeme_durumu='odendi' THEN toplam ELSE 0 END), 0) AS toplam_tahsil,
            COALESCE(SUM(CASE WHEN odeme_durumu!='odendi' AND durum!='iptal' THEN toplam ELSE 0 END), 0) AS bekleyen,
            SUM(CASE WHEN durum='iptal' THEN 1 ELSE 0 END) AS iptal_sayi
        FROM faturalar WHERE tarih BETWEEN ? AND ?
    ");
    $ozet->execute([$bas, $bit]);
    $row = $ozet->fetch();

    $aylik = $pdo->prepare("
        SELECT
            DATE_FORMAT(tarih,'%Y-%m') AS ay,
            COUNT(*) AS adet,
            COALESCE(SUM(toplam), 0) AS ciro,
            COALESCE(SUM(CASE WHEN odeme_durumu='odendi' THEN toplam ELSE 0 END), 0) AS tahsil,
            COALESCE(SUM(CASE WHEN odeme_durumu!='odendi' AND durum!='iptal' THEN toplam ELSE 0 END), 0) AS kalan
        FROM faturalar WHERE tarih BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(tarih,'%Y-%m')
        ORDER BY ay DESC
    ");
    $aylik->execute([$bas, $bit]);

    echo json_encode(['basari' => true] + $row + ['aylik' => $aylik->fetchAll()]);
    exit;
}

// ── RAPOR: MÜŞTERİ ───────────────────────────────────────────────
if ($action === 'rapor_musteri') {
    $bas = $_POST['tarih_bas'] ?? date('Y-01-01');
    $bit = $_POST['tarih_bit'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            m.ad_soyad,
            COUNT(f.id) AS fatura_sayi,
            COALESCE(SUM(f.toplam), 0) AS toplam,
            COALESCE(SUM(CASE WHEN f.odeme_durumu='odendi' THEN f.toplam ELSE 0 END), 0) AS odenen,
            COALESCE(SUM(CASE WHEN f.odeme_durumu!='odendi' AND f.durum!='iptal' THEN f.toplam ELSE 0 END), 0) AS kalan
        FROM faturalar f
        JOIN musteriler m ON m.id = f.musteri_id
        WHERE f.tarih BETWEEN ? AND ?
        GROUP BY m.id, m.ad_soyad
        ORDER BY toplam DESC
        LIMIT 100
    ");
    $stmt->execute([$bas, $bit]);
    echo json_encode(['basari' => true, 'musteriler' => $stmt->fetchAll()]);
    exit;
}

// ── RAPOR: ÜRÜN ──────────────────────────────────────────────────
if ($action === 'rapor_urun') {
    $bas = $_POST['tarih_bas'] ?? date('Y-01-01');
    $bit = $_POST['tarih_bit'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            k.urun_adi,
            SUM(k.miktar) AS toplam_miktar,
            SUM(k.satir_toplam) AS toplam_tutar,
            SUM(k.kdv_tutar) AS kdv_tutar
        FROM fatura_kalemleri k
        JOIN faturalar f ON f.id = k.fatura_id
        WHERE f.tarih BETWEEN ? AND ? AND f.durum != 'iptal'
        GROUP BY k.urun_adi
        ORDER BY toplam_tutar DESC
        LIMIT 100
    ");
    $stmt->execute([$bas, $bit]);
    echo json_encode(['basari' => true, 'urunler' => $stmt->fetchAll()]);
    exit;
}

// ── RAPOR: e-FATURA DURUMU ────────────────────────────────────────
if ($action === 'rapor_efatura') {
    $ozet = $pdo->query("
        SELECT
            SUM(CASE WHEN efatura_durum='GONDERILDI' THEN 1 ELSE 0 END) AS gonderildi,
            SUM(CASE WHEN efatura_durum='BEKLEMEDE' OR efatura_durum='TASLAK' THEN 1 ELSE 0 END) AS beklemede,
            SUM(CASE WHEN efatura_durum='HATA' THEN 1 ELSE 0 END) AS hata
        FROM faturalar
    ")->fetch();

    $faturalar = $pdo->query("
        SELECT f.fatura_no, m.ad_soyad AS musteri_adi, f.alici_unvan, f.tarih, f.toplam, f.ettn, f.efatura_durum AS durum
        FROM faturalar f
        LEFT JOIN musteriler m ON m.id = f.musteri_id
        WHERE f.efatura_durum IS NOT NULL AND f.efatura_durum != 'TASLAK'
        ORDER BY f.tarih DESC
        LIMIT 200
    ")->fetchAll();

    echo json_encode(['basari' => true] + $ozet + ['faturalar' => $faturalar]);
    exit;
}

// ════════════════════════════════════════════════════════
// FATURA PAYLAŞIM TOKEN OLUŞTUR
// ════════════════════════════════════════════════════════
if ($action === 'fatura_paylasim_token') {
    $faturaId = (int)($_POST['fatura_id'] ?? 0);
    if (!$faturaId) { echo json_encode(['basari'=>false,'mesaj'=>'Fatura ID gerekli']); exit; }

    // Mevcut token var mı?
    $mevcut = $pdo->prepare("SELECT paylasim_token FROM faturalar WHERE id = ?");
    $mevcut->execute([$faturaId]);
    $row = $mevcut->fetch(PDO::FETCH_ASSOC);

    if (!$row) { echo json_encode(['basari'=>false,'mesaj'=>'Fatura bulunamadı']); exit; }

    $token = $row['paylasim_token'];
    if (!$token) {
        // Benzersiz token üret
        do {
            $token = bin2hex(random_bytes(24)); // 48 karakter hex
            $check = $pdo->prepare("SELECT id FROM faturalar WHERE paylasim_token = ?");
            $check->execute([$token]);
        } while ($check->fetch());

        $pdo->prepare("UPDATE faturalar SET paylasim_token = ? WHERE id = ?")
            ->execute([$token, $faturaId]);
    }

    echo json_encode(['basari' => true, 'token' => $token]);
    exit;
}

