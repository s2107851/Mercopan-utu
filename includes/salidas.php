<?php
// Ejecutar dentro de guardar(): movimiento, stock e historial se confirman juntos.
function registrarSalida(): void {
    $tipo = texto('tipo', 8);
    if (!in_array($tipo, ['perdida','donacion'], true)) throw new RuntimeException('Elegí pérdida o donación.');
    $motivo = texto('motivo', 300);
    $id = filter_var($_POST['producto'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id === false) throw new RuntimeException('Seleccioná un producto válido.');
    $cantidad = numero($_POST['cantidad'] ?? '', 0.001, 999999, 3);
    $producto = consulta('SELECT * FROM productos WHERE id=? AND activo=1 FOR UPDATE', [$id])->fetch();
    if (!$producto) throw new RuntimeException('El producto no existe o está inactivo.');
    if ($producto['unidad'] === 'unidad' && floor($cantidad) !== $cantidad) throw new RuntimeException('La cantidad en unidades debe ser entera.');
    // Comparar y calcular en milésimas enteras evita diferencias de coma flotante.
    $antes = (int)round((float)$producto['stock']*1000);
    $retirar = (int)round($cantidad*1000);
    if ($retirar > $antes) throw new RuntimeException('Stock insuficiente. Hay ' . n($producto['stock']) . ' ' . $producto['unidad'] . '.');
    $cantidadSQL = number_format($retirar/1000,3,'.','');
    $nuevo = number_format(($antes-$retirar)/1000,3,'.','');
    $persona = responsable();
    if (!$persona) throw new RuntimeException('Elegí un responsable.');
    consulta('INSERT INTO salidas (producto_id,producto_nombre,unidad,tipo,cantidad,motivo,stock_anterior,stock_nuevo,persona_id,persona_nombre) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$id,$producto['nombre'],$producto['unidad'],$tipo,$cantidadSQL,$motivo,$producto['stock'],$nuevo,$persona['id'],$persona['nombre']]);
    $salidaId = db()->lastInsertId();
    $cambio = consulta('UPDATE productos SET stock=stock-? WHERE id=? AND stock>=?', [$cantidadSQL,$id,$cantidadSQL]);
    if ($cambio->rowCount() !== 1) throw new RuntimeException('El stock cambió. Volvé a intentarlo.');
    registrar('salidas', ucfirst($tipo) . ' #' . $salidaId . ': ' . n($cantidad) . ' ' . $producto['unidad'] . ' de ' . $producto['nombre'] . ' · ' . $motivo);
}
