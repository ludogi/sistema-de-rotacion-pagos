<?php

namespace InnovantCafe;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;
    
    private function __construct()
    {
        try {
            $config = Config::database();
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            $this->createTables();
        } catch (PDOException $e) {
            die('Error de conexión a MySQL: ' . $e->getMessage());
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->pdo;
    }
    
    private function createTables()
    {
        // Tabla de trabajadores
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS trabajadores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(150),
                avatar VARCHAR(255) DEFAULT 'default-avatar.png',
                activo BOOLEAN DEFAULT TRUE,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabla de productos
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS productos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                descripcion TEXT,
                precio_estimado DECIMAL(10,2),
                categoria VARCHAR(50) DEFAULT 'general',
                imagen VARCHAR(255),
                estado ENUM('disponible', 'agotado', 'pendiente_compra') DEFAULT 'disponible',
                cantidad_actual INT DEFAULT 0,
                cantidad_minima INT DEFAULT 1,
                activo BOOLEAN DEFAULT TRUE,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabla de inventario (stock actual)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS inventario (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT,
                cantidad INT NOT NULL DEFAULT 0,
                fecha_ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE,
                UNIQUE KEY unique_producto (producto_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabla de asignaciones de compra (cuando un producto se agota)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS asignaciones_compra (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT,
                trabajador_id INT,
                estado ENUM('pendiente', 'en_proceso', 'completada', 'cancelada') DEFAULT 'pendiente',
                prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
                fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_limite DATE,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE,
                FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id) ON DELETE CASCADE,
                INDEX idx_estado (estado),
                INDEX idx_fecha_limite (fecha_limite)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabla de historial de compras
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS historial_compras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT,
                trabajador_id INT,
                cantidad_comprada INT NOT NULL,
                precio_unitario DECIMAL(10,2),
                precio_total DECIMAL(10,2),
                lugar_compra VARCHAR(100),
                fecha_compra DATE,
                notas TEXT,
                recibos_adjuntos TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE,
                FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id) ON DELETE CASCADE,
                INDEX idx_fecha_compra (fecha_compra),
                INDEX idx_trabajador (trabajador_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabla de consumo de productos
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS consumo_productos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                producto_id INT,
                cantidad_consumida INT NOT NULL,
                fecha_consumo DATE,
                trabajador_responsable INT,
                motivo VARCHAR(255),
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE CASCADE,
                FOREIGN KEY (trabajador_responsable) REFERENCES trabajadores (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Tabla de configuraciones del sistema
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS configuraciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(100) UNIQUE NOT NULL,
                valor TEXT,
                descripcion TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Insertar configuraciones por defecto
        $this->insertDefaultConfigs();
    }
    
    private function insertDefaultConfigs()
    {
        $configs = [
            ['clave' => 'auto_rotation', 'valor' => '1', 'descripcion' => 'Generar rotación automáticamente'],
            ['clave' => 'notification_email', 'valor' => '0', 'descripcion' => 'Notificaciones por email'],
            ['clave' => 'currency', 'valor' => 'EUR', 'descripcion' => 'Moneda del sistema'],
            ['clave' => 'stock_warning', 'valor' => '1', 'descripcion' => 'Avisos de stock bajo']
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO configuraciones (clave, valor, descripcion) 
            VALUES (:clave, :valor, :descripcion)
        ");
        
        foreach ($configs as $config) {
            $stmt->execute($config);
        }
    }
}
