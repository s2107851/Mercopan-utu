# Entrega 14 — HU05, HU11 y HU12: facturas completas

Base revisada: main@46ee6e4c32ad9fd0c9b535b3e4efd24a74182fb9, 29/10/2026.
Esta entrega incorpora HU05, HU11 y HU12 en el repositorio. La base de cada instalación
debe actualizarse por separado con el instalador o el SQL incremental.

## Historias implementadas

- **HU05, registrar facturas:** proveedor, número/serie, emisión y hasta 50 ingredientes
  con cantidad y costo de renglón. El total suma esos costos. Guardar borrador no ingresa
  mercadería; confirmar aplica todo el stock y sus compras en una transacción.
- **HU11, consultar facturas:** listado paginado por fecha de emisión, estado y total.
  Al abrir se ven todos los renglones, autor de carga y confirmador. Una factura ocupa
  una fila en el listado, aunque tenga varios ingredientes. Nombres históricos conservados.
- **HU12, corregir antes de confirmar:** borrador persistente y recuperable; se pueden
  cambiar proveedor, número, emisión y renglones. Control de versión bloquea pestañas
  antiguas. Cancelar conserva el documento sin ingresar mercadería.

Cada renglón confirmado enlaza una compra. Resumen y CSV incluyen esas compras
por fecha de registro/confirmación sin sumar otra vez la cabecera de la factura.
No registrar manualmente otra vez los ingresos de una factura confirmada.
Se rechaza confirmar dos documentos del mismo proveedor y número, y el reenvío de
un mismo formulario. Para corregir una factura confirmada hace falta la futura HU16.
No incluye OCR ni cálculo de impuestos: indicar costos finales del comprobante.

## Base de datos: sí, requiere actualizarla

1. Respaldar la base de trabajo desde phpMyAdmin.
2. Copiar los archivos de esta entrega en la raíz del proyecto.
3. Con Apache y MySQL activos, abrir el instalar.php **actualizado** desde la misma PC
   del servidor y pulsar Preparar base de datos.
4. Alternativa al paso 3: seleccionar la base existente en phpMyAdmin e importar
   únicamente database/facturas.sql. Elegir un método; no hacen falta ambos.

Se agregan facturas y factura_detalle. El SQL usa CREATE TABLE IF NOT EXISTS,
no borra datos, no altera las tablas anteriores ni agrupa compras históricas.
No importar un SQL de instalación completa ni reinstalar XAMPP para este cambio.
El archivo SQL incremental está también en la raíz del ZIP por comodidad.
Si se restaura después el SQL completo anterior a esta entrega, volver a aplicar esta actualización.

## Prueba en XAMPP y definición de hecho

1. Elegir un responsable, proveedor e ingredientes activos.
2. Crear factura con 5 kg de harina y 6 huevos; costos $100,10 y $30,20.
   Guardar: total $130,30; stock y Compras todavía sin ingresos nuevos.
3. Volver a abrir, editar harina de 5 a 3 y guardar. Confirmar: ingresa 3 kg y 6 huevos,
   se crean dos compras y una acción de confirmación en Historial.
4. Repetir envío o confirmar otra factura del mismo proveedor/número: rechazar sin
   sumar stock. Intentar confirmar una pestaña con versión vieja: rechazar.
5. Cancelar otro borrador: stock y compras intactos. Las confirmadas no se editan.
6. Filtrar por emisión y abrir detalle. Revisar fecha vacía, incorrecta y sin resultados;
   probar paginación con más de 25 documentos y agregar renglones desde el botón.
7. Desactivar un ingrediente entre guardado y confirmación: rechazar todo sin ingresos
   parciales. Revisar Resumen, exportación CSV, Historial y responsables.
8. Comprobar aspecto y uso en escritorio y teléfono; registrar evidencia y otro integrante
   revisa los criterios. Solo entonces marcar las historias como Hechas.

Comando: C:\xampp\php\php.exe tests\facturas.php
También: tests\compras.php y tests\consultas.php.
Las pruebas de facturas/compras crean una base aleatoria y la eliminan al terminar;
requieren permisos CREATE/DROP DATABASE y no modifican la base de trabajo.

## Validación realizada

- PHP 8.3.6, PDO MySQL, MariaDB 10.11.14 con tablas InnoDB.
- 40 comprobaciones funcionales de facturas aprobadas, sin adaptar SQL ni lógica.
- Regresión: 22 comprobaciones de compras y 12 de consultas aprobadas.
- 14 verificaciones HTTP aprobadas: instalador, responsable, borrador, edición,
  confirmación, reenvío, filtros, cancelación, escape HTML, Compras y exportación CSV.
- Sintaxis de 45 archivos PHP y de assets/facturas.js correcta.
- SQL repetido conserva compras previas y los demás registros.
- No se realizó aceptación visual en un navegador del equipo ni prueba en su XAMPP.

La salida de tests/facturas.php se incluye en evidencia_facturas.txt.

## Qué queda

Hay 21 historias documentadas. Esta entrega integra HU05, HU11 y HU12 sobre las 17
ya implementadas, por lo que quedan 20 implementadas y **una pendiente de completar
en código: HU16**, anular compras y producciones con motivo y reversión transaccional.
No se confunde código implementado con aceptación final del equipo.
La búsqueda HU15 ya está integrada; no volver a aplicar el ZIP 13.

Documento actualizado:
https://docs.google.com/document/d/16irmJz7h_jn2CRlrQ0cwJ9QwZHHhkKEhedrHbiGZX3A/edit
