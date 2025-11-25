<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Core\Interfaces\ControllerInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Base Controller
 * 
 * Provides common functionality for all controllers.
 * Follows Single Responsibility Principle (SRP) and Template Method Pattern.
 */
abstract class BaseController implements ControllerInterface 
{
    protected ?Container $container = null;
    
    /**
     * Set dependency injection container
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
    /**
     * Handle request - Template method that can be overridden
     * Returns Symfony Response (base class for all response types)
     */
    public function handle(Request $request): SymfonyResponse 
    {
        // Pre-processing hook
        $this->before($request);
        
        // Main processing (to be implemented by child classes)
        $response = $this->process($request);
        
        // Post-processing hook
        $this->after($request, $response);
        
        return $response;
    }
    
    /**
     * Main processing method - must be implemented by child classes
     * Returns Symfony Response (allows Response, JsonResponse, RedirectResponse, etc.)
     */
    abstract protected function process(Request $request): SymfonyResponse;
    
    /**
     * Pre-processing hook - can be overridden by child classes
     */
    protected function before(Request $request): void 
    {
        // Default implementation - do nothing
    }
    
    /**
     * Post-processing hook - can be overridden by child classes
     */
    protected function after(Request $request, SymfonyResponse $response): void 
    {
        // Default implementation - do nothing
    }
    
    /**
     * Render view with data
     * Returns Symfony Response with HTML content
     */
    protected function view(string $template, array $data = []): SymfonyResponse 
    {
        // Try to use ViewService if available
        try {
            if ($this->container) {
                $viewService = $this->container->get('App\\Services\\ViewService');
                $content = $viewService->renderWithShared($template, $data);
                return Response::html($content);
            }
        } catch (\Exception $e) {
            // Fall back to simple rendering
        }
        
        // Fallback to simple template rendering
        extract($data);
        ob_start();
        
        $templatePath = $this->getTemplatePath($template);
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            throw new \Exception("Template not found: {$template}");
        }
        
        $content = ob_get_clean();
        return Response::html($content);
    }
    
    /**
     * Return JSON response
     * Returns Symfony JsonResponse (subclass of Response)
     */
    protected function json(array $data, int $statusCode = 200): SymfonyResponse 
    {
        return Response::json($data, $statusCode);
    }
    
    /**
     * Return redirect response
     * Returns Symfony RedirectResponse (subclass of Response)
     */
    protected function redirect(string $url, int $statusCode = 302): SymfonyResponse 
    {
        return Response::redirect($url, $statusCode);
    }
    
    /**
     * Validate request data
     */
    protected function validate(Request $request, array $rules): array 
    {
        $errors = [];
        $data = [];
        
        foreach ($rules as $field => $rule) {
            $value = $request->post($field);
            
            // Required validation
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "Field {$field} is required";
                continue;
            }
            
            // Email validation
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Field {$field} must be a valid email";
                continue;
            }
            
            $data[$field] = $value;
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        
        return $data;
    }
    
    /**
     * Get template file path
     */
    private function getTemplatePath(string $template): string 
    {
        // Convert dot notation to directory path
        $path = str_replace('.', '/', $template);
        
        // Add .php extension if not present
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
            $path .= '.php';
        }
        
        return __DIR__ . '/../../Views/' . $path;
    }
}

/**
 * Validation Exception
 */
class ValidationException extends \Exception 
{
    private array $errors;
    
    public function __construct(array $errors) 
    {
        $this->errors = $errors;
        parent::__construct('Validation failed');
    }
    
    public function getErrors(): array 
    {
        return $this->errors;
    }
}