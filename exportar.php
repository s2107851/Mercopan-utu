<?php
require 'includes/inicio.php';
require_once 'includes/reportes.php';
require_once 'includes/filtros_historial.php';
require_once 'includes/csv.php';
try {
    $tipo=(string)($_GET['tipo'] ?? '');
    [$desde,$hasta,$fin]=periodoReporte($_GET);
    $params=[$desde,$fin];
    switch($tipo) {
        case 'ventas':
            $cabecera=['ID','Fecha','Producto','Unidad','Cantidad','Precio UYU','Importe UYU','Responsable'];
            $sql='SELECT v.id,v.fecha,p.nombre,p.unidad,v.cantidad,v.precio_unitario,v.total,v.persona_nombre FROM ventas v JOIN productos p ON p.id=v.producto_id WHERE v.fecha>=? AND v.fecha<? ORDER BY v.fecha,v.id';
            break;
        case 'compras':
            $cabecera=['ID','Fecha','Proveedor','Ingrediente','Unidad','Cantidad','Costo UYU','Comprobante','Responsable actual','Estado','Fecha anulación','Motivo','Anuló'];
            $sql='SELECT c.id,c.fecha,p.nombre,i.nombre,i.unidad,c.cantidad,c.costo_total,c.comprobante,r.nombre,CASE WHEN a.id IS NULL THEN \'vigente\' ELSE \'anulada\' END,a.fecha,a.motivo,a.persona_nombre FROM compras c JOIN proveedores p ON p.id=c.proveedor_id JOIN ingredientes i ON i.id=c.ingrediente_id JOIN personas r ON r.id=c.persona_id LEFT JOIN anulaciones a ON a.tipo=\'compra\' AND a.registro_id=c.id WHERE c.fecha>=? AND c.fecha<? ORDER BY c.fecha,c.id';
            break;
        case 'historial':
            $f=filtrosHistorial($_GET); $params=$f['params'];
            $cabecera=['ID','Fecha','Responsable histórico','Módulo','Acción'];
            $sql='SELECT id,fecha,persona_nombre,modulo,detalle FROM historial WHERE '.$f['where'].' ORDER BY id DESC';
            break;
        default: throw new RuntimeException('Elegí ventas, compras o historial.');
    }
    // Validar y ejecutar antes de enviar cabeceras: un error no descarga un CSV falso.
    $q=consulta($sql,$params);
} catch(Throwable $ex) {
    http_response_code(400);
    $error=mensajeError($ex); $titulo='Exportación no disponible';
    require 'includes/header.php'; require 'includes/footer.php'; exit;
}
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="mercopan-'.$tipo.'-'.$desde.'-'.$hasta.'.csv"');
header('Cache-Control: no-store');
$salida=fopen('php://output','w');
fwrite($salida,"\xEF\xBB\xBF");
filaCsv($salida,$cabecera);
while($fila=$q->fetch(PDO::FETCH_NUM)) filaCsv($salida,$fila);
fclose($salida);
exit;
