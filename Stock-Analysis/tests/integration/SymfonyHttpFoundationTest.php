<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Container;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Test Symfony HTTP Foundation Integration
 * 
 * Following TDD principles - tests verify that our custom Request/Response
 * classes properly extend Symfony components and maintain compatibility.
 */
class SymfonyHttpFoundationTest extends TestCase
{
    private Container $container;
    private Router $router;
    
    protected function setUp(): void
    {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }
    
    /**
     * @test
     * Request should extend Symfony Request
     */
    public function request_extends_symfony_request(): void
    {
        $request = Request::fromGlobals();
        
        $this->assertInstanceOf(SymfonyRequest::class, $request);
        $this->assertInstanceOf(Request::class, $request);
    }
    
    /**
     * @test
     * Response should extend Symfony Response
     */
    public function response_extends_symfony_response(): void
    {
        $response = new Response('Test content', 200);
        
        $this->assertInstanceOf(SymfonyResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
    }
    
    /**
     * @test
     * Request compatibility methods should work
     */
    public function request_compatibility_methods_work(): void
    {
        // Create request with query parameters
        $symfonyRequest = SymfonyRequest::create(
            '/test?foo=bar&page=1',
            'GET',
            ['foo' => 'bar', 'page' => '1']
        );
        
        $request = Request::createFromSymfonyRequest($symfonyRequest);
        
        // Test compatibility methods
        $this->assertEquals('bar', $request->get('foo'));
        $this->assertEquals('1', $request->get('page'));
        $this->assertEquals('default', $request->get('missing', 'default'));
        $this->assertEquals('/test', $request->getUri());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertIsArray($request->allGet());
        $this->assertCount(2, $request->allGet());
    }
    
    /**
     * @test
     * POST request compatibility methods should work
     */
    public function post_request_compatibility_methods_work(): void
    {
        $symfonyRequest = SymfonyRequest::create(
            '/submit',
            'POST',
            ['username' => 'testuser', 'email' => 'test@example.com']
        );
        
        $request = Request::createFromSymfonyRequest($symfonyRequest);
        
        $this->assertEquals('testuser', $request->post('username'));
        $this->assertEquals('test@example.com', $request->post('email'));
        $this->assertEquals('default', $request->post('missing', 'default'));
        $this->assertIsArray($request->allPost());
        $this->assertCount(2, $request->allPost());
        $this->assertTrue($request->isMethod('POST'));
    }
    
    /**
     * @test
     * AJAX detection should work
     */
    public function ajax_detection_works(): void
    {
        // Regular request
        $request = Request::create('/test', 'GET');
        $this->assertFalse($request->isAjax());
        
        // AJAX request
        $ajaxRequest = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertTrue($ajaxRequest->isAjax());
    }
    
    /**
     * @test
     * Response JSON helper should work
     */
    public function response_json_helper_works(): void
    {
        $data = ['status' => 'success', 'message' => 'Test'];
        $response = Response::json($data);
        
        $this->assertInstanceOf(SymfonyResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(json_encode($data), $response->getContent());
    }
    
    /**
     * @test
     * Response redirect helper should work
     */
    public function response_redirect_helper_works(): void
    {
        $response = Response::redirect('/dashboard');
        
        $this->assertInstanceOf(SymfonyResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->headers->get('Location'));
    }
    
    /**
     * @test
     * Response can be created with custom status and headers
     */
    public function response_creation_with_custom_status_and_headers(): void
    {
        $response = new Response(
            'Not Found',
            404,
            ['Content-Type' => 'text/html', 'X-Custom' => 'Value']
        );
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('text/html', $response->headers->get('Content-Type'));
        $this->assertEquals('Value', $response->headers->get('X-Custom'));
        $this->assertEquals('Not Found', $response->getContent());
    }
    
    /**
     * @test
     * Router should accept Symfony Request and return Symfony Response
     */
    public function router_works_with_symfony_components(): void
    {
        // Register a test route
        $this->router->get('/test', function(Request $request) {
            return new Response('Test Response', 200);
        });
        
        // Create Symfony-based request
        $request = Request::create('/test', 'GET');
        
        // Dispatch and verify response
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(SymfonyResponse::class, $response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test Response', $response->getContent());
    }
    
    /**
     * @test
     * Router should handle 404 with Symfony Response
     */
    public function router_returns_symfony_response_for_404(): void
    {
        $request = Request::create('/nonexistent', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(SymfonyResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404', $response->getContent());
    }
    
    /**
     * @test
     * Request headers should be accessible via Symfony methods
     */
    public function request_headers_accessible_via_symfony(): void
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'TestAgent/1.0',
                'HTTP_AUTHORIZATION' => 'Bearer token123'
            ]
        );
        
        // Test both Symfony and compatibility methods
        $this->assertEquals('application/json', $request->headers->get('Accept'));
        $this->assertEquals('TestAgent/1.0', $request->headers->get('User-Agent'));
        $this->assertEquals('Bearer token123', $request->header('Authorization'));
        $this->assertEquals('application/json', $request->header('Accept'));
    }
    
    /**
     * @test
     * Response headers should be accessible via Symfony methods
     */
    public function response_headers_accessible_via_symfony(): void
    {
        $response = new Response('Content', 200, ['X-Custom-Header' => 'CustomValue']);
        
        $this->assertEquals('CustomValue', $response->headers->get('X-Custom-Header'));
        
        // Test compatibility method
        $response->setHeader('X-Another', 'AnotherValue');
        $this->assertEquals('AnotherValue', $response->headers->get('X-Another'));
    }
}
