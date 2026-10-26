<?php
require __DIR__ . '/soporte_modulos.php';
require __DIR__ . '/../includes/busqueda_catalogo.php';
ejecutarPruebasModulo([], function () {
    consulta("INSERT INTO ingredientes(nombre,unidad,stock,minimo) VALUES ('Harina','kg',100,5),('Sal','kg',8,1)");
    consulta("INSERT INTO ingredientes(nombre,unidad,stock,minimo,activo) VALUES ('Harina integral','kg',10,2,0)");
    foreach (['Pan 100%','Pan_otro','Pan!especial',"Pan d'oro",'Tostadas'] as $nombre) consulta('INSERT INTO productos(nombre,unidad,precio,stock) VALUES (?,?,?,?)',[$nombre,'unidad',10,4]);
    $antes = [consulta('SELECT * FROM ingredientes ORDER BY id')->fetchAll(),consulta('SELECT * FROM productos ORDER BY id')->fetchAll(),consulta('SELECT * FROM historial ORDER BY id')->fetchAll()];
    $casos = [
        ['ingredientes','hari',2], ['ingredientes','Harina',2], ['ingredientes','SAL',1],
        ['ingredientes','inexistente',0], ['ingredientes','',3], ['ingredientes','   ',3],
        ['productos','pan',5], ['productos','%',1], ['productos','_',1], ['productos','!',1],
        ['productos',"d'oro",1], ['productos',"' OR 1=1 --",0], ['productos','100%',1],
        ['productos','_otro',1], ['productos','   Pan   ',5], ['productos','inexistente',0]
    ];
    foreach ($casos as [$tabla,$texto,$esperado]) {
        $resultado = buscarCatalogo($tabla,['buscar'=>$texto]);
        comprobarModulo($tabla . ': ' . json_encode($texto), count($resultado['filas']) === $esperado && $resultado['buscar'] === trim($texto));
    }
    $resultado = buscarCatalogo('ingredientes',['buscar'=>'hari']);
    comprobarModulo('Incluye ingredientes inactivos al final', (int)$resultado['filas'][0]['activo']===1 && (int)$resultado['filas'][1]['activo']===0);
    $resultado = buscarCatalogo('productos',[]);
    comprobarModulo('Sin filtro restaura todos los productos', count($resultado['filas'])===7);
    $f = filtroCatalogo('productos',['buscar'=>"' OR 1=1 --"]);
    comprobarModulo('Texto separado del SQL mediante parámetros', strpos($f['sql'], "OR 1=1")===false && count($f['params'])===1);
    foreach ([['buscar'=>str_repeat('a',101)], ['buscar'=>['pan']], ['buscar'=>23]] as $datos) {
        $rechazado=false; try { buscarCatalogo('productos',$datos); } catch (RuntimeException $ex) { $rechazado=true; }
        comprobarModulo('Rechaza búsqueda mal formada', $rechazado);
    }
    $rechazado=false; try { filtroCatalogo('personas',[]); } catch (RuntimeException $ex) {$rechazado=true;}
    comprobarModulo('No permite elegir otras tablas', $rechazado);
    comprobarModulo('Acepta exactamente 100 bytes', strlen(filtroCatalogo('productos',['buscar'=>str_repeat('a',100)])['buscar'])===100);
    $despues = [consulta('SELECT * FROM ingredientes ORDER BY id')->fetchAll(),consulta('SELECT * FROM productos ORDER BY id')->fetchAll(),consulta('SELECT * FROM historial ORDER BY id')->fetchAll()];
    comprobarModulo('Buscar conserva datos, stock e historial', $antes===$despues);
});
