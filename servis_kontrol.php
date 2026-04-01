<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
require_once 'db.php';
require_once 'auth.php';

function cevapJson($basari, $mesaj = '', $extra = []) {
    ob_clean();
    echo json_encode(array_merge(['success' => $basari, 'message' => $mesaj], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// Parçaları kaydet + stok düş (eski parçaları önce iade et)
function parcalariKaydet($pdo, $servisId, $parcalarJson) {
    $parcalar = json_decode($parcalarJson, true);
    if (!is_array($parcalar)) $parcalar = [];

    // Eski parçaları al, stoğa geri ekle
    $eskiler = $pdo->prepare("SELECT urun_id, miktar FROM servis_parcalar WHERE servis_id=? AND urun_id IS NOT NULL");
    $eskiler->execute([$servisId]);
    foreach ($eskiler->fetchAll() as $e) {
        $pdo->prepare("UPDATE urunler SET stok_adeti = stok_adeti + ? WHERE id=?")->execute([$e['miktar'], $e['urun_id']]);
    }
    // Eski parçaları sil
    $pdo->prepare("DELETE FROM servis_parcalar WHERE servis_id=?")->execute([$servisId]);

    // Yeni parçaları ekle + stok düş
    foreach ($parcalar as $p) {
        $miktar     = max(0.001, floatval($p['miktar'] ?? 1));
        $birimFiyat = floatval($p['birim_fiyat'] ?? 0);
        $kdv        = floatval($p['kdv'] ?? 0);
        $urunId     = !empty($p['id']) ? (int)$p['id'] : null;
        $ad         = trim($p['ad'] ?? '');
        if (!$ad) continue;
        $pdo->prepare("INSERT INTO servis_parcalar (servis_id, urun_id, urun_adi, miktar, birim_fiyat, kdv_orani) VALUES (?,?,?,?,?,?)")
            ->execute([$servisId, $urunId, $ad, $miktar, $birimFiyat, $kdv]);
        if ($urunId) {
            $pdo->prepare("UPDATE urunler SET stok_adeti = stok_adeti - ? WHERE id=?")->execute([$miktar, $urunId]);
        }
    }
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {

        // ── DURUMLAR ────────────────────────────────────────────────
        case 'get_durumlar':
            $r = $pdo->query("SELECT * FROM servis_durumlar ORDER BY sira ASC")->fetchAll();
            cevapJson(true, '', ['data' => $r]);

        case 'add_durum':
            $ad = trim($_POST['durum_adi'] ?? '');
            if (!$ad) cevapJson(false, 'Durum adı boş olamaz');
            $pdo->prepare("INSERT INTO servis_durumlar (durum_adi,renk,sira,dashboard_goster) VALUES (?,?,?,?)")
                ->execute([$ad, $_POST['renk'] ?? '#667eea', (int)($_POST['sira'] ?? 1), !empty($_POST['dashboard_goster']) ? 1 : 0]);
            cevapJson(true, 'Durum eklendi', ['id' => $pdo->lastInsertId()]);

        case 'update_durum':
            $id = (int)($_POST['id'] ?? 0);
            $ad = trim($_POST['durum_adi'] ?? '');
            if (!$ad) cevapJson(false, 'Durum adı boş olamaz');
            $pdo->prepare("UPDATE servis_durumlar SET durum_adi=?,renk=?,sira=?,dashboard_goster=? WHERE id=?")
                ->execute([$ad, $_POST['renk'] ?? '#667eea', (int)($_POST['sira'] ?? 1), !empty($_POST['dashboard_goster']) ? 1 : 0, $id]);
            cevapJson(true, 'Durum güncellendi');

        case 'delete_durum':
            $id = (int)($_POST['id'] ?? 0);
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM servisler WHERE durum_id=?"); $cnt->execute([$id]);
            if ($cnt->fetchColumn() > 0) cevapJson(false, 'Bu durum servislerde kullanılıyor.');
            $pdo->prepare("DELETE FROM servis_durumlar WHERE id=?")->execute([$id]);
            cevapJson(true, 'Durum silindi');

        // ── MÜŞTERİLER ──────────────────────────────────────────────
        case 'get_musteriler':
            $arama = trim($_GET['arama'] ?? '');
            if ($arama) {
                // Vergi no, telefon, ad ve seri no (önceki servis kayıtları) ile arama
                $stmt = $pdo->prepare("
                    SELECT DISTINCT m.id, m.ad_soyad AS ad, m.telefon, m.email, m.vergi_no
                    FROM musteriler m
                    LEFT JOIN servisler s ON s.musteri_id = m.id
                    WHERE m.ad_soyad LIKE ?
                       OR m.telefon LIKE ?
                       OR m.email LIKE ?
                       OR m.vergi_no LIKE ?
                       OR s.seri_no LIKE ?
                    ORDER BY m.ad_soyad ASC LIMIT 30
                ");
                $stmt->execute(["%$arama%", "%$arama%", "%$arama%", "%$arama%", "%$arama%"]);
            } else {
                $stmt = $pdo->query("SELECT id, ad_soyad AS ad, telefon, email, vergi_no FROM musteriler ORDER BY ad_soyad ASC LIMIT 200");
            }
            cevapJson(true, '', ['data' => $stmt->fetchAll()]);

        case 'add_musteri':
            $ad = trim($_POST['ad_soyad'] ?? '');
            if (!$ad) cevapJson(false, 'Ad Soyad boş olamaz');
            $tel   = trim($_POST['telefon'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $adres = trim($_POST['adres'] ?? '');
            $pdo->prepare("INSERT INTO musteriler (ad_soyad, telefon, email, adres, musteri_tipi) VALUES (?,?,?,?,?)")
                ->execute([$ad, $tel, $email, $adres, $_POST['musteri_tipi'] ?? 'bireysel']);
            $newId = $pdo->lastInsertId();
            if ($tel) {
                $pdo->prepare("INSERT INTO musteri_telefonlar (musteri_id, telefon, etiket, varsayilan) VALUES (?,?,?,1)")
                    ->execute([$newId, $tel, 'Cep']);
            }
            cevapJson(true, 'Müşteri eklendi', ['id' => $newId, 'ad' => $ad, 'telefon' => $tel]);

        // ── ÜRÜNLER ──────────────────────────────────────────────────
        case 'get_urunler':
            $arama = trim($_GET['arama'] ?? '');
            if ($arama) {
                $stmt = $pdo->prepare("SELECT id, urun_kodu, ad, satis_fiyati, kdv_orani, birim, stok_adeti FROM urunler WHERE durum='aktif' AND (ad LIKE ? OR urun_kodu LIKE ?) ORDER BY ad ASC LIMIT 30");
                $stmt->execute(["%$arama%", "%$arama%"]);
            } else {
                $stmt = $pdo->query("SELECT id, urun_kodu, ad, satis_fiyati, kdv_orani, birim, stok_adeti FROM urunler WHERE durum='aktif' ORDER BY ad ASC LIMIT 100");
            }
            cevapJson(true, '', ['data' => $stmt->fetchAll()]);

        // ── CİHAZ TÜRLERİ ───────────────────────────────────────────
        case 'get_cihaz_turleri':
            cevapJson(true, '', ['data' => $pdo->query("SELECT * FROM cihaz_turleri WHERE aktif=1 ORDER BY tur_adi ASC")->fetchAll()]);

        case 'add_cihaz_turu':
            $ad = trim($_POST['tur_adi'] ?? '');
            if (!$ad) cevapJson(false, 'Cihaz türü adı boş olamaz');
            $pdo->prepare("INSERT INTO cihaz_turleri (tur_adi) VALUES (?)")->execute([$ad]);
            cevapJson(true, 'Cihaz türü eklendi', ['id' => $pdo->lastInsertId()]);

        case 'update_cihaz_turu':
            $id = (int)($_POST['id'] ?? 0); $ad = trim($_POST['tur_adi'] ?? '');
            if (!$ad) cevapJson(false, 'Cihaz türü adı boş olamaz');
            $pdo->prepare("UPDATE cihaz_turleri SET tur_adi=? WHERE id=?")->execute([$ad, $id]);
            cevapJson(true, 'Cihaz türü güncellendi');

        case 'delete_cihaz_turu':
            $pdo->prepare("UPDATE cihaz_turleri SET aktif=0 WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
            cevapJson(true, 'Cihaz türü silindi');

        // ── MARKALAR ────────────────────────────────────────────────
        case 'get_markalar':
            cevapJson(true, '', ['data' => $pdo->query("SELECT * FROM markalar WHERE aktif=1 ORDER BY marka_adi ASC")->fetchAll()]);

        case 'add_marka':
            $ad = trim($_POST['marka_adi'] ?? '');
            if (!$ad) cevapJson(false, 'Marka adı boş olamaz');
            $pdo->prepare("INSERT INTO markalar (marka_adi) VALUES (?)")->execute([$ad]);
            cevapJson(true, 'Marka eklendi', ['id' => $pdo->lastInsertId()]);

        case 'update_marka':
            $id = (int)($_POST['id'] ?? 0); $ad = trim($_POST['marka_adi'] ?? '');
            if (!$ad) cevapJson(false, 'Marka adı boş olamaz');
            $pdo->prepare("UPDATE markalar SET marka_adi=? WHERE id=?")->execute([$ad, $id]);
            cevapJson(true, 'Marka güncellendi');

        case 'delete_marka':
            $id = (int)($_POST['id'] ?? 0);
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM modeller WHERE marka_id=? AND aktif=1"); $cnt->execute([$id]);
            if ($cnt->fetchColumn() > 0) cevapJson(false, 'Bu markaya ait aktif modeller var.');
            $pdo->prepare("UPDATE markalar SET aktif=0 WHERE id=?")->execute([$id]);
            cevapJson(true, 'Marka silindi');

        // ── MODELLER ────────────────────────────────────────────────
        case 'get_modeller':
            $markaId = (int)($_GET['marka_id'] ?? 0);
            if ($markaId) {
                $stmt = $pdo->prepare("SELECT mo.*, ma.marka_adi FROM modeller mo JOIN markalar ma ON mo.marka_id=ma.id WHERE mo.marka_id=? AND mo.aktif=1 ORDER BY mo.model_adi");
                $stmt->execute([$markaId]);
            } else {
                $stmt = $pdo->query("SELECT mo.*, ma.marka_adi FROM modeller mo JOIN markalar ma ON mo.marka_id=ma.id WHERE mo.aktif=1 ORDER BY ma.marka_adi, mo.model_adi");
            }
            cevapJson(true, '', ['data' => $stmt->fetchAll()]);

        case 'add_model':
            $markaId = (int)($_POST['marka_id'] ?? 0); $ad = trim($_POST['model_adi'] ?? '');
            if (!$markaId) cevapJson(false, 'Marka seçilmedi');
            if (!$ad) cevapJson(false, 'Model adı boş olamaz');
            $pdo->prepare("INSERT INTO modeller (marka_id, model_adi) VALUES (?,?)")->execute([$markaId, $ad]);
            cevapJson(true, 'Model eklendi', ['id' => $pdo->lastInsertId()]);

        case 'update_model':
            $id = (int)($_POST['id'] ?? 0); $markaId = (int)($_POST['marka_id'] ?? 0); $ad = trim($_POST['model_adi'] ?? '');
            if (!$markaId) cevapJson(false, 'Marka seçilmedi');
            if (!$ad) cevapJson(false, 'Model adı boş olamaz');
            $pdo->prepare("UPDATE modeller SET marka_id=?, model_adi=? WHERE id=?")->execute([$markaId, $ad, $id]);
            cevapJson(true, 'Model güncellendi');

        case 'delete_model':
            $pdo->prepare("UPDATE modeller SET aktif=0 WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
            cevapJson(true, 'Model silindi');

        // ── SERVİSLER ───────────────────────────────────────────────
        case 'get_servisler':
            $arama   = trim($_GET['arama'] ?? '');
            $durumId = (int)($_GET['durum_id'] ?? 0);
            $limit   = max(1, (int)($_GET['limit'] ?? 50));
            $offset  = max(0, (int)($_GET['offset'] ?? 0));
            $where   = ['1=1']; $params = [];
            if ($arama) {
                $where[] = '(m.ad_soyad LIKE ? OR s.seri_no LIKE ? OR s.marka LIKE ? OR s.model LIKE ?)';
                $params  = ["%$arama%","%$arama%","%$arama%","%$arama%"];
            }
            if ($durumId) { $where[] = 's.durum_id=?'; $params[] = $durumId; }
            $ws = implode(' AND ', $where);
            $toplamStmt = $pdo->prepare("SELECT COUNT(*) FROM servisler s LEFT JOIN musteriler m ON s.musteri_id=m.id WHERE $ws");
            $toplamStmt->execute($params);
            $stmt = $pdo->prepare("SELECT s.*, m.ad_soyad AS musteri_adi, m.telefon AS musteri_telefon, d.durum_adi, d.renk AS durum_renk FROM servisler s LEFT JOIN musteriler m ON s.musteri_id=m.id LEFT JOIN servis_durumlar d ON s.durum_id=d.id WHERE $ws ORDER BY s.kayit_tarihi DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            cevapJson(true, '', ['data' => $stmt->fetchAll(), 'toplam' => (int)$toplamStmt->fetchColumn()]);

        case 'get_servis':
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT s.*, m.ad_soyad AS musteri_adi, m.telefon AS musteri_telefon, m.email AS musteri_email, m.adres AS musteri_adres, m.vergi_dairesi AS musteri_vergi_dairesi, m.vergi_no AS musteri_vergi_no, d.durum_adi, d.renk AS durum_renk FROM servisler s LEFT JOIN musteriler m ON s.musteri_id=m.id LEFT JOIN servis_durumlar d ON s.durum_id=d.id WHERE s.id=?");
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            if (!$r) cevapJson(false, 'Servis bulunamadı');
            // Parçaları da getir
            $pStmt = $pdo->prepare("SELECT * FROM servis_parcalar WHERE servis_id=? ORDER BY id");
            $pStmt->execute([$id]);
            $r['parcalar'] = $pStmt->fetchAll();
            cevapJson(true, '', ['data' => $r]);

        case 'add_servis':
            $musteriId = (int)($_POST['musteri_id'] ?? 0);
            if (!$musteriId) cevapJson(false, 'Müşteri seçilmedi');
            $pdo->prepare("INSERT INTO servisler (musteri_id,cihaz_turu,marka,model,seri_no,sikayet,yapilan_islem,durum_id,tutar,teslim_tarihi,notlar) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$musteriId, trim($_POST['cihaz_turu']??''), trim($_POST['marka']??''), trim($_POST['model']??''), trim($_POST['seri_no']??''), trim($_POST['sikayet']??''), trim($_POST['yapilan_islem']??''), (int)($_POST['durum_id']??1), floatval($_POST['tutar']??0), $_POST['teslim_tarihi']?:null, trim($_POST['notlar']??'')]);
            $newId = $pdo->lastInsertId();
            parcalariKaydet($pdo, $newId, $_POST['parcalar'] ?? '[]');
            cevapJson(true, 'Servis kaydı eklendi', ['id' => $newId]);

        case 'update_servis':
            $id = (int)($_POST['id'] ?? 0);
            $musteriId = (int)($_POST['musteri_id'] ?? 0);
            if (!$musteriId) cevapJson(false, 'Müşteri seçilmedi');
            $pdo->prepare("UPDATE servisler SET musteri_id=?,cihaz_turu=?,marka=?,model=?,seri_no=?,sikayet=?,yapilan_islem=?,durum_id=?,tutar=?,teslim_tarihi=?,notlar=? WHERE id=?")
                ->execute([$musteriId, trim($_POST['cihaz_turu']??''), trim($_POST['marka']??''), trim($_POST['model']??''), trim($_POST['seri_no']??''), trim($_POST['sikayet']??''), trim($_POST['yapilan_islem']??''), (int)($_POST['durum_id']??1), floatval($_POST['tutar']??0), $_POST['teslim_tarihi']?:null, trim($_POST['notlar']??''), $id]);
            parcalariKaydet($pdo, $id, $_POST['parcalar'] ?? '[]');
            cevapJson(true, 'Servis güncellendi');

        case 'delete_servis':
            $id = (int)($_POST['id'] ?? 0);
            // Stokları iade et
            $eskiler = $pdo->prepare("SELECT urun_id, miktar FROM servis_parcalar WHERE servis_id=? AND urun_id IS NOT NULL");
            $eskiler->execute([$id]);
            foreach ($eskiler->fetchAll() as $e) {
                $pdo->prepare("UPDATE urunler SET stok_adeti = stok_adeti + ? WHERE id=?")->execute([$e['miktar'], $e['urun_id']]);
            }
            $pdo->prepare("DELETE FROM servisler WHERE id=?")->execute([$id]);
            cevapJson(true, 'Servis silindi');

        case 'get_istatistikler':
            $r = $pdo->query("SELECT d.id, d.durum_adi, d.renk, d.sira, COUNT(s.id) AS sayi FROM servis_durumlar d LEFT JOIN servisler s ON d.id=s.durum_id GROUP BY d.id ORDER BY d.sira")->fetchAll();
            cevapJson(true, '', ['data' => $r]);

        default:
            cevapJson(false, 'Geçersiz işlem: ' . $action);
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
ob_end_flush();
