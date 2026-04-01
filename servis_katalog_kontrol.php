<?php
require_once 'db.php';
require_once 'auth.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── LİSTELE ───────────────────────────────────────────────────
        case 'liste':
            $kategori = $_GET['kategori'] ?? '';
            $arama    = trim($_GET['arama'] ?? '');
            $sql = 'SELECT * FROM servis_katalog WHERE aktif=1';
            $params = [];
            if ($kategori) { $sql .= ' AND kategori=?'; $params[] = $kategori; }
            if ($arama)    { $sql .= ' AND (ad LIKE ? OR kod LIKE ?)'; $params[] = "%$arama%"; $params[] = "%$arama%"; }
            $sql .= ' ORDER BY kategori, ad';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            echo json_encode(['success'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ── ARAMA (servis formunda autocomplete) ──────────────────────
        case 'ara':
            $q = trim($_GET['q'] ?? '');
            $kat = $_GET['kategori'] ?? '';
            $sql = 'SELECT * FROM servis_katalog WHERE aktif=1';
            $params = [];
            if ($q)   { $sql .= ' AND (ad LIKE ? OR kod LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
            if ($kat) { $sql .= ' AND kategori=?'; $params[] = $kat; }
            $sql .= ' ORDER BY kategori, ad LIMIT 30';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            echo json_encode(['success'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ── EKLE ──────────────────────────────────────────────────────
        case 'ekle':
            $ad       = strtoupper(trim($_POST['ad'] ?? ''));
            $kategori = $_POST['kategori'] ?? 'yedek_parca';
            $kod      = strtoupper(trim($_POST['kod'] ?? ''));
            $fiyat    = (float)($_POST['birim_fiyat'] ?? 0);
            $kdv      = (float)($_POST['kdv_orani'] ?? 20);
            $birim    = trim($_POST['birim'] ?? 'Adet');
            $aciklama = trim($_POST['aciklama'] ?? '');
            if (!$ad) { echo json_encode(['success'=>false,'mesaj'=>'Ad zorunludur']); break; }
            $st = $pdo->prepare('INSERT INTO servis_katalog (ad,kategori,kod,birim_fiyat,kdv_orani,birim,aciklama) VALUES (?,?,?,?,?,?,?)');
            $st->execute([$ad,$kategori,$kod,$fiyat,$kdv,$birim,$aciklama]);
            echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'mesaj'=>'Kaydedildi']);
            break;

        // ── GÜNCELLE ──────────────────────────────────────────────────
        case 'guncelle':
            $id       = (int)($_POST['id'] ?? 0);
            $ad       = strtoupper(trim($_POST['ad'] ?? ''));
            $kategori = $_POST['kategori'] ?? 'yedek_parca';
            $kod      = strtoupper(trim($_POST['kod'] ?? ''));
            $fiyat    = (float)($_POST['birim_fiyat'] ?? 0);
            $kdv      = (float)($_POST['kdv_orani'] ?? 20);
            $birim    = trim($_POST['birim'] ?? 'Adet');
            $aciklama = trim($_POST['aciklama'] ?? '');
            if (!$id || !$ad) { echo json_encode(['success'=>false,'mesaj'=>'Geçersiz veri']); break; }
            $st = $pdo->prepare('UPDATE servis_katalog SET ad=?,kategori=?,kod=?,birim_fiyat=?,kdv_orani=?,birim=?,aciklama=? WHERE id=?');
            $st->execute([$ad,$kategori,$kod,$fiyat,$kdv,$birim,$aciklama,$id]);
            echo json_encode(['success'=>true,'mesaj'=>'Güncellendi']);
            break;

        // ── SİL (soft-delete) ─────────────────────────────────────────
        case 'sil':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success'=>false,'mesaj'=>'ID eksik']); break; }
            $pdo->prepare('UPDATE servis_katalog SET aktif=0 WHERE id=?')->execute([$id]);
            echo json_encode(['success'=>true,'mesaj'=>'Silindi']);
            break;

        // ── TEK KAYIT ─────────────────────────────────────────────────
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM servis_katalog WHERE id=?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success'=>true,'data'=>$row] : ['success'=>false]);
            break;

        default:
            echo json_encode(['success'=>false,'mesaj'=>'Bilinmeyen action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'mesaj'=>$e->getMessage()]);
}
