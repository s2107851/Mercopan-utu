<?php
// Todas las escrituras se invocan dentro de guardar().
function idFactura($valor): int {
    if (!is_scalar($valor) || !ctype_digit((string)$valor) || (float)$valor < 1 || (float)$valor > 2147483647) {
        throw new RuntimeException('Identificador inválido.');
    }
    return (int)$valor;
}
function textoFactura($valor, int $max): string {
    if (!is_string($valor) || trim($valor) === '' || strlen(trim($valor)) > $max) {
        throw new RuntimeException('Completá los datos de la factura y respetá su longitud máxima.');
    }
    return trim($valor);
}
function personaFactura(): array {
    $p = responsable();
    if (!$p) throw new RuntimeException('Elegí un responsable activo.');
    return $p;
}
function datosFactura(array $datos): array {
    $proveedor = consulta('SELECT id,nombre FROM proveedores WHERE id=? AND activo=1 FOR UPDATE', [idFactura($datos['proveedor'] ?? '')])->fetch();
    if (!$proveedor) throw new RuntimeException('El proveedor no existe o está inactivo.');
    $numero = textoFactura($datos['numero'] ?? '',80);
    $emision = fecha(textoFactura($datos['emision'] ?? '',10));
    if ($emision < '1000-01-01') throw new RuntimeException('La fecha de emisión debe ser posterior al año 999.');
    $items = $datos['items'] ?? null;
    if (!is_array($items) || count($items) > 50) throw new RuntimeException('Ingresá entre 1 y 50 renglones.');
    $lineas = []; $centavos = 0;
    foreach ($items as $item) {
        if (!is_array($item)) throw new RuntimeException('Renglón inválido.');
        foreach (['ingrediente','cantidad','costo_total'] as $campo) {
            if (isset($item[$campo]) && !is_scalar($item[$campo])) throw new RuntimeException('Renglón inválido.');
        }
        if (trim((string)($item['ingrediente'] ?? '')) === '' && trim((string)($item['cantidad'] ?? '')) === '' && trim((string)($item['costo_total'] ?? '')) === '') continue;
        $id = idFactura($item['ingrediente'] ?? '');
        if (isset($lineas[$id])) throw new RuntimeException('Reuní cada ingrediente en un solo renglón.');
        $cantidad = numero($item['cantidad'] ?? '',0.001,999999,3);
        $costo = numero($item['costo_total'] ?? '',0,999999999.99,2);
        $centavos += (int)round($costo * 100);
        if ($centavos > 99999999999) throw new RuntimeException('El total supera el máximo admitido.');
        $lineas[$id] = ['ingrediente_id'=>$id,'cantidad'=>$cantidad,'costo_total'=>$costo];
    }
    if (!$lineas) throw new RuntimeException('Ingresá al menos un ingrediente.');
    // Orden estable de bloqueo para evitar invertir bloqueos entre facturas.
    ksort($lineas);
    foreach ($lineas as $id=>&$linea) {
        $i = consulta('SELECT nombre,unidad FROM ingredientes WHERE id=? AND activo=1 FOR UPDATE',[$id])->fetch();
        if (!$i) throw new RuntimeException('Un ingrediente no existe o está inactivo.');
        if ($i['unidad'] === 'unidad' && floor($linea['cantidad']) !== $linea['cantidad']) throw new RuntimeException('Los ingredientes en unidades exigen cantidades enteras.');
        $linea['ingrediente_nombre']=$i['nombre']; $linea['unidad']=$i['unidad'];
    }
    unset($linea);
    return [$proveedor,$numero,$emision,$lineas,$centavos/100];
}
function bloquearFactura(int $id, int $version): array {
    $f = consulta('SELECT * FROM facturas WHERE id=? FOR UPDATE',[$id])->fetch();
    if (!$f || $f['estado'] !== 'borrador' || (int)$f['version'] !== $version) {
        throw new RuntimeException('La factura cambió o ya no es un borrador. Volvé a abrirla.');
    }
    return $f;
}
function guardarBorradorFactura(array $datos, int $id=0, int $version=0): int {
    $p=personaFactura();
    if ($id) bloquearFactura($id,$version);
    [$proveedor,$numero,$emision,$lineas,$total]=datosFactura($datos);
    if ($id) {
        consulta('UPDATE facturas SET proveedor_id=?,proveedor_nombre=?,numero=?,emision=?,total=?,version=version+1 WHERE id=?',[$proveedor['id'],$proveedor['nombre'],$numero,$emision,$total,$id]);
        consulta('DELETE FROM factura_detalle WHERE factura_id=?',[$id]);
    } else {
        consulta('INSERT INTO facturas(proveedor_id,proveedor_nombre,numero,emision,total,persona_id,persona_nombre) VALUES (?,?,?,?,?,?,?)',[$proveedor['id'],$proveedor['nombre'],$numero,$emision,$total,$p['id'],$p['nombre']]);
        $id=(int)db()->lastInsertId();
    }
    foreach ($lineas as $l) consulta('INSERT INTO factura_detalle(factura_id,ingrediente_id,ingrediente_nombre,unidad,cantidad,costo_total) VALUES (?,?,?,?,?,?)',[$id,$l['ingrediente_id'],$l['ingrediente_nombre'],$l['unidad'],$l['cantidad'],$l['costo_total']]);
    registrar('facturas','Guardó borrador de factura #'.$id.' (sin ingreso de stock).');
    return $id;
}
function confirmarFactura(int $id,int $version): void {
    $p=personaFactura(); $f=bloquearFactura($id,$version);
    if (!consulta('SELECT id FROM proveedores WHERE id=? AND activo=1 FOR UPDATE',[$f['proveedor_id']])->fetch()) throw new RuntimeException('El proveedor está inactivo.');
    $lineas=consulta('SELECT * FROM factura_detalle WHERE factura_id=? ORDER BY ingrediente_id',[$id])->fetchAll();
    if (!$lineas) throw new RuntimeException('La factura no tiene renglones.');
    // La clave única protege también confirmaciones simultáneas de dos borradores del mismo comprobante.
    consulta("UPDATE facturas SET estado='confirmada',numero_confirmado=numero,confirmada=CURRENT_TIMESTAMP,confirmador_id=?,confirmador_nombre=?,version=version+1 WHERE id=?",[$p['id'],$p['nombre'],$id]);
    foreach ($lineas as $l) {
        $i=consulta('SELECT stock,activo,unidad FROM ingredientes WHERE id=? FOR UPDATE',[$l['ingrediente_id']])->fetch();
        if (!$i || !$i['activo']) throw new RuntimeException('Un ingrediente está inactivo. Corregí el borrador.');
        if ($i['unidad'] !== $l['unidad']) throw new RuntimeException('Cambió la unidad de un ingrediente. Revisá y guardá el borrador nuevamente.');
        if ((float)$i['stock']+(float)$l['cantidad'] > 999999999.999) throw new RuntimeException('Un ingrediente supera el stock máximo.');
        consulta('INSERT INTO compras(proveedor_id,ingrediente_id,cantidad,costo_total,comprobante,persona_id) VALUES (?,?,?,?,?,?)',[$f['proveedor_id'],$l['ingrediente_id'],$l['cantidad'],$l['costo_total'],$f['numero'],$p['id']]);
        $compra=(int)db()->lastInsertId();
        consulta('UPDATE factura_detalle SET compra_id=? WHERE id=?',[$compra,$l['id']]);
        consulta('UPDATE ingredientes SET stock=stock+? WHERE id=?',[$l['cantidad'],$l['ingrediente_id']]);
    }
    registrar('facturas','Confirmó factura #'.$id.' · '.$f['numero'].' · $ '.n($f['total'],2).' · '.count($lineas).' ingresos de stock.');
}
function cancelarFactura(int $id,int $version): void {
    personaFactura(); bloquearFactura($id,$version);
    consulta("UPDATE facturas SET estado='cancelada',version=version+1 WHERE id=?",[$id]);
    registrar('facturas','Canceló borrador de factura #'.$id.' (sin ingreso de stock).');
}
function obtenerFactura(int $id): ?array {
    $f=consulta('SELECT * FROM facturas WHERE id=?',[$id])->fetch();
    if (!$f) return null;
    $f['items']=consulta('SELECT * FROM factura_detalle WHERE factura_id=? ORDER BY id',[$id])->fetchAll();
    return $f;
}
function listarFacturas(string $dia='',int $pagina=1): array {
    $where=''; $params=[];
    if ($dia !== '') { fecha($dia); $where=' WHERE emision=?'; $params=[$dia]; }
    $total=(int)consulta('SELECT COUNT(*) FROM facturas'.$where,$params)->fetchColumn();
    $paginas=max(1,(int)ceil($total/25)); $pagina=min($paginas,max(1,$pagina));
    $filas=consulta('SELECT * FROM facturas'.$where.' ORDER BY emision DESC,id DESC LIMIT 25 OFFSET '.(($pagina-1)*25),$params)->fetchAll();
    return compact('filas','total','paginas','pagina');
}
