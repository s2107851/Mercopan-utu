# Commit 09_historial: Historial con filtros

Base revisada: s2107851/Mercopan-utu, e7865a9aa094d83b5a331b6f2ea483941a92185a.
Implementación preparada con asistencia de IA. Cada integrante debe revisar y comprender su cambio.

## Problema y comportamiento

- Filtrar por intervalo, responsable, módulo y texto literal en el detalle.
- Incluir responsables inactivos para consultar sus acciones anteriores.
- Mostrar el nombre histórico del responsable y el total de coincidencias.
- Paginar de a 50 resultados conservando todos los filtros.

## Instalación

Requiere aplicar primero 08. Copiar archivos/ en la raíz y reemplazar historial.php. Abrir Historial; por defecto se muestra el mes actual.
No se agregan tablas ni se ejecutan migraciones; se utilizan los datos existentes.

## Archivos

- `historial.php`
- `includes/filtros_historial.php`

## Criterios de aceptación

- Filtrar por intervalo, responsable, módulo y texto literal en el detalle.
- Incluir responsables inactivos para consultar sus acciones anteriores.
- Mostrar el nombre histórico del responsable y el total de coincidencias.
- Paginar de a 50 resultados conservando todos los filtros.

## Verificación manual en XAMPP

Buscar por cada filtro y luego combinarlos. Una persona desactivada debe seguir apareciendo en el selector. Buscar un texto inexistente: cero resultados. Con más de 50 coincidencias, avanzar y volver verificando que se conservan filtros. Limpiar filtros vuelve al mes actual.

## Validación de hecho

Se analizó la sintaxis de los nueve archivos PHP de las entregas con php-parser configurado para PHP 8.0, sin errores. Esto no reemplaza la ejecución en PHP.
Se revisó la integración con las tablas, funciones y navegación de la base indicada.
Las consultas del resumen se comprobaron con cuatro casos sobre SQLite: ambos límites del mes, exclusión del mes siguiente, agrupación por producto y período vacío.
La ejecución PHP/MySQL y la revisión visual en XAMPP quedan pendientes de realizar en el equipo del grupo; no se presentan como aprobadas.
