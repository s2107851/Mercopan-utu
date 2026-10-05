<?php
require 'includes/inicio.php';
require 'includes/produccion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar();
        guardar('elaborar');
        ir('produccion.php', 'Producción registrada. Se descontaron ingredientes y se sumaron productos.');
    } catch (Throwable $e) {
        $error = mensajeError($e);
    }
}

// El filtro usa un intervalo de fechas para incluir todas las horas del día.
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
    $condicion = ' WHERE pr.fecha >= ? AND pr.fecha < ?';
    $parametros = [$dia, $fin];
}
$total = (int)consulta('SELECT COUNT(*) FROM producciones pr' . $condicion, $parametros)->fetchColumn();
$paginas = max(1, (int)ceil($total / 25));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$offset = ($pagina - 1) * 25;
$filas = consulta(
    'SELECT pr.*, p.nombre, p.unidad, pe.nombre responsable
     FROM producciones pr
     JOIN productos p ON p.id = pr.producto_id
     JOIN personas pe ON pe.id = pr.persona_id' . $condicion .
    ' ORDER BY pr.fecha DESC, pr.id DESC LIMIT 25 OFFSET ' . $offset,
    $parametros
)->fetchAll();
$titulo = 'Producción';
require 'includes/header.php';
?>
<p class="descripcion">Registrá lo elaborado y consultá la producción de cada jornada. Los ingredientes se descuentan según la receta.</p>
<div class="dos">
    <section class="panel">
        <h2>Registrar elaboración</h2>
        <form method="post" data-confirmar="¿Confirmás la elaboración? Se consumirán los ingredientes de la receta.">
            <?php formulario(); ?>
            <label for="producto">Producto con receta</label>
            <select id="producto" name="producto" required>
                <option value="">Elegir</option>
                <?php opciones(consulta(
                    "SELECT p.id, CONCAT(p.nombre, ' · ', r.rendimiento, ' ', p.unidad, ' por lote') nombre
                     FROM productos p JOIN recetas r ON r.producto_id = p.id
                     WHERE p.activo = 1 ORDER BY p.nombre"
                )->fetchAll(), $_POST['producto'] ?? 0); ?>
            </select>
            <label for="lotes">Cantidad de lotes completos</label>
            <input id="lotes" name="lotes" type="number" min="1" max="1000" step="1" required value="<?= valor('lotes', 1) ?>">
            <p class="ayuda">Un lote produce la cantidad indicada en la receta. Si falta algún ingrediente, no se guarda la elaboración ni se descuenta stock.</p>
            <button class="amarillo" type="submit">Registrar producción</button>
        </form>
        <div class="acciones">
            <a class="boton secundario" href="recetas.php">Consultar recetas</a>
            <a class="boton secundario" href="productos.php">Ver productos</a>
        </div>
    </section>
    <section class="panel">
        <h2>Producciones realizadas</h2>
        <form method="get" class="filtros">
            <div>
                <label for="dia">Jornada</label>
                <input id="dia" name="dia" type="date" value="<?= e($dia) ?>">
            </div>
            <button type="submit">Consultar</button>
            <a class="boton secundario" href="produccion.php?dia=">Ver todas</a>
        </form>
        <p class="ayuda"><?= $total ?> elaboraciones · <?= $dia ? e($dia) : 'Todas las fechas' ?> · Página <?= $pagina ?> de <?= $paginas ?></p>
        <div class="tabla">
            <table>
                <thead><tr><th>Producto / fecha</th><th>Obtenido</th><th>Responsable</th><th>Consumo</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $r): ?>
                    <tr>
                        <td>#<?= e($r['id']) ?> · <?= e($r['nombre']) ?><small><?= e($r['fecha']) ?> · <?= e($r['lotes']) ?> lotes</small></td>
                        <td><?= n($r['cantidad']) ?> <?= e($r['unidad']) ?></td>
                        <td><?= e($r['responsable']) ?></td>
                        <td>
                            <details><summary>Ingredientes</summary>
                            <?php foreach (consulta(
                                'SELECT c.*, i.nombre, i.unidad FROM consumos c
                                 JOIN ingredientes i ON i.id = c.ingrediente_id WHERE produccion_id = ?', [$r['id']]
                            ) as $d): ?>
                                <p><?= e($d['nombre']) ?>: <?= n($d['cantidad']) ?> <?= e($d['unidad']) ?></p>
                            <?php endforeach; ?>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="4" class="vacio">No hay elaboraciones para esta fecha.</td></tr><?php endif; ?>
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
