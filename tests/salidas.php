<?php
require __DIR__ . '/soporte_modulos.php';
require __DIR__ . '/../includes/salidas.php';
ejecutarPruebasModulo(['salidas'], function () {
    $valido=['producto'=>'1','cantidad'=>'3','tipo'=>'perdida','motivo'=>'Producto quemado'];
    $_POST=$valido; guardar('registrarSalida');
    $s=consulta('SELECT * FROM salidas WHERE id=1')->fetch();
    comprobarModulo('Pérdida descuenta tres unidades', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn()===17.0);
    comprobarModulo('Conserva stock anterior y nuevo, motivo y responsable', (float)$s['stock_anterior']===20.0 && (float)$s['stock_nuevo']===17.0 && $s['motivo']==='Producto quemado' && $s['persona_nombre']==='Persona de prueba');
    $_POST=['producto'=>'2','cantidad'=>'0.750','tipo'=>'donacion','motivo'=>'Merendero']; guardar('registrarSalida');
    comprobarModulo('Donación admite kg con decimales', (float)consulta('SELECT stock FROM productos WHERE id=2')->fetchColumn()===4.75);
    comprobarModulo('No genera ventas', (int)consulta('SELECT COUNT(*) FROM ventas')->fetchColumn()===0);
    comprobarModulo('Registra historial de las dos salidas', (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='salidas'")->fetchColumn()===2);
    foreach ([['cantidad'=>'0'], ['cantidad'=>'-1'], ['cantidad'=>'1.5'], ['cantidad'=>'18'], ['cantidad'=>'0.0001'], ['tipo'=>'venta'], ['motivo'=>'  '], ['producto'=>'999999']] as $c) {
        $_POST=array_replace($valido,$c); rechazarModulo('salidas','Rechaza salida inválida ' . json_encode($c),'registrarSalida');
    }
    consulta('UPDATE productos SET activo=0 WHERE id=1'); $_POST=$valido; rechazarModulo('salidas','Producto inactivo','registrarSalida'); consulta('UPDATE productos SET activo=1 WHERE id=1');
    $_SESSION['persona']=99999; rechazarModulo('salidas','Responsable inexistente','registrarSalida'); $_SESSION['persona']=1;
    db()->exec("CREATE TRIGGER fallo_salida BEFORE INSERT ON historial FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo simulado'");
    rechazarModulo('salidas','Fallo en historial revierte salida y stock','registrarSalida'); db()->exec('DROP TRIGGER fallo_salida');
    $_POST=array_replace($valido,['cantidad'=>'17']); guardar('registrarSalida');
    comprobarModulo('Permite retirar todo y dejar cero', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn()===0.0);
    $_POST=$valido; rechazarModulo('salidas','No permite stock negativo','registrarSalida');
    $_SESSION['tokens']['salida-unica']=time(); $_POST=['token'=>'salida-unica','responsable_id'=>1]; verificar();
    $rechazada=false; try { verificar(); } catch (RuntimeException $ex) {$rechazada=true;}
    comprobarModulo('Bloquea reenvío del formulario', $rechazada);
    db()->exec(file_get_contents(__DIR__ . '/../database/salidas.sql'));
    comprobarModulo('Instalación repetible conserva salidas', (int)consulta('SELECT COUNT(*) FROM salidas')->fetchColumn()===3);
});
