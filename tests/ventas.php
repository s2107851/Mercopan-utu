<?php
// Ejecutar solamente por terminal: php tests/ventas.php.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por terminal.'); }
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/ventas.php';

$basePrueba = 'mercopan_utu_pruebas_' . bin2hex(random_bytes(6));
$config['base'] = $basePrueba;
$servidor = conectar(false);
$creada = false;
$pruebas = 0;
$fallo = false;

function comprobarVenta(string $descripcion, bool $condicion): void {
    global $pruebas;
    if (!$condicion) throw new RuntimeException('FALLÓ: ' . $descripcion);
    $pruebas++;
    echo 'OK: ' . $descripcion . PHP_EOL;
}
function estadoVentas(): array {
    return [
        consulta('SELECT id, stock FROM productos ORDER BY id')->fetchAll(),
        (int)consulta('SELECT COUNT(*) FROM ventas')->fetchColumn(),
        (int)consulta('SELECT COUNT(*) FROM historial')->fetchColumn()
    ];
}
function rechazarVenta(string $descripcion, array $datos): void {
    $antes = estadoVentas();
    $_POST = $datos;
    $rechazado = false;
    try { guardar('registrarVenta'); }
    catch (RuntimeException $e) { $rechazado = true; }
    comprobarVenta($descripcion, $rechazado && estadoVentas() === $antes);
}

try {
    $servidor->exec('CREATE DATABASE `' . $basePrueba . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $creada = true;
    foreach (['base', 'productos', 'recetas', 'ventas', 'demo', 'demo_produccion'] as $archivo) {
        db()->exec(file_get_contents(__DIR__ . '/../database/' . $archivo . '.sql'));
    }
    $_SESSION['persona'] = 1;
    consulta('UPDATE productos SET stock=20 WHERE id=1');
    $valida = ['producto'=>'1', 'cantidad'=>'3'];
    $_POST = $valida;
    guardar('registrarVenta');
    comprobarVenta('Descuenta tres unidades del stock', (float)consulta('SELECT stock FROM productos WHERE id=1')->fetchColumn() === 17.0);
    $venta = consulta('SELECT * FROM ventas WHERE id=1')->fetch();
    comprobarVenta('Registra producto y cantidad', (int)$venta['producto_id'] === 1 && (float)$venta['cantidad'] === 3.0);
    comprobarVenta('Precio fijado en 25 pesos', (float)$venta['precio_unitario'] === 25.0);
    comprobarVenta('Total calculado 75 pesos', (float)$venta['total'] === 75.0);
    comprobarVenta('Guarda responsable y nombre histórico', (int)$venta['persona_id'] === 1 && $venta['persona_nombre'] === 'Ana · ejemplo');
    comprobarVenta('Registra una acción en historial', (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='ventas' AND persona_id=1")->fetchColumn() === 1);

    consulta('UPDATE productos SET stock=5.500 WHERE id=2');
    $_POST = ['producto'=>'2', 'cantidad'=>'0,750'];
    guardar('registrarVenta');
    comprobarVenta('Acepta venta decimal por kilogramo', (float)consulta('SELECT stock FROM productos WHERE id=2')->fetchColumn() === 4.75);
    comprobarVenta('Calcula 0,750 kg a 280 pesos', (float)consulta('SELECT total FROM ventas WHERE id=2')->fetchColumn() === 210.0);

    consulta('UPDATE productos SET precio=26 WHERE id=1');
    $_POST = ['producto'=>'1', 'cantidad'=>'2'];
    guardar('registrarVenta');
    comprobarVenta('La nueva venta usa el precio vigente', (float)consulta('SELECT total FROM ventas WHERE id=3')->fetchColumn() === 52.0);
    comprobarVenta('Las ventas anteriores conservan el precio', (float)consulta('SELECT precio_unitario FROM ventas WHERE id=1')->fetchColumn() === 25.0);
    comprobarVenta('La suma de ventas queda correcta', (float)consulta('SELECT SUM(total) FROM ventas')->fetchColumn() === 337.0);

    foreach ([
        ['producto'=>'0', 'cantidad'=>'1'],
        ['producto'=>'1abc', 'cantidad'=>'1'],
        ['producto'=>'99999', 'cantidad'=>'1'],
        ['producto'=>'1', 'cantidad'=>'0'],
        ['producto'=>'1', 'cantidad'=>'-1'],
        ['producto'=>'1', 'cantidad'=>'abc'],
        ['producto'=>'1', 'cantidad'=>'1.2345'],
        ['producto'=>'1', 'cantidad'=>'1.5'],
        ['producto'=>'1', 'cantidad'=>'999999'],
        ['producto'=>'2', 'cantidad'=>'0.0001']
    ] as $caso) {
        rechazarVenta('Rechaza venta inválida: ' . json_encode($caso), $caso);
    }

    consulta('UPDATE productos SET activo=0 WHERE id=1');
    rechazarVenta('Producto desactivado', $valida);
    consulta('UPDATE productos SET activo=1 WHERE id=1');
    consulta('UPDATE productos SET stock=0 WHERE id=1');
    rechazarVenta('Producto sin existencias', $valida);
    consulta('UPDATE productos SET stock=15 WHERE id=1');

    $_SESSION['persona'] = 999999;
    rechazarVenta('Responsable inexistente revierte la operación', $valida);
    $_SESSION['persona'] = 1;
    consulta('UPDATE personas SET activo=0 WHERE id=1');
    rechazarVenta('Responsable inactivo revierte la operación', $valida);
    consulta('UPDATE personas SET activo=1 WHERE id=1');

    $_SESSION['tokens']['unico-ventas'] = time();
    $_POST = ['token'=>'unico-ventas','responsable_id'=>1];
    verificar();
    $rechazado = false;
    try { verificar(); } catch (RuntimeException $e) { $rechazado = true; }
    comprobarVenta('No permite reenvío del formulario', $rechazado);

    echo $pruebas . ' pruebas correctas.' . PHP_EOL;
} catch (Throwable $e) {
    $fallo = true;
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
} finally {
    if ($creada) $servidor->exec('DROP DATABASE `' . $basePrueba . '`');
}
exit($fallo ? 1 : 0);
