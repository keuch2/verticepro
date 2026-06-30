-- 2026-06-29 — Add missing `size` column to companies.
--
-- The admin form (admin/empresas/edit.php) and the public profile
-- (empresa.php) both reference a company "size" (employee range, e.g. "11-50"),
-- but the column was never added to the schema. As a result every INSERT/UPDATE
-- of a company from the admin panel threw "Unknown column 'size'" (HTTP 500),
-- making the Empresas module impossible to create or edit.

ALTER TABLE companies
    ADD COLUMN size VARCHAR(20) NULL AFTER founded_year;
