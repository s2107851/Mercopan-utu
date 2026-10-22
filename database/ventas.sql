-- Registro de ventas de productos elaborados. Repetible y no destructivo.
-- El importe y el precio unitario quedan fijados al momento de vender.
CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL,
    precio_unitario DECIMAL(12,2) NOT NULL,
    total DECIMAL(18,2) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    persona_id INT NOT NULL,
    persona_nombre VARCHAR(100) NOT NULL,
    CONSTRAINT fk_ventas_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    CONSTRAINT fk_ventas_persona FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_ventas_fecha (fecha),
    INDEX idx_ventas_producto (producto_id)
) ENGINE=InnoDB;
