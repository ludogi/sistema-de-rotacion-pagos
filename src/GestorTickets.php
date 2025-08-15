<?php

namespace InnovantCafe;

use PDO;
use Exception;

class GestorTickets
{
    private $db;
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../uploads/tickets/';
        $this->allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        
        // Crear directorio si no existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Sube un ticket para una compra
     */
    public function subirTicket($compraId, $trabajadorId, $archivo, $notas = null)
    {
        try {
            // Validar archivo
            if (!$this->validarArchivo($archivo)) {
                return [
                    'success' => false,
                    'message' => 'Archivo no válido o demasiado grande'
                ];
            }
            
            // Generar nombre único para el archivo
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'ticket_' . $compraId . '_' . time() . '.' . $extension;
            $rutaCompleta = $this->uploadDir . $nombreArchivo;
            
            // Mover archivo
            if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
                return [
                    'success' => false,
                    'message' => 'Error al subir el archivo'
                ];
            }
            
            // Guardar en base de datos
            $stmt = $this->db->prepare("
                INSERT INTO tickets_compras (
                    compra_id, subido_por, nombre_archivo, ruta_archivo, 
                    tipo_archivo, tamano_archivo, descripcion
                ) VALUES (
                    :compra_id, :subido_por, :nombre_archivo, :ruta_archivo,
                    :tipo_archivo, :tamano_archivo, :notas
                )
            ");
            
            $stmt->execute([
                'compra_id' => $compraId,
                'subido_por' => $trabajadorId,
                'nombre_archivo' => $archivo['name'],
                'ruta_archivo' => $rutaCompleta,
                'tipo_archivo' => $archivo['type'],
                'tamano_archivo' => $archivo['size'],
                'notas' => $notas
            ]);
            
            $ticketId = $this->db->lastInsertId();
            
            // Actualizar la tabla compras
            $stmt = $this->db->prepare("
                UPDATE compras 
                SET ticket_id = :ticket_id, ticket_subido = TRUE 
                WHERE id = :compra_id
            ");
            
            $stmt->execute([
                'ticket_id' => $ticketId,
                'compra_id' => $compraId
            ]);
            
            return [
                'success' => true,
                'ticket_id' => $ticketId,
                'nombre_archivo' => $archivo['name'],
                'message' => 'Ticket subido correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene todos los tickets de una compra
     */
    public function obtenerTicketsCompra($compraId)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, tr.nombre as trabajador_nombre
            FROM tickets_compras t
            JOIN trabajadores tr ON t.subido_por = tr.id
            WHERE t.compra_id = :compra_id AND t.activo = TRUE
            ORDER BY t.fecha_subida DESC
        ");
        
        $stmt->execute(['compra_id' => $compraId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene todos los tickets de un trabajador
     */
    public function obtenerTicketsTrabajador($trabajadorId, $limite = null)
    {
        $sql = "
            SELECT t.*, c.fecha_compra, p.nombre as producto_nombre, tr.nombre as trabajador_nombre
            FROM tickets_compras t
            JOIN compras c ON t.compra_id = c.id
            JOIN productos p ON c.producto_id = p.id
            JOIN trabajadores tr ON t.subido_por = tr.id
            WHERE t.subido_por = :trabajador_id AND t.activo = TRUE
            ORDER BY t.fecha_subida DESC
        ";
        
        if ($limite) {
            $sql .= " LIMIT :limite";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':trabajador_id', $trabajadorId, PDO::PARAM_INT);
        
        if ($limite) {
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene el ticket de una compra específica
     */
    public function obtenerTicketCompra($compraId)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, tr.nombre as trabajador_nombre
            FROM tickets_compras t
            JOIN trabajadores tr ON t.subido_por = tr.id
            WHERE t.compra_id = :compra_id AND t.activo = TRUE
            LIMIT 1
        ");
        
        $stmt->execute(['compra_id' => $compraId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verifica si una compra tiene ticket
     */
    public function compraTieneTicket($compraId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total
            FROM tickets_compras 
            WHERE compra_id = :compra_id AND activo = TRUE
        ");
        
        $stmt->execute(['compra_id' => $compraId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] > 0;
    }
    
    /**
     * Elimina un ticket (marca como inactivo)
     */
    public function eliminarTicket($ticketId, $trabajadorId)
    {
        $stmt = $this->db->prepare("
            UPDATE tickets_compras 
            SET activo = FALSE 
            WHERE id = :ticket_id AND subido_por = :trabajador_id
        ");
        
        return $stmt->execute([
            'ticket_id' => $ticketId,
            'trabajador_id' => $trabajadorId
        ]);
    }
    
    /**
     * Obtiene estadísticas de tickets
     */
    public function obtenerEstadisticasTickets()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_tickets,
                COUNT(DISTINCT compra_id) as compras_con_ticket,
                COUNT(DISTINCT subido_por) as trabajadores_con_ticket,
                SUM(tamano_archivo) as tamano_total,
                AVG(tamano_archivo) as tamano_promedio
            FROM tickets_compras 
            WHERE activo = TRUE
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Valida un archivo antes de subirlo
     */
    private function validarArchivo($archivo)
    {
        // Verificar que se subió correctamente
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Verificar tipo de archivo
        if (!in_array($archivo['type'], $this->allowedTypes)) {
            return false;
        }
        
        // Verificar tamaño
        if ($archivo['size'] > $this->maxFileSize) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtiene la URL pública del ticket
     */
    public function obtenerUrlTicket($rutaArchivo)
    {
        $rutaRelativa = str_replace(__DIR__ . '/../', '', $rutaArchivo);
        return '/' . $rutaRelativa;
    }
    
    /**
     * Obtiene el tipo de archivo como icono
     */
    public function obtenerIconoTipo($tipoArchivo)
    {
        switch ($tipoArchivo) {
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
    
    /**
     * Obtiene tickets de una transacción específica (fecha + trabajador)
     */
    public function obtenerTicketsTransaccion($fecha, $trabajadorId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                tc.*,
                c.fecha_compra,
                c.precio_real,
                p.nombre as producto_nombre,
                t.nombre as trabajador_nombre
            FROM tickets_compras tc
            JOIN compras c ON tc.compra_id = c.id
            JOIN productos p ON c.producto_id = p.id
            JOIN trabajadores t ON c.trabajador_id = t.id
            WHERE DATE(c.fecha_compra) = DATE(:fecha)
            AND c.trabajador_id = :trabajador_id
            AND tc.activo = TRUE
            ORDER BY c.fecha_compra DESC, p.nombre ASC
        ");
        
        $stmt->execute([
            'fecha' => $fecha,
            'trabajador_id' => $trabajadorId
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
