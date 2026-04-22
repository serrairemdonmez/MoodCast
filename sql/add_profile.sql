-- Mevcut users tablosuna profil kolonları ekle
-- phpMyAdmin'de SQL sekmesinde çalıştır

USE moodcast;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS full_name   VARCHAR(100) DEFAULT NULL AFTER username,
    ADD COLUMN IF NOT EXISTS bio         VARCHAR(300) DEFAULT NULL AFTER full_name,
    ADD COLUMN IF NOT EXISTS avatar_url  VARCHAR(300) DEFAULT NULL AFTER bio,
    ADD COLUMN IF NOT EXISTS birth_date  DATE         DEFAULT NULL AFTER avatar_url,
    ADD COLUMN IF NOT EXISTS country     VARCHAR(100) DEFAULT NULL AFTER birth_date,
    ADD COLUMN IF NOT EXISTS fav_genre   VARCHAR(50)  DEFAULT NULL AFTER country,
    ADD COLUMN IF NOT EXISTS updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER fav_genre;
