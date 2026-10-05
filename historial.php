<?php require 'includes/inicio.php'; $titulo='Historial de acciones'; require 'includes/header.php';
$pagina=max(1,(int)($_GET['pagina'] ?? 1));$offset=($pagina-1)*50;
$filas=consulta('SELECT * FROM historial ORDER BY id DESC LIMIT 50 OFFSET ' . $offset)->fetchAll(); ?>
<p class="descripcion">Cada cambio guarda fecha, responsable y detalle. Se muestra el nombre que tenía la persona al realizar la acción.</p>
<section class="panel"><div class="tabla"><table><thead><tr><th>Fecha</th><th>Responsable</th><th>Módulo</th><th>Acción</th></tr></thead><tbody>
<?php foreach($filas as $r): ?><tr><td><?= e($r['fecha']) ?></td><td><?= e($r['persona_nombre']) ?></td><td><?= e($r['modulo']) ?></td><td><?= e($r['detalle']) ?></td></tr><?php endforeach; ?>
<?php if(!$filas): ?><tr><td colspan="4" class="vacio">Sin acciones para mostrar.</td></tr><?php endif; ?></tbody></table></div>
<div class="acciones"><?php if($pagina>1): ?><a class="boton secundario" href="?pagina=<?= $pagina-1 ?>">Anterior</a><?php endif; ?><?php if(count($filas)===50): ?><a class="boton secundario" href="?pagina=<?= $pagina+1 ?>">Siguiente</a><?php endif; ?></div></section>
<?php require 'includes/footer.php'; ?>
