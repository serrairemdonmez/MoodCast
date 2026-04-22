<?php
/**
 * MoodCast - Hava Durumu API İşleyicisi
 * GET /php/weather.php?city=Istanbul
 * Hem OpenWeatherMap API'si hem de DB kayıtlarını kullanır.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Yalnızca GET kabul et ─────────────────────────────────────────────────────
// Hava durumu OKUMA işlemi → GET semantiği doğru
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Yalnızca GET metodu kabul edilir.'], 405);
}

// ─── Rate limit: IP başına dakikada 20 istek ──────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimitCheck('weather_' . $ip, 20, 60)) {
    jsonResponse(['error' => 'Çok fazla istek. Lütfen bekleyin.'], 429);
}

// ─── Şehir parametresini al ve doğrula ────────────────────────────────────────
$city = sanitizeString($_GET['city'] ?? '', 100);
if (empty($city)) {
    jsonResponse(['error' => 'Şehir adı gereklidir.'], 400);
}


// ─── TEST MODU (sadece geliştirme ortamında) ──────────────────────────────────
// Kullanım: ?city=TEST_KAR veya ?city=TEST_YAGMUR vs.
$testCodes = [
    'TEST_KAR'     => ['snow',         '❄️',  'Karlı',      -5.0,  75, 3.2],
    'TEST_YAGMUR'  => ['rain',         '🌧️', 'Yağmurlu',   12.0,  88, 5.1],
    'TEST_GUNES'   => ['clear',        '☀️',  'Açık Hava',  28.0,  25, 1.8],
    'TEST_FIRTINA' => ['thunderstorm', '⛈️',  'Fırtına',     9.0,  92, 8.5],
    'TEST_BULUT'   => ['clouds',       '☁️',  'Bulutlu',    15.0,  60, 3.0],
    'TEST_SIS'     => ['mist',         '🌫️', 'Sisli',       8.0,  95, 1.2],
    'TEST_CISE'    => ['drizzle',      '🌦️', 'Çiseleyen',  11.0,  82, 2.4],
];

if (APP_ENV === 'development' && isset($testCodes[strtoupper($city)])) {
    $t = $testCodes[strtoupper($city)];
    $pdo  = Database::getInstance();
    $weatherMain = $t[0];

    $trackStmt = $pdo->prepare('
        SELECT t.id, t.title, t.artist, t.genre, t.duration, t.youtube_id,
               m.description_tr AS mood_desc
        FROM   tracks t
        JOIN   mood_music_map m ON m.genre = t.genre
        WHERE  m.weather_code = ?
        ORDER  BY RAND() LIMIT 8
    ');
    $trackStmt->execute([$weatherMain]);
    $tracks = $trackStmt->fetchAll();

    $genreStmt = $pdo->prepare('SELECT DISTINCT genre, description_tr FROM mood_music_map WHERE weather_code = ?');
    $genreStmt->execute([$weatherMain]);
    $genres = $genreStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'weather' => [
            'city'        => 'Test Şehri',
            'country'     => 'TR',
            'code'        => $t[0],
            'label_tr'    => $t[2],
            'emoji'       => $t[1],
            'description' => strtolower($t[2]),
            'temperature' => $t[3],
            'humidity'    => $t[4],
            'wind_speed'  => $t[5],
            'icon_url'    => '',
        ],
        'genres' => $genres,
        'tracks' => $tracks,
    ]);
}

// ─── OpenWeatherMap API çağrısı ───────────────────────────────────────────────
$apiUrl = OWM_BASE_URL . '?' . http_build_query([
    'q'     => $city,
    'appid' => OWM_API_KEY,
    'units' => 'metric',
    'lang'  => 'tr',
]);

$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$raw = @file_get_contents($apiUrl, false, $ctx);

if ($raw === false) {
    jsonResponse(['error' => 'Hava durumu servisi erişilemez.'], 503);
}

$apiData = json_decode($raw, true);

if (!$apiData || ($apiData['cod'] ?? '') != 200) {
    $msg = $apiData['message'] ?? 'Şehir bulunamadı.';
    jsonResponse(['error' => ucfirst($msg)], 404);
}

// ─── API verisini ayrıştır ────────────────────────────────────────────────────
$weatherMain = strtolower($apiData['weather'][0]['main'] ?? 'clear'); // 'Rain', 'Clear'...
$description  = $apiData['weather'][0]['description'] ?? '';
$temperature  = round($apiData['main']['temp'] ?? 0, 1);
$humidity     = $apiData['main']['humidity'] ?? 0;
$windSpeed    = round($apiData['wind']['speed'] ?? 0, 1);
$iconCode     = $apiData['weather'][0]['icon'] ?? '01d';
$cityName     = $apiData['name'] ?? $city;
$country      = $apiData['sys']['country'] ?? '';

// ─── DB: weather_conditions tablosundan ek bilgi al ──────────────────────────
// Veritabanı → API fallback zinciri
$pdo  = Database::getInstance();
$stmt = $pdo->prepare('SELECT label_tr, icon FROM weather_conditions WHERE code = ? LIMIT 1');
$stmt->execute([$weatherMain]);
$weatherRow = $stmt->fetch();

$labelTr  = $weatherRow['label_tr'] ?? ucfirst($description);
$emoji    = $weatherRow['icon']     ?? '🌤️';

// ─── DB: Müzik önerilerini çek (JOIN kullan) ──────────────────────────────────
$trackStmt = $pdo->prepare('
    SELECT t.id, t.title, t.artist, t.genre, t.duration, t.youtube_id,
           m.description_tr AS mood_desc
    FROM   tracks t
    JOIN   mood_music_map m ON m.genre = t.genre
    WHERE  m.weather_code = ?
    ORDER  BY RAND()
    LIMIT  8
');
$trackStmt->execute([$weatherMain]);
$tracks = $trackStmt->fetchAll();

// ─── DB: Genre listesini al ───────────────────────────────────────────────────
$genreStmt = $pdo->prepare('
    SELECT DISTINCT genre, description_tr
    FROM   mood_music_map
    WHERE  weather_code = ?
');
$genreStmt->execute([$weatherMain]);
$genres = $genreStmt->fetchAll();

// ─── Arama geçmişine kaydet (POST değil, arka planda kayıt) ───────────────────
$userId = currentUserId();
$histStmt = $pdo->prepare('
    INSERT INTO search_history (user_id, city, weather_code, temperature)
    VALUES (?, ?, ?, ?)
');
$histStmt->execute([$userId, $cityName, $weatherMain, $temperature]);

// ─── Yanıt ────────────────────────────────────────────────────────────────────
jsonResponse([
    'success'  => true,
    'weather'  => [
        'city'        => $cityName,
        'country'     => $country,
        'code'        => $weatherMain,
        'label_tr'    => $labelTr,
        'emoji'       => $emoji,
        'description' => $description,
        'temperature' => $temperature,
        'humidity'    => $humidity,
        'wind_speed'  => $windSpeed,
        'icon_url'    => "https://openweathermap.org/img/wn/{$iconCode}@2x.png",
    ],
    'genres'   => $genres,
    'tracks'   => $tracks,
]);
