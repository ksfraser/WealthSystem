<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PythonResponseParser;

/**
 * Test PythonResponseParser
 * 
 * Tests JSON response parsing, error extraction, and output filtering.
 */
class PythonResponseParserTest extends TestCase
{
    private PythonResponseParser $parser;
    
    protected function setUp(): void
    {
        $this->parser = new PythonResponseParser();
    }
    
    /**
     * Test parse valid JSON response
     */
    public function testParseValidJsonResponse(): void
    {
        $json = '{"data": [1, 2, 3], "status": "success"}';
        
        $result = $this->parser->parse($json);
        
        $this->assertTrue($result['success']);
        $this->assertEquals([1, 2, 3], $result['data']);
        $this->assertEquals('success', $result['status']);
    }
    
    /**
     * Test parse invalid JSON returns error
     */
    public function testParseInvalidJsonReturnsError(): void
    {
        $invalid = '{invalid json}';
        
        $result = $this->parser->parse($invalid);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid JSON', $result['error']);
    }
    
    /**
     * Test parse empty string returns error
     */
    public function testParseEmptyStringReturnsError(): void
    {
        $result = $this->parser->parse('');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('empty', strtolower($result['error']));
    }
    
    /**
     * Test parse response with Python error
     */
    public function testParseResponseWithPythonError(): void
    {
        $json = '{"error": "Module not found"}';
        
        $result = $this->parser->parse($json);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Module not found', $result['error']);
    }
    
    /**
     * Test filter output removes warnings
     */
    public function testFilterOutputRemovesWarnings(): void
    {
        $output = [
            'Warning: Deprecated function',
            '{"data": "value"}',
            'Another warning line'
        ];
        
        $filtered = $this->parser->filterOutput($output);
        
        $this->assertCount(1, $filtered);
        $this->assertEquals('{"data": "value"}', $filtered[0]);
    }
    
    /**
     * Test filter output finds JSON line
     */
    public function testFilterOutputFindsJsonLine(): void
    {
        $output = [
            'Loading module...',
            'Processing data...',
            '{"result": "success", "count": 42}',
            'Done.'
        ];
        
        $filtered = $this->parser->filterOutput($output);
        
        $this->assertCount(1, $filtered);
        $this->assertStringStartsWith('{', $filtered[0]);
    }
    
    /**
     * Test filter output handles no JSON
     */
    public function testFilterOutputHandlesNoJson(): void
    {
        $output = [
            'No JSON here',
            'Just plain text',
            'Nothing to see'
        ];
        
        $filtered = $this->parser->filterOutput($output);
        
        $this->assertEmpty($filtered);
    }
    
    /**
     * Test extract JSON from output
     */
    public function testExtractJsonFromOutput(): void
    {
        $output = "Warning: something\n{\"data\": 123}\nDone";
        
        $json = $this->parser->extractJson($output);
        
        $this->assertEquals('{"data": 123}', $json);
    }
    
    /**
     * Test extract JSON with no JSON returns null
     */
    public function testExtractJsonWithNoJsonReturnsNull(): void
    {
        $output = 'No JSON in this output';
        
        $json = $this->parser->extractJson($output);
        
        $this->assertNull($json);
    }
    
    /**
     * Test parse result from process output
     */
    public function testParseResultFromProcessOutput(): void
    {
        $processResult = [
            'success' => true,
            'output' => '{"data": [1, 2, 3]}',
            'error' => '',
            'exit_code' => 0
        ];
        
        $result = $this->parser->parseProcessResult($processResult);
        
        $this->assertTrue($result['success']);
        $this->assertEquals([1, 2, 3], $result['data']);
    }
    
    /**
     * Test parse result with process error
     */
    public function testParseResultWithProcessError(): void
    {
        $processResult = [
            'success' => false,
            'output' => '',
            'error' => 'Script not found',
            'exit_code' => 1
        ];
        
        $result = $this->parser->parseProcessResult($processResult);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Script not found', $result['error']);
    }
    
    /**
     * Test parse result filters warnings from output
     */
    public function testParseResultFiltersWarnings(): void
    {
        $processResult = [
            'success' => true,
            'output' => "DeprecationWarning: old API\n{\"result\": \"ok\"}",
            'error' => '',
            'exit_code' => 0
        ];
        
        $result = $this->parser->parseProcessResult($processResult);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('ok', $result['result']);
    }
    
    /**
     * Test is JSON string
     */
    public function testIsJsonString(): void
    {
        $this->assertTrue($this->parser->isJson('{"key": "value"}'));
        $this->assertTrue($this->parser->isJson('[1, 2, 3]'));
        $this->assertFalse($this->parser->isJson('not json'));
        $this->assertFalse($this->parser->isJson(''));
    }
    
    /**
     * Test parse handles nested JSON
     */
    public function testParseHandlesNestedJson(): void
    {
        $json = '{"data": {"nested": {"deep": "value"}}, "count": 1}';
        
        $result = $this->parser->parse($json);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('value', $result['data']['nested']['deep']);
        $this->assertEquals(1, $result['count']);
    }
    
    /**
     * Test parse handles arrays
     */
    public function testParseHandlesArrays(): void
    {
        $json = '[{"id": 1}, {"id": 2}, {"id": 3}]';
        
        $result = $this->parser->parse($json);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result['data']);
    }
    
    /**
     * Test parse result structure is consistent
     */
    public function testParseResultStructureIsConsistent(): void
    {
        $result1 = $this->parser->parse('{"test": 1}');
        $result2 = $this->parser->parse('invalid');
        
        $this->assertArrayHasKey('success', $result1);
        $this->assertArrayHasKey('success', $result2);
        
        $this->assertIsBool($result1['success']);
        $this->assertIsBool($result2['success']);
    }
    
    /**
     * Test extract JSON handles multiple JSON objects
     */
    public function testExtractJsonHandlesMultipleJsonObjects(): void
    {
        $output = '{"first": 1} some text {"second": 2}';
        
        $json = $this->parser->extractJson($output);
        
        // Should extract first JSON object
        $this->assertStringStartsWith('{', $json);
        $this->assertStringEndsWith('}', $json);
    }
}
