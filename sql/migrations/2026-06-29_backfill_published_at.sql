-- 2026-06-29 — Backfill publication date for already-published articles.
--
-- Some articles were saved with status='published' but no published_at (the admin
-- form previously allowed it), showing "Publicado: —" on the public site and
-- breaking chronological ordering / SEO. Going forward the admin handler stamps
-- the current time on publish; this fixes the existing rows by falling back to
-- their creation date.

UPDATE articles
   SET published_at = created_at
 WHERE status = 'published'
   AND published_at IS NULL;
