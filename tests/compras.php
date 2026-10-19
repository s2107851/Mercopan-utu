<?php
// Ejecutar por terminal: php tests/compras.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por terminal.'); }
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/compras.php';

$basePrueba = 'mercopan_utu_pruebas_' . bin2hex(random_bytes(6));
$config['base'] = $basePrueba;
$servidor = conectar(false);
$creada = false;
$pruebas = 0;
$fallo = false;

function comprobarCompra(string $nombre, bool $condicion): void {
    global $pruebas;
    if (!$condicion) throw new RuntimeException('FALLÓ: ' . $nombre);
    $pruebas++;
    echo 'OK: ' . $nombre . PHP_EOL;
}
function estadoCompra(): array {
    return [
        consulta('SELECT id, stock FROM ingredientes ORDER BY id')->fetchAll(),
        (int)consulta('SELECT COUNT(*) FROM compras')->fetchColumn(),
        (int)consulta('SELECT COUNT(*) FROM historial')->fetchColumn()
    ];
}
function rechazarCompra(string $nombre, array $datos): void {
    $antes = estadoCompra();
    $_POST = $datos;
    $rechazado = false;
    try { guardar('registrarCompra'); } catch (RuntimeException $e) { $rechazado = true; }
    comprobarCompra($nombre, $rechazado && estadoCompra() === $antes);
}

try {
    $servidor->exec('CREATE DATABASE `' . $basePrueba . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $creada = true;
    foreach (['base', 'compras', 'demo'] as $archivo) {
        db()->exec(file_get_contents(__DIR__ . '/../database/' . $archivo . '.sql'));
    }
    $_SESSION['persona'] = 1;
    $valida = ['proveedor'=>'1', 'ingrediente'=>'1', 'cantidad'=>'2,500', 'costo_total'=>'1250,45', 'comprobante'=>'A-001'];
    $_POST = $valida;
    guardar('registrarCompra');
    comprobarCompra('La compra suma 2,5 kg de harina', (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn() === 102.5);
    comprobarCompra('Guarda la cantidad de la compra', (float)consulta('SELECT cantidad FROM compras WHERE id=1')->fetchColumn() === 2.5);
    comprobarCompra('Guarda el precio con 2 decimales', (float)consulta('SELECT costo_total FROM compras WHERE id=1')->fetchColumn() === 1250.45);
    comprobarCompra('Relaciona proveedor y responsable', consulta('SELECT proveedor_id,persona_id FROM compras WHERE id=1')->fetch() == ['proveedor_id'=>1,'persona_id'=>1]);
    comprobarCompra('Conserva número de comprobante', consulta('SELECT comprobante FROM compras WHERE id=1')->fetchColumn() === 'A-001');
    comprobarCompra('Registra acción en historial', (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='compras' AND persona_id=1")->fetchColumn() === 1);

    rechazarCompra('No admite cantidades nulas', array_replace($valida, ['cantidad'=>'0']));
    rechazarCompra('No admite cantidades negativas', array_replace($valida, ['cantidad'=>'-5']));
    rechazarCompra('No admite más de 3 decimales', array_replace($valida, ['cantidad'=>'0.0001']));
    rechazarCompra('No admite valores no numéricos', array_replace($valida, ['cantidad'=>'abc']));
    rechazarCompra('No admite un proveedor inexistente', array_replace($valida, ['proveedor'=>'999']));
    rechazarCompra('No admite ID con caracteres adicionales', array_replace($valida, ['proveedor'=>'1abc']));
    rechazarCompra('No admite ingrediente inexistente', array_replace($valida, ['ingrediente'=>'999']));
    rechazarCompra('Huevos se ingresan en unidades enteras', array_replace($valida, ['ingrediente'=>'6','cantidad'=>'1.5']));
    rechazarCompra('No admite costo negativo', array_replace($valida, ['costo_total'=>'-1']));
    rechazarCompra('No admite costo con más de 2 decimales', array_replace($valida, ['costo_total'=>'12.345']));
    rechazarCompra('No admite comprobante excesivamente largo', array_replace($valida, ['comprobante'=>str_repeat('X',81)]));

    consulta('UPDATE proveedores SET activo=0 WHERE id=1');
    rechazarCompra('Proveedor inactivo', $valida);
    consulta('UPDATE proveedores SET activo=1 WHERE id=1');
    consulta('UPDATE ingredientes SET activo=0 WHERE id=1');
    rechazarCompra('Ingrediente inactivo', $valida);
    consulta('UPDATE ingredientes SET activo=1 WHERE id=1');
    consulta('UPDATE ingredientes SET stock=999999999.999 WHERE id=1');
    rechazarCompra('No desborda el límite del stock', $valida);
    consulta('UPDATE ingredientes SET stock=102.500 WHERE id=1');

    $_SESSION['persona'] = 99999;
    rechazarCompra('Si el responsable no existe, revierte toda la compra', $valida);
    $_SESSION['persona'] = 1;

    // Se utiliza el mismo mecanismo CSRF/antirreenvío del resto del sistema.
    $_SESSION['tokens']['unico-compras'] = time();
    $_POST = ['token'=>'unico-compras','responsable_id'=>1];
    verificar();
    $rechazo = false;
    try { verificar(); } catch (RuntimeException $e) { $rechazo = true; }
    comprobarCompra('Rechaza envío repetido de formulario', $rechazo);

    echo $pruebas . ' pruebas correctas.' . PHP_EOL;
} catch (Throwable $e) {
    $fallo = true;
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
} finally {
    if ($creada) $servidor->exec('DROP DATABASE `' . $basePrueba . '`');
}
exit($fallo ? 1 : 0);
