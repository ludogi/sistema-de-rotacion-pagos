<?php

namespace InnovantCafe;

class Router
{
    private static $routes = [];
    private static $baseUrl;
    
    public static function init()
    {
        self::$baseUrl = Config::url('base');
    }
    
    /**
     * Genera una URL amigable
     */
    public static function url($route, $params = [])
    {
        $url = self::$baseUrl . '/' . trim($route, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Genera una URL para una acción específica
     */
    public static function action($action, $params = [])
    {
        $params['action'] = $action;
        return self::$baseUrl . '/index.php?' . http_build_query($params);
    }
    
    /**
     * Genera una URL para un recurso específico
     */
    public static function resource($resource, $id = null, $action = null)
    {
        $url = self::$baseUrl . '/' . $resource;
        
        if ($id) {
            $url .= '/' . $id;
        }
        
        if ($action) {
            $url .= '/' . $action;
        }
        
        return $url;
    }
    
    /**
     * Obtiene la URL base
     */
    public static function base()
    {
        return self::$baseUrl;
    }
    
    /**
     * Obtiene la URL actual
     */
    public static function current()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Obtiene la sección actual desde la URL
     */
    public static function getSection()
    {
        return $_GET['section'] ?? 'dashboard';
    }
    
    /**
     * Obtiene el ID del recurso desde la URL
     */
    public static function getResourceId()
    {
        return $_GET['id'] ?? null;
    }
    
    /**
     * Obtiene la acción desde la URL
     */
    public static function getAction()
    {
        return $_GET['action'] ?? null;
    }
    
    /**
     * Verifica si la URL actual coincide con un patrón
     */
    public static function is($pattern)
    {
        $current = self::current();
        return strpos($current, $pattern) !== false;
    }
    
    /**
     * Redirige a una URL
     */
    public static function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Redirige a una ruta amigable
     */
    public static function redirectTo($route, $params = [])
    {
        self::redirect(self::url($route, $params));
    }
}
