-- Un pedido contiene un producto. Crear tablas no modifica datos existentes.
CREATE TABLE IF NOT EXISTS pedidos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 cliente VARCHAR(100) NOT NULL,
 telefono VARCHAR(40) NOT NULL DEFAULT '',
 producto_id INT NOT NULL,
 producto_nombre VARCHAR(100) NOT NULL,
 unidad VARCHAR(10) NOT NULL,
 cantidad DECIMAL(12,3) NOT NULL,
 fecha_entrega DATE NOT NULL,
 notas VARCHAR(300) NOT NULL DEFAULT '',
 estado ENUM('pendiente','listo','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 persona_id INT NOT NULL,
 persona_nombre VARCHAR(100) NOT NULL,
 venta_id INT NULL UNIQUE,
 FOREIGN KEY (producto_id) REFERENCES productos(id),
 FOREIGN KEY (persona_id) REFERENCES personas(id),
 FOREIGN KEY (venta_id) REFERENCES ventas(id),
 INDEX idx_pedidos_entrega (estado, fecha_entrega)
) ENGINE=InnoDB;
