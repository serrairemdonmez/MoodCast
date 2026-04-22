<?php
/**
 * MoodCast - iTunes Search API
 * GET /php/itunes.php?genre=Jazz&weather=rain
 * iTunes API ücretsiz, kayıt gerektirmez, 30sn preview verir
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Yalnızca GET.'], 405);
}

$genre   = sanitizeString($_GET['genre']   ?? 'pop', 50);
$weather = sanitizeString($_GET['weather'] ?? 'clear', 20);

// Hava → arama terimi eşleştirmesi
$searchMap = [
    'clear'        => ['happy pop hits', 'summer dance', 'feel good music'],
    'clouds'       => ['indie acoustic', 'chill lofi', 'soft rock'],
    'rain'         => ['jazz classics', 'rainy day piano', 'blues'],
    'drizzle'      => ['bossa nova', 'cafe music', 'acoustic guitar'],
    'thunderstorm' => ['epic cinematic', 'heavy metal', 'dramatic orchestra'],
    'snow'         => ['classical piano winter', 'ambient snow', 'christmas instrumental'],
    'mist'         => ['chillhop beats', 'dream pop', 'lo fi hip hop'],
    'haze'         => ['dream pop', 'shoegaze', 'ethereal'],
    'fog'          => ['ambient drone', 'dark ambient', 'atmospheric'],
];

$terms = $searchMap[$weather] ?? ['pop hits'];
$term  = $terms[array_rand($terms)];

// Türkçe şarkı eklemek için bazen Türkçe arama yap
$turkishTerms = ['türkçe pop', 'türk müziği', 'turkish pop', 'aşk şarkısı'];
$addTurkish = rand(0, 2) === 0; // %33 ihtimalle Türkçe şarkı ekle

$url = 'https://itunes.apple.com/search?' . http_build_query([
    'term'     => $term,
    'media'    => 'music',
    'entity'   => 'song',
    'limit'    => 20,
    'explicit' => 'No',
]);

$ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'MoodCast/1.0']]);
$raw = @file_get_contents($url, false, $ctx);

if ($raw === false) {
    jsonResponse(['error' => 'iTunes servisine erişilemedi.'], 503);
}

$data = json_decode($raw, true);

if (!$data || !isset($data['results'])) {
    jsonResponse(['error' => 'Sonuç alınamadı.'], 500);
}

// Sonuçları temizle
$tracks = [];
foreach ($data['results'] as $r) {
    if (empty($r['previewUrl'])) continue; // Preview olmayanı atla

    $tracks[] = [
        'id'           => $r['trackId'] ?? rand(10000, 99999),
        'title'        => $r['trackName']     ?? 'Bilinmiyor',
        'artist'       => $r['artistName']    ?? 'Bilinmiyor',
        'album'        => $r['collectionName'] ?? '',
        'genre'        => $r['primaryGenreName'] ?? $genre,
        'duration_ms'  => $r['trackTimeMillis'] ?? 0,
        'duration'     => isset($r['trackTimeMillis'])
                          ? sprintf('%d:%02d', intdiv($r['trackTimeMillis'], 60000), intdiv($r['trackTimeMillis'] % 60000, 1000))
                          : '--:--',
        'preview_url'  => $r['previewUrl'],   // 30sn MP3
        'artwork'      => str_replace('100x100', '300x300', $r['artworkUrl100'] ?? ''),
        'itunes_url'   => $r['trackViewUrl']  ?? '#',
    ];
}

// Türkçe şarkı ekle
if ($addTurkish) {
    $trTerm = $turkishTerms[array_rand($turkishTerms)];
    $trUrl  = 'https://itunes.apple.com/search?' . http_build_query([
        'term' => $trTerm, 'media' => 'music', 'entity' => 'song',
        'limit' => 8, 'country' => 'TR', 'explicit' => 'No',
    ]);
    $trRaw = @file_get_contents($trUrl, false, $ctx);
    if ($trRaw) {
        $trData = json_decode($trRaw, true);
        foreach (($trData['results'] ?? []) as $r) {
            if (empty($r['previewUrl'])) continue;
            $tracks[] = [
                'id'          => $r['trackId'] ?? rand(10000, 99999),
                'title'       => $r['trackName']     ?? 'Bilinmiyor',
                'artist'      => $r['artistName']    ?? 'Bilinmiyor',
                'album'       => $r['collectionName'] ?? '',
                'genre'       => 'Türkçe Pop',
                'duration_ms' => $r['trackTimeMillis'] ?? 0,
                'duration'    => isset($r['trackTimeMillis'])
                                 ? sprintf('%d:%02d', intdiv($r['trackTimeMillis'], 60000), intdiv($r['trackTimeMillis'] % 60000, 1000))
                                 : '--:--',
                'preview_url' => $r['previewUrl'],
                'artwork'     => str_replace('100x100', '300x300', $r['artworkUrl100'] ?? ''),
                'itunes_url'  => $r['trackViewUrl'] ?? '#',
            ];
        }
    }
}

// Karıştır ve 12 tane al
shuffle($tracks);
$tracks = array_slice($tracks, 0, 12);

jsonResponse(['success' => true, 'tracks' => $tracks, 'term' => $term]);
