-- ============================================================
-- FaturaApp - Tam Veritabanı Şeması
-- QNB eSolutions e-Fatura Entegrasyonlu
-- ============================================================

CREATE DATABASE IF NOT EXISTS fatura_db CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE fatura_db;

-- ─────────────────────────────────────
-- MÜŞTERİLER
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS musteriler (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad       VARCHAR(150) NOT NULL,
    email          VARCHAR(150),
    telefon        VARCHAR(30),
    adres          TEXT,
    vergi_no       VARCHAR(20),
    vergi_dairesi  VARCHAR(100),
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum          TINYINT(1) DEFAULT 1
);

-- ─────────────────────────────────────
-- FATURALAR
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS faturalar (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    fatura_no       VARCHAR(30) UNIQUE NOT NULL,
    musteri_id      INT,
    alici_vkn       VARCHAR(20),
    alici_unvan     VARCHAR(200),
    tarih           DATE NOT NULL,
    vade_tarihi     DATE,
    durum           ENUM('beklemede','odendi','iptal') DEFAULT 'beklemede',
    odeme_durumu    ENUM('odenmedi','kismi','odendi') DEFAULT 'odenmedi',
    matrah          DECIMAL(15,2) DEFAULT 0,
    kdv_tutari      DECIMAL(15,2) DEFAULT 0,
    toplam          DECIMAL(15,2) DEFAULT 0,
    ettn            VARCHAR(100),
    efatura_durum   ENUM('TASLAK','GONDERILDI','HATA','IPTAL') DEFAULT 'TASLAK',
    notlar          TEXT,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (musteri_id) REFERENCES musteriler(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────
-- FATURA KALEMLERİ
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS fatura_kalemleri (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    fatura_id     INT NOT NULL,
    urun_adi      VARCHAR(200) NOT NULL,
    urun_kodu     VARCHAR(50),
    miktar        DECIMAL(12,3) DEFAULT 1,
    birim         VARCHAR(10) DEFAULT 'C62',
    birim_fiyat   DECIMAL(15,2) DEFAULT 0,
    kdv_orani     INT DEFAULT 20,
    kdv_tutar     DECIMAL(15,2) DEFAULT 0,
    satir_toplam  DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (fatura_id) REFERENCES faturalar(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────
-- TAHSİLATLAR
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS tahsilatlar (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    musteri_id    INT NOT NULL,
    fatura_id     INT,
    hesap_id      INT,
    tutar         DECIMAL(15,2) NOT NULL,
    odeme_tipi    ENUM('nakit','havale','eft','kredi_karti','cek','senet','diger') DEFAULT 'nakit',
    tarih         DATE NOT NULL,
    aciklama      TEXT,
    olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (musteri_id) REFERENCES musteriler(id),
    FOREIGN KEY (fatura_id)  REFERENCES faturalar(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────
-- ÖDEME HESAPLARI (Kasa/Banka)
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS odeme_hesaplari (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    ad       VARCHAR(100) NOT NULL,
    tip      ENUM('nakit','banka','pos','diger') DEFAULT 'nakit',
    ikon     VARCHAR(10) DEFAULT '💰',
    bakiye   DECIMAL(15,2) DEFAULT 0,
    aciklama TEXT,
    durum    TINYINT(1) DEFAULT 1
);

-- ─────────────────────────────────────
-- KATEGORİLER
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS kategoriler (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    ad        VARCHAR(100) NOT NULL,
    aciklama  TEXT,
    durum     TINYINT(1) DEFAULT 1
);

-- ─────────────────────────────────────
-- TEDARİKÇİLER
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS tedarikciler (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ad              VARCHAR(150) NOT NULL,
    telefon         VARCHAR(30),
    email           VARCHAR(150),
    vergi_no        VARCHAR(20),
    vergi_dairesi   VARCHAR(100),
    adres           TEXT,
    notlar          TEXT,
    durum           TINYINT(1) DEFAULT 1
);

-- ─────────────────────────────────────
-- ÜRÜNLER
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS urunler (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    urun_kodu       VARCHAR(50) UNIQUE NOT NULL,
    ad              VARCHAR(200) NOT NULL,
    kategori_id     INT,
    tedarikci_id    INT,
    aciklama        TEXT,
    alis_fiyati     DECIMAL(15,2) DEFAULT 0,
    satis_fiyati    DECIMAL(15,2) DEFAULT 0,
    bayi_fiyati     DECIMAL(15,2) DEFAULT 0,
    kdv_orani       INT DEFAULT 20,
    stok_adeti      DECIMAL(12,3) DEFAULT 0,
    seri_no_takip   TINYINT(1) DEFAULT 0,
    resim           VARCHAR(255),
    durum           ENUM('aktif','pasif') DEFAULT 'aktif',
    FOREIGN KEY (kategori_id)  REFERENCES kategoriler(id) ON DELETE SET NULL,
    FOREIGN KEY (tedarikci_id) REFERENCES tedarikciler(id) ON DELETE SET NULL
);

-- ─────────────────────────────────────
-- STOK HAREKETLERİ
-- ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS stok_hareketler (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    urun_id       INT NOT NULL,
    hareket_tipi  ENUM('giris','cikis','iade','sayim','transfer') NOT NULL,
    miktar        DECIMAL(12,3) NOT NULL,
    birim_fiyat   DECIMAL(15,2) DEFAULT 0,
    toplam_tutar  DECIMAL(15,2) DEFAULT 0,
    kaynak_tip    VARCHAR(50),
    kaynak_ad     VARCHAR(150),
    hedef_tip     VARCHAR(50),
    hedef_ad      VARCHAR(150),
    seri_no       VARCHAR(100),
    aciklama      TEXT,
    tarih         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (urun_id) REFERENCES urunler(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────
-- ÖRNEK VERİLER
-- ─────────────────────────────────────
INSERT IGNORE INTO odeme_hesaplari (ad, tip, ikon, bakiye) VALUES
    ('Kasa (Nakit)', 'nakit', '💵', 0.00),
    ('Banka Hesabı', 'banka', '🏦', 0.00);

INSERT IGNORE INTO kategoriler (ad, aciklama) VALUES
    ('Genel', 'Genel ürün kategorisi'),
    ('Elektronik', NULL),
    ('Hizmet', 'Hizmet kalemleri');
