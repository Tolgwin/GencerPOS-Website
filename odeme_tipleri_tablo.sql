-- Ödeme hesapları (Kasa, Banka, POS vb.)
CREATE TABLE IF NOT EXISTS odeme_hesaplari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    tip ENUM('nakit', 'banka', 'pos', 'diger') NOT NULL DEFAULT 'nakit',
    aciklama VARCHAR(255) DEFAULT NULL,
    baslangic_bak DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    renk VARCHAR(7) NOT NULL DEFAULT '#3b82f6',
    ikon VARCHAR(10) NOT NULL DEFAULT '💵',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Hesap hareketleri
CREATE TABLE IF NOT EXISTS hesap_hareketleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hesap_id INT NOT NULL,
    tip ENUM(
        'giris',
        'cikis',
        'transfer_giris',
        'transfer_cikis'
    ) NOT NULL,
    tutar DECIMAL(15, 2) NOT NULL,
    referans_tip ENUM('tahsilat', 'transfer', 'manuel') NOT NULL DEFAULT 'manuel',
    referans_id INT DEFAULT NULL,
    aciklama VARCHAR(255) DEFAULT NULL,
    tarih DATE NOT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hesap_id) REFERENCES odeme_hesaplari(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Transfer kayıtları
CREATE TABLE IF NOT EXISTS hesap_transferler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kaynak_id INT NOT NULL,
    hedef_id INT NOT NULL,
    tutar DECIMAL(15, 2) NOT NULL,
    aciklama VARCHAR(255) DEFAULT NULL,
    tarih DATE NOT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kaynak_id) REFERENCES odeme_hesaplari(id),
    FOREIGN KEY (hedef_id) REFERENCES odeme_hesaplari(id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Varsayılan hesaplar
INSERT INTO odeme_hesaplari (ad, tip, renk, ikon, baslangic_bak)
VALUES ('Kasa', 'nakit', '#10b981', '💵', 0.00),
    ('Banka Hesabı', 'banka', '#3b82f6', '🏦', 0.00),
    ('POS Cihazı', 'pos', '#8b5cf6', '💳', 0.00);