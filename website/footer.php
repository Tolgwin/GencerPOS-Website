<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-about">
        <a href="index.php" class="logo">
          <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="36" height="36" rx="8" fill="#1a73e8"/>
            <path d="M8 12h20v2H8zM8 17h14v2H8zM8 22h18v2H8z" fill="#fff"/>
            <circle cx="28" cy="23" r="4" fill="#fff" opacity="0.8"/>
          </svg>
          Gencer<span>POS</span>
        </a>
        <p>Profesyonel POS donanım ve yazılım çözümleri ile işletmenizi dijitalleştirin. Güvenilir, hızlı ve kolay kullanım.</p>
      </div>

      <div>
        <h4>Hızlı Bağlantılar</h4>
        <ul>
          <li><a href="index.php">Anasayfa</a></li>
          <li><a href="urunler.php">Ürünler</a></li>
          <li><a href="hakkimizda.php">Hakkımızda</a></li>
          <li><a href="iletisim.php">İletişim</a></li>
        </ul>
      </div>

      <div>
        <h4>Çözümler</h4>
        <ul>
          <li><a href="urunler.php">POS Cihazları</a></li>
          <li><a href="urunler.php">Barkod Okuyucular</a></li>
          <li><a href="urunler.php">Yazarkasa Yazılımı</a></li>
          <li><a href="urunler.php">e-Fatura Sistemi</a></li>
        </ul>
      </div>

      <div>
        <h4>İletişim</h4>
        <ul class="footer-contact">
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
            <span>0(312) 000 00 00</span>
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>info@gencerpos.com</span>
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>Ankara, Türkiye</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> GencerPOS. Tüm hakları saklıdır.</span>
      <div class="footer-social">
        <a href="#" aria-label="Instagram">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </a>
        <a href="#" aria-label="LinkedIn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        </a>
        <a href="#" aria-label="Twitter">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
        </a>
      </div>
    </div>
  </div>
</footer>

<script>
  // Navbar scroll effect
  window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10);
  });
</script>
</body>
</html>
