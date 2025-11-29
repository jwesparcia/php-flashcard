<?php
// process.php - Works on InfinityFree even with PHP 5.3/5.4/5.6
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    // FULL PATH instead of "use" - works on old PHP
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception('Missing vendor folder. Run composer locally and upload it.');
    }
    require_once $autoloadPath;

    // No more "use" statements - use full class names instead
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['pdf'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        throw new Exception('Invalid PDF file');
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception('Cannot create uploads folder');
    }

    $filename = 'upload_' . uniqid() . '.pdf';
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }

    // === Try to repair PDF (pdftk) - safe if it fails ===
    $repairedPdf = null;
    try {
        $pdftk = new \mikehaertl\pdftk\Pdf($filepath);  // full name
        $repairedPdf = $uploadDir . 'repaired_' . uniqid() . '.pdf';
        if ($pdftk->saveAs($repairedPdf) !== false) {
            $finalPdf = $repairedPdf;
        } else {
            $finalPdf = $filepath; // fallback to original
        }
    } catch (Exception $e) {
        $finalPdf = $filepath; // pdftk not available or failed - totally fine
    }

    // === Extract text with Smalot PDFParser (pure PHP) ===
    $text = '';
    try {
        $parser = new \Smalot\PdfParser\Parser();  // full name - works on old PHP
        $pdf    = $parser->parseFile($finalPdf);
        $text   = $pdf->getText();
    } catch (Exception $e) {
        // ignore - text stays empty
    }

    // === Optional Poppler fallback (only works on your local Windows) ===
    if (empty(trim($text))) {
        $poppler = 'C:\\Users\\jhune\\Downloads\\Release-25.11.0-0\\poppler-25.11.0\\Library\\bin\\pdftotext.exe';
        if (file_exists($poppler)) {
            $txtFile = $uploadDir . 'temp_' . uniqid() . '.txt';
            $cmd = "\"$poppler\" -layout \"$finalPdf\" \"$txtFile\"";
            exec($cmd, $out, $ret);
            if ($ret == 0 && file_exists($txtFile) && filesize($txtFile) > 0) {
                $text = file_get_contents($txtFile);
                @unlink($txtFile);
            }
        }
    }

    // Cleanup
    @unlink($filepath);
    if ($repairedPdf && file_exists($repairedPdf)) @unlink($repairedPdf);

    if (empty(trim($text))) {
        throw new Exception('No text found in PDF. If it is a scanned/image PDF, convert it to selectable text first (Print → Save as PDF).');
    }

    $text = trim(preg_replace('/\s+/', ' ', $text));

    $response = [
        'text'    => $text,
        'length'  => strlen($text),
        'success' => true
    ];

} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;