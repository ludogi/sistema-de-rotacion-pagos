<?php

namespace InnovantCafe;

use PDO;

class Auth
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Autentica un usuario
     */
    public function login($username, $password)
    {
        $stmt = $this->db->prepare("
            SELECT u.*, t.nombre as trabajador_nombre, t.email as trabajador_email
            FROM usuarios u
            LEFT JOIN trabajadores t ON u.trabajador_id = t.id
            WHERE u.username = :username AND u.activo = TRUE
        ");
        
        $stmt->execute(['username' => $username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Actualizar último login
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET ultimo_login = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $usuario['id']]);
            
            // Crear sesión
            $this->crearSesion($usuario);
            return true;
        }
        
        return false;
    }
    
    /**
     * Crea la sesión del usuario
     */
    private function crearSesion($usuario)
    {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['trabajador_id'] = $usuario['trabajador_id'];
        $_SESSION['trabajador_nombre'] = $usuario['trabajador_nombre'];
        $_SESSION['trabajador_email'] = $usuario['trabajador_email'];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Verifica si el usuario está autenticado
     */
    public function isAuthenticated()
    {
        return isset($_SESSION['usuario_id']);
    }
    
    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole($rol)
    {
        return $this->isAuthenticated() && $_SESSION['rol'] === $rol;
    }
    
    /**
     * Verifica si el usuario es admin
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
    
    /**
     * Obtiene el ID del trabajador actual
     */
    public function getTrabajadorId()
    {
        return $_SESSION['trabajador_id'] ?? null;
    }
    
    /**
     * Obtiene el nombre del trabajador actual
     */
    public function getTrabajadorNombre()
    {
        return $_SESSION['trabajador_nombre'] ?? 'Usuario';
    }
    
    /**
     * Cierra la sesión
     */
    public function logout()
    {
        session_destroy();
        return true;
    }
    
    /**
     * Crea un nuevo usuario
     */
    public function crearUsuario($trabajadorId, $username, $password, $rol = 'trabajador')
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (trabajador_id, username, password, rol)
            VALUES (:trabajador_id, :username, :password, :rol)
        ");
        
        return $stmt->execute([
            'trabajador_id' => $trabajadorId,
            'username' => $username,
            'password' => $hashedPassword,
            'rol' => $rol
        ]);
    }
    
    /**
     * Cambia la contraseña de un usuario
     */
    public function cambiarPassword($usuarioId, $nuevaPassword)
    {
        $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET password = :password, fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $usuarioId,
            'password' => $hashedPassword
        ]);
    }
    
    /**
     * Obtiene información del usuario actual
     */
    public function getUsuarioActual()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['usuario_id'],
            'username' => $_SESSION['username'],
            'rol' => $_SESSION['rol'],
            'trabajador_id' => $_SESSION['trabajador_id'],
            'trabajador_nombre' => $_SESSION['trabajador_nombre'],
            'trabajador_email' => $_SESSION['trabajador_email']
        ];
    }
}
