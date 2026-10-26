# Commit 08_resumen: Resumen por período

Base revisada: s2107851/Mercopan-utu, e7865a9aa094d83b5a331b6f2ea483941a92185a.
Implementación preparada con asistencia de IA. Cada integrante debe revisar y comprender su cambio.

## Problema y comportamiento

- Consultar ventas y compras entre dos fechas, incluyendo ambos días.
- Ver cantidad de registros y total monetario separado para cada movimiento.
- Agrupar ventas por producto, conservando el total histórico aunque cambie el precio del catálogo.
- Mostrar cero y una tabla vacía cuando no existen ventas; rechazar fechas inválidas o invertidas.

## Instalación

Copiar archivos/ en la raíz. El enlace Resumen aparece automáticamente en el menú y en Inicio. Abrir reportes.php.
No se agregan tablas ni se ejecutan migraciones; se utilizan los datos existentes.

## Archivos

- `includes/reportes.php`
- `reportes.php`

## Criterios de aceptación

- Consultar ventas y compras entre dos fechas, incluyendo ambos días.
- Ver cantidad de registros y total monetario separado para cada movimiento.
- Agrupar ventas por producto, conservando el total histórico aunque cambie el precio del catálogo.
- Mostrar cero y una tabla vacía cuando no existen ventas; rechazar fechas inválidas o invertidas.

## Verificación manual en XAMPP

Crear ventas de 2 unidades a $25 y 3 unidades a $30 para el mismo producto: debe mostrar cantidad 5 e importe $140. Cambiar luego el precio del catálogo: el reporte debe seguir mostrando $140. Filtrar un período vacío: total $0. Invertir fechas: mostrar error.

## Validación de hecho

Se analizó la sintaxis de los nueve archivos PHP de las entregas con php-parser configurado para PHP 8.0, sin errores. Esto no reemplaza la ejecución en PHP.
Se revisó la integración con las tablas, funciones y navegación de la base indicada.
Las consultas del resumen se comprobaron con cuatro casos sobre SQLite: ambos límites del mes, exclusión del mes siguiente, agrupación por producto y período vacío.
La ejecución PHP/MySQL y la revisión visual en XAMPP quedan pendientes de realizar en el equipo del grupo; no se presentan como aprobadas.
