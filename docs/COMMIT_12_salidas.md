# Commit 12: pérdidas y donaciones de productos

## Problema y resultado

Permite descontar productos elaborados que se pierden o donan, conservando el motivo y el responsable sin registrarlos como ventas ni volver a consumir ingredientes.

## Historia de usuario propuesta

Como responsable del stock, quiero registrar las pérdidas y donaciones para que las existencias coincidan con lo disponible y pueda explicar cada salida.

## Criterios de aceptación

- Producto activo, cantidad positiva dentro del stock, tipo pérdida o donación y motivo obligatorio.
- Cantidades enteras para unidades y hasta tres decimales para kg. Permite retirar todo el stock y dejar cero.
- Guarda nombre y unidad históricos, cantidad, motivo, tipo, fecha, responsable y stock anterior/nuevo.
- Movimiento, descuento e historial forman una transacción; un fallo no deja cambios parciales. Se bloquea la fila del producto durante la operación.
- No genera ventas ni modifica materias primas. Rechaza stock insuficiente y reenvío del mismo formulario.
- Consultar fechas inclusivas y tipo; paginar de a 25 conservando filtros. Totales de todo el período separados por tipo y unidad.

## Instalación y commit

1. Usar el repositorio Mercopan-utu actualizado hasta el commit 10. Hacer un respaldo de la base.
2. Copiar el contenido de `archivos/` en la raíz del repositorio, conservando las carpetas y reemplazando los archivos indicados.
3. Iniciar Apache y MySQL en XAMPP. Abrir `http://localhost/Mercopan-utu/instalar.php` y pulsar **Preparar base de datos**. Agrega la tabla faltante sin borrar registros.
4. Elegir un responsable y realizar las verificaciones siguientes.
5. Desde la raíz del repositorio, ejecutar los comandos del archivo `COMANDOS.txt`. Cada integrante usa su propia cuenta y configuración de Git. Revisar y comprender el cambio antes de subirlo.

El menú detecta estas páginas automáticamente. El instalador existente ya contempla las tablas nuevas. No es necesario modificar esos dos archivos.

Aplicar preferentemente después del 11. El módulo no necesita la tabla de pedidos; comparte con el 11 el soporte de pruebas y la documentación README acumulada.

## Verificación manual

1. Con 20 panes disponibles, registrar pérdida de 3 con motivo «Producto quemado»: stock 17, antes 20/después 17, responsable y acción en Historial.
2. Donar 0,750 kg de un producto vendido por kg: descuenta esa cantidad y guarda el destino.
3. Intentar retirar más stock, indicar cantidad cero/negativa, fracciones de unidades o motivo vacío: no cambia nada.
4. Retirar exactamente el stock restante: queda cero. Reenviar el formulario anterior: no vuelve a descontar.
5. Filtrar por fecha y tipo. Con más de 25 registros comprobar los filtros al avanzar de página y los totales de todas las coincidencias.
6. Renombrar/desactivar un producto: sus salidas anteriores siguen conservando nombre, unidad y responsable.
7. Revisar escritorio/móvil y comprobar que Ventas conserva sus importes y registros anteriores.

## Prueba automatizada

`php tests/salidas.php` o `C:\xampp\php\php.exe tests\salidas.php`.
Usa una base temporal aleatoria y la elimina al terminar. Requiere MySQL y permisos CREATE/DROP DATABASE y TRIGGER.

## Alcance

Solo productos elaborados. No incluye pérdida de ingredientes, edición/anulación de salidas ni valoración monetaria. El resumen de ventas/compras continúa mostrando esos movimientos; las pérdidas/donaciones se consultan en su propia pantalla.

## Base revisada

Repositorio s2107851/Mercopan-utu, SHA `6d5e488e57a51a7290f97bb2f88df39847492e11`, 26/10/2026. Código preparado con asistencia de IA.

## Validación realizada

- Análisis sintáctico de 40 archivos PHP con php-parser configurado para PHP 8.0: sin errores.
- Comprobación de sintaxis con PHP 8.5.10 en WebAssembly de todos los archivos PHP: sin errores.
- Ejecución funcional real de PHP: 22 comprobaciones de pedidos y 20 de salidas aprobadas, usando una copia temporal con PDO SQLite. Solo se adaptaron la conexión, el DDL, los bloqueos FOR UPDATE y la sintaxis de los triggers para ese motor. No se incluyó esa adaptación en los archivos entregados.
- Los casos verifican ventas vinculadas, precios vigentes, cantidades decimales, stock, responsable, transiciones, entregas repetidas y reversión íntegra ante fallos simulados.
- Pendiente: ejecución de los scripts originales sobre MySQL/MariaDB, validación de concurrencia entre conexiones y revisión visual/navegación en XAMPP. Las pruebas SQLite no verifican bloqueos ni compatibilidad de motor MySQL.
