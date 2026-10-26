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
Personal, proveedores, ingredientes, productos terminados, recetas, producción,
consulta de stock, avisos de stock bajo, compras, ajustes de inventario, ventas y selección
de responsable. El historial registra altas, ediciones, cambios de estado, elaboraciones,
compras, ajustes y ventas. Las bajas de catálogos son lógicas: desactivar conserva sus
relaciones y el historial de movimientos anteriores.

## Organización del código
- `includes/funciones.php`: PDO, validaciones, formularios, transacciones e historial.
- `includes/catalogos.php`: formularios comunes de personal, proveedores e ingredientes.
- `includes/compras.php`: validación y guardado transaccional de compras de materias primas.
- `includes/ajustes.php`: registro de conteos y corrección de stock con trazabilidad.
- `includes/ventas.php`: validación de ventas y descuento de stock de productos terminados.
- `database/`: tablas y ejemplos ficticios.
- `assets/`: estilos y pequeñas mejoras de los formularios.

El stock inicial de un ingrediente nuevo es cero. Las compras incrementan el stock y
los ajustes permiten corregirlo por inventario sin modificar compras o producciones.
El registro de cambios de desarrollo se consulta en los commits de GitHub.
Proyecto preparado e integrado con asistencia de IA; el equipo debe revisar y comprender el código.

## Productos y recetas
En Productos se crean los productos terminados y su unidad de medida.
En Recetas se indica cuántos productos rinde un lote y qué ingredientes utiliza.
La receta se puede editar; el catálogo se puede desactivar sin borrar sus referencias.
El precio vigente se utiliza para registrar cada venta y su importe histórico.

Una instalación nueva con ejemplos crea Pan flauta y su receta de demostración:
20 unidades, 5 kg de harina, 0,120 kg de levadura y 0,080 kg de sal.
En una base ya usada no se insertan ejemplos otra vez; registrar el producto y la receta
manualmente para conservar los datos existentes.

## Ventas de productos terminados
En **Ventas** se elige un producto activo con existencias y la cantidad vendida.
El sistema toma el precio del catálogo, calcula el importe y descuenta los productos
terminados; no descuenta materias primas de nuevo (ya se consumieron al producir).
Cada venta guarda fecha, cantidad, precio de ese momento, importe y responsable.
Los productos por unidad exigen cantidades enteras; los vendidos por kg admiten tres
decimales. No se permite vender más stock que el disponible.

La operación guarda venta, descuento e historial en una única transacción; un fallo
revierte todos los cambios. Se pueden filtrar ventas por día o consultar todas las fechas,
con total de importes y paginación. Cada registro corresponde a **un producto**.
Todavía no incluye pagos, facturación electrónica, devoluciones ni anulaciones.
Para detalles y validación, consultar `docs/COMMIT_07_ventas.md`.

## Compras e ingreso de stock
En Compras se selecciona un proveedor y un ingrediente activos, se indica la cantidad
según la unidad de stock, su costo total y opcionalmente un número de comprobante.
Cada ingreso corresponde a un ingrediente. Si una factura contiene varios, registrar
una compra por cada ingrediente, repitiendo el mismo número de comprobante.

Cada compra guarda fecha, responsable y costo, y suma stock e historial en una
transacción. No se permiten cantidades negativas, fracciones para artículos contados
ni superar la capacidad de la columna de stock. No se duplica un formulario reenviado.
No se incluye todavía lectura automática de facturas, anulaciones ni devoluciones.

## Ajustes de inventario
En Ajustes se selecciona un ingrediente activo, se ingresa la cantidad **real que se
contó físicamente** y se justifica el motivo. El sistema calcula la diferencia respecto
del stock registrado y guarda los valores anterior y nuevo, la fecha, el motivo y el
responsable. Se puede ajustar a cero, pero no a valores negativos ni fracciones de
unidades. No se guarda un ajuste si el conteo coincide con el stock actual.

Los ajustes se registran separados de compras y producción para mantener su historial.
Consultar `docs/COMMIT_06_ajustes_inventario.md` para instrucciones de implementación.

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
Desde la carpeta del proyecto, ejecutar `php tests/produccion.php`,
`php tests/compras.php`, `php tests/ajustes.php` y `php tests/ventas.php`
con MySQL iniciado.
En Windows pueden usarse las mismas rutas con `C:\xampp\php\php.exe`,
por ejemplo `C:\xampp\php\php.exe tests\ventas.php`.
El usuario de MySQL necesita permiso para crear bases. Cada prueba crea una base temporal
con nombre aleatorio, utiliza únicamente esa base y la elimina al terminar.
No ejecutar los scripts demo manualmente sobre una base real.


## Pedidos de clientes (commit 11)
Registrar un producto por pedido y consultar los estados pendiente, listo, entregado y cancelado.
Anotar/listar/cancelar no reserva ni descuenta stock. Al entregar un pedido listo, se registra
una venta al precio vigente y se descuenta stock en la misma transacción. No registrar esa
venta otra vez desde Ventas. Sin stock suficiente la entrega no se confirma.
Consultar `docs/COMMIT_11_pedidos.md`. Prueba: `php tests/pedidos.php`.
