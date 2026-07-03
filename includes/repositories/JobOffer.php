<?php
require_once __DIR__ . '/../db.php';

class JobOfferRepo {
    public static function all(): array {
        return DB::all('SELECT j.*, c.name company_name, c.slug company_slug, co.name country_name, co.slug country_slug,
                               ci.name city_name
                        FROM job_offers j
                        JOIN companies c ON c.id = j.company_id
                        LEFT JOIN countries co ON co.id = j.country_id
                        LEFT JOIN cities ci ON ci.id = j.city_id
                        WHERE j.status = "published"
                        ORDER BY j.published_at DESC');
    }
}

class ServiceRepo {
    public static function all(): array {
        return DB::all('SELECT s.*, p.name professional_name, p.slug professional_slug, p.title professional_title,
                               ci.name city_name, co.name country_name, co.slug country_slug
                        FROM services s
                        JOIN professionals p ON p.id = s.professional_id
                        LEFT JOIN cities ci ON ci.id = p.city_id
                        LEFT JOIN countries co ON co.id = s.country_id
                        WHERE s.status = "published"
                        ORDER BY s.published_at DESC');
    }
}
