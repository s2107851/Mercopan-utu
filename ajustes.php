<?php
require 'includes/inicio.php';
require 'includes/ajustes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar();
        guardar('registrarAjusteInventario');
        ir('ajustes.php', 'Ajuste registrado. El stock refleja el conteo físico.');
    } catch (Throwable $e) {
        $error = mensajeError($e);
    }
}

$dia = (string)($_GET['dia'] ?? date('Y-m-d'));
try {
    if ($dia !== '') fecha($dia);
} catch (Throwable $e) {
    $error = 'La fecha del filtro no es válida. Se muestra la jornada de hoy.';
    $dia = date('Y-m-d');
}
$condicion = '';
$parametros = [];
if ($dia !== '') {
    $fin = (new DateTime($dia))->modify('+1 day')->format('Y-m-d');
    $condicion = ' WHERE a.fecha >= ? AND a.fecha < ?';
    $parametros = [$dia, $fin];
}
$total = (int)consulta('SELECT COUNT(*) FROM ajustes_inventario a' . $condicion, $parametros)->fetchColumn();
$paginas = max(1, (int)ceil($total / 25));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$offset = ($pagina - 1) * 25;
$filas = consulta(
    'SELECT a.*, i.nombre AS ingrediente_nombre, i.unidad, p.nombre AS responsable_nombre
     FROM ajustes_inventario a
     JOIN ingredientes i ON i.id=a.ingrediente_id
     JOIN personas p ON p.id=a.persona_id' . $condicion .
    ' ORDER BY a.fecha DESC, a.id DESC LIMIT 25 OFFSET ' . $offset,
    $parametros
)->fetchAll();
$ingredientes = consulta(
    'SELECT id, nombre, unidad, stock FROM ingredientes WHERE activo=1 ORDER BY nombre'
)->fetchAll();
$titulo = 'Ajustes de inventario';
require 'includes/header.php';
?>
<p class="descripcion">Cuando la cantidad real no coincide con el sistema, ingresá el conteo físico. El ajuste queda registrado con fecha, motivo y responsable.</p>
<div class="dos">
    <section class="panel">
        <h2>Corregir existencias</h2>
        <?php if (!$ingredientes): ?>
            <p class="aviso">Primero agregá un ingrediente activo.</p>
            <a class="boton secundario" href="ingredientes.php">Ver ingredientes</a>
        <?php else: ?>
            <form method="post" data-confirmar="¿Confirmás este ajuste? Se reemplazará el stock del ingrediente por el conteo físico.">
                <?php formulario(); ?>
                <label for="ingrediente">Ingrediente</label>
                <select id="ingrediente" name="ingrediente" required>
                    <option value="">Elegir ingrediente</option>
                    <?php foreach ($ingredientes as $i): ?>
                        <option value="<?= e($i['id']) ?>" <?= (string)($_POST['ingrediente'] ?? '') === (string)$i['id'] ? 'selected' : '' ?>>
                            <?= e($i['nombre']) ?> · Sistema: <?= n($i['stock']) ?> <?= e($i['unidad']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="stock_real">Cantidad contada físicamente</label>
                <input id="stock_real" name="stock_real" type="number" min="0" max="999999999.999" step="0.001" required value="<?= valor('stock_real') ?>">
                <label for="motivo">Motivo del ajuste</label>
                <textarea id="motivo" name="motivo" maxlength="250" required placeholder="Ej.: conteo semanal, diferencia por derrame..."><?= valor('motivo') ?></textarea>
                <p class="ayuda">Ingresá el total real, no la diferencia. Ejemplo: si el sistema marca 10 kg y contaste 8 kg, escribí 8. Para productos contados por unidad, usá números enteros. Las compras y producciones anteriores no cambian.</p>
                <button class="amarillo" type="submit">Guardar ajuste</button>
            </form>
        <?php endif; ?>
        <div class="acciones"><a class="boton secundario" href="ingredientes.php">Consultar stock</a></div>
    </section>
    <section class="panel">
        <h2>Historial de ajustes</h2>
        <form class="filtros" method="get">
            <div><label for="dia">Jornada</label><input id="dia" name="dia" type="date" value="<?= e($dia) ?>"></div>
            <button type="submit">Consultar</button>
            <a class="boton secundario" href="ajustes.php?dia=">Ver todos</a>
        </form>
        <p class="ayuda"><?= $total ?> ajustes · <?= $dia ? e($dia) : 'Todas las fechas' ?> · Página <?= $pagina ?> de <?= $paginas ?></p>
        <div class="tabla">
            <table>
                <thead><tr><th>Fecha / ingrediente</th><th>Antes → después</th><th>Diferencia</th><th>Motivo / responsable</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $r): ?>
                    <tr>
                        <td>#<?= e($r['id']) ?> · <?= e($r['ingrediente_nombre']) ?><small><?= e($r['fecha']) ?></small></td>
                        <td><?= n($r['stock_anterior']) ?> → <?= n($r['stock_nuevo']) ?> <?= e($r['unidad']) ?></td>
                        <td><?= $r['diferencia'] > 0 ? '+' : '' ?><?= n($r['diferencia']) ?> <?= e($r['unidad']) ?></td>
                        <td><?= e($r['motivo']) ?><small><?= e($r['responsable_nombre']) ?></small></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="4" class="vacio">No hay ajustes para esta fecha.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="acciones">
            <?php if ($pagina > 1): ?><a class="boton secundario" href="?<?= e(http_build_query(['dia'=>$dia, 'pagina'=>$pagina-1])) ?>">Anterior</a><?php endif; ?>
            <?php if ($pagina < $paginas): ?><a class="boton secundario" href="?<?= e(http_build_query(['dia'=>$dia, 'pagina'=>$pagina+1])) ?>">Siguiente</a><?php endif; ?>
        </div>
    </section>
</div>
<?php require 'includes/footer.php'; ?>
