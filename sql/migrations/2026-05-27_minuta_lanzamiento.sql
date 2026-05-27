-- Vértice Pro — Migración 2026-05-27
-- Cambios derivados de la minuta del 2026-05-11 (foco Paraguay).
-- Idempotente cuando es posible. Ejecutar en orden.
SET NAMES utf8mb4;

-- ==========================================
-- 1) Departamentos (nueva taxonomía geográfica)
-- ==========================================
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    country_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
    INDEX idx_country (country_id, sort_order)
) ENGINE=InnoDB;

ALTER TABLE cities
    ADD COLUMN department_id INT UNSIGNED NULL AFTER country_id,
    ADD CONSTRAINT fk_cities_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- ==========================================
-- 2) Companies: agregar city_id (hoy solo tiene country_id)
-- ==========================================
ALTER TABLE companies
    ADD COLUMN city_id INT UNSIGNED NULL AFTER country_id,
    ADD CONSTRAINT fk_companies_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;

-- ==========================================
-- 3) Visibilidad de campos y opt-in de notificaciones
-- ==========================================
ALTER TABLE professionals
    ADD COLUMN visibility_email   TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN visibility_linkedin TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN visibility_website TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN notifications_opt_in TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE companies
    ADD COLUMN visibility_email   TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN visibility_website TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN notifications_opt_in TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE users
    ADD COLUMN notifications_opt_in TINYINT(1) NOT NULL DEFAULT 1;

-- ==========================================
-- 4) Notificaciones (in-app + email)
-- ==========================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NULL,
    link VARCHAR(255) NULL,
    read_at DATETIME NULL,
    email_sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, read_at),
    INDEX idx_user_time (user_id, created_at DESC)
) ENGINE=InnoDB;

-- ==========================================
-- 5) Eventos (calendario)
-- ==========================================
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(200) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    modality ENUM('presencial','virtual','hibrido') NULL,
    location VARCHAR(255) NULL,
    url VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    discipline_id INT UNSIGNED NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id) ON DELETE SET NULL,
    INDEX idx_status_starts (status, starts_at),
    INDEX idx_starts (starts_at)
) ENGINE=InnoDB;

-- ==========================================
-- 6) Bolsa: interesados en ofertas
-- ==========================================
CREATE TABLE IF NOT EXISTS job_interests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offer_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    guest_name VARCHAR(150) NULL,
    guest_email VARCHAR(190) NULL,
    professional_id INT UNSIGNED NULL,
    message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_offer_email (offer_id, guest_email),
    FOREIGN KEY (offer_id) REFERENCES job_offers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE SET NULL,
    INDEX idx_offer (offer_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ==========================================
-- 7) Aportes colaborativos (subida con revisión)
-- ==========================================
CREATE TABLE IF NOT EXISTS user_contributions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    guest_name VARCHAR(150) NULL,
    guest_email VARCHAR(190) NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NULL,
    file_mime VARCHAR(120) NULL,
    category VARCHAR(80) NULL,
    discipline_id INT UNSIGNED NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    review_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status, created_at DESC)
) ENGINE=InnoDB;

-- ==========================================
-- 8) Biblioteca — Clipping de noticias
-- ==========================================
CREATE TABLE IF NOT EXISTS news_clippings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    source_name VARCHAR(120) NOT NULL,
    source_url VARCHAR(500) NOT NULL,
    summary TEXT NULL,
    published_at DATE NOT NULL,
    discipline_id INT UNSIGNED NULL,
    section_slug VARCHAR(50) NULL,
    thumb_image VARCHAR(255) NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discipline_id) REFERENCES disciplines(id) ON DELETE SET NULL,
    FOREIGN KEY (section_slug) REFERENCES sections(slug) ON DELETE SET NULL,
    INDEX idx_status_pub (status, published_at DESC)
) ENGINE=InnoDB;

-- ==========================================
-- 9) Países: priorizar Paraguay como mercado principal
-- ==========================================
ALTER TABLE countries
    ADD COLUMN sort_order INT NOT NULL DEFAULT 100 AFTER name;

UPDATE countries SET sort_order = 1 WHERE slug = 'paraguay';
UPDATE countries SET sort_order = 100 WHERE slug <> 'paraguay';

-- ==========================================
-- 10) Seed: 17 departamentos de Paraguay + Asunción
-- ==========================================
INSERT INTO departments (slug, name, country_id, sort_order)
SELECT v.slug, v.name, c.id, v.sort_order FROM (
    SELECT 'asuncion'           AS slug, 'Asunción (Distrito Capital)' AS name, 1  AS sort_order UNION ALL
    SELECT 'central',                 'Central',                                  2  UNION ALL
    SELECT 'alto-parana',             'Alto Paraná',                              3  UNION ALL
    SELECT 'itapua',                  'Itapúa',                                   4  UNION ALL
    SELECT 'caaguazu',                'Caaguazú',                                 5  UNION ALL
    SELECT 'concepcion',              'Concepción',                               6  UNION ALL
    SELECT 'san-pedro',               'San Pedro',                                7  UNION ALL
    SELECT 'cordillera',              'Cordillera',                               8  UNION ALL
    SELECT 'guaira',                  'Guairá',                                   9  UNION ALL
    SELECT 'caazapa',                 'Caazapá',                                  10 UNION ALL
    SELECT 'misiones',                'Misiones',                                 11 UNION ALL
    SELECT 'paraguari',               'Paraguarí',                                12 UNION ALL
    SELECT 'neembucu',                'Ñeembucú',                                 13 UNION ALL
    SELECT 'amambay',                 'Amambay',                                  14 UNION ALL
    SELECT 'canindeyu',               'Canindeyú',                                15 UNION ALL
    SELECT 'presidente-hayes',        'Presidente Hayes',                         16 UNION ALL
    SELECT 'boqueron',                'Boquerón',                                 17 UNION ALL
    SELECT 'alto-paraguay',           'Alto Paraguay',                            18
) v
JOIN countries c ON c.slug = 'paraguay';

-- ==========================================
-- 11) Seed: ciudades principales de Paraguay (vinculadas a departamento)
-- ==========================================
-- Asunción (distrito capital): la ciudad ya existe; vincularla a su departamento.
UPDATE cities ci
JOIN countries co ON co.id = ci.country_id AND co.slug = 'paraguay'
JOIN departments d ON d.country_id = co.id AND d.slug = 'asuncion'
SET ci.department_id = d.id
WHERE ci.slug = 'asuncion';

INSERT INTO cities (slug, name, country_id, department_id)
SELECT v.slug, v.name, co.id, d.id
FROM (
    SELECT 'ciudad-del-este'   AS slug, 'Ciudad del Este'   AS name, 'alto-parana' AS dept_slug UNION ALL
    SELECT 'encarnacion',           'Encarnación',           'itapua'       UNION ALL
    SELECT 'san-lorenzo',           'San Lorenzo',           'central'      UNION ALL
    SELECT 'luque',                 'Luque',                 'central'      UNION ALL
    SELECT 'capiata',               'Capiatá',               'central'      UNION ALL
    SELECT 'lambare',               'Lambaré',               'central'      UNION ALL
    SELECT 'fernando-de-la-mora',   'Fernando de la Mora',   'central'      UNION ALL
    SELECT 'nemby',                 'Ñemby',                 'central'      UNION ALL
    SELECT 'mariano-roque-alonso',  'Mariano Roque Alonso',  'central'      UNION ALL
    SELECT 'itaugua',               'Itauguá',               'central'      UNION ALL
    SELECT 'villa-elisa',           'Villa Elisa',           'central'      UNION ALL
    SELECT 'limpio',                'Limpio',                'central'      UNION ALL
    SELECT 'pedro-juan-caballero',  'Pedro Juan Caballero',  'amambay'      UNION ALL
    SELECT 'coronel-oviedo',        'Coronel Oviedo',        'caaguazu'     UNION ALL
    SELECT 'caaguazu-ciudad',       'Caaguazú',              'caaguazu'     UNION ALL
    SELECT 'villarrica',            'Villarrica',            'guaira'       UNION ALL
    SELECT 'concepcion-ciudad',     'Concepción',            'concepcion'   UNION ALL
    SELECT 'pilar',                 'Pilar',                 'neembucu'     UNION ALL
    SELECT 'caacupe',                'Caacupé',              'cordillera'   UNION ALL
    SELECT 'paraguari-ciudad',      'Paraguarí',             'paraguari'    UNION ALL
    SELECT 'caazapa-ciudad',        'Caazapá',               'caazapa'      UNION ALL
    SELECT 'san-juan-bautista',     'San Juan Bautista',     'misiones'     UNION ALL
    SELECT 'san-pedro-ycuamandyju', 'San Pedro de Ycuamandyyú','san-pedro'  UNION ALL
    SELECT 'salto-del-guaira',      'Salto del Guairá',      'canindeyu'    UNION ALL
    SELECT 'villa-hayes',           'Villa Hayes',           'presidente-hayes' UNION ALL
    SELECT 'filadelfia',            'Filadelfia',            'boqueron'     UNION ALL
    SELECT 'fuerte-olimpo',         'Fuerte Olimpo',         'alto-paraguay'
) v
JOIN countries co ON co.slug = 'paraguay'
JOIN departments d ON d.country_id = co.id AND d.slug = v.dept_slug
WHERE NOT EXISTS (SELECT 1 FROM cities x WHERE x.slug = v.slug);

-- ==========================================
-- 12) Términos y condiciones — placeholder de sección
-- (Página estática; no requiere tabla. Ver terminos.php)
-- ==========================================
