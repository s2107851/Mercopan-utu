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
con zona horaria de Uruguay. No se incluyen edición/anulación de elaboraciones en esta pantalla. Las pérdidas de productos terminados se registran en Pérdidas / donaciones.

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

## Pérdidas y donaciones (commit 12)
En Pérdidas / donaciones se retiran productos elaborados indicando cantidad y motivo/destino.
Guarda stock anterior/nuevo, fecha y responsable en una transacción; no genera ventas ni
consume materias primas otra vez. Consulta por período y tipo con totales separados por unidad.
Consultar `docs/COMMIT_12_salidas.md`. Prueba: `php tests/salidas.php`.


## Búsqueda por nombre (commit 13 · HU15)
Ingredientes y Productos permiten buscar por nombre completo o parcial y limpiar el filtro.
Los símbolos %, _ y ! se buscan como texto literal. Se incluyen registros activos e inactivos,
conservando existencias, unidades y estados. Buscar no modifica datos ni genera historial.
La comparación de mayúsculas/tildes depende de la intercalación de MySQL; la instalación
normal usa utf8mb4_unicode_ci. Se admiten hasta 100 bytes de texto UTF-8.
Después de guardar una edición se vuelve al listado sin filtro, como en las pantallas previas.
No requiere migración ni volver a ejecutar el instalador. Ver `docs/COMMIT_13_busqueda.md`.
Prueba: `php tests/busqueda.php`.


## Facturas de proveedores (entrega 14 · HU05, HU11 y HU12)
En **Facturas** se carga proveedor, número con su serie, fecha de emisión y hasta
50 ingredientes diferentes. Indicar cantidad y costo total de cada renglón, con los
impuestos incluidos según el comprobante. El total se calcula sumando esos costos.

**Guardar borrador** conserva los datos sin modificar stock ni registrar compras.
Abrirlo otra vez permite corregir proveedor, número, fecha y renglones. La pantalla de
revisión muestra el total y ofrece **Confirmar e ingresar stock** o **Cancelar borrador**.
La confirmación genera una compra por renglón y actualiza todas las existencias en una
transacción. No cargar esos ingresos de nuevo en Compras. Resumen y CSV de compras
los incluyen automáticamente por fecha de confirmación/registro. En Facturas el filtro
usa la **fecha de emisión**, y cada documento se muestra una sola vez.

La combinación proveedor + número confirmado es única. Formularios repetidos y
pestañas con una versión antigua se rechazan. Una factura confirmada o cancelada no
se edita; cancelar un borrador no revierte compras porque todavía no tiene ingresos.
Se conservan nombres históricos y responsables de carga/confirmación, además del
historial de guardado y cancelación del borrador. No incluye OCR ni anulación de
facturas confirmadas (la reversión de operaciones corresponde al pendiente HU16).

**Actualización de BD obligatoria:** respaldar la base, copiar los archivos y abrir
`instalar.php` desde la PC de XAMPP; pulsar **Preparar base de datos**. Agrega
`facturas` y `factura_detalle` sin borrar tablas ni registros. Alternativa: importar
solo `database/facturas.sql` dentro de la base existente. Usar uno de esos métodos.
Las compras antiguas se conservan, pero no se agrupan automáticamente en facturas.
No importar un SQL de instalación completa sobre la base de trabajo.

Prueba: `php tests/facturas.php`. Ver `docs/ENTREGA_14_facturas.md`.
Después de esta entrega quedan 20 de 21 historias implementadas en código; falta
completar HU16 (anulación trazable de compras y producciones). La aceptación final
requiere prueba en XAMPP y revisión del equipo.
