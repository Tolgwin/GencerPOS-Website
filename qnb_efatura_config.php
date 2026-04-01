<?php
// ════════════════════════════════════════════════════════
// QNB eSolutions e-Fatura YAPILANDIRMA
// Panel: https://efatura.qnbesolutions.com.tr
// ════════════════════════════════════════════════════════

define('QNB_EF_WSDL', 'https://efatura.qnbesolutions.com.tr/EFaturaService.svc?wsdl');
define('QNB_EF_USERNAME', 'KULLANICI_ADINIZ');   // QNB panelinden
define('QNB_EF_PASSWORD', 'SIFRENIZ');           // QNB panelinden
define('QNB_EF_VKNO', '1234567890');         // Kendi VKN'niz
define('QNB_EF_UNVAN', 'FİRMA ÜNVANINIZ');
define('QNB_EF_ADRES', 'Firma Adresiniz');

// Test / Canlı mod
define('QNB_EF_TEST_MODE', true);  // Canlıya geçince false yap

// Test WSDL (farklı endpoint)
define('QNB_EF_TEST_WSDL', 'https://efaturatest.qnbesolutions.com.tr/EFaturaService.svc?wsdl');
