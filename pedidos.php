<?php
require 'includes/inicio.php';
require 'includes/pedidos.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar();
        $accion = texto('accion', 10);
        if (!in_array($accion, ['crear','estado'], true)) throw new RuntimeException('Acción no válida.');
        guardar($accion === 'crear' ? 'crearPedido' : 'cambiarPedido');
        ir('pedidos.php', 'Pedido guardado. Si se entregó, su venta ya quedó registrada.');
    } catch (Throwable $ex) { $error = mensajeError($ex); }
}
$estado = (string)($_GET['estado'] ?? 'pendiente');
$estados = ['pendiente'=>'Pendiente','listo'=>'Listo','entregado'=>'Entregado','cancelado'=>'Cancelado'];
if ($estado !== '' && !isset($estados[$estado])) { $estado = 'pendiente'; $error = 'Estado de búsqueda no válido.'; }
$where = $estado === '' ? '' : ' WHERE estado=?';
$params = $estado === '' ? [] : [$estado];
$total = (int)consulta('SELECT COUNT(*) FROM pedidos' . $where, $params)->fetchColumn();
$paginas = max(1, (int)ceil($total/25));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$filas = consulta('SELECT * FROM pedidos' . $where . ' ORDER BY fecha_entrega,id LIMIT 25 OFFSET ' . (($pagina-1)*25), $params)->fetchAll();
$productos = consulta('SELECT id,CONCAT(nombre," · ",unidad) nombre FROM productos WHERE activo=1 ORDER BY nombre')->fetchAll();
$titulo = 'Pedidos de clientes'; require 'includes/header.php';
?>
<p class="descripcion">Anotá un producto por pedido. Marcarlo como listo no reserva stock. Al entregar, se registra la venta al precio vigente y se descuenta el producto: no lo cargues otra vez en Ventas.</p>
<div class="dos"><section class="panel"><h2>Nuevo pedido</h2>
<?php if (!$productos): ?><p class="aviso">Primero agregá un producto activo en Productos.</p><?php else: ?>
<form method="post"><?php formulario(); ?><input type="hidden" name="accion" value="crear">
<label for="cliente">Nombre del cliente</label><input id="cliente" name="cliente" maxlength="100" required value="<?= valor('cliente') ?>">
<label for="telefono">Teléfono (opcional)</label><input id="telefono" name="telefono" maxlength="40" value="<?= valor('telefono') ?>">
<label for="producto">Producto</label><select id="producto" name="producto" required><option value="">Elegir producto</option><?php opciones($productos,$_POST['producto'] ?? 0); ?></select>
<label for="cantidad">Cantidad según la unidad del producto</label><input id="cantidad" type="number" name="cantidad" min="0.001" max="999999" step="0.001" required value="<?= valor('cantidad',1) ?>">
<label for="fecha_entrega">Fecha de entrega</label><input id="fecha_entrega" type="date" name="fecha_entrega" min="<?= e(date('Y-m-d')) ?>" required value="<?= valor('fecha_entrega',date('Y-m-d')) ?>">
<label for="notas">Observaciones (opcional)</label><textarea id="notas" name="notas" maxlength="300"><?= valor('notas') ?></textarea>
<button class="amarillo">Guardar pedido</button></form><?php endif; ?></section>
<section class="panel"><h2>Consultar pedidos</h2><form method="get" class="filtros"><div><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach ($estados as $clave=>$nombre): ?><option value="<?= e($clave) ?>" <?= $estado===$clave?'selected':'' ?>><?= e($nombre) ?></option><?php endforeach; ?></select></div><button>Consultar</button></form>
<p><?= e($total) ?> pedidos · Página <?= e($pagina) ?> de <?= e($paginas) ?></p><div class="tabla"><table><thead><tr><th>Cliente / entrega</th><th>Producto</th><th>Estado / acciones</th></tr></thead><tbody>
<?php foreach ($filas as $r): ?><tr class="<?= in_array($r['estado'],['pendiente','listo'],true) && $r['fecha_entrega']<date('Y-m-d')?'bajo':'' ?>">
<td>#<?= e($r['id']) ?> · <?= e($r['cliente']) ?><small><?= e($r['telefono']) ?></small><strong><?= e($r['fecha_entrega']) ?></strong><?php if (in_array($r['estado'],['pendiente','listo'],true) && $r['fecha_entrega']<date('Y-m-d')): ?><small>Entrega atrasada</small><?php endif; ?><small>Anotó: <?= e($r['persona_nombre']) ?></small></td>
<td><?= e($r['producto_nombre']) ?><small><?= n($r['cantidad']) ?> <?= e($r['unidad']) ?></small><small><?= e($r['notas']) ?></small></td><td><span class="badge"><?= e($estados[$r['estado']]) ?></span>
<?php if ($r['venta_id']): ?><small>Venta #<?= e($r['venta_id']) ?> registrada</small><?php endif; ?>
<?php foreach (['pendiente'=>['listo'=>'Marcar listo','cancelado'=>'Cancelar pedido'],'listo'=>['entregado'=>'Entregar y registrar venta','cancelado'=>'Cancelar pedido']][$r['estado']] ?? [] as $s=>$etiqueta): ?>
<form method="post" data-confirmar="<?= $s==='entregado'?'¿Entregar y registrar la venta? Se descontará el stock al precio vigente.':'¿Confirmás este cambio de estado?' ?>"><?php formulario(); ?><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= e($r['id']) ?>"><input type="hidden" name="estado" value="<?= e($s) ?>"><button class="secundario"><?= e($etiqueta) ?></button></form><?php endforeach; ?></td></tr><?php endforeach; ?>
<?php if (!$filas): ?><tr><td colspan="3" class="vacio">Sin pedidos para este estado.</td></tr><?php endif; ?></tbody></table></div>
<div class="acciones"><?php if ($pagina>1): ?><a class="boton secundario" href="?<?= e(http_build_query(['estado'=>$estado,'pagina'=>$pagina-1])) ?>">Anterior</a><?php endif; ?><?php if ($pagina<$paginas): ?><a class="boton secundario" href="?<?= e(http_build_query(['estado'=>$estado,'pagina'=>$pagina+1])) ?>">Siguiente</a><?php endif; ?></div></section></div>
<?php require 'includes/footer.php'; ?>
