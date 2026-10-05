<?php
// Copiar a config.local.php solamente los valores que cambien en su computadora.
$config = [
    'host' => '127.0.0.1', 'puerto' => '3306',
    'base' => 'mercopan_utu', 'usuario' => 'root', 'clave' => ''
];
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace($config, require __DIR__ . '/config.local.php');
}
date_default_timezone_set('America/Montevideo');
