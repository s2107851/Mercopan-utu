# Commit 07 — Registro de ventas y descuento de productos terminados

## Objetivo
Completar el recorrido **compras de ingredientes → producción → productos terminados → ventas** de la versión académica Mercopan UTU.

## Cambios
- `ventas.php`: formulario de ventas y consulta por jornada (fecha, producto, cantidad, importe, responsable, paginación).
- `includes/ventas.php`: validación, importe, descuento de stock y trazabilidad.
- `database/ventas.sql`: tabla de ventas con precio y nombre de responsable históricos.
- `tests/ventas.php`: pruebas de cálculo, unidades/kilos, stock y validaciones.
- `README.md`: instalación y descripción actualizadas.

El menú y la tarjeta de inicio muestran Ventas automáticamente al existir `ventas.php`. El instalador ya incluía el nombre `ventas` en sus scripts opcionales; ahora encuentra y crea esa tabla sin borrar las existentes.

## Reglas funcionales
1. Seleccionar un producto activo que tenga stock y cantidad mayor a cero.
2. Los productos por unidad exigen cantidades enteras. Los vendidos por kg admiten tres decimales.
3. El precio se toma del catálogo; el navegador no puede modificarlo.
4. La venta almacena cantidad, precio de ese momento, importe, fecha, responsable y nombre histórico.
5. El stock nunca puede ser negativo. Se bloquea la fila del producto durante el guardado.
6. Venta, descuento de stock e historial se guardan en una sola transacción; ante un error se revierten todos.
7. Se reutiliza el mecanismo existente de formularios con token y responsable seleccionado (sin contraseña).

## Probar en XAMPP
1. Actualizar el repositorio, iniciar Apache y MySQL y hacer copia de seguridad de la base.
2. Abrir `http://localhost/Mercopan-utu/instalar.php` y hacer clic en **Preparar base de datos**. La instalación es repetible y no borra datos.
3. Seleccionar responsable. Si no hay productos con stock, registrar primero una elaboración en **Producción**.
4. Elegir **Ventas**. Con 20 panes a $25, registrar 3 unidades: deben quedar 17 y constar $75.
5. Consultar por fecha y revisar que Historial registre al responsable.
6. Verificar que se rechace una venta mayor al stock, un producto inactivo, una cantidad fraccionaria para unidades y un reenvío del formulario.
7. Ejecutar con MySQL activo: `C:\xampp\php\php.exe tests\ventas.php` (o `php tests/ventas.php`).

La prueba crea y elimina únicamente una base temporal aleatoria; requiere permiso de creación de bases.

## Alcance pendiente
Se registra **un producto por venta**, sin carrito, descuentos, pagos, devoluciones ni anulaciones. No gestiona todavía encargos, salidas por pérdidas o reportes generales. Estas mejoras se reservan para commits posteriores.

## Autoría y validación
Desarrollo preparado con asistencia de IA; el equipo debe revisar, ejecutar los casos de prueba y entender el código antes de presentarlo. El módulo queda integrado en la rama principal a través de un commit de GitHub; no se atribuyen pruebas locales no ejecutadas.
