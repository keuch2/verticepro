-- Vértice Pro — Migración 2026-05-27 (login público)
-- Sistema de login para profesionales y empresas registrados.
SET NAMES utf8mb4;

-- ==========================================
-- 1) Tokens de recuperación de contraseña
-- ==========================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token_hash (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ==========================================
-- 2) Asegurar que professionals y companies acepten user_id NULL
--    (ya está NULL en schema, pero en algunas instalaciones antiguas
--    podría no estarlo, lo afirmamos por idempotencia).
-- ==========================================
-- (no-op si ya está)
