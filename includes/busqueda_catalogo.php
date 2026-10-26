<?php
// Tablas permitidas por el código; nunca se toma el nombre de tabla del navegador.
function filtroCatalogo(string $tabla, array $datos): array {
    if (!in_array($tabla, ['ingredientes','productos'], true)) throw new RuntimeException('Catálogo no válido.');
    $buscar = $datos['buscar'] ?? '';
    if (!is_string($buscar)) throw new RuntimeException('Ingresá un texto para buscar.');
    $buscar = trim($buscar);
    if (strlen($buscar) > 100) throw new RuntimeException('La búsqueda admite hasta 100 bytes de texto.');
    $sql = 'SELECT * FROM ' . $tabla;
    $params = [];
    if ($buscar !== '') {
        // ! escapa los comodines: %, _ y ! se buscan literalmente.
        $literal = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $buscar);
        $sql .= " WHERE nombre LIKE ? ESCAPE '!'";
        $params[] = '%' . $literal . '%';
    }
    $sql .= ' ORDER BY activo DESC,nombre';
    return compact('buscar','sql','params');
}
function buscarCatalogo(string $tabla, array $datos): array {
    $filtro = filtroCatalogo($tabla, $datos);
    return ['buscar'=>$filtro['buscar'], 'filas'=>consulta($filtro['sql'], $filtro['params'])->fetchAll()];
}
