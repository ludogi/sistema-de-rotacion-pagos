<?php

namespace InnovantCafe;

use PDO;

class Trabajador
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea un nuevo trabajador
     */
    public function crear($nombre, $email = null, $ordenRotacion = null)
    {
        try {
            // Validar que el email no esté duplicado si se proporciona
            if ($email !== null) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM trabajadores WHERE email = :email AND activo = TRUE");
                $stmt->execute(['email' => $email]);
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($resultado['total'] > 0) {
                    throw new \Exception("El email '{$email}' ya está registrado para otro trabajador");
                }
            }
            
            // Si no se especifica orden, asignar el siguiente disponible
            if ($ordenRotacion === null) {
                $stmt = $this->db->prepare("SELECT COALESCE(MAX(orden_rotacion), 0) + 1 as siguiente FROM trabajadores");
                $stmt->execute();
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $ordenRotacion = $resultado['siguiente'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO trabajadores (nombre, email, orden_rotacion)
                VALUES (:nombre, :email, :orden_rotacion)
            ");
            
            return $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'orden_rotacion' => $ordenRotacion
            ]);
        } catch (\Exception $e) {
            // Log del error para debugging
            error_log("Error al crear trabajador: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todos los trabajadores activos ordenados por rotación
     */
    public function obtenerTodos()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM trabajadores 
            WHERE activo = TRUE 
            ORDER BY orden_rotacion
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene un trabajador por ID
     */
    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM trabajadores 
            WHERE id = :id AND activo = TRUE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene un trabajador por orden de rotación
     */
    public function obtenerPorOrdenRotacion($orden)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM trabajadores 
            WHERE orden_rotacion = :orden AND activo = TRUE
        ");
        $stmt->execute(['orden' => $orden]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el siguiente trabajador en la rotación
     */
    public function obtenerSiguiente($ordenActual)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM trabajadores 
            WHERE orden_rotacion > :orden_actual AND activo = TRUE
            ORDER BY orden_rotacion ASC
            LIMIT 1
        ");
        $stmt->execute(['orden_actual' => $ordenActual]);
        $siguiente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay siguiente, volver al primero
        if (!$siguiente) {
            $stmt = $this->db->prepare("
                SELECT * FROM trabajadores 
                WHERE activo = TRUE
                ORDER BY orden_rotacion ASC
                LIMIT 1
            ");
            $stmt->execute();
            $siguiente = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $siguiente;
    }
    
    /**
     * Actualiza un trabajador
     */
    public function actualizar($id, $nombre, $email = null, $ordenRotacion = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE trabajadores 
                SET nombre = :nombre, 
                    email = :email,
                    orden_rotacion = :orden_rotacion
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'email' => $email,
                'orden_rotacion' => $ordenRotacion
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Elimina un trabajador (marca como inactivo)
     */
    public function eliminar($id)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE trabajadores 
                SET activo = FALSE 
                WHERE id = :id
            ");
            
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtiene estadísticas de compras del trabajador
     */
    public function obtenerEstadisticasCompras($trabajadorId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_compras,
                SUM(CASE WHEN precio_real IS NOT NULL THEN precio_real ELSE 0 END) as total_gastado,
                AVG(CASE WHEN precio_real IS NOT NULL THEN precio_real ELSE NULL END) as precio_promedio,
                MIN(fecha_compra) as primera_compra,
                MAX(fecha_compra) as ultima_compra
            FROM compras 
            WHERE trabajador_id = :trabajador_id
        ");
        
        $stmt->execute(['trabajador_id' => $trabajadorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el historial de compras del trabajador
     */
    public function obtenerHistorialCompras($trabajadorId, $limite = 10)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, p.nombre as producto_nombre, p.descripcion
            FROM compras c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.trabajador_id = :trabajador_id
            ORDER BY c.fecha_compra DESC
            LIMIT :limite
        ");
        
        $stmt->bindValue(':trabajador_id', $trabajadorId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene trabajadores con más compras
     */
    public function obtenerTopCompradores($limite = 5)
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.nombre,
                t.orden_rotacion,
                COUNT(c.id) as total_compras,
                SUM(CASE WHEN c.precio_real IS NOT NULL THEN c.precio_real ELSE 0 END) as total_gastado
            FROM trabajadores t
            LEFT JOIN compras c ON t.id = c.trabajador_id
            WHERE t.activo = TRUE
            GROUP BY t.id, t.nombre, t.orden_rotacion
            ORDER BY total_compras DESC, total_gastado DESC
            LIMIT :limite
        ");
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Reordena la rotación de trabajadores
     */
    public function reordenarRotacion($nuevoOrden)
    {
        try {
            $this->db->beginTransaction();
            
            foreach ($nuevoOrden as $orden => $trabajadorId) {
                $stmt = $this->db->prepare("
                    UPDATE trabajadores 
                    SET orden_rotacion = :orden 
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    'orden' => $orden + 1,
                    'id' => $trabajadorId
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
