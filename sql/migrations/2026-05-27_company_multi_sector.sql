-- Vértice Pro — Migración 2026-05-27 (empresa multi-sector)
-- 1) Permitir que una empresa tenga múltiples sectores.
-- 2) Quitar el campo `size` (no es necesario).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS company_sector_links (
    company_id INT UNSIGNED NOT NULL,
    sector_id  INT UNSIGNED NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (company_id, sector_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id)  REFERENCES sectors(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- Migrar el sector_id actual a la tabla M:N como primario.
INSERT IGNORE INTO company_sector_links (company_id, sector_id, is_primary)
SELECT id, sector_id, 1 FROM companies WHERE sector_id IS NOT NULL;

-- Quitar columna size (ya no se usa).
ALTER TABLE companies DROP COLUMN size;
