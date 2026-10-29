-- Actualización aditiva: conserva todas las compras y existencias previas.
CREATE TABLE IF NOT EXISTS facturas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 proveedor_id INT NOT NULL,
 proveedor_nombre VARCHAR(100) NOT NULL,
 numero VARCHAR(80) NOT NULL,
 numero_confirmado VARCHAR(80) NULL,
 emision DATE NOT NULL,
 estado ENUM('borrador','confirmada','cancelada') NOT NULL DEFAULT 'borrador',
 total DECIMAL(12,2) NOT NULL,
 version INT NOT NULL DEFAULT 1,
 persona_id INT NOT NULL,
 persona_nombre VARCHAR(100) NOT NULL,
 creada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 confirmada DATETIME NULL,
 confirmador_id INT NULL,
 confirmador_nombre VARCHAR(100) NULL,
 FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
 FOREIGN KEY (persona_id) REFERENCES personas(id),
 FOREIGN KEY (confirmador_id) REFERENCES personas(id),
 UNIQUE KEY factura_unica (proveedor_id,numero_confirmado),
 INDEX factura_fecha (emision,id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS factura_detalle (
 id INT AUTO_INCREMENT PRIMARY KEY,
 factura_id INT NOT NULL,
 ingrediente_id INT NOT NULL,
 ingrediente_nombre VARCHAR(100) NOT NULL,
 unidad VARCHAR(10) NOT NULL,
 cantidad DECIMAL(12,3) NOT NULL,
 costo_total DECIMAL(12,2) NOT NULL,
 compra_id INT NULL UNIQUE,
 FOREIGN KEY (factura_id) REFERENCES facturas(id),
 FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id),
 FOREIGN KEY (compra_id) REFERENCES compras(id),
 UNIQUE KEY factura_ingrediente (factura_id,ingrediente_id)
) ENGINE=InnoDB;
