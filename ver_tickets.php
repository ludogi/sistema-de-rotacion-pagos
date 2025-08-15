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
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
$usuarioActual = $auth->getUsuarioActual();
$esAdmin = $auth->isAdmin();

// Inicializar gestor de tickets
$gestorTickets = new GestorTickets();

// Obtener parámetros de la URL
$compraId = $_GET['compra_id'] ?? null;
$trabajadorId = $_GET['trabajador_id'] ?? null;
$compraFecha = $_GET['compra_fecha'] ?? null;
$verTodos = $_GET['todos'] ?? false;

// Obtener tickets según los parámetros
if ($compraId) {
    $tickets = $gestorTickets->obtenerTicketsCompra($compraId);
    $titulo = "Tickets de la Compra #$compraId";
} elseif ($compraFecha && $trabajadorId) {
    // Obtener tickets de una transacción específica (fecha + trabajador)
    $tickets = $gestorTickets->obtenerTicketsTransaccion($compraFecha, $trabajadorId);
    $fechaFormateada = date('d/m/Y', strtotime($compraFecha));
    $titulo = "Tickets del $fechaFormateada";
} elseif ($trabajadorId && $esAdmin) {
    $tickets = $gestorTickets->obtenerTicketsTrabajador($trabajadorId);
    $titulo = "Tickets del Trabajador";
} elseif ($verTodos && $esAdmin) {
    $tickets = $gestorTickets->obtenerTicketsTrabajador($usuarioActual['trabajador_id'] ?? 0);
    $titulo = "Todos los Tickets";
} else {
    $tickets = $gestorTickets->obtenerTicketsTrabajador($usuarioActual['trabajador_id'] ?? 0);
    $titulo = "Mis Tickets";
}

// Obtener estadísticas
$estadisticas = $gestorTickets->obtenerEstadisticasTickets();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - Innovant Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header-container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-center flex-grow-1">
                    <h1 class="header-title">
                        <i class="bi bi-receipt"></i> <?php echo $titulo; ?>
                    </h1>
                    <p class="header-subtitle">Gestión de Tickets de Compras</p>
                </div>
                
                <div class="user-profile">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($usuarioActual['trabajador_nombre'] ?? $usuarioActual['username']); ?></div>
                            <?php if ($esAdmin): ?>
                                <span class="user-role role-admin">Administrador</span>
                            <?php else: ?>
                                <span class="user-role role-worker">Trabajador</span>
                            <?php endif; ?>
                        </div>
                        <div class="header-actions">
                            <button type="button" class="theme-toggle" id="themeToggle" title="Cambiar Tema">
                                <i class="bi bi-moon-fill"></i>
                            </button>
                            <a href="index.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <a href="logout.php" class="logout-btn" title="Cerrar Sesión">
                                <i class="bi bi-box-arrow-right"></i> Salir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Tickets -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo $estadisticas['total_tickets']; ?></h3>
                    <p class="mb-0"><i class="bi bi-receipt"></i> Total Tickets</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo $estadisticas['compras_con_ticket']; ?></h3>
                    <p class="mb-0"><i class="bi bi-cart-check"></i> Compras con Ticket</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo $estadisticas['trabajadores_con_ticket']; ?></h3>
                    <p class="mb-0"><i class="bi bi-people"></i> Trabajadores</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo round($estadisticas['tamano_promedio'] / 1024, 1); ?> KB</h3>
                    <p class="mb-0"><i class="bi bi-file-earmark"></i> Tamaño Promedio</p>
                </div>
            </div>
        </div>

        <!-- Lista de Tickets -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul"></i> Lista de Tickets
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($tickets)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">No hay tickets disponibles</h4>
                        <p class="text-muted">Aún no se han subido tickets para las compras.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Producto</th>
                                    <th>Fecha Compra</th>
                                    <th>Trabajador</th>
                                    <th>Tamaño</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi <?php echo $gestorTickets->obtenerIconoTipo($ticket['tipo_archivo']); ?> fs-4 me-2 text-primary"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($ticket['nombre_archivo']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_subida'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($ticket['producto_nombre'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($ticket['fecha_compra'] ?? $ticket['fecha_subida'])); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ticket['trabajador_nombre']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo round($ticket['tamano_archivo'] / 1024, 1); ?> KB
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo $gestorTickets->obtenerUrlTicket($ticket['ruta_archivo']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary" 
                                                   title="Ver Ticket">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo $gestorTickets->obtenerUrlTicket($ticket['ruta_archivo']); ?>" 
                                                   download="<?php echo $ticket['nombre_archivo']; ?>" 
                                                   class="btn btn-outline-success" 
                                                   title="Descargar">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <?php if ($esAdmin || $ticket['trabajador_id'] == $usuarioActual['trabajador_id']): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            onclick="eliminarTicket(<?php echo $ticket['id']; ?>)"
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtros y Navegación -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="btn-group">
                    <a href="ver_tickets.php" class="btn btn-outline-primary">
                        <i class="bi bi-receipt"></i> Mis Tickets
                    </a>
                    <?php if ($esAdmin): ?>
                        <a href="ver_tickets.php?todos=1" class="btn btn-outline-info">
                            <i class="bi bi-list-ul"></i> Todos los Tickets
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver al Sistema
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sistema de cambio de tema
        function cargarTemaGuardado() {
            const temaGuardado = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', temaGuardado);
            
            // Actualizar el icono del botón según el tema
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.innerHTML = temaGuardado === 'light' ? '<i class="bi bi-moon-fill"></i>' : '<i class="bi bi-sun-fill"></i>';
                themeToggle.title = temaGuardado === 'light' ? 'Cambiar a Tema Oscuro' : 'Cambiar a Tema Claro';
            }
        }
        
        // Configurar botón de tema
        document.addEventListener('DOMContentLoaded', function() {
            cargarTemaGuardado();
            
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    
                    document.documentElement.setAttribute('data-theme', newTheme);
                    this.innerHTML = newTheme === 'light' ? '<i class="bi bi-moon-fill"></i>' : '<i class="bi bi-sun-fill"></i>';
                    this.title = newTheme === 'light' ? 'Cambiar a Tema Oscuro' : 'Cambiar a Tema Claro';
                    localStorage.setItem('theme', newTheme);
                });
            }
        });
        
        function eliminarTicket(ticketId) {
            if (confirm('¿Estás seguro de que quieres eliminar este ticket? Esta acción no se puede deshacer.')) {
                // Aquí implementarías la lógica para eliminar el ticket
                fetch('eliminar_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar el ticket: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el ticket');
                });
            }
        }
    </script>
</body>
</html>
