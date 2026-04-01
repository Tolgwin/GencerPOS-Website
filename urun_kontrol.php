<?php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── Yardımcı: JSON cevap ───────────────────────────────────────────────────
function cevap(bool $basari, string $mesaj, array $data = []): void
{
    echo json_encode(array_merge(['basari' => $basari, 'mesaj' => $mesaj], $data));
    exit;
}

// ─── Yardımcı: Stok güncelle ────────────────────────────────────────────────
function stokGuncelle(PDO $pdo, int $urunId): void
{
    $pdo->prepare("
        UPDATE urunler SET stok_adeti = (
            SELECT COALESCE(SUM(
                CASE WHEN hareket_tipi IN ('giris','iade') THEN miktar
                     WHEN hareket_tipi IN ('cikis')        THEN -miktar
                     ELSE 0 END
            ), 0)
            FROM urun_hareketleri WHERE urun_id = ?
        ) WHERE id = ?
    ")->execute([$urunId, $urunId]);
}

switch ($action) {

    // ════════════════════════════════════════════════════════
    // ÜRÜN LİSTESİ
    // ════════════════════════════════════════════════════════
    case 'urun_liste':
        $ara = '%' . ($_GET['ara'] ?? '') . '%';
        $kategori = $_GET['kategori_id'] ?? '';
        $durum = $_GET['durum'] ?? 'aktif';
        $sayfa = max(1, (int) ($_GET['sayfa'] ?? 1));
        $limit = 20;
        $offset = ($sayfa - 1) * $limit;

        $where = "WHERE u.durum = ?";
        $params = [$durum];

        if ($ara !== '%%') {
            $where .= " AND (u.ad LIKE ? OR u.urun_kodu LIKE ?)";
            $params[] = $ara;
            $params[] = $ara;
        }
        if ($kategori) {
            $where .= " AND u.kategori_id = ?";
            $params[] = $kategori;
        }

        $toplam = $pdo->prepare("
            SELECT COUNT(*) FROM urunler u $where
        ");
        $toplam->execute($params);

        $stmt = $pdo->prepare("
            SELECT u.*,
                   k.ad  AS kategori_adi,
                   t.ad  AS tedarikci_adi
            FROM urunler u
            LEFT JOIN kategoriler  k ON u.kategori_id  = k.id
            LEFT JOIN tedarikciler t ON u.tedarikci_id = t.id
            $where
            ORDER BY u.ad
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);

        cevap(true, '', [
            'urunler' => $stmt->fetchAll(),
            'toplam_kayit' => (int) $toplam->fetchColumn(),
            'toplam_sayfa' => ceil($toplam->fetchColumn(0) / $limit),
        ]);

    // ════════════════════════════════════════════════════════
    // ÜRÜN KAYDET (Ekle / Güncelle)
    // ════════════════════════════════════════════════════════
    case 'urun_kaydet':
        $id = (int) ($_POST['id'] ?? 0);
        $urunKodu = trim($_POST['urun_kodu'] ?? '');
        $ad = trim($_POST['ad'] ?? '');
        $kategoriId = (int) ($_POST['kategori_id'] ?? 0) ?: null;
        $tedarikcId = (int) ($_POST['tedarikci_id'] ?? 0) ?: null;
        $alisFiyati = (float) ($_POST['alis_fiyati'] ?? 0);
        $satisFiyati = (float) ($_POST['satis_fiyati'] ?? 0);
        $bayiFiyati = (float) ($_POST['bayi_fiyati'] ?? 0);
        $kdvOrani = (float) ($_POST['kdv_orani'] ?? 18);
        $seriTakip = (int) ($_POST['seri_no_takip'] ?? 0);
        $aciklama = trim($_POST['aciklama'] ?? '');

        if (!$urunKodu || !$ad)
            cevap(false, 'Ürün kodu ve adı zorunludur.');

        // Resim yükleme
        $resimYol = $_POST['mevcut_resim'] ?? null;
        if (!empty($_FILES['resim']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION));
            $izinli = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $izinli))
                cevap(false, 'Geçersiz resim formatı.');
            $klasor = 'uploads/urunler/';
            if (!is_dir($klasor))
                mkdir($klasor, 0755, true);
            $dosyaAdi = uniqid('urun_') . '.' . $ext;
            move_uploaded_file($_FILES['resim']['tmp_name'], $klasor . $dosyaAdi);
            $resimYol = $klasor . $dosyaAdi;
        }

        if ($id) {
            $pdo->prepare("
                UPDATE urunler SET
                    urun_kodu=?, ad=?, kategori_id=?, tedarikci_id=?,
                    alis_fiyati=?, satis_fiyati=?, bayi_fiyati=?,
                    kdv_orani=?, seri_no_takip=?, aciklama=?,
                    resim=COALESCE(?,resim)
                WHERE id=?
            ")->execute([
                        $urunKodu,
                        $ad,
                        $kategoriId,
                        $tedarikcId,
                        $alisFiyati,
                        $satisFiyati,
                        $bayiFiyati,
                        $kdvOrani,
                        $seriTakip,
                        $aciklama,
                        $resimYol,
                        $id
                    ]);
            cevap(true, 'Ürün güncellendi.', ['id' => $id]);
        } else {
            $pdo->prepare("
                INSERT INTO urunler
                    (urun_kodu,ad,kategori_id,tedarikci_id,
                     alis_fiyati,satis_fiyati,bayi_fiyati,
                     kdv_orani,seri_no_takip,aciklama,resim)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                        $urunKodu,
                        $ad,
                        $kategoriId,
                        $tedarikcId,
                        $alisFiyati,
                        $satisFiyati,
                        $bayiFiyati,
                        $kdvOrani,
                        $seriTakip,
                        $aciklama,
                        $resimYol
                    ]);
            cevap(true, 'Ürün eklendi.', ['id' => (int) $pdo->lastInsertId()]);
        }

    // ════════════════════════════════════════════════════════
    // HAREKET EKLE
    // ════════════════════════════════════════════════════════
    case 'hareket_ekle':
        $urunId = (int) $_POST['urun_id'];
        $tip = $_POST['hareket_tipi'];
        $miktar = (float) $_POST['miktar'];
        $birimFiyat = (float) ($_POST['birim_fiyat'] ?? 0);
        $kaynakTip = $_POST['kaynak_tip'] ?? 'diger';
        $kaynakId = (int) ($_POST['kaynak_id'] ?? 0) ?: null;
        $kaynakAd = trim($_POST['kaynak_ad'] ?? '');
        $hedefTip = $_POST['hedef_tip'] ?? 'diger';
        $hedefId = (int) ($_POST['hedef_id'] ?? 0) ?: null;
        $hedefAd = trim($_POST['hedef_ad'] ?? '');
        $faturaId = (int) ($_POST['fatura_id'] ?? 0) ?: null;
        $seriNo = trim($_POST['seri_no'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $tarih = $_POST['tarih'] ?? date('Y-m-d H:i:s');

        if (!$urunId || !$tip || $miktar <= 0)
            cevap(false, 'Eksik bilgi.');

        $pdo->prepare("
            INSERT INTO urun_hareketleri
                (urun_id,hareket_tipi,miktar,birim_fiyat,toplam_tutar,
                 kaynak_tip,kaynak_id,kaynak_ad,
                 hedef_tip,hedef_id,hedef_ad,
                 fatura_id,seri_no,aciklama,tarih)
            VALUES (?,?,?,?,?, ?,?,?, ?,?,?, ?,?,?,?)
        ")->execute([
                    $urunId,
                    $tip,
                    $miktar,
                    $birimFiyat,
                    $miktar * $birimFiyat,
                    $kaynakTip,
                    $kaynakId,
                    $kaynakAd,
                    $hedefTip,
                    $hedefId,
                    $hedefAd,
                    $faturaId,
                    $seriNo ?: null,
                    $aciklama,
                    $tarih
                ]);

        // Seri no takipli ürünse durumu güncelle
        if ($seriNo) {
            $yeniDurum = match ($tip) {
                'giris', 'iade' => 'stokta',
                'cikis' => 'satildi',
                default => 'stokta'
            };
            $pdo->prepare("
                INSERT INTO seri_numaralari (urun_id,seri_no,durum)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE durum=VALUES(durum)
            ")->execute([$urunId, $seriNo, $yeniDurum]);
        }

        stokGuncelle($pdo, $urunId);
        cevap(true, 'Hareket kaydedildi.');

    // ════════════════════════════════════════════════════════
    // HAREKET LİSTESİ
    // ════════════════════════════════════════════════════════
    case 'hareket_liste':
        $urunId = (int) ($_GET['urun_id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT h.*,
                   u.ad AS urun_adi, u.urun_kodu
            FROM urun_hareketleri h
            JOIN urunler u ON h.urun_id = u.id
            WHERE (? = 0 OR h.urun_id = ?)
            ORDER BY h.tarih DESC
            LIMIT 200
        ");
        $stmt->execute([$urunId, $urunId]);
        cevap(true, '', ['hareketler' => $stmt->fetchAll()]);

    // ════════════════════════════════════════════════════════
    // RAPOR
    // ════════════════════════════════════════════════════════
    case 'rapor':
        $tip = $_GET['tip'] ?? 'stok';

        if ($tip === 'stok') {
            $stmt = $pdo->query("
                SELECT u.urun_kodu, u.ad, k.ad AS kategori,
                       u.stok_adeti,
                       u.alis_fiyati,
                       u.satis_fiyati,
                       u.bayi_fiyati,
                       ROUND(u.stok_adeti * u.alis_fiyati,  2) AS stok_alis_degeri,
                       ROUND(u.stok_adeti * u.satis_fiyati, 2) AS stok_satis_degeri,
                       ROUND((u.satis_fiyati - u.alis_fiyati) /
                             NULLIF(u.alis_fiyati,0) * 100, 2) AS kar_marji
                FROM urunler u
                LEFT JOIN kategoriler k ON u.kategori_id = k.id
                WHERE u.durum = 'aktif'
                ORDER BY u.stok_adeti DESC
            ");
        } elseif ($tip === 'hareket_ozet') {
            $bas = $_GET['bas'] ?? date('Y-m-01');
            $bit = $_GET['bit'] ?? date('Y-m-d');
            $stmt = $pdo->prepare("
                SELECT u.urun_kodu, u.ad,
                       SUM(CASE WHEN h.hareket_tipi='giris' THEN h.miktar ELSE 0 END) AS toplam_giris,
                       SUM(CASE WHEN h.hareket_tipi='cikis' THEN h.miktar ELSE 0 END) AS toplam_cikis,
                       SUM(CASE WHEN h.hareket_tipi='cikis' THEN h.toplam_tutar ELSE 0 END) AS satis_cirosu,
                       SUM(CASE WHEN h.hareket_tipi='giris' THEN h.toplam_tutar ELSE 0 END) AS alis_maliyeti
                FROM urun_hareketleri h
                JOIN urunler u ON h.urun_id = u.id
                WHERE DATE(h.tarih) BETWEEN ? AND ?
                GROUP BY u.id
                ORDER BY satis_cirosu DESC
            ");
            $stmt->execute([$bas, $bit]);
        }

        cevap(true, '', ['rapor' => $stmt->fetchAll()]);

    // ════════════════════════════════════════════════════════
    // KATEGORİ / TEDARİKÇİ LİSTELERİ
    // ════════════════════════════════════════════════════════
    case 'kategori_liste':
        $rows = $pdo->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();
        cevap(true, '', ['kategoriler' => $rows]);

    case 'tedarikci_liste':
        $rows = $pdo->query("SELECT * FROM tedarikciler ORDER BY ad")->fetchAll();
        cevap(true, '', ['tedarikciler' => $rows]);

    case 'urun_sil':
        $id = (int) $_POST['id'];
        $pdo->prepare("UPDATE urunler SET durum='pasif' WHERE id=?")->execute([$id]);
        cevap(true, 'Ürün pasife alındı.');

    // ── Fatura ekleme için tüm aktif ürünleri JSON döndür ─────────
    case 'liste_json':
        $stmt = $pdo->query("
            SELECT id, urun_kodu, ad, ad AS urun_adi, satis_fiyati, kdv_orani, stok_adeti, seri_no_takip, birim
            FROM urunler
            WHERE durum = 'aktif'
            ORDER BY ad ASC
        ");
        cevap(true, '', ['urunler' => $stmt->fetchAll()]);
        break;

    // ── Belirli bir ürünün stokta seri numaralarını getir ─────────
    case 'seri_listele':
        if (!session_id()) session_start();
        $urunId = (int)($_POST['urun_id'] ?? 0);
        $durum  = in_array($_POST['durum'] ?? 'stokta', ['stokta','satildi','iade']) ? $_POST['durum'] : 'stokta';
        if (!$urunId) { cevap(false, 'Ürün ID gerekli.'); break; }
        // Süresi dolmuş rezervasyonları temizle
        try { $pdo->exec("UPDATE seri_numaralari SET rezerve_son=NULL, rezerve_session=NULL WHERE rezerve_son IS NOT NULL AND rezerve_son < NOW()"); } catch(Exception $e) {}
        $stmt = $pdo->prepare("
            SELECT id, seri_no, durum
            FROM seri_numaralari
            WHERE urun_id = ? AND durum = ?
              AND (rezerve_son IS NULL OR rezerve_session = ?)
            ORDER BY seri_no ASC
        ");
        $stmt->execute([$urunId, $durum, session_id()]);
        cevap(true, '', ['seriler' => $stmt->fetchAll()]);
        break;

    // ── Ürün kartından tüm seri numaralarını getir (yönetim için) ──
    case 'seri_listele_tumu':
        $urunId = (int)($_POST['urun_id'] ?? 0);
        if (!$urunId) { cevap(false, 'Ürün ID gerekli.'); break; }
        $stmt = $pdo->prepare("SELECT id, seri_no, durum FROM seri_numaralari WHERE urun_id = ? ORDER BY durum, seri_no ASC");
        $stmt->execute([$urunId]);
        cevap(true, '', ['seriler' => $stmt->fetchAll()]);
        break;

    // ── Ürün kartından manuel seri no ekle ─────────────────────────
    case 'seri_ekle_manuel':
        $urunId = (int)($_POST['urun_id'] ?? 0);
        $seriNo = trim($_POST['seri_no'] ?? '');
        if (!$urunId || $seriNo === '') { cevap(false, 'Gerekli alanlar eksik.'); break; }
        $check = $pdo->prepare("SELECT id FROM seri_numaralari WHERE urun_id=? AND seri_no=?");
        $check->execute([$urunId, $seriNo]);
        if ($check->fetch()) { cevap(false, 'Bu seri no zaten kayıtlı.'); break; }
        $pdo->prepare("INSERT INTO seri_numaralari (urun_id, seri_no, durum) VALUES (?,?,'stokta')")->execute([$urunId, $seriNo]);
        cevap(true, 'Seri no eklendi.');
        break;

    // ── Ürün kartından toplu seri no ekle (+1 artırarak) ───────────
    case 'seri_ekle_toplu':
        $urunId = (int)($_POST['urun_id'] ?? 0);
        $seriListeJson = trim($_POST['seri_liste'] ?? '');
        if (!$urunId || $seriListeJson === '') { cevap(false, 'Gerekli alanlar eksik.'); break; }
        $seriListe = json_decode($seriListeJson, true);
        if (!is_array($seriListe) || empty($seriListe)) { cevap(false, 'Geçersiz seri listesi.'); break; }
        $eklenen = 0;
        $atlanan = 0;
        $insert = $pdo->prepare("INSERT IGNORE INTO seri_numaralari (urun_id, seri_no, durum) VALUES (?,?,'stokta')");
        $pdo->beginTransaction();
        try {
            foreach ($seriListe as $sn) {
                $sn = trim((string)$sn);
                if ($sn === '') continue;
                $insert->execute([$urunId, $sn]);
                if ($insert->rowCount() > 0) $eklenen++;
                else $atlanan++;
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            cevap(false, 'İşlem hatası: ' . $e->getMessage());
            break;
        }
        cevap(true, "{$eklenen} seri no eklendi.", ['eklenen' => $eklenen, 'atlanan' => $atlanan]);
        break;

    // ── Seri no sil ────────────────────────────────────────────────
    case 'seri_sil':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { cevap(false, 'ID gerekli.'); break; }
        $row = $pdo->prepare("SELECT durum FROM seri_numaralari WHERE id=?");
        $row->execute([$id]);
        $r = $row->fetch();
        if (!$r) { cevap(false, 'Kayıt bulunamadı.'); break; }
        if ($r['durum'] === 'satildi') { cevap(false, 'Satılmış seri no silinemez.'); break; }
        $pdo->prepare("DELETE FROM seri_numaralari WHERE id=?")->execute([$id]);
        cevap(true, 'Seri no silindi.');
        break;

    // ── Seri no ara (fatura ekle / düzenle için) ───────────────────
    case 'seri_ara':
        if (!session_id()) session_start();
        $seriNo = trim($_POST['seri_no'] ?? '');
        if ($seriNo === '') { cevap(false, 'Seri no boş.'); break; }
        // Süresi dolmuş rezervasyonları temizle
        try { $pdo->exec("ALTER TABLE seri_numaralari ADD COLUMN rezerve_son DATETIME NULL"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE seri_numaralari ADD COLUMN rezerve_session VARCHAR(100) NULL"); } catch(Exception $e) {}
        $pdo->exec("UPDATE seri_numaralari SET rezerve_son=NULL, rezerve_session=NULL WHERE rezerve_son IS NOT NULL AND rezerve_son < NOW()");
        $stmt = $pdo->prepare("
            SELECT sn.id, sn.seri_no, sn.durum, sn.fatura_id, sn.urun_id,
                   sn.rezerve_son, sn.rezerve_session,
                   u.ad AS urun_adi, u.urun_kodu, u.satis_fiyati, u.kdv_orani
            FROM seri_numaralari sn
            JOIN urunler u ON u.id = sn.urun_id
            WHERE sn.seri_no = ?
        ");
        $stmt->execute([$seriNo]);
        $row = $stmt->fetch();
        if (!$row) { cevap(false, 'Bu seri no sistemde kayıtlı değil.'); break; }
        if ($row['durum'] === 'satildi') {
            cevap(false, 'Bu seri no zaten satılmış.', ['seri' => $row]);
            break;
        }
        if ($row['rezerve_son'] && $row['rezerve_session'] !== session_id()) {
            cevap(false, 'Bu seri no şu an başka bir işlemde rezerve edilmiş. Lütfen bekleyin.', ['seri' => $row]);
            break;
        }
        cevap(true, '', ['seri' => $row, 'basari' => true]);
        break;

    // ── Seri no geçici rezerve et ──────────────────────────────────
    case 'seri_rezerve':
        if (!session_id()) session_start();
        $urunId = (int)($_POST['urun_id'] ?? 0);
        $seriNo = trim($_POST['seri_no'] ?? '');
        if (!$urunId || !$seriNo) { cevap(false, 'Gerekli alanlar eksik.'); break; }
        try { $pdo->exec("ALTER TABLE seri_numaralari ADD COLUMN rezerve_son DATETIME NULL"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE seri_numaralari ADD COLUMN rezerve_session VARCHAR(100) NULL"); } catch(Exception $e) {}
        // Süresi dolmuşları temizle
        $pdo->exec("UPDATE seri_numaralari SET rezerve_son=NULL, rezerve_session=NULL WHERE rezerve_son IS NOT NULL AND rezerve_son < NOW()");
        $stmt = $pdo->prepare("SELECT id, durum, rezerve_son, rezerve_session FROM seri_numaralari WHERE urun_id=? AND seri_no=?");
        $stmt->execute([$urunId, $seriNo]);
        $row = $stmt->fetch();
        if (!$row) { cevap(false, 'Seri no bulunamadı.'); break; }
        if ($row['durum'] === 'satildi') { cevap(false, 'Bu seri no zaten satılmış.'); break; }
        if ($row['rezerve_son'] && $row['rezerve_session'] !== session_id()) {
            cevap(false, 'Bu seri no başka bir işlemde rezerve edilmiş.'); break;
        }
        $pdo->prepare("UPDATE seri_numaralari SET rezerve_son=DATE_ADD(NOW(), INTERVAL 15 MINUTE), rezerve_session=? WHERE id=?")
            ->execute([session_id(), $row['id']]);
        cevap(true, 'Rezerve edildi.');
        break;

    // ── Seri no rezervasyonunu iptal et ────────────────────────────
    case 'seri_rezerve_iptal':
        if (!session_id()) session_start();
        $seriListeJson = trim($_POST['seri_liste'] ?? '');
        $seriNolar = json_decode($seriListeJson, true) ?: [];
        if (!empty($seriNolar)) {
            $placeholders = implode(',', array_fill(0, count($seriNolar), '?'));
            $params = array_merge([session_id()], $seriNolar);
            $pdo->prepare("UPDATE seri_numaralari SET rezerve_son=NULL, rezerve_session=NULL WHERE rezerve_session=? AND seri_no IN ($placeholders)")
                ->execute($params);
        } else {
            $pdo->prepare("UPDATE seri_numaralari SET rezerve_son=NULL, rezerve_session=NULL WHERE rezerve_session=?")
                ->execute([session_id()]);
        }
        cevap(true, 'Rezervasyonlar iptal edildi.');
        break;

    default:
        cevap(false, 'Geçersiz işlem.');
}
