-- Solo se ejecuta junto a demo.sql en la primera instalación, con base vacía.
INSERT INTO productos(nombre,unidad,precio) VALUES ('Pan flauta','unidad',25),('Bizcochos','kg',280),('Medialunas','unidad',35);
INSERT INTO recetas(producto_id,rendimiento,instrucciones)
 SELECT id,20,'Receta ficticia para demostrar el control de stock. No es una fórmula de cocina.' FROM productos WHERE nombre='Pan flauta';
INSERT INTO receta_detalle(producto_id,ingrediente_id,cantidad)
 SELECT p.id,i.id,CASE i.nombre WHEN 'Harina' THEN 5 WHEN 'Levadura' THEN 0.12 ELSE 0.08 END
 FROM productos p CROSS JOIN ingredientes i WHERE p.nombre='Pan flauta' AND i.nombre IN ('Harina','Levadura','Sal');
