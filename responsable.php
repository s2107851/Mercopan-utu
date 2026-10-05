<?php
require 'includes/inicio.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificar(false);
        $p = consulta('SELECT id FROM personas WHERE id=? AND activo=1', [(int)($_POST['persona'] ?? 0)])->fetch();
        if (!$p) throw new RuntimeException('Seleccioná una persona activa.');
        session_regenerate_id(true); $_SESSION['persona'] = $p['id'];
        ir('index.php', 'Responsable seleccionado.');
    } catch (Throwable $e) { $error = mensajeError($e); }
}
$titulo='¿Quién está trabajando?'; require 'includes/header.php';
?>
<p class="descripcion">Elegí tu nombre para registrar quién realiza cada acción. No se solicita contraseña.</p>
<div class="grid">
<?php foreach (consulta('SELECT * FROM personas WHERE activo=1 ORDER BY nombre') as $p): ?>
<form class="panel" method="post"><?php formulario(); ?><input type="hidden" name="persona" value="<?= e($p['id']) ?>"><button type="submit"><?= e($p['nombre']) ?></button></form>
<?php endforeach; ?></div>
<p class="ayuda">Esta selección registra el nombre declarado; no verifica la identidad de la persona.</p>
<?php require 'includes/footer.php'; ?>
