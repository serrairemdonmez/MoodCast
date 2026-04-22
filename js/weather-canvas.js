'use strict';

/**
 * MoodCast — Hava Durumu Canvas Animasyonları
 * Tam ekran, güçlü, hissedilen efektler
 */
const WeatherCanvas = (() => {
    let canvas, ctx, anim, particles = [], t = 0;
    let currentWeather = null;

    function init() {
        canvas = document.getElementById('weather-canvas');
        if (!canvas) return;
        ctx = canvas.getContext('2d');
        resize();
        window.addEventListener('resize', resize);
    }

    function resize() {
        if (!canvas) return;
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    function stop() {
        if (anim) { cancelAnimationFrame(anim); anim = null; }
        particles = [];
        t = 0;
        if (ctx && canvas) ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (canvas) canvas.style.opacity = '0';
    }

    function show() {
        if (canvas) canvas.style.opacity = '1';
    }

    function start(weather) {
        if (currentWeather === weather) return;
        currentWeather = weather;
        stop();
        if (!canvas || !ctx) return;

        setTimeout(() => {
            switch (weather) {
                case 'clear':        startSunny();   break;
                case 'clouds':       startCloudy();  break;
                case 'rain':         startRain();    break;
                case 'drizzle':      startRain(true);break;
                case 'thunderstorm': startStorm();   break;
                case 'snow':         startSnow();    break;
                case 'mist': case 'haze': case 'fog': startMist(); break;
                default: break;
            }
        }, 100);
    }

    /* ══════════════════════════════════════════
       ☀️ GÜNEŞLI — Sarı gökyüzü + gökkuşağı + ışık huzmeleri
    ══════════════════════════════════════════ */
    function startSunny() {
        show();
        // Işık partikülleri (toz zerrecikleri)
        for (let i = 0; i < 80; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 2.5 + 0.5,
                speedX: (Math.random() - 0.5) * 0.4,
                speedY: -Math.random() * 0.5 - 0.1,
                opacity: Math.random() * 0.5 + 0.1,
                phase: Math.random() * Math.PI * 2,
            });
        }

        function draw() {
            t += 0.008;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // ── Gökyüzü gradyanı (sarı-turuncu)
            const sky = ctx.createLinearGradient(0, 0, 0, canvas.height);
            sky.addColorStop(0,   'hsla(42, 80%, 18%, 0.92)');
            sky.addColorStop(0.4, 'hsla(45, 90%, 22%, 0.75)');
            sky.addColorStop(1,   'hsla(35, 70%, 12%, 0.60)');
            ctx.fillStyle = sky;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // ── Güneş (sağ üst)
            const sx = canvas.width * 0.80, sy = canvas.height * 0.14;

            // Güneş dış hâlesi
            const glow = ctx.createRadialGradient(sx, sy, 20, sx, sy, 280);
            glow.addColorStop(0,   'hsla(48, 100%, 70%, 0.35)');
            glow.addColorStop(0.3, 'hsla(45, 95%, 60%, 0.15)');
            glow.addColorStop(1,   'hsla(42, 80%, 50%, 0.0)');
            ctx.fillStyle = glow;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Güneş diski
            ctx.beginPath();
            ctx.arc(sx, sy, 55, 0, Math.PI * 2);
            const sunGrad = ctx.createRadialGradient(sx, sy, 0, sx, sy, 55);
            sunGrad.addColorStop(0,   'hsla(55, 100%, 90%, 0.95)');
            sunGrad.addColorStop(0.6, 'hsla(48, 100%, 70%, 0.85)');
            sunGrad.addColorStop(1,   'hsla(38, 90%, 55%, 0.70)');
            ctx.fillStyle = sunGrad;
            ctx.fill();

            // Güneş ışınları (dönen)
            ctx.save();
            ctx.translate(sx, sy);
            ctx.rotate(t * 0.2);
            for (let i = 0; i < 16; i++) {
                const angle = (i / 16) * Math.PI * 2;
                const len1 = 70 + Math.sin(t * 1.5 + i * 0.8) * 15;
                const len2 = len1 + 40 + Math.sin(t * 2 + i) * 20;
                const a = 0.08 + Math.sin(t * 2 + i) * 0.03;
                ctx.beginPath();
                ctx.moveTo(Math.cos(angle) * len1, Math.sin(angle) * len1);
                ctx.lineTo(Math.cos(angle) * len2, Math.sin(angle) * len2);
                ctx.strokeStyle = `hsla(48, 100%, 75%, ${a})`;
                ctx.lineWidth = 3 + Math.sin(t + i) * 1;
                ctx.stroke();
            }
            ctx.restore();

            // ── Gökkuşağı (sol alt yarım çember)
            const rcx = canvas.width * 0.38;
            const rcy = canvas.height * 0.90;
            const rainbow = [
                'hsla(0,   90%, 60%, 0.18)',
                'hsla(22,  90%, 60%, 0.16)',
                'hsla(48,  95%, 58%, 0.20)',
                'hsla(120, 70%, 50%, 0.16)',
                'hsla(195, 80%, 58%, 0.16)',
                'hsla(240, 75%, 65%, 0.15)',
                'hsla(280, 75%, 62%, 0.15)',
            ];
            const rBase = canvas.width * 0.28;
            rainbow.forEach((color, i) => {
                const r = rBase + i * 22;
                ctx.beginPath();
                ctx.arc(rcx, rcy, r, Math.PI, 0, false);
                ctx.strokeStyle = color;
                ctx.lineWidth = 18;
                ctx.stroke();
            });

            // ── Işık hüzmeleri (güneşten yayılan)
            for (let i = 0; i < 6; i++) {
                const angle = (-0.3 + i * 0.12) + Math.sin(t * 0.5 + i) * 0.04;
                const len = canvas.height * 1.4;
                ctx.save();
                ctx.translate(sx, sy);
                ctx.rotate(angle);
                const beam = ctx.createLinearGradient(0, 0, 0, len);
                beam.addColorStop(0,   `hsla(48, 100%, 80%, ${0.06 + i * 0.008})`);
                beam.addColorStop(0.5, `hsla(45, 90%, 70%, ${0.03})`);
                beam.addColorStop(1,   'hsla(45, 80%, 60%, 0)');
                ctx.fillStyle = beam;
                ctx.beginPath();
                const w = 60 + i * 30;
                ctx.moveTo(-w/2, 60);
                ctx.lineTo(w/2, 60);
                ctx.lineTo(w * 3, len);
                ctx.lineTo(-w * 3, len);
                ctx.closePath();
                ctx.fill();
                ctx.restore();
            }

            // ── Toz zerrecikleri
            particles.forEach(p => {
                p.x += p.speedX + Math.sin(t * 2 + p.phase) * 0.3;
                p.y += p.speedY;
                if (p.y < -5) { p.y = canvas.height + 5; p.x = Math.random() * canvas.width; }
                const op = p.opacity * (0.6 + Math.sin(t * 3 + p.phase) * 0.4);
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `hsla(48, 95%, 80%, ${op})`;
                ctx.fill();
            });

            anim = requestAnimationFrame(draw);
        }
        draw();
    }

    /* ══════════════════════════════════════════
       🌧️ YAĞMURLU — Mavi-gri + gerçekçi su damlaları
    ══════════════════════════════════════════ */
    function startRain(isDrizzle = false) {
        show();
        const count = isDrizzle ? 80 : 180;
        const speed = isDrizzle ? 4 : 14;

        for (let i = 0; i < count; i++) {
            particles.push({
                x: Math.random() * canvas.width * 1.2 - canvas.width * 0.1,
                y: Math.random() * canvas.height,
                len: isDrizzle ? Math.random() * 10 + 5 : Math.random() * 25 + 12,
                speed: Math.random() * speed * 0.5 + speed * 0.6,
                opacity: Math.random() * 0.45 + 0.15,
                thickness: isDrizzle ? Math.random() * 0.8 + 0.3 : Math.random() * 1.5 + 0.5,
                type: 'drop',
            });
        }

        // Yüzey damlaları (splash circles)
        const splashes = [];
        for (let i = 0; i < 12; i++) {
            splashes.push({ x: Math.random() * canvas.width, y: canvas.height * (0.85 + Math.random() * 0.15), r: 0, maxR: Math.random() * 18 + 8, speed: Math.random() * 1 + 0.5, alpha: 0 });
        }

        function draw() {
            t += 0.012;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // ── Gökyüzü (mavi-gri)
            const sky = ctx.createLinearGradient(0, 0, 0, canvas.height);
            sky.addColorStop(0,   'hsla(215, 35%, 12%, 0.95)');
            sky.addColorStop(0.5, 'hsla(210, 28%, 16%, 0.80)');
            sky.addColorStop(1,   'hsla(205, 22%, 10%, 0.70)');
            ctx.fillStyle = sky;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // ── Bulut katmanları
            for (let c = 0; c < 3; c++) {
                const cy = canvas.height * (0.08 + c * 0.12);
                const cw = canvas.width * (0.7 + c * 0.2);
                const cx = (canvas.width * 0.5 + Math.sin(t * 0.2 + c) * 40);
                const cGrad = ctx.createRadialGradient(cx, cy, 10, cx, cy, cw * 0.4);
                cGrad.addColorStop(0, `hsla(210, 20%, ${30 + c * 5}%, ${0.15 - c * 0.03})`);
                cGrad.addColorStop(1, 'transparent');
                ctx.fillStyle = cGrad;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }

            // ── Yağmur damlaları
            particles.forEach(p => {
                p.y += p.speed;
                p.x -= p.speed * 0.15;
                if (p.y > canvas.height + p.len) {
                    p.y = -p.len - Math.random() * 100;
                    p.x = Math.random() * canvas.width * 1.2 - canvas.width * 0.1;
                    // splash ekle
                    if (!isDrizzle && Math.random() > 0.7) {
                        const s = splashes[Math.floor(Math.random() * splashes.length)];
                        s.x = p.x; s.y = canvas.height * 0.92; s.r = 0; s.alpha = 0.6;
                    }
                }
                // Damlayı çiz
                const grad = ctx.createLinearGradient(p.x, p.y, p.x - p.len * 0.15, p.y + p.len);
                grad.addColorStop(0, `hsla(200, 70%, 80%, 0)`);
                grad.addColorStop(0.3, `hsla(200, 70%, 80%, ${p.opacity})`);
                grad.addColorStop(1, `hsla(210, 60%, 70%, ${p.opacity * 0.7})`);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p.x - p.len * 0.15, p.y + p.len);
                ctx.strokeStyle = grad;
                ctx.lineWidth = p.thickness;
                ctx.lineCap = 'round';
                ctx.stroke();
            });

            // ── Splash circles
            splashes.forEach(s => {
                if (s.alpha <= 0) return;
                s.r += s.speed;
                s.alpha -= 0.025;
                ctx.beginPath();
                ctx.ellipse(s.x, s.y, s.r, s.r * 0.3, 0, 0, Math.PI * 2);
                ctx.strokeStyle = `hsla(200, 70%, 80%, ${s.alpha})`;
                ctx.lineWidth = 1;
                ctx.stroke();
            });

            // ── Zemin yansıması
            const refl = ctx.createLinearGradient(0, canvas.height * 0.85, 0, canvas.height);
            refl.addColorStop(0, 'transparent');
            refl.addColorStop(1, 'hsla(210, 40%, 20%, 0.35)');
            ctx.fillStyle = refl;
            ctx.fillRect(0, canvas.height * 0.85, canvas.width, canvas.height * 0.15);

            anim = requestAnimationFrame(draw);
        }
        draw();
    }

    /* ══════════════════════════════════════════
       ❄️ KARLI — Beyaz + gerçekçi kar taneleri
    ══════════════════════════════════════════ */
    function startSnow() {
        show();
        for (let i = 0; i < 120; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 5 + 2,
                speedY: Math.random() * 1.2 + 0.3,
                speedX: (Math.random() - 0.5) * 0.5,
                wobble: Math.random() * Math.PI * 2,
                wobbleSpeed: Math.random() * 0.02 + 0.008,
                opacity: Math.random() * 0.7 + 0.2,
                type: Math.random() > 0.5 ? 'flake' : 'circle', // karışık
            });
        }

        function drawFlake(x, y, r, opacity) {
            ctx.save();
            ctx.translate(x, y);
            ctx.globalAlpha = opacity;
            const arms = 6;
            for (let i = 0; i < arms; i++) {
                ctx.save();
                ctx.rotate((i / arms) * Math.PI * 2);
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.lineTo(0, r);
                ctx.strokeStyle = 'hsl(210, 60%, 95%)';
                ctx.lineWidth = r > 4 ? 1.2 : 0.8;
                ctx.stroke();
                // Yan dallar
                if (r > 3) {
                    const bd = r * 0.4;
                    ctx.beginPath();
                    ctx.moveTo(0, bd);
                    ctx.lineTo(r * 0.3, bd - r * 0.2);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(0, bd);
                    ctx.lineTo(-r * 0.3, bd - r * 0.2);
                    ctx.stroke();
                }
                ctx.restore();
            }
            // Merkez nokta
            ctx.beginPath();
            ctx.arc(0, 0, r * 0.18, 0, Math.PI * 2);
            ctx.fillStyle = 'hsl(210, 50%, 98%)';
            ctx.fill();
            ctx.restore();
        }

        function draw() {
            t += 0.008;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // ── Gökyüzü (beyaz-mavi)
            const sky = ctx.createLinearGradient(0, 0, 0, canvas.height);
            sky.addColorStop(0,   'hsla(210, 30%, 10%, 0.96)');
            sky.addColorStop(0.5, 'hsla(205, 25%, 14%, 0.82)');
            sky.addColorStop(1,   'hsla(200, 20%, 9%, 0.75)');
            ctx.fillStyle = sky;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // ── Kar parıltısı (üstten gelen ışık)
            const snowLight = ctx.createRadialGradient(canvas.width / 2, -50, 50, canvas.width / 2, -50, canvas.height * 0.8);
            snowLight.addColorStop(0,   'hsla(200, 50%, 80%, 0.12)');
            snowLight.addColorStop(0.5, 'hsla(200, 40%, 70%, 0.05)');
            snowLight.addColorStop(1,   'transparent');
            ctx.fillStyle = snowLight;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // ── Kar birikintisi (altta)
            ctx.beginPath();
            ctx.moveTo(0, canvas.height);
            const pileY = canvas.height * 0.88;
            for (let x = 0; x <= canvas.width; x += 30) {
                const y = pileY + Math.sin(x * 0.015 + t * 0.3) * 12 + Math.sin(x * 0.04) * 8;
                ctx.lineTo(x, y);
            }
            ctx.lineTo(canvas.width, canvas.height);
            ctx.closePath();
            const pileGrad = ctx.createLinearGradient(0, pileY, 0, canvas.height);
            pileGrad.addColorStop(0, 'hsla(210, 50%, 88%, 0.25)');
            pileGrad.addColorStop(1, 'hsla(210, 40%, 75%, 0.15)');
            ctx.fillStyle = pileGrad;
            ctx.fill();

            // ── Kar taneleri
            particles.forEach(p => {
                p.wobble += p.wobbleSpeed;
                p.y += p.speedY;
                p.x += p.speedX + Math.sin(p.wobble) * 0.6;
                if (p.y > canvas.height + p.r * 2) {
                    p.y = -p.r * 2;
                    p.x = Math.random() * canvas.width;
                }
                if (p.x > canvas.width + p.r) p.x = -p.r;
                if (p.x < -p.r) p.x = canvas.width + p.r;

                if (p.type === 'flake' && p.r >= 4) {
                    drawFlake(p.x, p.y, p.r, p.opacity);
                } else {
                    // Basit daire kar tanesi
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    const flakeGrad = ctx.createRadialGradient(p.x - p.r * 0.2, p.y - p.r * 0.2, 0, p.x, p.y, p.r);
                    flakeGrad.addColorStop(0, `hsla(210, 60%, 98%, ${p.opacity})`);
                    flakeGrad.addColorStop(1, `hsla(210, 50%, 85%, ${p.opacity * 0.6})`);
                    ctx.fillStyle = flakeGrad;
                    ctx.fill();
                }
            });

            anim = requestAnimationFrame(draw);
        }
        draw();
    }

    /* ══════════════════════════════════════════
       ⛈️ FIRTINA
    ══════════════════════════════════════════ */
    function startStorm() {
        show();
        for (let i = 0; i < 200; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                len: Math.random() * 28 + 14,
                speed: Math.random() * 18 + 16,
                opacity: Math.random() * 0.5 + 0.2,
                thickness: Math.random() * 1.5 + 0.5,
            });
        }

        let lightningTimer = 0;
        let flash = 0;

        function drawLightning(x1, y1) {
            let cx = x1, cy = y1;
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            while (cy < canvas.height * 0.85) {
                cx += (Math.random() - 0.5) * 80;
                cy += Math.random() * 90 + 40;
                ctx.lineTo(cx, cy);
            }
            ctx.strokeStyle = `hsla(60, 100%, 95%, ${0.6 + Math.random() * 0.4})`;
            ctx.lineWidth = Math.random() * 3 + 1;
            ctx.shadowColor = 'hsl(60, 100%, 80%)';
            ctx.shadowBlur = 20;
            ctx.stroke();
            ctx.shadowBlur = 0;
        }

        function draw() {
            t += 0.012;
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const sky = ctx.createLinearGradient(0, 0, 0, canvas.height);
            sky.addColorStop(0, 'hsla(255, 40%, 8%, 0.97)');
            sky.addColorStop(1, 'hsla(250, 30%, 5%, 0.88)');
            ctx.fillStyle = sky;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            if (flash > 0) {
                ctx.fillStyle = `hsla(260, 50%, 70%, ${flash * 0.15})`;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                flash -= 0.06;
            }

            lightningTimer++;
            if (lightningTimer > 70 + Math.random() * 60) {
                drawLightning(Math.random() * canvas.width, 0);
                if (Math.random() > 0.4) drawLightning(Math.random() * canvas.width, 0);
                flash = 1;
                lightningTimer = 0;
            }

            particles.forEach(p => {
                p.y += p.speed;
                p.x -= p.speed * 0.35;
                if (p.y > canvas.height + p.len) { p.y = -p.len; p.x = Math.random() * canvas.width; }
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p.x - p.len * 0.35, p.y + p.len);
                ctx.strokeStyle = `hsla(240, 50%, 75%, ${p.opacity})`;
                ctx.lineWidth = p.thickness;
                ctx.stroke();
            });

            anim = requestAnimationFrame(draw);
        }
        draw();
    }

    /* ══════════════════════════════════════════
       ☁️ BULUTLU
    ══════════════════════════════════════════ */
    function startCloudy() {
        show();
        for (let i = 0; i < 7; i++) {
            particles.push({
                x: Math.random() * canvas.width * 1.5,
                y: Math.random() * canvas.height * 0.55,
                w: Math.random() * 280 + 150,
                h: Math.random() * 70 + 50,
                speed: Math.random() * 0.18 + 0.06,
                opacity: Math.random() * 0.14 + 0.06,
                segments: Math.floor(Math.random() * 4) + 4,
            });
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const sky = ctx.createLinearGradient(0, 0, 0, canvas.height);
            sky.addColorStop(0, 'hsla(210, 22%, 10%, 0.90)');
            sky.addColorStop(1, 'hsla(210, 18%, 8%, 0.75)');
            ctx.fillStyle = sky;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            particles.forEach(p => {
                p.x += p.speed;
                if (p.x > canvas.width + p.w) p.x = -p.w;
                ctx.save();
                ctx.globalAlpha = p.opacity;
                ctx.fillStyle = 'hsl(210, 25%, 72%)';
                for (let i = 0; i < p.segments; i++) {
                    const ex = p.x + i * (p.w / p.segments);
                    const ey = p.y + Math.sin(i * 1.1) * 12;
                    const er = p.h * (0.5 + Math.sin(i * 0.8) * 0.2);
                    ctx.beginPath();
                    ctx.ellipse(ex, ey, p.w / p.segments * 0.9, er, 0, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.restore();
            });
            anim = requestAnimationFrame(draw);
        }
        draw();
    }

    /* ══════════════════════════════════════════
       🌫️ SİSLİ
    ══════════════════════════════════════════ */
    function startMist() {
        show();
        for (let i = 0; i < 6; i++) {
            particles.push({ y: canvas.height * (0.15 + i * 0.14), offset: Math.random() * Math.PI * 2, speed: (Math.random() - 0.5) * 0.4, opacity: 0.04 + i * 0.005 });
        }
        function draw() {
            t += 0.003;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            const sky = ctx.createLinearGradient(0, 0, 0, canvas.height);
            sky.addColorStop(0, 'hsla(200, 12%, 9%, 0.92)');
            sky.addColorStop(1, 'hsla(200, 8%, 6%, 0.78)');
            ctx.fillStyle = sky;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.offset += p.speed * 0.01;
                const grad = ctx.createLinearGradient(0, p.y - 60, 0, p.y + 60);
                grad.addColorStop(0, 'transparent');
                grad.addColorStop(0.5, `hsla(200, 18%, 70%, ${p.opacity})`);
                grad.addColorStop(1, 'transparent');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.moveTo(0, p.y);
                for (let x = 0; x <= canvas.width; x += 35) {
                    ctx.lineTo(x, p.y + Math.sin(x * 0.007 + t + p.offset) * 35);
                }
                ctx.lineTo(canvas.width, canvas.height);
                ctx.lineTo(0, canvas.height);
                ctx.closePath();
                ctx.fill();
            });
            anim = requestAnimationFrame(draw);
        }
        draw();
    }

    return { init, start, stop };
})();
