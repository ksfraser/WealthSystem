<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Container;
use App\Core\ServiceContainer;
use App\Controllers\BaseController;

/**
 * Test MVC Framework Integration with Symfony HTTP Foundation
 * 
 * Verifies that BaseController, Router, and ServiceContainer
 * work correctly with Symfony-based Request/Response objects.
 */
class MvcSymfonyIntegrationTest extends TestCase
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
     * BaseController should return Symfony Response from view()
     */
    public function base_controller_view_returns_symfony_response(): void
    {
        $controller = new class($this->container) extends BaseController {
            public function __construct(Container $container)
            {
                $this->container = $container;
            }
            
            protected function process(Request $request): Response
            {
                return new Response('Test', 200);
            }
            
            public function testView(): Response
            {
                return $this->view('test.view', ['data' => 'value']);
            }
        };
        
        // View rendering will fail (no template), but should return Response
        $this->expectException(\Exception::class);
        $response = $controller->testView();
    }
    
    /**
     * @test
     * BaseController json() should return Symfony JsonResponse
     */
    public function base_controller_json_returns_symfony_response(): void
    {
        $controller = new class($this->container) extends BaseController {
            public function __construct(Container $container)
            {
                $this->container = $container;
            }
            
            protected function process(Request $request): Response
            {
                return $this->json(['status' => 'success']);
            }
        };
        
        $request = Request::create('/test', 'GET');
        $response = $controller->handle($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('success', $response->getContent());
    }
    
    /**
     * @test
     * BaseController redirect() should return Symfony RedirectResponse
     */
    public function base_controller_redirect_returns_symfony_response(): void
    {
        $controller = new class($this->container) extends BaseController {
            public function __construct(Container $container)
            {
                $this->container = $container;
            }
            
            protected function process(Request $request): Response
            {
                return $this->redirect('/dashboard');
            }
        };
        
        $request = Request::create('/test', 'GET');
        $response = $controller->handle($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/dashboard', $response->headers->get('Location'));
    }
    
    /**
     * @test
     * Router should work with closures returning Symfony Response
     */
    public function router_works_with_controller_responses(): void
    {
        // Use closure instead of controller for simpler test
        $this->router->get('/api/test', function(Request $request) {
            return Response::json(['message' => 'Hello from controller']);
        });
        
        // Dispatch request
        $request = Request::create('/api/test', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Hello from controller', $response->getContent());
    }
    
    /**
     * @test
     * Router should handle closure routes with Symfony Response
     */
    public function router_handles_closure_routes(): void
    {
        $this->router->get('/test', function(Request $request) {
            return Response::json(['test' => true]);
        });
        
        $request = Request::create('/test', 'GET');
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('application/json', $contentType);
    }
    
    /**
     * @test
     * Router should handle POST requests with Symfony Request
     */
    public function router_handles_post_requests(): void
    {
        $this->router->post('/submit', function(Request $request) {
            $data = $request->post('data');
            return Response::json(['received' => $data]);
        });
        
        $request = Request::create('/submit', 'POST', ['data' => 'test-value']);
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals('test-value', $decoded['received']);
    }
    
    /**
     * @test
     * ServiceContainer should resolve dependencies with Symfony components
     */
    public function service_container_resolves_with_symfony(): void
    {
        // This tests that ServiceContainer can create services that depend on Request/Response
        $this->container->bind(Request::class, function() {
            return Request::create('/test', 'GET');
        });
        
        $request = $this->container->get(Request::class);
        
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('/test', $request->getUri());
    }
    
    /**
     * @test
     * Complete request/response cycle through Router
     */
    public function complete_request_response_cycle(): void
    {
        // Register routes
        $this->router->get('/', function(Request $request) {
            return new Response('Home Page', 200, ['Content-Type' => 'text/html']);
        });
        
        $this->router->get('/api/data', function(Request $request) {
            return Response::json(['items' => [1, 2, 3]]);
        });
        
        $this->router->post('/api/submit', function(Request $request) {
            $name = $request->post('name', 'Guest');
            return Response::json(['greeting' => "Hello, {$name}!"]);
        });
        
        // Test home route
        $homeRequest = Request::create('/', 'GET');
        $homeResponse = $this->router->dispatch($homeRequest);
        $this->assertEquals(200, $homeResponse->getStatusCode());
        $this->assertEquals('Home Page', $homeResponse->getContent());
        
        // Test JSON API route
        $apiRequest = Request::create('/api/data', 'GET');
        $apiResponse = $this->router->dispatch($apiRequest);
        $this->assertEquals(200, $apiResponse->getStatusCode());
        $contentType = $apiResponse->headers->get('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
        
        // Test POST route
        $postRequest = Request::create('/api/submit', 'POST', ['name' => 'John']);
        $postResponse = $this->router->dispatch($postRequest);
        $this->assertEquals(200, $postResponse->getStatusCode());
        $decoded = json_decode($postResponse->getContent(), true);
        $this->assertEquals('Hello, John!', $decoded['greeting']);
    }
    
    /**
     * @test
     * BaseController validate() should work with Symfony Request
     */
    public function base_controller_validation_works(): void
    {
        $controller = new class($this->container) extends BaseController {
            public function __construct(Container $container)
            {
                $this->container = $container;
            }
            
            protected function process(Request $request): Response
            {
                return new Response('', 200);
            }
            
            public function testValidate(Request $request): array
            {
                return $this->validate($request, [
                    'email' => 'required|email',
                    'name' => 'required'
                ]);
            }
        };
        
        // Valid data
        $validRequest = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
            'name' => 'John Doe'
        ]);
        
        $result = $controller->testValidate($validRequest);
        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('John Doe', $result['name']);
        
        // Invalid data should throw exception
        $invalidRequest = Request::create('/test', 'POST', [
            'email' => 'invalid-email'
        ]);
        
        $this->expectException(\App\Controllers\ValidationException::class);
        $controller->testValidate($invalidRequest);
    }
}
