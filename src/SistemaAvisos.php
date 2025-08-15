<?php

namespace InnovantCafe;

use PDO;
use DateTime;

class SistemaAvisos
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Verifica si un producto necesita ser recomprado basándose en su período personalizado
     */
    public function verificarAvisosPeriodicos()
    {
        $productos = $this->obtenerProductosConPeriodos();
        $avisosGenerados = [];
        
        foreach ($productos as $producto) {
            if ($this->debeGenerarAviso($producto)) {
                $trabajadorId = $this->seleccionarTrabajadorCompra($producto['id']);
                
                if ($trabajadorId) {
                    $this->crearAvisoPeriodico($producto['id'], $trabajadorId, $producto);
                    $avisosGenerados[] = [
                        'producto' => $producto['nombre'],
                        'trabajador' => $this->obtenerNombreTrabajador($trabajadorId),
                        'periodo' => $this->formatearPeriodo($producto['periodo_aviso'], $producto['unidad_periodo'])
                    ];
                }
            }
        }
        
        return $avisosGenerados;
    }
    
    /**
     * Obtiene todos los productos con sus períodos de aviso configurados
     */
    private function obtenerProductosConPeriodos()
    {
        $stmt = $this->db->prepare("
            SELECT p.*, i.cantidad as stock_actual
            FROM productos p
            LEFT JOIN inventario i ON p.id = i.producto_id
            WHERE p.activo = TRUE 
            AND p.periodo_aviso > 0
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Determina si debe generar un aviso para un producto
     */
    private function debeGenerarAviso($producto)
    {
        // Obtener la última compra del producto
        $stmt = $this->db->prepare("
            SELECT MAX(fecha_compra) as ultima_compra
            FROM historial_compras 
            WHERE producto_id = :producto_id
        ");
        
        $stmt->execute(['producto_id' => $producto['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result['ultima_compra']) {
            // Si nunca se ha comprado, generar aviso
            return true;
        }
        
        $ultimaCompra = new DateTime($result['ultima_compra']);
        $hoy = new DateTime();
        $diferencia = $hoy->diff($ultimaCompra);
        
        // Calcular días desde la última compra
        $diasDesdeCompra = $diferencia->days;
        
        // Convertir período de aviso a días
        $periodoEnDias = $this->convertirPeriodoADias($producto['periodo_aviso'], $producto['unidad_periodo']);
        
        // Si han pasado más días que el período configurado, generar aviso
        return $diasDesdeCompra >= $periodoEnDias;
    }
    
    /**
     * Convierte el período configurado a días
     */
    private function convertirPeriodoADias($periodo, $unidad)
    {
        switch ($unidad) {
            case 'dias':
                return $periodo;
            case 'semanas':
                return $periodo * 7;
            case 'meses':
                return $periodo * 30; // Aproximación
            default:
                return $periodo;
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
     * Crea un aviso periódico para un producto
     */
    private function crearAvisoPeriodico($productoId, $trabajadorId, $producto)
    {
        // Verificar si ya existe una asignación pendiente
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM asignaciones_compra 
            WHERE producto_id = :producto_id 
            AND estado = 'pendiente'
        ");
        
        $stmt->execute(['producto_id' => $productoId]);
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
            return false; // Ya existe una asignación pendiente
        }
        
        // Calcular fecha límite basada en el período del producto
        $periodoEnDias = $this->convertirPeriodoADias($producto['periodo_aviso'], $producto['unidad_periodo']);
        $fechaLimite = date('Y-m-d', strtotime("+{$periodoEnDias} days"));
        
        // Crear la asignación
        $stmt = $this->db->prepare("
            INSERT INTO asignaciones_compra (producto_id, trabajador_id, estado, tipo_asignacion, fecha_limite, motivo)
            VALUES (:producto_id, :trabajador_id, 'pendiente', 'periodo_vencido', :fecha_limite, :motivo)
        ");
        
        $motivo = "Aviso periódico - {$this->formatearPeriodo($producto['periodo_aviso'], $producto['unidad_periodo'])}";
        
        return $stmt->execute([
            'producto_id' => $productoId,
            'trabajador_id' => $trabajadorId,
            'fecha_limite' => $fechaLimite,
            'motivo' => $motivo
        ]);
    }
    
    /**
     * Obtiene el nombre de un trabajador por ID
     */
    private function obtenerNombreTrabajador($trabajadorId)
    {
        $stmt = $this->db->prepare("SELECT nombre FROM trabajadores WHERE id = :id");
        $stmt->execute(['id' => $trabajadorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nombre'] : 'Desconocido';
    }
    
    /**
     * Formatea el período para mostrar
     */
    private function formatearPeriodo($periodo, $unidad)
    {
        switch ($unidad) {
            case 'dias':
                return $periodo == 1 ? '1 día' : "{$periodo} días";
            case 'semanas':
                return $periodo == 1 ? '1 semana' : "{$periodo} semanas";
            case 'meses':
                return $periodo == 1 ? '1 mes' : "{$periodo} meses";
            default:
                return "{$periodo} {$unidad}";
        }
    }
    
    /**
     * Obtiene todos los avisos pendientes
     */
    public function obtenerAvisosPendientes()
    {
        $stmt = $this->db->prepare("
            SELECT ac.*, t.nombre as trabajador_nombre, p.nombre as producto_nombre, 
                   p.descripcion, p.periodo_aviso, p.unidad_periodo, ac.motivo
            FROM asignaciones_compra ac
            JOIN trabajadores t ON ac.trabajador_id = t.id
            JOIN productos p ON ac.producto_id = p.id
            WHERE ac.estado = 'pendiente'
            ORDER BY ac.prioridad DESC, ac.fecha_limite ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el resumen de avisos por tipo
     */
    public function obtenerResumenAvisos()
    {
        $stmt = $this->db->prepare("
            SELECT 
                ac.tipo_asignacion,
                COUNT(*) as total,
                GROUP_CONCAT(p.nombre SEPARATOR ', ') as productos
            FROM asignaciones_compra ac
            JOIN productos p ON ac.producto_id = p.id
            WHERE ac.estado = 'pendiente'
            GROUP BY ac.tipo_asignacion
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
