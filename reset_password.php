<?php
session_start();
require_once 'vendor/autoload.php';

use InnovantCafe\Auth;
use InnovantCafe\Database;
use InnovantCafe\EmailService;

// Si ya está autenticado, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$step = 'request'; // request, verify, reset

// Procesar solicitud de restablecimiento
if ($_POST) {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'solicitar_reset':
                $email = $_POST['email'] ?? '';
                
                if (empty($email)) {
                    $error = 'Por favor, ingresa tu email';
                } else {
                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        // Verificar si el email existe
                        $stmt = $db->prepare("
                            SELECT t.id, t.nombre, t.email 
                            FROM trabajadores t 
                            WHERE t.email = :email AND t.activo = TRUE
                        ");
                        $stmt->execute(['email' => $email]);
                        $trabajador = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($trabajador) {
                            // Generar token único
                            $token = bin2hex(random_bytes(32));
                            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
                            
                            // Guardar token en base de datos
                            $stmt = $db->prepare("
                                INSERT INTO reset_tokens (trabajador_id, token, expira, usado)
                                VALUES (:trabajador_id, :token, :expira, FALSE)
                            ");
                            
                            if ($stmt->execute([
                                'trabajador_id' => $trabajador['id'],
                                'token' => $token,
                                'expira' => $expira
                            ])) {
                                // Enviar email real
                                $emailService = new EmailService();
                                
                                // Obtener URL base desde la configuración centralizada
                                $baseUrl = \InnovantCafe\Config::url('base');
                                
                                $resultadoEmail = $emailService->enviarEmailResetPassword(
                                    $trabajador['email'],
                                    $trabajador['nombre'],
                                    $token,
                                    $baseUrl
                                );
                                
                                if ($resultadoEmail['success']) {
                                    $success = 'Se ha enviado un enlace de restablecimiento a tu email. Revisa tu bandeja de entrada.';
                                } else {
                                    // Si falla el email, mostrar el token para pruebas
                                    $success = 'Token generado correctamente. Para pruebas, usa este enlace: ' . $baseUrl . '/reset-password?token=' . $token;
                                }
                                $step = 'request';
                            } else {
                                $error = 'Error al procesar la solicitud';
                            }
                        } else {
                            $error = 'No se encontró ningún trabajador con ese email';
                        }
                    } catch (Exception $e) {
                        $error = 'Error en el sistema: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'verificar_token':
                $token = $_POST['token'] ?? '';
                
                if (empty($token)) {
                    $error = 'Token inválido';
                } else {
                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        // Verificar token
                        $stmt = $db->prepare("
                            SELECT rt.*, t.nombre, t.email 
                            FROM reset_tokens rt
                            JOIN trabajadores t ON rt.trabajador_id = t.id
                            WHERE rt.token = :token 
                            AND rt.usado = FALSE 
                            AND rt.expira > NOW()
                        ");
                        $stmt->execute(['token' => $token]);
                        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($resetData) {
                            $step = 'reset';
                            $_SESSION['reset_token'] = $token;
                            $_SESSION['reset_trabajador_id'] = $resetData['trabajador_id'];
                        } else {
                            $error = 'Token inválido o expirado';
                        }
                    } catch (Exception $e) {
                        $error = 'Error en el sistema: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'cambiar_password':
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $token = $_SESSION['reset_token'] ?? '';
                $trabajadorId = $_SESSION['reset_trabajador_id'] ?? '';
                
                if (empty($password) || empty($confirmPassword)) {
                    $error = 'Por favor, completa todos los campos';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Las contraseñas no coinciden';
                } elseif (strlen($password) < 6) {
                    $error = 'La contraseña debe tener al menos 6 caracteres';
                } elseif (empty($token) || empty($trabajadorId)) {
                    $error = 'Sesión de restablecimiento inválida';
                } else {
                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        // Verificar que el token siga siendo válido
                        $stmt = $db->prepare("
                            SELECT * FROM reset_tokens 
                            WHERE token = :token 
                            AND trabajador_id = :trabajador_id
                            AND usado = FALSE 
                            AND expira > NOW()
                        ");
                        $stmt->execute([
                            'token' => $token,
                            'trabajador_id' => $trabajadorId
                        ]);
                        
                        if ($stmt->fetch()) {
                            // Buscar usuario asociado al trabajador
                            $stmt = $db->prepare("
                                SELECT id FROM usuarios 
                                WHERE trabajador_id = :trabajador_id AND activo = TRUE
                            ");
                            $stmt->execute(['trabajador_id' => $trabajadorId]);
                            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($usuario) {
                                // Actualizar contraseña
                                $auth = new Auth();
                                if ($auth->cambiarPassword($usuario['id'], $password)) {
                                    // Marcar token como usado
                                    $stmt = $db->prepare("
                                        UPDATE reset_tokens 
                                        SET usado = TRUE 
                                        WHERE token = :token
                                    ");
                                    $stmt->execute(['token' => $token]);
                                    
                                    // Limpiar sesión
                                    unset($_SESSION['reset_token']);
                                    unset($_SESSION['reset_trabajador_id']);
                                    
                                    $success = 'Contraseña cambiada exitosamente. Ya puedes iniciar sesión.';
                                    $step = 'request';
                                } else {
                                    $error = 'Error al cambiar la contraseña';
                                }
                            } else {
                                $error = 'No se encontró usuario asociado al trabajador';
                            }
                        } else {
                            $error = 'Token inválido o expirado';
                        }
                    } catch (Exception $e) {
                        $error = 'Error en el sistema: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Si hay un token en la URL, verificar automáticamente
if (isset($_GET['token']) && empty($_POST)) {
    $token = $_GET['token'];
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT rt.*, t.nombre, t.email 
            FROM reset_tokens rt
            JOIN trabajadores t ON rt.trabajador_id = t.id
            WHERE rt.token = :token 
            AND rt.usado = FALSE 
            AND rt.expira > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $resetData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resetData) {
            $step = 'reset';
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_trabajador_id'] = $resetData['trabajador_id'];
        } else {
            $error = 'Token inválido o expirado';
        }
    } catch (Exception $e) {
        $error = 'Error en el sistema: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema Innovant Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/modern-theme.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .reset-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .logo {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: white;
        }
        .step.active {
            background: #667eea;
        }
        .step.completed {
            background: #28a745;
        }
        .step.pending {
            background: #6c757d;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="logo">
                <i class="fas fa-key"></i>
            </div>
            <h4>Restablecer Contraseña</h4>
            <p class="subtitle mb-0">Sistema Innovant Café</p>
        </div>
        
        <div class="reset-body">
            <!-- Indicador de pasos -->
            <div class="step-indicator">
                <div class="step <?php echo $step === 'request' ? 'active' : ($step === 'reset' ? 'completed' : 'pending'); ?>">1</div>
                <div class="step <?php echo $step === 'reset' ? 'active' : ($step === 'request' ? 'pending' : 'completed'); ?>">2</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 'request'): ?>
                <!-- Paso 1: Solicitar restablecimiento -->
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="solicitar_reset">
                    
                    <div class="mb-4">
                        <h5 class="text-center mb-3">¿Olvidaste tu contraseña?</h5>
                        <p class="text-muted text-center">
                            Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña.
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email del Trabajador
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               required autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-reset w-100 mb-3">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Enlace
                    </button>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Login
                        </a>
                    </div>
                </form>
                
            <?php elseif ($step === 'reset'): ?>
                <!-- Paso 2: Cambiar contraseña -->
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="cambiar_password">
                    
                    <div class="mb-4">
                        <h5 class="text-center mb-3">Nueva Contraseña</h5>
                        <p class="text-muted text-center">
                            Ingresa tu nueva contraseña. Debe tener al menos 6 caracteres.
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Nueva Contraseña
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Confirmar Contraseña
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-reset w-100 mb-3">
                        <i class="fas fa-save me-2"></i>Cambiar Contraseña
                    </button>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Volver al Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-switcher.js"></script>
</body>
</html>
