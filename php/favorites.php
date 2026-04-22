<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

startSecureSession();
header('Content-Type: application/json; charset=utf-8');

// Giriş kontrolü — requireLogin() yerine manuel kontrol
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Bu işlem için giriş yapmalısınız.'], 401);
}

$pdo = Database::getInstance();

// GET — favori listesi
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = $pdo->prepare('
        SELECT t.id, t.title, t.artist, t.genre, t.duration, t.youtube_id, f.added_at 
        FROM favorites f 
        JOIN tracks t ON t.id = f.track_id 
        WHERE f.user_id = ? 
        ORDER BY f.added_at DESC
    ');
    $s->execute([currentUserId()]);
    jsonResponse(['success' => true, 'favorites' => $s->fetchAll()]);
}

// POST — ekle / sil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action  = sanitizeString($input['action'] ?? '', 10);
    $trackId = (int)($input['track_id'] ?? 0);

    if (!$trackId) jsonResponse(['error' => 'Geçersiz parça.'], 400);

    if ($action === 'add') {
        // tracks tablosunda olmasa bile ekle (iTunes parçaları DB'de yok)
        // Önce tracks tablosuna ekle, yoksa insert et
        $chk = $pdo->prepare('SELECT id FROM tracks WHERE id = ? LIMIT 1');
        $chk->execute([$trackId]);
        if (!$chk->fetch()) {
            // iTunes track — önce tracks tablosuna ekle
            $title  = sanitizeString($input['title']  ?? 'Bilinmiyor', 150);
            $artist = sanitizeString($input['artist'] ?? 'Bilinmiyor', 100);
            $genre  = sanitizeString($input['genre']  ?? 'Pop', 50);
            $ins = $pdo->prepare('INSERT IGNORE INTO tracks (id, title, artist, genre) VALUES (?, ?, ?, ?)');
            $ins->execute([$trackId, $title, $artist, $genre]);
        }
        $pdo->prepare('INSERT IGNORE INTO favorites (user_id, track_id) VALUES (?, ?)')->execute([currentUserId(), $trackId]);
        jsonResponse(['success' => true]);
    }

    if ($action === 'remove') {
        $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND track_id = ?')->execute([currentUserId(), $trackId]);
        jsonResponse(['success' => true]);
    }
}

jsonResponse(['error' => 'Geçersiz istek.'], 400);
