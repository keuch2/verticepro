-- Vértice Pro — Migración 2026-06-10
-- 1) Tipos de profesional ampliados (19 nuevos)
-- 2) Disciplinas: Psicología + Higiene Industrial
-- 3) Servicios ofrecidos por empresas (nueva taxonomía + M:N)
-- 4) Doble perfil: (sin cambios de schema, solo lógica de app)
-- 5) Postulaciones: estado + conversación
SET NAMES utf8mb4;

-- ==========================================
-- 1) Tipos de profesional — agregar 19 nuevos
-- ==========================================
INSERT IGNORE INTO professional_types (slug, name) VALUES
('tecnico-seguridad-ocupacional',     'Técnico en Seguridad Ocupacional'),
('ingenieria-civil-industrial-seguridad', 'Ingeniería Civil, Industrial, Seguridad Ocupacional y afines'),
('arquitectura',                       'Arquitectura'),
('medico-laboral',                     'Médico Laboral / Salud Ocupacional'),
('analista-seguridad-salud',           'Analista de Seguridad y Salud Ocupacional'),
('enfermero-laboral',                  'Enfermero Laboral'),
('psicologia',                         'Psicología'),
('gerente-jefe-calidad',               'Gerente / Jefe de Calidad'),
('auditor-interno-externo',            'Auditor Interno / Externo'),
('analista-asegurador-calidad',        'Analista / Asegurador de Calidad'),
('ingeniero-ambiental-forestal',       'Ingeniero Ambiental / Forestal / y afines'),
('consultor-ambiental',                'Consultor Ambiental'),
('regente-ambiental',                  'Regente Ambiental'),
('tecnico-gestion-residuos',           'Técnico en gestión de residuos / efluentes'),
('consultor-general-csms',             'Consultor general CSMS'),
('asesor-evaluador-riesgos-sms',       'Asesor / Evaluador de riesgos de SMS'),
('capacitador',                        'Capacitador'),
('gerente-analista-rrhh',              'Gerente / Analista de Recursos Humanos'),
('estudiante-pasante',                 'Estudiante / Pasante');

-- ==========================================
-- 2) Disciplinas — agregar 2 nuevas
-- ==========================================
INSERT IGNORE INTO disciplines (slug, name, color_token) VALUES
('psicologia',         'Psicología',         'naranja'),
('higiene-industrial', 'Higiene Industrial', 'naranja');

-- ==========================================
-- 3) Servicios ofrecidos por empresas (taxonomía nueva, separada de la tabla `services`
--    que ya existe para servicios profesionales en la bolsa de trabajo).
-- ==========================================
CREATE TABLE IF NOT EXISTS company_services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO company_services (slug, name) VALUES
('epp',                                 'Equipos de Protección Personal'),
('equipos-riesgos-especiales',          'Equipos para trabajos con riesgos especiales (altura, espacios confinados, alta tensión)'),
('senalizacion-loto',                   'Señalización y bloqueo (LOTO)'),
('senaletica',                          'Señalética'),
('kits-emergencia-primeros-auxilios',   'Kits de emergencia y Primeros Auxilios'),
('medicion-agentes-fisicos',            'Medición de agentes físicos (luz, ruido, calor, químicos)'),
('instrumentos-medicion-monitoreo',     'Instrumentos de medición y monitoreo'),
('insumos-control-ambiental',           'Insumos para control ambiental'),
('consultoria-sistemas-gestion',        'Consultoría en Sistemas de Gestión'),
('asesoria-legal',                      'Asesoría Legal'),
('medicina-laboral',                    'Medicina Laboral'),
('capacitacion-entrenamiento',          'Capacitación y Entrenamiento'),
('gestion-disposicion-residuos',        'Gestión y disposición de residuos'),
('control-plagas-desinfeccion',         'Control de plagas y desinfección'),
('mantenimiento-certificacion-equipos', 'Mantenimiento y certificación de equipos'),
('sistemas-pci',                        'Sistemas de Protección Contra Incendios (PCI)');

CREATE TABLE IF NOT EXISTS company_service_links (
    company_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (company_id, service_id),
    FOREIGN KEY (company_id) REFERENCES companies(id)         ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES company_services(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 5) Postulaciones: estado + conversación
-- ==========================================
ALTER TABLE job_interests
    ADD COLUMN status ENUM('received','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'received' AFTER message,
    ADD COLUMN status_updated_at DATETIME NULL AFTER status;

CREATE TABLE IF NOT EXISTS application_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    interest_id INT UNSIGNED NOT NULL,
    sender_role ENUM('company','professional') NOT NULL,
    sender_user_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interest_id)    REFERENCES job_interests(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES users(id)         ON DELETE SET NULL,
    INDEX idx_interest_time (interest_id, created_at)
) ENGINE=InnoDB;
