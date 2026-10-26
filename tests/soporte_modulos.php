<?php
// Solo CLI. Nunca cambia la base de trabajo configurada por el grupo.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por terminal.'); }
require_once __DIR__ . '/../includes/funciones.php';
$pruebas = 0;
function comprobarModulo(string $nombre, bool $ok): void {
    global $pruebas;
    if (!$ok) throw new RuntimeException('FALLÓ: ' . $nombre);
    $pruebas++; echo 'OK: ' . $nombre . PHP_EOL;
}
function ejecutarPruebasModulo(array $tablas, callable $casos): void {
    global $config, $pruebas;
    $base = 'mercopan_utu_pruebas_' . bin2hex(random_bytes(6));
    $config['base'] = $base;
    $servidor = conectar(false); $creada = false; $fallo = false;
    try {
        $servidor->exec('CREATE DATABASE `' . $base . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $creada = true;
        foreach (array_merge(['base','productos','ventas'], $tablas) as $archivo) db()->exec(file_get_contents(__DIR__ . '/../database/' . $archivo . '.sql'));
        consulta("INSERT INTO personas(nombre) VALUES ('Persona de prueba')");
        consulta("INSERT INTO productos(nombre,unidad,precio,stock) VALUES ('Pan','unidad',25,20),('Galletas','kg',280,5.500)");
        $_SESSION['persona'] = 1;
        $casos();
        echo $pruebas . ' pruebas correctas.' . PHP_EOL;
    } catch (Throwable $ex) { $fallo = true; fwrite(STDERR, $ex->getMessage() . PHP_EOL); }
    finally { if ($creada) $servidor->exec('DROP DATABASE `' . $base . '`'); }
    exit($fallo ? 1 : 0);
}
function estadoModulo(string $tabla): array {
    return [consulta('SELECT * FROM ' . $tabla . ' ORDER BY id')->fetchAll(),
        consulta('SELECT id,stock FROM productos ORDER BY id')->fetchAll(),
        consulta('SELECT * FROM ventas ORDER BY id')->fetchAll(),
        consulta('SELECT * FROM historial ORDER BY id')->fetchAll()];
}
function rechazarModulo(string $tabla, string $nombre, callable $operacion): void {
    $antes = estadoModulo($tabla); $rechazada = false;
    try { guardar($operacion); } catch (RuntimeException $ex) { $rechazada = true; }
    comprobarModulo($nombre, $rechazada && estadoModulo($tabla) === $antes);
}
