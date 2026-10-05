<?php
require_once __DIR__ . '/funciones.php';
try {
    consulta('SELECT id FROM personas LIMIT 1');
} catch (Throwable $error) {
    http_response_code(503);
    exit('<!doctype html><html lang="es"><meta charset="utf-8"><title>Preparar Mercopan</title><body><h1>Falta preparar la base de datos</h1><p>Iniciá MySQL en XAMPP y revisá includes/config.php.</p><p><a href="instalar.php">Abrir el instalador</a></p></body></html>');
}
$error = '';
