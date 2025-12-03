<?php

// Simulate a save request
$_SERVER['REQUEST_METHOD'] = 'POST';

$testData = [
    'action' => 'save_config',
    'strategy' => 'Warren Buffett Value Strategy',
    'parameters' => [
        'min_roe_percent' => 20.0,
        'margin_of_safety_percent' => 30.0
    ]
];

// Create a temporary stream for php://input
$tempFile = tmpfile();
fwrite($tempFile, json_encode($testData));
rewind($tempFile);

// Mock php://input
stream_wrapper_unregister("php");
stream_wrapper_register("php", "VariableStream");

class VariableStream {
    private $position;
    private $data;
    
    function stream_open($path, $mode, $options, &$opened_path) {
        global $tempFile;
        $this->data = stream_get_contents($tempFile);
        $this->position = 0;
        return true;
    }
    
    function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    function stream_eof() {
        return $this->position >= strlen($this->data);
    }
    
    function stream_stat() {
        return [];
    }
}

// Capture output
ob_start();

try {
    require __DIR__ . '/web_ui/api/strategy-config.php';
    $output = ob_get_clean();
    echo "Response:\n";
    echo $output . "\n\n";
    
    $decoded = json_decode($output, true);
    if ($decoded) {
        echo "Success: " . ($decoded['success'] ? 'YES' : 'NO') . "\n";
        if (isset($decoded['error'])) {
            echo "Error: " . $decoded['error'] . "\n";
        }
        if (isset($decoded['message'])) {
            echo "Message: " . $decoded['message'] . "\n";
        }
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
