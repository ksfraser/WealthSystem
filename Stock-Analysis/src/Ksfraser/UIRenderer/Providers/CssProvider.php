<?php

namespace Ksfraser\UIRenderer\Providers;

/**
 * CSS Provider - Single Responsibility for styling
 * Provides customizable CSS themes and styles
 */
class CssProvider {
    
    /**
     * Get base application styles
     */
    public static function getBaseStyles(): string {
        return '
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; line-height: 1.6;
        }
        * { box-sizing: border-box; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { 
            background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .card { 
            background: white; padding: 20px; margin: 10px 0; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: box-shadow 0.3s ease;
        }
        .card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .error { border-left: 4px solid #dc3545; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .flex { display: flex; }
        .flex-wrap { flex-wrap: wrap; }
        .justify-between { justify-content: space-between; }
        .align-center { align-items: center; }
        .gap-1 { gap: 0.5rem; }
        .gap-2 { gap: 1rem; }
        .gap-3 { gap: 1.5rem; }
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        .mt-1 { margin-top: 0.5rem; }
        .mt-2 { margin-top: 1rem; }
        .mt-3 { margin-top: 1.5rem; }
        ';
    }
    
    /**
     * Get button styles
     */
    public static function getButtonStyles(): string {
        return '
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px; transition: all 0.3s ease;
            border: none; cursor: pointer; font-size: 14px; font-weight: 500;
        }
        .btn:hover { background: #0056b3; transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-lg { padding: 12px 24px; font-size: 16px; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .btn-outline { background: transparent; border: 2px solid currentColor; }
        .btn-outline:hover { background: currentColor; }
        ';
    }
    
    /**
     * Get navigation styles
     */
    public static function getNavigationStyles(): string {
        return '
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .nav-header.dark {
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .nav-links a.active {
            background: rgba(255,255,255,0.3);
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .nav-container { flex-direction: column; gap: 15px; }
            .nav-links { justify-content: center; }
        }
        ';
    }
    
    /**
     * Get table styles
     */
    public static function getTableStyles(): string {
        return '
        .table-container { overflow-x: auto; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .table tr:hover {
            background-color: #f8f9fa;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .table-striped tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        ';
    }
    
    /**
     * Get form styles
     */
    public static function getFormStyles(): string {
        return '
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            color: #495057;
            background-color: #fff;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .form-control::placeholder {
            color: #6c757d;
            opacity: 1;
        }
        .form-control:disabled {
            background-color: #e9ecef;
            opacity: 1;
        }
        ';
    }
    
    /**
     * Get all styles combined
     */
    public static function getAllStyles(): string {
        return self::getBaseStyles() . 
               self::getButtonStyles() . 
               self::getNavigationStyles() . 
               self::getTableStyles() . 
               self::getFormStyles();
    }
    
    /**
     * Get styles for a specific theme
     */
    public static function getThemeStyles(string $theme = 'default'): string {
        $base = self::getAllStyles();
        
        switch ($theme) {
            case 'dark':
                return $base . self::getDarkThemeOverrides();
            case 'minimal':
                return $base . self::getMinimalThemeOverrides();
            default:
                return $base;
        }
    }
    
    /**
     * Dark theme overrides
     */
    private static function getDarkThemeOverrides(): string {
        return '
        body { background: #212529; color: #fff; }
        .card, .header { background: #343a40; color: #fff; }
        .table th { background-color: #495057; color: #fff; }
        .table tr:hover { background-color: #495057; }
        .form-control { background-color: #495057; color: #fff; border-color: #6c757d; }
        ';
    }
    
    /**
     * Minimal theme overrides
     */
    private static function getMinimalThemeOverrides(): string {
        return '
        .card { box-shadow: none; border: 1px solid #e9ecef; }
        .nav-header { background: #fff; color: #212529; border-bottom: 1px solid #e9ecef; }
        .nav-links a { color: #212529; }
        ';
    }
}
