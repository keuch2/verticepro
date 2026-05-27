-- Vértice Pro — Migración 2026-05-27 (multi-tipo profesional)
-- 1) Permitir que un profesional tenga múltiples tipos (consultor + auditor + academia, etc).
-- 2) (la foto de perfil se gestiona con la columna existente avatar_image; el registro
--    público ya tiene infra de upload, sólo le falta el campo en el form).
SET NAMES utf8mb4;

-- Tabla M:N
CREATE TABLE IF NOT EXISTS professional_type_links (
    professional_id INT UNSIGNED NOT NULL,
    type_id         INT UNSIGNED NOT NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (professional_id, type_id),
    FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    FOREIGN KEY (type_id)         REFERENCES professional_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Migración de datos: copiar el type_id actual a la tabla M:N marcándolo como primario.
INSERT IGNORE INTO professional_type_links (professional_id, type_id, is_primary)
SELECT id, type_id, 1 FROM professionals WHERE type_id IS NOT NULL;

-- professionals.type_id queda como "tipo primario" cache (no se elimina la columna para
-- no romper queries existentes y BASE_SELECT del repo).
