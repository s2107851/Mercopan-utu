# Commit 10_exportaciones: Exportación CSV

Base revisada: s2107851/Mercopan-utu, e7865a9aa094d83b5a331b6f2ea483941a92185a.
Implementación preparada con asistencia de IA. Cada integrante debe revisar y comprender su cambio.

## Problema y comportamiento

- Descargar todas las ventas y compras del período elegido desde Resumen.
- Descargar todas las coincidencias del historial, incluyendo otras páginas.
- Conservar tildes, comillas, separadores y saltos de línea en CSV UTF-8.
- Neutralizar texto que podría interpretarse como fórmula al abrirlo en una planilla.
- Rechazar tipos y fechas inválidos antes de iniciar la descarga.

## Instalación

Requiere 08 y 09. Copiar archivos/ en la raíz, reemplazando reportes.php e historial.php. Los botones de descarga quedan en Resumen y en Historial.
No se agregan tablas ni se ejecutan migraciones; se utilizan los datos existentes.

## Archivos

- `exportar.php`
- `historial.php`
- `includes/csv.php`
- `reportes.php`
- `tests/consultas.php`

## Criterios de aceptación

- Descargar todas las ventas y compras del período elegido desde Resumen.
- Descargar todas las coincidencias del historial, incluyendo otras páginas.
- Conservar tildes, comillas, separadores y saltos de línea en CSV UTF-8.
- Neutralizar texto que podría interpretarse como fórmula al abrirlo en una planilla.
- Rechazar tipos y fechas inválidos antes de iniciar la descarga.

## Verificación manual en XAMPP

Descargar ventas y comprobar que coincidan con el período y total del resumen. Descargar compras e historial filtrado. Abrir en Excel o importar en Google Sheets con separador punto y coma. Si una planilla interpreta los decimales como texto, elegir una configuración regional que use punto decimal al importar. Probar exportar.php?tipo=incorrecto: debe mostrar error HTTP 400, sin CSV.

## Validación de hecho

Se analizó la sintaxis de los nueve archivos PHP de las entregas con php-parser configurado para PHP 8.0, sin errores. Esto no reemplaza la ejecución en PHP.
Se revisó la integración con las tablas, funciones y navegación de la base indicada.
Las consultas del resumen se comprobaron con cuatro casos sobre SQLite: ambos límites del mes, exclusión del mes siguiente, agrupación por producto y período vacío.
La ejecución PHP/MySQL y la revisión visual en XAMPP quedan pendientes de realizar en el equipo del grupo; no se presentan como aprobadas.

Ejecutar desde la raíz: `C:\xampp\php\php.exe tests\consultas.php`.
El script comprueba fechas, parámetros y serialización CSV sin modificar la base.
Las compras usan el nombre actual del responsable porque su tabla no guarda una copia histórica; la cabecera del CSV lo aclara. Los nombres de productos y proveedores también son los actuales del catálogo.
Los CSV son exportaciones de consulta, no copias completas de respaldo de la base.
