<?php
// Un intervalo inclusivo en pantalla se convierte a [desde, día siguiente).
function periodoReporte(array $datos): array {
    $desde = fecha((string)($datos['desde'] ?? date('Y-m-01')));
    $hasta = fecha((string)($datos['hasta'] ?? date('Y-m-d')));
    if ($desde > $hasta) throw new RuntimeException('La fecha inicial no puede superar la final.');
    if ($hasta >= '9999-12-31') throw new RuntimeException('Elegí una fecha final anterior a 9999-12-31.');
    return [$desde, $hasta, (new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d')];
}
function resumenPeriodo(string $desde, string $fin): array {
    $ventas = consulta('SELECT COUNT(*) registros, COALESCE(SUM(total),0) importe FROM ventas WHERE fecha>=? AND fecha<?', [$desde,$fin])->fetch();
    $compras = consulta('SELECT COUNT(*) registros, COALESCE(SUM(costo_total),0) importe FROM compras WHERE fecha>=? AND fecha<? AND NOT EXISTS (SELECT 1 FROM anulaciones a WHERE a.tipo=\'compra\' AND a.registro_id=compras.id)', [$desde,$fin])->fetch();
    $productos = consulta('SELECT p.id,p.nombre,p.unidad,SUM(v.cantidad) cantidad,SUM(v.total) importe FROM ventas v JOIN productos p ON p.id=v.producto_id WHERE v.fecha>=? AND v.fecha<? GROUP BY p.id,p.nombre,p.unidad ORDER BY importe DESC,p.nombre', [$desde,$fin])->fetchAll();
    return ['ventas'=>$ventas,'compras'=>$compras,'productos'=>$productos];
}
