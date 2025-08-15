-- Script completo para crear la base de datos Innovant Café
-- Sistema simplificado: rotación fija + fechas estimadas + tickets
-- Ejecutar desde terminal: mysql -u root -p < database/complete_database.sql

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS `innovant_cafe` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE `innovant_cafe`;

-- Eliminar tablas si existen (para limpiar instalaciones previas)
DROP TABLE IF EXISTS `tickets_compras`;
DROP TABLE IF EXISTS `historial_compras`;
DROP TABLE IF EXISTS `asignaciones_compra`;
DROP TABLE IF EXISTS `inventario`;
DROP TABLE IF EXISTS `productos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `trabajadores`;
DROP TABLE IF EXISTS `configuraciones`;

-- Tabla de trabajadores (orden fijo de rotación)
CREATE TABLE `trabajadores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) UNIQUE,
    `orden_rotacion` INT NOT NULL COMMENT 'Orden fijo en la rotación (1, 2, 3...)',
    `activo` BOOLEAN DEFAULT TRUE,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios para login
CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trabajador_id` INT,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('trabajador', 'admin') DEFAULT 'trabajador',
    `ultimo_login` TIMESTAMP NULL,
    `activo` BOOLEAN DEFAULT TRUE,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE CASCADE,
    INDEX `idx_username` (`username`),
    INDEX `idx_orden_rotacion` (`orden_rotacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de productos con fechas estimadas de finalización
CREATE TABLE `productos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` TEXT,
    `precio_estimado` DECIMAL(10,2),
    `dias_duracion_estimada` INT NOT NULL COMMENT 'Días estimados que dura el producto',
    `dias_aviso_previo` INT DEFAULT 7 COMMENT 'Días antes de finalizar para avisar',
    `activo` BOOLEAN DEFAULT TRUE,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de compras realizadas
CREATE TABLE `compras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT,
    `trabajador_id` INT,
    `fecha_compra` DATE NOT NULL,
    `precio_real` DECIMAL(10,2),
    `lugar_compra` VARCHAR(100),
    `notas` TEXT,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE CASCADE,
    INDEX `idx_fecha_compra` (`fecha_compra`),
    INDEX `idx_trabajador` (`trabajador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tickets de compras
CREATE TABLE `tickets_compras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `compra_id` INT NOT NULL,
    `nombre_archivo` VARCHAR(255) NOT NULL,
    `ruta_archivo` VARCHAR(500) NOT NULL,
    `tipo_archivo` VARCHAR(100),
    `tamaño_archivo` INT COMMENT 'Tamaño en bytes',
    `descripcion` VARCHAR(255),
    `fecha_subida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `subido_por` INT COMMENT 'ID del usuario que subió el ticket',
    `activo` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    INDEX `idx_compra` (`compra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de avisos pendientes
CREATE TABLE `avisos_pendientes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT,
    `trabajador_asignado` INT,
    `fecha_limite` DATE NOT NULL,
    `estado` ENUM('pendiente', 'completado', 'cancelado') DEFAULT 'pendiente',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_completado` TIMESTAMP NULL,
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trabajador_asignado`) REFERENCES `trabajadores` (`id`) ON DELETE CASCADE,
    INDEX `idx_fecha_limite` (`fecha_limite`),
    INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuraciones del sistema
CREATE TABLE `configuraciones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(100) UNIQUE NOT NULL,
    `valor` TEXT,
    `descripcion` TEXT,
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones por defecto
INSERT INTO `configuraciones` (`clave`, `valor`, `descripcion`) VALUES
('max_ticket_size', '5242880', 'Tamaño máximo de ticket en bytes (5MB)'),
('allowed_ticket_types', 'jpg,jpeg,png,pdf', 'Tipos de archivo permitidos para tickets'),
('tickets_folder', 'uploads/tickets', 'Carpeta para almacenar tickets'),
('dias_aviso_default', '7', 'Días por defecto para aviso previo');

-- Insertar trabajadores en orden fijo de rotación
INSERT INTO `trabajadores` (`nombre`, `email`, `orden_rotacion`) VALUES
('Luis', 'luis@innovant.com', 1),
('Antonio', 'antonio@innovant.com', 2),
('Emilio', 'emilio@innovant.com', 3);

-- Insertar usuarios para login (password: innovant123)
INSERT INTO `usuarios` (`trabajador_id`, `username`, `password`, `rol`) VALUES
(1, 'luis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trabajador'),
(2, 'antonio', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trabajador'),
(3, 'emilio', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trabajador'),
(NULL, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insertar productos con duración estimada
INSERT INTO `productos` (`nombre`, `descripcion`, `precio_estimado`, `dias_duracion_estimada`, `dias_aviso_previo`) VALUES
('Pack de Leche', 'Leche semidesnatada 6 unidades', 4.50, 25, 7),      -- Dura 25 días, avisa 7 días antes
('Café en Grano', 'Café arábica premium 1kg', 12.00, 14, 7),            -- Dura 14 días, avisa 7 días antes
('Galletas', 'Galletas integrales 2 paquetes', 3.80, 10, 7),             -- Dura 10 días, avisa 7 días antes
('Zumo de Naranja', 'Zumo natural 1L', 2.50, 5, 7),                     -- Dura 5 días, avisa 7 días antes
('Fruta Fresca', 'Manzanas y plátanos', 8.00, 7, 7),                     -- Dura 7 días, avisa 7 días antes
('Té Verde', 'Té verde orgánico 50 bolsitas', 6.50, 30, 7);              -- Dura 30 días, avisa 7 días antes

-- Crear primera compra de ejemplo (Luis compra leche el 10/12/2025)
INSERT INTO `compras` (`producto_id`, `trabajador_id`, `fecha_compra`, `precio_real`, `lugar_compra`) VALUES
(1, 1, '2025-12-10', 4.50, 'Supermercado Local');

-- Crear aviso para Antonio (siguiente en la rotación) - 1 semana antes del 05/01/2026
INSERT INTO `avisos_pendientes` (`producto_id`, `trabajador_asignado`, `fecha_limite`, `estado`) VALUES
(1, 2, '2025-12-29', 'pendiente'); -- Antonio debe comprar leche antes del 29/12 (1 semana antes del 05/01)

-- Verificar que las tablas se crearon correctamente
SELECT '🎉 Base de datos innovant_cafe creada correctamente' as mensaje;
SELECT '📊 Resumen de la instalación:' as info;

-- Mostrar estadísticas
SELECT 
    'Trabajadores' as tipo,
    COUNT(*) as total
FROM `trabajadores`
UNION ALL
SELECT 
    'Usuarios' as tipo,
    COUNT(*) as total
FROM `usuarios`
UNION ALL
SELECT 
    'Productos' as tipo,
    COUNT(*) as total
FROM `productos`
UNION ALL
SELECT 
    'Compras registradas' as tipo,
    COUNT(*) as total
FROM `compras`
UNION ALL
SELECT 
    'Avisos pendientes' as tipo,
    COUNT(*) as total
FROM `avisos_pendientes`
WHERE estado = 'pendiente';

-- Mostrar orden de rotación
SELECT 
    '🔄 Orden de Rotación:' as info;
SELECT 
    orden_rotacion,
    nombre,
    email
FROM trabajadores 
ORDER BY orden_rotacion;

-- Mostrar productos con duración estimada
SELECT 
    '📦 Productos configurados:' as info;
SELECT 
    nombre,
    dias_duracion_estimada,
    dias_aviso_previo,
    CONCAT('Dura ', dias_duracion_estimada, ' días, avisa ', dias_aviso_previo, ' días antes') as descripcion
FROM productos 
ORDER BY nombre;

-- Mostrar compra de ejemplo
SELECT 
    '🛒 Compra de ejemplo registrada:' as info;
SELECT 
    p.nombre as producto,
    t.nombre as trabajador,
    c.fecha_compra,
    c.precio_real,
    c.lugar_compra,
    DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY) as fecha_estimada_fin,
    DATE_SUB(DATE_ADD(c.fecha_compra, INTERVAL p.dias_duracion_estimada DAY), INTERVAL p.dias_aviso_previo DAY) as fecha_aviso
FROM compras c
JOIN productos p ON c.producto_id = p.id
JOIN trabajadores t ON c.trabajador_id = t.id
ORDER BY c.fecha_compra DESC;

-- Mostrar avisos pendientes
SELECT 
    '🔔 Avisos pendientes:' as info;
SELECT 
    p.nombre as producto,
    t.nombre as trabajador_asignado,
    ap.fecha_limite,
    ap.estado
FROM avisos_pendientes ap
JOIN productos p ON ap.producto_id = p.id
JOIN trabajadores t ON ap.trabajador_asignado = t.id
WHERE ap.estado = 'pendiente'
ORDER BY ap.fecha_limite ASC;

-- Mostrar próxima rotación
SELECT 
    '📋 Próxima rotación para leche:' as info;
SELECT 
    'Luis → Antonio → Emilio → Luis...' as orden,
    'Antonio debe comprar antes del 29/12/2025' as proximo_aviso,
    'Emilio será el siguiente después de Antonio' as siguiente;

SELECT '🎯 Sistema listo para usar en http://localhost:8088' as final;
SELECT '🔐 Login con usuario: innovant123' as credenciales;
SELECT '🔄 Rotación fija: Luis → Antonio → Emilio' as rotacion;
SELECT '📅 Sistema de fechas estimadas + avisos automáticos' as funcionalidad;
