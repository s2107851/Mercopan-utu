<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por terminal.'); }
// Prueba sin modificar bases de datos. Ejecutar: php tests/consultas.php
require_once __DIR__.'/../includes/funciones.php';
require_once __DIR__.'/../includes/reportes.php';
require_once __DIR__.'/../includes/filtros_historial.php';
require_once __DIR__.'/../includes/csv.php';
$pruebas=0;
function comprobar($condicion, string $mensaje): void {
    global $pruebas;
    if (!$condicion) throw new RuntimeException($mensaje);
    $pruebas++;
}
$p=periodoReporte(['desde'=>'2024-02-01','hasta'=>'2024-02-29']);
comprobar($p===['2024-02-01','2024-02-29','2024-03-01'],'Fin de febrero bisiesto.');
foreach([
    ['desde'=>'2026-02-30','hasta'=>'2026-03-01'],
    ['desde'=>'2026-10-20','hasta'=>'2026-10-01']
] as $datos) {
    $rechazado=false;
    try {periodoReporte($datos);} catch(RuntimeException $e) {$rechazado=true;}
    comprobar($rechazado,'Fecha inválida o intervalo invertido.');
}
$f=filtrosHistorial(['desde'=>'2026-10-01','hasta'=>'2026-10-31','persona'=>'2','modulo'=>'ventas','buscar'=>'100%']);
comprobar($f['params']===['2026-10-01','2026-11-01',2,'ventas','100%'],'Parámetros separados del SQL.');
comprobar(strpos($f['where'],'100%')===false,'Texto del usuario fuera del SQL.');
foreach(['=1+1','+SUM(A1)','-1+1','@SUM(A1)',"\t=1"] as $texto) {
    comprobar(substr(celdaCsv($texto),0,1)==="'",'Protección de fórmulas CSV.');
}
comprobar(celdaCsv('Pan flauta')==='Pan flauta','Texto normal.');
$archivo=fopen('php://temp','w+');
filaCsv($archivo,['Pan; integral','Dijo "hola"',"Dos\nlíneas"]);
rewind($archivo);
comprobar(fgetcsv($archivo,0,';','"','')===['Pan; integral','Dijo "hola"',"Dos\nlíneas"],'CSV conserva comillas, separadores y saltos.');
fclose($archivo);
echo $pruebas." comprobaciones correctas.\n";
