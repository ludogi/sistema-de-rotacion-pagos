<?php

namespace InnovantCafe;

use PDO;
use Exception;

/**
 * Clase para generar reportes de gastos del sistema
 */
class Reportes
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene el total de gastos por trabajador en un período
     */
    public function obtenerGastosPorTrabajador($fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.nombre,
                t.email,
                COUNT(c.id) as total_compras,
                SUM(c.precio_real) as total_gastado,
                AVG(c.precio_real) as promedio_por_compra,
                MIN(c.fecha_compra) as primera_compra,
                MAX(c.fecha_compra) as ultima_compra
            FROM trabajadores t
            LEFT JOIN compras c ON t.id = c.trabajador_id 
                AND c.fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
            WHERE t.activo = TRUE
            GROUP BY t.id, t.nombre, t.email
            ORDER BY total_gastado DESC
        ");
        
        $stmt->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el resumen total de gastos en un período
     */
    public function obtenerResumenTotalGastos($fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_compras,
                SUM(precio_real) as gasto_total,
                AVG(precio_real) as gasto_promedio,
                COUNT(DISTINCT trabajador_id) as trabajadores_activos,
                COUNT(DISTINCT producto_id) as productos_comprados
            FROM compras 
            WHERE fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
        ");
        
        $stmt->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el detalle de compras de un trabajador
     */
    public function obtenerDetalleComprasTrabajador($trabajadorId, $fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nombre as producto_nombre,
                p.descripcion as producto_descripcion
            FROM compras c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.trabajador_id = :trabajador_id
            AND c.fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY c.fecha_compra DESC
        ");
        
        $stmt->execute([
            'trabajador_id' => $trabajadorId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene gastos por categoría de producto
     */
    public function obtenerGastosPorCategoria($fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                p.nombre as producto,
                COUNT(c.id) as veces_comprado,
                SUM(c.precio_real) as total_gastado,
                AVG(c.precio_real) as precio_promedio
            FROM productos p
            LEFT JOIN compras c ON p.id = c.producto_id 
                AND c.fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
            WHERE p.activo = TRUE
            GROUP BY p.id, p.nombre
            ORDER BY total_gastado DESC
        ");
        
        $stmt->execute([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene estadísticas mensuales del año
     */
    public function obtenerEstadisticasMensuales($anio = null)
    {
        if (!$anio) {
            $anio = date('Y');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                MONTH(fecha_compra) as mes,
                COUNT(*) as total_compras,
                SUM(precio_real) as gasto_total,
                AVG(precio_real) as gasto_promedio,
                COUNT(DISTINCT trabajador_id) as trabajadores_activos
            FROM compras 
            WHERE YEAR(fecha_compra) = :anio
            GROUP BY MONTH(fecha_compra)
            ORDER BY mes
        ");
        
        $stmt->execute(['anio' => $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el top de trabajadores que más compran
     */
    public function obtenerTopCompradores($limite = 5, $fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                t.nombre,
                t.email,
                COUNT(c.id) as total_compras,
                SUM(c.precio_real) as total_gastado,
                AVG(c.precio_real) as promedio_por_compra
            FROM trabajadores t
            JOIN compras c ON t.id = c.trabajador_id
            WHERE c.fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
            AND t.activo = TRUE
            GROUP BY t.id, t.nombre, t.email
            ORDER BY total_gastado DESC
            LIMIT :limite
        ");
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_inicio', $fechaInicio);
        $stmt->bindValue(':fecha_fin', $fechaFin);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Genera un reporte completo del mes especificado
     */
    public function generarReporteMensual($mes = null, $anio = null)
    {
        if (!$mes) $mes = date('n');
        if (!$anio) $anio = date('Y');
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    p.nombre as producto_nombre,
                    t.nombre as trabajador_nombre,
                    t.email as trabajador_email
                FROM compras c
                JOIN productos p ON c.producto_id = p.id
                JOIN trabajadores t ON c.trabajador_id = t.id
                WHERE MONTH(c.fecha_compra) = :mes
                AND YEAR(c.fecha_compra) = :anio
                ORDER BY c.fecha_compra DESC
            ");
            
            $stmt->execute(['mes' => $mes, 'anio' => $anio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            throw new Exception("Error al generar reporte mensual: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene el total de gastos mensuales
     */
    public function obtenerGastosMensuales($anio = null, $mes = null)
    {
        if (!$anio) $anio = date('Y');
        if (!$mes) $mes = date('n');
        
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(precio_real), 0) as total_gastos
            FROM compras 
            WHERE YEAR(fecha_compra) = :anio 
            AND MONTH(fecha_compra) = :mes
        ");
        
        $stmt->execute(['anio' => $anio, 'mes' => $mes]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (float) $resultado['total_gastos'];
    }
    
    /**
     * Obtiene el resumen de rotación y gastos
     */
    public function obtenerResumenRotacion()
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.id,
                t.nombre,
                t.orden_rotacion,
                COUNT(c.id) as total_compras,
                SUM(c.precio_real) as total_gastado,
                MAX(c.fecha_compra) as ultima_compra
            FROM trabajadores t
            LEFT JOIN compras c ON t.id = c.trabajador_id
            WHERE t.activo = TRUE
            GROUP BY t.id, t.nombre, t.orden_rotacion
            ORDER BY t.orden_rotacion
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el historial completo de compras agrupadas por transacción
     */
    public function obtenerHistorialCompras($limite = null)
    {
        $sql = "
            SELECT 
                MAX(c.fecha_compra) as fecha_compra,
                c.trabajador_id,
                MAX(t.nombre) as trabajador_nombre,
                COUNT(c.id) as total_productos,
                SUM(c.precio_real) as total_gastado,
                GROUP_CONCAT(p.nombre SEPARATOR ', ') as productos_comprados,
                MAX(c.lugar_compra) as lugar_compra,
                MAX(c.ticket_subido) as ticket_subido,
                MAX(c.id) as id
            FROM compras c
            JOIN productos p ON c.producto_id = p.id
            JOIN trabajadores t ON c.trabajador_id = t.id
            GROUP BY DATE(c.fecha_compra), c.trabajador_id
            ORDER BY MAX(c.fecha_compra) DESC
        ";
        
        if ($limite) {
            $sql .= " LIMIT :limite";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($limite) {
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene las compras de un producto específico
     */
    public function obtenerComprasPorProducto($productoId, $fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                t.nombre as trabajador_nombre
            FROM compras c
            JOIN trabajadores t ON c.trabajador_id = t.id
            WHERE c.producto_id = :producto_id
            AND c.fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY c.fecha_compra DESC
        ");
        
        $stmt->execute([
            'producto_id' => $productoId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene los gastos de un trabajador específico
     */
    public function obtenerGastosTrabajadorEspecifico($trabajadorId, $fechaInicio = null, $fechaFin = null)
    {
        if (!$fechaInicio) {
            $fechaInicio = date('Y-m-01');
        }
        if (!$fechaFin) {
            $fechaFin = date('Y-m-t');
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                p.nombre as producto_nombre
            FROM compras c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.trabajador_id = :trabajador_id
            AND c.fecha_compra BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY c.fecha_compra DESC
        ");
        
        $stmt->execute([
            'trabajador_id' => $trabajadorId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
