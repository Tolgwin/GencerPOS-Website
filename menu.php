<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$mevcutSayfa = basename($_SERVER['PHP_SELF']);

$menuGruplar = [
    [
        'baslik' => 'SATIŞ',
        'items' => [
            ['href' => 'fatura_liste.php',    'label' => 'Faturalar',       'icon' => 'file-text',   'izin' => 'fatura'],
            ['href' => 'fatura_ekle.php',     'label' => 'Fatura Ekle',     'icon' => 'plus-circle', 'izin' => 'fatura'],
            ['href' => 'musteri_liste.php',   'label' => 'Müşteriler',      'icon' => 'users',       'izin' => 'musteri'],
            ['href' => 'servis.php',          'label' => 'Servis',          'icon' => 'tool',        'izin' => 'servis'],
            ['href' => 'tutanak.php',         'label' => 'Tutanak',         'icon' => 'clipboard',   'izin' => 'tutanak'],
        ],
    ],
    [
        'baslik' => 'STOK',
        'items' => [
            ['href' => 'urunler.php',         'label' => 'Ürünler',         'icon' => 'package',     'izin' => 'urun'],
            ['href' => 'kategoriler.php',     'label' => 'Kategoriler',     'icon' => 'tag',         'izin' => 'urun'],
            ['href' => 'servis_katalog.php',  'label' => 'Servis Kataloğu', 'icon' => 'book-open',   'izin' => 'servis'],
        ],
    ],
    [
        'baslik' => 'İŞLETME',
        'items' => [
            ['href' => 'odeme_hesaplari.php', 'label' => 'Kasa & Hesaplar', 'icon' => 'credit-card', 'izin' => 'kasa'],
            ['href' => 'raporlar.php',        'label' => 'Raporlar',        'icon' => 'bar-chart-2', 'izin' => 'rapor'],
            ['href' => 'personel_liste.php',  'label' => 'Personel',        'icon' => 'briefcase',   'izin' => 'personel'],
        ],
    ],
    [
        'baslik' => 'SİSTEM',
        'items' => [
            ['href' => 'kullanici_yonetim.php','label'=> 'Kullanıcılar',   'icon' => 'user-check',  'izin' => 'kullanici'],
            ['href' => 'ayarlar.php',          'label' => 'Ayarlar',        'icon' => 'settings',    'izin' => 'ayarlar'],
            ['href' => 'import_export.php',    'label' => 'Import / Export','icon' => 'upload',      'izin' => 'import_export'],
            ['href' => 'db_yonetim.php',       'label' => 'DB Yönetim',     'icon' => 'database',    'izin' => 'db'],
            ['href' => 'cikis.php',            'label' => 'Çıkış',          'icon' => 'log-out',     'izin' => null],
        ],
    ],
];

// Aktif etiket
$aktifLabel = '—';
$aktifIcon  = '';
foreach ($menuGruplar as $g) {
    foreach ($g['items'] as $item) {
        if ($item['href'] === $mevcutSayfa) {
            $aktifLabel = $item['label'];
            $aktifIcon  = $item['icon'];
        }
    }
}

// SVG ikonları (Feather-style)
function menuIcon(string $name): string {
    $icons = [
        'file-text'   => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'plus-circle' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
        'users'       => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'tool'        => '<svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'clipboard'   => '<svg viewBox="0 0 24 24"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>',
        'package'     => '<svg viewBox="0 0 24 24"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        'tag'         => '<svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
        'book-open'   => '<svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
        'credit-card' => '<svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'bar-chart-2' => '<svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'briefcase'   => '<svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'settings'    => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'upload'      => '<svg viewBox="0 0 24 24"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>',
        'database'    => '<svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        'log-out'     => '<svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'user-check'  => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>',
    ];
    $svg = $icons[$name] ?? '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/></svg>';
    return str_replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ', $svg);
}
?>

<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --sb-w:        240px;
        --sb-bg:       #0d1117;
        --sb-surface:  #161b22;
        --sb-border:   #21262d;
        --sb-text:     #8b949e;
        --sb-text-h:   #e6edf3;
        --sb-active:   #1f6feb;
        --sb-active-bg:#0d419d22;
        --tb-h:        58px;
        --tb-bg:       #ffffff;
        --accent:      #1f6feb;
        --accent2:     #388bfd;
        --tr:          .18s ease;
    }

    html, body { height: 100%; }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        background: #f0f4f8;
        color: #1c2333;
        display: flex;
        min-height: 100vh;
    }

    /* ══ SIDEBAR ══ */
    .sidebar {
        width: var(--sb-w);
        background: var(--sb-bg);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0; left: 0;
        height: 100vh;
        z-index: 300;
        transition: transform var(--tr);
        overflow: hidden;
    }

    /* Logo */
    .sb-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 18px;
        border-bottom: 1px solid var(--sb-border);
        text-decoration: none;
        flex-shrink: 0;
    }

    .sb-logo-mark {
        width: 38px; height: 38px;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(31,111,235,.35);
    }

    .sb-logo-mark svg {
        width: 20px; height: 20px;
        stroke: #fff; fill: none;
        stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    }

    .sb-logo-info { line-height: 1.2; }
    .sb-logo-name { font-size: 15px; font-weight: 700; color: #e6edf3; letter-spacing: -.2px; }
    .sb-logo-sub  { font-size: 10px; color: #484f58; margin-top: 1px; }

    /* Nav */
    .sb-nav {
        flex: 1;
        padding: 12px 10px 8px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #21262d transparent;
    }

    .sb-nav::-webkit-scrollbar { width: 4px; }
    .sb-nav::-webkit-scrollbar-thumb { background: #21262d; border-radius: 4px; }

    .sb-group { margin-bottom: 6px; }

    .sb-group-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        color: #484f58;
        padding: 8px 10px 4px;
    }

    .sb-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        text-decoration: none;
        color: var(--sb-text);
        font-size: 13.5px;
        font-weight: 500;
        transition: background var(--tr), color var(--tr);
        margin-bottom: 1px;
        position: relative;
        white-space: nowrap;
    }

    .sb-link:hover {
        background: var(--sb-surface);
        color: var(--sb-text-h);
    }

    .sb-link.aktif {
        background: var(--sb-active-bg);
        color: #79c0ff;
        font-weight: 600;
    }

    .sb-link.aktif::before {
        content: '';
        position: absolute;
        left: 0; top: 25%; bottom: 25%;
        width: 3px;
        background: var(--accent2);
        border-radius: 0 3px 3px 0;
    }

    .sb-link svg {
        width: 16px; height: 16px;
        flex-shrink: 0;
        opacity: .7;
        transition: opacity var(--tr);
    }

    .sb-link:hover svg, .sb-link.aktif svg { opacity: 1; }

    .sb-sep {
        height: 1px;
        background: var(--sb-border);
        margin: 6px 10px;
    }

    /* Footer */
    .sb-footer {
        padding: 14px 18px;
        border-top: 1px solid var(--sb-border);
        flex-shrink: 0;
    }

    .sb-user {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sb-avatar {
        width: 32px; height: 32px;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }

    .sb-user-name { font-size: 12.5px; font-weight: 600; color: #8b949e; }
    .sb-user-role { font-size: 10px; color: #484f58; }

    /* ══ TOPBAR ══ */
    .topbar {
        position: fixed;
        top: 0; left: var(--sb-w); right: 0;
        height: var(--tb-h);
        background: var(--tb-bg);
        border-bottom: 1px solid #e5e9f0;
        display: flex;
        align-items: center;
        padding: 0 20px;
        z-index: 200;
        gap: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }

    .tb-hamburger {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 6px;
        border-radius: 8px;
        color: #555;
    }
    .tb-hamburger:hover { background: #f0f4f8; }
    .tb-hamburger svg { width: 20px; height: 20px; display: block; }

    .tb-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
    }

    .tb-home {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px; height: 28px;
        border-radius: 7px;
        background: #f0f4f8;
        text-decoration: none;
        transition: background var(--tr);
    }
    .tb-home:hover { background: #e2e8f0; }
    .tb-home svg { width: 14px; height: 14px; stroke: #64748b; }

    .tb-sep-icon { color: #c8d0da; font-size: 15px; font-weight: 300; }

    .tb-page-icon { display: flex; align-items: center; }
    .tb-page-icon svg { width: 15px; height: 15px; stroke: var(--accent); margin-right: 6px; }

    .tb-page-name { font-weight: 600; color: #1c2333; font-size: 14px; }

    .tb-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tb-date {
        font-size: 12px;
        color: #6b7280;
        background: #f8fafc;
        padding: 5px 14px;
        border-radius: 20px;
        border: 1px solid #e5e9f0;
        white-space: nowrap;
    }

    .tb-divider {
        width: 1px; height: 22px;
        background: #e5e9f0;
    }

    .tb-avatar {
        width: 34px; height: 34px;
        background: linear-gradient(135deg, var(--accent), var(--accent2));
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: 13px; font-weight: 700;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* ══ İÇERİK ALANI ══ */
    .sayfa-icerik {
        margin-left: var(--sb-w);
        margin-top: var(--tb-h);
        flex: 1;
        padding: 24px 22px;
        min-height: calc(100vh - var(--tb-h));
        width: calc(100% - var(--sb-w));
    }

    /* ══ OVERLAy & MOBİL ══ */
    .sb-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.55);
        z-index: 299;
        backdrop-filter: blur(2px);
    }

    @media (max-width: 860px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); }
        .sb-overlay.open { display: block; }
        .topbar { left: 0; }
        .tb-hamburger { display: flex; align-items: center; }
        .sayfa-icerik { margin-left: 0; width: 100%; padding: 16px 14px; }
        .tb-date { display: none; }
    }

    @media (max-width: 480px) {
        .tb-divider { display: none; }
    }
</style>

<!-- OVERLAY -->
<div class="sb-overlay" id="sbOverlay" onclick="sbKapat()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <a href="fatura_liste.php" class="sb-logo">
        <div class="sb-logo-mark">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="sb-logo-info">
            <div class="sb-logo-name">FaturaApp</div>
            <div class="sb-logo-sub">Yönetim Paneli</div>
        </div>
    </a>

    <nav class="sb-nav">
        <?php foreach ($menuGruplar as $gi => $grup):
            $gorunurItems = array_filter($grup['items'], fn($item) =>
                $item['izin'] === null || izinVar($item['izin'])
            );
            if (empty($gorunurItems)) continue;
        ?>
            <?php if ($gi > 0): ?><div class="sb-sep"></div><?php endif; ?>
            <div class="sb-group">
                <div class="sb-group-label"><?= $grup['baslik'] ?></div>
                <?php foreach ($gorunurItems as $item): ?>
                    <a href="<?= $item['href'] ?>"
                       class="sb-link <?= $mevcutSayfa === $item['href'] ? 'aktif' : '' ?>">
                        <?= menuIcon($item['icon']) ?>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar"><?= mb_strtoupper(mb_substr($_SESSION['ad_soyad'] ?? 'A', 0, 1)) ?></div>
            <div>
                <div class="sb-user-name"><?= htmlspecialchars($_SESSION['ad_soyad'] ?? 'Admin') ?></div>
                <div class="sb-user-role"><?= htmlspecialchars($_SESSION['rol_adi'] ?? '') ?></div>
            </div>
        </div>
    </div>

</aside>

<!-- TOPBAR -->
<div class="topbar">
    <button class="tb-hamburger" onclick="sbAc()" aria-label="Menü">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <div class="tb-breadcrumb">
        <a href="fatura_liste.php" class="tb-home" title="Ana Sayfa">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>
        <span class="tb-sep-icon">/</span>
        <span class="tb-page-icon">
            <?= menuIcon($aktifIcon) ?>
        </span>
        <span class="tb-page-name"><?= htmlspecialchars($aktifLabel) ?></span>
    </div>

    <div class="tb-right">
        <div class="tb-date" id="tbTarih"></div>
        <div class="tb-divider"></div>
        <div class="tb-avatar" title="Kullanıcı">A</div>
    </div>
</div>

<!-- İÇERİK ALANI -->
<div class="sayfa-icerik">

<script>
    (function () {
        const el = document.getElementById('tbTarih');
        if (!el) return;
        el.textContent = new Date().toLocaleDateString('tr-TR', {
            weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'
        });
    })();

    function sbAc() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sbOverlay').classList.add('open');
    }
    function sbKapat() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sbOverlay').classList.remove('open');
    }
</script>
