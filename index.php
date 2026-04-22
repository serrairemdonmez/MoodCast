<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/lang.php';

startSecureSession();
$csrfToken  = getCsrfToken();
$isLoggedIn = isLoggedIn();
$username   = $isLoggedIn ? e($_SESSION['username']) : '';
$lang       = getLang();
$dir        = langDir();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Tema: cookie'den oku
$theme = isset($_COOKIE['mc_theme']) ? ($_COOKIE['mc_theme'] === 'light' ? 'light' : 'dark') : 'dark';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= t('tagline') ?>">
    <meta name="theme-color" content="#7C3AED">
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    <title><?= t('app_name') ?> — <?= t('tagline') ?></title>
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Leaflet Harita -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/light.css" rel="stylesheet" id="light-css" <?= $theme === 'dark' ? 'disabled' : '' ?>>
    <!-- Arapça/Farsça font -->
    <?php if (in_array($lang, ['ar','fa'])): ?>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Arabic', 'Outfit', sans-serif !important; }
    </style>
    <?php endif; ?>
    <!-- Dil verisini JS'e aktar -->
    <script>
        window.MC_LANG = '<?= $lang ?>';
        window.MC_DIR  = '<?= $dir ?>';
        window.MC_TRANSLATIONS = <?= json_encode($TRANSLATIONS[$lang], JSON_UNESCAPED_UNICODE) ?>;
        window.MC_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
    </script>
</head>
<body data-theme="<?= $theme ?>">

<canvas id="weather-canvas"></canvas>

<!-- ══ SPLASH ═══════════════════════════════════════════════════════════════ -->
<div id="splash" class="splash-overlay" style="display:none">
    <button class="splash-skip-btn" id="splash-skip">Geç ×</button>

    <div class="splash-slide on" data-s="1">
        <div class="splash-big-icon">🎵</div>
        <h1 class="splash-main-title">MoodCast'e<br>Hoş Geldiniz!</h1>
        <p class="splash-main-desc">Hava durumuna göre müzik öneren akıllı sistem. Şehrini gir, ruh haline uygun müziği keşfet.</p>
        <button class="splash-main-btn" id="splash-btn-1">Başla <i class="bi bi-arrow-right"></i></button>
    </div>

    <div class="splash-slide" data-s="2">
        <div class="splash-big-icon">🎧</div>
        <h1 class="splash-main-title">Müziği Keşfet</h1>
        <p class="splash-main-desc">iTunes'dan gerçek şarkılar tarayıcında çalar. Hava durumuna özel seçki!</p>
        <button class="splash-main-btn" id="splash-btn-2">Devam <i class="bi bi-arrow-right"></i></button>
    </div>

    <div class="splash-slide" data-s="3">
        <div class="splash-big-icon">🌍</div>
        <h1 class="splash-main-title">Şehrini Gir</h1>
        <p class="splash-main-desc">İstanbul, Paris, Tokyo... Haritadan seç veya yaz. 4 dil desteği!</p>
        <button class="splash-main-btn splash-main-btn-green" id="splash-btn-3">
            <i class="bi bi-play-fill"></i> Hemen Başla!
        </button>
    </div>

    <div class="splash-dots-row">
        <span class="splash-dot-item on" data-s="1"></span>
        <span class="splash-dot-item" data-s="2"></span>
        <span class="splash-dot-item" data-s="3"></span>
    </div>
</div>

<!-- ══ NAVBAR ════════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-broadcast me-1"></i><?= t('app_name') ?>
        </a>
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-house me-1"></i><?= t('nav_home') ?>
                    </a>
                </li>
                <?php if ($isLoggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#favModal">
                        <i class="bi bi-heart me-1"></i><?= t('nav_favorites') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#favCitiesModal">
                        <i class="bi bi-geo-heart me-1"></i><?= t('nav_fav_cities') ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Saat -->
            <div class="clock-wrap me-2">
                <span class="clock-time" id="clock-time">--:--:--</span>
                <span class="clock-date" id="clock-date"></span>
            </div>

            <!-- Dil Seçici -->
            <div class="lang-switcher me-2">
                <?php foreach(['tr'=>'🇹🇷','en'=>'🇬🇧','ar'=>'🇸🇦','fa'=>'🇮🇷'] as $code => $flag): ?>
                <a href="?lang=<?= $code ?>" class="lang-btn <?= $lang === $code ? 'active' : '' ?>"
                   title="<?= strtoupper($code) ?>"><?= $flag ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Dark/Light Toggle -->
            <button class="theme-toggle me-2" id="theme-toggle" title="<?= t('mode_dark') ?>">
                <i class="bi bi-<?= $theme === 'dark' ? 'sun' : 'moon' ?>-fill"></i>
            </button>

            <!-- Auth -->
            <div id="auth-btns" class="d-flex gap-2 <?= $isLoggedIn ? 'd-none' : '' ?>">
                <button class="btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <?= t('btn_login') ?>
                </button>
                <button class="btn-register" data-bs-toggle="modal" data-bs-target="#regModal">
                    <?= t('btn_register') ?>
                </button>
            </div>
            <div id="user-info" class="d-flex align-items-center gap-2 <?= $isLoggedIn ? '' : 'd-none' ?>">
                <a href="profil.php" style="color:var(--accent);font-weight:700;font-size:.88rem;text-decoration:none">
                    <i class="bi bi-person-circle me-1"></i>
                    <span id="nav-user"><?= $username ?></span>
                </a>
                <button class="btn-login" id="btn-logout"
                        style="border-color:#f87171;color:#f87171">
                    <?= t('btn_logout') ?>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- ══ HERO ══════════════════════════════════════════════════════════════════ -->
<section class="hero">
    <div class="container">
        <div class="hero-pill"><i class="bi bi-music-note-beamed"></i> iTunes</div>
        <h1 class="hero-title">
            <?= t('hero_title_1') ?><br>
            <span class="grad"><?= t('hero_title_2') ?></span>
        </h1>
        <p class="hero-sub"><?= t('hero_sub') ?></p>
        <span class="greeting" id="greeting"></span>

        <form id="search-form" class="search-card" novalidate>
            <label class="form-label fw-bold mb-2" for="city-input">
                <i class="bi bi-geo-alt me-1" style="color:var(--accent)"></i>
                <?= t('search_label') ?>
            </label>
            <div class="search-row">
                <input type="text" id="city-input" class="search-input"
                       placeholder="<?= t('search_ph') ?>"
                       autocomplete="off" maxlength="100">
                <button type="button" class="btn-map-pick" id="btn-map"
                        title="Haritadan seç">
                    <i class="bi bi-map"></i>
                </button>
                <button type="submit" class="btn-ara" id="btn-ara">
                    <i class="bi bi-search me-1"></i><?= t('btn_search') ?>
                </button>
            </div>
            <p class="search-hint">
                💡 <kbd>/</kbd> <?= t('hint_slash') ?> &nbsp;·&nbsp;
                <kbd>Space</kbd> <?= t('hint_space') ?>
            </p>
        </form>

        <!-- Favori Şehirler Hızlı Erişim -->
        <div id="quick-cities" class="quick-cities mt-3" style="display:none"></div>
    </div>
</section>

<!-- ══ YÜKLENIYOR ════════════════════════════════════════════════════════════ -->
<div class="container z1">
    <div class="loader" id="loader">
        <div class="spin"></div>
        <p id="loading-text"><?= t('loading') ?></p>
    </div>
</div>

<!-- ══ HAVA DURUMU ═══════════════════════════════════════════════════════════ -->
<div id="weather-wrap" class="container z1 mb-4" style="display:none">
    <div class="weather-card" id="weather-card">
        <div class="w-row">
            <div class="w-icon"><span id="w-emoji">☀️</span></div>
            <div>
                <div class="w-city" id="w-city">—</div>
                <div class="w-desc" id="w-desc">—</div>
            </div>
            <div class="ms-auto text-end">
                <div class="w-temp" id="w-temp">—°C</div>
            </div>
        </div>
        <div class="w-stats">
            <div>
                <div class="w-stat-label">💧 <?= t('label_humidity') ?></div>
                <div class="w-stat-val" id="w-hum">—</div>
            </div>
            <div>
                <div class="w-stat-label">💨 <?= t('label_wind') ?></div>
                <div class="w-stat-val" id="w-wind">—</div>
            </div>
            <div>
                <div class="w-stat-label">🕐 <?= t('label_updated') ?></div>
                <div class="w-stat-val" id="w-time">—</div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MÜZİK ═════════════════════════════════════════════════════════════════ -->
<section id="music-wrap" class="container z1 mb-player" style="display:none">
    <div class="mb-3">
        <div class="sec-title">
            <span class="sec-dot"></span><?= t('genres_title') ?>
        </div>
        <div id="genres"></div>
    </div>
    <div>
        <div class="sec-title">
            <span class="sec-dot"></span><?= t('tracks_title') ?>
            <small style="color:var(--text3);font-size:.75rem;font-weight:400">
                — <?= t('tracks_sub') ?>
            </small>
        </div>
        <div id="tracks"></div>
    </div>
</section>

<!-- ══ PLAYER ════════════════════════════════════════════════════════════════ -->
<div class="player" id="player">
    <div class="player-progress" id="player-progress">
        <div class="player-progress-fill" id="progress-fill"></div>
    </div>
    <div class="player-body">
        <div class="container">
            <div class="player-inner">
                <img id="p-artwork" src="" alt="" class="player-artwork" style="display:none">
                <div id="p-artwork-ph" class="player-artwork-placeholder">🎵</div>
                <div class="player-info">
                    <div class="player-title">
                        <span class="now-dot"></span>
                        <span id="p-title">—</span>
                    </div>
                    <div class="player-artist" id="p-artist">—</div>
                </div>
                <div class="p-controls">
                    <button class="p-btn" id="btn-prev"><i class="bi bi-skip-backward-fill"></i></button>
                    <button class="p-btn p-btn-play" id="btn-play-pause"><i class="bi bi-play-fill"></i></button>
                    <button class="p-btn" id="btn-next"><i class="bi bi-skip-forward-fill"></i></button>
                </div>
                <div class="volume-wrap">
                    <i class="bi bi-volume-up" style="color:var(--text3);font-size:.9rem"></i>
                    <input type="range" class="volume-slider" id="volume-slider" min="0" max="100" value="80">
                </div>
                <span class="player-time">
                    <span id="p-current">0:00</span> / <span id="p-total">0:30</span>
                </span>
                <button class="p-btn" id="btn-close-player"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- ══ HARİTA MODALİ ═════════════════════════════════════════════════════════ -->
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-map me-2" style="color:var(--accent)"></i>
                    Haritadan Şehir Seç
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="map" style="height:420px;border-radius:0 0 var(--r-lg) var(--r-lg)"></div>
            </div>
            <div class="modal-footer border-0 pt-2">
                <small style="color:var(--text2)">
                    <i class="bi bi-info-circle me-1"></i>
                    Haritada bir yere tıkla, şehir adı otomatik gelsin
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ══ LOGIN ═════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-box-arrow-in-right me-2" style="color:var(--accent)"></i>
                    <?= t('login_title') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="login-form">
                    <div class="mb-3">
                        <label class="form-label"><?= t('label_username') ?></label>
                        <input type="text" id="l-user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= t('label_password') ?></label>
                        <input type="password" id="l-pass" class="form-control" required>
                    </div>
                    <p style="font-size:.75rem;color:var(--text3)">
                        <?= t('demo_account') ?>: <b style="color:var(--text2)">demo</b> /
                        <b style="color:var(--text2)">Test1234!</b>
                    </p>
                    <button type="submit" class="btn-submit"><?= t('btn_login') ?></button>
                    <div class="text-center mt-2">
                        <a href="#" class="forgot-link" data-bs-toggle="modal"
                           data-bs-target="#forgotModal" data-bs-dismiss="modal"
                           style="font-size:.8rem;color:var(--accent)">
                            <?= t('forgot_title') ?>?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ══ REGISTER ══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="regModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2" style="color:var(--accent)"></i>
                    <?= t('reg_title') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="reg-form">
                    <div class="mb-3">
                        <label class="form-label"><?= t('label_username') ?></label>
                        <input type="text" id="r-user" class="form-control" minlength="3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= t('label_email') ?></label>
                        <input type="email" id="r-email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <?= t('label_password') ?>
                            <small style="color:var(--text3)">(min. 8, A-Z, 0-9)</small>
                        </label>
                        <input type="password" id="r-pass" class="form-control" minlength="8" required>
                    </div>
                    <button type="submit" class="btn-submit"><?= t('btn_register') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ══ ŞİFREMİ UNUTTUM ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-key me-2" style="color:var(--accent)"></i>
                    <?= t('forgot_title') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p style="font-size:.88rem;color:var(--text2);margin-bottom:1rem">
                    <?= t('forgot_sub') ?>
                </p>
                <form id="forgot-form">
                    <div class="mb-3">
                        <label class="form-label"><?= t('label_email') ?></label>
                        <input type="email" id="f-email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn-submit"><?= t('forgot_btn') ?></button>
                </form>
                <div id="forgot-result" class="mt-3" style="display:none"></div>
            </div>
        </div>
    </div>
</div>

<!-- ══ FAVORİLER ═════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="favModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-heart-fill me-2" style="color:#f87171"></i>
                    <?= t('fav_title') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fav-list">
                <p style="color:var(--text2);text-align:center;padding:2rem">Yükleniyor...</p>
            </div>
        </div>
    </div>
</div>

<!-- ══ FAVORİ ŞEHİRLER ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="favCitiesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-geo-heart me-2" style="color:var(--accent)"></i>
                    <?= t('fav_cities_title') ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="fav-cities-list"></div>
                <div class="d-flex gap-2 mt-3">
                    <input type="text" id="new-city-input" class="form-control"
                           placeholder="Şehir adı..." maxlength="100">
                    <button class="btn-ara px-3" id="btn-add-city">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ TOAST ══════════════════════════════════════════════════════════════════ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"
     id="toasts" style="z-index:1100;margin-bottom:90px"></div>

<!-- ══ SCRIPTS ════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/weather-canvas.js"></script>
<script src="js/app.js"></script>
<script>
// ── Favoriler modal ──────────────────────────────────────────────────────────
document.getElementById('favModal')?.addEventListener('show.bs.modal', async () => {
    const list = document.getElementById('fav-list');
    try {
        const d = await Api.get('php/favorites.php');
        list.innerHTML = '';
        if (!d.favorites?.length) {
            list.innerHTML = `<p style="color:var(--text2);text-align:center;padding:2rem">${MC_T('no_favorites')}</p>`;
            return;
        }
        d.favorites.forEach((t,i) => list.append(MusicUI.trackCard(t, i+1)));
    } catch(e) { list.innerHTML = '<p style="color:var(--text2);text-align:center;padding:2rem">Yüklenemedi.</p>'; }
});

// ── Favori şehirler modal ────────────────────────────────────────────────────
document.getElementById('favCitiesModal')?.addEventListener('show.bs.modal', () => FavCities.loadModal());
document.getElementById('btn-add-city')?.addEventListener('click', () => {
    const v = document.getElementById('new-city-input').value.trim();
    if (v) FavCities.add(v);
});
</script>
</body>
</html>
