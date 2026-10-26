# Commit 13 · HU15: búsqueda por nombre

Base publicada revisada: s2107851/Mercopan-utu, rama main, d4625eb549763691ede8737f751867dbc753e57d (26/10/2026).
Código preparado con asistencia de IA. Cada integrante debe comprender y probar su cambio antes de publicarlo.

## Problema y comportamiento

Ingredientes y Productos mostraban el catálogo completo sin búsqueda. Ahora permiten escribir un nombre completo o parcial, consultar coincidencias y limpiar el filtro. Conservan cantidades, unidades, estados y acciones de edición/desactivación. Los registros inactivos también se encuentran y aparecen después de los activos.

Los símbolos %, _ y ! se buscan literalmente; los valores se separan del SQL con parámetros. Se admiten hasta 100 bytes de texto UTF-8. El comportamiento de mayúsculas y tildes depende de la intercalación de MySQL; el instalador crea la base con utf8mb4_unicode_ci.

## Historia y criterios de aceptación

HU15 original: Como empleado, quiero buscar un producto por su nombre para encontrar rápidamente su información y cantidad disponible. Se conserva la dificultad estimada original de 2.

1. Buscar por nombre completo o parcial en Ingredientes y Productos.
2. Mostrar las coincidencias con nombre, cantidad, unidad y estado sin modificar registros.
3. Informar ausencia de coincidencias y restaurar el catálogo al limpiar la búsqueda.
4. Mantener las acciones existentes; al editar desde resultados se conserva el filtro hasta guardar/cancelar. Después de guardar se vuelve al listado sin filtro, como antes.

## Archivos

- includes/busqueda_catalogo.php: validación y consultas parametrizadas compartidas.
- includes/catalogos.php: búsqueda solo para Ingredientes; conserva Personal y Proveedores.
- productos.php: buscador en productos terminados.
- tests/busqueda.php: pruebas de consultas, entradas y ausencia de cambios en datos.
- README.md y este documento: uso y validación.

## Instalación

1. Actualizar el repositorio hasta los commits 11 y 12 ya publicados. Guardar cambios propios antes de reemplazar archivos.
2. Copiar el contenido de archivos/ en la raíz del repositorio, conservando carpetas y reemplazando archivos.
3. Iniciar Apache/MySQL y abrir Ingredientes y Productos. No se crean tablas ni se ejecutan migraciones; no hace falta volver al instalador.
4. Realizar las pruebas siguientes y ejecutar los comandos de COMANDOS.txt desde la raíz.

Alternativa: aplicar commit_13.patch con git apply --check y luego git apply. Usar el parche o los archivos completos, no ambos métodos.

## Verificación manual en XAMPP

- Ingredientes: buscar hari con Harina registrada; revisar nombre, stock, unidad, mínimo y estado.
- Productos: buscar pan y después un nombre completo; comprobar stock, precio y estado.
- Buscar texto inexistente: mensaje sin resultados. Vaciar el campo y buscar o pulsar Limpiar búsqueda: listado completo.
- Crear nombres de prueba con %, _ y ! y buscar cada símbolo: no actúan como comodines.
- Buscar un registro inactivo y comprobar su estado. Abrir Editar desde resultados, guardar o cancelar y revisar que las acciones existentes sigan funcionando.
- Revisar Personal y Proveedores: no muestran un nuevo buscador y siguen operativos.
- Probar en móvil/escritorio. Buscar no debe modificar existencias ni generar acciones de éxito en Historial.

## Pruebas automatizadas

Con MySQL iniciado: php tests/busqueda.php
En Windows: C:\xampp\php\php.exe tests\busqueda.php

El script utiliza tests/soporte_modulos.php, ya incluido en los commits 11–12. Crea una base temporal aleatoria y la elimina al terminar. Requiere permiso para CREATE/DROP DATABASE. No ejecutar datos demo manualmente sobre una base de trabajo.

## Validación realizada y definición de hecho

- 42 archivos PHP analizados con php-parser configurado para PHP 8.0, sin errores.
- Los cuatro archivos PHP agregados/modificados pasaron la comprobación de sintaxis con PHP 8.5.10 en WebAssembly.
- 25 comprobaciones funcionales aprobadas con PHP/PDO SQLite en una copia temporal. Se adaptaron únicamente la conexión, creación/eliminación de la base y DDL para SQLite; la lógica y las consultas de búsqueda se ejecutaron sin cambios.
- Ocho comprobaciones del HTML generado aprobadas: coincidencias, filtro vacío, ausencia de coincidencias y búsqueda mal formada en ambas pantallas. No equivalen a una revisión visual de navegador.
- Parche comprobado sobre la base indicada y comparado con los archivos completos.
- Pendiente: ejecutar el script original en MySQL/MariaDB, revisar la intercalación, acciones de edición/desactivación y apariencia en XAMPP, conservar evidencia y obtener revisión del equipo.

Estado: código preparado y validado parcialmente; pendiente de publicación y aceptación. Solo marcar HU15 como Hecha al comprobar todos sus criterios y la definición de hecho del documento de HU.
Registro de cierre: commit ___; fecha ___; entorno ___; prueba/evidencia ___; resultado ___; revisó ___.
