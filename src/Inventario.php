<?php

namespace InnovantCafe;

use PDO;

class Inventario
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Actualiza el stock de un producto
     */
    public function actualizarStock($productoId, $cantidad, $trabajadorId = null, $motivo = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Actualizar inventario
            $stmt = $this->db->prepare("
                INSERT INTO inventario (producto_id, cantidad) 
                VALUES (:producto_id, :cantidad)
                ON DUPLICATE KEY UPDATE 
                cantidad = :cantidad,
                fecha_ultima_actualizacion = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                'producto_id' => $productoId,
                'cantidad' => $cantidad
            ]);
            
            // Actualizar estado del producto
            $estado = $cantidad > 0 ? 'disponible' : 'agotado';
            $stmt = $this->db->prepare("
                UPDATE productos 
                SET estado = :estado, cantidad_actual = :cantidad
                WHERE id = :id
            ");
            
            $stmt->execute([
                'estado' => $estado,
                'cantidad' => $cantidad,
                'id' => $productoId
            ]);
            
            // Si se consume producto, registrar el consumo
            if ($trabajadorId && $motivo) {
                $stmt = $this->db->prepare("
                    INSERT INTO consumo_productos (producto_id, cantidad_consumida, trabajador_responsable, motivo, fecha_consumo)
                    VALUES (:producto_id, 1, :trabajador_id, :motivo, CURRENT_DATE)
                ");
                
                $stmt->execute([
                    'producto_id' => $productoId,
                    'trabajador_id' => $trabajadorId,
                    'motivo' => $motivo
                ]);
            }
            
            // Si el producto se agotó, crear asignación de compra
            if ($cantidad <= 0) {
                $this->crearAsignacionCompra($productoId);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Crea una asignación de compra cuando un producto se agota
     */
    private function crearAsignacionCompra($productoId)
    {
        // Obtener el trabajador que menos ha comprado este producto
        $trabajadorId = $this->seleccionarTrabajadorCompra($productoId);
        
        if ($trabajadorId) {
            $stmt = $this->db->prepare("
                INSERT INTO asignaciones_compra (producto_id, trabajador_id, estado, fecha_limite)
                VALUES (:producto_id, :trabajador_id, 'pendiente', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY))
            ");
            
            $stmt->execute([
                'producto_id' => $productoId,
                'trabajador_id' => $trabajadorId
            ]);
            
            // Actualizar estado del producto
            $stmt = $this->db->prepare("
                UPDATE productos 
                SET estado = 'pendiente_compra'
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $productoId]);
        }
    }
    
    /**
     * Selecciona el trabajador más apropiado para comprar un producto
     */
    private function seleccionarTrabajadorCompra($productoId)
    {
        $stmt = $this->db->prepare("
            SELECT t.id, t.nombre,
                   COUNT(hc.id) as total_compras,
                   MAX(hc.fecha_compra) as ultima_compra
            FROM trabajadores t
            LEFT JOIN historial_compras hc ON t.id = hc.trabajador_id AND hc.producto_id = :producto_id
            WHERE t.activo = TRUE
            GROUP BY t.id
            ORDER BY total_compras ASC, ultima_compra ASC
            LIMIT 1
        ");
        
        $stmt->execute(['producto_id' => $productoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['id'] : null;
    }
    
    /**
     * Obtiene el stock actual de un producto
     */
    public function obtenerStock($productoId)
    {
        $stmt = $this->db->prepare("
            SELECT i.cantidad, p.estado, p.cantidad_minima
            FROM inventario i
            JOIN productos p ON i.producto_id = p.id
            WHERE i.producto_id = :producto_id
        ");
        
        $stmt->execute(['producto_id' => $productoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene todos los productos con stock bajo
     */
    public function obtenerProductosStockBajo()
    {
        $stmt = $this->db->prepare("
            SELECT p.*, i.cantidad, i.cantidad as stock_actual
            FROM productos p
            LEFT JOIN inventario i ON p.id = i.producto_id
            WHERE p.activo = TRUE 
            AND (i.cantidad IS NULL OR i.cantidad <= p.cantidad_minima)
            ORDER BY p.nombre
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el inventario completo
     */
    public function obtenerInventarioCompleto()
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   COALESCE(i.cantidad, 0) as stock_actual,
                   p.cantidad_minima,
                   CASE 
                       WHEN i.cantidad IS NULL OR i.cantidad = 0 THEN 'agotado'
                       WHEN i.cantidad <= p.cantidad_minima THEN 'bajo'
                       ELSE 'disponible'
                   END as estado_stock
            FROM productos p
            LEFT JOIN inventario i ON p.id = i.producto_id
            WHERE p.activo = TRUE
            ORDER BY p.nombre
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Registra una compra y actualiza el stock
     */
    public function registrarCompra($asignacionId, $cantidad, $precioUnitario, $lugarCompra = null, $notas = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Obtener la asignación
            $stmt = $this->db->prepare("
                SELECT * FROM asignaciones_compra WHERE id = :id
            ");
            $stmt->execute(['id' => $asignacionId]);
            $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asignacion) {
                throw new Exception('Asignación no encontrada');
            }
            
            // Registrar en historial
            $stmt = $this->db->prepare("
                INSERT INTO historial_compras (producto_id, trabajador_id, cantidad_comprada, precio_unitario, precio_total, lugar_compra, fecha_compra, notas)
                VALUES (:producto_id, :trabajador_id, :cantidad, :precio_unitario, :precio_total, :lugar_compra, CURRENT_DATE, :notas)
            ");
            
            $precioTotal = $cantidad * $precioUnitario;
            $stmt->execute([
                'producto_id' => $asignacion['producto_id'],
                'trabajador_id' => $asignacion['trabajador_id'],
                'cantidad_comprada' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'precio_total' => $precioTotal,
                'lugar_compra' => $lugarCompra,
                'notas' => $notas
            ]);
            
            // Actualizar stock
            $this->actualizarStock($asignacion['producto_id'], $cantidad);
            
            // Marcar asignación como completada
            $stmt = $this->db->prepare("
                UPDATE asignaciones_compra 
                SET estado = 'completada', fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            
            $stmt->execute(['id' => $asignacionId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
