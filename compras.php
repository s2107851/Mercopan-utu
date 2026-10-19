<?php
require 'includes/inicio.php';
require 'includes/compras.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar();
        guardar('registrarCompra');
        ir('compras.php', 'Compra registrada. El stock del ingrediente se actualizó.');
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
    $condicion = ' WHERE c.fecha >= ? AND c.fecha < ?';
    $parametros = [$dia, $fin];
}

$total = (int)consulta('SELECT COUNT(*) FROM compras c' . $condicion, $parametros)->fetchColumn();
$paginas = max(1, (int)ceil($total / 25));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$offset = ($pagina - 1) * 25;
$filas = consulta(
    'SELECT c.*, i.nombre AS ingrediente_nombre, i.unidad,
            pr.nombre AS proveedor_nombre, pe.nombre AS responsable_nombre
     FROM compras c
     JOIN ingredientes i ON i.id=c.ingrediente_id
     JOIN proveedores pr ON pr.id=c.proveedor_id
     JOIN personas pe ON pe.id=c.persona_id' . $condicion .
    ' ORDER BY c.fecha DESC, c.id DESC LIMIT 25 OFFSET ' . $offset,
    $parametros
)->fetchAll();

$proveedores = consulta('SELECT id, nombre FROM proveedores WHERE activo=1 ORDER BY nombre')->fetchAll();
$ingredientes = consulta('SELECT id, nombre, unidad FROM ingredientes WHERE activo=1 ORDER BY nombre')->fetchAll();
$titulo = 'Compras';
require 'includes/header.php';
?>
<p class="descripcion">Registrá el ingreso de materias primas desde un proveedor. Cada compra suma ingredientes al stock y deja constancia del responsable.</p>
<div class="dos">
    <section class="panel">
        <h2>Ingresar una compra</h2>
        <?php if (!$proveedores || !$ingredientes): ?>
            <p class="aviso">Para registrar una compra necesitás al menos un proveedor y un ingrediente activos.</p>
            <div class="acciones">
                <a class="boton secundario" href="proveedores.php">Administrar proveedores</a>
                <a class="boton secundario" href="ingredientes.php">Administrar ingredientes</a>
            </div>
        <?php else: ?>
            <form method="post" data-confirmar="¿Confirmás el ingreso al stock de este ingrediente?">
                <?php formulario(); ?>
                <label for="proveedor">Proveedor</label>
                <select id="proveedor" name="proveedor" required>
                    <option value="">Elegir proveedor</option>
                    <?php opciones($proveedores, $_POST['proveedor'] ?? 0); ?>
                </select>

                <label for="ingrediente">Ingrediente comprado</label>
                <select id="ingrediente" name="ingrediente" required>
                    <option value="">Elegir ingrediente</option>
                    <?php foreach ($ingredientes as $i): ?>
                        <option value="<?= e($i['id']) ?>" <?= (string)($_POST['ingrediente'] ?? '') === (string)$i['id'] ? 'selected' : '' ?>>
                            <?= e($i['nombre']) ?> (<?= e($i['unidad']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="cantidad">Cantidad ingresada (según la unidad del ingrediente)</label>
                <input id="cantidad" name="cantidad" type="number" min="0.001" max="999999" step="0.001" required value="<?= valor('cantidad') ?>">
                <label for="costo_total">Costo total de este ingrediente ($ UYU)</label>
                <input id="costo_total" name="costo_total" type="number" min="0" max="999999999.99" step="0.01" required value="<?= valor('costo_total') ?>">
                <label for="comprobante">Número de factura o comprobante (opcional)</label>
                <input id="comprobante" name="comprobante" maxlength="80" value="<?= valor('comprobante') ?>">
                <p class="ayuda">Ingresá una compra por ingrediente. Si una factura tiene varios ingredientes, repetí el ingreso con el mismo número de comprobante. Para huevos u otros artículos contados se exigen cantidades enteras.</p>
                <button class="amarillo" type="submit">Registrar compra</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Compras registradas</h2>
        <form class="filtros" method="get">
            <div><label for="dia">Jornada</label><input id="dia" name="dia" type="date" value="<?= e($dia) ?>"></div>
            <button type="submit">Consultar</button>
            <a class="boton secundario" href="compras.php?dia=">Ver todas</a>
        </form>
        <p class="ayuda"><?= $total ?> ingresos · <?= $dia ? e($dia) : 'Todas las fechas' ?> · Página <?= $pagina ?> de <?= $paginas ?></p>
        <div class="tabla">
            <table>
                <thead><tr><th>Fecha / ingrediente</th><th>Cantidad</th><th>Proveedor</th><th>Costo</th><th>Responsable</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $r): ?>
                    <tr>
                        <td>#<?= e($r['id']) ?> · <?= e($r['ingrediente_nombre']) ?>
                            <small><?= e($r['fecha']) ?><?= $r['comprobante'] !== '' ? ' · Comp.: ' . e($r['comprobante']) : '' ?></small>
                        </td>
                        <td><?= n($r['cantidad']) ?> <?= e($r['unidad']) ?></td>
                        <td><?= e($r['proveedor_nombre']) ?></td>
                        <td>$ <?= n($r['costo_total'], 2) ?></td>
                        <td><?= e($r['responsable_nombre']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="5" class="vacio">No hay compras para esta fecha.</td></tr><?php endif; ?>
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
