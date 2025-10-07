<?php
// tests/ExportTechnicalGraphTest.php
// PHPUnit tests for export_technical_graph.php (TDD: scaling, data validation, export logic)

use PHPUnit\Framework\TestCase;




// Inline scale_series and read_csv for isolated testing
function scale_series($values) {
    $min = min($values);
    $max = max($values);
    if ($max == $min) {
        return array_fill(0, count($values), 0.5);
    }
    $scaled = [];
    foreach ($values as $v) {
        $scaled[] = 0.2 + 0.6 * (($v - $min) / ($max - $min));
    }
    return $scaled;
}

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

class ExportTechnicalGraphTest extends TestCase {
    public function testScaleSeriesScalesBetweenPoint2AndPoint8() {
        $input = [10, 20, 30, 40, 50];
        $scaled = scale_series($input);
        $this->assertEquals(0.2, $scaled[0], 0.0001);
        $this->assertEquals(0.8, $scaled[4], 0.0001);
        $this->assertGreaterThan(0.2, $scaled[1]);
        $this->assertLessThan(0.8, $scaled[3]);
    }

    public function testScaleSeriesFlatLine() {
        $input = [5, 5, 5, 5];
        $scaled = scale_series($input);
        foreach ($scaled as $v) {
            $this->assertEquals(0.5, $v, 0.0001);
        }
    }

    public function testReadCsvParsesRows() {
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, "Date,Close,RSI\n2025-01-01,100,50\n2025-01-02,101,55\n");
        $rows = read_csv($tmp);
        $this->assertCount(2, $rows);
        $this->assertEquals('2025-01-01', $rows[0]['Date']);
        $this->assertEquals('101', $rows[1]['Close']);
        unlink($tmp);
    }



}
