<?php
require_once __DIR__ . '/config.php';
// Cada carpeta usa su propia sesión cuando se comparan ambas versiones.
session_name('mercopan_utu_' . substr(hash('sha256', dirname(__DIR__)), 0, 12));
session_start();
// PDO separa los valores del SQL. Nunca concatenamos datos del formulario al SQL.
function conectar(bool $usarBase = true): PDO {
    global $config;
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['puerto'] . ';charset=utf8mb4';
    if ($usarBase) $dsn .= ';dbname=' . $config['base'];
    $pdo = new PDO($dsn, $config['usuario'], $config['clave'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
    return $pdo;
}
function db(): PDO {
    static $pdo;
    if (!$pdo) $pdo = conectar();
    return $pdo;
}
function consulta(string $sql, array $datos = []): PDOStatement {
    $q = db()->prepare($sql); $q->execute($datos); return $q;
}
function e($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
function n($valor, int $decimales = 3): string {
    return number_format((float)$valor, $decimales, ',', '.');
}
function valor(string $campo, $defecto = ''): string { return e($_POST[$campo] ?? $defecto); }
function texto(string $campo, int $max = 100, bool $obligatorio = true): string {
    $v = trim((string)($_POST[$campo] ?? ''));
    if (($obligatorio && $v === '') || strlen($v) > $max) {
        throw new RuntimeException('Revisá el campo ' . str_replace('_', ' ', $campo) . '.');
    }
    return $v;
}
function numero($v, float $min = 0, float $max = 999999, int $dec = 3): float {
    $v = str_replace(',', '.', trim((string)$v));
    if (!is_numeric($v) || !is_finite((float)$v) || (float)$v < $min || (float)$v > $max) {
        throw new RuntimeException('Ingresá una cantidad válida entre ' . $min . ' y ' . $max . '.');
    }
    if (abs((float)$v - round((float)$v, $dec)) > 0.0000001) {
        throw new RuntimeException('Se admiten hasta ' . $dec . ' decimales.');
    }
    return round((float)$v, $dec);
}
function fecha(string $v): string {
    $f = DateTime::createFromFormat('!Y-m-d', $v);
    if (!$f || $f->format('Y-m-d') !== $v) throw new RuntimeException('La fecha no es válida.');
    return $v;
}
function responsable(): ?array {
    if (empty($_SESSION['persona'])) return null;
    $p = consulta('SELECT * FROM personas WHERE id=? AND activo=1', [$_SESSION['persona']])->fetch();
    return $p ?: null;
}
function formulario(): void {
    // Un token por formulario permite varias pestañas y evita repetir el mismo envío.
    if (count($_SESSION['tokens'] ?? []) >= 500) array_shift($_SESSION['tokens']);
    $token = bin2hex(random_bytes(24));
    $_SESSION['tokens'][$token] = time();
    echo '<input type="hidden" name="token" value="' . $token . '">';
    echo '<input type="hidden" name="responsable_id" value="' . e($_SESSION['persona'] ?? 0) . '">';
}
function verificar(bool $exigirPersona = true): void {
    $token = (string)($_POST['token'] ?? '');
    if (!isset($_SESSION['tokens'][$token])) throw new RuntimeException('Este formulario ya se envió o venció. Volvé a abrir la pantalla.');
    unset($_SESSION['tokens'][$token]);
    if ($exigirPersona && (!responsable() || (int)($_POST['responsable_id'] ?? 0) !== (int)$_SESSION['persona'])) {
        throw new RuntimeException('Elegí un responsable y volvé a abrir el formulario.');
    }
}
function registrar(string $modulo, string $detalle): void {
    $p = responsable();
    if (!$p) throw new RuntimeException('Falta seleccionar responsable.');
    consulta('INSERT INTO historial (persona_id,persona_nombre,modulo,detalle) VALUES (?,?,?,?)',
        [$p['id'], $p['nombre'], $modulo, $detalle]);
}
function guardar(callable $operacion): void {
    // O se guardan TODOS los cambios y su historial, o se deshacen TODOS.
    db()->beginTransaction();
    try { $operacion(); db()->commit(); }
    catch (Throwable $error) { if (db()->inTransaction()) db()->rollBack(); throw $error; }
}
function ir(string $pagina, string $mensaje = ''): void {
    $_SESSION['mensaje'] = $mensaje; header('Location: ' . $pagina); exit;
}
function mensajeError(Throwable $error): string {
    if ($error instanceof PDOException) {
        error_log($error->getMessage());
        return $error->getCode() === '23000'
            ? 'Ya existe ese registro, o está relacionado con otro. Revisá los datos.'
            : 'No se pudo guardar. Revisá la conexión y la instalación de la base.';
    }
    return $error->getMessage();
}
function opciones(array $filas, $seleccion = 0, string $etiqueta = 'nombre'): void {
    foreach ($filas as $fila) echo '<option value="' . e($fila['id']) . '"' . ((string)$seleccion === (string)$fila['id'] ? ' selected' : '') . '>' . e($fila[$etiqueta]) . '</option>';
}
