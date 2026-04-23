<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/lang.php';

startSecureSession();
if (!isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$theme = isset($_COOKIE['mc_theme']) ? ($_COOKIE['mc_theme'] === 'light' ? 'light' : 'dark') : 'dark';
$lang  = getLang(); $dir = langDir();
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim — MoodCast</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/light.css" rel="stylesheet" id="light-css" <?= $theme==='dark'?'disabled':'' ?>>
    <style>
        body { min-height: 100vh; position: relative; z-index: 1; }

        .profile-hero {
            background: linear-gradient(135deg, hsl(var(--hue,258),40%,12%), var(--bg2));
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .profile-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 60% 40% at 50% 0%, hsla(258,70%,50%,.12), transparent);
        }

        /* Avatar */
        .avatar-container {
            position: relative;
            width: 100px; height: 100px;
            margin: 0 auto 1rem;
            cursor: pointer;
        }
        .avatar-img {
            width: 100px; height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            box-shadow: 0 0 25px var(--accent-glow);
        }
        .avatar-placeholder {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-dim), var(--accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem;
            border: 3px solid var(--accent);
            box-shadow: 0 0 25px var(--accent-glow);
        }
        .avatar-edit-overlay {
            position: absolute; inset: 0;
            border-radius: 50%;
            background: rgba(0,0,0,.55);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
            font-size: .78rem; color: #fff; font-weight: 600;
            flex-direction: column; gap: .2rem;
        }
        .avatar-container:hover .avatar-edit-overlay { opacity: 1; }
        #avatar-file-input { display: none; }

        .profile-name { font-weight: 800; font-size: 1.5rem; margin-bottom: .2rem; }
        .profile-username { color: var(--accent); font-size: .9rem; font-weight: 600; }
        .profile-bio { color: var(--text2); font-size: .88rem; margin-top: .5rem; font-style: italic; }

        /* İstatistik kartları */
        .stat-card {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: var(--r-md); padding: 1.2rem;
            text-align: center; transition: all .2s;
            text-decoration: none; color: var(--text);
            display: block;
        }
        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--accent-glow);
            color: var(--text);
        }
        .stat-num { font-size: 2rem; font-weight: 900; color: var(--accent); line-height: 1; }
        .stat-label { font-size: .75rem; color: var(--text2); margin-top: .3rem; }

        /* Sekmeler */
        .tab-nav { display: flex; gap: .5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .tab-btn {
            background: var(--bg2); border: 1px solid var(--border);
            color: var(--text2); padding: .5rem 1.1rem; border-radius: 100px;
            font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .2s;
        }
        .tab-btn.active, .tab-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: up .3s ease; }

        /* Bölüm kartı */
        .section-card {
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: var(--r-lg); padding: 1.75rem; margin-bottom: 1.5rem;
        }
        .section-head { font-weight: 800; font-size: 1.05rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem; }
        .section-head i { color: var(--accent); }

        .form-control, .form-select {
            background: var(--bg) !important; border-color: var(--border2) !important;
            color: var(--text) !important; border-radius: var(--r-sm) !important;
        }
        .form-control:focus { border-color: var(--accent) !important; box-shadow: 0 0 0 3px var(--accent-glow) !important; }
        .form-label { color: var(--text2); font-size: .85rem; font-weight: 600; margin-bottom: .35rem; }

        .btn-save {
            background: linear-gradient(135deg, var(--accent-dim), var(--accent));
            border: none; color: #fff; border-radius: var(--r-sm);
            padding: .65rem 2rem; font-weight: 700; cursor: pointer; transition: all .2s;
        }
        .btn-save:hover { transform: translateY(-1px); opacity: .9; }

        /* Favori listesi */
        .fav-track-row {
            display: flex; align-items: center; gap: .75rem;
            padding: .75rem; border-radius: var(--r-sm);
            border: 1px solid var(--border); margin-bottom: .4rem;
            background: var(--bg); transition: all .2s;
        }
        .fav-track-row:hover { border-color: var(--accent); background: var(--bg2); }
        .fav-track-info { flex: 1; min-width: 0; }
        .fav-track-title { font-weight: 600; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fav-track-artist { font-size: .78rem; color: var(--text2); }
        .fav-remove-btn { background: none; border: none; color: var(--text3); cursor: pointer; font-size: 1rem; padding: .2rem; transition: all .2s; }
        .fav-remove-btn:hover { color: #f87171; transform: scale(1.2); }

        /* Favori şehirler */
        .fav-city-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: .65rem 1rem; border-radius: var(--r-sm);
            border: 1px solid var(--border); margin-bottom: .4rem;
            background: var(--bg); transition: all .2s; cursor: pointer;
        }
        .fav-city-row:hover { border-color: var(--accent); background: var(--bg2); }
        .fav-city-name { font-weight: 600; font-size: .9rem; display: flex; align-items: center; gap: .5rem; }
        .fav-city-btns { display: flex; gap: .4rem; }
        .fav-city-search { background: var(--accent); border: none; color: #fff; padding: .25rem .7rem; border-radius: 100px; font-size: .72rem; font-weight: 700; cursor: pointer; }
        .fav-city-remove { background: none; border: 1px solid var(--border2); color: var(--text3); padding: .25rem .5rem; border-radius: 100px; font-size: .72rem; cursor: pointer; }
        .fav-city-remove:hover { border-color: #f87171; color: #f87171; }

        .danger-zone { border-color: #f87171 !important; }
        .danger-zone .section-head i { color: #f87171; }

        @keyframes up { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
    </style>
</head>
<body data-theme="<?= $theme ?>">

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-broadcast me-1"></i>MoodCast</a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="index.php" class="btn-login"><i class="bi bi-house me-1"></i>Ana Sayfa</a>
            <button class="theme-toggle" id="theme-toggle">
                <i class="bi bi-<?= $theme==='dark'?'sun':'moon' ?>-fill"></i>
            </button>
        </div>
    </div>
</nav>

<div class="container py-4" style="max-width:860px;position:relative;z-index:1">

    <!-- Yükleniyor -->
    <div id="profile-loading" style="text-align:center;padding:4rem;color:var(--text2)">
        <div class="spin"></div>
        <p class="mt-3">Profil yükleniyor...</p>
    </div>

    <div id="profile-content" style="display:none">

        <!-- Profil Hero -->
        <div class="profile-hero">
            <!-- Avatar -->
            <div class="avatar-container" onclick="document.getElementById('avatar-file-input').click()" title="Fotoğraf değiştir">
                <img id="avatar-img" src="" alt="" class="avatar-img" style="display:none">
                <div id="avatar-ph" class="avatar-placeholder">👤</div>
                <div class="avatar-edit-overlay">
                    <i class="bi bi-camera-fill" style="font-size:1.2rem"></i>
                    <span>Değiştir</span>
                </div>
            </div>
            <input type="file" id="avatar-file-input" accept="image/*">

            <div class="profile-name" id="p-display-name">—</div>
            <div class="profile-username" id="p-display-username">@—</div>
            <div class="profile-bio" id="p-display-bio"></div>
        </div>

        <!-- İstatistikler (tıklanabilir) -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <a href="#" class="stat-card" onclick="switchTab('favorites'); return false;">
                    <div class="stat-num" id="stat-favs">0</div>
                    <div class="stat-label">♥ Favori Şarkı</div>
                </a>
            </div>
            <div class="col-4">
                <a href="#" class="stat-card" onclick="switchTab('cities'); return false;">
                    <div class="stat-num" id="stat-cities">0</div>
                    <div class="stat-label">🌍 Favori Şehir</div>
                </a>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-num" id="stat-searches">0</div>
                    <div class="stat-label">🔍 Arama</div>
                </div>
            </div>
        </div>

        <!-- Sekmeler -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('info')"><i class="bi bi-person me-1"></i>Bilgilerim</button>
            <button class="tab-btn" onclick="switchTab('favorites')"><i class="bi bi-heart me-1"></i>Favorilerim</button>
            <button class="tab-btn" onclick="switchTab('cities')"><i class="bi bi-geo-heart me-1"></i>Favori Şehirler</button>
            <button class="tab-btn" onclick="switchTab('password')"><i class="bi bi-shield-lock me-1"></i>Şifre</button>
            <button class="tab-btn" onclick="switchTab('danger')"><i class="bi bi-exclamation-triangle me-1"></i>Tehlikeli</button>
        </div>

        <!-- Bilgilerim -->
        <div class="tab-pane active" id="tab-info">
            <div class="section-card">
                <div class="section-head"><i class="bi bi-person-circle"></i>Kişisel Bilgiler</div>
                <form id="profile-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="p-username" readonly style="opacity:.6;cursor:not-allowed">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" id="p-fullname" placeholder="Adın Soyadın" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="p-email" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doğum Tarihi</label>
                            <input type="date" class="form-control" id="p-birthdate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ülke</label>
                            <input type="text" class="form-control" id="p-country" placeholder="Türkiye" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Favori Müzik Türü</label>
                            <select class="form-select" id="p-genre">
                                <option value="">Seç...</option>
                                <option>Pop / Dance</option><option>Jazz</option><option>Classical</option>
                                <option>Rock</option><option>Hip-Hop</option><option>Ambient</option>
                                <option>Electronic</option><option>Indie</option><option>Türkçe Pop</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Hakkımda <small style="color:var(--text3)"><span id="bio-count">0</span>/300</small></label>
                            <textarea class="form-control" id="p-bio" rows="3" placeholder="Kendin hakkında..." maxlength="300"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-save"><i class="bi bi-check-lg me-1"></i>Kaydet</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Favorilerim -->
        <div class="tab-pane" id="tab-favorites">
            <div class="section-card">
                <div class="section-head"><i class="bi bi-heart-fill"></i>Favori Şarkılarım</div>
                <div id="fav-tracks-list"><p style="color:var(--text2);text-align:center;padding:2rem">Yükleniyor...</p></div>
            </div>
        </div>

        <!-- Favori Şehirler -->
        <div class="tab-pane" id="tab-cities">
            <div class="section-card">
                <div class="section-head"><i class="bi bi-geo-heart"></i>Favori Şehirlerim</div>
                <div id="fav-cities-profile"></div>
                <div class="d-flex gap-2 mt-3">
                    <input type="text" class="form-control" id="new-city-prof" placeholder="Şehir ekle..." maxlength="100">
                    <button class="btn-save px-3" id="btn-add-city-prof"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </div>

        <!-- Şifre -->
        <div class="tab-pane" id="tab-password">
            <div class="section-card">
                <div class="section-head"><i class="bi bi-shield-lock"></i>Şifre Değiştir</div>
                <form id="password-form" style="max-width:420px">
                    <div class="mb-3"><label class="form-label">Mevcut Şifre</label><input type="password" class="form-control" id="pw-current" required></div>
                    <div class="mb-3"><label class="form-label">Yeni Şifre</label><input type="password" class="form-control" id="pw-new" minlength="8" required></div>
                    <div class="mb-3"><label class="form-label">Yeni Şifre (Tekrar)</label><input type="password" class="form-control" id="pw-confirm" required></div>
                    <button type="submit" class="btn-save"><i class="bi bi-shield-check me-1"></i>Güncelle</button>
                </form>
            </div>
        </div>

        <!-- Tehlikeli -->
        <div class="tab-pane" id="tab-danger">
            <div class="section-card danger-zone">
                <div class="section-head"><i class="bi bi-exclamation-triangle-fill"></i>Tehlikeli Alan</div>
                <p style="color:var(--text2);font-size:.9rem;margin-bottom:1.5rem">Hesabınızı silerseniz tüm verileriniz kalıcı olarak silinir. Bu işlem <strong style="color:#f87171">geri alınamaz.</strong></p>
                <form id="delete-form" style="max-width:380px">
                    <div class="mb-3"><label class="form-label">Şifrenizi girin</label><input type="password" class="form-control" id="del-password" required></div>
                    <button type="submit" class="btn-save" style="background:linear-gradient(135deg,#dc2626,#f87171)"><i class="bi bi-trash3 me-1"></i>Hesabımı Sil</button>
                </form>
            </div>
        </div>

    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts" style="z-index:1100"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toast(msg, type='info') {
    const colors={success:'#4ade80',error:'#f87171',info:'#818cf8',warning:'#fbbf24'};
    const icons={success:'✓',error:'✕',info:'ℹ',warning:'⚠'};
    const wrap=document.getElementById('toasts');
    const el=document.createElement('div'); el.className='toast show mb-2';
    el.style.borderLeft=`4px solid ${colors[type]}`;
    const body=document.createElement('div'); body.className='d-flex align-items-center p-3 gap-2';
    const ic=document.createElement('span'); ic.style.cssText=`color:${colors[type]};font-weight:bold`; ic.textContent=icons[type];
    const tx=document.createElement('span'); tx.textContent=msg;
    body.append(ic,tx); el.append(body); wrap.append(el);
    const t=setTimeout(()=>el.remove(),4000);
    el.addEventListener('click',()=>{clearTimeout(t);el.remove();});
}

async function api(url, data=null) {
    const opts = data ? {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)} : {};
    const r = await fetch(url, opts);
    const j = await r.json();
    if (!r.ok) throw new Error(j.error||'Hata');
    return j;
}

function switchTab(name) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name)?.classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b => {
        if (b.getAttribute('onclick')?.includes("'"+name+"'")) b.classList.add('active');
    });
    if (name === 'favorites') loadFavTracks();
    if (name === 'cities') loadFavCities();
}

const AVATARS = ['🎵','🎸','🎹','🎺','🎻','🥁','🎷','🎤','🎧','🎼'];

async function loadProfile() {
    try {
        const d = await api('php/profile.php');
        const u = d.user;
        document.getElementById('profile-loading').style.display = 'none';
        document.getElementById('profile-content').style.display = '';

        // Avatar
        if (u.avatar_url) {
            const img = document.getElementById('avatar-img');
            img.src = u.avatar_url; img.style.display = '';
            document.getElementById('avatar-ph').style.display = 'none';
        } else {
            const idx = u.username.charCodeAt(0) % AVATARS.length;
            document.getElementById('avatar-ph').textContent = AVATARS[idx];
        }

        document.getElementById('p-display-name').textContent = u.full_name || u.username;
        document.getElementById('p-display-username').textContent = '@' + u.username;
        if (u.bio) document.getElementById('p-display-bio').textContent = '"' + u.bio + '"';

        document.getElementById('stat-favs').textContent    = u.fav_count || 0;
        document.getElementById('stat-cities').textContent  = u.fav_cities_count || 0;
        document.getElementById('stat-searches').textContent = u.search_count || 0;

        document.getElementById('p-username').value  = u.username;
        document.getElementById('p-fullname').value  = u.full_name || '';
        document.getElementById('p-email').value     = u.email;
        document.getElementById('p-birthdate').value = u.birth_date || '';
        document.getElementById('p-country').value   = u.country || '';
        document.getElementById('p-genre').value     = u.fav_genre || '';
        document.getElementById('p-bio').value       = u.bio || '';
        document.getElementById('bio-count').textContent = (u.bio||'').length;
    } catch(e) { toast(e.message,'error'); }
}

async function loadFavTracks() {
    const list = document.getElementById('fav-tracks-list');
    try {
        const d = await api('php/favorites.php');
        list.innerHTML = '';
        if (!d.favorites?.length) {
            list.innerHTML = '<p style="color:var(--text2);text-align:center;padding:2rem">Henüz favori şarkı yok.</p>';
            return;
        }
        d.favorites.forEach(t => {
            const row = document.createElement('div');
            row.className = 'fav-track-row';
            const info = document.createElement('div'); info.className = 'fav-track-info';
            const title = document.createElement('div'); title.className = 'fav-track-title'; title.textContent = t.title;
            const artist = document.createElement('div'); artist.className = 'fav-track-artist'; artist.textContent = t.artist + ' · ' + t.genre;
            info.append(title, artist);
            const rm = document.createElement('button');
            rm.className = 'fav-remove-btn'; rm.innerHTML = '♥'; rm.title = 'Favorilerden çıkar';
            rm.addEventListener('click', async () => {
                await api('php/favorites.php', {action:'remove', track_id: t.id});
                toast('Favorilerden kaldırıldı.','info');
                loadFavTracks();
                loadProfile();
            });
            row.append(info, rm);
            list.append(row);
        });
    } catch(e) { list.innerHTML = '<p style="color:var(--text2);text-align:center;padding:2rem">Yüklenemedi.</p>'; }
}

async function loadFavCities() {
    const list = document.getElementById('fav-cities-profile');
    try {
        const d = await api('php/fav_cities.php');
        list.innerHTML = '';
        if (!d.cities?.length) {
            list.innerHTML = '<p style="color:var(--text2);text-align:center;padding:1.5rem">Henüz favori şehir yok.</p>';
            return;
        }
        d.cities.forEach(c => {
            const row = document.createElement('div');
            row.className = 'fav-city-row';
            const name = document.createElement('div'); name.className = 'fav-city-name';
            name.innerHTML = `<i class="bi bi-geo-alt-fill" style="color:var(--accent)"></i>${c.city}`;
            const btns = document.createElement('div'); btns.className = 'fav-city-btns';
            const rmBtn = document.createElement('button');
            rmBtn.className = 'fav-city-remove'; rmBtn.textContent = '× Sil';
            rmBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                await api('php/fav_cities.php', {action:'remove', city: c.city});
                toast('Şehir kaldırıldı.','info');
                loadFavCities(); loadProfile();
            });
            btns.append(rmBtn);
            // Satıra tıklayınca ara
            row.addEventListener('click', () => {
                window.location.href = 'index.php?city=' + encodeURIComponent(c.city);
            });
            row.style.cursor = 'pointer';
            row.title = c.city + ' için ara';
            row.append(name, btns);
            list.append(row);
        });
    } catch(e) {}
}

// Avatar yükleme
document.getElementById('avatar-file-input')?.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { toast('Dosya 2MB\'dan küçük olmalı!','error'); return; }
    const reader = new FileReader();
    reader.onload = async (ev) => {
        const base64 = ev.target.result;
        try {
            const d = await api('php/avatar.php', {avatar: base64});
            const img = document.getElementById('avatar-img');
            img.src = d.avatar_url + '?t=' + Date.now();
            img.style.display = '';
            document.getElementById('avatar-ph').style.display = 'none';
            toast('Profil fotoğrafı güncellendi! ✓','success');
        } catch(err) { toast(err.message,'error'); }
    };
    reader.readAsDataURL(file);
});

// Profil güncelle
document.getElementById('profile-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Kaydediliyor...';
    try {
        await api('php/profile.php', {
            action:'update',
            full_name: document.getElementById('p-fullname').value,
            email: document.getElementById('p-email').value,
            bio: document.getElementById('p-bio').value,
            birth_date: document.getElementById('p-birthdate').value,
            country: document.getElementById('p-country').value,
            fav_genre: document.getElementById('p-genre').value,
        });
        toast('Profil güncellendi! ✓','success');
        loadProfile();
    } catch(err) { toast(err.message,'error'); }
    finally { btn.disabled=false; btn.innerHTML='<i class="bi bi-check-lg me-1"></i>Kaydet'; }
});

// Bio sayacı
document.getElementById('p-bio')?.addEventListener('input', e => {
    document.getElementById('bio-count').textContent = e.target.value.length;
});

// Şifre
document.getElementById('password-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    try {
        await api('php/profile.php', {action:'change_password',
            current_password: document.getElementById('pw-current').value,
            new_password: document.getElementById('pw-new').value,
            confirm_password: document.getElementById('pw-confirm').value,
        });
        toast('Şifre güncellendi! ✓','success');
        e.target.reset();
    } catch(err) { toast(err.message,'error'); }
    finally { btn.disabled=false; btn.innerHTML='<i class="bi bi-shield-check me-1"></i>Güncelle'; }
});

// Hesap sil
document.getElementById('delete-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    if (!confirm('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem GERİ ALINAMAZ.')) return;
    try {
        await api('php/profile.php', {action:'delete_account', password: document.getElementById('del-password').value});
        toast('Hesabınız silindi.','info');
        setTimeout(() => location.href='index.php', 1500);
    } catch(err) { toast(err.message,'error'); }
});

// Şehir ekle
document.getElementById('btn-add-city-prof')?.addEventListener('click', async () => {
    const val = document.getElementById('new-city-prof').value.trim();
    if (!val) return;
    try {
        await api('php/fav_cities.php', {action:'add', city: val});
        toast('Şehir eklendi!','success');
        document.getElementById('new-city-prof').value = '';
        loadFavCities(); loadProfile();
    } catch(e) { toast(e.message,'error'); }
});

// Dark/Light toggle
document.getElementById('theme-toggle')?.addEventListener('click', () => {
    const body = document.body;
    const newTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    body.setAttribute('data-theme', newTheme);
    document.getElementById('theme-toggle').innerHTML = newTheme==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';
    document.getElementById('light-css').disabled = newTheme === 'dark';
    document.cookie = `mc_theme=${newTheme};max-age=31536000;path=/;samesite=Strict`;
});

loadProfile();
</script>
</body>
</html>
