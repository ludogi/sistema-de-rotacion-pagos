-- Tabla para almacenar tickets de compras
CREATE TABLE IF NOT EXISTS tickets_compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compra_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL,
    tamano_archivo INT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trabajador_id INT NOT NULL,
    notas TEXT,
    activo BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id) ON DELETE CASCADE,
    
    INDEX idx_compra (compra_id),
    INDEX idx_trabajador (trabajador_id),
    INDEX idx_fecha (fecha_subida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna para tickets en la tabla compras si no existe
ALTER TABLE compras ADD COLUMN IF NOT EXISTS ticket_id INT NULL;
ALTER TABLE compras ADD COLUMN IF NOT EXISTS ticket_subido BOOLEAN DEFAULT FALSE;

-- Índice para búsquedas rápidas
CREATE INDEX IF NOT EXISTS idx_compras_ticket ON compras(ticket_id, ticket_subido);
