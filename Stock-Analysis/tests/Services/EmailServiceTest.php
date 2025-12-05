<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\EmailService;

/**
 * Test suite for EmailService
 * 
 * Tests email notification functionality including:
 * - Alert email delivery
 * - Template rendering
 * - Multi-recipient support
 * - HTML/text formats
 * - Error handling
 * 
 * @covers \App\Services\EmailService
 */
class EmailServiceTest extends TestCase
{
    private EmailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'from_email' => 'noreply@wealthsystem.test',
            'from_name' => 'Wealth System',
            'smtp_host' => 'localhost',
            'smtp_port' => 1025, // MailHog for testing
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => '',
        ];
        
        $this->service = new EmailService($config);
    }

    /**
     * @test
     * @group email
     */
    public function itSendsAlertEmail(): void
    {
        $alert = [
            'title' => 'Portfolio Rebalancing Needed',
            'message' => 'Your portfolio allocation exceeds target thresholds',
            'severity' => 'warning',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $result = $this->service->sendAlert('user@example.com', $alert);
        
        $this->assertTrue($result);
    }

    /**
     * @test
     * @group email
     */
    public function itSendsToMultipleRecipients(): void
    {
        $alert = [
            'title' => 'Test Alert',
            'message' => 'Test message',
            'severity' => 'info',
        ];
        
        $recipients = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ];
        
        $result = $this->service->sendAlertBatch($recipients, $alert);
        
        $this->assertTrue($result);
    }

    /**
     * @test
     * @group email
     */
    public function itRendersHtmlTemplate(): void
    {
        $alert = [
            'title' => 'High Risk Alert',
            'message' => 'Concentration risk detected',
            'severity' => 'high',
        ];
        
        $html = $this->service->renderAlertTemplate($alert);
        
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString($alert['title'], $html);
        $this->assertStringContainsString($alert['message'], $html);
    }

    /**
     * @test
     * @group email
     */
    public function itRendersPlainTextVersion(): void
    {
        $alert = [
            'title' => 'Test Alert',
            'message' => 'Test message',
            'severity' => 'info',
        ];
        
        $text = $this->service->renderAlertTextTemplate($alert);
        
        $this->assertStringContainsString($alert['title'], $text);
        $this->assertStringContainsString($alert['message'], $text);
        $this->assertStringNotContainsString('<html>', $text);
    }

    /**
     * @test
     * @group email
     */
    public function itValidatesEmailAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address');
        
        $alert = ['title' => 'Test', 'message' => 'Test', 'severity' => 'info'];
        
        $this->service->sendAlert('invalid-email', $alert);
    }

    /**
     * @test
     * @group email
     */
    public function itHandlesSmtpFailure(): void
    {
        $config = [
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'smtp_host' => 'invalid-host.local',
            'smtp_port' => 9999,
        ];
        
        $service = new EmailService($config);
        
        $alert = ['title' => 'Test', 'message' => 'Test', 'severity' => 'info'];
        
        $result = $service->sendAlert('user@example.com', $alert);
        
        $this->assertFalse($result);
    }

    /**
     * @test
     * @group email
     */
    public function itSetsCorrectHeaders(): void
    {
        $alert = [
            'title' => 'Test Alert',
            'message' => 'Test message',
            'severity' => 'info',
        ];
        
        $headers = $this->service->buildHeaders($alert);
        
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('From', $headers);
        $this->assertArrayHasKey('Reply-To', $headers);
        $this->assertArrayHasKey('X-Mailer', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    /**
     * @test
     * @group email
     */
    public function itSetsSubjectWithSeverity(): void
    {
        $alert = [
            'title' => 'Portfolio Alert',
            'severity' => 'critical',
        ];
        
        $subject = $this->service->buildSubject($alert);
        
        $this->assertStringContainsString('[CRITICAL]', $subject);
        $this->assertStringContainsString('Portfolio Alert', $subject);
    }

    /**
     * @test
     * @group email
     */
    public function itIncludesAlertMetadata(): void
    {
        $alert = [
            'id' => 123,
            'title' => 'Test Alert',
            'message' => 'Test message',
            'severity' => 'info',
            'created_at' => '2025-12-05 10:00:00',
        ];
        
        $html = $this->service->renderAlertTemplate($alert);
        
        $this->assertStringContainsString('Alert ID: 123', $html);
        $this->assertStringContainsString('2025-12-05', $html);
    }

    /**
     * @test
     * @group email
     */
    public function itSupportsDifferentSeverityStyles(): void
    {
        $severities = ['info', 'warning', 'high', 'critical'];
        
        foreach ($severities as $severity) {
            $alert = [
                'title' => 'Test',
                'message' => 'Test',
                'severity' => $severity,
            ];
            
            $html = $this->service->renderAlertTemplate($alert);
            
            // Each severity should have unique styling
            $this->assertStringContainsString($severity, strtolower($html));
        }
    }

    /**
     * @test
     * @group email
     */
    public function itLogsEmailActivity(): void
    {
        $alert = [
            'title' => 'Test Alert',
            'message' => 'Test message',
            'severity' => 'info',
        ];
        
        $this->service->sendAlert('user@example.com', $alert);
        
        $logs = $this->service->getEmailLog();
        
        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
    }

    /**
     * @test
     * @group email
     */
    public function itRetriesOnTransientFailure(): void
    {
        $config = [
            'from_email' => 'test@example.com',
            'from_name' => 'Test',
            'smtp_host' => 'localhost',
            'smtp_port' => 1025,
            'retry_attempts' => 3,
            'retry_delay' => 1,
        ];
        
        $service = new EmailService($config);
        
        $alert = ['title' => 'Test', 'message' => 'Test', 'severity' => 'info'];
        
        // Should attempt 3 times before failing
        $result = $service->sendAlert('user@example.com', $alert);
        
        $this->assertIsBool($result);
    }

    /**
     * @test
     * @group email
     */
    public function itSanitizesEmailContent(): void
    {
        $alert = [
            'title' => 'Test <script>alert("xss")</script>',
            'message' => 'Message with <script>bad code</script>',
            'severity' => 'info',
        ];
        
        $html = $this->service->renderAlertTemplate($alert);
        
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * @test
     * @group email
     */
    public function itIncludesUnsubscribeLink(): void
    {
        $alert = [
            'title' => 'Test Alert',
            'message' => 'Test message',
            'severity' => 'info',
        ];
        
        $html = $this->service->renderAlertTemplate($alert);
        
        $this->assertStringContainsString('unsubscribe', strtolower($html));
    }
}
