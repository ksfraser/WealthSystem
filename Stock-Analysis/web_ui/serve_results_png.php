<?php
// serve_results_png.php
// Serves Results.png from the parent directory with correct headers
$file = __DIR__ . '/../Results.png';
if (!file_exists($file)) {
    http_response_code(404);
    echo 'Results.png not found.';
    exit;
}
header('Content-Type: image/png');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache, must-revalidate');
readfile($file);
exit;
