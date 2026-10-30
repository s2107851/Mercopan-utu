# Entrega 15 — HU16: anular registros incorrectos

Base: main@0534d0a2a5041bcdcdd0a4a4c5afa00bc85f0e7f, revisada el 29/10/2026.
Fuente de requisitos: https://docs.google.com/document/d/16irmJz7h_jn2CRlrQ0cwJ9QwZHHhkKEhedrHbiGZX3A/edit

## Resultado de la revisión de historias

El documento conserva 21 historias. Antes de este paquete había 20 implementadas;
la única parcial era HU16. Su baja lógica de catálogos ya existía; faltaba anular
compras y producciones conservando el original, motivo y responsable, con reversión
transaccional una sola vez y sin stock negativo. Este paquete implementa esa parte.
Con el paquete aplicado quedan 21 historias implementadas y ninguna pendiente de
código según el alcance documentado. No significa que las 21 estén aceptadas como
Hechas: el equipo debe ejecutar la validación en su XAMPP y registrar evidencia.
No se modificó el Google Doc ni se publicó este commit en GitHub: lo aplica el compañero.

## Criterios y evidencia

| Criterio HU16 | Implementación |
| --- | --- |
| Baja lógica de catálogos con confirmación | Funcionalidad existente en catálogos y productos; se conserva. |
| Conservar compra/producción original y motivo/responsable | Tabla anulaciones, fecha y nombre histórico; no se elimina el original. |
| Reversión única y transaccional | Bloqueo del original, clave única tipo/registro, guardar(), controles de stock y auditoría en la misma transacción. |

Compras retira lo ingresado; Producción retira lo elaborado y devuelve los consumos
históricos, no la receta actual. Un desborde o stock insuficiente revierte todo.
Un catálogo inactivo puede revertirse sin reactivarlo.
Las compras asociadas a una factura se anulan en conjunto desde Facturas. La cabecera
y los renglones originales permanecen; la anulación no libera el número confirmado.
Se muestra Anulada y el detalle del motivo en las pantallas correspondientes.
Resumen excluye compras anuladas del período original; CSV las conserva con estado,
fecha, motivo y responsable de anulación. Para totalizar CSV, filtrar estado vigente.
No incluye devoluciones, notas de crédito fiscales, anulación de ventas o autenticación.
El responsable sigue siendo declarado como en el resto del proyecto.

## Aplicación y base de datos

1. Actualizar el repositorio y comprobar que incluye 0534d0a (entrega 14).
2. Copiar el contenido de COPIAR_AL_REPOSITORIO en la raíz, conservando carpetas.
3. Respaldar la base de trabajo en phpMyAdmin.
4. Ejecutar instalar.php actualizado desde la PC de XAMPP y pulsar Preparar base de datos.
   Alternativa: importar solo database/anulaciones.sql sobre la base existente.
   Es una migración aditiva, repetible y sin datos de ejemplo. Elegir un método.
5. Ejecutar pruebas y revisar diferencias antes del commit.

No importar un SQL de instalación completa ni sustituir config.local.php.
El sistema solicita preparar/actualizar la base mientras falte la tabla anulaciones.

## Validación automatizada

PHP 8.3.6, PDO MySQL y MariaDB 10.11.14, sin adaptar SQL ni lógica a otro motor.
- 31 pruebas de HU16 aprobadas: compras, producciones, facturas, repetición,
  stock insuficiente, rollback completo, receta editada, desborde, responsables,
  migración repetida e integración con Resumen.
- 98 pruebas de regresión: Facturas 40, Compras 22, Producción 24 y Consultas 12.
- 12 verificaciones HTTP aprobadas: pantallas, envío, reenvío, escape HTML, CSV, estado e historial.
- Sintaxis correcta en 48 archivos PHP y revisión git diff --check.
La salida se adjunta en EVIDENCIA_PRUEBAS.txt fuera de los archivos para copiar.
Las bases de pruebas se crean con nombre aleatorio y se eliminan al terminar.
El usuario de MySQL requiere CREATE/DROP DATABASE para ejecutar los tests.
No se realizó aceptación visual en el navegador del equipo ni prueba en su XAMPP.
No se ejecutó una prueba de carga concurrente; la protección usa bloqueos de fila y
la clave única, además de los tokens existentes del formulario.

## Prueba manual / definición de hecho

1. En una base de prueba, ingresar 2 kg de harina. Anular esa compra con motivo:
   vuelve el stock anterior y se conservan original y auditoría.
2. Reenviar o abrir desde otra pestaña: no vuelve a descontar. Sin motivo o sin
   confirmar, rechaza. Probar sin stock suficiente: no altera ningún registro.
3. Elaborar un lote. Cambiar la receta y anular la producción: devuelve exactamente
   los consumos originales y retira lo elaborado. Si falta producto, rechaza.
4. Confirmar factura de dos ingredientes. Anularla y comprobar ambos stocks,
   compras asociadas, estado visible, Resumen, CSV e Historial. Si falta el segundo
   ingrediente no debe descontar tampoco el primero.
5. Cambiar responsable entre apertura y envío: debe rechazar. Revisar el motivo
   y el nombre histórico luego de renombrar al responsable.
6. Desactivar un catálogo y comprobar que mantiene relaciones e historial.
7. Revisar diseño en escritorio y teléfono y completar: commit ___; fecha ___;
   evidencia ___; resultado ___; integrante revisor ___. Solo entonces marcar Hecha.
