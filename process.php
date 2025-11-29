<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

function log_debug($msg) {
    echo "[".date('Y-m-d H:i:s')."] DEBUG: ".$msg."\n";
}

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

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filepath = $uploadDir . 'upload_' . uniqid() . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }

    log_debug("Uploaded file: $filepath");

    // Extract text using pdftotext
    $txtFile = $uploadDir . 'tmp_' . uniqid() . '.txt';
    $cmd = "pdftotext " . escapeshellarg($filepath) . " " . escapeshellarg($txtFile);
    exec($cmd, $out, $ret);

    if ($ret !== 0 || !file_exists($txtFile)) {
        throw new Exception("Failed to extract text from PDF");
    }

    $text = file_get_contents($txtFile);
    @unlink($txtFile);
    @unlink($filepath);

    if (empty(trim($text))) {
        throw new Exception("No text found in PDF. Make sure it contains selectable text.");
    }

    log_debug("Parsed text length: " . strlen($text));

    echo json_encode([
        'success' => true,
        'text' => trim(preg_replace('/\s+/', ' ', $text)),
        'length' => strlen($text)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    log_debug("Exception: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
