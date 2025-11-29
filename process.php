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

    // Rewrite PDF via Ghostscript to ensure parser can read it
    $fixedPdf = $uploadDir . 'fixed_' . uniqid() . '.pdf';
    $cmd = "gs -sDEVICE=pdfwrite -dNOPAUSE -dBATCH -dSAFER -sOutputFile=" . escapeshellarg($fixedPdf) . " " . escapeshellarg($filepath);
    exec($cmd, $out, $ret);
    if ($ret !== 0) {
        log_debug("Ghostscript rewrite failed, using original PDF");
        $fixedPdf = $filepath;
    }

    // Parse PDF
    require_once __DIR__ . '/vendor/autoload.php';
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($fixedPdf);
    $text = $pdf->getText();

    log_debug("Parsed text length: " . strlen($text));
    log_debug("Parsed text (first 500 chars): " . substr($text, 0, 500));

    // Cleanup uploaded files
    @unlink($filepath);
    if ($fixedPdf !== $filepath && file_exists($fixedPdf)) @unlink($fixedPdf);

    if (empty(trim($text))) {
        throw new Exception('No text found in PDF. Make sure it contains selectable text.');
    }

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
