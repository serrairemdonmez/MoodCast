# 🎵 MoodCast — Hava Durumuna Göre Müzik Öneri Sistemi

**Stack:** HTML5 · Bootstrap 5 · JavaScript (ES6+) · PHP 8+ · MySQL (XAMPP)

---

## 📁 Proje Yapısı

```
moodcast/
├── index.php                 ← Ana sayfa (HTML5 + PHP)
├── includes/
│   ├── config.php            ← DB bağlantısı, sabitler (require_once)
│   └── session.php           ← Güvenlik fonksiyonları (require_once)
├── php/
│   ├── weather.php           ← GET  — Hava durumu + müzik önerileri
│   ├── auth.php              ← POST — Kayıt / Giriş / Çıkış
│   └── favorites.php         ← GET (liste) / POST (ekle-sil)
├── css/
│   └── style.css             ← CSS renk fonksiyonları, animasyonlar
├── js/
│   └── app.js                ← Event yapıları, Date API, güvenlik
└── sql/
    └── moodcast.sql         ← Tüm tablolar + örnek veriler
```

---

## ⚙️ Kurulum (XAMPP)

### 1. Dosyaları Kopyala
```
C:\xampp\htdocs\moodcast\   ← Tüm proje buraya
```

### 2. Veritabanını Oluştur
XAMPP → phpMyAdmin → **İçe Aktar** → `sql/moodcast.sql` dosyasını seç → Çalıştır

### 3. API Anahtarı Al
1. https://openweathermap.org/api adresine git
2. Ücretsiz hesap oluştur → API Keys sekmesi
3. `includes/config.php` dosyasında:
   ```php
   define('OWM_API_KEY', 'BURAYA_ANAHTARINIZI_YAZIN');
   ```

### 4. Çalıştır
```
http://localhost/moodcast/
```

---

## 🔐 Güvenlik Özellikleri

| Özellik | Yöntem |
|---------|--------|
| SQL Injection | PDO Prepared Statements |
| XSS | `htmlspecialchars()` + DOM textContent |
| CSRF | Token (session tabanlı, her formda) |
| Şifre | `password_hash()` bcrypt cost=12 |
| Session Fixation | `session_regenerate_id()` login'de |
| Brute Force | Rate limiting (dosya tabanlı) |
| Clickjacking | `X-Frame-Options: SAMEORIGIN` |
| MIME Sniffing | `X-Content-Type-Options: nosniff` |
| Cookie Güvenliği | httponly + samesite=Strict |

---

## 🎯 HTTP Metod Seçim Gerekçesi

| Endpoint | Metod | Neden |
|----------|-------|-------|
| `weather.php` | **GET** | Sadece okuma; URL'den paylaşılabilir; cache'lenebilir |
| `auth.php` (login/register) | **POST** | Şifre URL'de görünmemeli; DB yazma; idempotent değil |
| `favorites.php` (liste) | **GET** | Okuma işlemi |
| `favorites.php` (ekle/sil) | **POST** | DB durumu değişiyor |

---

## 📦 include vs require — Neden require_once?

```php
// ❌ include: Dosya bulunamazsa uyarı verir, çalışmaya devam eder
include 'config.php';       // Güvensiz — DB bağlantısı olmadan devam eder

// ✅ require_once: Dosya bulunamazsa FATAL ERROR — güvenli
require_once 'config.php';  // Kritik dosyalar için zorunlu
```

**Kural:**
- `require_once` → Kritik dosyalar (config, session, DB)  
- `include` → İsteğe bağlı parçalar (widget, yardımcı şablonlar)

---

## 🎨 Renk Fonksiyonları (CSS)

```css
/* HSL renk fonksiyonları ile dinamik tema */
--color-primary: hsl(var(--hue-accent), 80%, 65%);

/* Hava durumuna göre otomatik renk geçişi */
body[data-weather="clear"]  { --weather-active: hsl(45, 95%, 60%); }
body[data-weather="rain"]   { --weather-active: hsl(210, 65%, 55%); }

/* rgba ile şeffaf gölgeler */
--glow-purple: 0 0 30px rgba(130, 80, 255, .5);
```

---

## 📅 JavaScript — Date API Kullanımı

```javascript
const now   = new Date();           // Anlık zaman
const saat  = now.getHours();       // Saat → selamlama
const dakika = now.getMinutes();    // Dakika
const gun   = now.getDay();         // Gün → Türkçe ad
const ay    = now.getMonth();       // Ay → Türkçe ad
```

---

## 🗄️ Veritabanı Şeması

```
users              → Kullanıcılar (bcrypt şifre)
weather_conditions → Hava kodu lookup tablosu
mood_music_map     → Hava kodu ↔ Müzik türü eşleşmesi
tracks             → Müzik parçaları havuzu
favorites          → user_id ↔ track_id (unique)
search_history     → Arama geçmişi logu
```

---

## 🧪 Demo Hesap

| Alan | Değer |
|------|-------|
| Kullanıcı Adı | `demo` |
| Şifre | `Test1234!` |

---

## 📋 Öğretmen Kontrol Listesi

- [x] HTML5 semantic elementler kullanıldı
- [x] Bootstrap 5 responsive grid kullanıldı
- [x] JavaScript event yapıları (addEventListener, delegasyon, CustomEvent)
- [x] Date fonksiyonu ile anlık saat/tarih/selamlama
- [x] Yazılım güvenliği (CSRF, XSS, SQL Injection, bcrypt, rate limit)
- [x] CSS renk fonksiyonları (hsl, rgba, CSS variables)
- [x] `require_once` kullanıldı (kritik dosyalarda)
- [x] Responsive tasarım
- [x] MySQL veritabanı bağlantısı (XAMPP)
- [x] Veritabanına gerçek bağlantı (API yanı sıra DB de kullanıldı)
- [x] GET ve POST metodları gerekçeli şekilde ayrıldı
- [x] JavaScript kullanıldı (TypeScript değil)
