<?php
// Ejecutar por terminal: php tests/produccion.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por terminal.'); }
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/produccion.php';

$basePrueba = 'mercopan_utu_pruebas_' . bin2hex(random_bytes(6));
$config['base'] = $basePrueba;
$servidor = conectar(false);
$creada = false;
$pruebas = 0;
$fallo = false;
function comprobar(string $nombre, bool $condicion): void {
    global $pruebas;
    if (!$condicion) throw new RuntimeException('FALLÓ: ' . $nombre);
    $pruebas++;
    echo 'OK: ' . $nombre . PHP_EOL;
}
function estadoStock(): array {
    return [
        consulta('SELECT id, stock FROM ingredientes ORDER BY id')->fetchAll(),
        consulta('SELECT id, stock FROM productos ORDER BY id')->fetchAll(),
        consulta('SELECT COUNT(*) FROM producciones')->fetchColumn(),
        consulta('SELECT COUNT(*) FROM consumos')->fetchColumn(),
        consulta('SELECT COUNT(*) FROM historial')->fetchColumn()
    ];
}
function rechazaSinCambios(string $nombre, array $datos): void {
    $antes = estadoStock();
    $_POST = $datos;
    $rechazo = false;
    try { guardar('elaborar'); } catch (RuntimeException $e) { $rechazo = true; }
    comprobar($nombre, $rechazo && $antes === estadoStock());
}

try {
    $servidor->exec('CREATE DATABASE `' . $basePrueba . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $creada = true;
    foreach (['base','productos','recetas','produccion','demo','demo_produccion'] as $archivo) {
        db()->exec(file_get_contents(__DIR__ . '/../database/' . $archivo . '.sql'));
    }
    $_SESSION['persona'] = 1;
    $_POST = ['producto'=>1, 'lotes'=>1];
    guardar('elaborar');
    comprobar('El lote genera 20 panes', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn() === 20.0);
    comprobar('Descuenta 5 kg de harina', (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn() === 95.0);
    comprobar('Descuenta 0,120 kg de levadura', (float)consulta('SELECT stock FROM ingredientes WHERE id=2')->fetchColumn() === 5.88);
    comprobar('Descuenta 0,080 kg de sal', (float)consulta('SELECT stock FROM ingredientes WHERE id=3')->fetchColumn() === 7.92);
    comprobar('Guarda los tres consumos', (int)consulta('SELECT COUNT(*) FROM consumos')->fetchColumn() === 3);
    comprobar('Registra al responsable', (int)consulta('SELECT persona_id FROM producciones LIMIT 1')->fetchColumn() === 1);
    comprobar('Guarda la acción en historial', (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='produccion' AND persona_id=1")->fetchColumn() === 1);

    consulta('UPDATE ingredientes SET stock=0.01 WHERE id=3');
    rechazaSinCambios('Falta el último ingrediente: revierte consumos previos', ['producto'=>1, 'lotes'=>1]);
    consulta('UPDATE ingredientes SET stock=7.92 WHERE id=3');
    consulta('UPDATE ingredientes SET activo=0 WHERE id=2');
    rechazaSinCambios('Ingrediente inactivo no modifica existencias', ['producto'=>1, 'lotes'=>1]);
    consulta('UPDATE ingredientes SET activo=1 WHERE id=2');
    rechazaSinCambios('Producto sin receta', ['producto'=>2, 'lotes'=>1]);
    rechazaSinCambios('Producto inexistente', ['producto'=>99999, 'lotes'=>1]);
    foreach ([0, -1, 1.5, 1001, 'texto'] as $lotes) {
        rechazaSinCambios('Lotes inválidos: ' . $lotes, ['producto'=>1, 'lotes'=>$lotes]);
    }
    consulta('UPDATE productos SET activo=0 WHERE id=1');
    rechazaSinCambios('Producto inactivo', ['producto'=>1, 'lotes'=>1]);
    consulta('UPDATE productos SET activo=1 WHERE id=1');
    consulta('UPDATE productos SET stock=999999999.990 WHERE id=1');
    rechazaSinCambios('Protege contra desbordamiento del stock', ['producto'=>1, 'lotes'=>1]);
    consulta('UPDATE productos SET stock=20 WHERE id=1');

    // Una receta futura no debe modificar los consumos ya registrados.
    consulta('UPDATE receta_detalle SET cantidad=6 WHERE producto_id=1 AND ingrediente_id=1');
    comprobar('Conserva consumos al editar receta', (float)consulta('SELECT cantidad FROM consumos WHERE ingrediente_id=1')->fetchColumn() === 5.0);
    $_POST = ['producto'=>1, 'lotes'=>2]; guardar('elaborar');
    comprobar('Dos lotes aplican la receta nueva', (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn() === 83.0);
    comprobar('Dos lotes agregan 40 panes', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn() === 60.0);

    $_SESSION['tokens']['prueba-unica'] = time();
    $_POST = ['token'=>'prueba-unica','responsable_id'=>1]; verificar();
    $rechazo = false;
    try { verificar(); } catch (RuntimeException $e) { $rechazo = true; }
    comprobar('Rechaza reenviar el mismo formulario', $rechazo);
    $_SESSION['tokens']['prueba-persona'] = time();
    $_POST = ['token'=>'prueba-persona', 'responsable_id'=>2];
    $rechazo = false;
    try { verificar(); } catch (RuntimeException $e) { $rechazo = true; }
    comprobar('Rechaza responsable distinto al del formulario', $rechazo);
    $_SESSION['persona'] = 99999;
    rechazaSinCambios('Sin responsable válido revierte la operación', ['producto'=>1,'lotes'=>1]);
    echo $pruebas . ' pruebas correctas.' . PHP_EOL;
} catch (Throwable $e) {
    $fallo = true;
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
} finally {
    // Solo borra la base aleatoria que esta ejecución logró crear.
    if ($creada) $servidor->exec('DROP DATABASE `' . $basePrueba . '`');
}
exit($fallo ? 1 : 0);
