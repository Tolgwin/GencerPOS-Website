<?php
$pageTitle = 'İletişim - GencerPOS';
$pageDesc = 'GencerPOS ile iletişime geçin. Demo talebi, fiyat teklifi ve teknik destek için bize ulaşın.';
include 'header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>İletişim</h1>
    <p>Sorularınız, demo talepleriniz ve teklifler için bize ulaşın</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-grid">
      <div class="contact-form">
        <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--dark); margin-bottom: 24px;">Bize Yazın</h3>
        <form action="#" method="POST">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label>Ad Soyad</label>
              <input type="text" name="name" placeholder="Adınız Soyadınız" required>
            </div>
            <div class="form-group">
              <label>Telefon</label>
              <input type="tel" name="phone" placeholder="0(5XX) XXX XX XX">
            </div>
          </div>
          <div class="form-group">
            <label>E-posta</label>
            <input type="email" name="email" placeholder="ornek@email.com" required>
          </div>
          <div class="form-group">
            <label>Konu</label>
            <select name="subject">
              <option value="">Konu Seçiniz</option>
              <option value="demo">Demo Talebi</option>
              <option value="teklif">Fiyat Teklifi</option>
              <option value="destek">Teknik Destek</option>
              <option value="diger">Diğer</option>
            </select>
          </div>
          <div class="form-group">
            <label>Mesajınız</label>
            <textarea name="message" placeholder="Mesajınızı buraya yazın..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
            Gönder
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </form>
      </div>

      <div class="contact-info-cards">
        <div class="contact-info-card">
          <div class="contact-info-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.11 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          </div>
          <div>
            <h4>Telefon</h4>
            <p>0(312) 000 00 00<br>0(532) 000 00 00</p>
          </div>
        </div>

        <div class="contact-info-card">
          <div class="contact-info-icon" style="background: #fef3c7; color: #d97706;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <h4>E-posta</h4>
            <p>info@gencerpos.com<br>destek@gencerpos.com</p>
          </div>
        </div>

        <div class="contact-info-card">
          <div class="contact-info-icon" style="background: #dcfce7; color: #16a34a;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <h4>Adres</h4>
            <p>Ankara, Türkiye</p>
          </div>
        </div>

        <div class="contact-info-card">
          <div class="contact-info-icon" style="background: #ede9fe; color: #7c3aed;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div>
            <h4>Çalışma Saatleri</h4>
            <p>Pazartesi - Cumartesi: 09:00 - 18:00<br>Teknik Destek: 7/24</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
