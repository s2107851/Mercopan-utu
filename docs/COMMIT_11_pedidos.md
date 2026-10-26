# Commit 11: pedidos de clientes

## Problema y resultado

Permite anotar encargos sin confundirlos con ventas ya realizadas. Cada pedido contiene un producto, cliente, teléfono opcional, cantidad, fecha de entrega y observaciones. Se conservan el nombre y unidad del producto y el responsable del alta.

## Historia de usuario propuesta

Como persona que atiende la panadería, quiero anotar y consultar pedidos por estado para preparar las entregas y registrar su venta una sola vez.

## Criterios de aceptación

- Alta en estado pendiente, con cliente y fecha válida desde hoy; producto activo y cantidad positiva. Unidades enteras; kg hasta tres decimales.
- Pendiente puede pasar a listo o cancelado. Listo puede pasar a entregado o cancelado. Los estados finales no se reabren.
- Anotar, preparar o cancelar no modifica stock ni genera ventas.
- Entregar exige stock suficiente y producto activo. Crea una venta al precio vigente, conserva su identificador y descuenta stock en una transacción junto al pedido e historial.
- Una entrega repetida se rechaza, incluso desde otro formulario. El bloqueo del pedido y del producto protege las actualizaciones concurrentes.
- Filtrar por estado, ver todos y paginar de a 25 sin perder el filtro. Los pendientes/listos con fecha pasada se resaltan.
- Un fallo en cualquier guardado revierte todos los cambios; los formularios requieren responsable y token.

## Instalación y commit

1. Usar el repositorio Mercopan-utu actualizado hasta el commit 10. Hacer un respaldo de la base.
2. Copiar el contenido de `archivos/` en la raíz del repositorio, conservando las carpetas y reemplazando los archivos indicados.
3. Iniciar Apache y MySQL en XAMPP. Abrir `http://localhost/Mercopan-utu/instalar.php` y pulsar **Preparar base de datos**. Agrega la tabla faltante sin borrar registros.
4. Elegir un responsable y realizar las verificaciones siguientes.
5. Desde la raíz del repositorio, ejecutar los comandos del archivo `COMANDOS.txt`. Cada integrante usa su propia cuenta y configuración de Git. Revisar y comprender el cambio antes de subirlo.

El menú detecta estas páginas automáticamente. El instalador existente ya contempla las tablas nuevas. No es necesario modificar esos dos archivos.

## Verificación manual

1. Crear un pedido de 3 panes para hoy con al menos 3 panes en stock. Anotarlo y marcar listo: stock y ventas permanecen iguales.
2. Entregar: aparecen una venta de 3 panes, el descuento de 3 y dos acciones (venta y entrega) en Historial.
3. Volver a enviar una entrega desde una pestaña antigua: no crea otra venta.
4. Preparar otro pedido de cantidad superior al stock: al entregar informa el problema y permanece listo.
5. Cancelar un pedido pendiente y otro listo: stock sin cambios. Probar cliente vacío, fecha pasada y cantidad fraccionaria en unidades.
6. Cambiar responsable antes de entregar: en Ventas y en Historial debe figurar quien entregó; el pedido conserva también quién lo anotó.
7. Consultar estados, todos y navegación con más de 25 pedidos. Comprobar móvil/escritorio en XAMPP.

## Prueba automatizada

`php tests/pedidos.php` o `C:\xampp\php\php.exe tests\pedidos.php`.
Usa una base temporal aleatoria y la elimina al terminar. Requiere MySQL y permisos CREATE/DROP DATABASE y TRIGGER. Nunca ejecutar ejemplos SQL sobre datos reales.

## Alcance

Un producto por pedido; no incluye señas, cobros, reservas de stock, presupuestos, modificación de pedidos ni facturación. El precio se toma al entregar, como informa la pantalla. Un pedido entregado ya cuenta en Ventas y Resumen: no registrarlo nuevamente.

La función registrarVenta ahora admite datos opcionales y devuelve el ID de venta. Los formularios anteriores siguen funcionando sin argumentos. Las comprobaciones de stock y precio son las mismas de Ventas.

## Base revisada

Repositorio s2107851/Mercopan-utu, SHA `6d5e488e57a51a7290f97bb2f88df39847492e11`, 26/10/2026. Código preparado con asistencia de IA.

## Validación realizada

- Análisis sintáctico de 40 archivos PHP con php-parser configurado para PHP 8.0: sin errores.
- Comprobación de sintaxis con PHP 8.5.10 en WebAssembly de todos los archivos PHP: sin errores.
- Ejecución funcional real de PHP: 22 comprobaciones de pedidos y 20 de salidas aprobadas, usando una copia temporal con PDO SQLite. Solo se adaptaron la conexión, el DDL, los bloqueos FOR UPDATE y la sintaxis de los triggers para ese motor. No se incluyó esa adaptación en los archivos entregados.
- Los casos verifican ventas vinculadas, precios vigentes, cantidades decimales, stock, responsable, transiciones, entregas repetidas y reversión íntegra ante fallos simulados.
- Pendiente: ejecución de los scripts originales sobre MySQL/MariaDB, validación de concurrencia entre conexiones y revisión visual/navegación en XAMPP. Las pruebas SQLite no verifican bloqueos ni compatibilidad de motor MySQL.
