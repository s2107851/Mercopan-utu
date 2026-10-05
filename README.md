# Mercopan-utu

Sistema académico de panadería con PHP 8 y MySQL/MariaDB. Sin framework.
La selección de responsable registra quién declara realizar cada operación; no utiliza contraseña.

## Instalación en XAMPP
1. Copiar o clonar el repositorio en `C:\xampp\htdocs\Mercopan-utu`.
2. Iniciar Apache y MySQL.
3. Abrir `http://localhost/Mercopan-utu/instalar.php`.
4. Preparar la base; marcar ejemplos para practicar con datos ficticios.
5. Abrir el sistema y elegir responsable.

Se usa la base `mercopan_utu`, independiente de `mercopan` del producto comercial.
El instalador agrega tablas faltantes sin borrar registros. Para actualizar desde una
versión anterior, respaldar primero la base y ejecutar otra vez `instalar.php`.

La configuración por defecto está en `includes/config.php`. Si es necesario, crear
`includes/config.local.php` (excluido de Git):

```php
<?php
return ['host'=>'127.0.0.1', 'puerto'=>'3306', 'base'=>'mercopan_utu', 'usuario'=>'root', 'clave'=>''];
```

## Módulos disponibles
Personal, proveedores, ingredientes, consulta de stock, aviso de stock bajo y selección
de responsable. El historial registra las altas, ediciones y cambios de estado.
Las bajas de catálogos son lógicas: desactivar conserva sus relaciones e historial.

## Organización del código
- `includes/funciones.php`: PDO, validaciones, formularios, transacciones e historial.
- `includes/catalogos.php`: formularios comunes de personal, proveedores e ingredientes.
- `database/`: tablas y ejemplos ficticios.
- `assets/`: estilos y pequeñas mejoras de los formularios.

El stock inicial de un ingrediente nuevo es cero. La carga de compras y los ajustes
manuales están pendientes; los ejemplos permiten practicar mientras se implementan.
El registro de cambios de desarrollo se consulta en los commits de GitHub.
Proyecto preparado e integrado con asistencia de IA; el equipo debe revisar y comprender el código.
