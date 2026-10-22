<?php
require 'includes/inicio.php';
require 'includes/ventas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar();
        guardar('registrarVenta');
        ir('ventas.php', 'Venta registrada. Se descontó el producto del stock.');
    } catch (Throwable $e) {
        $error = mensajeError($e);
    }
}

$dia = (string)($_GET['dia'] ?? date('Y-m-d'));
try {
    if ($dia !== '') fecha($dia);
} catch (Throwable $e) {
    $error = 'La fecha no es válida. Se muestra la jornada de hoy.';
    $dia = date('Y-m-d');
}
$condicion = '';
$parametros = [];
if ($dia !== '') {
    $fin = (new DateTime($dia))->modify('+1 day')->format('Y-m-d');
    $condicion = ' WHERE v.fecha >= ? AND v.fecha < ?';
    $parametros = [$dia, $fin];
}

$resumen = consulta(
    'SELECT COUNT(*) operaciones, COALESCE(SUM(v.total), 0) importe FROM ventas v' . $condicion,
    $parametros
)->fetch();
$total = (int)$resumen['operaciones'];
$paginas = max(1, (int)ceil($total / 25));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$offset = ($pagina - 1) * 25;
$filas = consulta(
    'SELECT v.*, p.nombre AS producto_nombre, p.unidad
     FROM ventas v
     JOIN productos p ON p.id=v.producto_id' . $condicion .
    ' ORDER BY v.fecha DESC, v.id DESC LIMIT 25 OFFSET ' . $offset,
    $parametros
)->fetchAll();
$disponibles = consulta(
    'SELECT id, CONCAT(nombre, " · ", stock, " ", unidad, " disponibles · $", precio) AS nombre
     FROM productos WHERE activo=1 AND stock>0 ORDER BY nombre'
)->fetchAll();

$titulo = 'Ventas';
require 'includes/header.php';
?>
<p class="descripcion">Registrá las ventas de productos elaborados. El precio se toma del catálogo y las existencias se descuentan automáticamente.</p>
<div class="dos">
    <section class="panel">
        <h2>Registrar venta</h2>
        <?php if (!$disponibles): ?>
            <p class="aviso">No hay productos activos con existencias. Primero registrá una elaboración.</p>
            <a class="boton secundario" href="produccion.php">Ir a Producción</a>
        <?php else: ?>
            <form method="post" data-confirmar="¿Confirmás la venta? Se descontará la cantidad del stock del producto.">
                <?php formulario(); ?>
                <label for="producto">Producto disponible</label>
                <select id="producto" name="producto" required>
                    <option value="">Elegir producto</option>
                    <?php opciones($disponibles, $_POST['producto'] ?? 0); ?>
                </select>
                <label for="cantidad">Cantidad vendida (unidades o kg según el producto)</label>
                <input id="cantidad" name="cantidad" type="number" min="0.001" max="999999" step="0.001" required value="<?= valor('cantidad', 1) ?>">
                <p class="ayuda">Si se vende por unidad, ingresá un número entero. Si es por kilogramo, podés ingresar hasta tres decimales. El importe se calcula al confirmar.</p>
                <button class="amarillo" type="submit">Confirmar venta</button>
            </form>
        <?php endif; ?>
    </section>
    <section class="panel">
        <h2>Ventas registradas</h2>
        <form method="get" class="filtros">
            <div><label for="dia">Jornada</label><input id="dia" name="dia" type="date" value="<?= e($dia) ?>"></div>
            <button type="submit">Consultar</button>
            <a class="boton secundario" href="ventas.php?dia=">Ver todas</a>
        </form>
        <p><strong>Total vendido: $ <?= n($resumen['importe'], 2) ?></strong></p>
        <p class="ayuda"><?= $total ?> ventas · <?= $dia ? e($dia) : 'Todas las fechas' ?> · Página <?= $pagina ?> de <?= $paginas ?></p>
        <div class="tabla">
            <table>
                <thead><tr><th>Producto / fecha</th><th>Cantidad</th><th>Precio</th><th>Importe</th><th>Responsable</th></tr></thead>
                <tbody>
                <?php foreach ($filas as $r): ?>
                    <tr>
                        <td>#<?= e($r['id']) ?> · <?= e($r['producto_nombre']) ?><small><?= e($r['fecha']) ?></small></td>
                        <td><?= n($r['cantidad']) ?> <?= e($r['unidad']) ?></td>
                        <td>$ <?= n($r['precio_unitario'], 2) ?></td>
                        <td><strong>$ <?= n($r['total'], 2) ?></strong></td>
                        <td><?= e($r['persona_nombre']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filas): ?><tr><td colspan="5" class="vacio">No hay ventas para esta fecha.</td></tr><?php endif; ?>
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
