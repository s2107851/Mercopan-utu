<?php
require_once __DIR__ . '/reportes.php';
function filtrosHistorial(array $datos): array {
    [$desde,$hasta,$fin] = periodoReporte($datos);
    $persona = filter_var($datos['persona'] ?? '0', FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
    if ($persona === false) throw new RuntimeException('Responsable no válido.');
    $modulo = trim((string)($datos['modulo'] ?? ''));
    if (strlen($modulo)>40) throw new RuntimeException('Módulo no válido.');
    $buscar = trim((string)($datos['buscar'] ?? ''));
    if (strlen($buscar)>100) throw new RuntimeException('La búsqueda admite hasta 100 bytes.');
    $where = 'fecha>=? AND fecha<?'; $params = [$desde,$fin];
    if ($persona) { $where .= ' AND persona_id=?'; $params[]=$persona; }
    if ($modulo !== '') { $where .= ' AND modulo=?'; $params[]=$modulo; }
    // LOCATE busca texto literal, sin interpretar % o _ como comodines.
    if ($buscar !== '') { $where .= ' AND LOCATE(?,detalle)>0'; $params[]=$buscar; }
    return compact('desde','hasta','persona','modulo','buscar','where','params');
}
