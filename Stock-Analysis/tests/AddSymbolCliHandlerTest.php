<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/AddSymbolCliHandler.php';
require_once __DIR__ . '/../src/AddSymbolAction.php';

/**
 * @covers AddSymbolCliHandler
 */
class AddSymbolCliHandlerTest extends TestCase
{
    private $mockAddSymbolAction;
    private $handler;

    protected function setUp(): void
    {
        $this->mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $this->handler = new AddSymbolCliHandler($this->mockAddSymbolAction);
    }

    public function testRunWithValidSymbol()
    {
        $this->mockAddSymbolAction
            ->expects($this->once())
            ->method('execute')
            ->with('IBM', $this->isType('array'))
            ->willReturn(['status' => 'created', 'tables_created' => ['prices', 'indicators']]);

        $result = $this->handler->run(['script.php', 'IBM']);
        $this->assertEquals(0, $result);
    }

    public function testRunWithExistingSymbol()
    {
        $this->mockAddSymbolAction
            ->expects($this->once())
            ->method('execute')
            ->with('AAPL', $this->isType('array'))
            ->willReturn(['status' => 'exists', 'tables_created' => []]);

        $result = $this->handler->run(['script.php', 'AAPL']);
        $this->assertEquals(0, $result);
    }

    public function testRunWithMissingSymbol()
    {
        $this->mockAddSymbolAction
            ->expects($this->never())
            ->method('execute');

        $result = $this->handler->run(['script.php']);
        $this->assertEquals(1, $result);
    }

    public function testRunWithOptions()
    {
        $expectedOptions = [
            'company_name' => 'Apple Inc.',
            'sector' => 'Technology',
            'industry' => 'Computers',
            'market_cap' => 'large',
            'active' => true
        ];

        $this->mockAddSymbolAction
            ->expects($this->once())
            ->method('execute')
            ->with('AAPL', $expectedOptions)
            ->willReturn(['status' => 'created', 'tables_created' => []]);

        $argv = [
            'script.php', 
            'AAPL', 
            '--company=Apple Inc.', 
            '--sector=Technology',
            '--industry=Computers',
            '--market-cap=large'
        ];

        $result = $this->handler->run($argv);
        $this->assertEquals(0, $result);
    }

    public function testRunWithInactiveFlag()
    {
        $this->mockAddSymbolAction
            ->expects($this->once())
            ->method('execute')
            ->with('IBM', $this->callback(function($options) {
                return $options['active'] === false;
            }))
            ->willReturn(['status' => 'created', 'tables_created' => []]);

        $result = $this->handler->run(['script.php', 'IBM', '--inactive']);
        $this->assertEquals(0, $result);
    }

    public function testRunWithActionException()
    {
        $this->mockAddSymbolAction
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception('Database error'));

        $result = $this->handler->run(['script.php', 'IBM']);
        $this->assertEquals(1, $result);
    }

    public function testSymbolCaseNormalization()
    {
        $this->mockAddSymbolAction
            ->expects($this->once())
            ->method('execute')
            ->with('IBM', $this->isType('array'))
            ->willReturn(['status' => 'created', 'tables_created' => []]);

        $result = $this->handler->run(['script.php', 'ibm']);
        $this->assertEquals(0, $result);
    }
}
