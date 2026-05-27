-- Vértice Pro — Migración 2026-05-27 (flyer en ofertas y servicios)
-- Permite subir una imagen tipo flyer al publicar oferta de trabajo o servicio.
SET NAMES utf8mb4;

ALTER TABLE job_offers
    ADD COLUMN flyer_image VARCHAR(255) NULL AFTER description;

ALTER TABLE services
    ADD COLUMN flyer_image VARCHAR(255) NULL AFTER description;
