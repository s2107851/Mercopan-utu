CREATE TABLE IF NOT EXISTS producciones (
 id INT AUTO_INCREMENT PRIMARY KEY, producto_id INT NOT NULL, lotes INT NOT NULL,
 cantidad DECIMAL(12,3) NOT NULL, fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 persona_id INT NOT NULL, FOREIGN KEY(producto_id) REFERENCES productos(id),
 FOREIGN KEY(persona_id) REFERENCES personas(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS consumos (
 produccion_id INT NOT NULL, ingrediente_id INT NOT NULL, cantidad DECIMAL(12,3) NOT NULL,
 PRIMARY KEY(produccion_id,ingrediente_id), FOREIGN KEY(produccion_id) REFERENCES producciones(id),
 FOREIGN KEY(ingrediente_id) REFERENCES ingredientes(id)
) ENGINE=InnoDB;
