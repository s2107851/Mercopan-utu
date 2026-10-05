<?php require 'includes/inicio.php'; $titulo='Inicio'; require 'includes/header.php'; ?>
<section class="hero"><p class="ceja" style="color:#ffd24b">DEL INGREDIENTE AL MOSTRADOR</p><h2>Todo listo para trabajar.</h2><p>Registrá cada movimiento y consultá las existencias de la panadería desde un solo lugar.</p></section>
<div class="grid">
<div class="tarjeta"><span class="paso">INGREDIENTES ACTIVOS</span><div class="numero"><?= consulta('SELECT COUNT(*) FROM ingredientes WHERE activo=1')->fetchColumn() ?></div><p>Materias primas registradas</p></div>
<div class="tarjeta"><span class="paso">REVISAR STOCK</span><div class="numero"><?= consulta('SELECT COUNT(*) FROM ingredientes WHERE activo=1 AND stock<=minimo')->fetchColumn() ?></div><p>Ingredientes en su mínimo o por debajo</p></div>
<div class="tarjeta"><span class="paso">RESPONSABLE</span><h3><?= $persona ? e($persona['nombre']) : 'Sin seleccionar' ?></h3><p><a href="responsable.php">Seleccionar o cambiar</a></p></div>
</div><h2>¿Qué necesitás hacer?</h2><div class="grid">
<?php $modulos = [
 'ingredientes.php'=>['01','Ver ingredientes','Consultar existencias y niveles mínimos.'],
 'compras.php'=>['02','Registrar una compra','Ingresar ingredientes con su comprobante.'],
 'produccion.php'=>['03','Elaborar productos','Aplicar una receta y actualizar el stock.'],
 'ventas.php'=>['04','Registrar una venta','Descontar productos del mostrador.'],
 'pedidos.php'=>['05','Ver pedidos','Anotar encargos y registrar su entrega.'],
 'reportes.php'=>['06','Ver el resumen','Consultar ventas, compras y existencias.']
]; foreach ($modulos as $ruta=>$datos): if (!is_file(__DIR__ . '/' . $ruta)) continue; ?>
<a class="tarjeta" href="<?= e($ruta) ?>"><span class="paso"><?= e($datos[0]) ?></span><h3><?= e($datos[1]) ?></h3><p><?= e($datos[2]) ?></p></a>
<?php endforeach; ?></div>
<?php require 'includes/footer.php'; ?>
