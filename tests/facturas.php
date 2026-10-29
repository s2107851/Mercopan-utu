<?php
require __DIR__.'/soporte_modulos.php';
require __DIR__.'/../includes/facturas.php';
require __DIR__.'/../includes/reportes.php';
function estadoFacturas(): array {
    $r=[];
    foreach (['facturas','factura_detalle','compras','historial','ingredientes'] as $tabla) $r[]=consulta('SELECT * FROM '.$tabla.' ORDER BY id')->fetchAll();
    return $r;
}
function rechazarFactura(string $nombre, callable $accion): void {
    $antes=estadoFacturas(); $rechazada=false;
    try { guardar($accion); } catch (Throwable $ex) { $rechazada=true; }
    comprobarModulo($nombre,$rechazada && estadoFacturas()===$antes);
}
function nuevoBorrador(array $datos): int {
    $id=0; guardar(function() use($datos,&$id) { $id=guardarBorradorFactura($datos); }); return $id;
}
ejecutarPruebasModulo(['compras','facturas'],function() {
    consulta("INSERT INTO proveedores(nombre) VALUES ('Molino')");
    consulta("INSERT INTO ingredientes(nombre,unidad,stock) VALUES ('Harina','kg',10),('Huevos','unidad',12)");
    // Compatibilidad: compra previa independiente y migración repetible.
    consulta("INSERT INTO compras(proveedor_id,ingrediente_id,cantidad,costo_total,comprobante,persona_id) VALUES (1,1,1,20,'PREVIA',1)");
    $antes=estadoFacturas(); db()->exec(file_get_contents(__DIR__.'/../database/facturas.sql'));
    comprobarModulo('Migración repetible conserva datos',estadoFacturas()===$antes);
    $datos=['proveedor'=>'1','numero'=>'A-100','emision'=>'2026-10-29','items'=>[
        ['ingrediente'=>'1','cantidad'=>'5','costo_total'=>'100,10'],
        ['ingrediente'=>'2','cantidad'=>'6','costo_total'=>'30,20']]];
    $id=nuevoBorrador($datos); $f=obtenerFactura($id);
    comprobarModulo('Cabecera y dos renglones persistidos',count($f['items'])===2 && $f['numero']==='A-100' && $f['emision']==='2026-10-29');
    comprobarModulo('Total exacto de renglones',(float)$f['total']===130.30);
    comprobarModulo('Borrador sin stock ni compras', (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn()===10.0 && (int)consulta('SELECT COUNT(*) FROM compras')->fetchColumn()===1);
    comprobarModulo('Responsable de carga histórico',$f['persona_nombre']==='Persona de prueba');
    $datos['items'][0]['cantidad']='3';
    guardar(function() use($datos,$id){guardarBorradorFactura($datos,$id,1);});
    comprobarModulo('Editar conserva tres kg sin ingreso', (float)obtenerFactura($id)['items'][0]['cantidad']===3.0 && (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn()===10.0);
    rechazarFactura('Rechaza edición de pestaña antigua',function() use($datos,$id){guardarBorradorFactura($datos,$id,1);});
    rechazarFactura('Rechaza confirmar versión anterior',function() use($id){confirmarFactura($id,1);});
    guardar(function() use($id){confirmarFactura($id,2);}); $f=obtenerFactura($id);
    comprobarModulo('Confirma e ingresa tres kg y seis huevos',(float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn()===13.0 && (float)consulta('SELECT stock FROM ingredientes WHERE id=2')->fetchColumn()===18.0);
    comprobarModulo('Una compra por línea y enlaces únicos',(int)consulta('SELECT COUNT(*) FROM compras')->fetchColumn()===3 && $f['items'][0]['compra_id'] && $f['items'][1]['compra_id']);
    comprobarModulo('Estado y responsable confirmador',$f['estado']==='confirmada' && $f['confirmador_nombre']==='Persona de prueba');
    comprobarModulo('Resumen incluye factura una sola vez',abs((float)resumenPeriodo('2026-01-01','2027-01-01')['compras']['importe']-150.30)<0.001);
    rechazarFactura('Reconfirmación no duplica ingresos',function() use($id){confirmarFactura($id,3);});
    rechazarFactura('Confirmada no se edita',function() use($datos,$id){guardarBorradorFactura($datos,$id,3);});
    rechazarFactura('Confirmada no se cancela como borrador',function() use($id){cancelarFactura($id,3);});
    $duplicado=nuevoBorrador($datos);
    rechazarFactura('Factura duplicada de proveedor rechazada',function() use($duplicado){confirmarFactura($duplicado,1);});
    guardar(function() use($duplicado){cancelarFactura($duplicado,1);});
    comprobarModulo('Cancelación conserva borrador sin ingreso',obtenerFactura($duplicado)['estado']==='cancelada' && (int)consulta('SELECT COUNT(*) FROM compras')->fetchColumn()===3);
    rechazarFactura('Cancelada no se confirma',function() use($duplicado){confirmarFactura($duplicado,2);});
    $datos['numero']='A-101'; $id2=nuevoBorrador($datos);
    consulta('UPDATE ingredientes SET activo=0 WHERE id=2');
    rechazarFactura('Ingrediente inactivo revierte todo, incluso primer renglón',function() use($id2){confirmarFactura($id2,1);});
    consulta('UPDATE ingredientes SET activo=1,stock=999999999.999 WHERE id=2');
    rechazarFactura('Desborde en segundo ingrediente revierte primer ingreso',function() use($id2){confirmarFactura($id2,1);});
    consulta('UPDATE ingredientes SET stock=18 WHERE id=2');
    consulta('UPDATE proveedores SET activo=0 WHERE id=1');
    rechazarFactura('Proveedor inactivo impide confirmar',function() use($id2){confirmarFactura($id2,1);});
    consulta('UPDATE proveedores SET activo=1 WHERE id=1');
    foreach (['vacío'=>[], 'repetido'=>[$datos['items'][0],$datos['items'][0]],'fracción de unidad'=>[['ingrediente'=>'2','cantidad'=>'1.5','costo_total'=>'2']], 'cero'=>[['ingrediente'=>'1','cantidad'=>'0','costo_total'=>'2']], 'costo negativo'=>[['ingrediente'=>'1','cantidad'=>'1','costo_total'=>'-2']], 'tres decimales de dinero'=>[['ingrediente'=>'1','cantidad'=>'1','costo_total'=>'1.111']], 'identificador inválido'=>[['ingrediente'=>'1abc','cantidad'=>'1','costo_total'=>'2']]] as $nombre=>$items) {
        $d=$datos; $d['items']=$items;
        rechazarFactura('Rechaza '.$nombre,function() use($d){guardarBorradorFactura($d);});
    }
    foreach (['numero'=>'','emision'=>'2026-02-30','proveedor'=>'9999'] as $campo=>$valor) {
        $d=$datos; $d[$campo]=$valor;
        rechazarFactura('Rechaza campo '.$campo,function() use($d){guardarBorradorFactura($d);});
    }
    $d=$datos; $d['items']=array_fill(0,51,$datos['items'][0]);
    rechazarFactura('Rechaza más de cincuenta líneas',function() use($d){guardarBorradorFactura($d);});
    $d=$datos; $d['items'][0]['costo_total']='999999999.99'; $d['items'][1]['costo_total']='999999999.99';
    rechazarFactura('Rechaza total fuera de capacidad',function() use($d){guardarBorradorFactura($d);});
    $d=$datos; $d['items'][]=['ingrediente'=>'','cantidad'=>'','costo_total'=>'']; $d['numero']='A-102';
    $id3=nuevoBorrador($d); comprobarModulo('Ignora filas totalmente vacías',count(obtenerFactura($id3)['items'])===2);
    comprobarModulo('Filtra fecha de emisión sin duplicar líneas',listarFacturas('2026-10-29')['total']===4 && count(listarFacturas('2026-10-29')['filas'])===4);
    comprobarModulo('Período sin facturas',listarFacturas('2025-01-01')['total']===0);
    consulta("UPDATE personas SET nombre='Nombre nuevo' WHERE id=1");
    consulta("UPDATE proveedores SET nombre='Proveedor nuevo' WHERE id=1");
    consulta("UPDATE ingredientes SET nombre='Ingrediente nuevo' WHERE id=1");
    $f=obtenerFactura($id);
    comprobarModulo('Detalle confirmado conserva nombres históricos',$f['persona_nombre']==='Persona de prueba' && $f['proveedor_nombre']==='Molino' && $f['items'][0]['ingrediente_nombre']==='Harina');
    $antes=estadoFacturas(); $_SESSION['persona']=0;
    rechazarFactura('No guarda sin responsable',function() use($d){guardarBorradorFactura($d);});
    $_SESSION['persona']=1;
    $_SESSION['tokens']['unico']=time(); $_POST=['token'=>'unico','responsable_id'=>1]; verificar();
    $rechazo=false; try { verificar(); } catch(Throwable $ex) {$rechazo=true;}
    comprobarModulo('Reenvío de token rechazado',$rechazo);
    $_SESSION['tokens']['cambio']=time(); $_POST=['token'=>'cambio','responsable_id'=>999];
    $rechazo=false; try { verificar(); } catch(Throwable $ex) {$rechazo=true;}
    comprobarModulo('Cambio de responsable rechaza formulario',$rechazo);
});
