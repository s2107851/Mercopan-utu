<?php
require_once __DIR__ . '/includes/funciones.php';
// El instalador se ejecuta desde la misma PC donde está XAMPP.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) { http_response_code(403); exit('Abrí el instalador desde la PC del servidor.'); }
$error = ''; $exito = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar(false);
        if (!preg_match('/^mercopan_utu[a-zA-Z0-9_]*$/', $config['base'])) throw new RuntimeException('Usá una base separada cuyo nombre empiece por mercopan_utu.');
        $pdo = conectar(false);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['base'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $config['base'] . '`');
        // Los archivos SQL no contienen datos aportados por el visitante.
        $archivos = ['base','compras','productos','recetas','produccion','ventas','pedidos','salidas'];
        foreach ($archivos as $archivo) {
            $ruta = __DIR__ . '/database/' . $archivo . '.sql';
            if (is_file($ruta)) $pdo->exec(file_get_contents($ruta));
        }
        if ((int)$pdo->query('SELECT COUNT(*) FROM personas')->fetchColumn() === 0) {
            $pdo->beginTransaction();
            if (isset($_POST['demo'])) {
                $pdo->exec(file_get_contents(__DIR__ . '/database/demo.sql'));
                if (is_file(__DIR__ . '/database/demo_completo.sql')) $pdo->exec(file_get_contents(__DIR__ . '/database/demo_completo.sql'));
            } else $pdo->exec("INSERT INTO personas(nombre) VALUES ('Responsable inicial')");
            $pdo->commit();
        }
        $exito = 'Base preparada. Los datos anteriores se conservaron.';
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $error = 'No se pudo preparar la base. Verificá que MySQL esté iniciado, PDO MySQL esté habilitado y los datos de includes/config.php sean correctos. ';
        if (!$e instanceof PDOException) $error .= $e->getMessage();
        error_log($e->getMessage());
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Preparar Mercopan UTU</title><link rel="stylesheet" href="assets/estilos.css"></head><body><main style="max-width:700px">
<p class="ceja">MERCOPAN / PRIMER INICIO</p><h1>Preparar el proyecto</h1><section class="panel">
<p>Iniciá Apache y MySQL en XAMPP. Este instalador crea las tablas de <strong><?= e($config['base']) ?></strong>.</p>
<p>No borra tablas ni vacía registros. Podés ejecutarlo al pasar de la base a la versión completa.</p>
<?php if ($error): ?><p class="aviso error"><?= e($error) ?></p><?php endif; ?>
<?php if ($exito): ?><p class="aviso exito"><?= e($exito) ?></p><a class="boton" href="index.php">Abrir Mercopan</a><?php else: ?>
<form method="post"><?php formulario(); ?><label><input type="checkbox" name="demo" checked> Incluir ejemplos ficticios (solo en una base nueva)</label><button type="submit">Preparar base de datos</button></form>
<?php endif; ?></section><p class="ayuda">Requiere PHP 8 o superior y MySQL/MariaDB. Leé README.md si necesitás cambiar el puerto o la contraseña.</p></main></body></html>
