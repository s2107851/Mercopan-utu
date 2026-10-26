<?php
require 'includes/inicio.php';
require 'includes/salidas.php';
require_once 'includes/reportes.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar(); guardar('registrarSalida');
        ir('salidas.php', 'Salida registrada. Se descontó el producto del stock.');
    } catch (Throwable $ex) { $error = mensajeError($ex); }
}
$filas = []; $resumen = []; $total = 0; $pagina = 1; $paginas = 1;
$desde = date('Y-m-01'); $hasta = date('Y-m-d'); $tipo = '';
try {
    [$desde,$hasta,$fin] = periodoReporte($_GET);
    $tipo = (string)($_GET['tipo'] ?? '');
    if (!in_array($tipo, ['', 'perdida','donacion'], true)) throw new RuntimeException('Filtro de salida no válido.');
    $where = ' WHERE fecha>=? AND fecha<?'; $params = [$desde,$fin];
    if ($tipo !== '') { $where .= ' AND tipo=?'; $params[] = $tipo; }
    $total = (int)consulta('SELECT COUNT(*) FROM salidas' . $where, $params)->fetchColumn();
    $paginas = max(1,(int)ceil($total/25));
    $pagina = min($paginas,max(1,(int)($_GET['pagina'] ?? 1)));
    $filas = consulta('SELECT * FROM salidas' . $where . ' ORDER BY fecha DESC,id DESC LIMIT 25 OFFSET ' . (($pagina-1)*25), $params)->fetchAll();
    $resumen = consulta('SELECT tipo,unidad,SUM(cantidad) cantidad FROM salidas' . $where . ' GROUP BY tipo,unidad ORDER BY tipo,unidad', $params)->fetchAll();
} catch (Throwable $ex) { $error = mensajeError($ex); }
$productos = consulta('SELECT id,CONCAT(nombre," · ",stock," ",unidad," disponibles") nombre FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre')->fetchAll();
$titulo = 'Pérdidas y donaciones'; require 'includes/header.php';
?>
<p class="descripcion">Registrá los productos elaborados que se retiran sin vender. No genera ventas ni vuelve a descontar ingredientes.</p>
<div class="dos"><section class="panel"><h2>Registrar salida</h2>
<?php if (!$productos): ?><p class="aviso">No hay productos activos con existencias.</p><?php else: ?>
<form method="post" data-confirmar="¿Confirmás la salida? Se descontará el stock y quedará registrada de forma permanente."><?php formulario(); ?>
<label for="producto">Producto disponible</label><select id="producto" name="producto" required><option value="">Elegir producto</option><?php opciones($productos,$_POST['producto'] ?? 0); ?></select>
<label for="tipo_salida">Tipo de salida</label><select id="tipo_salida" name="tipo"><option value="perdida">Pérdida</option><option value="donacion" <?= ($_POST['tipo'] ?? '')==='donacion'?'selected':'' ?>>Donación</option></select>
<label for="cantidad">Cantidad según la unidad del producto</label><input id="cantidad" name="cantidad" type="number" min="0.001" max="999999" step="0.001" required value="<?= valor('cantidad',1) ?>">
<label for="motivo">Motivo / destino de la donación</label><textarea id="motivo" name="motivo" maxlength="300" required><?= valor('motivo') ?></textarea><p class="ayuda">Ejemplos: producto quemado, rotura o donación al merendero. Las unidades deben ser enteras.</p><button class="amarillo">Confirmar salida</button></form><?php endif; ?></section>
<section class="panel"><h2>Consultar salidas</h2><form method="get" class="filtros">
<div><label for="desde">Desde</label><input id="desde" type="date" name="desde" required value="<?= e($desde) ?>"></div><div><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" required value="<?= e($hasta) ?>"></div>
<div><label for="tipo">Tipo</label><select id="tipo" name="tipo"><option value="">Todos</option><option value="perdida" <?= $tipo==='perdida'?'selected':'' ?>>Pérdidas</option><option value="donacion" <?= $tipo==='donacion'?'selected':'' ?>>Donaciones</option></select></div><button>Consultar</button></form>
<?php foreach ($resumen as $r): ?><p><strong><?= $r['tipo']==='perdida'?'Pérdidas':'Donaciones' ?>: <?= n($r['cantidad']) ?> <?= e($r['unidad']) ?></strong></p><?php endforeach; ?>
<p class="ayuda">Totales de todo el período, separados por tipo y unidad. No representan una valoración económica.</p><p><?= e($total) ?> salidas · Página <?= e($pagina) ?> de <?= e($paginas) ?></p>
<div class="tabla"><table><thead><tr><th>Salida / producto</th><th>Cantidad / stock</th><th>Motivo / responsable</th></tr></thead><tbody>
<?php foreach ($filas as $r): ?><tr><td>#<?= e($r['id']) ?> · <?= $r['tipo']==='perdida'?'Pérdida':'Donación' ?><small><?= e($r['fecha']) ?></small><?= e($r['producto_nombre']) ?></td><td><?= n($r['cantidad']) ?> <?= e($r['unidad']) ?><small>Antes: <?= n($r['stock_anterior']) ?></small><small>Después: <?= n($r['stock_nuevo']) ?></small></td><td><?= e($r['motivo']) ?><small><?= e($r['persona_nombre']) ?></small></td></tr><?php endforeach; ?>
<?php if (!$filas): ?><tr><td colspan="3" class="vacio">Sin salidas para estos filtros.</td></tr><?php endif; ?></tbody></table></div><div class="acciones">
<?php foreach ([$pagina-1=>'Anterior',$pagina+1=>'Siguiente'] as $p=>$texto): if ($p<1 || $p>$paginas) continue; ?><a class="boton secundario" href="?<?= e(http_build_query(['desde'=>$desde,'hasta'=>$hasta,'tipo'=>$tipo,'pagina'=>$p])) ?>"><?= e($texto) ?></a><?php endforeach; ?></div></section></div>
<?php require 'includes/footer.php'; ?>
