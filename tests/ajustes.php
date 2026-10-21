<?php
// Ejecutar por terminal: php tests/ajustes.php. Nunca usar desde el navegador.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por terminal.'); }
require __DIR__ . '/../includes/funciones.php';
require __DIR__ . '/../includes/ajustes.php';

$basePrueba = 'mercopan_utu_pruebas_' . bin2hex(random_bytes(6));
$config['base'] = $basePrueba;
$servidor = conectar(false);
$creada = false;
$pruebas = 0;
$fallo = false;

function comprobarAjuste(string $descripcion, bool $condicion): void {
    global $pruebas;
    if (!$condicion) throw new RuntimeException('FALLÓ: ' . $descripcion);
    $pruebas++;
    echo 'OK: ' . $descripcion . PHP_EOL;
}
function estadoAjustes(): array {
    return [
        consulta('SELECT id,stock FROM ingredientes ORDER BY id')->fetchAll(),
        (int)consulta('SELECT COUNT(*) FROM ajustes_inventario')->fetchColumn(),
        (int)consulta('SELECT COUNT(*) FROM historial')->fetchColumn(),
    ];
}
function rechazarAjuste(string $descripcion, array $datos): void {
    $antes = estadoAjustes();
    $_POST = $datos;
    $rechazado = false;
    try { guardar('registrarAjusteInventario'); }
    catch (RuntimeException $e) { $rechazado = true; }
    comprobarAjuste($descripcion, $rechazado && estadoAjustes() === $antes);
}

try {
    $servidor->exec('CREATE DATABASE `' . $basePrueba . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $creada = true;
    foreach (['base','ajustes','demo'] as $archivo) {
        db()->exec(file_get_contents(__DIR__ . '/../database/' . $archivo . '.sql'));
    }
    $_SESSION['persona'] = 1;
    $datos = ['ingrediente'=>'1', 'stock_real'=>'12,250', 'motivo'=>'Conteo semanal'];
    $_POST = $datos;
    guardar('registrarAjusteInventario');
    comprobarAjuste('El conteo reemplaza la cantidad anterior', (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn() === 12.25);
    $ajuste = consulta('SELECT * FROM ajustes_inventario WHERE id=1')->fetch();
    comprobarAjuste('Guarda valor anterior 100', (float)$ajuste['stock_anterior'] === 100.0);
    comprobarAjuste('Guarda conteo 12,250', (float)$ajuste['stock_nuevo'] === 12.25);
    comprobarAjuste('Calcula diferencia -87,750', (float)$ajuste['diferencia'] === -87.75);
    comprobarAjuste('Conserva motivo y responsable', $ajuste['motivo'] === 'Conteo semanal' && (int)$ajuste['persona_id'] === 1);
    comprobarAjuste('Registra la acción en historial', (int)consulta("SELECT COUNT(*) FROM historial WHERE modulo='ajustes' AND persona_id=1")->fetchColumn() === 1);

    rechazarAjuste('No permite ajustar al mismo valor', array_replace($datos, ['stock_real'=>'12,250']));
    rechazarAjuste('No permite stock negativo', array_replace($datos, ['stock_real'=>'-1']));
    rechazarAjuste('No permite más de tres decimales', array_replace($datos, ['stock_real'=>'2.1234']));
    rechazarAjuste('No permite stock no numérico', array_replace($datos, ['stock_real'=>'abc']));
    rechazarAjuste('No permite stock fuera de rango', array_replace($datos, ['stock_real'=>'1000000000']));
    rechazarAjuste('No permite ID de ingrediente inexistente', array_replace($datos, ['ingrediente'=>'999']));
    rechazarAjuste('No permite ID mezclado con texto', array_replace($datos, ['ingrediente'=>'1abc']));
    rechazarAjuste('Exige motivo', array_replace($datos, ['motivo'=>' ']));
    rechazarAjuste('Limita motivo a 250 caracteres', array_replace($datos, ['motivo'=>str_repeat('X',251)]));
    rechazarAjuste('No permite fracciones de huevos', array_replace($datos, ['ingrediente'=>'6','stock_real'=>'3.5']));

    // Permitir un ajuste a cero es importante en inventarios reales.
    $_POST = array_replace($datos, ['stock_real'=>'0','motivo'=>'No queda harina']);
    guardar('registrarAjusteInventario');
    comprobarAjuste('Permite un conteo igual a cero', (float)consulta('SELECT stock FROM ingredientes WHERE id=1')->fetchColumn() === 0.0);
    $_POST = array_replace($datos, ['ingrediente'=>'6','stock_real'=>'35','motivo'=>'Conteo de huevos']);
    guardar('registrarAjusteInventario');
    comprobarAjuste('Acepta cantidades enteras en unidades', (float)consulta('SELECT stock FROM ingredientes WHERE id=6')->fetchColumn() === 35.0);

    consulta('UPDATE ingredientes SET activo=0 WHERE id=1');
    rechazarAjuste('Ingrediente inactivo: no registra nada', $datos);
    consulta('UPDATE ingredientes SET activo=1 WHERE id=1');
    $_SESSION['persona'] = 999999;
    rechazarAjuste('Responsable inexistente: revierte todo', $datos);
    $_SESSION['persona'] = 1;

    $_SESSION['tokens']['prueba-ajustes'] = time();
    $_POST = ['token'=>'prueba-ajustes','responsable_id'=>1];
    verificar();
    $rechazo = false;
    try { verificar(); } catch (RuntimeException $e) { $rechazo = true; }
    comprobarAjuste('Evita reenviar el mismo formulario', $rechazo);

    echo $pruebas . ' pruebas correctas.' . PHP_EOL;
} catch (Throwable $e) {
    $fallo = true;
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
} finally {
    if ($creada) $servidor->exec('DROP DATABASE `' . $basePrueba . '`');
}
exit($fallo ? 1 : 0);
