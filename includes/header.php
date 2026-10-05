<?php $persona = responsable(); ?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Inicio') ?> · Mercopan</title><link rel="stylesheet" href="assets/estilos.css"></head>
<body>
<header class="barra"><a class="marca" href="index.php"><span class="isotipo">M</span><span>MERCOPAN<small>Gestión de panadería · UTU</small></span></a>
<a class="persona" href="responsable.php"><?= $persona ? e($persona['nombre']) . ' · Cambiar' : 'Elegir responsable' ?></a></header>
<nav aria-label="Navegación principal">
<?php $enlaces = ['index.php'=>'Inicio','ingredientes.php'=>'Ingredientes','compras.php'=>'Compras','productos.php'=>'Productos','recetas.php'=>'Recetas','produccion.php'=>'Producción','ventas.php'=>'Ventas','pedidos.php'=>'Pedidos','salidas.php'=>'Pérdidas / donaciones','reportes.php'=>'Resumen','historial.php'=>'Historial','personas.php'=>'Personal','proveedores.php'=>'Proveedores'];
foreach ($enlaces as $archivo=>$nombre): if (!is_file(dirname(__DIR__) . '/' . $archivo)) continue; ?>
<a <?= basename($_SERVER['SCRIPT_NAME']) === $archivo ? 'aria-current="page"' : '' ?> href="<?= e($archivo) ?>"><?= e($nombre) ?></a>
<?php endforeach; ?></nav>
<main>
<?php if (!empty($_SESSION['mensaje'])): ?><p class="aviso exito" role="status"><?= e($_SESSION['mensaje']) ?></p><?php unset($_SESSION['mensaje']); endif; ?>
<?php if ($error): ?><p class="aviso error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if (!$persona): ?><p class="aviso">Antes de guardar cambios, <a href="responsable.php">elegí quién está trabajando</a>.</p><?php endif; ?>
<div class="titulo"><div><p class="ceja">MERCOPAN / <?= e($titulo ?? 'Inicio') ?></p><h1><?= e($titulo ?? 'Inicio') ?></h1></div><span class="etiqueta">Proyecto UTU</span></div>
