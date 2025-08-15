<?php
// Script para verificación automática de avisos
// Este archivo debe ejecutarse mediante cron job
// Ejemplo: */5 * * * * php /ruta/completa/cron_check_avisos.php

require_once 'vendor/autoload.php';

use InnovantCafe\SistemaAvisos;
use InnovantCafe\EmailService;
use InnovantCafe\Database;

try {
    echo "🕐 " . date('Y-m-d H:i:s') . " - Iniciando verificación automática de avisos...\n";
    
    // Inicializar sistema
    $sistemaAvisos = new SistemaAvisos();
    $emailService = new EmailService();
    
    // Verificar avisos periódicos
    $avisosGenerados = $sistemaAvisos->verificarAvisosPeriodicos();
    
    if (empty($avisosGenerados)) {
        echo "✅ No hay avisos pendientes de generar\n";
        exit(0);
    }
    
    echo "📢 Se generaron " . count($avisosGenerados) . " avisos\n";
    
    // Obtener avisos pendientes para enviar emails
    $avisosPendientes = $sistemaAvisos->obtenerAvisosPendientes();
    
    foreach ($avisosPendientes as $aviso) {
        // Obtener información del trabajador
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT email FROM trabajadores WHERE id = ?");
        $stmt->execute([$aviso['trabajador_id']]);
        $trabajador = $stmt->fetch();
        
        if ($trabajador && $trabajador['email']) {
            // Enviar email de aviso
            $subject = "🚨 Aviso de Compra Pendiente - " . $aviso['producto_nombre'];
            
            $message = "
            <h2>🚨 Aviso de Compra Pendiente</h2>
            <p>Hola <strong>{$aviso['trabajador_nombre']}</strong>,</p>
            
            <p>El sistema ha detectado que necesitas comprar el siguiente producto:</p>
            
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                <h3>📦 {$aviso['producto_nombre']}</h3>
                <p><strong>Motivo:</strong> {$aviso['motivo']}</p>
                <p><strong>Fecha límite:</strong> " . date('d/m/Y', strtotime($aviso['fecha_limite'])) . "</p>
                <p><strong>Prioridad:</strong> " . ucfirst($aviso['prioridad']) . "</p>
            </div>
            
            <p>Por favor, accede al sistema para marcar esta compra como completada.</p>
            
            <p>Saludos,<br>
            <strong>Sistema Innovant Café</strong></p>
            ";
            
            $resultado = $emailService->enviarEmail(
                $trabajador['email'],
                $subject,
                $message,
                true
            );
            
            if ($resultado['success']) {
                echo "✅ Email enviado a {$trabajador['email']} para {$aviso['producto_nombre']}\n";
                
                // Marcar aviso como notificado
                $stmt = $db->prepare("UPDATE asignaciones_compra SET notificado = 1 WHERE id = ?");
                $stmt->execute([$aviso['id']]);
            } else {
                echo "❌ Error enviando email a {$trabajador['email']}: {$resultado['message']}\n";
            }
        } else {
            echo "⚠️ Trabajador {$aviso['trabajador_nombre']} no tiene email configurado\n";
        }
    }
    
    echo "🎉 Verificación automática completada\n";
    
} catch (Exception $e) {
    echo "❌ Error en verificación automática: " . $e->getMessage() . "\n";
    exit(1);
}
?>
