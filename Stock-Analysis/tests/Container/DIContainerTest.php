<?php

namespace Tests\Container;

use App\Container\DIContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * Test suite for DIContainer (TDD approach)
 * 
 * Tests PSR-11 compliance, dependency injection, auto-wiring,
 * singletons, and method injection.
 */
class DIContainerTest extends TestCase
{
    private DIContainer $container;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new DIContainer();
    }
    
    public function testBindAndResolveSimpleClass(): void
    {
        // Arrange
        $this->container->bind(SimpleClass::class);
        
        // Act
        $instance = $this->container->get(SimpleClass::class);
        
        // Assert
        $this->assertInstanceOf(SimpleClass::class, $instance);
    }
    
    public function testBindWithClosure(): void
    {
        // Arrange
        $this->container->bind(SimpleClass::class, function() {
            return new SimpleClass('custom value');
        });
        
        // Act
        $instance = $this->container->get(SimpleClass::class);
        
        // Assert
        $this->assertInstanceOf(SimpleClass::class, $instance);
        $this->assertEquals('custom value', $instance->value);
    }
    
    public function testSingletonBinding(): void
    {
        // Arrange
        $this->container->singleton(SimpleClass::class);
        
        // Act
        $instance1 = $this->container->get(SimpleClass::class);
        $instance2 = $this->container->get(SimpleClass::class);
        
        // Assert
        $this->assertSame($instance1, $instance2, 'Singletons should return same instance');
    }
    
    public function testIsSingleton(): void
    {
        // Arrange
        $this->container->singleton(SimpleClass::class);
        $this->container->bind(AnotherClass::class);
        
        // Assert
        $this->assertTrue($this->container->isSingleton(SimpleClass::class));
        $this->assertFalse($this->container->isSingleton(AnotherClass::class));
    }
    
    public function testInstanceRegistration(): void
    {
        // Arrange
        $instance = new SimpleClass('test value');
        $this->container->instance(SimpleClass::class, $instance);
        
        // Act
        $retrieved = $this->container->get(SimpleClass::class);
        
        // Assert
        $this->assertSame($instance, $retrieved);
    }
    
    public function testHasMethod(): void
    {
        // Arrange
        $this->container->bind(SimpleClass::class);
        
        // Assert
        $this->assertTrue($this->container->has(SimpleClass::class));
        $this->assertFalse($this->container->has('NonExistentClass'));
    }
    
    public function testAutoWiringWithDependencies(): void
    {
        // Arrange - ClassWithDependency depends on SimpleClass
        $this->container->bind(SimpleClass::class);
        $this->container->bind(ClassWithDependency::class);
        
        // Act
        $instance = $this->container->get(ClassWithDependency::class);
        
        // Assert
        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->dependency);
    }
    
    public function testMakeWithParameters(): void
    {
        // Arrange
        $this->container->bind(SimpleClass::class);
        
        // Act
        $instance = $this->container->make(SimpleClass::class, ['value' => 'override']);
        
        // Assert
        $this->assertInstanceOf(SimpleClass::class, $instance);
        $this->assertEquals('override', $instance->value);
    }
    
    public function testCallMethodInjection(): void
    {
        // Arrange
        $this->container->bind(SimpleClass::class);
        $object = new ClassWithMethods();
        
        // Act
        $result = $this->container->call([$object, 'methodWithDependency'], ['extra' => 'param']);
        
        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('SimpleClass', $result);
        $this->assertStringContainsString('param', $result);
    }
    
    public function testGetNonExistentThrowsException(): void
    {
        // Assert
        $this->expectException(NotFoundExceptionInterface::class);
        
        // Act
        $this->container->get('NonExistent\\Class\\Name');
    }
    
    public function testBindingInterfaceToImplementation(): void
    {
        // Arrange
        $this->container->bind(TestInterface::class, ConcreteImplementation::class);
        
        // Act
        $instance = $this->container->get(TestInterface::class);
        
        // Assert
        $this->assertInstanceOf(ConcreteImplementation::class, $instance);
        $this->assertInstanceOf(TestInterface::class, $instance);
    }
    
    public function testNestedDependencyResolution(): void
    {
        // Arrange - ClassA needs ClassB needs ClassC
        $this->container->bind(ClassC::class);
        $this->container->bind(ClassB::class);
        $this->container->bind(ClassA::class);
        
        // Act
        $instance = $this->container->get(ClassA::class);
        
        // Assert
        $this->assertInstanceOf(ClassA::class, $instance);
        $this->assertInstanceOf(ClassB::class, $instance->classB);
        $this->assertInstanceOf(ClassC::class, $instance->classB->classC);
    }
    
    public function testBindIfNotBound(): void
    {
        // Arrange
        $first = new SimpleClass('first');
        $second = new SimpleClass('second');
        
        // Act
        $this->container->instance(SimpleClass::class, $first);
        $this->container->bind(SimpleClass::class); // Should not override existing
        
        $retrieved = $this->container->get(SimpleClass::class);
        
        // Assert
        $this->assertSame($first, $retrieved);
    }
    
    public function testResolvingWithoutBinding(): void
    {
        // Act - Should auto-resolve since SimpleClass has no dependencies
        $instance = $this->container->get(SimpleClass::class);
        
        // Assert
        $this->assertInstanceOf(SimpleClass::class, $instance);
    }
}

// Test helper classes
class SimpleClass
{
    public string $value;
    
    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }
}

class AnotherClass
{
    public function __construct()
    {
    }
}

class ClassWithDependency
{
    public SimpleClass $dependency;
    
    public function __construct(SimpleClass $dependency)
    {
        $this->dependency = $dependency;
    }
}

class ClassWithMethods
{
    public function methodWithDependency(SimpleClass $dep, string $extra): string
    {
        return get_class($dep) . ' with ' . $extra;
    }
}

interface TestInterface
{
}

class ConcreteImplementation implements TestInterface
{
}

class ClassC
{
    public function __construct()
    {
    }
}

class ClassB
{
    public ClassC $classC;
    
    public function __construct(ClassC $classC)
    {
        $this->classC = $classC;
    }
}

class ClassA
{
    public ClassB $classB;
    
    public function __construct(ClassB $classB)
    {
        $this->classB = $classB;
    }
}
