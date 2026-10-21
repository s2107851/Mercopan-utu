# Commit 06 — Ajustes manuales de inventario

**Punto de partida:** rama `main` de `s2107851/Mercopan-utu`, último commit revisado `cba3404` del 19/10/2026.

## Funcionalidad nueva

La opción **Ajustes** corrige el stock de un ingrediente tras contarlo físicamente. Se selecciona ingrediente activo, se ingresa **la cantidad real final** y un motivo obligatorio. El sistema:

1. Bloquea el ingrediente durante la operación para evitar inconsistencias.
2. Comprueba que la cantidad no sea negativa, que respete tres decimales y que los artículos en unidades tengan cantidades enteras.
3. Conserva stock anterior, nuevo, diferencia, motivo, fecha e identidad del responsable.
4. Actualiza stock, registra ajuste e historial de acciones en una única transacción.
5. Impide reenviar el mismo formulario mediante el token existente.

Ejemplo: harina 10 kg en el sistema, conteo real 8 kg → stock nuevo 8 kg; diferencia registrada **-2 kg**. La compra y producción original no se editan ni eliminan.

## Archivos de este paquete

**Nuevos:** `ajustes.php`, `includes/ajustes.php`, `database/ajustes.sql`, `tests/ajustes.php`, `docs/COMMIT_06_ajustes_inventario.md`.

**Actualizados:** `instalar.php`, `includes/header.php`, `includes/catalogos.php`, `README.md`.

Copiar los archivos del ZIP **sobre la carpeta raíz del repositorio** respetando subcarpetas. No reemplazar `includes/config.local.php` ni las bases de datos (no vienen en el ZIP).

## Pasos de instalación y pruebas

1. Actualizar la rama local con `git pull --ff-only origin main`, comprobar que `git status` esté limpio y hacer respaldo de `mercopan_utu`.
2. Descomprimir el paquete en la carpeta del proyecto manteniendo las subcarpetas.
3. Iniciar Apache y MySQL de XAMPP. Abrir `http://localhost/Mercopan-utu/instalar.php` y pulsar **Preparar base de datos**. El instalador ejecuta `database/ajustes.sql` y conserva los datos anteriores.
4. Entrar al sistema, **Elegir responsable**, luego **Ajustes**. Probar harina: si figura 100 kg, contar 98,5 kg y usar motivo `Conteo semanal`.
5. Comprobar que **Ingredientes** muestre 98,5 kg; **Ajustes**, 100 → 98,5 y diferencia -1,5; e **Historial** registre el responsable.
6. Probar ajustes al mismo valor, cantidades negativas, más de tres decimales, unidades fraccionarias y falta de motivo: deben rechazarse sin alterar stock.
7. Ejecutar las pruebas automatizadas desde la carpeta del repositorio con MySQL activo:
   - `C:\xampp\php\php.exe tests\ajustes.php`
   - `C:\xampp\php\php.exe tests\compras.php`
   - `C:\xampp\php\php.exe tests\produccion.php`

Las pruebas de ajustes utilizan **una base temporal aleatoria**, creada y eliminada únicamente por el script. Se necesitan permisos para crear bases en MySQL. No usar las pruebas contra una base real.

## Commit desde la cuenta del integrante

Verificar que el compañero haya revisado y ejecutado el código. En su equipo, debe tener configurados su propio nombre y correo vinculado a su cuenta de GitHub (no usar la identidad de otra persona).

```bash
git status
git add ajustes.php includes/ajustes.php database/ajustes.sql tests/ajustes.php docs/COMMIT_06_ajustes_inventario.md instalar.php includes/header.php includes/catalogos.php README.md
git commit -m "feat: agregar ajustes manuales de inventario con trazabilidad"
git push origin main
```

Si hay cambios nuevos en `main` antes de subir, consultar `git status`, `git fetch origin` y resolver la actualización o conflicto antes del push. No usar `git push --force`.

## Límites de esta entrega

- Ajustes **solo de materias primas** (ingredientes), no de productos terminados.
- No incluye escaneo de facturas ni ajustes masivos.
- El registro de responsable es declarativo, **no autentica identidades**; se mantiene la decisión de no utilizar login en la versión UTU.
- La tabla solo registra nuevos ajustes; no reconstruye retroactivamente diferencias antiguas.
