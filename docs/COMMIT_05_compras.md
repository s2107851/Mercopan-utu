# Commit 05 — Registro de compras e ingreso de stock

Base: `main`, commit `b915fbdf522f0fe74064b4a5797d9b8954e4342d` (5/10/2026).

## Qué incorpora

- Pantalla `compras.php` para registrar una entrada manual de ingredientes.
- Selección de proveedor e ingrediente activos.
- Cantidad, costo en pesos uruguayos y número de comprobante opcional.
- Actualización del stock, registro de compra e historial en una sola transacción.
- Fecha y nombre de quien declara la operación; listado filtrable por jornada.
- Validaciones de unidades enteras, cantidades, registros inactivos y stock máximo.
- Prueba de integración `tests/compras.php` con base temporal que se elimina al terminar.

## Archivos del ZIP

Copiar estos archivos a la raíz del repositorio **manteniendo las carpetas**:

- `compras.php` (nuevo)
- `includes/compras.php` (nuevo)
- `database/compras.sql` (nuevo)
- `tests/compras.php` (nuevo)
- `README.md` (actualizado)
- `docs/COMMIT_05_compras.md` (nuevo)

No hay que editar a mano `instalar.php`: ya ejecuta `database/compras.sql` cuando el archivo existe.
El menú principal ya detecta `compras.php` automáticamente.

## Instalar y verificar

1. Respaldar la base `mercopan_utu` antes de aplicar cambios.
2. Iniciar Apache y MySQL desde XAMPP.
3. Abrir `http://localhost/Mercopan-utu/instalar.php` y pulsar **Preparar base de datos**.
4. Ir a `Compras`; elegir un responsable previamente, proveedor e ingrediente activos.
5. Comprar, por ejemplo, **2,5 kg de harina**, a un costo de **$ 1250,45**.
6. Comprobar que el stock aumenta 2,5 kg y que aparecen la compra y el responsable en Historial.
7. Probar una cantidad inválida o un ingrediente inactivo: no debe modificar nada.
8. Probar por terminal: `C:\xampp\php\php.exe tests\compras.php` desde la raíz del proyecto.
9. Comprobar también que producción sigue funcionando con `C:\xampp\php\php.exe tests\produccion.php`.

## Cómo subirlo desde la cuenta del compañero

Luego de probar, el compañero abre terminal dentro del clon actualizado del proyecto:

```bash
git status
git add compras.php includes/compras.php database/compras.sql tests/compras.php README.md docs/COMMIT_05_compras.md
git commit -m "feat: registrar compras e ingresar ingredientes al stock"
git push origin main
```

La identidad del commit depende de los ajustes Git `user.name` y `user.email`,
los cuales deben coincidir con los datos reales del integrante que lo revisó y realizó.
Si hay commits ajenos nuevos en `main`, actualizar primero y resolver cualquier conflicto.

## Alcance (versión UTU)

Esta versión **no** importa imágenes de facturas, no analiza comprobantes con IA y no agrupa varios ingredientes en una misma operación: se ingresa una fila por ingrediente y se puede repetir el número de factura.
No incluye ajuste manual por inventario, devoluciones ni anulación de compras. Esas son tareas futuras.
