<?php
function elaborar(): void {
 $id=(int)($_POST['producto'] ?? 0);$lotes=numero($_POST['lotes'] ?? '',1,1000,0);
 // Se bloquea primero el producto; editar su receta usa el mismo bloqueo.
 $p=consulta('SELECT * FROM productos WHERE id=? AND activo=1 FOR UPDATE',[$id])->fetch();
 if(!$p) throw new RuntimeException('Elegí un producto activo.');
 $receta=consulta('SELECT * FROM recetas WHERE producto_id=?',[$id])->fetch();
 $detalles=consulta('SELECT * FROM receta_detalle WHERE producto_id=? ORDER BY ingrediente_id',[$id])->fetchAll();
 if(!$receta || !$detalles) throw new RuntimeException('Primero guardá una receta para este producto.');
 $obtenido=round((float)$receta['rendimiento']*$lotes,3);
 if((float)$p['stock']+$obtenido>999999999.999) throw new RuntimeException('La producción supera el stock máximo.');
 consulta('INSERT INTO producciones(producto_id,lotes,cantidad,persona_id) VALUES (?,?,?,?)',[$id,$lotes,$obtenido,$_SESSION['persona']]);
 $produccion=db()->lastInsertId();
 foreach($detalles as $d){
  $i=consulta('SELECT * FROM ingredientes WHERE id=? FOR UPDATE',[$d['ingrediente_id']])->fetch();
  $necesario=round((float)$d['cantidad']*$lotes,3);
  if(!$i || !$i['activo'] || (float)$i['stock']+0.0000001<$necesario) throw new RuntimeException('No alcanza o está inactivo: ' . ($i['nombre'] ?? 'ingrediente inexistente') . '. Se necesitan ' . n($necesario) . ' ' . ($i['unidad'] ?? '') . '. No se guardó ningún consumo.');
  consulta('UPDATE ingredientes SET stock=stock-? WHERE id=?',[$necesario,$i['id']]);
  consulta('INSERT INTO consumos VALUES (?,?,?)',[$produccion,$i['id'],$necesario]);
 }
 consulta('UPDATE productos SET stock=stock+? WHERE id=?',[$obtenido,$id]);
 registrar('produccion','Elaboración #' . $produccion . ': ' . n($obtenido) . ' ' . $p['unidad'] . ' de ' . $p['nombre']);
}
