<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['pdf'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error code: ' . $file['error']);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('Invalid file type, must be PDF');
    }

    // Ensure uploads folder exists and is writable
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        throw new Exception('Cannot create uploads folder');
    }

    $filename = 'upload_' . uniqid() . '.pdf';
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Debug: check file
    if (!file_exists($filepath)) {
        throw new Exception('File not found after upload');
    }
    if (filesize($filepath) === 0) {
        throw new Exception('Uploaded file is empty');
    }

    // Parse PDF
    require_once __DIR__ . '/vendor/autoload.php';
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($filepath);
    $text = $pdf->getText();

    // Cleanup uploaded file
    @unlink($filepath);

    if (empty(trim($text))) {
        throw new Exception('No text found in PDF. Make sure it contains selectable text.');
    }

    $response = [
        'success' => true,
        'text' => trim(preg_replace('/\s+/', ' ', $text)),
        'length' => strlen($text)
    ];

} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
