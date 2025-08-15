<?php

namespace InnovantCafe;

use PDO;

class Producto
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Crea un nuevo producto
     */
    public function crear($nombre, $descripcion = null, $precioEstimado = null, $diasDuracion = null, $diasAviso = 7)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO productos (nombre, descripcion, precio_estimado, dias_duracion_estimada, dias_aviso_previo)
                VALUES (:nombre, :descripcion, :precio_estimado, :dias_duracion, :dias_aviso)
            ");
            
            return $stmt->execute([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio_estimado' => $precioEstimado,
                'dias_duracion' => $diasDuracion,
                'dias_aviso' => $diasAviso
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtiene todos los productos activos
     */
    public function obtenerTodos()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM productos 
            WHERE activo = TRUE 
            ORDER BY nombre
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene un producto por ID
     */
    public function obtenerPorId($id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM productos 
            WHERE id = :id AND activo = TRUE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Actualiza un producto
     */
    public function actualizar($id, $nombre, $descripcion = null, $precioEstimado = null, $diasDuracion = null, $diasAviso = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE productos 
                SET nombre = :nombre, 
                    descripcion = :descripcion, 
                    precio_estimado = :precio_estimado,
                    dias_duracion_estimada = :dias_duracion,
                    dias_aviso_previo = :dias_aviso
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio_estimado' => $precioEstimado,
                'dias_duracion' => $diasDuracion,
                'dias_aviso' => $diasAviso
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Elimina un producto (marca como inactivo)
     */
    public function eliminar($id)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE productos 
                SET activo = FALSE 
                WHERE id = :id
            ");
            
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtiene productos que necesitan compra pronto
     */
    public function obtenerProductosNecesitanCompra($dias = 7)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   c.fecha_compra as ultima_compra,
                   DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY) as fecha_estimada_fin,
                   DATE_SUB(DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY), INTERVAL p.dias_aviso_previo DAY) as fecha_aviso
            FROM productos p
            LEFT JOIN compras c ON p.id = c.producto_id
            WHERE p.activo = TRUE
            AND (
                c.id IS NULL OR 
                DATE_SUB(DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY), INTERVAL p.dias_aviso_previo DAY) <= DATE_ADD(CURRENT_DATE, INTERVAL :dias DAY)
            )
            ORDER BY fecha_aviso ASC
        ");
        
        $stmt->execute(['dias' => $dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene estadísticas de productos
     */
    public function obtenerEstadisticas()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_productos,
                AVG(dias_duracion_estimada) as duracion_promedio,
                AVG(dias_aviso_previo) as aviso_promedio,
                SUM(CASE WHEN precio_estimado IS NOT NULL THEN precio_estimado ELSE 0 END) as precio_total_estimado
            FROM productos 
            WHERE activo = TRUE
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca productos por nombre
     */
    public function buscarPorNombre($nombre)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM productos 
            WHERE nombre LIKE :nombre AND activo = TRUE
            ORDER BY nombre
        ");
        
        $stmt->execute(['nombre' => "%{$nombre}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
