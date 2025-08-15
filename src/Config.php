<?php

namespace InnovantCafe;

class Config
{
    private static $config = [];
    
    public static function load()
    {
        if (empty(self::$config)) {
            $configPath = __DIR__ . '/../config/';
            
            // Cargar configuración de base de datos
            if (file_exists($configPath . 'database.php')) {
                self::$config['database'] = require $configPath . 'database.php';
            }
            
            // Cargar configuración de la aplicación
            if (file_exists($configPath . 'app.php')) {
                self::$config['app'] = require $configPath . 'app.php';
            }
        }
        
        return self::$config;
    }
    
    public static function get($key, $default = null)
    {
        $config = self::load();
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    public static function database($key = null)
    {
        if ($key) {
            return self::get("database.mysql.$key");
        }
        return self::get('database.mysql');
    }
    
    public static function app($key = null)
    {
        if ($key) {
            return self::get("app.$key");
        }
        return self::get('app');
    }
    
    public static function url($key = null)
    {
        if ($key) {
            return self::get("app.urls.$key");
        }
        return self::get('app.urls.base');
    }
}
