-- Se puede ejecutar varias veces: no borra datos existentes.
-- Cada registro equivale al ingreso de un ingrediente desde un proveedor.
CREATE TABLE IF NOT EXISTS compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    ingrediente_id INT NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL,
    costo_total DECIMAL(12,2) NOT NULL,
    comprobante VARCHAR(80) NOT NULL DEFAULT '',
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    persona_id INT NOT NULL,
    CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    CONSTRAINT fk_compras_ingrediente FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id),
    CONSTRAINT fk_compras_persona FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_compras_fecha (fecha)
) ENGINE=InnoDB;
