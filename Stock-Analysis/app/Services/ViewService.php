<?php

namespace App\Services;

/**
 * View Service
 * 
 * Simple template rendering service for the MVC application.
 * Handles view rendering with layout support.
 */
class ViewService
{
    private string $viewsPath;
    private string $layoutsPath;
    private string $defaultLayout = 'app';
    
    public function __construct(string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? __DIR__ . '/../Views';
        $this->layoutsPath = $this->viewsPath . '/Layouts';
    }
    
    /**
     * Render a view with optional layout
     */
    public function render(string $view, array $data = [], string $layout = null): string
    {
        $layout = $layout ?? $this->defaultLayout;
        
        // Render the view content
        $content = $this->renderView($view, $data);
        
        // If no layout specified, return just the content
        if ($layout === false || $layout === null) {
            return $content;
        }
        
        // Render with layout
        return $this->renderLayout($layout, array_merge($data, ['content' => $content]));
    }
    
    /**
     * Render view without layout
     */
    public function renderView(string $view, array $data = []): string
    {
        $viewFile = $this->getViewPath($view);
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$view}");
        }
        
        return $this->renderFile($viewFile, $data);
    }
    
    /**
     * Render layout file
     */
    public function renderLayout(string $layout, array $data = []): string
    {
        $layoutFile = $this->layoutsPath . '/' . $layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file not found: {$layout}");
        }
        
        return $this->renderFile($layoutFile, $data);
    }
    
    /**
     * Get view file path
     */
    private function getViewPath(string $view): string
    {
        // Convert dot notation to directory structure
        $path = str_replace('.', '/', $view);
        return $this->viewsPath . '/' . $path . '.php';
    }
    
    /**
     * Render PHP file with data
     */
    private function renderFile(string $file, array $data = []): string
    {
        // Extract variables to local scope
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the file
            include $file;
            
            // Get and clean the buffer
            return ob_get_clean();
            
        } catch (\Throwable $e) {
            // Clean the buffer on error
            ob_end_clean();
            throw $e;
        }
    }
    
    /**
     * Set default layout
     */
    public function setDefaultLayout(string $layout): void
    {
        $this->defaultLayout = $layout;
    }
    
    /**
     * Get default layout
     */
    public function getDefaultLayout(): string
    {
        return $this->defaultLayout;
    }
    
    /**
     * Check if view exists
     */
    public function exists(string $view): bool
    {
        return file_exists($this->getViewPath($view));
    }
    
    /**
     * Share data across all views
     */
    private static array $sharedData = [];
    
    public static function share(string $key, $value): void
    {
        self::$sharedData[$key] = $value;
    }
    
    public static function getSharedData(): array
    {
        return self::$sharedData;
    }
    
    /**
     * Render with shared data
     */
    public function renderWithShared(string $view, array $data = [], string $layout = null): string
    {
        $data = array_merge(self::$sharedData, $data);
        return $this->render($view, $data, $layout);
    }
}