-- Kategoriler
CREATE TABLE IF NOT EXISTS kategoriler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    aciklama TEXT,
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- Tedarikçiler
CREATE TABLE IF NOT EXISTS tedarikciler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(150) NOT NULL,
    telefon VARCHAR(20),
    email VARCHAR(100),
    adres TEXT,
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- Ürünler
CREATE TABLE IF NOT EXISTS urunler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_kodu VARCHAR(50) UNIQUE NOT NULL,
    kategori_id INT,
    tedarikci_id INT,
    ad VARCHAR(200) NOT NULL,
    aciklama TEXT,
    alis_fiyati DECIMAL(12, 2) DEFAULT 0,
    satis_fiyati DECIMAL(12, 2) DEFAULT 0,
    bayi_fiyati DECIMAL(12, 2) DEFAULT 0,
    kdv_orani DECIMAL(5, 2) DEFAULT 18,
    stok_adeti DECIMAL(12, 3) DEFAULT 0,
    seri_no_takip TINYINT(1) DEFAULT 0,
    resim VARCHAR(255),
    durum ENUM('aktif', 'pasif') DEFAULT 'aktif',
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP,
    guncelleme DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategoriler(id) ON DELETE
    SET NULL,
        FOREIGN KEY (tedarikci_id) REFERENCES tedarikciler(id) ON DELETE
    SET NULL
);
-- Seri Numaraları
CREATE TABLE IF NOT EXISTS seri_numaralari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_id INT NOT NULL,
    seri_no VARCHAR(100) NOT NULL,
    durum ENUM('stokta', 'satildi', 'iade') DEFAULT 'stokta',
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (urun_id) REFERENCES urunler(id) ON DELETE CASCADE
);
-- Ürün Hareketleri
CREATE TABLE IF NOT EXISTS urun_hareketleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_id INT NOT NULL,
    hareket_tipi ENUM('giris', 'cikis', 'iade', 'sayim', 'transfer') NOT NULL,
    miktar DECIMAL(12, 3) NOT NULL,
    birim_fiyat DECIMAL(12, 2) DEFAULT 0,
    toplam_tutar DECIMAL(14, 2) DEFAULT 0,
    -- Kimden / Kime
    kaynak_tip ENUM('tedarikci', 'musteri', 'depo', 'diger') DEFAULT 'diger',
    kaynak_id INT,
    kaynak_ad VARCHAR(200),
    hedef_tip ENUM('tedarikci', 'musteri', 'depo', 'diger') DEFAULT 'diger',
    hedef_id INT,
    hedef_ad VARCHAR(200),
    -- Bağlantılar
    fatura_id INT,
    seri_no VARCHAR(100),
    aciklama TEXT,
    tarih DATETIME DEFAULT CURRENT_TIMESTAMP,
    kullanici VARCHAR(100),
    FOREIGN KEY (urun_id) REFERENCES urunler(id) ON DELETE CASCADE,
    FOREIGN KEY (fatura_id) REFERENCES faturalar(id) ON DELETE
    SET NULL
);