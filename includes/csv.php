<?php
function celdaCsv($valor): string {
    $texto=(string)$valor;
    // Evita que Excel/Sheets interpreten texto del usuario como una fórmula.
    if (preg_match('/^[\x00-\x20]*[=+@-]/u',$texto)) $texto="'".$texto;
    return $texto;
}
function filaCsv($salida, array $fila): void {
    fputcsv($salida,array_map('celdaCsv',$fila),';','"','');
}
