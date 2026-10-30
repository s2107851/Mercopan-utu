<?php
require_once __DIR__.'/facturas.php';
function anulacion(string $tipo, int $id): ?array {
    $a=consulta('SELECT * FROM anulaciones WHERE tipo=? AND registro_id=?',[$tipo,$id])->fetch();
    return $a ?: null;
}
function guardarAnulacion(string $tipo,int $id,string $motivo,array $persona): void {
    consulta('INSERT INTO anulaciones(tipo,registro_id,motivo,persona_id,persona_nombre) VALUES (?,?,?,?,?)',
        [$tipo,$id,$motivo,$persona['id'],$persona['nombre']]);
    registrar('anulaciones','Anuló '.$tipo.' #'.$id.' · Motivo: '.$motivo);
}
// Siempre dentro de guardar(): bloqueos, reversión y auditoría son indivisibles.
function anularOperacion(array $datos): void {
    if (!db()->inTransaction()) throw new RuntimeException('La anulación requiere una transacción.');
    $persona=personaFactura();
    $tipo=$datos['tipo'] ?? '';
    if (!in_array($tipo,['compra','produccion','factura'],true)) throw new RuntimeException('Tipo de operación inválido.');
    $id=idFactura($datos['id'] ?? '');
    $motivo=textoFactura($datos['motivo'] ?? '',250);
    if (($datos['confirmar'] ?? '') !== '1') throw new RuntimeException('Confirmá que revisaste la operación.');
    $tabla=['compra'=>'compras','produccion'=>'producciones','factura'=>'facturas'][$tipo];
    $original=consulta('SELECT * FROM '.$tabla.' WHERE id=? FOR UPDATE',[$id])->fetch();
    if (!$original) throw new RuntimeException('La operación no existe.');
    // Lectura bloqueante: ve la última anulación incluso tras esperar a otra transacción.
    if (consulta('SELECT id FROM anulaciones WHERE tipo=? AND registro_id=? FOR UPDATE',[$tipo,$id])->fetch()) throw new RuntimeException('Esta operación ya fue anulada.');
    if ($tipo==='compra') {
        if (consulta('SELECT factura_id FROM factura_detalle WHERE compra_id=?',[$id])->fetch()) throw new RuntimeException('Esta compra pertenece a una factura. Anulá la factura completa desde Facturas.');
        revertirCompra($original);
    } elseif ($tipo==='factura') {
        if ($original['estado']!=='confirmada') throw new RuntimeException('Solo se anulan facturas confirmadas. Los borradores se cancelan desde Facturas.');
        $lineas=consulta('SELECT compra_id FROM factura_detalle WHERE factura_id=? ORDER BY ingrediente_id',[$id])->fetchAll();
        if (!$lineas) throw new RuntimeException('La factura no tiene compras para revertir.');
        foreach ($lineas as $linea) {
            $c=consulta('SELECT * FROM compras WHERE id=? FOR UPDATE',[$linea['compra_id']])->fetch();
            if (!$c || anulacion('compra',(int)$c['id'])) throw new RuntimeException('La factura tiene una compra ausente o ya anulada.');
            revertirCompra($c);
            guardarAnulacion('compra',(int)$c['id'],$motivo,$persona);
        }
        // Se conserva el número confirmado: la anulación no habilita duplicar comprobantes.
    } else {
        $p=consulta('SELECT * FROM productos WHERE id=? FOR UPDATE',[$original['producto_id']])->fetch();
        if (!$p || (float)$p['stock']+0.0000001<(float)$original['cantidad']) throw new RuntimeException('No hay suficientes productos para revertir esta producción.');
        $consumos=consulta('SELECT * FROM consumos WHERE produccion_id=? ORDER BY ingrediente_id',[$id])->fetchAll();
        if (!$consumos) throw new RuntimeException('No hay consumos históricos para revertir esta producción.');
        consulta('UPDATE productos SET stock=stock-? WHERE id=?',[$original['cantidad'],$p['id']]);
        foreach ($consumos as $c) {
            $i=consulta('SELECT * FROM ingredientes WHERE id=? FOR UPDATE',[$c['ingrediente_id']])->fetch();
            if (!$i || (float)$i['stock']+(float)$c['cantidad']>999999999.999) throw new RuntimeException('La devolución de ingredientes supera el stock máximo.');
            consulta('UPDATE ingredientes SET stock=stock+? WHERE id=?',[$c['cantidad'],$i['id']]);
        }
    }
    guardarAnulacion($tipo,$id,$motivo,$persona);
}
function revertirCompra(array $c): void {
    $i=consulta('SELECT * FROM ingredientes WHERE id=? FOR UPDATE',[$c['ingrediente_id']])->fetch();
    if (!$i || (float)$i['stock']+0.0000001<(float)$c['cantidad']) throw new RuntimeException('No hay suficientes ingredientes para revertir la compra #'.$c['id'].'.');
    consulta('UPDATE ingredientes SET stock=stock-? WHERE id=?',[$c['cantidad'],$i['id']]);
}
function enlaceAnulacion(string $tipo,int $id): void {
    $a=anulacion($tipo,$id);
    if ($a) echo '<p class="aviso"><strong>Anulada</strong> · '.e($a['fecha']).' · '.e($a['persona_nombre']).'<br>Motivo: '.e($a['motivo']).'</p>';
    elseif ($tipo==='compra' && ($factura=consulta('SELECT factura_id FROM factura_detalle WHERE compra_id=?',[$id])->fetchColumn())) echo '<a class="boton secundario" href="facturas.php?id='.e($factura).'">Ver factura para anular</a>';
    else echo '<a class="boton secundario" href="anular.php?'.e(http_build_query(['tipo'=>$tipo,'id'=>$id])).'">Anular operación</a>';
}
