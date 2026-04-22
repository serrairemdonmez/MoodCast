<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

$theme = isset($_COOKIE['mc_theme']) ? ($_COOKIE['mc_theme'] === 'light' ? 'light' : 'dark') : 'dark';
$token = htmlspecialchars($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla — MoodCast</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/light.css" rel="stylesheet" id="light-css" <?= $theme==='dark'?'disabled':'' ?>>
</head>
<body data-theme="<?= $theme ?>" style="display:flex;align-items:center;justify-content:center;min-height:100vh">
<div style="width:100%;max-width:440px;padding:1.5rem">
    <div class="search-card">
        <div style="text-align:center;margin-bottom:1.5rem">
            <a href="index.php" class="navbar-brand" style="font-size:1.5rem;font-weight:900;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none">
                <i class="bi bi-broadcast me-1"></i>MoodCast
            </a>
        </div>
        <h5 style="font-weight:800;margin-bottom:.5rem"><i class="bi bi-key me-2" style="color:var(--accent)"></i>Şifre Sıfırla</h5>
        <p style="color:var(--text2);font-size:.85rem;margin-bottom:1.5rem">Yeni şifrenizi belirleyin.</p>

        <?php if (!$token): ?>
            <div style="color:#f87171;text-align:center;padding:1rem">
                Geçersiz veya eksik token. <a href="index.php" style="color:var(--accent)">Ana sayfaya dön</a>
            </div>
        <?php else: ?>
        <form id="reset-form">
            <input type="hidden" id="r-token" value="<?= $token ?>">
            <div class="mb-3">
                <label class="form-label">Yeni Şifre</label>
                <input type="password" class="form-control" id="r-pass" minlength="8" placeholder="En az 8 karakter" required>
                <small style="color:var(--text3)">Büyük harf ve rakam içermeli</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre Tekrar</label>
                <input type="password" class="form-control" id="r-pass2" required>
            </div>
            <button type="submit" class="btn-submit w-100">
                <i class="bi bi-shield-check me-1"></i>Şifremi Güncelle
            </button>
        </form>
        <div id="reset-result" style="margin-top:1rem;display:none"></div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:1.25rem">
            <a href="index.php" style="color:var(--text3);font-size:.82rem">← Ana sayfaya dön</a>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts" style="z-index:1100"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toast(msg, type='info') {
    const colors={success:'#4ade80',error:'#f87171',info:'#818cf8'};
    const wrap=document.getElementById('toasts');
    const el=document.createElement('div'); el.className='toast show mb-2';
    el.style.borderLeft=`4px solid ${colors[type]}`;
    const body=document.createElement('div'); body.className='d-flex align-items-center p-3 gap-2';
    const tx=document.createElement('span'); tx.textContent=msg;
    body.append(tx); el.append(body); wrap.append(el);
    setTimeout(()=>el.remove(),5000);
}

document.getElementById('reset-form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Güncelleniyor...';

    try {
        const r = await fetch('php/forgot.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                action: 'reset',
                token: document.getElementById('r-token').value,
                password: document.getElementById('r-pass').value,
                confirm: document.getElementById('r-pass2').value,
            })
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Hata');

        const res = document.getElementById('reset-result');
        res.style.display = '';
        res.innerHTML = `<div style="background:hsla(145,60%,30%,.2);border:1px solid hsla(145,60%,40%,.4);border-radius:var(--r-sm);padding:1rem;color:#4ade80;text-align:center">
            <i class="bi bi-check-circle-fill me-2"></i>${d.message}
        </div>`;
        toast(d.message, 'success');
        setTimeout(() => location.href = 'index.php', 2000);
    } catch(err) {
        toast(err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Şifremi Güncelle';
    }
});
</script>
</body>
</html>
