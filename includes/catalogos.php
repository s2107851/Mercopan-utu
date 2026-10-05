<?php
// $tipo solo lo define una de las páginas PHP; nunca se toma de GET o POST.
$definiciones = ['personas'=>'Personal','proveedores'=>'Proveedores','ingredientes'=>'Ingredientes'];
$titulo = $definiciones[$tipo];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar();
        guardar(function () use ($tipo) {
            $id = (int)($_POST['id'] ?? 0);
            if (isset($_POST['cambiar_estado'])) {
                $r = consulta("SELECT * FROM $tipo WHERE id=? FOR UPDATE", [$id])->fetch();
                if (!$r) throw new RuntimeException('No se encontró el registro.');
                if ($tipo === 'personas' && $id === (int)$_SESSION['persona']) throw new RuntimeException('No podés desactivar a quien está trabajando. Cambiá el responsable primero.');
                consulta("UPDATE $tipo SET activo=? WHERE id=?", [1-(int)$r['activo'], $id]);
                registrar($tipo, ($r['activo'] ? 'Desactivó: ' : 'Activó: ') . $r['nombre']);
                return;
            }
            $nombre = texto('nombre');
            if ($id && !consulta("SELECT id FROM $tipo WHERE id=? FOR UPDATE",[$id])->fetch()) throw new RuntimeException('El registro no existe.');
            if ($tipo === 'ingredientes') {
                $minimo = numero($_POST['minimo'] ?? 0);
                if ($id) {
                    $unidad = consulta('SELECT unidad FROM ingredientes WHERE id=?',[$id])->fetchColumn();
                    if ($unidad === 'unidad' && floor($minimo) !== $minimo) throw new RuntimeException('El mínimo en unidades debe ser entero.');
                    consulta('UPDATE ingredientes SET nombre=?,minimo=? WHERE id=?',[$nombre,$minimo,$id]);
                } else {
                    $unidad = texto('unidad',10);
                    if (!in_array($unidad,['kg','l','unidad'],true)) throw new RuntimeException('Unidad incorrecta.');
                    if ($unidad === 'unidad' && floor($minimo) !== $minimo) throw new RuntimeException('El mínimo en unidades debe ser entero.');
                    consulta('INSERT INTO ingredientes(nombre,unidad,minimo) VALUES (?,?,?)',[$nombre,$unidad,$minimo]);
                }
            } elseif ($tipo === 'proveedores') {
                $telefono = texto('telefono',40,false);
                if ($id) consulta('UPDATE proveedores SET nombre=?,telefono=? WHERE id=?',[$nombre,$telefono,$id]);
                else consulta('INSERT INTO proveedores(nombre,telefono) VALUES (?,?)',[$nombre,$telefono]);
            } else {
                if ($id) consulta('UPDATE personas SET nombre=? WHERE id=?',[$nombre,$id]);
                else consulta('INSERT INTO personas(nombre) VALUES (?)',[$nombre]);
            }
            registrar($tipo, ($id ? 'Editó: ' : 'Agregó: ') . $nombre);
        });
        ir($tipo . '.php','Cambio guardado.');
    } catch (Throwable $e) { $error = mensajeError($e); }
}
$editar = consulta("SELECT * FROM $tipo WHERE id=?", [(int)($_GET['editar'] ?? 0)])->fetch() ?: [];
require 'header.php';
?>
<div class="dos"><section class="panel"><h2><?= $editar ? 'Editar registro' : 'Agregar registro' ?></h2>
<form method="post"><?php formulario(); ?><input type="hidden" name="id" value="<?= e($editar['id'] ?? 0) ?>">
<label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="100" required value="<?= valor('nombre',$editar['nombre'] ?? '') ?>">
<?php if ($tipo==='proveedores'): ?><label for="telefono">Teléfono (opcional)</label><input id="telefono" name="telefono" maxlength="40" value="<?= valor('telefono',$editar['telefono'] ?? '') ?>"><?php endif; ?>
<?php if ($tipo==='ingredientes'): ?>
<?php if (!$editar): ?><label for="unidad">Unidad del stock</label><select id="unidad" name="unidad"><option value="kg">Kilogramos</option><option value="l">Litros</option><option value="unidad">Unidades</option></select><?php else: ?><p>Unidad: <strong><?= e($editar['unidad']) ?></strong> (no se cambia)</p><?php endif; ?>
<label for="minimo">Stock mínimo</label><input id="minimo" name="minimo" type="number" min="0" max="999999" step="0.001" required value="<?= valor('minimo',$editar['minimo'] ?? 0) ?>">
<p class="ayuda">Los ingredientes nuevos comienzan en cero. La carga de ingresos y los ajustes de stock están pendientes.</p>
<?php endif; ?><button type="submit">Guardar registro</button><?php if($editar): ?><a class="boton secundario" href="<?= e($tipo) ?>.php">Cancelar</a><?php endif; ?></form></section>
<section class="panel"><h2>Registros</h2><div class="tabla"><table><thead><tr><th>Nombre</th><th><?= $tipo==='ingredientes'?'Stock / mínimo':'Estado' ?></th><th>Acciones</th></tr></thead><tbody>
<?php $filas=consulta("SELECT * FROM $tipo ORDER BY activo DESC,nombre")->fetchAll(); foreach ($filas as $r): ?>
<tr class="<?= $tipo==='ingredientes' && $r['activo'] && $r['stock']<=$r['minimo'] ? 'bajo' : '' ?>"><td><?= e($r['nombre']) ?><?php if ($tipo==='proveedores'): ?><small><?= e($r['telefono']) ?></small><?php endif; ?></td>
<td><?php if($tipo==='ingredientes'): ?><?= n($r['stock']) ?> <?= e($r['unidad']) ?><small>Mínimo: <?= n($r['minimo']) ?></small><?php endif; ?><span class="badge"><?= $r['activo']?'Activo':'Inactivo' ?></span></td>
<td><a href="<?= e($tipo) ?>.php?editar=<?= e($r['id']) ?>">Editar</a> <form class="enlinea" method="post" data-confirmar="¿Cambiar el estado de este registro?"><?php formulario(); ?><input type="hidden" name="id" value="<?= e($r['id']) ?>"><input type="hidden" name="cambiar_estado" value="1"><button class="secundario" type="submit"><?= $r['activo']?'Desactivar':'Activar' ?></button></form></td></tr>
<?php endforeach; if (!$filas): ?><tr><td colspan="3" class="vacio">Todavía no hay registros.</td></tr><?php endif; ?></tbody></table></div></section></div>
<p class="ayuda">Desactivar oculta el registro de nuevas operaciones y conserva sus referencias anteriores.</p>
<?php require 'footer.php'; ?>
