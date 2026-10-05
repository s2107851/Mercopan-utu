CREATE TABLE IF NOT EXISTS recetas (
 producto_id INT PRIMARY KEY, rendimiento DECIMAL(12,3) NOT NULL,
 instrucciones VARCHAR(500) NOT NULL DEFAULT '', FOREIGN KEY(producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS receta_detalle (
 producto_id INT NOT NULL, ingrediente_id INT NOT NULL, cantidad DECIMAL(12,3) NOT NULL,
 PRIMARY KEY(producto_id,ingrediente_id), FOREIGN KEY(producto_id) REFERENCES recetas(producto_id),
 FOREIGN KEY(ingrediente_id) REFERENCES ingredientes(id)
) ENGINE=InnoDB;
