<?php

declare(strict_types=1);

namespace Tests\Queue\Jobs;

use PHPUnit\Framework\TestCase;
use App\Queue\Jobs\FetchPriceJob;
use App\Queue\Jobs\SendNotificationJob;

class JobsTest extends TestCase
{
    public function testFetchPriceJobCreation(): void
    {
        $job = new FetchPriceJob(['symbol' => 'BTC']);
        
        $this->assertSame('fetch_price', $job->getType());
        $this->assertSame('BTC', $job->getSymbol());
        $this->assertSame(3, $job->getMaxAttempts());
    }
    
    public function testFetchPriceJobHandle(): void
    {
        $job = new FetchPriceJob(['symbol' => 'ETH']);
        
        // Should not throw exception
        $job->handle();
        
        $this->assertTrue(true);
    }
    
    public function testFetchPriceJobMissingSymbol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $job = new FetchPriceJob([]);
        $job->handle();
    }
    
    public function testSendNotificationJobCreation(): void
    {
        $job = new SendNotificationJob([
            'message' => 'Trade executed',
            'recipient' => 'user@example.com'
        ]);
        
        $this->assertSame('send_notification', $job->getType());
        $this->assertSame('Trade executed', $job->getMessage());
        $this->assertSame('user@example.com', $job->getRecipient());
    }
    
    public function testSendNotificationJobHandle(): void
    {
        $job = new SendNotificationJob([
            'message' => 'Alert!',
            'recipient' => 'admin@example.com'
        ]);
        
        // Should not throw exception
        $job->handle();
        
        $this->assertTrue(true);
    }
    
    public function testSendNotificationJobMissingData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $job = new SendNotificationJob(['message' => 'Test']);
        $job->handle();
    }
    
    public function testJobIdUniqueness(): void
    {
        $job1 = new FetchPriceJob(['symbol' => 'BTC']);
        $job2 = new FetchPriceJob(['symbol' => 'BTC']);
        
        $this->assertNotSame($job1->getId(), $job2->getId());
    }
    
    public function testJobPayload(): void
    {
        $payload = ['symbol' => 'ADA', 'source' => 'binance'];
        $job = new FetchPriceJob($payload);
        
        $this->assertSame($payload, $job->getPayload());
    }
    
    public function testCustomMaxAttempts(): void
    {
        $job = new FetchPriceJob(['symbol' => 'SOL'], 5);
        
        $this->assertSame(5, $job->getMaxAttempts());
    }
}
