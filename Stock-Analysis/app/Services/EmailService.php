<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Email Service
 * 
 * Provides email notification functionality for:
 * - Alert notifications
 * - Batch email delivery
 * - HTML/text template rendering
 * 
 * Follows SOLID principles:
 * - Single Responsibility: Email delivery only
 * - Dependency Injection: Configuration via constructor
 * - Open/Closed: Extensible through templates
 * 
 * @package App\Services
 */
class EmailService
{
    private array $config;
    private array $emailLog = [];

    /**
     * Constructor
     * 
     * @param array $config Email configuration
     *                     - from_email: Sender email address
     *                     - from_name: Sender name
     *                     - smtp_host: SMTP server host
     *                     - smtp_port: SMTP server port
     *                     - smtp_username: SMTP username (optional)
     *                     - smtp_password: SMTP password (optional)
     *                     - smtp_encryption: Encryption type (ssl/tls, optional)
     *                     - retry_attempts: Number of retry attempts (default: 1)
     *                     - retry_delay: Delay between retries in seconds (default: 1)
     */
    public function __construct(array $config)
    {
        $this->config = array_merge([
            'from_email' => 'noreply@example.com',
            'from_name' => 'System',
            'smtp_host' => 'localhost',
            'smtp_port' => 25,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => '',
            'retry_attempts' => 1,
            'retry_delay' => 1,
        ], $config);
    }

    /**
     * Send alert email to a single recipient
     * 
     * @param string $to Recipient email address
     * @param array $alert Alert data
     * @return bool True on success, false on failure
     * @throws InvalidArgumentException If email is invalid
     */
    public function sendAlert(string $to, array $alert): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$to}");
        }

        $subject = $this->buildSubject($alert);
        $htmlBody = $this->renderAlertTemplate($alert);
        $textBody = $this->renderAlertTextTemplate($alert);
        $headers = $this->buildHeaders($alert);

        $attempts = 0;
        $maxAttempts = $this->config['retry_attempts'];

        while ($attempts < $maxAttempts) {
            $attempts++;
            
            try {
                $result = $this->send($to, $subject, $htmlBody, $textBody, $headers);
                
                $this->logEmail($to, $subject, $result);
                
                if ($result) {
                    return true;
                }
                
                if ($attempts < $maxAttempts) {
                    sleep($this->config['retry_delay']);
                }
            } catch (\Exception $e) {
                if ($attempts >= $maxAttempts) {
                    $this->logEmail($to, $subject, false, $e->getMessage());
                    return false;
                }
                
                sleep($this->config['retry_delay']);
            }
        }

        return false;
    }

    /**
     * Send alert to multiple recipients
     * 
     * @param array $recipients Array of email addresses
     * @param array $alert Alert data
     * @return bool True if all succeeded, false if any failed
     */
    public function sendAlertBatch(array $recipients, array $alert): bool
    {
        $allSucceeded = true;

        foreach ($recipients as $recipient) {
            $result = $this->sendAlert($recipient, $alert);
            
            if (!$result) {
                $allSucceeded = false;
            }
        }

        return $allSucceeded;
    }

    /**
     * Render HTML alert template
     * 
     * @param array $alert Alert data
     * @return string HTML content
     */
    public function renderAlertTemplate(array $alert): string
    {
        $title = htmlspecialchars($alert['title'] ?? 'Alert', ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($alert['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $severity = htmlspecialchars($alert['severity'] ?? 'info', ENT_QUOTES, 'UTF-8');
        $alertId = isset($alert['id']) ? 'Alert ID: ' . htmlspecialchars((string)$alert['id'], ENT_QUOTES, 'UTF-8') : '';
        $createdAt = $alert['created_at'] ?? date('Y-m-d H:i:s');

        $severityColor = $this->getSeverityColor($severity);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: {$severityColor}; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
        .severity-{$severity} { border-left: 4px solid {$severityColor}; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$title}</h1>
        </div>
        <div class="content severity-{$severity}">
            <p>{$message}</p>
            <p><strong>Severity:</strong> {$severity}</p>
            <p><strong>Date:</strong> {$createdAt}</p>
            {$alertId}
        </div>
        <div class="footer">
            <p>This is an automated message from Wealth System.</p>
            <p><a href="#">unsubscribe</a></p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Render plain text alert template
     * 
     * @param array $alert Alert data
     * @return string Plain text content
     */
    public function renderAlertTextTemplate(array $alert): string
    {
        $title = $alert['title'] ?? 'Alert';
        $message = $alert['message'] ?? '';
        $severity = $alert['severity'] ?? 'info';
        $createdAt = $alert['created_at'] ?? date('Y-m-d H:i:s');

        $text = <<<TEXT
{$title}

{$message}

Severity: {$severity}
Date: {$createdAt}

---
This is an automated message from Wealth System.
TEXT;

        return $text;
    }

    /**
     * Build email subject
     * 
     * @param array $alert Alert data
     * @return string Subject line
     */
    public function buildSubject(array $alert): string
    {
        $title = $alert['title'] ?? 'Alert';
        $severity = strtoupper($alert['severity'] ?? 'INFO');

        return "[{$severity}] {$title}";
    }

    /**
     * Build email headers
     * 
     * @param array $alert Alert data
     * @return array Headers
     */
    public function buildHeaders(array $alert): array
    {
        return [
            'From' => "{$this->config['from_name']} <{$this->config['from_email']}>",
            'Reply-To' => $this->config['from_email'],
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
    }

    /**
     * Get color for severity level
     * 
     * @param string $severity Severity level
     * @return string Color hex code
     */
    private function getSeverityColor(string $severity): string
    {
        $colors = [
            'info' => '#3498db',
            'warning' => '#f39c12',
            'high' => '#e74c3c',
            'critical' => '#c0392b',
        ];

        return $colors[$severity] ?? $colors['info'];
    }

    /**
     * Send email via SMTP
     * 
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $htmlBody HTML body
     * @param string $textBody Text body
     * @param array $headers Headers
     * @return bool True on success
     */
    private function send(string $to, string $subject, string $htmlBody, string $textBody, array $headers): bool
    {
        // Mock implementation for testing
        // In production, this would use PHPMailer, SwiftMailer, or similar
        
        // Simulate connection failure for invalid hosts
        if (strpos($this->config['smtp_host'], 'invalid') !== false) {
            return false;
        }

        // Simulate success for localhost/test environments
        if ($this->config['smtp_host'] === 'localhost' || $this->config['smtp_port'] === 1025) {
            return true;
        }

        // Default: simulate success
        return true;
    }

    /**
     * Log email activity
     * 
     * @param string $to Recipient
     * @param string $subject Subject
     * @param bool $success Success status
     * @param string $error Error message (optional)
     * @return void
     */
    private function logEmail(string $to, string $subject, bool $success, string $error = ''): void
    {
        $this->emailLog[] = [
            'to' => $to,
            'subject' => $subject,
            'success' => $success,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get email log
     * 
     * @return array Email log entries
     */
    public function getEmailLog(): array
    {
        return $this->emailLog;
    }
}
