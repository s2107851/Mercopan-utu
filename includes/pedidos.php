<?php
require_once __DIR__ . '/ventas.php';
function crearPedido(): void {
    $cliente = texto('cliente');
    $telefono = texto('telefono', 40, false);
    $notas = texto('notas', 300, false);
    $entrega = fecha(texto('fecha_entrega', 10));
    if ($entrega < date('Y-m-d')) throw new RuntimeException('La entrega no puede ser anterior a hoy.');
    $id = filter_var($_POST['producto'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id === false) throw new RuntimeException('Seleccioná un producto válido.');
    $cantidad = numero($_POST['cantidad'] ?? '', 0.001, 999999, 3);
    $producto = consulta('SELECT id,nombre,unidad FROM productos WHERE id=? AND activo=1 FOR UPDATE', [$id])->fetch();
    if (!$producto) throw new RuntimeException('El producto no existe o está inactivo.');
    if ($producto['unidad'] === 'unidad' && floor($cantidad) !== $cantidad) throw new RuntimeException('La cantidad en unidades debe ser entera.');
    $persona = responsable();
    if (!$persona) throw new RuntimeException('Elegí un responsable.');
    consulta('INSERT INTO pedidos (cliente,telefono,producto_id,producto_nombre,unidad,cantidad,fecha_entrega,notas,persona_id,persona_nombre) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$cliente,$telefono,$id,$producto['nombre'],$producto['unidad'],number_format($cantidad,3,'.',''),$entrega,$notas,$persona['id'],$persona['nombre']]);
    $pedidoId = db()->lastInsertId();
    registrar('pedidos', 'Creó pedido #' . $pedidoId . ': ' . n($cantidad) . ' ' . $producto['unidad'] . ' de ' . $producto['nombre']);
}
function cambiarPedido(): void {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id === false) throw new RuntimeException('Pedido no válido.');
    $nuevo = texto('estado', 10);
    $pedido = consulta('SELECT * FROM pedidos WHERE id=? FOR UPDATE', [$id])->fetch();
    if (!$pedido) throw new RuntimeException('El pedido no existe.');
    $permitidos = ['pendiente'=>['listo','cancelado'], 'listo'=>['entregado','cancelado']];
    if (!in_array($nuevo, $permitidos[$pedido['estado']] ?? [], true)) throw new RuntimeException('Ese cambio ya fue realizado o no está permitido.');
    $ventaId = null;
    if ($nuevo === 'entregado') {
        // Reutiliza las reglas de ventas en la MISMA transacción del pedido.
        $ventaId = registrarVenta(['producto'=>(string)$pedido['producto_id'], 'cantidad'=>$pedido['cantidad']]);

    }
    consulta('UPDATE pedidos SET estado=?,venta_id=? WHERE id=?', [$nuevo,$ventaId,$id]);
    registrar('pedidos', 'Pedido #' . $id . ': ' . $pedido['estado'] . ' → ' . $nuevo . ($ventaId ? ' · Venta #' . $ventaId : ''));
}
