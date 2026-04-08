<?php
$pageTitle = 'GencerPOS - POS Donanım & Yazılım Çözümleri';
$pageDesc = 'Profesyonel POS cihazları, barkod okuyucular, yazarkasa yazılımları ve e-Fatura çözümleri ile işletmenizi dijitalleştirin.';
include 'header.php';
?>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <h1>İşletmeniz İçin<br><span class="highlight">Akıllı POS Çözümleri</span></h1>
      <p>Donanımdan yazılıma, e-Fatura'dan stok yönetimine kadar tüm ihtiyaçlarınız için tek adres. İşinizi hızlandırın, maliyetlerinizi düşürün.</p>
      <div class="hero-buttons">
        <a href="urunler.php" class="btn btn-primary">
          Ürünleri İncele
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
        <a href="iletisim.php" class="btn btn-outline">Bize Ulaşın</a>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hero-stat-number">500+</div>
          <div class="hero-stat-label">Mutlu Müşteri</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-number">10+</div>
          <div class="hero-stat-label">Yıllık Deneyim</div>
        </div>
        <div class="hero-stat">
          <div class="hero-stat-number">7/24</div>
          <div class="hero-stat-label">Teknik Destek</div>
        </div>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-card">
        <div class="hero-card-header">
          <span class="dot red"></span>
          <span class="dot yellow"></span>
          <span class="dot green"></span>
        </div>
        <div class="hero-card-row">
          <span class="hero-card-label">Günlük Satış</span>
          <span class="hero-card-value blue">₺12.450,00</span>
        </div>
        <div class="hero-card-row">
          <span class="hero-card-label">İşlem Sayısı</span>
          <span class="hero-card-value">47 adet</span>
        </div>
        <div class="hero-card-row">
          <span class="hero-card-label">Tahsilat Oranı</span>
          <span class="hero-card-value green">%94,2</span>
        </div>
        <div class="hero-card-row">
          <span class="hero-card-label">e-Fatura Durumu</span>
          <span class="hero-card-value green">✓ Tamamlandı</span>
        </div>
        <div class="hero-card-row">
          <span class="hero-card-label">Stok Uyarısı</span>
          <span class="hero-card-value">3 ürün</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-badge">Neden GencerPOS?</span>
      <h2>Her Şey Tek Çatı Altında</h2>
      <p>Donanım ve yazılım çözümlerini bir arada sunarak işletmenizin tüm ihtiyaçlarını karşılıyoruz.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <h3>POS Donanımları</h3>
        <p>Dokunmatik POS terminalleri, barkod okuyucular, fiş yazıcıları ve daha fazlası. En son teknoloji, dayanıklı cihazlar.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background: #fef3c7; color: #d97706;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <h3>e-Fatura Çözümleri</h3>
        <p>GİB onaylı e-Fatura, e-Arşiv ve e-İrsaliye entegrasyonu. Otomatik fatura oluşturma ve gönderim.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background: #dcfce7; color: #16a34a;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <h3>Stok Yönetimi</h3>
        <p>Gerçek zamanlı stok takibi, otomatik sipariş uyarıları ve detaylı raporlama ile stoklarınızı kontrol altında tutun.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background: #fce7f3; color: #db2777;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <h3>Müşteri Yönetimi</h3>
        <p>Müşteri kartları, cari hesap takibi, borç-alacak durumu ve detaylı müşteri raporları tek ekranda.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background: #ede9fe; color: #7c3aed;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
        <h3>Ödeme Sistemleri</h3>
        <p>Kredi kartı, nakit, havale ve online ödeme seçenekleri. Entegre sanal POS ve taksitli satış imkanı.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon" style="background: #e0f2fe; color: #0284c7;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 118 2.83"/><polyline points="22 12 22 2 12 2"/></svg>
        </div>
        <h3>Raporlama & Analiz</h3>
        <p>Satış raporları, kar-zarar analizi, personel performansı ve trend analizi ile işletmenizin nabzını tutun.</p>
      </div>
    </div>
  </div>
</section>

<!-- PRODUCTS PREVIEW -->
<section class="section products-section">
  <div class="container">
    <div class="section-header">
      <span class="section-badge">Ürünlerimiz</span>
      <h2>Donanım & Yazılım</h2>
      <p>İşletmenizin büyüklüğüne ve ihtiyaçlarına uygun çözümler sunuyoruz.</p>
    </div>

    <div class="products-grid">
      <div class="product-card">
        <div class="product-img">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="product-info">
          <h3>Dokunmatik POS Terminal</h3>
          <p>15.6" Full HD dokunmatik ekran, hızlı işlemci ve geniş depolama ile kesintisiz satış.</p>
          <a href="urunler.php" class="product-link">Detayları Gör <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
      </div>

      <div class="product-card">
        <div class="product-img" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
          <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        </div>
        <div class="product-info">
          <h3>Termal Fiş Yazıcı</h3>
          <p>Yüksek hızlı baskı, otomatik kesici ve USB/Ethernet bağlantı seçenekleri.</p>
          <a href="urunler.php" class="product-link">Detayları Gör <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
      </div>

      <div class="product-card">
        <div class="product-img" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0);">
          <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div class="product-info">
          <h3>Yazarkasa Yazılımı</h3>
          <p>Kolay kullanım, hızlı satış, e-Fatura entegrasyonu ve çoklu şube desteği.</p>
          <a href="urunler.php" class="product-link">Detayları Gör <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHY US -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-badge">Rakamlarla Biz</span>
      <h2>Güvenilir Çözüm Ortağınız</h2>
    </div>

    <div class="why-grid">
      <div class="why-card">
        <div class="why-number">500+</div>
        <h3>Aktif Müşteri</h3>
        <p>Türkiye genelinde</p>
      </div>
      <div class="why-card">
        <div class="why-number">10+</div>
        <h3>Yıllık Tecrübe</h3>
        <p>Sektörde lider</p>
      </div>
      <div class="why-card">
        <div class="why-number">%99.9</div>
        <h3>Uptime</h3>
        <p>Kesintisiz hizmet</p>
      </div>
      <div class="why-card">
        <div class="why-number">7/24</div>
        <h3>Teknik Destek</h3>
        <p>Her zaman yanınızda</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="container">
    <h2>İşletmenizi Dijitalleştirin</h2>
    <p>Ücretsiz demo için hemen bizimle iletişime geçin. Size en uygun çözümü birlikte belirleyelim.</p>
    <a href="iletisim.php" class="btn btn-primary">
      Ücretsiz Demo Talep Et
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
  </div>
</section>

<?php include 'footer.php'; ?>
