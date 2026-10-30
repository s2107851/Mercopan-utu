<?php
require __DIR__.'/soporte_modulos.php';
require __DIR__.'/../includes/anulaciones.php';
require __DIR__.'/../includes/compras.php';
require __DIR__.'/../includes/produccion.php';
require __DIR__.'/../includes/reportes.php';
function estadoAnulaciones(): array {
    $r=[];
    foreach(['compras','producciones','ingredientes','productos','anulaciones','historial','facturas','factura_detalle'] as $t) $r[]=consulta('SELECT * FROM '.$t.' ORDER BY id')->fetchAll();
    $r[]=consulta('SELECT * FROM consumos ORDER BY produccion_id,ingrediente_id')->fetchAll();
    return $r;
}
function rechazarAnulacion(string $nombre,array $datos): void {
    $antes=estadoAnulaciones(); $rechazo=false;
    try { guardar(function() use($datos){anularOperacion($datos);}); } catch(RuntimeException $e) {$rechazo=true;}
    comprobarModulo($nombre,$rechazo && estadoAnulaciones()===$antes);
}
function anularPrueba(string $tipo,int $id): void {
    guardar(function() use($tipo,$id){anularOperacion(['tipo'=>$tipo,'id'=>(string)$id,'motivo'=>'Error de carga','confirmar'=>'1']);});
}
ejecutarPruebasModulo(['compras','recetas','produccion','facturas','anulaciones'],function(){
    consulta("INSERT INTO proveedores(nombre) VALUES ('Molino')");
    consulta("INSERT INTO ingredientes(nombre,unidad,stock) VALUES ('Harina','kg',10),('Sal','kg',5)");
    $_POST=['proveedor'=>'1','ingrediente'=>'1','cantidad'=>'2','costo_total'=>'20']; guardar('registrarCompra');
    $datos=['tipo'=>'compra','id'=>'1','motivo'=>'Error','confirmar'=>'1'];
    foreach(['motivo'=>'','confirmar'=>'','id'=>'1abc','tipo'=>'ventas'] as $k=>$v) rechazarAnulacion('Rechaza campo '.$k,array_replace($datos,[$k=>$v]));
    rechazarAnulacion('Rechaza inexistente',array_replace($datos,['id'=>'999']));
    $_SESSION['persona']=0; rechazarAnulacion('Rechaza sin responsable',$datos); $_SESSION['persona']=1;
    consulta('UPDATE ingredientes SET stock=1 WHERE id=1'); rechazarAnulacion('Compra sin stock no cambia nada',$datos);
    consulta('UPDATE ingredientes SET stock=12,activo=0 WHERE id=1');
    anularPrueba('compra',1);
    comprobarModulo('Revierte compra incluso ingrediente inactivo',(float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn()===10.0);
    comprobarModulo('Conserva operación original',(int)consulta('SELECT COUNT(*) FROM compras')->fetchColumn()===1);
    comprobarModulo('Guarda motivo y autor',anulacion('compra',1)['motivo']==='Error de carga' && anulacion('compra',1)['persona_nombre']==='Persona de prueba');
    rechazarAnulacion('Doble anulación no repite stock',$datos);
    comprobarModulo('Resumen excluye compras anuladas',(float)resumenPeriodo('2000-01-01','2099-01-01')['compras']['importe']===0.0);
    consulta("UPDATE personas SET nombre='Nombre nuevo' WHERE id=1");
    comprobarModulo('Conserva nombre histórico del anulador',anulacion('compra',1)['persona_nombre']==='Persona de prueba');
    $antes=estadoAnulaciones(); db()->exec(file_get_contents(__DIR__.'/../database/anulaciones.sql'));
    comprobarModulo('SQL repetible conserva datos',estadoAnulaciones()===$antes);
    consulta('UPDATE ingredientes SET activo=1 WHERE id=1');
    consulta("INSERT INTO recetas(producto_id,rendimiento,instrucciones) VALUES (1,20,'Prueba')");
    consulta('INSERT INTO receta_detalle VALUES (1,1,2),(1,2,1)');
    $_POST=['producto'=>'1','lotes'=>'1'];guardar('elaborar');
    consulta('UPDATE receta_detalle SET cantidad=4 WHERE ingrediente_id=1');
    consulta('UPDATE productos SET stock=19 WHERE id=1');
    $prod=['tipo'=>'produccion','id'=>'1','motivo'=>'Error','confirmar'=>'1'];
    rechazarAnulacion('Producción sin producto suficiente se rechaza',$prod);
    consulta('UPDATE productos SET stock=40 WHERE id=1');
    consulta('UPDATE ingredientes SET stock=999999999.999 WHERE id=2');
    rechazarAnulacion('Desborde en segundo consumo revierte producto y primer ingrediente',$prod);
    consulta('UPDATE ingredientes SET stock=4 WHERE id=2');
    anularPrueba('produccion',1);
    comprobarModulo('Retira producto terminado',(float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn()===20.0);
    comprobarModulo('Devuelve consumos originales, no receta editada',(float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn()===10.0 && (float)consulta('SELECT stock FROM ingredientes WHERE id=2')->fetchColumn()===5.0);
    comprobarModulo('Conserva producción y consumos',(int)consulta('SELECT COUNT(*) FROM producciones')->fetchColumn()===1 && (int)consulta('SELECT COUNT(*) FROM consumos')->fetchColumn()===2);
    rechazarAnulacion('Producción no puede anularse dos veces',$prod);
    $id=0;guardar(function() use(&$id){$id=guardarBorradorFactura(['proveedor'=>'1','numero'=>'F1','emision'=>'2026-10-29','items'=>[['ingrediente'=>'1','cantidad'=>'3','costo_total'=>'30'],['ingrediente'=>'2','cantidad'=>'2','costo_total'=>'10']]]);});
    $fac=['tipo'=>'factura','id'=>(string)$id,'motivo'=>'Error','confirmar'=>'1'];
    rechazarAnulacion('Borrador no se anula como confirmado',$fac);
    guardar(function() use($id){confirmarFactura($id,1);});
    $f=obtenerFactura($id);$compra=(int)$f['items'][0]['compra_id'];
    rechazarAnulacion('No anula renglón aislado de factura',array_replace($datos,['id'=>(string)$compra]));
    consulta('UPDATE ingredientes SET stock=1 WHERE id=2');
    rechazarAnulacion('Factura sin stock en segundo renglón revierte primero',$fac);
    consulta('UPDATE ingredientes SET stock=7 WHERE id=2');
    anularPrueba('factura',$id);
    comprobarModulo('Factura revierte todos sus ingredientes',(float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn()===10.0 && (float)consulta('SELECT stock FROM ingredientes WHERE id=2')->fetchColumn()===5.0);
    comprobarModulo('Anula cabecera y compras asociadas',anulacion('factura',$id)!==null && anulacion('compra',$compra)!==null && anulacion('compra',(int)$f['items'][1]['compra_id'])!==null);
    comprobarModulo('Conserva factura y detalle',obtenerFactura($id)===$f);
    rechazarAnulacion('Factura no se anula dos veces',$fac);
    comprobarModulo('Resumen excluye también renglones anulados',(float)resumenPeriodo('2000-01-01','2099-01-01')['compras']['importe']===0.0);
    $antes=estadoAnulaciones(); $_POST=['proveedor'=>'1','ingrediente'=>'1','cantidad'=>'1','costo_total'=>'1'];guardar('registrarCompra');$nuevo=(int)db()->lastInsertId();
    $compra=(int)consulta('SELECT MAX(id) FROM compras')->fetchColumn();
    $antes=estadoAnulaciones();try{guardar(function() use($compra){anularOperacion(['tipo'=>'compra','id'=>(string)$compra,'motivo'=>'Prueba rollback','confirmar'=>'1']);throw new RuntimeException('Fallo simulado posterior');});}catch(RuntimeException $e){}
    comprobarModulo('Fallo posterior revierte stock, anulación e historial',estadoAnulaciones()===$antes);
    $_SESSION['tokens']['hu16']=time();$_POST=['token'=>'hu16','responsable_id'=>'1'];verificar();$rechazo=false;try{verificar();}catch(RuntimeException $e){$rechazo=true;}
    comprobarModulo('Reenvío del formulario rechazado',$rechazo);
    $_SESSION['tokens']['otro']=time();$_POST=['token'=>'otro','responsable_id'=>'2'];$rechazo=false;try{verificar();}catch(RuntimeException $e){$rechazo=true;}
    comprobarModulo('Cambio de responsable rechazado',$rechazo);
});
