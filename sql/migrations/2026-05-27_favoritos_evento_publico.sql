-- Vértice Pro — Migración 2026-05-27 (favoritos + evento público)
-- 1) job_interests: agregar índice único sobre (offer_id, user_id) para evitar duplicados
--    cuando el interesado es un usuario logueado.
-- 2) events: nueva columna proposer_email para registrar quién propuso un evento
--    desde el formulario público (status=draft hasta aprobación admin).
SET NAMES utf8mb4;

-- Índice único para evitar duplicados de interés por usuario logueado.
-- Tolerante si ya existe (intento + ignore vía un wrapper).
ALTER TABLE job_interests
    ADD UNIQUE KEY uniq_offer_user (offer_id, user_id);

ALTER TABLE events
    ADD COLUMN proposer_name  VARCHAR(150) NULL AFTER url,
    ADD COLUMN proposer_email VARCHAR(190) NULL AFTER proposer_name;
