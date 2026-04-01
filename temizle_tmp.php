<?php
require 'db.php';

$tablolar = [
    'hesap_hareketleri',
    'hesap_transferler',
    'tahsilatlar',
    'fatura_kalemleri',
    'faturalar',
    'personel_prim',
    'personel_avans',
    'personeller',
    'odeme_hesaplari',
    'servis_kalemleri',
    'servis_kayitlari',
    'tutanak_hurda',
    'tutanak_devir',
    'kullanicilar',
    'roller',
];

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($tablolar as $t) {
        try {
            $pdo->exec("TRUNCATE TABLE `$t`");
            echo "OK: $t\n";
        } catch (PDOException $e) {
            echo "SKIP: $t ({$e->getMessage()})\n";
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "DONE\n";
} catch (PDOException $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
