<?php
require_once 'vendor/autoload.php';

use InnovantCafe\Database;
use InnovantCafe\Trabajador;
use InnovantCafe\Producto;
use InnovantCafe\SistemaRotacion;

echo "🚀 Inicializando sistema Innovant Café...\n\n";

try {
    // Inicializar base de datos
    $db = Database::getInstance();
    echo "✅ Base de datos inicializada correctamente\n";
    
    // Crear trabajadores de ejemplo
    $trabajador = new Trabajador();
    $trabajadoresEjemplo = [
        ['nombre' => 'Luis García', 'email' => 'luis@innovant.com'],
        ['nombre' => 'María López', 'email' => 'maria@innovant.com'],
        ['nombre' => 'Carlos Ruiz', 'email' => 'carlos@innovant.com'],
        ['nombre' => 'Ana Martín', 'email' => 'ana@innovant.com'],
        ['nombre' => 'David Sánchez', 'email' => 'david@innovant.com']
    ];
    
    foreach ($trabajadoresEjemplo as $trab) {
        if ($trabajador->crear($trab['nombre'], $trab['email'])) {
            echo "✅ Trabajador creado: {$trab['nombre']}\n";
        }
    }
    
    // Crear productos de ejemplo
    $producto = new Producto();
    $productosEjemplo = [
        ['nombre' => 'Pack de Leche', 'descripcion' => 'Leche semidesnatada 6 unidades', 'precio' => 4.50],
        ['nombre' => 'Café en Grano', 'descripcion' => 'Café arábica premium 1kg', 'precio' => 12.00],
        ['nombre' => 'Galletas', 'descripcion' => 'Galletas integrales 2 paquetes', 'precio' => 3.80],
        ['nombre' => 'Zumo de Naranja', 'descripcion' => 'Zumo natural 1L', 'precio' => 2.50],
        ['nombre' => 'Fruta Fresca', 'descripcion' => 'Manzanas y plátanos', 'precio' => 8.00],
        ['nombre' => 'Té Verde', 'descripcion' => 'Té verde orgánico 50 bolsitas', 'precio' => 6.50]
    ];
    
    foreach ($productosEjemplo as $prod) {
        if ($producto->crear($prod['nombre'], $prod['descripcion'], $prod['precio'])) {
            echo "✅ Producto creado: {$prod['nombre']}\n";
        }
    }
    
    // Generar rotación semanal
    $sistema = new SistemaRotacion();
    if ($sistema->generarRotacionSemanal()) {
        echo "✅ Rotación semanal generada correctamente\n";
    }
    
    echo "\n🎉 Sistema inicializado completamente!\n";
    echo "📱 Puedes acceder a la aplicación en: http://localhost:8000\n";
    echo "👥 Trabajadores creados: " . count($trabajadoresEjemplo) . "\n";
    echo "📦 Productos creados: " . count($productosEjemplo) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la inicialización: " . $e->getMessage() . "\n";
}
