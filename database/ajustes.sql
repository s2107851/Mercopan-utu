-- Repetible: crea la tabla solo si no existe y nunca elimina movimientos anteriores.
CREATE TABLE IF NOT EXISTS ajustes_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingrediente_id INT NOT NULL,
    stock_anterior DECIMAL(12,3) NOT NULL,
    stock_nuevo DECIMAL(12,3) NOT NULL,
    diferencia DECIMAL(13,3) NOT NULL,
    motivo VARCHAR(250) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    persona_id INT NOT NULL,
    CONSTRAINT fk_ajustes_ingrediente FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id),
    CONSTRAINT fk_ajustes_persona FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_ajustes_fecha (fecha)
) ENGINE=InnoDB;
