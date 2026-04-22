-- MoodCast Veritabanı Şeması
-- XAMPP / MySQL 5.7+
-- Çalıştırma: phpMyAdmin veya mysql -u root -p < moodcast.sql

CREATE DATABASE IF NOT EXISTS moodcast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE moodcast;

-- ─────────────────────────────────────────
-- KULLANICILAR
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- bcrypt hash
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login  DATETIME,
    is_active   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- HAVA DURUMLARI (sabit lookup tablosu)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS weather_conditions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)  NOT NULL UNIQUE,   -- 'clear', 'rain', 'snow', 'clouds', 'thunderstorm', 'drizzle', 'mist'
    label_tr    VARCHAR(50)  NOT NULL,
    icon        VARCHAR(10)  NOT NULL           -- emoji veya icon kodu
) ENGINE=InnoDB;

INSERT INTO weather_conditions (code, label_tr, icon) VALUES
('clear',        'Açık Hava',     '☀️'),
('clouds',       'Bulutlu',       '☁️'),
('rain',         'Yağmurlu',      '🌧️'),
('drizzle',      'Çiseleyen',     '🌦️'),
('thunderstorm', 'Fırtına',       '⛈️'),
('snow',         'Karlı',         '❄️'),
('mist',         'Sisli',         '🌫️'),
('haze',         'Dumanlı',       '🌫️'),
('fog',          'Yoğun Sis',     '🌁️');

-- ─────────────────────────────────────────
-- MÜZİK TÜRÜ ↔ HAVA DURUMU EŞLEŞTİRMESİ
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mood_music_map (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    weather_code    VARCHAR(20) NOT NULL,
    genre           VARCHAR(50) NOT NULL,
    description_tr  VARCHAR(200),
    FOREIGN KEY (weather_code) REFERENCES weather_conditions(code) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO mood_music_map (weather_code, genre, description_tr) VALUES
('clear',        'Pop / Dance',        'Güneşli havada enerjik, neşeli ritimler'),
('clear',        'Indie Summer',       'Keyifli açık hava melodileri'),
('clouds',       'Chillout / Lo-fi',   'Bulutlu günler için sakin arka plan müziği'),
('clouds',       'Acoustic',           'Yumuşak gitar ve vokal armonileri'),
('rain',         'Jazz',               'Yağmur sesi eşliğinde klasik caz'),
('rain',         'Classical Piano',    'Romantik piyano eserleri'),
('drizzle',      'Bossa Nova',         'Hafif yağmurda Brezilyalı ritimler'),
('thunderstorm', 'Epic / Cinematic',   'Dramatik orkestral müzik'),
('thunderstorm', 'Heavy Metal',        'Güçlü riff\'ler ve elektrik gitarlar'),
('snow',         'Ambient',            'Sessiz karlı günler için ambiyans'),
('snow',         'Classical',          'Kış sonatları ve klasik eserler'),
('mist',         'Chillhop',           'Sisli sabahlar için sakin hip-hop'),
('haze',         'Dream Pop',          'Bulanık havada hayalci melodiler'),
('fog',          'Drone / Ambient',    'Yoğun sis için derin ambiyans sesleri');

-- ─────────────────────────────────────────
-- MÜZİK PARÇALARI (DB tabanlı öneri havuzu)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tracks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    artist      VARCHAR(100) NOT NULL,
    genre       VARCHAR(50)  NOT NULL,
    duration    VARCHAR(10),                    -- '3:45'
    youtube_id  VARCHAR(20),                    -- embed için
    cover_url   VARCHAR(300),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tracks (title, artist, genre, duration, youtube_id) VALUES
-- Pop / Dance
('Levitating',          'Dua Lipa',              'Pop / Dance',     '3:23', 'TUVcZfQe-Kw'),
('As It Was',           'Harry Styles',           'Pop / Dance',     '2:37', 'H5v3kku4y6Q'),
('Blinding Lights',     'The Weeknd',             'Pop / Dance',     '3:20', '4NRXx6U8ABQ'),
-- Indie Summer
('Good As Hell',        'Lizzo',                  'Indie Summer',    '2:39', 'SmbmeOgWsqE'),
('Golden',              'Harry Styles',           'Indie Summer',    '3:28', 'P3cffdsEXXw'),
-- Chillout / Lo-fi
('Clair de Lune',       'Claude Debussy',         'Chillout / Lo-fi','5:00', 'CvFH_6DNRCY'),
('Lofi Hip Hop Mix',    'ChilledCow',             'Chillout / Lo-fi','60:00','5qap5aO4i9A'),
-- Acoustic
('The Night We Met',    'Lord Huron',             'Acoustic',        '3:28', 'KtlgYxa6BMU'),
('Fast Car',            'Tracy Chapman',          'Acoustic',        '4:57', 'AIOAlaAuH50'),
-- Jazz
('Take Five',           'Dave Brubeck',           'Jazz',            '5:24', 'vmDDOFXSgAs'),
('So What',             'Miles Davis',            'Jazz',            '9:25', 'zqNTltOGh5c'),
('Fly Me to the Moon',  'Frank Sinatra',          'Jazz',            '2:28', 'ZEcqHA7dbwM'),
-- Classical Piano
('Moonlight Sonata',    'Beethoven',              'Classical Piano', '5:47', '_mVW8tgGY_w'),
('Raindrop Prelude',    'Chopin',                 'Classical Piano', '4:40', 'XeX4X_1_lo0'),
-- Bossa Nova
('The Girl from Ipanema','João Gilberto',         'Bossa Nova',      '4:39', 'UJkxFhFer0s'),
('Garota de Ipanema',   'Astrud Gilberto',        'Bossa Nova',      '5:45', 'LNGOhFOXWu8'),
-- Epic / Cinematic
('Time',                'Hans Zimmer',            'Epic / Cinematic','4:35', 'RxabLA7UQ9k'),
('Now We Are Free',     'Hans Zimmer',            'Epic / Cinematic','4:42', 'l2_G4LOd7BI'),
-- Heavy Metal
('Enter Sandman',       'Metallica',              'Heavy Metal',     '5:31', 'CD-E-LDc384'),
('Paranoid',            'Black Sabbath',          'Heavy Metal',     '2:48', 'uk_wUT1CvWM'),
-- Ambient
('Experience',          'Ludovico Einaudi',       'Ambient',         '5:15', 'hN_q-_nGv4U'),
('Weightless',          'Marconi Union',          'Ambient',         '8:09', 'UfcAVejslrU'),
-- Classical
('Four Seasons - Winter','Vivaldi',               'Classical',       '8:20', 'TkDZO8XVNXM'),
('Swan Lake',           'Tchaikovsky',            'Classical',       '6:13', 'yGh0MKCjCFI'),
-- Chillhop
('Coffee Shop Chillhop','Various Artists',        'Chillhop',        '60:00','Dx5qFachd3A'),
-- Dream Pop
('Myth',                'Beach House',            'Dream Pop',       '4:19', '3R1LfzixSig'),
-- Drone / Ambient
('An Ending (Ascent)',  'Brian Eno',              'Drone / Ambient', '4:14', 'A_6jNNmi3oo');

-- ─────────────────────────────────────────
-- FAVORİLER
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS favorites (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    track_id    INT NOT NULL,
    added_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY  uq_fav (user_id, track_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (track_id) REFERENCES tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- ARAMA / HAVA DURUMU GEÇMİŞİ (log)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS search_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,                         -- NULL = misafir
    city            VARCHAR(100) NOT NULL,
    weather_code    VARCHAR(20),
    temperature     DECIMAL(5,2),
    searched_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- DEMO KULLANICI (şifre: Test1234!)
-- ─────────────────────────────────────────
INSERT INTO users (username, email, password) VALUES
('demo', 'demo@moodcast.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uHsbzbui9W');
