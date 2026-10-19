<?php
/**
 * Registra el ingreso de un ingrediente comprado.
 * Se ejecuta dentro de guardar(), que administra la transacción.
 */
function registrarCompra(): void {
    // Evita que valores como "1abc" se transformen silenciosamente en el ID 1.
    $proveedorTexto = trim((string)($_POST['proveedor'] ?? ''));
    $ingredienteTexto = trim((string)($_POST['ingrediente'] ?? ''));
    if (!ctype_digit($proveedorTexto) || !ctype_digit($ingredienteTexto)) {
        throw new RuntimeException('Seleccioná un proveedor y un ingrediente válidos.');
    }
    $proveedorId = (int)$proveedorTexto;
    $ingredienteId = (int)$ingredienteTexto;
    if ($proveedorId <= 0 || $ingredienteId <= 0) {
        throw new RuntimeException('Seleccioná un proveedor y un ingrediente.');
    }

    $cantidad = numero($_POST['cantidad'] ?? '', 0.001, 999999, 3);
    $costo = numero($_POST['costo_total'] ?? '', 0, 999999999.99, 2);
    $comprobante = texto('comprobante', 80, false);

    // Solo se permite comprar a proveedores activos.
    $proveedor = consulta('SELECT id, nombre FROM proveedores WHERE id=? AND activo=1 FOR UPDATE', [$proveedorId])->fetch();
    if (!$proveedor) throw new RuntimeException('El proveedor no existe o está inactivo.');

    // Bloquear la fila evita perder actualizaciones simultáneas del stock.
    $ingrediente = consulta('SELECT id, nombre, unidad, stock FROM ingredientes WHERE id=? AND activo=1 FOR UPDATE', [$ingredienteId])->fetch();
    if (!$ingrediente) throw new RuntimeException('El ingrediente no existe o está inactivo.');
    if ($ingrediente['unidad'] === 'unidad' && floor($cantidad) !== $cantidad) {
        throw new RuntimeException('Para ingredientes en unidades ingresá cantidades enteras.');
    }
    if ((float)$ingrediente['stock'] + $cantidad > 999999999.999) {
        throw new RuntimeException('La cantidad supera el máximo permitido para el stock.');
    }

    consulta(
        'INSERT INTO compras (proveedor_id, ingrediente_id, cantidad, costo_total, comprobante, persona_id)
         VALUES (?,?,?,?,?,?)',
        [$proveedorId, $ingredienteId, $cantidad, $costo, $comprobante, $_SESSION['persona']]
    );
    $compraId = (int)db()->lastInsertId();
    consulta('UPDATE ingredientes SET stock=stock+? WHERE id=?', [$cantidad, $ingredienteId]);
    registrar(
        'compras',
        'Compra #' . $compraId . ': ingresó ' . n($cantidad) . ' ' . $ingrediente['unidad'] .
        ' de ' . $ingrediente['nombre'] . ' · proveedor: ' . $proveedor['nombre'] .
        ($comprobante !== '' ? ' · comprobante: ' . $comprobante : '')
    );
}
