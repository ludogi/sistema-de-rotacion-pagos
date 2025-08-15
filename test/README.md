# 🧪 Suite de Pruebas - Sistema Innovant Café

Esta carpeta contiene todas las pruebas del sistema para verificar su funcionamiento correcto.

## 📋 Pruebas Disponibles

### 🔌 **test_connection.php**
- **Propósito**: Verifica la conexión a la base de datos MySQL
- **Prueba**: Conexión desde Docker, configuración de red, credenciales

### 👥 **test_crear_trabajador.php**
- **Propósito**: Prueba la creación de trabajadores
- **Prueba**: Crear, validar, eliminar trabajadores de prueba

### ✉️ **test_validacion_email.php**
- **Propósito**: Verifica la validación de emails duplicados
- **Prueba**: Bloqueo de emails duplicados, emails únicos, sin email

### 🔄 **test_rotacion_simple.php**
- **Propósito**: Prueba el sistema de rotación fija
- **Prueba**: Orden de rotación, asignación automática, avisos

### 🛒 **test_compra_completa.php**
- **Propósito**: Verifica el sistema de compra completa por turno
- **Prueba**: Compra múltiple, cálculo de fechas, rotación automática

### 📱 **test_modal_compra.php**
- **Propósito**: Prueba el modal de registro de compras
- **Prueba**: Formularios, validaciones, base de datos

### 🔔 **test_avisos.php**
- **Propósito**: Verifica el sistema de avisos pendientes
- **Prueba**: Generación, estado, fechas límite

### 🎫 **test_tickets.php**
- **Propósito**: Prueba la gestión de tickets de compra
- **Prueba**: Subida, almacenamiento, recuperación

### 📊 **test_reportes.php**
- **Propósito**: Verifica el sistema de reportes
- **Prueba**: Estadísticas, cálculos, exportación

## 🚀 Cómo Ejecutar las Pruebas

### **Ejecutar Todas las Pruebas:**
```bash
# Desde la raíz del proyecto
php test/run_all_tests.php

# Desde Docker
docker exec -it serverphp7 php /var/www/html/test/run_all_tests.php
```

### **Ejecutar Prueba Individual:**
```bash
# Desde la raíz del proyecto
php test/test_connection.php

# Desde Docker
docker exec -it serverphp7 php /var/www/html/test/test_connection.php
```

### **Ejecutar desde la Raíz del Proyecto:**
```bash
# Navegar al directorio de pruebas
cd test

# Ejecutar script principal
php run_all_tests.php

# Ejecutar prueba específica
php test_connection.php
```

## 📊 Interpretación de Resultados

### **✅ Indicadores de Éxito:**
- `✅ PASÓ` - La prueba se ejecutó correctamente
- `✅ CORRECTO` - La funcionalidad funciona como esperado
- `🎉` - Mensajes de éxito

### **❌ Indicadores de Falla:**
- `❌ FALLÓ` - La prueba no pasó
- `💥 ERROR` - Error crítico en la ejecución
- `⚠️` - Advertencias o problemas menores

### **📈 Métricas:**
- **Total de pruebas**: Número total de pruebas ejecutadas
- **Pasaron**: Pruebas exitosas
- **Fallaron**: Pruebas que no pasaron
- **Porcentaje de éxito**: Ratio de pruebas exitosas

## 🔧 Solución de Problemas

### **Si una Prueba Falla:**
1. Revisar el mensaje de error específico
2. Verificar la configuración de la base de datos
3. Comprobar que Docker esté funcionando
4. Revisar los logs del sistema

### **Problemas Comunes:**
- **Conexión a BD**: Verificar puertos y credenciales
- **Permisos**: Comprobar permisos de archivos y carpetas
- **Dependencias**: Verificar que Composer esté instalado
- **Docker**: Comprobar que los contenedores estén activos

## 📁 Estructura de Archivos

```
test/
├── README.md                 # Este archivo
├── run_all_tests.php        # Script principal de pruebas
├── test_connection.php      # Prueba de conexión
├── test_crear_trabajador.php # Prueba de trabajadores
├── test_validacion_email.php # Prueba de emails
├── test_rotacion_simple.php  # Prueba de rotación
├── test_compra_completa.php  # Prueba de compra completa
├── test_modal_compra.php     # Prueba de modal
├── test_avisos.php          # Prueba de avisos
├── test_tickets.php         # Prueba de tickets
└── test_reportes.php        # Prueba de reportes
```

## 🎯 Objetivo

Estas pruebas garantizan que:
- ✅ La base de datos funcione correctamente
- ✅ Los formularios validen los datos
- ✅ El sistema de rotación funcione
- ✅ Las compras se registren correctamente
- ✅ Los avisos se generen automáticamente
- ✅ Los reportes sean precisos
- ✅ El sistema sea estable y confiable

## 🚀 Después de las Pruebas

Una vez que todas las pruebas pasen:
1. El sistema estará listo para uso en producción
2. Se puede acceder a la interfaz web en `http://localhost:8088`
3. Todas las funcionalidades estarán operativas
4. El sistema será estable y confiable
