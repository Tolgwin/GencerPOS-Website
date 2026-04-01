<?php
// ============================================================
//  QNB eSolutions - Konfigürasyon Dosyası
//  Test Ortamı: erpefaturatest2
// ============================================================

return [

    // --- Kimlik Bilgileri ---
    'username' => '3930311899',   // Test kullanıcı adı
    'password' => 'Tolga3583.',           // Test şifresi

    // --- Servis URL'leri ---
    'wsdl_user'      => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl',
    'wsdl_connector' => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl',


    // --- SOAP Seçenekleri ---
    'soap_options' => [
        'trace'              => true,
        'exceptions'         => true,
        'cache_wsdl'         => WSDL_CACHE_NONE,
        'connection_timeout' => 30,
        'encoding'           => 'UTF-8',
        // Test ortamı SSL sertifika sorunları için:
        'stream_context'     => null, // aşağıda dinamik set edilecek
    ],

    // --- UBL XML Dosya Yolu ---
    'ubl_xml_path' => __DIR__ . '/invoice.xml',
];
