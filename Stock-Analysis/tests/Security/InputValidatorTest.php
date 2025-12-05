<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\InputValidator;

/**
 * Test suite for InputValidator
 * 
 * @covers \App\Security\InputValidator
 */
class InputValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function itValidatesRequiredField(): void
    {
        $validator = new InputValidator(['name' => 'John']);
        $value = $validator->required('name')->string()->getValue();
        
        $this->assertEquals('John', $value);
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsMissingRequiredField(): void
    {
        $validator = new InputValidator([]);
        $validator->required('name')->string();
        
        $this->assertTrue($validator->hasErrors());
        $this->assertStringContainsString('required', $validator->getFirstError());
    }

    /**
     * @test
     */
    public function itValidatesInteger(): void
    {
        $validator = new InputValidator(['age' => '25']);
        $value = $validator->required('age')->int()->getValue();
        
        $this->assertSame(25, $value);
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsInvalidInteger(): void
    {
        $validator = new InputValidator(['age' => 'not_a_number']);
        $validator->required('age')->int();
        
        $this->assertTrue($validator->hasErrors());
        $this->assertStringContainsString('integer', $validator->getFirstError());
    }

    /**
     * @test
     */
    public function itValidatesFloat(): void
    {
        $validator = new InputValidator(['price' => '19.99']);
        $value = $validator->required('price')->float()->getValue();
        
        $this->assertSame(19.99, $value);
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itValidatesEmail(): void
    {
        $validator = new InputValidator(['email' => 'test@example.com']);
        $validator->required('email')->email();
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsInvalidEmail(): void
    {
        $validator = new InputValidator(['email' => 'not_an_email']);
        $validator->required('email')->email();
        
        $this->assertTrue($validator->hasErrors());
        $this->assertStringContainsString('email', $validator->getFirstError());
    }

    /**
     * @test
     */
    public function itValidatesUrl(): void
    {
        $validator = new InputValidator(['website' => 'https://example.com']);
        $validator->required('website')->url();
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsInvalidUrl(): void
    {
        $validator = new InputValidator(['website' => 'not_a_url']);
        $validator->required('website')->url();
        
        $this->assertTrue($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itValidatesBoolean(): void
    {
        $validator = new InputValidator(['active' => 'true']);
        $value = $validator->required('active')->bool()->getValue();
        
        $this->assertTrue($value);
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itValidatesMinValue(): void
    {
        $validator = new InputValidator(['age' => '25']);
        $validator->required('age')->int()->min(18);
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsValueBelowMinimum(): void
    {
        $validator = new InputValidator(['age' => '15']);
        $validator->required('age')->int()->min(18);
        
        $this->assertTrue($validator->hasErrors());
        $this->assertStringContainsString('at least', $validator->getFirstError());
    }

    /**
     * @test
     */
    public function itValidatesMaxValue(): void
    {
        $validator = new InputValidator(['age' => '25']);
        $validator->required('age')->int()->max(100);
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsValueAboveMaximum(): void
    {
        $validator = new InputValidator(['age' => '150']);
        $validator->required('age')->int()->max(100);
        
        $this->assertTrue($validator->hasErrors());
        $this->assertStringContainsString('at most', $validator->getFirstError());
    }

    /**
     * @test
     */
    public function itValidatesMinLength(): void
    {
        $validator = new InputValidator(['password' => 'secure123']);
        $validator->required('password')->string()->minLength(8);
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsStringBelowMinLength(): void
    {
        $validator = new InputValidator(['password' => 'short']);
        $validator->required('password')->string()->minLength(8);
        
        $this->assertTrue($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itValidatesMaxLength(): void
    {
        $validator = new InputValidator(['username' => 'john']);
        $validator->required('username')->string()->maxLength(20);
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsStringAboveMaxLength(): void
    {
        $validator = new InputValidator(['username' => str_repeat('a', 50)]);
        $validator->required('username')->string()->maxLength(20);
        
        $this->assertTrue($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itValidatesPattern(): void
    {
        $validator = new InputValidator(['phone' => '555-1234']);
        $validator->required('phone')->pattern('/^\d{3}-\d{4}$/');
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsInvalidPattern(): void
    {
        $validator = new InputValidator(['phone' => 'invalid']);
        $validator->required('phone')->pattern('/^\d{3}-\d{4}$/');
        
        $this->assertTrue($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itValidatesInArray(): void
    {
        $validator = new InputValidator(['status' => 'active']);
        $validator->required('status')->in(['active', 'inactive', 'pending']);
        
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itRejectsValueNotInArray(): void
    {
        $validator = new InputValidator(['status' => 'invalid']);
        $validator->required('status')->in(['active', 'inactive', 'pending']);
        
        $this->assertTrue($validator->hasErrors());
        $this->assertStringContainsString('must be one of', $validator->getFirstError());
    }

    /**
     * @test
     */
    public function itSanitizesHtml(): void
    {
        $validator = new InputValidator(['comment' => '<script>alert("xss")</script>Hello']);
        $value = $validator->required('comment')->sanitizeHtml()->getValue();
        
        $this->assertEquals('alert("xss")Hello', $value);
        $this->assertStringNotContainsString('<script>', $value);
        $this->assertStringNotContainsString('</script>', $value);
    }

    /**
     * @test
     */
    public function itAllowsBasicHtmlTags(): void
    {
        $validator = new InputValidator(['text' => '<b>Bold</b> and <script>bad</script>']);
        $value = $validator->required('text')->sanitizeHtml(true)->getValue();
        
        $this->assertStringContainsString('<b>Bold</b>', $value);
        $this->assertStringNotContainsString('<script>', $value);
    }

    /**
     * @test
     */
    public function itHandlesOptionalFields(): void
    {
        $validator = new InputValidator([]);
        $value = $validator->optional('middle_name', 'N/A')->getValue();
        
        $this->assertEquals('N/A', $value);
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itChainsMultipleValidations(): void
    {
        $validator = new InputValidator(['age' => '25']);
        $value = $validator->required('age')->int()->min(18)->max(100)->getValue();
        
        $this->assertEquals(25, $value);
        $this->assertFalse($validator->hasErrors());
    }

    /**
     * @test
     */
    public function itThrowsExceptionOnValidate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $validator = new InputValidator([]);
        $validator->required('name')->string();
        $validator->validate();
    }

    /**
     * @test
     */
    public function itReturnsAllErrors(): void
    {
        $validator = new InputValidator(['age' => 'invalid', 'email' => 'bad']);
        $validator->required('age')->int();
        $validator->required('email')->email();
        
        $errors = $validator->getErrors();
        
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('email', $errors);
    }
}
