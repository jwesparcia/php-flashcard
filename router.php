<?php
// router.php for PHP built-in server routing

$requested = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve existing files directly
if ($requested !== '/' && file_exists(__DIR__ . $requested)) {
    return false; // serve the file as-is
}

// Fallback to index.php
require_once __DIR__ . '/index.php';
