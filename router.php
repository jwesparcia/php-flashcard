<?php
// router.php for PHP built-in server routing

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files if they exist
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise fallback to index.php
require_once __DIR__ . '/index.php';
