<?php require 'includes/inicio.php';
$id=(int)($_GET['producto'] ?? $_POST['producto'] ?? 0);
if($_SERVER['REQUEST_METHOD']==='POST') {
 try { verificar();guardar(function()use($id){
  $p=consulta('SELECT * FROM productos WHERE id=? AND activo=1 FOR UPDATE',[$id])->fetch();
  if(!$p) throw new RuntimeException('Elegí un producto activo.');
  $rendimiento=numero($_POST['rendimiento'] ?? '',0.001);
  if($p['unidad']==='unidad' && floor($rendimiento)!==$rendimiento) throw new RuntimeException('El rendimiento en unidades debe ser entero.');
  $instrucciones=texto('instrucciones',500,false);$detalles=[];
  foreach(consulta('SELECT * FROM ingredientes ORDER BY id') as $i){
   $entrada=$_POST['cantidades'][$i['id']] ?? '';
   if(trim((string)$entrada)==='' || (string)$entrada==='0') continue;
   $cantidad=numero($entrada,0.001);
   if(!$i['activo']) throw new RuntimeException('La receta contiene un ingrediente inactivo. Reactivalo o quitá su cantidad.');
   if($i['unidad']==='unidad' && floor($cantidad)!==$cantidad) throw new RuntimeException('Usá cantidades enteras para ingredientes en unidades.');
   $detalles[$i['id']]=$cantidad;
  }
  if(!$detalles) throw new RuntimeException('La receta necesita al menos un ingrediente.');
  consulta('INSERT INTO recetas(producto_id,rendimiento,instrucciones) VALUES (?,?,?) ON DUPLICATE KEY UPDATE rendimiento=VALUES(rendimiento),instrucciones=VALUES(instrucciones)',[$id,$rendimiento,$instrucciones]);
  consulta('DELETE FROM receta_detalle WHERE producto_id=?',[$id]);
  foreach($detalles as $i=>$cant) consulta('INSERT INTO receta_detalle VALUES (?,?,?)',[$id,$i,$cant]);
  registrar('recetas','Guardó receta de ' . $p['nombre'] . ' · rendimiento ' . n($rendimiento) . ' ' . $p['unidad']);
 });ir('recetas.php?producto='.$id,'Receta guardada.');}catch(Throwable $e){$error=mensajeError($e);}
}
$titulo='Recetas';require 'includes/header.php';
$p=consulta('SELECT * FROM productos WHERE id=?',[$id])->fetch();$receta=consulta('SELECT * FROM recetas WHERE producto_id=?',[$id])->fetch() ?: [];
$cantidades=[];foreach(consulta('SELECT * FROM receta_detalle WHERE producto_id=?',[$id]) as $r)$cantidades[$r['ingrediente_id']]=$r['cantidad']; ?>
<p class="descripcion">Una receta indica los ingredientes necesarios para un lote. En producción se elige cuántos lotes preparar.</p>
<section class="panel"><form method="get" class="filtros"><div><label for="producto">Producto</label><select id="producto" name="producto" required><option value="">Elegir</option><?php opciones(consulta('SELECT * FROM productos WHERE activo=1 ORDER BY nombre')->fetchAll(),$id); ?></select></div><button type="submit">Abrir receta</button></form></section>
<?php if($p): ?><section class="panel"><h2><?= e($p['nombre']) ?></h2><form method="post"><?php formulario(); ?><input type="hidden" name="producto" value="<?= $id ?>">
<label for="rendimiento">Cantidad obtenida por lote (<?= e($p['unidad']) ?>)</label><input id="rendimiento" name="rendimiento" type="number" min="0.001" max="999999" step="0.001" required value="<?= valor('rendimiento',$receta['rendimiento'] ?? '') ?>">
<div class="grid"><?php foreach(consulta('SELECT * FROM ingredientes ORDER BY nombre') as $i): ?><div><label for="i<?= $i['id'] ?>"><?= e($i['nombre']) ?> (<?= e($i['unidad']) ?>)<?= !$i['activo']?' · inactivo':'' ?></label><input id="i<?= $i['id'] ?>" name="cantidades[<?= $i['id'] ?>]" type="number" min="0" max="999999" step="0.001" value="<?= e($_POST['cantidades'][$i['id']] ?? $cantidades[$i['id']] ?? '') ?>"></div><?php endforeach; ?></div>
<p class="ayuda">Dejá vacíos los ingredientes que no se utilizan. Guardar reemplaza la receta actual, sin cambiar producciones anteriores.</p><label for="instrucciones">Instrucciones (opcional)</label><textarea id="instrucciones" name="instrucciones" maxlength="500"><?= valor('instrucciones',$receta['instrucciones'] ?? '') ?></textarea><button type="submit">Guardar receta</button></form></section><?php endif; ?>
<?php require 'includes/footer.php'; ?>
