<?php
require 'includes/inicio.php';
require_once 'includes/filtros_historial.php';
$f = null; $filas=[]; $total=0; $pagina=1;
try {
    $f = filtrosHistorial($_GET);
    $total=(int)consulta('SELECT COUNT(*) FROM historial WHERE '.$f['where'],$f['params'])->fetchColumn();
    $paginas=max(1,(int)ceil($total/50));
    $pagina=min($paginas,max(1,(int)($_GET['pagina'] ?? 1)));
    $offset=($pagina-1)*50;
    $filas=consulta('SELECT * FROM historial WHERE '.$f['where'].' ORDER BY id DESC LIMIT 50 OFFSET '.$offset,$f['params'])->fetchAll();
} catch(Throwable $ex) { $error=mensajeError($ex); }
$titulo='Historial de acciones'; require 'includes/header.php';
$valores=$f ?? ['desde'=>date('Y-m-01'),'hasta'=>date('Y-m-d'),'persona'=>0,'modulo'=>'','buscar'=>''];
?>
<p class="descripcion">Consultá quién registró cada acción. Se conserva el nombre que tenía al realizarla. Por defecto se muestra el mes actual.</p>
<section class="panel"><form method="get" class="filtros">
<div><label for="desde">Desde</label><input type="date" id="desde" name="desde" required value="<?= e($valores['desde']) ?>"></div>
<div><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" required value="<?= e($valores['hasta']) ?>"></div>
<div><label for="persona">Responsable</label><select id="persona" name="persona"><option value="0">Todos</option><?php opciones(consulta('SELECT id,nombre FROM personas ORDER BY nombre')->fetchAll(),$valores['persona']); ?></select></div>
<div><label for="modulo">Módulo</label><select id="modulo" name="modulo"><option value="">Todos</option>
<?php foreach(consulta('SELECT DISTINCT modulo FROM historial ORDER BY modulo')->fetchAll() as $m): ?><option value="<?= e($m['modulo']) ?>" <?= $valores['modulo']===$m['modulo']?'selected':'' ?>><?= e($m['modulo']) ?></option><?php endforeach; ?></select></div>
<div><label for="buscar">Texto de la acción</label><input id="buscar" name="buscar" maxlength="100" value="<?= e($valores['buscar']) ?>"></div>
<button>Buscar acciones</button><a href="historial.php" class="boton secundario">Limpiar filtros</a></form></section>
<?php if($f): ?>
<a class="boton secundario" href="exportar.php?<?= e(http_build_query(['tipo'=>'historial','desde'=>$f['desde'],'hasta'=>$f['hasta'],'persona'=>$f['persona'],'modulo'=>$f['modulo'],'buscar'=>$f['buscar']])) ?>">Exportar todas las acciones filtradas CSV</a>
<section class="panel"><h2><?= e($total) ?> acciones encontradas</h2><div class="tabla"><table>
<thead><tr><th>Fecha</th><th>Responsable</th><th>Módulo</th><th>Acción</th></tr></thead><tbody>
<?php foreach($filas as $r): ?><tr><td><?= e($r['fecha']) ?></td><td><?= e($r['persona_nombre']) ?></td><td><?= e($r['modulo']) ?></td><td><?= e($r['detalle']) ?></td></tr><?php endforeach; ?>
<?php if(!$filas): ?><tr><td colspan="4" class="vacio">Sin acciones para estos filtros.</td></tr><?php endif; ?>
</tbody></table></div><p>Página <?= e($pagina) ?> de <?= e($paginas) ?>.</p><div class="acciones">
<?php $enlace=function($p)use($f){return '?'.http_build_query(['desde'=>$f['desde'],'hasta'=>$f['hasta'],'persona'=>$f['persona'],'modulo'=>$f['modulo'],'buscar'=>$f['buscar'],'pagina'=>$p]);}; ?>
<?php if($pagina>1): ?><a class="boton secundario" href="<?= e($enlace($pagina-1)) ?>">Anterior</a><?php endif; ?>
<?php if($pagina<$paginas): ?><a class="boton secundario" href="<?= e($enlace($pagina+1)) ?>">Siguiente</a><?php endif; ?>
</div></section><?php endif; ?>
<?php require 'includes/footer.php'; ?>
