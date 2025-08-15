<?php
require_once 'vendor/autoload.php';

use InnovantCafe\Database;
use InnovantCafe\Trabajador;
use InnovantCafe\Producto;
use InnovantCafe\SistemaRotacion;
use InnovantCafe\Reportes;
use InnovantCafe\Auth;
use InnovantCafe\Router;
use InnovantCafe\GestorTickets;

// Configuración básica
date_default_timezone_set('Europe/Madrid');
session_start();

// Inicializar Router
Router::init();

// Verificar autenticación
$auth = new Auth();
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario actual
$usuarioActual = $auth->getUsuarioActual();
$esAdmin = $auth->isAdmin();

// Inicializar clases
$trabajador = new Trabajador();
$producto = new Producto();
$sistemaRotacion = new SistemaRotacion();
$reportes = new Reportes();
$gestorTickets = new GestorTickets();

// Procesar formularios
$mensaje = '';
$tipoMensaje = '';

// Leer mensajes de la URL (para redirecciones POST/REDIRECT/GET)
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje = urldecode($_GET['mensaje']);
    $tipoMensaje = urldecode($_GET['tipo']);
}

// Solo procesar formularios si hay datos POST y no es una carga de página normal
if ($_POST && !empty($_POST['accion']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: ver qué se está enviando
    error_log("DEBUG: Procesando formulario POST - Acción: " . $_POST['accion']);
    
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'crear_trabajador':
                if (!$esAdmin) {
                    $mensaje = 'Solo los administradores pueden crear trabajadores';
                    $tipoMensaje = 'danger';
                } else {
                    if ($trabajador->crear($_POST['nombre'], $_POST['email'])) {
                        $mensaje = 'Trabajador creado correctamente';
                        $tipoMensaje = 'success';
                    } else {
                        $mensaje = 'Error al crear trabajador';
                        $tipoMensaje = 'danger';
                    }
                }
                break;
                
            case 'crear_producto':
                if (!$esAdmin) {
                    $mensaje = 'Solo los administradores pueden crear productos';
                    $tipoMensaje = 'danger';
                } else {
                    if ($producto->crear($_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['dias_duracion'], $_POST['dias_aviso'])) {
                        $mensaje = 'Producto creado correctamente';
                        $tipoMensaje = 'success';
                    } else {
                        $mensaje = 'Error al crear producto';
                        $tipoMensaje = 'danger';
                    }
                }
                break;
                
            case 'registrar_compra_completa':
                error_log("DEBUG: Registrando compra completa para trabajador: " . $_POST['trabajador_id']);
                $productosComprados = [];
                foreach ($_POST['productos'] as $productoId) {
                    $productosComprados[] = [
                        'producto_id' => $productoId,
                        'precio_real' => $_POST['precios'][$productoId] ?? null
                    ];
                }
                
                $resultado = $sistemaRotacion->registrarCompraCompleta(
                    $_POST['trabajador_id'],
                    $productosComprados,
                    $_POST['fecha_compra'],
                    $_POST['lugar_compra'],
                    $_POST['notas']
                );
                
                if ($resultado['success']) {
                    $mensaje = $resultado['mensaje'];
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = 'Error al registrar compra: ' . $resultado['error'];
                    $tipoMensaje = 'danger';
                }
                break;
                
            case 'registrar_compra':
                // Procesar compra manual individual
                $productosComprados = [
                    [
                        'producto_id' => $_POST['producto_id'],
                        'precio_real' => $_POST['precio_real'] ?? null
                    ]
                ];
                
                $resultado = $sistemaRotacion->registrarCompraCompleta(
                    $_POST['trabajador_id'],
                    $productosComprados,
                    $_POST['fecha_compra'],
                    $_POST['lugar_compra'] ?? '',
                    $_POST['notas'] ?? ''
                );
                
                if ($resultado['success']) {
                    $mensaje = $resultado['mensaje'];
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = 'Error al registrar compra: ' . $resultado['error'];
                    $tipoMensaje = 'danger';
                }
                break;
                
            case 'eliminar_trabajador':
                if (!$esAdmin) {
                    $mensaje = 'Solo los administradores pueden eliminar trabajadores';
                    $tipoMensaje = 'danger';
                } else {
                    if ($trabajador->eliminar($_POST['trabajador_id'])) {
                        $mensaje = 'Trabajador eliminado correctamente';
                        $tipoMensaje = 'success';
                    } else {
                        $mensaje = 'Error al eliminar trabajador';
                        $tipoMensaje = 'danger';
                    }
                }
                break;
                
            case 'eliminar_producto':
                if (!$esAdmin) {
                    $mensaje = 'Solo los administradores pueden eliminar productos';
                    $tipoMensaje = 'danger';
                } else {
                    if ($producto->eliminar($_POST['producto_id'])) {
                        $mensaje = 'Producto eliminado correctamente';
                        $tipoMensaje = 'success';
                    } else {
                        $mensaje = 'Error al eliminar producto';
                        $tipoMensaje = 'danger';
                    }
                }
                break;
                
            case 'actualizar_producto':
                if (!$esAdmin) {
                    $mensaje = 'Solo los administradores pueden actualizar productos';
                    $tipoMensaje = 'danger';
                } else {
                    if ($producto->actualizar(
                        $_POST['producto_id'],
                        $_POST['nombre'],
                        $_POST['descripcion'],
                        $_POST['precio'],
                        $_POST['dias_duracion'],
                        $_POST['dias_aviso']
                    )) {
                        $mensaje = 'Producto actualizado correctamente';
                        $tipoMensaje = 'success';
                    } else {
                        $mensaje = 'Error al actualizar producto';
                        $tipoMensaje = 'danger';
                    }
                }
                break;
                
            case 'actualizar_trabajador':
                if (!$esAdmin) {
                    $mensaje = 'Solo los administradores pueden actualizar trabajadores';
                    $tipoMensaje = 'danger';
                } else {
                    if ($trabajador->actualizar(
                        $_POST['trabajador_id'],
                        $_POST['nombre'],
                        $_POST['email'],
                        $_POST['orden_rotacion']
                    )) {
                        $mensaje = 'Trabajador actualizado correctamente';
                        $tipoMensaje = 'success';
                    } else {
                        $mensaje = 'Error al actualizar trabajador';
                        $tipoMensaje = 'danger';
                    }
                }
                
            case 'subir_ticket':
                // Verificar permisos: solo el trabajador que hizo la compra o un admin puede subir tickets
                $compraFecha = $_POST['compra_fecha'];
                $trabajadorId = $_POST['trabajador_id'];
                
                if (!$esAdmin && $trabajadorId != $usuarioActual['trabajador_id']) {
                    $mensaje = 'Solo puedes subir tickets de tus propias compras';
                    $tipoMensaje = 'danger';
                } else {
                    // Obtener las compras de esa transacción
                    $stmt = Database::getInstance()->getConnection()->prepare("
                        SELECT id FROM compras 
                        WHERE DATE(fecha_compra) = DATE(:fecha) 
                        AND trabajador_id = :trabajador_id
                    ");
                    $stmt->execute(['fecha' => $compraFecha, 'trabajador_id' => $trabajadorId]);
                    $compras = $stmt->fetchAll();
                    
                    if (empty($compras)) {
                        $mensaje = 'No se encontraron compras para esa transacción';
                        $tipoMensaje = 'danger';
                    } else {
                        // Subir ticket para cada compra de la transacción
                        $ticketsSubidos = 0;
                        foreach ($compras as $compra) {
                            if (isset($_FILES['archivo_ticket']) && $_FILES['archivo_ticket']['error'] === UPLOAD_ERR_OK) {
                                $resultado = $gestorTickets->subirTicket(
                                    $compra['id'],
                                    $usuarioActual['trabajador_id'],
                                    $_FILES['archivo_ticket'],
                                    $_POST['notas_ticket'] ?? null
                                );
                                
                                if ($resultado['success']) {
                                    $ticketsSubidos++;
                                }
                            }
                        }
                        
                        if ($ticketsSubidos > 0) {
                            $mensaje = "Ticket subido correctamente para $ticketsSubidos compra(s)";
                            $tipoMensaje = 'success';
                        } else {
                            $mensaje = 'Error al subir el ticket';
                            $tipoMensaje = 'danger';
                        }
                    }
                }
                break;
        }
    }
}

// Obtener datos para mostrar
$trabajadores = $trabajador->obtenerTodos();
$productos = $producto->obtenerTodos();
$resumenRotacion = $sistemaRotacion->obtenerResumenRotacion();
$avisosPendientes = $sistemaRotacion->obtenerAvisosPendientes();
$avisosPronto = $sistemaRotacion->obtenerAvisosVencenPronto(7);

// Obtener el próximo comprador
$proximoComprador = $sistemaRotacion->obtenerProximoComprador();
$productosParaComprar = null;
if ($proximoComprador) {
    $productosParaComprar = $sistemaRotacion->obtenerProductosParaTrabajador($proximoComprador['id']);
}

// Obtener compras recientes agrupadas por transacción
$stmt = Database::getInstance()->getConnection()->prepare("
    SELECT 
        MAX(c.fecha_compra) as fecha_compra,
        c.trabajador_id,
        MAX(t.nombre) as trabajador_nombre,
        COUNT(c.id) as total_productos,
        SUM(c.precio_real) as total_gastado,
        GROUP_CONCAT(p.nombre SEPARATOR ', ') as productos,
        MAX(c.ticket_subido) as tiene_ticket
    FROM compras c
    JOIN productos p ON c.producto_id = p.id
    JOIN trabajadores t ON c.trabajador_id = t.id
    GROUP BY DATE(c.fecha_compra), c.trabajador_id
    ORDER BY MAX(c.fecha_compra) DESC
    LIMIT 10
");
$stmt->execute();
$comprasRecientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovant Café - Sistema de Rotación</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    

    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px;
            padding: 30px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        
        .btn {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
        }
        
        .list-group-item {
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
        }
        
        .badge {
            border-radius: 20px;
            padding: 8px 12px;
        }
        
        /* Header profesional y limpio */
        .header-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 24px 32px;
            margin-bottom: 32px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        
        .header-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header-subtitle {
            color: #6c757d;
            font-weight: 400;
            font-size: 1.1rem;
            margin: 0;
        }
        
        .user-profile {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.08);
            min-width: 280px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 16px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .user-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .role-worker {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: 20px;
        }
        
        .theme-toggle {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 8px;
            color: #6c757d;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        
        .theme-toggle:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
            color: #495057;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        
        /* Responsive design para el header */
        @media (max-width: 768px) {
            .header-container {
                padding: 20px;
                margin-bottom: 24px;
            }
            
            .header-title {
                font-size: 2rem;
            }
            
            .user-profile {
                min-width: auto;
                padding: 16px;
            }
            
            .header-actions {
                margin-left: 12px;
                gap: 8px;
            }
            
            .theme-toggle,
            .logout-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
        
        .producto-urgente {
            border-left: 4px solid var(--danger-color);
            background-color: #fff5f5;
        }
        
        .producto-normal {
            border-left: 4px solid var(--warning-color);
            background-color: #fffbf0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header profesional y limpio -->
        <div class="header-container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-center flex-grow-1">
                    <h1 class="header-title">
                        <i class="bi bi-cup-hot"></i> Innovant Café
                    </h1>
                    <p class="header-subtitle">Sistema de Rotación Fija - Compra Completa por Turno</p>
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
                            <a href="/logout" class="logout-btn" title="Cerrar Sesión">
                                <i class="bi bi-box-arrow-right"></i> Salir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Mensaje de sistema limpio -->
        <?php if (empty($comprasRecientes)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle"></i> <strong>Sistema Limpio:</strong> No hay compras registradas. El sistema está listo para comenzar con la primera rotación de compras.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas principales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo count($trabajadores); ?></h3>
                    <p class="mb-0"><i class="bi bi-people"></i> Trabajadores</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo count($productos); ?></h3>
                    <p class="mb-0"><i class="bi bi-box"></i> Productos</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo count($avisosPendientes); ?></h3>
                    <p class="mb-0"><i class="bi bi-bell"></i> Avisos Pendientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo count($comprasRecientes); ?></h3>
                    <p class="mb-0"><i class="bi bi-cart-check"></i> Compras Recientes</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna izquierda -->
            <div class="col-lg-8">
                <!-- Próximo Turno de Compra -->
                <?php if ($proximoComprador && $productosParaComprar && !empty($productosParaComprar['todos'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-cart-plus"></i> 🎯 Próximo Turno: <?php echo htmlspecialchars($proximoComprador['nombre']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>💡 Nuevo Sistema:</strong> <?php echo htmlspecialchars($proximoComprador['nombre']); ?> debe comprar <strong>TODOS</strong> los productos que necesiten reposición en una sola salida.
                        </div>
                        
                        <?php if (!empty($productosParaComprar['urgentes'])): ?>
                        <div class="mb-3">
                            <h6 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Productos Urgentes (≤3 días)</h6>
                            <div class="list-group">
                                <?php foreach ($productosParaComprar['urgentes'] as $producto): ?>
                                    <div class="list-group-item producto-urgente">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($producto['producto_nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($producto['descripcion']); ?>
                                                    <?php if ($producto['precio_estimado']): ?>
                                                        • €<?php echo number_format($producto['precio_estimado'], 2); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-danger">¡Urgente!</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($productosParaComprar['normales'])): ?>
                        <div class="mb-3">
                            <h6 class="text-warning"><i class="bi bi-clock"></i> Productos Normales</h6>
                            <div class="list-group">
                                <?php foreach ($productosParaComprar['normales'] as $producto): ?>
                                    <div class="list-group-item producto-normal">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($producto['producto_nombre']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($producto['descripcion']); ?>
                                                    <?php if ($producto['precio_estimado']): ?>
                                                        • €<?php echo number_format($producto['precio_estimado'], 2); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-warning">Normal</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalCompraCompleta">
                                <i class="bi bi-cart-check"></i> Completar Compra Completa
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-check-circle"></i> 🎉 ¡Todo Abastecido!</h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="bi bi-emoji-smile" style="font-size: 3rem; color: var(--success-color);"></i>
                        <h5 class="mt-3">No hay productos que necesiten compra</h5>
                        <p class="text-muted">Todos los productos están bien abastecidos. ¡Excelente trabajo!</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Compras Recientes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Compras Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($comprasRecientes): ?>
                            <div class="list-group">
                                <?php foreach ($comprasRecientes as $compra): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <strong class="text-primary"><?php echo htmlspecialchars($compra['productos']); ?></strong>
                                                    <span class="badge bg-success">Completada</span>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <small class="text-muted">
                                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($compra['trabajador_nombre']); ?>
                                                            <i class="bi bi-calendar ms-2"></i> <?php echo date('d/m/Y', strtotime($compra['fecha_compra'])); ?>
                                                            <i class="bi bi-box ms-2"></i> <?php echo $compra['total_productos']; ?> producto<?php echo $compra['total_productos'] > 1 ? 's' : ''; ?>
                                                            <i class="bi bi-currency-euro ms-2"></i> €<?php echo number_format($compra['total_gastado'], 2); ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <?php if ($compra['tiene_ticket']): ?>
                                                            <button class="btn btn-outline-info btn-sm" 
                                                                    onclick="abrirModalVerTicket('<?php echo urlencode($compra['fecha_compra']); ?>', <?php echo $compra['trabajador_id']; ?>, '<?php echo htmlspecialchars($compra['trabajador_nombre']); ?>')"
                                                                    title="Ver Tickets">
                                                                <i class="bi bi-receipt"></i> Ver Ticket
                                                            </button>
                                                        <?php else: ?>
                                                            <?php if ($esAdmin || $compra['trabajador_id'] == $usuarioActual['trabajador_id']): ?>
                                                                <button class="btn btn-outline-success btn-sm" 
                                                                        onclick="abrirModalSubirTicket('<?php echo $compra['fecha_compra']; ?>', <?php echo $compra['trabajador_id']; ?>, '<?php echo htmlspecialchars($compra['trabajador_nombre']); ?>')"
                                                                        title="Subir Ticket">
                                                                    <i class="bi bi-upload"></i> Subir Ticket
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Sin Ticket</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No hay compras registradas</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna derecha -->
            <div class="col-lg-4">
                <!-- Resumen de Rotación -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Orden de Rotación</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($resumenRotacion as $trab): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo $trab['orden_rotacion']; ?>º <?php echo htmlspecialchars($trab['nombre']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $trab['total_compras']; ?> compras
                                            <?php if ($trab['total_gastado']): ?>
                                                • €<?php echo number_format($trab['total_gastado'], 2); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2"><?php echo $trab['orden_rotacion']; ?>º</span>
                                        <?php if ($esAdmin): ?>
                                            <button class="btn btn-outline-primary btn-sm me-1 btn-editar-trabajador" 
                                                    data-id="<?php echo $trab['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($trab['nombre']); ?>"
                                                    data-email="<?php echo htmlspecialchars($trab['email'] ?? ''); ?>"
                                                    data-orden="<?php echo $trab['orden_rotacion']; ?>"
                                                    title="Editar Trabajador">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($trab['id'] != $usuarioActual['trabajador_id']): ?>
                                                <button class="btn btn-outline-danger btn-sm btn-eliminar-trabajador" 
                                                        data-tipo="trabajador"
                                                        data-id="<?php echo $trab['id']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($trab['nombre']); ?>"
                                                        title="Eliminar Trabajador">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Avisos que Vencen Pronto -->
                <?php if ($avisosPronto): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> ¡Atención!</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($avisosPronto as $aviso): ?>
                                <div class="list-group-item list-group-item-warning">
                                    <strong><?php echo htmlspecialchars($aviso['producto_nombre']); ?></strong>
                                    <br>
                                    <small>
                                        <?php echo htmlspecialchars($aviso['trabajador_nombre']); ?> - 
                                        <?php echo $aviso['dias_restantes']; ?> días restantes
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acciones Rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($esAdmin): ?>
                            <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalTrabajador">
                                <i class="bi bi-person-plus"></i> Nuevo Trabajador
                            </button>
                            <button class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalProducto">
                                <i class="bi bi-box-seam"></i> Nuevo Producto
                            </button>
                            <button class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalAdmin">
                                <i class="bi bi-shield-exclamation"></i> Panel de Administración
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalCompraManual">
                            <i class="bi bi-cart-plus"></i> Registrar Compra Manual
                        </button>
                        <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#modalReportes">
                            <i class="bi bi-graph-up"></i> Ver Reportes y Estadísticas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar trabajador -->
    <div class="modal fade" id="modalTrabajador" tabindex="-1" onhidden="limpiarModalTrabajador()" style="z-index: 1065;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTrabajadorTitle">Nuevo Trabajador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="accion" id="accionTrabajador" value="crear_trabajador">
                        <input type="hidden" name="trabajador_id" id="trabajador_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre:</label>
                            <input type="text" name="nombre" id="trabajador_nombre" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="email" name="email" id="trabajador_email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Orden de Rotación:</label>
                            <input type="number" name="orden_rotacion" id="trabajador_orden" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnTrabajador">
                            <i class="bi bi-plus-circle"></i> Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar producto -->
    <div class="modal fade" id="modalProducto" tabindex="-1" onhidden="limpiarModalProducto()" style="z-index: 1065;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProductoTitle">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="accion" id="accionProducto" value="crear_producto">
                        <input type="hidden" name="producto_id" id="producto_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre:</label>
                            <input type="text" name="nombre" id="producto_nombre" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descripción:</label>
                            <textarea name="descripcion" id="producto_descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio Estimado:</label>
                                    <input type="number" name="precio" id="producto_precio" class="form-control" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duración (días):</label>
                                    <input type="number" name="dias_duracion" id="producto_duracion" class="form-control" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Días de Aviso Previo:</label>
                            <input type="number" name="dias_aviso" id="producto_aviso" class="form-control" min="1" value="7" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnProducto">
                            <i class="bi bi-plus-circle"></i> Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para compra completa -->
    <div class="modal fade" id="modalCompraCompleta" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cart-check"></i> Compra Completa - <?php echo htmlspecialchars($proximoComprador['nombre'] ?? ''); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data" id="formCompraCompleta">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="registrar_compra_completa">
                        <input type="hidden" name="trabajador_id" value="<?php echo $proximoComprador['id'] ?? ''; ?>">
                        
                        <div class="alert alert-info">
                            <strong>💡 Nuevo Sistema:</strong> <?php echo htmlspecialchars($proximoComprador['nombre'] ?? ''); ?> debe comprar <strong>TODOS</strong> los productos que necesiten reposición en una sola salida.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Compra:</label>
                                    <input type="date" name="fecha_compra" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Lugar de Compra:</label>
                                    <input type="text" name="lugar_compra" class="form-control" placeholder="Ej: Supermercado, Tienda...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notas:</label>
                            <textarea name="notas" class="form-control" rows="3" placeholder="Observaciones sobre la compra completa..."></textarea>
                        </div>
                        
                        <h6 class="mb-3">Productos a Comprar:</h6>
                        <?php if ($productosParaComprar && !empty($productosParaComprar['todos'])): ?>
                            <div class="row">
                                <?php foreach ($productosParaComprar['todos'] as $producto): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card <?php echo in_array($producto, $productosParaComprar['urgentes']) ? 'border-danger' : 'border-warning'; ?>">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="productos[]" value="<?php echo $producto['producto_id']; ?>" id="producto_<?php echo $producto['producto_id']; ?>" checked>
                                                    <label class="form-check-label" for="producto_<?php echo $producto['producto_id']; ?>">
                                                        <strong><?php echo htmlspecialchars($producto['producto_nombre']); ?></strong>
                                                    </label>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></small>
                                                <div class="mt-2">
                                                    <label class="form-label">Precio Real:</label>
                                                    <input type="number" name="precios[<?php echo $producto['producto_id']; ?>]" class="form-control form-control-sm" step="0.01" placeholder="0.00" value="<?php echo $producto['precio_estimado'] ?? ''; ?>">
                                                </div>
                                                <?php if (in_array($producto, $productosParaComprar['urgentes'])): ?>
                                                    <span class="badge bg-danger mt-2">¡Urgente!</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Ticket de Compra (opcional):</label>
                            <input type="file" name="ticket" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Formatos permitidos: JPG, PNG, PDF. Máximo 5MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cart-check"></i> Completar Compra Completa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para compra manual -->
    <div class="modal fade" id="modalCompraManual" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Compra Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data" id="formCompraManual">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="registrar_compra">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Producto:</label>
                                    <select name="producto_id" class="form-select" required>
                                        <option value="">Seleccionar producto...</option>
                                        <?php foreach ($productos as $prod): ?>
                                            <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Trabajador:</label>
                                    <select name="trabajador_id" class="form-select" required>
                                        <option value="">Seleccionar trabajador...</option>
                                        <?php foreach ($trabajadores as $trab): ?>
                                            <option value="<?php echo $trab['id']; ?>"><?php echo htmlspecialchars($trab['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Compra:</label>
                                    <input type="date" name="fecha_compra" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio Real:</label>
                                    <input type="number" name="precio_real" class="form-control" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lugar de Compra:</label>
                            <input type="text" name="lugar_compra" class="form-control" placeholder="Ej: Supermercado, Tienda...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notas:</label>
                            <textarea name="notas" class="form-control" rows="3" placeholder="Observaciones sobre la compra..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ticket de Compra (opcional):</label>
                            <input type="file" name="ticket" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Formatos permitidos: JPG, PNG, PDF. Máximo 5MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cart-plus"></i> Registrar Compra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Reportes y Estadísticas -->
    <div class="modal fade" id="modalReportes" tabindex="-1" style="z-index: 1065;">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-graph-up"></i> Reportes y Estadísticas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas de Reportes -->
                    <ul class="nav nav-tabs" id="reportesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab">
                                <i class="bi bi-pie-chart"></i> Resumen General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="gastos-tab" data-bs-toggle="tab" data-bs-target="#gastos" type="button" role="tab">
                                <i class="bi bi-cash-stack"></i> Gastos por Trabajador
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos" type="button" role="tab">
                                <i class="bi bi-box"></i> Gastos por Producto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="mensual-tab" data-bs-toggle="tab" data-bs-target="#mensual" type="button" role="tab">
                                <i class="bi bi-calendar-month"></i> Estadísticas Mensuales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rotacion-tab" data-bs-toggle="tab" data-bs-target="#rotacion" type="button" role="tab">
                                <i class="bi bi-arrow-repeat"></i> Historial de Rotación
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de las Pestañas -->
                    <div class="tab-content mt-3" id="reportesTabsContent">
                        <!-- Resumen General -->
                        <div class="tab-pane fade show active" id="resumen" role="tabpanel">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h3 class="text-primary"><?php echo count($trabajadores); ?></h3>
                                            <p class="mb-0">Trabajadores Activos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h3 class="text-success"><?php echo count($productos); ?></h3>
                                            <p class="mb-0">Productos Disponibles</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h3 class="text-info"><?php echo $sistemaRotacion->obtenerProximoComprador()['nombre'] ?? 'N/A'; ?></h3>
                                            <p class="mb-0">Próximo Comprador</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h3 class="text-warning"><?php echo count($sistemaRotacion->obtenerAvisosPendientes()); ?></h3>
                                            <p class="mb-0">Avisos Pendientes</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gastos por Trabajador -->
                        <div class="tab-pane fade" id="gastos" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Trabajador</th>
                                            <th>Total Gastado</th>
                                            <th>Compras Realizadas</th>
                                            <th>Última Compra</th>
                                            <th>Promedio por Compra</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trabajadores as $trab): ?>
                                            <?php 
                                            $gastosTrabajador = $reportes->obtenerGastosTrabajadorEspecifico($trab['id']);
                                            $totalGastado = array_sum(array_column($gastosTrabajador, 'precio_real'));
                                            $numCompras = count($gastosTrabajador);
                                            $promedio = $numCompras > 0 ? $totalGastado / $numCompras : 0;
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($trab['nombre']); ?></strong></td>
                                                <td class="text-success">€<?php echo number_format($totalGastado, 2); ?></td>
                                                <td><?php echo $numCompras; ?></td>
                                                <td><?php echo $numCompras > 0 ? date('d/m/Y', strtotime($gastosTrabajador[0]['fecha_compra'])) : 'N/A'; ?></td>
                                                <td>€<?php echo number_format($promedio, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Gastos por Producto -->
                        <div class="tab-pane fade" id="productos" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Precio Estimado</th>
                                            <th>Precio Real Promedio</th>
                                            <th>Diferencia</th>
                                            <th>Compras Realizadas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $prod): ?>
                                            <?php 
                                            $comprasProducto = $reportes->obtenerComprasPorProducto($prod['id']);
                                            $precioRealPromedio = 0;
                                            $diferencia = 0;
                                            if (!empty($comprasProducto)) {
                                                $precioRealPromedio = array_sum(array_column($comprasProducto, 'precio_real')) / count($comprasProducto);
                                                $diferencia = $precioRealPromedio - $prod['precio_estimado'];
                                            }
                                            ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
                                                <td>€<?php echo number_format($prod['precio_estimado'], 2); ?></td>
                                                <td class="text-info">€<?php echo number_format($precioRealPromedio, 2); ?></td>
                                                <td class="<?php echo $diferencia > 0 ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo $diferencia > 0 ? '+' : ''; ?>€<?php echo number_format($diferencia, 2); ?>
                                                </td>
                                                <td><?php echo count($comprasProducto); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Estadísticas Mensuales -->
                        <div class="tab-pane fade" id="mensual" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Gastos del Mes Actual</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="text-primary">€<?php echo number_format($reportes->obtenerGastosMensuales(date('Y'), date('m')), 2); ?></h4>
                                            <p class="mb-0">Total gastado en <?php 
                                                $meses = [
                                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                                ];
                                                echo $meses[date('n')] . ' ' . date('Y');
                                            ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Comparativa con Mes Anterior</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            <?php 
                                            $mesActual = $reportes->obtenerGastosMensuales(date('Y'), date('m'));
                                            $mesAnterior = $reportes->obtenerGastosMensuales(date('Y'), date('m') - 1);
                                            $diferencia = $mesActual - $mesAnterior;
                                            $porcentaje = $mesAnterior > 0 ? ($diferencia / $mesAnterior) * 100 : 0;
                                            ?>
                                            <h4 class="<?php echo $diferencia > 0 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $diferencia > 0 ? '+' : ''; ?>€<?php echo number_format($diferencia, 2); ?>
                                            </h4>
                                            <p class="mb-0"><?php echo $diferencia > 0 ? 'Incremento' : 'Decremento'; ?> del <?php echo number_format(abs($porcentaje), 1); ?>%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de Rotación -->
                        <div class="tab-pane fade" id="rotacion" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                                                                    <th>Fecha</th>
                                        <th>Trabajador</th>
                                        <th>Productos Comprados</th>
                                        <th>Total Gastado</th>
                                        <th>Lugar</th>
                                        <th>Ticket</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $historialCompras = $reportes->obtenerHistorialCompras();
                                        foreach (array_slice($historialCompras, 0, 10) as $compra): 
                                        ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($compra['fecha_compra'])); ?></td>
                                                <td><strong><?php echo htmlspecialchars($compra['trabajador_nombre']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($compra['productos_comprados']); ?></td>
                                                <td class="text-success">€<?php echo number_format($compra['total_gastado'], 2); ?></td>
                                                                                            <td><?php echo htmlspecialchars($compra['lugar_compra'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (isset($compra['ticket_subido']) && $compra['ticket_subido']): ?>
                                                    <a href="ver_tickets.php?compra_id=<?php echo $compra['id']; ?>" 
                                                       class="btn btn-success btn-sm" 
                                                       title="Ver Ticket">
                                                        <i class="bi bi-receipt"></i> Ver
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin Ticket</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            <button type="button" class="btn btn-primary" onclick="exportarReporte()">
                            <i class="bi bi-download"></i> Exportar Reporte
                        </button>
                        <a href="ver_tickets.php" class="btn btn-info">
                            <i class="bi bi-receipt"></i> Ver Tickets
                        </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Administración (solo para admins) -->
    <?php if ($esAdmin): ?>
    <div class="modal fade" id="modalAdmin" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-shield-exclamation"></i> Panel de Administración</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-people"></i> Gestión de Trabajadores</h6>
                            <div class="list-group mb-3">
                                <?php foreach ($trabajadores as $trab): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($trab['nombre']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($trab['email'] ?? 'Sin email'); ?>
                                                • <?php echo $trab['orden_rotacion']; ?>º en rotación
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-editar-trabajador" 
                                                    data-id="<?php echo $trab['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($trab['nombre']); ?>"
                                                    data-email="<?php echo htmlspecialchars($trab['email'] ?? ''); ?>"
                                                    data-orden="<?php echo $trab['orden_rotacion']; ?>"
                                                    title="Editar Trabajador">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($trab['id'] != $usuarioActual['trabajador_id']): ?>
                                                <button class="btn btn-outline-danger btn-eliminar-trabajador" 
                                                        data-tipo="trabajador"
                                                        data-id="<?php echo $trab['id']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($trab['nombre']); ?>"
                                                        title="Eliminar Trabajador">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6><i class="bi bi-box"></i> Gestión de Productos</h6>
                            <div class="list-group">
                                <?php foreach ($productos as $prod): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                €<?php echo number_format($prod['precio_estimado'], 2); ?>
                                                • <?php echo $prod['dias_duracion_estimada']; ?> días duración
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-editar-producto" 
                                                    data-id="<?php echo $prod['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                                    data-descripcion="<?php echo htmlspecialchars($prod['descripcion'] ?? ''); ?>"
                                                    data-precio="<?php echo $prod['precio_estimado'] ?? 0; ?>"
                                                    data-duracion="<?php echo $prod['dias_duracion_estimada'] ?? 0; ?>"
                                                    data-aviso="<?php echo $prod['dias_aviso_previo'] ?? 7; ?>"
                                                    title="Editar Producto">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-eliminar-producto" 
                                                    data-tipo="producto"
                                                    data-id="<?php echo $prod['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>"
                                                    title="Eliminar Producto">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal para ver ticket -->
    <div class="modal fade" id="modalVerTicket" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerTicketTitle">Ver Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                                            <div class="mb-3">
                            <label class="form-label">Transacción:</label>
                            <div class="alert alert-info">
                                <strong id="ver_ticket_transaccion_info" data-trabajador-id="">Cargando...</strong>
                            </div>
                        </div>
                    
                    <div id="ticket_content">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando ticket...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para subir ticket -->
    <div class="modal fade" id="modalSubirTicket" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSubirTicketTitle">Subir Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data" id="formSubirTicket">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="subir_ticket">
                        <input type="hidden" name="compra_fecha" id="ticket_compra_fecha">
                        <input type="hidden" name="trabajador_id" id="ticket_trabajador_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Transacción:</label>
                            <div class="alert alert-info">
                                <strong id="ticket_transaccion_info">Cargando...</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Archivo del Ticket:</label>
                            <input type="file" name="archivo_ticket" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.pdf" required>
                            <small class="text-muted">
                                Formatos permitidos: JPG, PNG, PDF. Máximo 5MB.
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notas (opcional):</label>
                            <textarea name="notas_ticket" class="form-control" rows="3" 
                                      placeholder="Agregar notas sobre el ticket..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btnSubirTicket">
                            <i class="bi bi-upload"></i> Subir Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FUNCIONES PRINCIPALES
        function confirmarEliminacion(tipo, id, nombre) {
            if (confirm(`¿Estás seguro de que quieres eliminar ${tipo} "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar_${tipo}">
                    <input type="hidden" name="${tipo}_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function editarTrabajador(id, nombre, email, ordenRotacion) {
            console.log(`✏️ Editando trabajador: ${nombre} (ID: ${id})`);
            
            // Configurar modal para editar
            document.getElementById('trabajador_id').value = id;
            document.getElementById('trabajador_nombre').value = nombre;
            document.getElementById('trabajador_email').value = email || '';
            document.getElementById('trabajador_orden').value = ordenRotacion;
            document.getElementById('accionTrabajador').value = 'editar_trabajador';
            document.getElementById('modalTrabajadorTitle').textContent = 'Editar Trabajador';
            document.getElementById('btnTrabajador').innerHTML = '<i class="bi bi-check-circle"></i> Actualizar';
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalTrabajador'));
            modal.show();
        }
        
        function editarProducto(id, nombre, descripcion, precio, duracion, aviso) {
            console.log(`✏️ Editando producto: ${nombre} (ID: ${id})`);
            
            // Configurar modal para editar
            document.getElementById('producto_id').value = id;
            document.getElementById('producto_nombre').value = nombre;
            document.getElementById('producto_descripcion').value = descripcion || '';
            document.getElementById('producto_precio').value = precio || '';
            document.getElementById('producto_duracion').value = duracion;
            document.getElementById('producto_aviso').value = aviso;
            document.getElementById('accionProducto').value = 'editar_producto';
            document.getElementById('modalProductoTitle').textContent = 'Editar Producto';
            document.getElementById('btnProducto').innerHTML = '<i class="bi bi-check-circle"></i> Actualizar';
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalProducto'));
            modal.show();
        }
        
        function limpiarModalTrabajador() {
            document.getElementById('trabajador_id').value = '';
            document.getElementById('trabajador_nombre').value = '';
            document.getElementById('trabajador_email').value = '';
            document.getElementById('trabajador_orden').value = '';
            document.getElementById('accionTrabajador').value = 'crear_trabajador';
            document.getElementById('modalTrabajadorTitle').textContent = 'Nuevo Trabajador';
            document.getElementById('btnTrabajador').innerHTML = '<i class="bi bi-plus-circle"></i> Crear';
        }
        
        function limpiarModalProducto() {
            document.getElementById('producto_id').value = '';
            document.getElementById('producto_nombre').value = '';
            document.getElementById('producto_descripcion').value = '';
            document.getElementById('producto_precio').value = '';
            document.getElementById('producto_duracion').value = '';
            document.getElementById('producto_aviso').value = '7';
            document.getElementById('accionProducto').value = 'crear_producto';
            document.getElementById('modalProductoTitle').textContent = 'Nuevo Producto';
            document.getElementById('btnProducto').innerHTML = '<i class="bi bi-plus-circle"></i> Crear';
        }
        
        function abrirModalSubirTicket(fecha, trabajadorId, trabajadorNombre) {
            console.log(`📤 Abriendo modal para subir ticket: ${fecha} - ${trabajadorNombre}`);
            
            // Configurar campos del modal
            document.getElementById('ticket_compra_fecha').value = fecha;
            document.getElementById('ticket_trabajador_id').value = trabajadorId;
            
            // Mostrar información de la transacción
            const fechaFormateada = new Date(fecha).toLocaleDateString('es-ES');
            document.getElementById('ticket_transaccion_info').textContent = 
                `${trabajadorNombre} - ${fechaFormateada}`;
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalSubirTicket'));
            modal.show();
        }
        
        function abrirModalVerTicket(fecha, trabajadorId, trabajadorNombre) {
            console.log(`👁️ Abriendo modal para ver ticket: ${fecha} - ${trabajadorNombre}`);
            
            // Configurar información de la transacción
            const fechaFormateada = new Date(fecha).toLocaleDateString('es-ES');
            const elementoInfo = document.getElementById('ver_ticket_transaccion_info');
            elementoInfo.textContent = `${trabajadorNombre} - ${fechaFormateada}`;
            elementoInfo.dataset.trabajadorId = trabajadorId;
            
            // Mostrar spinner de carga
            document.getElementById('ticket_content').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando ticket...</p>
                </div>
            `;
            
            // Abrir modal
            const modal = new bootstrap.Modal(document.getElementById('modalVerTicket'));
            modal.show();
            
            // Cargar contenido del ticket
            cargarTicketTransaccion(fecha, trabajadorId);
        }
        
        function cargarTicketTransaccion(fecha, trabajadorId, indice = 0) {
            // Hacer petición AJAX para obtener los tickets
            fetch(`obtener_tickets_ajax.php?fecha=${encodeURIComponent(fecha)}&trabajador_id=${trabajadorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (indice >= 0 && indice < data.tickets.length) {
                            mostrarContenidoTicket(data.tickets[indice]);
                            if (data.tickets.length > 1) {
                                mostrarNavegacionTickets(data.tickets, indice);
                            }
                        } else {
                            mostrarTicketsEnModal(data.tickets);
                        }
                    } else {
                        mostrarErrorEnModal(data.message || 'Error al cargar los tickets');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar tickets:', error);
                    mostrarErrorEnModal('Error de conexión al cargar los tickets');
                });
        }
        
        function mostrarTicketsEnModal(tickets) {
            if (!tickets || tickets.length === 0) {
                document.getElementById('ticket_content').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        No se encontraron tickets para esta transacción.
                    </div>
                `;
                return;
            }
            
            // Si hay múltiples tickets, mostrar el primero por defecto
            const primerTicket = tickets[0];
            mostrarContenidoTicket(primerTicket);
            
            // Si hay más de un ticket, mostrar navegación
            if (tickets.length > 1) {
                mostrarNavegacionTickets(tickets, 0);
            }
        }
        
        function mostrarContenidoTicket(ticket) {
            const icono = obtenerIconoTipoArchivo(ticket.tipo_archivo);
            const fecha = new Date(ticket.fecha_subida).toLocaleDateString('es-ES');
            const tamano = (ticket.tamano_archivo / 1024).toFixed(1);
            
            let contenido = '';
            
            if (ticket.tipo_archivo.startsWith('image/')) {
                // Mostrar imagen directamente
                const rutaImagen = ticket.ruta_archivo.startsWith('/') ? ticket.ruta_archivo : '/' + ticket.ruta_archivo;
                contenido = `
                    <div class="text-center mb-3">
                        <img src="${rutaImagen}" class="img-fluid" style="max-height: 400px; max-width: 100%; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" alt="${ticket.nombre_archivo}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="alert alert-warning" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            No se pudo cargar la imagen. Verifica que el archivo exista.
                        </div>
                    </div>
                `;
            } else if (ticket.tipo_archivo === 'application/pdf') {
                // Mostrar PDF embebido
                const rutaPDF = ticket.ruta_archivo.startsWith('/') ? ticket.ruta_archivo : '/' + ticket.ruta_archivo;
                contenido = `
                    <div class="text-center mb-3">
                        <iframe src="${rutaPDF}" width="100%" height="500" style="border: none; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"></iframe>
                        <div class="alert alert-warning" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i>
                            No se pudo cargar el PDF. <a href="${rutaPDF}" target="_blank">Abrir en nueva pestaña</a>
                        </div>
                    </div>
                `;
            } else {
                // Mostrar información del archivo
                contenido = `
                    <div class="alert alert-info">
                        <i class="bi ${icono} fs-4 me-3"></i>
                        <strong>${ticket.nombre_archivo}</strong>
                        <br>
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> ${fecha} | 
                            <i class="bi bi-file-earmark"></i> ${tamano} KB
                        </small>
                    </div>
                `;
            }
            
            // Agregar información del ticket
            contenido += `
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi ${icono} me-2"></i>
                            ${ticket.nombre_archivo}
                        </h6>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> ${fecha} | 
                                <i class="bi bi-file-earmark"></i> ${tamano} KB
                            </small>
                        </p>
                        ${ticket.descripcion ? `<p class="card-text">${ticket.descripcion}</p>` : ''}
                        <button class="btn btn-outline-primary btn-sm" onclick="descargarTicket('${ticket.ruta_archivo}', '${ticket.nombre_archivo}')">
                            <i class="bi bi-download"></i> Descargar
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('ticket_content').innerHTML = contenido;
        }
        
        function mostrarNavegacionTickets(tickets, indiceActual) {
            const navegacion = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="cambiarTicket(${indiceActual - 1}, ${tickets.length})" ${indiceActual === 0 ? 'disabled' : ''}>
                        <i class="bi bi-chevron-left"></i> Anterior
                    </button>
                    <span class="text-muted">${indiceActual + 1} de ${tickets.length}</span>
                    <button class="btn btn-outline-secondary btn-sm" onclick="cambiarTicket(${indiceActual + 1}, ${tickets.length})" ${indiceActual === tickets.length - 1 ? 'disabled' : ''}>
                        Siguiente <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            `;
            
            document.getElementById('ticket_content').insertAdjacentHTML('afterbegin', navegacion);
        }
        
        function cambiarTicket(nuevoIndice, totalTickets) {
            if (nuevoIndice < 0 || nuevoIndice >= totalTickets) return;
            
            // Recargar el ticket específico
            const fecha = document.getElementById('ver_ticket_transaccion_info').textContent.split(' - ')[1];
            const trabajadorId = document.getElementById('ver_ticket_transaccion_info').dataset.trabajadorId;
            
            cargarTicketTransaccion(fecha, trabajadorId, nuevoIndice);
        }
        
        function mostrarErrorEnModal(mensaje) {
            document.getElementById('ticket_content').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    ${mensaje}
                </div>
            `;
        }
        
        function obtenerIconoTipoArchivo(tipoArchivo) {
            switch (tipoArchivo) {
                case 'image/jpeg':
                case 'image/jpg':
                case 'image/png':
                    return 'bi-image';
                case 'application/pdf':
                    return 'bi-file-pdf';
                default:
                    return 'bi-file';
            }
        }
        
        function descargarTicket(rutaArchivo, nombreArchivo) {
            // Crear enlace temporal para descarga
            const link = document.createElement('a');
            
            // Asegurar que la ruta sea relativa al servidor web
            if (rutaArchivo.startsWith('/')) {
                link.href = rutaArchivo;
            } else {
                link.href = '/' + rutaArchivo;
            }
            
            link.download = nombreArchivo;
            link.target = '_blank';
            
            // Agregar el enlace al DOM
            document.body.appendChild(link);
            
            // Hacer clic en el enlace
            link.click();
            
            // Limpiar el enlace del DOM
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);
        }
        
        // CONFIGURACIÓN UNIFICADA - Todo en un solo DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 DOM cargado, configurando funcionalidades...');
            
            // Verificar que los botones existan en el DOM
            console.log('🔍 Verificando botones en el DOM:');
            console.log(`- Botones editar trabajador: ${document.querySelectorAll('.btn-editar-trabajador').length}`);
            console.log(`- Botones editar producto: ${document.querySelectorAll('.btn-editar-producto').length}`);
            console.log(`- Botones eliminar trabajador: ${document.querySelectorAll('.btn-eliminar-trabajador').length}`);
            console.log(`- Botones eliminar producto: ${document.querySelectorAll('.btn-eliminar-producto').length}`);
            
            // Cargar tema guardado al iniciar la página
            function cargarTemaGuardado() {
                const temaGuardado = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', temaGuardado);
                
                // Actualizar el icono del botón según el tema
                const themeToggle = document.getElementById('themeToggle');
                if (themeToggle) {
                    themeToggle.innerHTML = temaGuardado === 'light' ? '<i class="bi bi-moon-fill"></i>' : '<i class="bi bi-sun-fill"></i>';
                    themeToggle.title = temaGuardado === 'light' ? 'Cambiar a Tema Oscuro' : 'Cambiar a Tema Claro';
                }
                
                console.log('✅ Tema cargado:', temaGuardado);
            }
            
            // Ejecutar al cargar la página
            cargarTemaGuardado();
            
            // Configurar botón de tema
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                console.log('✅ Botón de tema encontrado');
                themeToggle.addEventListener('click', function() {
                    console.log('🖱️ Botón de tema clickeado');
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    
                    document.documentElement.setAttribute('data-theme', newTheme);
                    this.innerHTML = newTheme === 'light' ? '<i class="bi bi-moon-fill"></i>' : '<i class="bi bi-sun-fill"></i>';
                    this.title = newTheme === 'light' ? 'Cambiar a Tema Oscuro' : 'Cambiar a Tema Claro';
                    localStorage.setItem('theme', newTheme);
                    console.log('✅ Tema cambiado a:', newTheme);
                });
            }
            
            // Configurar botones de editar trabajador
            const botonesEditarTrabajador = document.querySelectorAll('.btn-editar-trabajador');
            console.log(`🔧 Encontrados ${botonesEditarTrabajador.length} botones editar trabajador`);
            
            botonesEditarTrabajador.forEach((btn, index) => {
                console.log(`🔧 Configurando botón editar trabajador ${index + 1}:`, btn);
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón editar trabajador clickeado');
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    const email = this.dataset.email;
                    const orden = this.dataset.orden;
                    
                    console.log('📊 Datos del trabajador:', {id, nombre, email, orden});
                    editarTrabajador(id, nombre, email, orden);
                });
                console.log(`✅ Botón editar trabajador ${index + 1} configurado correctamente`);
            });
            
            // Configurar botones de editar producto
            const botonesEditarProducto = document.querySelectorAll('.btn-editar-producto');
            console.log(`🔧 Encontrados ${botonesEditarProducto.length} botones editar producto`);
            
            botonesEditarProducto.forEach((btn, index) => {
                console.log(`🔧 Configurando botón editar producto ${index + 1}:`, btn);
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón editar producto clickeado');
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    const descripcion = this.dataset.descripcion;
                    const precio = this.dataset.precio;
                    const duracion = this.dataset.duracion;
                    const aviso = this.dataset.aviso;
                    
                    console.log('📊 Datos del producto:', {id, nombre, descripcion, precio, duracion, aviso});
                    editarProducto(id, nombre, descripcion, precio, duracion, aviso);
                });
                console.log(`✅ Botón editar producto ${index + 1} configurado correctamente`);
            });
            
            // Configurar botones de eliminar trabajador
            const botonesEliminarTrabajador = document.querySelectorAll('.btn-eliminar-trabajador');
            console.log(`🔧 Encontrados ${botonesEliminarTrabajador.length} botones eliminar trabajador`);
            
            botonesEliminarTrabajador.forEach((btn, index) => {
                console.log(`🔧 Configurando botón eliminar trabajador ${index + 1}:`, btn);
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón eliminar trabajador clickeado');
                    const tipo = this.dataset.tipo;
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    
                    console.log('📊 Datos para eliminar:', {tipo, id, nombre});
                    confirmarEliminacion(tipo, id, nombre);
                });
                console.log(`✅ Botón eliminar trabajador ${index + 1} configurado correctamente`);
            });
            
            // Configurar botones de eliminar producto
            const botonesEliminarProducto = document.querySelectorAll('.btn-eliminar-producto');
            console.log(`🔧 Encontrados ${botonesEliminarProducto.length} botones eliminar producto`);
            
            botonesEliminarProducto.forEach((btn, index) => {
                console.log(`🔧 Configurando botón eliminar producto ${index + 1}:`, btn);
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🖱️ Botón eliminar producto clickeado');
                    const tipo = this.dataset.tipo;
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    
                    console.log('📊 Datos para eliminar:', {tipo, id, nombre});
                    confirmarEliminacion(tipo, id, nombre);
                });
                console.log(`✅ Botón eliminar producto ${index + 1} configurado correctamente`);
            });
            
            // Configurar validación de formularios (simplificada)
            const formularios = document.querySelectorAll('form[method="post"]');
            formularios.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const accion = form.querySelector('input[name="accion"]');
                    if (!accion || !accion.value) {
                        console.log('Formulario sin acción definida, permitiendo envío normal');
                        return true;
                    }
                });
            });
            
            console.log('✅ Todas las funcionalidades configuradas correctamente');
            console.log('📊 RESUMEN DE CONFIGURACIÓN:');
            console.log(`- Botones editar trabajador: ${botonesEditarTrabajador.length}`);
            console.log(`- Botones editar producto: ${botonesEditarProducto.length}`);
            console.log(`- Botones eliminar trabajador: ${botonesEliminarTrabajador.length}`);
            console.log(`- Botones eliminar producto: ${botonesEliminarProducto.length}`);
            console.log('🎯 Sistema listo para usar');
        });
        
        // Log inicial
        console.log('📱 Página cargada, JavaScript iniciado');
    </script>
</body>
</html>
