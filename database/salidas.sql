-- Salidas de productos elaborados por pérdida o donación.
CREATE TABLE IF NOT EXISTS salidas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 producto_id INT NOT NULL,
 producto_nombre VARCHAR(100) NOT NULL,
 unidad VARCHAR(10) NOT NULL,
 tipo ENUM('perdida','donacion') NOT NULL,
 cantidad DECIMAL(12,3) NOT NULL,
 motivo VARCHAR(300) NOT NULL,
 stock_anterior DECIMAL(12,3) NOT NULL,
 stock_nuevo DECIMAL(12,3) NOT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 persona_id INT NOT NULL,
 persona_nombre VARCHAR(100) NOT NULL,
 FOREIGN KEY (producto_id) REFERENCES productos(id),
 FOREIGN KEY (persona_id) REFERENCES personas(id),
 INDEX idx_salidas_fecha (fecha)
) ENGINE=InnoDB;
