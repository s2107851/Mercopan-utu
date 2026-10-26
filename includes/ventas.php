<?php
/**
 * Registra la venta de un producto terminado. Debe llamarse desde guardar()
 * para conservar venta, existencias e historial en una sola transacción.
 */
function registrarVenta(?array $datos = null): int {
    $datos = $datos ?? $_POST;
    $idTexto = trim((string)($datos['producto'] ?? ''));
    if (!ctype_digit($idTexto) || (int)$idTexto < 1) {
        throw new RuntimeException('Seleccioná un producto válido.');
    }
    $productoId = (int)$idTexto;
    $cantidad = numero($datos['cantidad'] ?? '', 0.001, 999999, 3);

    // La fila queda bloqueada mientras se comprueban y descuentan las existencias.
    $producto = consulta(
        'SELECT id, nombre, unidad, precio, stock FROM productos WHERE id=? AND activo=1 FOR UPDATE',
        [$productoId]
    )->fetch();
    if (!$producto) throw new RuntimeException('El producto no existe o está inactivo.');
    if ($producto['unidad'] === 'unidad' && floor($cantidad) !== $cantidad) {
        throw new RuntimeException('Los productos vendidos por unidad requieren una cantidad entera.');
    }
    if ((float)$producto['stock'] + 0.0000001 < $cantidad) {
        throw new RuntimeException('Stock insuficiente de ' . $producto['nombre'] .
            '. Hay ' . n($producto['stock']) . ' ' . $producto['unidad'] . ' disponibles.');
    }

    // Precio tomado del catálogo, no del navegador; guardamos copia histórica.
    $precio = numero($producto['precio'], 0.01, 999999, 2);
    $precioCentavos = (int)round($precio * 100);
    $cantidadMilesimas = (int)round($cantidad * 1000);
    $totalCentavos = intdiv($precioCentavos * $cantidadMilesimas + 500, 1000);
    $precioGuardado = number_format($precioCentavos / 100, 2, '.', '');
    $totalGuardado = number_format($totalCentavos / 100, 2, '.', '');
    $cantidadGuardada = number_format($cantidad, 3, '.', '');

    $persona = responsable();
    if (!$persona) throw new RuntimeException('Elegí un responsable antes de registrar una venta.');

    consulta(
        'INSERT INTO ventas (producto_id, cantidad, precio_unitario, total, persona_id, persona_nombre)
         VALUES (?,?,?,?,?,?)',
        [$productoId, $cantidadGuardada, $precioGuardado, $totalGuardado, $persona['id'], $persona['nombre']]
    );
    $ventaId = (int)db()->lastInsertId();
    $actualizacion = consulta(
        'UPDATE productos SET stock=stock-? WHERE id=? AND stock>=?',
        [$cantidadGuardada, $productoId, $cantidadGuardada]
    );
    if ($actualizacion->rowCount() !== 1) {
        throw new RuntimeException('El stock cambió durante la venta. Volvé a intentarlo.');
    }
    registrar(
        'ventas',
        'Venta #' . $ventaId . ': ' . n($cantidad) . ' ' . $producto['unidad'] .
        ' de ' . $producto['nombre'] . ' · $ ' . n($totalGuardado, 2)
    );
    return $ventaId;
}

