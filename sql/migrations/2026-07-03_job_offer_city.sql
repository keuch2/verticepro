-- Agrega localidad (ciudad) a las ofertas de la Bolsa de Trabajo.
-- La oferta puede indicar el país y, opcionalmente, la ciudad concreta.
ALTER TABLE job_offers
    ADD COLUMN city_id INT UNSIGNED NULL AFTER country_id,
    ADD CONSTRAINT fk_job_offers_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL;
