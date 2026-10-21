<?php
/**
 * Reemplaza la cantidad registrada por el conteo físico de un ingrediente.
 * Se invoca siempre dentro de guardar(), para conservar stock, ajuste e historial juntos.
 */
function registrarAjusteInventario(): void {
    $idTexto = trim((string)($_POST['ingrediente'] ?? ''));
    if (!ctype_digit($idTexto) || (int)$idTexto < 1) {
        throw new RuntimeException('Seleccioná un ingrediente válido.');
    }
    $id = (int)$idTexto;
    $stockReal = numero($_POST['stock_real'] ?? '', 0, 999999999.999, 3);
    $motivo = texto('motivo', 250);

    // El bloqueo mantiene consistente el conteo aunque otra operación modifique el stock.
    $ingrediente = consulta(
        'SELECT id, nombre, unidad, stock FROM ingredientes WHERE id=? AND activo=1 FOR UPDATE',
        [$id]
    )->fetch();
    if (!$ingrediente) throw new RuntimeException('El ingrediente no existe o está inactivo.');
    if ($ingrediente['unidad'] === 'unidad' && floor($stockReal) !== $stockReal) {
        throw new RuntimeException('El stock en unidades debe ser un número entero.');
    }

    $anterior = round((float)$ingrediente['stock'], 3);
    $nuevo = round($stockReal, 3);
    $diferencia = round($nuevo - $anterior, 3);
    if ($diferencia === 0.0) {
        throw new RuntimeException('El conteo coincide con el stock actual. No hace falta ajustarlo.');
    }

    // No modifica compras ni producciones previas: deja un movimiento independiente.
    consulta('UPDATE ingredientes SET stock=? WHERE id=?', [$nuevo, $id]);
    consulta(
        'INSERT INTO ajustes_inventario (ingrediente_id, stock_anterior, stock_nuevo, diferencia, motivo, persona_id)
         VALUES (?,?,?,?,?,?)',
        [$id, $anterior, $nuevo, $diferencia, $motivo, $_SESSION['persona'] ?? 0]
    );
    $ajusteId = (int)db()->lastInsertId();
    registrar('ajustes', 'Ajuste #' . $ajusteId . ' de ' . $ingrediente['nombre'] .
        ': ' . n($anterior) . ' → ' . n($nuevo) . ' ' . $ingrediente['unidad'] .
        '. Motivo: ' . $motivo);
}
