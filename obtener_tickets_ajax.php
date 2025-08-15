<?php
require_once 'vendor/autoload.php';

use InnovantCafe\Auth;
use InnovantCafe\GestorTickets;
use InnovantCafe\Database;

// Configuración básica
date_default_timezone_set('Europe/Madrid');
session_start();

// Verificar autenticación
$auth = new Auth();
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener parámetros
$fecha = $_GET['fecha'] ?? null;
$trabajadorId = $_GET['trabajador_id'] ?? null;

if (!$fecha || !$trabajadorId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

try {
    // Inicializar gestor de tickets
    $gestorTickets = new GestorTickets();
    
    // Obtener tickets de la transacción
    $tickets = $gestorTickets->obtenerTicketsTransaccion($fecha, $trabajadorId);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'tickets' => $tickets,
        'total' => count($tickets)
    ];
    
    // Enviar respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
