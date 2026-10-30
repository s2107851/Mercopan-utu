<?php
require 'includes/inicio.php';
require_once 'includes/anulaciones.php';
$tipo=$_GET['tipo'] ?? ''; $id=0; $registro=null; $a=null;
try {
    consulta('SELECT id FROM anulaciones LIMIT 1');
    if (!in_array($tipo,['compra','produccion','factura'],true)) throw new RuntimeException('Elegí la operación desde Compras, Producción o Facturas.');
    $id=idFactura($_GET['id'] ?? '');
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        verificar();
        guardar(function() use($tipo,$id) { anularOperacion(['tipo'=>$tipo,'id'=>(string)$id,'motivo'=>$_POST['motivo'] ?? '', 'confirmar'=>$_POST['confirmar'] ?? '']); });
        ir('anular.php?'.http_build_query(['tipo'=>$tipo,'id'=>$id]),'Operación anulada. Stock revertido y motivo registrado.');
    }
} catch(Throwable $ex) { $error=mensajeError($ex); }
try {
    if ($id && in_array($tipo,['compra','produccion','factura'],true)) {
        if ($tipo==='compra') $registro=consulta('SELECT c.*,i.nombre,i.unidad,p.nombre proveedor FROM compras c JOIN ingredientes i ON i.id=c.ingrediente_id JOIN proveedores p ON p.id=c.proveedor_id WHERE c.id=?',[$id])->fetch();
        elseif ($tipo==='produccion') $registro=consulta('SELECT c.*,p.nombre,p.unidad FROM producciones c JOIN productos p ON p.id=c.producto_id WHERE c.id=?',[$id])->fetch();
        else $registro=obtenerFactura($id);
        if (!$registro) throw new RuntimeException('La operación no existe.');
        $a=anulacion($tipo,$id);
    }
} catch(Throwable $ex) { $error=mensajeError($ex); $registro=null; }
$titulo='Anular operación'; require 'includes/header.php';
?>
<section class="panel">
<?php if($registro): ?>
<h2><?= e(ucfirst($tipo)) ?> #<?= e($id) ?></h2>
<?php if($tipo==='factura'): ?>
<p><?= e($registro['proveedor_nombre']) ?> · <?= e($registro['numero']) ?> · <?= e($registro['emision']) ?> · $ <?= n($registro['total'],2) ?></p>
<ul><?php foreach($registro['items'] as $l): ?><li><?= e($l['ingrediente_nombre']) ?>: <?= n($l['cantidad']) ?> <?= e($l['unidad']) ?></li><?php endforeach; ?></ul>
<?php else: ?>
<p><?= e($registro['nombre']) ?> · <?= n($registro['cantidad']) ?> <?= e($registro['unidad']) ?> · <?= e($registro['fecha']) ?></p>
<?php endif; ?>
<?php if($a): enlaceAnulacion($tipo,$id); else: ?>
<p>Se conservará el registro original. <?= $tipo==='produccion' ? 'Se retirarán los productos elaborados y se devolverán los ingredientes según sus consumos originales, aunque la receta haya cambiado.' : 'Se retirarán del stock los ingredientes ingresados. Una factura se revierte completa.' ?> Si falta stock, no se guardará ningún cambio.</p>
<form method="post" data-confirmar="¿Confirmás la anulación y la reversión del stock?">
<?php formulario(); ?>
<label for="motivo">Motivo de la anulación</label><textarea id="motivo" name="motivo" maxlength="250" required><?= valor('motivo') ?></textarea>
<label><input type="checkbox" name="confirmar" value="1" required> Revisé la operación y quiero anularla.</label>
<button type="submit">Confirmar anulación</button>
</form>
<?php endif; ?>
<?php endif; ?>
<p><a class="boton secundario" href="<?= $tipo==='produccion' ? 'produccion.php?dia=' : ($tipo==='factura' ? 'facturas.php' : 'compras.php?dia=') ?>">Volver al listado</a></p>
</section>
<?php require 'includes/footer.php'; ?>
