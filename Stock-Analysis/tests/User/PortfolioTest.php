<?php

declare(strict_types=1);

namespace Tests\User;

use PHPUnit\Framework\TestCase;
use App\User\Portfolio;
use App\User\PortfolioManager;
use App\Exceptions\DataException;

class PortfolioTest extends TestCase
{
    public function testPortfolioCreation(): void
    {
        $portfolio = new Portfolio(1, 100, 'Main Portfolio', 50000.0);
        
        $this->assertSame(1, $portfolio->getId());
        $this->assertSame(100, $portfolio->getUserId());
        $this->assertSame('Main Portfolio', $portfolio->getName());
        $this->assertSame(50000.0, $portfolio->getCashBalance());
    }
    
    public function testPortfolioDefaultBalance(): void
    {
        $portfolio = new Portfolio(1, 100, 'Test');
        
        $this->assertSame(10000.0, $portfolio->getCashBalance());
    }
    
    public function testPortfolioAddCash(): void
    {
        $portfolio = new Portfolio(1, 100, 'Test', 1000.0);
        
        $portfolio->addCash(500.0);
        
        $this->assertSame(1500.0, $portfolio->getCashBalance());
    }
    
    public function testPortfolioDeductCash(): void
    {
        $portfolio = new Portfolio(1, 100, 'Test', 1000.0);
        
        $portfolio->deductCash(300.0);
        
        $this->assertSame(700.0, $portfolio->getCashBalance());
    }
    
    public function testPortfolioAddHolding(): void
    {
        $portfolio = new Portfolio(1, 100, 'Test');
        
        $portfolio->addHolding('AAPL', 10.0);
        
        $this->assertTrue($portfolio->hasPosition('AAPL'));
        $this->assertSame(10.0, $portfolio->getPosition('AAPL'));
    }
    
    public function testPortfolioRemoveHolding(): void
    {
        $portfolio = new Portfolio(1, 100, 'Test');
        $portfolio->addHolding('TSLA', 20.0);
        
        $portfolio->removeHolding('TSLA', 5.0);
        
        $this->assertSame(15.0, $portfolio->getPosition('TSLA'));
    }
    
    public function testPortfolioRemoveEntireHolding(): void
    {
        $portfolio = new Portfolio(1, 100, 'Test');
        $portfolio->addHolding('NVDA', 10.0);
        
        $portfolio->removeHolding('NVDA', 10.0);
        
        $this->assertFalse($portfolio->hasPosition('NVDA'));
        $this->assertSame(0.0, $portfolio->getPosition('NVDA'));
    }
    
    public function testPortfolioManagerCreate(): void
    {
        $manager = new PortfolioManager();
        
        $portfolio = $manager->createPortfolio(1, 'My Portfolio', 25000.0);
        
        $this->assertSame(1, $portfolio->getId());
        $this->assertSame(1, $portfolio->getUserId());
        $this->assertSame(25000.0, $portfolio->getCashBalance());
    }
    
    public function testPortfolioManagerFindById(): void
    {
        $manager = new PortfolioManager();
        $created = $manager->createPortfolio(1, 'Test');
        
        $found = $manager->findById($created->getId());
        
        $this->assertSame($created, $found);
    }
    
    public function testPortfolioManagerGetByUserId(): void
    {
        $manager = new PortfolioManager();
        $manager->createPortfolio(1, 'Portfolio 1');
        $manager->createPortfolio(1, 'Portfolio 2');
        $manager->createPortfolio(2, 'Portfolio 3');
        
        $user1Portfolios = $manager->getByUserId(1);
        
        $this->assertCount(2, $user1Portfolios);
    }
    
    public function testPortfolioManagerExecuteBuy(): void
    {
        $manager = new PortfolioManager();
        $portfolio = $manager->createPortfolio(1, 'Test', 50000.0);
        
        $manager->executeBuy($portfolio->getId(), 'BTC', 0.5, 40000.0);
        
        $this->assertSame(0.5, $portfolio->getPosition('BTC'));
        $this->assertSame(30000.0, $portfolio->getCashBalance()); // 50000 - (0.5 * 40000)
    }
    
    public function testPortfolioManagerBuyInsufficientFunds(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Insufficient cash balance');
        
        $manager = new PortfolioManager();
        $portfolio = $manager->createPortfolio(1, 'Test', 1000.0);
        
        $manager->executeBuy($portfolio->getId(), 'TSLA', 100.0, 250.0);
    }
    
    public function testPortfolioManagerExecuteSell(): void
    {
        $manager = new PortfolioManager();
        $portfolio = $manager->createPortfolio(1, 'Test', 10000.0);
        $portfolio->addHolding('ETH', 10.0);
        
        $manager->executeSell($portfolio->getId(), 'ETH', 5.0, 2000.0);
        
        $this->assertSame(5.0, $portfolio->getPosition('ETH'));
        $this->assertSame(20000.0, $portfolio->getCashBalance()); // 10000 + (5 * 2000)
    }
    
    public function testPortfolioManagerSellInsufficientPosition(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Insufficient position in AAPL');
        
        $manager = new PortfolioManager();
        $portfolio = $manager->createPortfolio(1, 'Test');
        $portfolio->addHolding('AAPL', 5.0);
        
        $manager->executeSell($portfolio->getId(), 'AAPL', 10.0, 150.0);
    }
    
    public function testPortfolioManagerPortfolioNotFound(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Portfolio with ID 999 not found');
        
        $manager = new PortfolioManager();
        
        $manager->executeBuy(999, 'BTC', 1.0, 40000.0);
    }
}
