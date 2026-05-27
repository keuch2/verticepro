-- Vértice Pro — Migración 2026-05-27 (settings + ads + empresa)
-- Configuración key-value, módulo de publicidad, perfil público de empresa.
SET NAMES utf8mb4;

-- ==========================================
-- 1) Settings (configuración del sitio)
-- ==========================================
CREATE TABLE IF NOT EXISTS settings (
    `key`       VARCHAR(80) PRIMARY KEY,
    `value`     TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Defaults
INSERT INTO settings (`key`, `value`) VALUES
('site.name',               'Vértice Pro'),
('site.tagline',            'Plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional y medio ambiente.'),
('contact.email',           'contacto@verticepro.com.py'),
('contact.phone',           ''),
('contact.address',         'Asunción, Paraguay'),
('social.linkedin',         ''),
('social.twitter',          ''),
('social.youtube',          ''),
('social.facebook',         ''),
('social.instagram',        ''),
('smtp.enabled',            '0'),
('smtp.host',               ''),
('smtp.port',               '587'),
('smtp.user',               ''),
('smtp.pass',               ''),
('smtp.from_email',         'no-reply@verticepro.com.py'),
('smtp.from_name',          'Vértice Pro'),
('smtp.encryption',         'tls'),
('seo.meta_description',    'Plataforma editorial y red profesional para especialistas en calidad, seguridad, salud ocupacional y medio ambiente en Paraguay.')
ON DUPLICATE KEY UPDATE `value` = `value`;

-- ==========================================
-- 2) Publicidad (banners por slot)
-- ==========================================
CREATE TABLE IF NOT EXISTS ads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slot ENUM('header_top', 'sidebar', 'between_articles', 'footer', 'home_hero') NOT NULL,
    image_path VARCHAR(255) NULL,
    html_content TEXT NULL,
    target_url VARCHAR(500) NULL,
    alt VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    status ENUM('active', 'paused') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slot_status (slot, status, sort_order)
) ENGINE=InnoDB;

-- ==========================================
-- 3) Asociación profesionales ↔ empresa
-- ==========================================
ALTER TABLE professionals
    ADD COLUMN company_id INT UNSIGNED NULL AFTER user_id,
    ADD CONSTRAINT fk_professionals_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    ADD INDEX idx_company (company_id);

-- ==========================================
-- 4) Teléfono / WhatsApp para profesionales y empresas
-- ==========================================
ALTER TABLE professionals
    ADD COLUMN phone VARCHAR(50) NULL AFTER linkedin,
    ADD COLUMN visibility_phone TINYINT(1) NOT NULL DEFAULT 0 AFTER visibility_linkedin;

ALTER TABLE companies
    ADD COLUMN phone VARCHAR(50) NULL AFTER website,
    ADD COLUMN visibility_phone TINYINT(1) NOT NULL DEFAULT 0 AFTER visibility_email;
