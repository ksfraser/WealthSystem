<?php

declare(strict_types=1);

namespace App\Queue\Jobs;

/**
 * Job to send trade notifications
 */
class SendNotificationJob extends AbstractJob
{
    public function getType(): string
    {
        return 'send_notification';
    }
    
    public function handle(): void
    {
        $message = $this->payload['message'] ?? null;
        $recipient = $this->payload['recipient'] ?? null;
        
        if ($message === null || $recipient === null) {
            throw new \InvalidArgumentException('Message and recipient are required');
        }
        
        // Simulated notification sending
        // In real implementation would send email/SMS/push notification
    }
    
    public function getMessage(): string
    {
        return $this->payload['message'] ?? '';
    }
    
    public function getRecipient(): string
    {
        return $this->payload['recipient'] ?? '';
    }
}
