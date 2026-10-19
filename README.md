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
Personal, proveedores, ingredientes, consulta de stock, aviso de stock bajo, compras
y selección de responsable. El historial registra las altas, ediciones, cambios de estado
y compras. Las bajas de catálogos son lógicas: desactivar conserva sus relaciones e historial.

## Organización del código
- `includes/funciones.php`: PDO, validaciones, formularios, transacciones e historial.
- `includes/catalogos.php`: formularios comunes de personal, proveedores e ingredientes.
- `includes/compras.php`: validación y guardado transaccional de compras de materias primas.
- `database/`: tablas y ejemplos ficticios.
- `assets/`: estilos y pequeñas mejoras de los formularios.

El stock inicial de un ingrediente nuevo es cero. Las compras incrementan el stock;
los ajustes manuales por inventario siguen pendientes. Los ejemplos ficticios permiten
practicar mientras se implementan las siguientes funcionalidades.
El registro de cambios de desarrollo se consulta en los commits de GitHub.
Proyecto preparado e integrado con asistencia de IA; el equipo debe revisar y comprender el código.

## Productos y recetas
En Productos se crean los productos terminados y su unidad de medida.
En Recetas se indica cuántos productos rinde un lote y qué ingredientes utiliza.
La receta se puede editar; el catálogo se puede desactivar sin borrar sus referencias.
El precio del producto queda preparado para una futura etapa de ventas.

Una instalación nueva con ejemplos crea Pan flauta y su receta de demostración:
20 unidades, 5 kg de harina, 0,120 kg de levadura y 0,080 kg de sal.
En una base ya usada no se insertan ejemplos otra vez; registrar el producto y la receta
manualmente para conservar los datos existentes.

## Compras e ingreso de stock
En Compras se selecciona un proveedor y un ingrediente activos, se indica la cantidad
según la unidad de stock, su costo total y opcionalmente un número de comprobante.
Cada ingreso corresponde a un ingrediente. Si una factura contiene varios, registrar
una compra por cada ingrediente, repitiendo el mismo número de comprobante.

Cada compra guarda fecha, responsable y costo, y suma stock e historial en una
transacción. No se permiten cantidades negativas, fracciones para artículos contados
ni superar la capacidad de la columna de stock. No se duplica un formulario reenviado.
No se incluye todavía lectura automática de facturas, anulaciones ni devoluciones.

## Producción
Cubre HU03 (registrar lo elaborado), HU04 (descontar ingredientes) y HU10 (consultar
producciones por fecha). Elegir un producto con receta, ingresar lotes enteros y confirmar.
La cantidad obtenida se calcula según el rendimiento; cada elaboración conserva sus consumos.
La pantalla muestra la fecha, el responsable, lo producido y los ingredientes utilizados.
El filtro por día empieza en hoy y permite consultar otras jornadas o todas las fechas.

Cada operación actualiza ingredientes, productos e historial en una sola transacción.
Si falta stock, un ingrediente está inactivo o falla el guardado, se revierte todo.
No se admite reenviar el mismo formulario. La producción toma la fecha actual del servidor
con zona horaria de Uruguay. No se incluyen edición/anulación de elaboraciones ni mermas.

### Prueba rápida
Con ejemplos y sin movimientos previos: elaborar un lote de Pan flauta.
Deben quedar 20 panes, 95 kg de harina, 5,880 kg de levadura y 7,920 kg de sal.
Revisar su consumo en Producción y su responsable en Historial.
Probar muchos lotes sin stock suficiente: no debe cambiar ninguna existencia.

### Pruebas automatizadas
Desde la carpeta del proyecto, ejecutar `php tests/produccion.php` y
`php tests/compras.php` con MySQL iniciado.
En Windows pueden usarse `C:\xampp\php\php.exe tests\produccion.php` y
`C:\xampp\php\php.exe tests\compras.php`.
El usuario de MySQL necesita permiso para crear bases. Cada prueba crea una base temporal
con nombre aleatorio, utiliza únicamente esa base y la elimina al terminar.
No ejecutar los scripts demo manualmente sobre una base real.

Consultar `docs/COMMIT_05_compras.md` para instrucciones de este cambio.
