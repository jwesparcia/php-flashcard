<?php
// process.php - Fixed for php-pdftk API + pdftotext extraction
header('Content-Type: application/json');
require_once 'vendor/autoload.php';

use mikehaertl\pdftk\Pdf;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['pdf'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['pdf'];
if ($file['error'] !== UPLOAD_ERR_OK || $file['type'] !== 'application/pdf') {
    echo json_encode(['error' => 'Invalid PDF file']);
    exit;
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$filename = 'upload_' . uniqid() . '.pdf';
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Step 1: Use php-pdftk to "unlock" / repair the PDF (removes restrictions, works on secured files)
$repairedPdf = $uploadDir . 'repaired_' . uniqid() . '.pdf';
$pdf = new Pdf();  // Start fresh

// Add the file (no password assumed; add $password param if needed: addFile($filepath, null, $password))
$result = $pdf->addFile($filepath)
              ->saveAs($repairedPdf);

if ($result === false) {
    // Fallback: Use original if repair fails (e.g., heavily encrypted)
    $finalPdf = $filepath;
    $error = $pdf->getError();
    if (!empty($error)) {
        // Log error but continue (common for non-encrypted files)
        error_log("PDFTK Repair Warning: " . $error);
    }
} else {
    $finalPdf = $repairedPdf;
}

// Step 2: Extract text using pdftotext (from Poppler)
$textFile = $uploadDir . 'extracted_' . uniqid() . '.txt';
$pdftotextPath = 'C:\\Users\\jhune\\Downloads\\Release-25.11.0-0\\poppler-25.11.0\\Library\\bin\\pdftotext.exe';  // Assumes in PATH (see install notes below)

$cmd = sprintf('"%s" -layout "%s" "%s" 2>&1', $pdftotextPath, $finalPdf, $textFile);
exec($cmd, $output, $returnCode);

$text = '';
if ($returnCode === 0 && file_exists($textFile)) {
    $text = file_get_contents($textFile);
    @unlink($textFile);
} else {
    // Fallback: If pdftotext fails, try extracting metadata as poor-man's text
    $pdfData = new Pdf($finalPdf);
    $data = $pdfData->getData();
    if ($data !== false) {
        $raw = $data->getRaw();  // Gets raw PDF info
        // Extract any text-like strings (basic)
        if (preg_match_all('/\/Title\s*\( (.+?) \)/', $raw, $matches)) {
            $text = implode("\n", $matches[1]);
        }
    }
}

// Clean up files
@unlink($filepath);
if (isset($repairedPdf) && file_exists($repairedPdf)) @unlink($repairedPdf);

if (empty(trim($text))) {
    echo json_encode(['error' => 'Could not extract text. PDF may be scanned (image-based), heavily encrypted, or pdftotext not installed. Check logs.']);
    exit;
}

// Clean and return text
$text = preg_replace('/\s+/', ' ', $text);  // Normalize whitespace
$text = trim($text);

echo json_encode([
    'text' => $text,
    'length' => strlen($text),
    'success' => true
]);
?>