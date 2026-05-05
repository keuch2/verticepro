<?php
require_once __DIR__ . '/../_helpers.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file']);
    exit;
}

$rel = upload_image($_FILES['file'], 'articles/inline', 'inline-' . date('Ymd'));
if (!$rel) {
    http_response_code(415);
    echo json_encode(['error' => 'Upload rejected (invalid type or size)']);
    exit;
}

echo json_encode(['location' => img_url($rel)]);
