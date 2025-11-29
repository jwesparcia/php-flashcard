<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log to stdout (Render logs)
function log_debug($msg) {
    echo "[".date('Y-m-d H:i:s')."] DEBUG: ".$msg."\n";
}

$response = [];

try {
    log_debug("Request method: " . $_SERVER['REQUEST_METHOD']);
    log_debug("FILES array: " . print_r($_FILES, true));

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
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        throw new Exception('Cannot create uploads folder');
    }

    $filename = 'upload_' . uniqid() . '.pdf';
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }

    log_debug("Uploaded file saved: $filepath");
    log_debug("File exists: " . (file_exists($filepath) ? 'yes' : 'no'));
    log_debug("File size: " . filesize($filepath));

    // Use pdftotext to extract text
    $txtFile = $uploadDir . 'tmp_' . uniqid() . '.txt';
    $cmd = "pdftotext " . escapeshellarg($filepath) . " " . escapeshellarg($txtFile);
    exec($cmd, $out, $ret);

    if ($ret !== 0 || !file_exists($txtFile)) {
        throw new Exception("Failed to extract text from PDF using pdftotext");
    }

    $text = file_get_contents($txtFile);
    @unlink($txtFile);
    @unlink($filepath); // remove uploaded PDF after processing

    if (empty(trim($text))) {
        throw new Exception("No text found in PDF. Make sure it contains selectable text.");
    }

    log_debug("Parsed text length: " . strlen($text));
    log_debug("Parsed text (first 500 chars): " . substr($text, 0, 500));

    $response = [
        'success' => true,
        'text' => trim(preg_replace('/\s+/', ' ', $text)),
        'length' => strlen($text)
    ];

} catch (Exception $e) {
    log_debug("Exception: " . $e->getMessage());
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
