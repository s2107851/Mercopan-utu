<?php
require 'includes/inicio.php';
require_once 'includes/facturas.php';
require_once 'includes/anulaciones.php';
$f=null; $listado=null; $listaError=''; $dia=''; $id=0; $editar=false;
try {
    consulta('SELECT id FROM facturas LIMIT 1');
} catch (Throwable $ex) {
    $error='Falta actualizar la base. Respaldá los datos y ejecutá instalar.php para agregar las tablas de facturas.';
    $titulo='Facturas'; require 'includes/header.php'; require 'includes/footer.php'; exit;
}
try {
    if (isset($_GET['id'])) $id=idFactura($_GET['id']);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verificar();
        $accion=$_POST['accion'] ?? '';
        if (!is_string($accion)) throw new RuntimeException('Acción inválida.');
        $id=empty($_POST['id']) ? 0 : idFactura($_POST['id']);
        $version=$id ? idFactura($_POST['version'] ?? '') : 0;
        if ($accion==='guardar') {
            guardar(function() use (&$id,$version) { $id=guardarBorradorFactura($_POST,$id,$version); });
            ir('facturas.php?id='.$id,'Borrador guardado. Revisá el detalle y confirmalo para ingresar el stock.');
        }
        if (!$id) throw new RuntimeException('Elegí una factura.');
        if ($accion==='confirmar') guardar(function() use($id,$version) { confirmarFactura($id,$version); });
        elseif ($accion==='cancelar') guardar(function() use($id,$version) { cancelarFactura($id,$version); });
        else throw new RuntimeException('Acción inválida.');
        ir('facturas.php?id='.$id,$accion==='confirmar' ? 'Factura confirmada. Stock actualizado una sola vez.' : 'Borrador cancelado.');
    }
} catch (Throwable $ex) { $error=mensajeError($ex); }
try {
    if ($id) {
        $f=obtenerFactura($id);
        if (!$f) throw new RuntimeException('No existe esa factura.');
    }
    $editar=!$f || ($f['estado']==='borrador' && (isset($_GET['editar']) || ($_POST['accion'] ?? '')==='guardar'));
    $dia=isset($_GET['dia']) && $_GET['dia']!=='' ? textoFactura($_GET['dia'],10) : '';
} catch (Throwable $ex) {
    $error=mensajeError($ex);
}
try {
    if ($dia!=='') fecha($dia);
    $listado=listarFacturas($dia,max(1,(int)($_GET['pagina'] ?? 1)));
} catch (Throwable $ex) { $listaError=mensajeError($ex); }
$proveedores=consulta('SELECT id,nombre,activo FROM proveedores ORDER BY nombre')->fetchAll();
$ingredientes=consulta('SELECT id,nombre,unidad,activo FROM ingredientes ORDER BY nombre')->fetchAll();
$datos=['proveedor'=>$f['proveedor_id'] ?? '', 'numero'=>$f['numero'] ?? '', 'emision'=>$f['emision'] ?? date('Y-m-d')];
$items=[];
if ($f) foreach ($f['items'] as $l) $items[]=['ingrediente'=>$l['ingrediente_id'],'cantidad'=>$l['cantidad'],'costo_total'=>$l['costo_total']];
if (($_POST['accion'] ?? '')==='guardar') {
    foreach ($datos as $k=>$v) if (isset($_POST[$k]) && is_scalar($_POST[$k])) $datos[$k]=$_POST[$k];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        $items=[];
        foreach (array_slice($_POST['items'],0,50) as $item) {
            $fila=[];
            foreach (['ingrediente','cantidad','costo_total'] as $campo) $fila[$campo]=is_array($item) && is_scalar($item[$campo] ?? '') ? ($item[$campo] ?? '') : '';
            $items[]=$fila;
        }
    }
}
while(count($items)<3) $items[]=['ingrediente'=>'','cantidad'=>'','costo_total'=>''];
$titulo='Facturas'; require 'includes/header.php';
?>
<p>Guardá un borrador, revisá sus datos y confirmalo para ingresar los ingredientes. Los costos se expresan en pesos uruguayos.</p>
<p class="aviso">Una factura confirmada genera sus ingresos en Compras. No vuelvas a cargarlos manualmente. Las compras anteriores se conservan sin convertirlas automáticamente en facturas.</p>
<div class="acciones"><a class="boton" href="facturas.php">Nueva factura</a><a class="boton secundario" href="#listado">Consultar facturas</a></div>
<?php if ($editar): ?>
<section class="panel"><h2><?= $f ? 'Corregir borrador #'.e($f['id']) : 'Nueva factura — borrador' ?></h2>
<form method="post" action="facturas.php<?= $id ? '?id='.$id.'&editar=1' : '' ?>">
<?php formulario(); ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="version" value="<?= e($f['version'] ?? 0) ?>">
<label for="proveedor">Proveedor</label><select id="proveedor" name="proveedor" required><option value="">Elegir proveedor</option>
<?php foreach ($proveedores as $p): ?><option value="<?= e($p['id']) ?>" <?= (string)$datos['proveedor']===(string)$p['id'] ? 'selected' : '' ?> <?= !$p['activo'] ? 'disabled' : '' ?>><?= e($p['nombre']) ?><?= !$p['activo'] ? ' (inactivo)' : '' ?></option><?php endforeach; ?></select>
<label for="numero">Número de factura (incluí la serie, si tiene)</label><input id="numero" name="numero" maxlength="80" required value="<?= e($datos['numero']) ?>">
<label for="emision">Fecha de emisión</label><input id="emision" name="emision" type="date" min="1000-01-01" required value="<?= e($datos['emision']) ?>">
<p>Costo total por renglón, con los impuestos incluidos según el comprobante. El sistema suma los renglones; no agrega impuestos. Dejá totalmente vacíos los renglones que no uses.</p>
<div class="tabla"><table><thead><tr><th>Ingrediente</th><th>Cantidad</th><th>Costo total ($)</th></tr></thead><tbody id="renglones">
<?php foreach ($items as $j=>$l): if (!is_array($l)) $l=[]; ?>
<tr><td><select aria-label="Ingrediente del renglón <?= $j+1 ?>" name="items[<?= $j ?>][ingrediente]"><option value="">Sin ingrediente</option>
<?php foreach ($ingredientes as $i): ?><option value="<?= e($i['id']) ?>" <?= (string)($l['ingrediente'] ?? '')===(string)$i['id'] ? 'selected' : '' ?> <?= !$i['activo'] ? 'disabled' : '' ?>><?= e($i['nombre'].' ('.$i['unidad'].')'.(!$i['activo'] ? ' — inactivo' : '')) ?></option><?php endforeach; ?></select></td>
<td><input aria-label="Cantidad del renglón <?= $j+1 ?>" name="items[<?= $j ?>][cantidad]" type="number" min="0.001" max="999999" step="0.001" value="<?= is_scalar($l['cantidad'] ?? '') ? e($l['cantidad'] ?? '') : '' ?>"></td>
<td><input aria-label="Costo del renglón <?= $j+1 ?>" name="items[<?= $j ?>][costo_total]" type="number" min="0" max="999999999.99" step="0.01" value="<?= is_scalar($l['costo_total'] ?? '') ? e($l['costo_total'] ?? '') : '' ?>"></td></tr>
<?php endforeach; ?></tbody></table></div>
<div class="acciones"><button type="button" class="secundario" id="agregar-renglon" hidden>Agregar renglón</button><button>Guardar borrador y revisar</button><?php if($f): ?><a class="boton secundario" href="facturas.php?id=<?= e($id) ?>">Volver sin guardar cambios</a><?php endif; ?></div>
</form></section><script src="assets/facturas.js" defer></script>
<?php elseif ($f): ?>
<section class="panel"><h2>Factura #<?= e($f['id']) ?> · <?= anulacion('factura',(int)$f['id']) ? 'anulada' : e($f['estado']) ?></h2>
<p>Proveedor: <?= e($f['proveedor_nombre']) ?> · Número: <?= e($f['numero']) ?> · Emisión: <?= e($f['emision']) ?></p>
<p>Cargó: <?= e($f['persona_nombre']) ?> · <?= e($f['creada']) ?></p>
<?php if($f['confirmada']): ?><p>Confirmó: <?= e($f['confirmador_nombre']) ?> · <?= e($f['confirmada']) ?></p><?php endif; ?>
<div class="tabla"><table><thead><tr><th>Ingrediente</th><th>Cantidad</th><th>Costo</th><th>Ingreso en Compras</th></tr></thead><tbody>
<?php foreach($f['items'] as $l): ?><tr><td><?= e($l['ingrediente_nombre']) ?></td><td><?= n($l['cantidad']) ?> <?= e($l['unidad']) ?></td><td>$ <?= n($l['costo_total'],2) ?></td><td><?= $l['compra_id'] ? '#'.e($l['compra_id']) : 'Sin ingreso' ?></td></tr><?php endforeach; ?>
</tbody></table></div><p><strong>Total: $ <?= n($f['total'],2) ?></strong></p>
<?php if($f['estado']==='confirmada') enlaceAnulacion('factura',(int)$f['id']); ?>
<?php if($f['estado']==='borrador'): ?>
<p>Revisá los renglones antes de confirmar. Este borrador todavía no modificó las existencias.</p>
<div class="acciones"><a class="boton secundario" href="facturas.php?id=<?= e($id) ?>&editar=1">Editar borrador</a>
<?php foreach(['confirmar'=>'Confirmar e ingresar stock','cancelar'=>'Cancelar borrador'] as $accion=>$etiqueta): ?>
<form method="post" data-confirmar="<?= $accion==='confirmar' ? '¿Confirmás el ingreso de todos los renglones?' : '¿Cancelás este borrador?' ?>"><?php formulario(); ?><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="version" value="<?= e($f['version']) ?>"><button name="accion" value="<?= $accion ?>"><?= $etiqueta ?></button></form>
<?php endforeach; ?></div><?php endif; ?></section>
<?php endif; ?>
<section class="panel" id="listado"><h2>Facturas y borradores</h2>
<form class="filtros" method="get"><div><label for="dia">Fecha de emisión</label><input type="date" id="dia" name="dia" value="<?= e($dia) ?>"></div><button>Consultar</button><a class="boton secundario" href="facturas.php?dia=">Ver todas</a></form>
<?php if($listaError): ?><p class="aviso error"><?= e($listaError) ?></p><?php elseif($listado): ?>
<p><?= $listado['total'] ?> documentos · Página <?= $listado['pagina'] ?> de <?= $listado['paginas'] ?></p>
<div class="tabla"><table><thead><tr><th>Emisión / número</th><th>Proveedor</th><th>Estado</th><th>Total</th><th>Detalle</th></tr></thead><tbody>
<?php foreach($listado['filas'] as $r): ?><tr><td><?= e($r['emision']) ?><small><?= e($r['numero']) ?></small></td><td><?= e($r['proveedor_nombre']) ?></td><td><?= anulacion('factura',(int)$r['id']) ? 'anulada' : e($r['estado']) ?></td><td>$ <?= n($r['total'],2) ?></td><td><a class="boton secundario" href="facturas.php?id=<?= e($r['id']) ?>">Abrir #<?= e($r['id']) ?></a></td></tr><?php endforeach; ?>
<?php if(!$listado['filas']): ?><tr><td colspan="5" class="vacio">No hay facturas para esta fecha.</td></tr><?php endif; ?></tbody></table></div>
<div class="acciones"><?php if($listado['pagina']>1): ?><a class="boton secundario" href="?<?= e(http_build_query(['dia'=>$dia,'pagina'=>$listado['pagina']-1])) ?>#listado">Anterior</a><?php endif; ?><?php if($listado['pagina']<$listado['paginas']): ?><a class="boton secundario" href="?<?= e(http_build_query(['dia'=>$dia,'pagina'=>$listado['pagina']+1])) ?>#listado">Siguiente</a><?php endif; ?></div>
<?php endif; ?></section>
<?php require 'includes/footer.php'; ?>
