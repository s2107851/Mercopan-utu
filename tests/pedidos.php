<?php
require __DIR__ . '/soporte_modulos.php';
require __DIR__ . '/../includes/pedidos.php';
ejecutarPruebasModulo(['pedidos'], function () {
    $valido = ['cliente'=>'Cliente ejemplo','telefono'=>'099000000','producto'=>'1','cantidad'=>'3','fecha_entrega'=>date('Y-m-d'),'notas'=>'Sin sal'];
    $_POST = $valido; guardar('crearPedido');
    comprobarModulo('Pedido pendiente sin reservar existencias', consulta('SELECT estado FROM pedidos WHERE id=1')->fetchColumn() === 'pendiente' && (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn() === 20.0);
    $_POST = ['id'=>'1','estado'=>'entregado']; rechazarModulo('pedidos','No salta de pendiente a entregado','cambiarPedido');
    $_POST = ['id'=>'1','estado'=>'listo']; guardar('cambiarPedido');
    comprobarModulo('Listo no modifica stock ni ventas', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn() === 20.0 && (int)consulta('SELECT COUNT(*) FROM ventas')->fetchColumn() === 0);
    consulta('UPDATE productos SET stock=2 WHERE id=1');
    $_POST = ['id'=>'1','estado'=>'entregado']; rechazarModulo('pedidos','Sin stock queda listo, sin venta ni historial parcial','cambiarPedido');
    consulta('UPDATE productos SET stock=20,precio=30 WHERE id=1');
    // Fuerza un fallo posterior a la venta y al descuento para verificar rollback.
    db()->exec("CREATE TRIGGER fallo_pedido BEFORE UPDATE ON pedidos FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
    rechazarModulo('pedidos','Fallo al actualizar pedido revierte venta, stock e historial','cambiarPedido');
    db()->exec('DROP TRIGGER fallo_pedido');
    guardar('cambiarPedido');
    $p = consulta('SELECT * FROM pedidos WHERE id=1')->fetch();
    $v = consulta('SELECT * FROM ventas WHERE id=?', [$p['venta_id']])->fetch();
    comprobarModulo('Entrega genera exactamente una venta al precio vigente', $p['estado'] === 'entregado' && (float)$v['total'] === 90.0 && (int)consulta('SELECT COUNT(*) FROM ventas')->fetchColumn() === 1);
    comprobarModulo('Descuenta stock y vincula la venta correcta', (int)$v['producto_id'] === 1 && (float)$v['cantidad'] === 3.0 && (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn() === 17.0);
    comprobarModulo('Historial de pedido y venta', (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='pedidos'")->fetchColumn() === 3 && (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='ventas'")->fetchColumn() === 1);
    rechazarModulo('pedidos','No duplica la entrega','cambiarPedido');
    $_POST = ['id'=>'1','estado'=>'cancelado']; rechazarModulo('pedidos','No cancela un pedido entregado','cambiarPedido');
    $_POST = array_replace($valido,['producto'=>'2','cantidad'=>'0.750']); guardar('crearPedido');
    $_POST = ['id'=>'2','estado'=>'cancelado']; guardar('cambiarPedido');
    comprobarModulo('Cancelar no altera stock', (float)consulta('SELECT stock FROM productos WHERE id=2')->fetchColumn() === 5.5);
    $_POST = ['id'=>'2','estado'=>'listo']; rechazarModulo('pedidos','No reabre cancelados','cambiarPedido');
    foreach ([['cliente'=>''], ['cantidad'=>'0'], ['cantidad'=>'1.5'], ['producto'=>'999999'], ['fecha_entrega'=>'2026-02-30'], ['fecha_entrega'=>date('Y-m-d',strtotime('-1 day'))]] as $c) {
        $_POST = array_replace($valido,$c); rechazarModulo('pedidos','Rechaza alta inválida ' . json_encode($c),'crearPedido');
    }
    consulta('UPDATE productos SET activo=0 WHERE id=1'); $_POST=$valido; rechazarModulo('pedidos','No encarga un producto inactivo','crearPedido');
    consulta('UPDATE productos SET activo=1 WHERE id=1');
    $_SESSION['persona']=99999; rechazarModulo('pedidos','Sin responsable no crea pedido','crearPedido'); $_SESSION['persona']=1;
    // La función de venta sigue admitiendo el formulario anterior sin argumentos.
    $_POST=['producto'=>'1','cantidad'=>'1']; guardar('registrarVenta');
    comprobarModulo('Ventas existentes siguen funcionando', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn()===16.0);
    db()->exec(file_get_contents(__DIR__ . '/../database/pedidos.sql'));
    comprobarModulo('Instalación repetible conserva pedidos', (int)consulta('SELECT COUNT(*) FROM pedidos')->fetchColumn()===2);
});
