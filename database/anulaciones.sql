-- HU16: migración aditiva y repetible; no borra ni modifica operaciones previas.
CREATE TABLE IF NOT EXISTS anulaciones (
 id INT AUTO_INCREMENT PRIMARY KEY,
 tipo ENUM('compra','produccion','factura') NOT NULL,
 registro_id INT NOT NULL,
 motivo VARCHAR(250) NOT NULL,
 persona_id INT NOT NULL,
 persona_nombre VARCHAR(100) NOT NULL,
 fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY anulacion_unica (tipo,registro_id),
 FOREIGN KEY (persona_id) REFERENCES personas(id)
) ENGINE=InnoDB;
