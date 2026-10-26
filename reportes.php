<?php
require 'includes/inicio.php';
require_once 'includes/reportes.php';
$resumen = null;
$desde = date('Y-m-01'); $hasta = date('Y-m-d');
try {
    [$desde,$hasta,$fin] = periodoReporte($_GET);
    $resumen = resumenPeriodo($desde,$fin);
} catch (Throwable $ex) { $error = mensajeError($ex); }
$titulo='Resumen por período'; require 'includes/header.php';
?>
<form class="filtros no-imprimir" method="get">
<div><label for="desde">Desde</label><input type="date" id="desde" name="desde" required value="<?= e($desde) ?>"></div>
<div><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" required value="<?= e($hasta) ?>"></div>
<button>Consultar período</button></form>
<p>Período: <?= e($desde) ?> al <?= e($hasta) ?>, ambos días incluidos.</p>
<?php if ($resumen): ?>
<div class="grid">
<div class="tarjeta"><h2>Ventas registradas</h2><div class="numero">$ <?= n($resumen['ventas']['importe'],2) ?></div><p><?= e($resumen['ventas']['registros']) ?> registros</p></div>
<div class="tarjeta"><h2>Compras registradas</h2><div class="numero">$ <?= n($resumen['compras']['importe'],2) ?></div><p><?= e($resumen['compras']['registros']) ?> registros</p></div>
</div>
<p class="aviso">Los importes están en pesos uruguayos. Son movimientos registrados, no un cálculo de ganancia ni un saldo de caja: no se registran aquí todos los costos, gastos o cobros.</p>
<section class="panel"><h2>Ventas por producto</h2><div class="tabla"><table>
<thead><tr><th>Producto</th><th>Cantidad</th><th>Importe histórico</th></tr></thead><tbody>
<?php foreach ($resumen['productos'] as $r): ?><tr><td><?= e($r['nombre']) ?></td><td><?= n($r['cantidad']) ?> <?= e($r['unidad']) ?></td><td>$ <?= n($r['importe'],2) ?></td></tr><?php endforeach; ?>
<?php if (!$resumen['productos']): ?><tr><td colspan="3" class="vacio">Sin ventas en este período.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
