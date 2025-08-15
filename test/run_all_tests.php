<?php
/**
 * Script principal para ejecutar todas las pruebas del sistema
 * Ejecutar desde la raíz del proyecto: php test/run_all_tests.php
 */

echo "🧪 INICIANDO SUITE COMPLETA DE PRUEBAS\n";
echo "=====================================\n\n";

// Lista de pruebas en orden de ejecución
$tests = [
    'test_connection.php' => 'Conexión a Base de Datos',
    'test_autenticacion.php' => 'Sistema de Autenticación',
    'test_reset_password.php' => 'Restablecimiento de Contraseña',
    'test_crear_trabajador.php' => 'Creación de Trabajadores',
    'test_validacion_email.php' => 'Validación de Emails',
    'test_rotacion_simple.php' => 'Sistema de Rotación',
    'test_compra_completa.php' => 'Compra Completa por Turno',
    'test_modal_compra.php' => 'Modal de Compras',
    'test_avisos.php' => 'Sistema de Avisos',
    'test_tickets.php' => 'Gestión de Tickets',
    'test_reportes.php' => 'Sistema de Reportes'
];

$totalTests = count($tests);
$passedTests = 0;
$failedTests = 0;
$results = [];

echo "📋 Ejecutando {$totalTests} pruebas...\n\n";

foreach ($tests as $testFile => $description) {
    echo "🔍 **{$description}** ({$testFile})\n";
    echo "   ";
    
    // Ejecutar la prueba
    $output = [];
    $returnCode = 0;
    
    ob_start();
    try {
        include __DIR__ . '/' . $testFile;
        $output = ob_get_contents();
        ob_end_clean();
        
        // Verificar si la prueba pasó (buscar indicadores de éxito)
        if (strpos($output, '✅') !== false || strpos($output, 'CORRECTO') !== false) {
            echo "✅ PASÓ\n";
            $passedTests++;
            $results[] = "✅ {$description} - PASÓ";
        } else {
            echo "❌ FALLÓ\n";
            $failedTests++;
            $results[] = "❌ {$description} - FALLÓ";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "💥 ERROR: " . $e->getMessage() . "\n";
        $failedTests++;
        $results[] = "💥 {$description} - ERROR: " . $e->getMessage();
    }
    
    echo "\n";
}

// Resumen final
echo "📊 RESUMEN DE PRUEBAS\n";
echo "====================\n";
echo "Total de pruebas: {$totalTests}\n";
echo "✅ Pasaron: {$passedTests}\n";
echo "❌ Fallaron: {$failedTests}\n";
echo "📈 Porcentaje de éxito: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

if ($failedTests > 0) {
    echo "❌ PRUEBAS QUE FALLARON:\n";
    foreach ($results as $result) {
        if (strpos($result, '❌') !== false || strpos($result, '💥') !== false) {
            echo "   {$result}\n";
        }
    }
    echo "\n";
}

if ($passedTests > 0) {
    echo "✅ PRUEBAS EXITOSAS:\n";
    foreach ($results as $result) {
        if (strpos($result, '✅') !== false) {
            echo "   {$result}\n";
        }
    }
    echo "\n";
}

// Estado final
if ($failedTests === 0) {
    echo "🎉 ¡TODAS LAS PRUEBAS PASARON! El sistema está funcionando correctamente.\n";
} else {
    echo "⚠️  Algunas pruebas fallaron. Revisa los errores antes de usar el sistema en producción.\n";
}

echo "\n🚀 Sistema listo para usar en: " . \InnovantCafe\Config::url('base') . "\n";
?>
