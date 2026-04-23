'use strict';

// ── Dil yardımcısı ────────────────────────────────────────────────────────────
function MC_T(key) {
    return (window.MC_TRANSLATIONS && window.MC_TRANSLATIONS[key]) || key;
}


// ── SPLASH TAM EKRAN ─────────────────────────────────────────────────────────
const Splash = {
    step: 1,
    init() {
        // Splash görünürlüğü PHP tarafından kontrol ediliyor
        // Burada sadece event'leri bağlıyoruz
        const splash = document.getElementById('splash');
        if (!splash || splash.style.display === 'none') return;

        document.getElementById('splash-btn-1')?.addEventListener('click', () => this.goTo(2));
        document.getElementById('splash-btn-2')?.addEventListener('click', () => this.goTo(3));
        document.getElementById('splash-btn-3')?.addEventListener('click', () => this.close());
        document.getElementById('splash-skip')?.addEventListener('click', () => this.close());

        document.querySelectorAll('.splash-dot-item').forEach(d => {
            d.addEventListener('click', () => this.goTo(parseInt(d.dataset.s)));
        });

        document.addEventListener('keydown', e => {
            if (!document.getElementById('splash') || document.getElementById('splash').style.display === 'none') return;
            if (e.key === 'Escape') this.close();
            if (e.key === 'ArrowRight' || e.key === 'Enter') {
                if (this.step < 3) this.goTo(this.step + 1);
                else this.close();
            }
        });
    },
    goTo(n) {
        this.step = n;
        document.querySelectorAll('.splash-slide').forEach(s => {
            s.classList.toggle('on', parseInt(s.dataset.s) === n);
        });
        document.querySelectorAll('.splash-dot-item').forEach(d => {
            d.classList.toggle('on', parseInt(d.dataset.s) === n);
        });
    },
    close() {
        const splash = document.getElementById('splash');
        if (!splash) return;
        splash.classList.add('hiding');
        setTimeout(() => { splash.style.display = 'none'; }, 500);
        document.cookie = 'mc_splash_seen=1;max-age=1800;path=/;samesite=Strict';
    }
};

// ── SAAT ──────────────────────────────────────────────────────────────────────
const Clock = {
    pad: n => String(n).padStart(2,'0'),
    DAYS_TR:   ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'],
    DAYS_EN:   ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
    MONTHS_TR: ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'],
    MONTHS_EN: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    greeting(h) {
        if (h>=5&&h<12) return MC_T('greeting_morning');
        if (h>=12&&h<17) return MC_T('greeting_noon');
        if (h>=17&&h<21) return MC_T('greeting_evening');
        return MC_T('greeting_night');
    },
    tick() {
        const now = new Date();
        const h=now.getHours(), m=now.getMinutes(), s=now.getSeconds();
        const lang = window.MC_LANG || 'tr';
        const days   = lang === 'tr' ? this.DAYS_TR   : this.DAYS_EN;
        const months = lang === 'tr' ? this.MONTHS_TR : this.MONTHS_EN;
        const $ = id => document.getElementById(id);
        if ($('clock-time')) $('clock-time').textContent = `${this.pad(h)}:${this.pad(m)}:${this.pad(s)}`;
        if ($('clock-date')) $('clock-date').textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
        if ($('greeting'))   $('greeting').textContent  = this.greeting(h);
    },
    init() { this.tick(); setInterval(()=>this.tick(), 1000); }
};

// ── API ────────────────────────────────────────────────────────────────────────
const Api = {
    async get(url) {
        const r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const j = await r.json();
        if (!r.ok) throw new Error(j.error||'Hata');
        return j;
    },
    async post(url, data) {
        const r = await fetch(url, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(data),
        });
        const j = await r.json();
        if (!r.ok) throw new Error(j.error||'Hata');
        return j;
    }
};

// ── TOAST ─────────────────────────────────────────────────────────────────────
const Toast = {
    show(msg, type='info') {
        const colors={success:'#4ade80',error:'#f87171',info:'#818cf8',warning:'#fbbf24'};
        const icons={success:'✓',error:'✕',info:'ℹ',warning:'⚠'};
        const wrap=document.getElementById('toasts'); if(!wrap)return;
        const el=document.createElement('div'); el.className='toast show mb-2';
        el.style.borderLeft=`4px solid ${colors[type]}`;
        const body=document.createElement('div'); body.className='d-flex align-items-center p-3 gap-2';
        const ic=document.createElement('span'); ic.style.cssText=`color:${colors[type]};font-weight:bold`; ic.textContent=icons[type];
        const tx=document.createElement('span'); tx.textContent=msg;
        body.append(ic,tx); el.append(body); wrap.append(el);
        const t=setTimeout(()=>el.remove(),4000);
        el.addEventListener('click',()=>{clearTimeout(t);el.remove();});
    }
};

// ── ÇEREZ BANNER ─────────────────────────────────────────────────────────────
const CookieBanner = {
    init() {
        // Her oturumda göster, sadece bu session'da kabul edilmemişse
        const acceptedThisSession = sessionStorage.getItem('mc_cookies_accepted');
        if (!acceptedThisSession) {
            const banner = document.getElementById('cookie-banner');
            if (banner) banner.style.display = '';
        }
        document.getElementById('cookie-accept')?.addEventListener('click', () => {
            document.cookie = 'mc_cookies_accepted=1;max-age=31536000;path=/;samesite=Strict';
            sessionStorage.setItem('mc_cookies_accepted', '1');
            document.getElementById('cookie-banner').style.display = 'none';
        });
        document.getElementById('cookie-reject')?.addEventListener('click', () => {
            document.getElementById('cookie-banner').style.display = 'none';
        });
    }
};

// ── ONBOARDING ────────────────────────────────────────────────────────────────
const Onboarding = {
    step: 1,
    init() {
        const seen = document.cookie.includes('mc_onboarding_done=');
        if (!seen) {
            const ob = document.getElementById('onboarding');
            if (ob) ob.style.display = '';
        }
        document.getElementById('ob-next')?.addEventListener('click', () => this.next());
        document.getElementById('ob-skip')?.addEventListener('click', () => this.done());
        document.querySelectorAll('.ob-dot').forEach(d => {
            d.addEventListener('click', () => this.goTo(parseInt(d.dataset.step)));
        });
    },
    goTo(n) {
        this.step = n;
        document.querySelectorAll('.ob-step').forEach(s => s.classList.toggle('active', parseInt(s.dataset.step) === n));
        document.querySelectorAll('.ob-dot').forEach(d => d.classList.toggle('active', parseInt(d.dataset.step) === n));
        const btn = document.getElementById('ob-next');
        if (btn) btn.textContent = n === 3 ? MC_T('ob_btn_start') : MC_T('ob_btn_next');
    },
    next() {
        if (this.step < 3) this.goTo(this.step + 1);
        else this.done();
    },
    done() {
        document.cookie = 'mc_onboarding_done=1;max-age=31536000;path=/;samesite=Strict';
        const ob = document.getElementById('onboarding');
        if (ob) { ob.style.opacity='0'; ob.style.transition='opacity .3s'; setTimeout(()=>ob.style.display='none',300); }
    }
};

// ── DARK/LIGHT MOD ───────────────────────────────────────────────────────────
const ThemeToggle = {
    init() {
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            const html = document.documentElement;
            const body = document.body;
            const isDark = body.getAttribute('data-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            html.setAttribute('data-theme', newTheme);
            document.getElementById('theme-toggle').innerHTML =
                newTheme === 'dark' ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
            const lightCss = document.getElementById('light-css');
            if (lightCss) lightCss.disabled = newTheme === 'dark';
            document.cookie = `mc_theme=${newTheme};max-age=31536000;path=/;samesite=Strict`;
        });
    }
};

// ── COOKIES ───────────────────────────────────────────────────────────────────
const Cookies = {
    async load() {
        try {
            const d = await Api.get('php/cookies.php');
            if (d.last_city) {
                const input = document.getElementById('city-input');
                if (input && !input.value) input.value = d.last_city;
            }
            return d;
        } catch(e) { return {}; }
    },
    async save(city, weather) {
        try { await Api.post('php/cookies.php', {last_city:city, last_weather:weather}); } catch(e) {}
    }
};

// ── FAVORİ ŞEHİRLER ──────────────────────────────────────────────────────────
const FavCities = {
    async load() {
        if (!window.MC_LOGGED_IN) return;
        try {
            const d = await Api.get('php/fav_cities.php');
            this.renderQuick(d.cities || []);
        } catch(e) {}
    },
    renderQuick(cities) {
        const wrap = document.getElementById('quick-cities');
        if (!wrap) return;
        if (!cities.length) { wrap.style.display='none'; return; }
        wrap.style.display='';
        wrap.innerHTML='';
        cities.forEach(c => {
            const btn = document.createElement('button');
            btn.className = 'quick-city-btn';
            btn.innerHTML = `<i class="bi bi-geo-alt-fill" style="font-size:.7rem;color:var(--accent)"></i>${c.city}`;
            btn.addEventListener('click', () => App.search(c.city));
            const rm = document.createElement('button');
            rm.className = 'quick-city-remove'; rm.textContent = '×'; rm.title = 'Kaldır';
            rm.addEventListener('click', async (e) => {
                e.stopPropagation();
                await this.remove(c.city);
            });
            btn.append(rm);
            wrap.append(btn);
        });
    },
    async loadModal() {
        const list = document.getElementById('fav-cities-list');
        if (!list) return;
        try {
            const d = await Api.get('php/fav_cities.php');
            list.innerHTML = '';
            if (!d.cities?.length) {
                list.innerHTML = `<p style="color:var(--text2);text-align:center;padding:1.5rem">${MC_T('no_fav_cities')}</p>`;
                return;
            }
            d.cities.forEach(c => {
                const row = document.createElement('div');
                row.className = 'quick-city-btn mb-2 w-100 justify-content-between';
                row.style.cssText = 'cursor:default;padding:.6rem 1rem';
                const name = document.createElement('span');
                name.innerHTML = `<i class="bi bi-geo-alt-fill me-1" style="color:var(--accent)"></i>${c.city}`;
                const btns = document.createElement('div');
                btns.className = 'd-flex gap-2';
                const searchBtn = document.createElement('button');
                searchBtn.className = 'btn-cookie-accept'; searchBtn.style.padding='.25rem .7rem'; searchBtn.style.fontSize='.75rem';
                searchBtn.textContent = MC_T('btn_search');
                searchBtn.addEventListener('click', () => { App.search(c.city); bootstrap.Modal.getInstance(document.getElementById('favCitiesModal'))?.hide(); });
                const rmBtn = document.createElement('button');
                rmBtn.className = 'btn-cookie-reject'; rmBtn.style.padding='.25rem .7rem'; rmBtn.style.fontSize='.75rem';
                rmBtn.textContent = '×';
                rmBtn.addEventListener('click', async () => { await this.remove(c.city); this.loadModal(); });
                btns.append(searchBtn, rmBtn);
                row.append(name, btns);
                list.append(row);
            });
        } catch(e) {}
    },
    async add(city) {
        if (!window.MC_LOGGED_IN) { Toast.show(MC_T('login_required'), 'warning'); return; }
        try {
            await Api.post('php/fav_cities.php', {action:'add', city});
            Toast.show(MC_T('city_added'), 'success');
            document.getElementById('new-city-input').value = '';
            this.load(); this.loadModal();
        } catch(e) { Toast.show(e.message, 'error'); }
    },
    async remove(city) {
        try {
            await Api.post('php/fav_cities.php', {action:'remove', city});
            Toast.show(MC_T('city_removed'), 'info');
            this.load();
        } catch(e) {}
    }
};

// ── HARİTA ────────────────────────────────────────────────────────────────────
const MapPicker = {
    map: null,
    init() {
        document.getElementById('btn-map')?.addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('mapModal'));
            modal.show();
            setTimeout(() => this.setup(), 400);
        });
    },
    setup() {
        if (this.map) { this.map.invalidateSize(); return; }
        this.map = L.map('map').setView([39, 35], 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(this.map);
        this.map.on('click', async (e) => {
            const {lat, lng} = e.latlng;
            try {
                // Reverse geocoding (nominatim — ücretsiz)
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
                const data = await res.json();
                const city = data.address?.city || data.address?.town || data.address?.village || data.name;
                if (city) {
                    document.getElementById('city-input').value = city;
                    bootstrap.Modal.getInstance(document.getElementById('mapModal'))?.hide();
                    App.search(city);
                }
            } catch(e) { Toast.show('Şehir belirlenemedi.', 'error'); }
        });
    }
};

// ── MÜZIK ÇALAR ───────────────────────────────────────────────────────────────
const Player = {
    audio: new Audio(),
    current: null,
    queue: [],
    init() {
        this.audio.volume = 0.8;
        this.audio.addEventListener('timeupdate', () => {
            if (!this.audio.duration) return;
            const pct = (this.audio.currentTime/this.audio.duration)*100;
            const fill = document.getElementById('progress-fill');
            if (fill) fill.style.width=pct+'%';
            const cur = document.getElementById('p-current');
            if (cur) cur.textContent = this.fmt(this.audio.currentTime);
        });
        this.audio.addEventListener('ended', () => this.go(1));
        this.audio.addEventListener('loadedmetadata', () => {
            const tot = document.getElementById('p-total');
            if (tot) tot.textContent = this.fmt(this.audio.duration);
        });
        document.getElementById('player-progress')?.addEventListener('click', (e) => {
            if (!this.audio.duration) return;
            const rect=e.currentTarget.getBoundingClientRect();
            this.audio.currentTime=((e.clientX-rect.left)/rect.width)*this.audio.duration;
        });
        document.getElementById('volume-slider')?.addEventListener('input', (e) => {
            this.audio.volume=e.target.value/100;
        });
        document.getElementById('btn-play-pause')?.addEventListener('click', ()=>this.togglePause());
        document.getElementById('btn-next')?.addEventListener('click', ()=>this.go(1));
        document.getElementById('btn-prev')?.addEventListener('click', ()=>this.go(-1));
        document.getElementById('btn-close-player')?.addEventListener('click', ()=>this.stop());
    },
    fmt(s) { if(!s||isNaN(s))return'0:00'; return`${Math.floor(s/60)}:${String(Math.floor(s%60)).padStart(2,'0')}`; },
    play(data) {
        this.current=data;
        this.audio.src=data.previewUrl;
        this.audio.play().catch(()=>Toast.show('Ses çalınamadı.','warning'));
        document.getElementById('player')?.classList.add('show');
        const artEl=document.getElementById('p-artwork'), artPh=document.getElementById('p-artwork-ph');
        if (data.artwork&&artEl) { artEl.src=data.artwork; artEl.style.display=''; if(artPh)artPh.style.display='none'; }
        else { if(artEl)artEl.style.display='none'; if(artPh)artPh.style.display=''; }
        document.getElementById('p-title').textContent=data.title;
        document.getElementById('p-artist').textContent=data.artist;
        document.getElementById('progress-fill').style.width='0%';
        document.getElementById('btn-play-pause').innerHTML='<i class="bi bi-pause-fill"></i>';
        document.querySelectorAll('.track').forEach(c=>{
            c.classList.remove('on');
            const pb=c.querySelector('.btn-play-track'); if(pb)pb.innerHTML='<i class="bi bi-play-fill"></i>';
        });
        const card=document.querySelector(`.track[data-track-id="${data.trackId}"]`);
        if (card) { card.classList.add('on'); const pb=card.querySelector('.btn-play-track'); if(pb)pb.innerHTML='<i class="bi bi-pause-fill"></i>'; }
    },
    togglePause() {
        if (!this.current) return;
        const btn=document.getElementById('btn-play-pause');
        if (this.audio.paused) { this.audio.play(); if(btn)btn.innerHTML='<i class="bi bi-pause-fill"></i>'; }
        else { this.audio.pause(); if(btn)btn.innerHTML='<i class="bi bi-play-fill"></i>'; }
        const card=document.querySelector(`.track[data-track-id="${this.current.trackId}"]`);
        const pb=card?.querySelector('.btn-play-track');
        if (pb) pb.innerHTML=this.audio.paused?'<i class="bi bi-play-fill"></i>':'<i class="bi bi-pause-fill"></i>';
    },
    go(dir) {
        if (!this.queue.length) return;
        const i=this.queue.findIndex(t=>String(t.trackId)===String(this.current?.trackId));
        const next=this.queue[(i+dir+this.queue.length)%this.queue.length];
        if (next) this.play(next);
    },
    stop() {
        this.audio.pause(); this.audio.src='';
        document.getElementById('player')?.classList.remove('show');
        document.querySelectorAll('.track').forEach(c=>{c.classList.remove('on');const pb=c.querySelector('.btn-play-track');if(pb)pb.innerHTML='<i class="bi bi-play-fill"></i>';});
        this.current=null;
    }
};

// ── ANA UYGULAMA ──────────────────────────────────────────────────────────────
const App = {
    weatherData: null,
    itunesTracks: [],
    currentWeatherCode: 'clear',

    async search(city) {
        const loader=document.getElementById('loader');
        const wWrap=document.getElementById('weather-wrap');
        const mWrap=document.getElementById('music-wrap');
        if(loader)loader.classList.add('show');
        if(wWrap)wWrap.style.display='none';
        if(mWrap)mWrap.style.display='none';
        try {
            const wd=await Api.get(`php/weather.php?city=${encodeURIComponent(city)}`);
            this.weatherData=wd; this.currentWeatherCode=wd.weather.code;
            const w=wd.weather;
            document.body.setAttribute('data-weather',w.code);
            document.getElementById('weather-card')?.classList.add('live');
            document.getElementById('w-emoji').textContent=w.emoji;
            document.getElementById('w-city').textContent=`${w.city}, ${w.country}`;
            document.getElementById('w-desc').textContent=w.description;
            document.getElementById('w-temp').textContent=`${Math.round(w.temperature)}°C`;
            document.getElementById('w-hum').textContent=`%${w.humidity}`;
            document.getElementById('w-wind').textContent=`${Math.round(w.wind_speed)} m/s`;
            document.getElementById('w-time').textContent=new Date().toLocaleTimeString();
            if(wWrap)wWrap.style.display='';
            WeatherCanvas.start(w.code);
            Cookies.save(w.city, w.code);
            const gWrap=document.getElementById('genres');
            if(gWrap) {
                gWrap.innerHTML='';
                wd.genres.forEach((g,i)=>{
                    const b=document.createElement('button');
                    b.className='g-badge'+(i===0?' on':'');
                    b.textContent=g.genre; b.dataset.genre=g.genre;
                    gWrap.append(b);
                });
            }
            await this.loadItunes(wd.genres[0]?.genre||'pop', w.code);
            if(mWrap)mWrap.style.display='';
        } catch(err) { Toast.show(err.message,'error'); }
        finally { if(loader)loader.classList.remove('show'); }
    },

    async loadItunes(genre, weatherCode) {
        const wrap=document.getElementById('tracks');
        if(wrap)wrap.innerHTML='<div style="color:var(--text2);text-align:center;padding:1.5rem">🎵 Yükleniyor...</div>';
        try {
            const d=await Api.get(`php/itunes.php?genre=${encodeURIComponent(genre)}&weather=${encodeURIComponent(weatherCode||this.currentWeatherCode)}`);
            this.itunesTracks=d.tracks||[];
            this.renderTracks(this.itunesTracks);
            Player.queue=this.itunesTracks.map(t=>({trackId:t.id,title:t.title,artist:t.artist,previewUrl:t.preview_url,artwork:t.artwork}));
        } catch(e) { if(wrap)wrap.innerHTML='<p style="color:var(--text2);text-align:center;padding:2rem">Şarkılar yüklenemedi.</p>'; }
    },

    renderTracks(tracks) {
        const wrap=document.getElementById('tracks'); if(!wrap)return;
        wrap.innerHTML='';
        if(!tracks.length){wrap.innerHTML='<p style="color:var(--text2);text-align:center;padding:2rem">Şarkı bulunamadı.</p>';return;}
        tracks.forEach((t,i)=>{
            const card=document.createElement('div'); card.className='track'; card.dataset.trackId=String(t.id); card.style.animationDelay=`${i*0.04}s`;
            if(t.artwork){const img=document.createElement('img');img.className='track-artwork';img.src=t.artwork;img.alt=t.title;img.onerror=()=>img.replaceWith(makePh());card.append(img);}else{card.append(makePh());}
            function makePh(){const d=document.createElement('div');d.className='track-artwork-placeholder';d.textContent='🎵';return d;}
            const info=document.createElement('div');info.className='track-info';
            const title=document.createElement('div');title.className='track-title';title.textContent=t.title;
            const artist=document.createElement('div');artist.className='track-artist';artist.textContent=t.artist;
            const gtag=document.createElement('span');gtag.className='track-genre-tag';gtag.textContent=t.genre;
            info.append(title,artist,gtag);
            const dur=document.createElement('span');dur.className='track-dur';dur.textContent=t.duration||'0:30';
            const playBtn=document.createElement('button');playBtn.className='btn-play-track';playBtn.innerHTML='<i class="bi bi-play-fill"></i>';
            const favBtn=document.createElement('button');favBtn.className='btn-fav';favBtn.textContent='♡';
            card.append(info,dur,playBtn,favBtn);
            const handlePlay=()=>{
                if(Player.current&&String(Player.current.trackId)===String(t.id))Player.togglePause();
                else Player.play({trackId:t.id,title:t.title,artist:t.artist,previewUrl:t.preview_url,artwork:t.artwork});
            };
            card.addEventListener('click',(e)=>{if(e.target.closest('.btn-fav'))return;handlePlay();});
            playBtn.addEventListener('click',(e)=>{e.stopPropagation();handlePlay();});
            favBtn.addEventListener('click',(e)=>{e.stopPropagation();Favs.toggle(t.id,favBtn,t.title,t.artist,t.genre);});
            wrap.append(card);
        });
    }
};

// ── FAVORİLER ─────────────────────────────────────────────────────────────────
const Favs = {
    async toggle(trackId,btn,title,artist,genre) {
        if(!window.MC_LOGGED_IN){Toast.show(MC_T('login_required'),'warning');return;}
        try {
            const on=btn.classList.contains('on');
            await Api.post('php/favorites.php',{action:on?'remove':'add',track_id:trackId,title,artist,genre});
            btn.classList.toggle('on'); btn.textContent=btn.classList.contains('on')?'♥':'♡';
            Toast.show(btn.classList.contains('on')?MC_T('fav_added'):MC_T('fav_removed'),'success');
        } catch(e){Toast.show(e.message,'error');}
    }
};

// ── AUTH ──────────────────────────────────────────────────────────────────────
const Auth = {
    async login(username,password) {
        const d=await Api.post('php/auth.php',{action:'login',username,password});
        Toast.show(MC_T('login_success'),'success');
        bootstrap.Modal.getInstance(document.getElementById('loginModal'))?.hide();
        document.getElementById('auth-btns')?.classList.add('d-none');
        const ui=document.getElementById('user-info');
        if(ui){ui.classList.remove('d-none');document.getElementById('nav-user').textContent=d.username;}
        window.MC_LOGGED_IN=true;
        FavCities.load();
    },
    async register(username,email,password) {
        const d=await Api.post('php/auth.php',{action:'register',username,email,password});
        Toast.show(MC_T('reg_success'),'success');
        bootstrap.Modal.getInstance(document.getElementById('regModal'))?.hide();
    },
    async logout() { await Api.post('php/auth.php',{action:'logout'}); location.reload(); },
    async forgot(email) {
        const d=await Api.post('php/forgot.php',{action:'request',email});
        const res=document.getElementById('forgot-result');
        if(res){
            res.style.display='';
            res.innerHTML=`<div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--r-sm);padding:.8rem;font-size:.85rem;color:var(--text2)">${d.message}${d.dev_link?`<br><br>🔧 <b>Dev link:</b> <a href="${d.dev_link}" style="color:var(--accent)">${d.dev_link}</a>`:''}`;
        }
    }
};

// ── MusicUI (favori modal) ────────────────────────────────────────────────────
const MusicUI = {
    trackCard(t,i) {
        const card=document.createElement('div'); card.className='track'; card.dataset.trackId=t.id;
        const info=document.createElement('div'); info.className='track-info';
        const title=document.createElement('div'); title.className='track-title'; title.textContent=t.title;
        const artist=document.createElement('div'); artist.className='track-artist'; artist.textContent=t.artist;
        const dur=document.createElement('span'); dur.className='track-dur'; dur.textContent=t.duration||'--:--';
        info.append(title,artist); card.append(info,dur); return card;
    }
};

// ── EVENTLER ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    Clock.init();
    Player.init();
    Splash.init();
    WeatherCanvas.init();
    CookieBanner.init();
    Onboarding.init();
    ThemeToggle.init();
    MapPicker.init();
    Cookies.load();
    FavCities.load();

    // Arama
    document.getElementById('search-form')?.addEventListener('submit', e=>{
        e.preventDefault();
        const city=document.getElementById('city-input')?.value.trim();
        if(city)App.search(city);
    });

    // Klavye
    document.addEventListener('keydown', e=>{
        const tag=document.activeElement.tagName;
        if(e.key==='/'&&!['INPUT','TEXTAREA'].includes(tag)){e.preventDefault();document.getElementById('city-input')?.focus();}
        if(e.key===' '&&!['INPUT','TEXTAREA'].includes(tag)){e.preventDefault();Player.togglePause();}
    });

    // Genre
    document.getElementById('genres')?.addEventListener('click', async e=>{
        const b=e.target.closest('.g-badge'); if(!b||!App.weatherData)return;
        document.querySelectorAll('.g-badge').forEach(x=>x.classList.remove('on')); b.classList.add('on');
        await App.loadItunes(b.dataset.genre,App.currentWeatherCode);
    });

    // Login
    document.getElementById('login-form')?.addEventListener('submit', async e=>{
        e.preventDefault(); const btn=e.target.querySelector('button[type=submit]');
        btn.disabled=true; btn.textContent='...';
        try{await Auth.login(document.getElementById('l-user').value,document.getElementById('l-pass').value);e.target.reset();}
        catch(err){Toast.show(err.message,'error');}
        finally{btn.disabled=false;btn.textContent=MC_T('btn_login');}
    });

    // Register
    document.getElementById('reg-form')?.addEventListener('submit', async e=>{
        e.preventDefault(); const btn=e.target.querySelector('button[type=submit]');
        btn.disabled=true; btn.textContent='...';
        try{await Auth.register(document.getElementById('r-user').value,document.getElementById('r-email').value,document.getElementById('r-pass').value);e.target.reset();}
        catch(err){Toast.show(err.message,'error');}
        finally{btn.disabled=false;btn.textContent=MC_T('btn_register');}
    });

    // Şifremi unuttum
    document.getElementById('forgot-form')?.addEventListener('submit', async e=>{
        e.preventDefault(); const btn=e.target.querySelector('button[type=submit]');
        btn.disabled=true; btn.textContent='...';
        try{await Auth.forgot(document.getElementById('f-email').value);}
        catch(err){Toast.show(err.message,'error');}
        finally{btn.disabled=false;btn.textContent=MC_T('forgot_btn');}
    });

    // Çıkış
    document.getElementById('btn-logout')?.addEventListener('click', ()=>Auth.logout());

    // PWA Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/moodcast/sw.js')
            .then(()=>console.log('%c✅ PWA aktif','color:#4ade80'))
            .catch(()=>{});
    }

    console.log('%cMoodCast 🎵','color:#818cf8;font-size:1.3rem;font-weight:900');

    // URL'de city parametresi varsa otomatik ara (profil sayfasından gelinince)
    const urlParams = new URLSearchParams(window.location.search);
    const cityParam = urlParams.get('city');
    if (cityParam) {
        const input = document.getElementById('city-input');
        if (input) input.value = cityParam;
        setTimeout(() => App.search(cityParam), 500);
        // URL'den parametreyi temizle
        window.history.replaceState({}, '', 'index.php');
    }

    // Safari ses sorunu için kullanıcı etkileşimi bekle
    document.addEventListener('click', () => {
        if (Player.audio && Player.audio.paused === false) return;
    }, { once: true });
});
