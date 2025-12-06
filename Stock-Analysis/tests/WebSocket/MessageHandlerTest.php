<?php

declare(strict_types=1);

namespace Tests\WebSocket;

use PHPUnit\Framework\TestCase;
use App\WebSocket\MessageHandler;

class MessageHandlerTest extends TestCase
{
    private MessageHandler $handler;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new MessageHandler();
    }
    
    public function testHandlerCreation(): void
    {
        $this->assertInstanceOf(MessageHandler::class, $this->handler);
    }
    
    public function testRegisterHandler(): void
    {
        $this->handler->register('price_update', function($msg) {});
        
        $this->assertTrue($this->handler->hasHandler('price_update'));
    }
    
    public function testHandleMessage(): void
    {
        $handled = false;
        
        $this->handler->register('test', function($msg) use (&$handled) {
            $handled = true;
        });
        
        $this->handler->handle(['type' => 'test', 'data' => []]);
        
        $this->assertTrue($handled);
    }
    
    public function testHandleUnknownMessageWithDefault(): void
    {
        $defaultHandled = false;
        
        $this->handler->register('default', function($msg) use (&$defaultHandled) {
            $defaultHandled = true;
        });
        
        $this->handler->handle(['type' => 'unknown']);
        
        $this->assertTrue($defaultHandled);
    }
    
    public function testGetRegisteredTypes(): void
    {
        $this->handler->register('type1', function($msg) {});
        $this->handler->register('type2', function($msg) {});
        $this->handler->register('type3', function($msg) {});
        
        $types = $this->handler->getRegisteredTypes();
        
        $this->assertCount(3, $types);
        $this->assertContains('type1', $types);
        $this->assertContains('type2', $types);
        $this->assertContains('type3', $types);
    }
    
    public function testUnregister(): void
    {
        $this->handler->register('temp', function($msg) {});
        $this->assertTrue($this->handler->hasHandler('temp'));
        
        $this->handler->unregister('temp');
        $this->assertFalse($this->handler->hasHandler('temp'));
    }
    
    public function testMessageData(): void
    {
        $receivedMessage = null;
        
        $this->handler->register('data', function($msg) use (&$receivedMessage) {
            $receivedMessage = $msg;
        });
        
        $message = ['type' => 'data', 'payload' => ['value' => 123]];
        $this->handler->handle($message);
        
        $this->assertSame($message, $receivedMessage);
    }
}
