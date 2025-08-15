# ☕ Innovant Café - Sistema de Rotación de Compras Semanales

## 📋 Descripción

Sistema web desarrollado en PHP para gestionar la rotación semanal de compras de alimentos entre trabajadores. El sistema asigna automáticamente a diferentes trabajadores la responsabilidad de comprar productos específicos cada semana, evitando que la misma persona compre siempre lo mismo.

## ✨ Características Principales

- **Rotación Automática**: Asigna productos a trabajadores de forma equilibrada
- **Gestión de Trabajadores**: Añadir, editar y gestionar trabajadores activos
- **Gestión de Productos**: Control de productos alimentarios con precios estimados
- **Seguimiento Semanal**: Vista clara de responsabilidades de la semana actual
- **Historial de Compras**: Registro completo de todas las compras realizadas
- **Interfaz Moderna**: Diseño responsive con Bootstrap 5
- **Base de Datos SQLite**: Fácil de implementar y mantener

## 🚀 Instalación

### Requisitos Previos
- PHP 8.0 o superior
- Composer
- Servidor web (Apache/Nginx) o servidor de desarrollo PHP

### Pasos de Instalación

1. **Clonar o descargar el proyecto**
   ```bash
   cd innovant_cafe
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Inicializar la base de datos**
   ```bash
   php init.php
   ```

4. **Configurar el servidor web**
   - Apuntar el document root a la carpeta del proyecto
   - Asegurar que PHP tenga permisos de escritura en la carpeta `database/`

5. **Acceder a la aplicación**
   - URL: `http://localhost:8000` (o el puerto configurado)

## 🏗️ Estructura del Proyecto

```
innovant_cafe/
├── src/                    # Código fuente PHP
│   ├── Database.php       # Clase de conexión a base de datos
│   ├── Trabajador.php     # Gestión de trabajadores
│   ├── Producto.php       # Gestión de productos
│   └── SistemaRotacion.php # Lógica principal del sistema
├── database/              # Base de datos SQLite
├── vendor/                # Dependencias de Composer
├── index.php             # Interfaz web principal
├── init.php              # Script de inicialización
├── config.php            # Configuración del sistema
├── composer.json         # Dependencias del proyecto
└── README.md             # Este archivo
```

## 📊 Cómo Funciona

### 1. **Asignación Semanal**
- Cada lunes se genera automáticamente la rotación de la semana
- El sistema asigna productos a trabajadores basándose en:
  - Número de veces que han comprado cada producto
  - Fecha de la última compra
  - Equilibrio general entre todos los trabajadores

### 2. **Flujo de Trabajo**
1. **Lunes**: Se genera la rotación semanal automáticamente
2. **Durante la semana**: Los trabajadores ven qué productos deben comprar
3. **Al completar**: Marcan la compra como realizada con precio real y notas
4. **Siguiente semana**: Nueva rotación automática

### 3. **Ejemplo de Rotación**
```
Semana 1:
- Pack de Leche → Luis García
- Café en Grano → María López
- Galletas → Carlos Ruiz

Semana 2:
- Pack de Leche → María López (Luis ya compró la semana anterior)
- Café en Grano → Carlos Ruiz
- Galletas → Ana Martín
```

## 🎯 Casos de Uso

### Para Administradores
- Gestionar trabajadores y productos
- Ver estadísticas generales del sistema
- Generar rotaciones manualmente si es necesario

### Para Trabajadores
- Ver qué productos deben comprar esta semana
- Marcar compras como completadas
- Consultar historial de compras propias

## 🔧 Configuración

### Archivo `config.php`
```php
return [
    'database' => [
        'type' => 'sqlite',
        'path' => __DIR__ . '/database/innovant_cafe.db'
    ],
    'app' => [
        'name' => 'Innovant Café',
        'version' => '1.0.0',
        'timezone' => 'Europe/Madrid'
    ],
    'features' => [
        'auto_rotation' => true,
        'email_notifications' => false,
        'price_tracking' => true
    ]
];
```

## 📱 Uso de la Interfaz

### Panel Principal
- **Estadísticas**: Resumen de trabajadores, productos y compras
- **Resumen Semanal**: Lista de productos y responsables de la semana actual
- **Gestión**: Formularios para añadir trabajadores y productos

### Acciones Disponibles
- ✅ Marcar compra como completada
- 👥 Añadir nuevo trabajador
- 📦 Añadir nuevo producto
- 🔄 Generar rotación manual

## 🗄️ Base de Datos

### Tablas Principales
- **trabajadores**: Información de trabajadores activos
- **productos**: Catálogo de productos alimentarios
- **compras_semanales**: Asignaciones semanales actuales
- **historial_compras**: Registro de todas las compras realizadas

## 🚀 Despliegue en Producción

### Recomendaciones
1. **Servidor Web**: Apache o Nginx con PHP 8.0+
2. **Base de Datos**: Considerar migrar a MySQL/PostgreSQL para mayor volumen
3. **Seguridad**: Implementar autenticación de usuarios
4. **Backup**: Configurar respaldos automáticos de la base de datos
5. **SSL**: Usar HTTPS en producción

### Variables de Entorno
```bash
# Crear archivo .env
DB_TYPE=sqlite
DB_PATH=/path/to/database/innovant_cafe.db
APP_ENV=production
APP_DEBUG=false
```

## 🐛 Solución de Problemas

### Errores Comunes
1. **Permisos de Base de Datos**
   ```bash
   chmod 755 database/
   chmod 644 database/*.db
   ```

2. **Extensiones PHP Faltantes**
   ```bash
   # Verificar extensiones requeridas
   php -m | grep -E "(pdo|sqlite3)"
   ```

3. **Problemas de Autoload**
   ```bash
   composer dump-autoload
   ```

## 🤝 Contribuciones

Para contribuir al proyecto:
1. Fork del repositorio
2. Crear rama para nueva funcionalidad
3. Commit de cambios
4. Push a la rama
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo licencia MIT. Ver archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o consultas:
- Email: soporte@innovant.com
- Documentación: [Wiki del proyecto]
- Issues: [GitHub Issues]

---

**Desarrollado con ❤️ para Innovant Café**
