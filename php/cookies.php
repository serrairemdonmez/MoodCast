<?php
/**
 * MoodCast - Cookie Yönetimi
 * GET  /php/cookies.php          → mevcut cookie'leri döndür
 * POST /php/cookies.php          → cookie kaydet (son şehir, tema tercihi)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

// ── GET: Cookie'leri oku ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse([
        'success'    => true,
        'last_city'  => sanitizeString($_COOKIE['mc_last_city']  ?? '', 100),
        'last_weather' => sanitizeString($_COOKIE['mc_last_weather'] ?? '', 20),
        'theme_pref' => sanitizeString($_COOKIE['mc_theme_pref'] ?? 'dark', 10),
        'visit_count'=> (int)($_COOKIE['mc_visit_count'] ?? 0),
    ]);
}

// ── POST: Cookie kaydet ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $secure   = false; // HTTPS'de true yapın
    $httponly = true;
    $samesite = 'Strict';
    $expire   = time() + (30 * 24 * 3600); // 30 gün

    $cookieOptions = [
        'expires'  => $expire,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite,
    ];

    $saved = [];

    // Son aranan şehri kaydet
    if (!empty($input['last_city'])) {
        $city = sanitizeString($input['last_city'], 100);
        setcookie('mc_last_city', $city, $cookieOptions);
        $saved[] = 'last_city';
    }

    // Son hava durumunu kaydet
    if (!empty($input['last_weather'])) {
        $weather = sanitizeString($input['last_weather'], 20);
        setcookie('mc_last_weather', $weather, $cookieOptions);
        $saved[] = 'last_weather';
    }

    // Ziyaret sayacını artır
    $visits = (int)($_COOKIE['mc_visit_count'] ?? 0) + 1;
    setcookie('mc_visit_count', (string)$visits, $cookieOptions);
    $saved[] = 'visit_count';

    jsonResponse(['success' => true, 'saved' => $saved, 'visit_count' => $visits]);
}

jsonResponse(['error' => 'Geçersiz istek.'], 400);
