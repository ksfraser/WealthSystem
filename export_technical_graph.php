<?php
// export_technical_graph.php
// Server-side PNG/SVG export for technical analysis graphs with multi-indicator scaling
// Usage: php export_technical_graph.php --input data.csv --indicators rsi,macd,sma --output chart.png --format png


require 'vendor/autoload.php'; // For composer packages if needed
require_once __DIR__ . '/src/CLI/GraphExportCLIOpts.php';

// Use the GD library for PNG, or Imagick for SVG/PNG if available
// This script expects a CSV with columns: Date, Close, [indicator1], [indicator2], ...

// parse_args() replaced by GraphExportCLIOpts

function read_csv($file) {
    $rows = [];
    if (($h = fopen($file, 'r')) !== false) {
        $header = fgetcsv($h);
        while (($row = fgetcsv($h)) !== false) {
            $rows[] = array_combine($header, $row);
        }
        fclose($h);
    }
    return $rows;
}

function scale_series($values) {
    $min = min($values);
    $max = max($values);
    if ($max == $min) {
        return array_fill(0, count($values), 0.5); // Flat line if no variation
    }
    $scaled = [];
    foreach ($values as $v) {
        $scaled[] = 0.2 + 0.6 * (($v - $min) / ($max - $min));
    }
    return $scaled;
}

function plot_graph($data, $indicators, $output, $format = 'png') {
    $width = 1000;
    $height = 600;
    $margin = 80;
    $img = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($img, 255,255,255);
    $black = imagecolorallocate($img, 0,0,0);
    $colors = [
        imagecolorallocate($img, 52, 152, 219), // blue
        imagecolorallocate($img, 231, 76, 60), // red
        imagecolorallocate($img, 46, 204, 113), // green
        imagecolorallocate($img, 155, 89, 182), // purple
        imagecolorallocate($img, 241, 196, 15), // yellow
        imagecolorallocate($img, 230, 126, 34), // orange
    ];
    imagefilledrectangle($img, 0, 0, $width, $height, $white);

    // Axes
    imageline($img, $margin, $margin, $margin, $height-$margin, $black);
    imageline($img, $margin, $height-$margin, $width-$margin, $height-$margin, $black);

    // Dates
    $dates = array_column($data, 'Date');
    $n = count($dates);
    if ($n < 2) {
        fwrite(STDERR, "Not enough data to plot.\n");
        exit(1);
    }
    // X scale
    $xstep = ($width - 2*$margin) / ($n-1);
    $xvals = [];
    for ($i=0; $i<$n; $i++) {
        $xvals[] = $margin + $i*$xstep;
    }

    // Plot each indicator
    foreach ($indicators as $idx => $ind) {
        $vals = array_map(function($row) use ($ind) { return isset($row[$ind]) ? floatval($row[$ind]) : null; }, $data);
        if (in_array(null, $vals, true)) continue; // skip if missing data
        $scaled = scale_series($vals);
        $color = $colors[$idx % count($colors)];
        // Draw line
        for ($i=1; $i<$n; $i++) {
            $y1 = $margin + (1.0 - $scaled[$i-1]) * ($height - 2*$margin);
            $y2 = $margin + (1.0 - $scaled[$i]) * ($height - 2*$margin);
            imageline($img, $xvals[$i-1], $y1, $xvals[$i], $y2, $color);
        }
        // Label
        imagestring($img, 5, $width-$margin+10, $margin+20*$idx, $ind, $color);
    }
    // Optionally plot Close price as black line
    if (isset($data[0]['Close'])) {
        $vals = array_map(function($row) { return floatval($row['Close']); }, $data);
        $scaled = scale_series($vals);
        for ($i=1; $i<$n; $i++) {
            $y1 = $margin + (1.0 - $scaled[$i-1]) * ($height - 2*$margin);
            $y2 = $margin + (1.0 - $scaled[$i]) * ($height - 2*$margin);
            imageline($img, $xvals[$i-1], $y1, $xvals[$i], $y2, $black);
        }
        imagestring($img, 5, $width-$margin+10, $margin+20*count($indicators), 'Close', $black);
    }
    // Title
    imagestring($img, 5, $width/2-100, 20, 'Technical Analysis Indicators (Scaled)', $black);
    // Save
    if ($format === 'png') {
        imagepng($img, $output);
    } elseif ($format === 'svg' && function_exists('imagegd2')) {
        // PHP-GD does not natively support SVG, but Imagick can
        // For now, fallback to PNG if SVG not supported
        imagepng($img, $output);
    } else {
        imagepng($img, $output);
    }
    imagedestroy($img);
}

echo "Graph exported to {$opts['output']}\n";
// MAIN
$cliOpts = new Ksfraser\CLI\GraphExportCLIOpts();
$input = $cliOpts->get('input');
$indicators = $cliOpts->get('indicators');
$output = $cliOpts->get('output');
$format = $cliOpts->get('format');
$data = read_csv($input);
$indicatorsArr = array_map('trim', explode(',', $indicators));
plot_graph($data, $indicatorsArr, $output, $format);
echo "Graph exported to {$output}\n";
