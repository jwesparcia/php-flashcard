<?php
// process.php – debug version for Render
// process.php – guaranteed debug
error_log('========= process.php START =========');   // must appear
error_log('_FILES = ' . print_r($_FILES,true));

// your existing code follows ...
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new RuntimeException('vendor/autoload.php missing – did Composer run?');
    }
    require_once $autoload;

    /* ---------- 1. upload debug ---------- */
    error_log('=== PDF UPLOAD DEBUG ===');
    error_log('$_FILES = ' . print_r($_FILES, true));

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['pdf']['tmp_name'])) {
        throw new RuntimeException('No PDF uploaded');
    }

    $tmp = $_FILES['pdf']['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Temp file not an uploaded file');
    }
    error_log('Temp file: ' . $tmp . ' size=' . filesize($tmp));

    /* ---------- 2. move to uploads ---------- */
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('Cannot create uploads/');
    }
    $target = $uploadDir . 'upload_' . uniqid() . '.pdf';
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('move_uploaded_file() failed');
    }
    error_log('Saved to: ' . $target);

    /* ---------- 3. parse with Smalot ---------- */
    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile($target);
    $text   = trim($pdf->getText());
    error_log('Extracted text length: ' . strlen($text));

    @unlink($target);               // tidy up

    if ($text === '') {
        throw new RuntimeException('No text found in PDF. If it is a scanned/image PDF, convert it to selectable text first (Print → Save as PDF).');
    }

    $response = [
        'success' => true,
        'text'    => $text,
        'length'  => strlen($text),
    ];

} catch (Throwable $e) {
    error_log('EXCEPTION: ' . $e->getMessage());
    $response = ['error' => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
