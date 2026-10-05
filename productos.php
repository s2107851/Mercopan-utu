<?php require 'includes/inicio.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
 try { verificar(); guardar(function(){
  $id=(int)($_POST['id'] ?? 0);
  if($id && !consulta('SELECT id FROM productos WHERE id=? FOR UPDATE',[$id])->fetch()) throw new RuntimeException('No se encontró el producto.');
  if(isset($_POST['cambiar_estado'])) {
   consulta('UPDATE productos SET activo=1-activo WHERE id=?',[$id]); registrar('productos','Cambió el estado del producto #' . $id); return;
  }
  $nombre=texto('nombre');$precio=numero($_POST['precio'] ?? '',0.01,999999,2);
  if($id) consulta('UPDATE productos SET nombre=?,precio=? WHERE id=?',[$nombre,$precio,$id]);
  else {
   $unidad=texto('unidad',10);if(!in_array($unidad,['kg','unidad'],true)) throw new RuntimeException('Unidad incorrecta.');
   consulta('INSERT INTO productos(nombre,unidad,precio) VALUES (?,?,?)',[$nombre,$unidad,$precio]);
  }
  registrar('productos',($id?'Editó: ':'Agregó: ') . $nombre);
 }); ir('productos.php','Producto guardado.'); } catch(Throwable $e){$error=mensajeError($e);}
}
$editar=consulta('SELECT * FROM productos WHERE id=?',[(int)($_GET['editar'] ?? 0)])->fetch() ?: [];
$titulo='Productos terminados';require 'includes/header.php'; ?>
<div class="dos"><section class="panel"><h2><?= $editar?'Editar producto':'Agregar producto' ?></h2><form method="post"><?php formulario(); ?><input type="hidden" name="id" value="<?= e($editar['id'] ?? 0) ?>">
<label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="100" required value="<?= valor('nombre',$editar['nombre'] ?? '') ?>">
<?php if(!$editar): ?><label for="unidad">Se vende por</label><select id="unidad" name="unidad"><option value="unidad">Unidad</option><option value="kg">Kilogramo</option></select><?php else: ?><p>Unidad fija: <?= e($editar['unidad']) ?></p><?php endif; ?>
<label for="precio">Precio por unidad / kg ($ UYU)</label><input id="precio" name="precio" type="number" min="0.01" max="999999" step="0.01" required value="<?= valor('precio',$editar['precio'] ?? '') ?>"><button type="submit">Guardar producto</button><?php if($editar): ?><a class="boton secundario" href="productos.php">Cancelar</a><?php endif; ?></form><p class="ayuda">Un producto nuevo comienza sin existencias. Se utilizará una receta para registrar su elaboración.</p></section>
<section class="panel"><h2>Catálogo</h2><div class="tabla"><table><thead><tr><th>Producto</th><th>Stock</th><th>Precio</th><th>Acciones</th></tr></thead><tbody>
<?php $filas=consulta('SELECT * FROM productos ORDER BY activo DESC,nombre')->fetchAll(); foreach($filas as $r): ?><tr><td><?= e($r['nombre']) ?><small><?= $r['activo']?'Activo':'Inactivo' ?></small></td><td><?= n($r['stock']) ?> <?= e($r['unidad']) ?></td><td>$ <?= n($r['precio'],2) ?></td><td><a href="?editar=<?= e($r['id']) ?>">Editar</a><form class="enlinea" method="post" data-confirmar="¿Cambiar el estado del producto?"><?php formulario(); ?><input type="hidden" name="id" value="<?= e($r['id']) ?>"><input type="hidden" name="cambiar_estado" value="1"><button class="secundario" type="submit"><?= $r['activo']?'Desactivar':'Activar' ?></button></form></td></tr><?php endforeach; if(!$filas): ?><tr><td colspan="4" class="vacio">Sin productos.</td></tr><?php endif; ?></tbody></table></div></section></div>
<?php require 'includes/footer.php'; ?>
