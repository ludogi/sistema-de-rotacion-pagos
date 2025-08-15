<?php

namespace InnovantCafe;

use PDO;
use Exception;

class SistemaRotacion
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene el siguiente trabajador en la rotación
     */
    public function obtenerSiguienteTrabajador($ordenActual)
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
     * Obtiene todos los productos que necesita comprar un trabajador específico
     */
    public function obtenerProductosParaTrabajador($trabajadorId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.id as producto_id,
                p.nombre as producto_nombre,
                p.descripcion,
                p.precio_estimado,
                p.dias_duracion_estimada,
                p.dias_aviso_previo,
                c.fecha_compra as ultima_compra,
                DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY) as fecha_estimada_fin,
                DATE_SUB(DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY), INTERVAL p.dias_aviso_previo DAY) as fecha_aviso,
                DATEDIFF(DATE_SUB(DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY), INTERVAL p.dias_aviso_previo DAY), CURRENT_DATE) as dias_restantes
            FROM productos p
            LEFT JOIN compras c ON p.id = c.producto_id
            WHERE p.activo = TRUE
            AND (
                c.id IS NULL OR 
                DATE_SUB(DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY), INTERVAL p.dias_aviso_previo DAY) <= CURRENT_DATE
            )
            ORDER BY fecha_aviso ASC
        ");
        
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar productos por prioridad
        $productosUrgentes = [];
        $productosNormales = [];
        
        foreach ($productos as $producto) {
            if ($producto['dias_restantes'] <= 3) {
                $productosUrgentes[] = $producto;
            } else {
                $productosNormales[] = $producto;
            }
        }
        
        return [
            'urgentes' => $productosUrgentes,
            'normales' => $productosNormales,
            'todos' => array_merge($productosUrgentes, $productosNormales)
        ];
    }
    
    /**
     * Obtiene el trabajador que debe comprar en el siguiente turno
     */
    public function obtenerProximoComprador()
    {
        // Obtener el trabajador que compró más recientemente
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.nombre,
                t.orden_rotacion,
                MAX(c.fecha_compra) as ultima_compra
            FROM trabajadores t
            LEFT JOIN compras c ON t.id = c.trabajador_id
            WHERE t.activo = TRUE
            GROUP BY t.id, t.nombre, t.orden_rotacion
            HAVING ultima_compra IS NOT NULL
            ORDER BY ultima_compra ASC
            LIMIT 1
        ");
        
        $stmt->execute();
        $ultimoComprador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ultimoComprador) {
            // Si no hay compras, empezar con el primer trabajador
            $stmt = $this->db->prepare("
                SELECT id, nombre, orden_rotacion
                FROM trabajadores 
                WHERE activo = TRUE
                ORDER BY orden_rotacion ASC
                LIMIT 1
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Obtener el siguiente trabajador en la rotación
        return $this->obtenerSiguienteTrabajador($ultimoComprador['orden_rotacion']);
    }
    
    /**
     * Registra una compra completa (múltiples productos) para un trabajador
     */
    public function registrarCompraCompleta($trabajadorId, $productosComprados, $fechaCompra, $lugarCompra = null, $notas = null)
    {
        try {
            $this->db->beginTransaction();
            
            $comprasRegistradas = [];
            $siguienteTrabajador = null;
            
            foreach ($productosComprados as $producto) {
                // Registrar la compra
                $stmt = $this->db->prepare("
                    INSERT INTO compras (producto_id, trabajador_id, fecha_compra, precio_real, lugar_compra, notas)
                    VALUES (:producto_id, :trabajador_id, :fecha_compra, :precio_real, :lugar_compra, :notas)
                ");
                
                $stmt->execute([
                    'producto_id' => $producto['producto_id'],
                    'trabajador_id' => $trabajadorId,
                    'fecha_compra' => $fechaCompra,
                    'precio_real' => $producto['precio_real'] ?? null,
                    'lugar_compra' => $lugarCompra,
                    'notas' => $notas
                ]);
                
                $compraId = $this->db->lastInsertId();
                $comprasRegistradas[] = $compraId;
                
                // Obtener el trabajador actual para calcular el siguiente
                $stmt = $this->db->prepare("SELECT orden_rotacion FROM trabajadores WHERE id = :id");
                $stmt->execute(['id' => $trabajadorId]);
                $trabajadorActual = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Obtener el siguiente trabajador en la rotación
                $siguienteTrabajador = $this->obtenerSiguienteTrabajador($trabajadorActual['orden_rotacion']);
                
                // Calcular fecha estimada de finalización y aviso
                $stmt = $this->db->prepare("
                    SELECT dias_duracion_estimada, dias_aviso_previo 
                    FROM productos 
                    WHERE id = :producto_id
                ");
                $stmt->execute(['producto_id' => $producto['producto_id']]);
                $productoInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $fechaEstimadaFin = date('Y-m-d', strtotime($fechaCompra . ' + ' . $productoInfo['dias_duracion_estimada'] . ' days'));
                $fechaAviso = date('Y-m-d', strtotime($fechaEstimadaFin . ' - ' . $productoInfo['dias_aviso_previo'] . ' days'));
                
                // Crear aviso para el siguiente trabajador
                $stmt = $this->db->prepare("
                    INSERT INTO avisos_pendientes (producto_id, trabajador_asignado, fecha_limite, estado)
                    VALUES (:producto_id, :trabajador_asignado, :fecha_limite, 'pendiente')
                ");
                
                $stmt->execute([
                    'producto_id' => $producto['producto_id'],
                    'trabajador_asignado' => $siguienteTrabajador['id'],
                    'fecha_limite' => $fechaAviso
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'compras_registradas' => $comprasRegistradas,
                'siguiente_trabajador' => $siguienteTrabajador,
                'total_productos' => count($productosComprados),
                'mensaje' => "Compra completa registrada. {$siguienteTrabajador['nombre']} será el siguiente en comprar."
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene el resumen de rotación con estadísticas
     */
    public function obtenerResumenRotacion()
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.nombre,
                t.orden_rotacion,
                COUNT(c.id) as total_compras,
                SUM(CASE WHEN c.precio_real IS NOT NULL THEN c.precio_real ELSE 0 END) as total_gastado,
                MAX(c.fecha_compra) as ultima_compra,
                CASE 
                    WHEN MAX(c.fecha_compra) = (
                        SELECT MAX(fecha_compra) 
                        FROM compras 
                        WHERE trabajador_id = t.id
                    ) THEN 'Último en comprar'
                    ELSE 'Pendiente'
                END as estado_rotacion
            FROM trabajadores t
            LEFT JOIN compras c ON t.id = c.trabajador_id
            WHERE t.activo = TRUE
            GROUP BY t.id, t.nombre, t.orden_rotacion
            ORDER BY t.orden_rotacion ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el historial de rotación ordenado por fecha de compra
     */
    public function obtenerHistorialRotacion($limite = 10)
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.fecha_compra,
                t.nombre as trabajador_nombre,
                t.orden_rotacion,
                p.nombre as producto_nombre,
                c.precio_real,
                c.lugar_compra
            FROM compras c
            JOIN trabajadores t ON c.trabajador_id = t.id
            JOIN productos p ON c.producto_id = p.id
            ORDER BY c.fecha_compra DESC
            LIMIT :limite
        ");
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el estado actual de la rotación
     */
    public function obtenerEstadoRotacion()
    {
        // Obtener el último trabajador que compró
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.nombre,
                t.orden_rotacion,
                MAX(c.fecha_compra) as ultima_compra
            FROM trabajadores t
            JOIN compras c ON t.id = c.trabajador_id
            WHERE t.activo = TRUE
            GROUP BY t.id, t.nombre, t.orden_rotacion
            HAVING ultima_compra = (
                SELECT MAX(fecha_compra) 
                FROM compras
            )
            LIMIT 1
        ");
        
        $stmt->execute();
        $ultimoComprador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ultimoComprador) {
            return [
                'ultimo_comprador' => null,
                'proximo_comprador' => null,
                'estado' => 'Sin compras registradas'
            ];
        }
        
        // Obtener el próximo comprador
        $proximoComprador = $this->obtenerSiguienteTrabajador($ultimoComprador['orden_rotacion']);
        
        return [
            'ultimo_comprador' => $ultimoComprador,
            'proximo_comprador' => $proximoComprador,
            'estado' => 'Rotación activa'
        ];
    }
    
    /**
     * Obtiene avisos pendientes agrupados por trabajador
     */
    public function obtenerAvisosPendientes()
    {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                p.nombre as producto_nombre,
                p.descripcion as producto_descripcion,
                p.precio_estimado,
                t.nombre as trabajador_nombre,
                t.orden_rotacion,
                DATEDIFF(ap.fecha_limite, CURRENT_DATE) as dias_restantes
            FROM avisos_pendientes ap
            JOIN productos p ON ap.producto_id = p.id
            JOIN trabajadores t ON ap.trabajador_asignado = t.id
            WHERE ap.estado = 'pendiente'
            ORDER BY ap.fecha_limite ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene avisos que vencen pronto
     */
    public function obtenerAvisosVencenPronto($dias = 7)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ap.*,
                p.nombre as producto_nombre,
                t.nombre as trabajador_nombre,
                DATEDIFF(ap.fecha_limite, CURRENT_DATE) as dias_restantes
            FROM avisos_pendientes ap
            JOIN productos p ON ap.producto_id = p.id
            JOIN trabajadores t ON ap.trabajador_asignado = t.id
            WHERE ap.estado = 'pendiente'
            AND ap.fecha_limite <= DATE_ADD(CURRENT_DATE, INTERVAL :dias DAY)
            ORDER BY ap.fecha_limite ASC
        ");
        
        $stmt->execute(['dias' => $dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el historial de compras
     */
    public function obtenerHistorialCompras($limite = 20)
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nombre as producto_nombre,
                t.nombre as trabajador_nombre
            FROM compras c
            JOIN productos p ON c.producto_id = p.id
            JOIN trabajadores t ON c.trabajador_id = t.id
            ORDER BY c.fecha_registro DESC
            LIMIT :limite
        ");
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marca un aviso como completado
     */
    public function marcarAvisoCompletado($avisoId)
    {
        $stmt = $this->db->prepare("
            UPDATE avisos_pendientes 
            SET estado = 'completado', fecha_completado = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        return $stmt->execute(['id' => $avisoId]);
    }
}
