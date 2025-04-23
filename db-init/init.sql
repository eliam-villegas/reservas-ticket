-- Crear base de datos
CREATE DATABASE IF NOT EXISTS tickets;
USE tickets;

-- Tabla: usuarios
CREATE TABLE usuarios (
  usuario_cod VARCHAR(20) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (usuario_cod)
);

-- Tabla: sectores
CREATE TABLE sectores (
  sector_cod INT(11) NOT NULL AUTO_INCREMENT,
  sector VARCHAR(50) NOT NULL,
  PRIMARY KEY (sector_cod)
);

-- Insertar sectores de ejemplo
INSERT INTO sectores (sector) VALUES
('Platea'),
('Galería'),
('Cancha'),
('VIP');

-- Tabla: asientos
CREATE TABLE asientos (
  asiento_cod INT(11) NOT NULL AUTO_INCREMENT,
  asiento VARCHAR(50) NOT NULL,
  ocupado TINYINT(1) NOT NULL DEFAULT 0,
  sector_cod INT(11) NOT NULL,
  PRIMARY KEY (asiento_cod),
  FOREIGN KEY (sector_cod) REFERENCES sectores(sector_cod) ON UPDATE CASCADE
);

-- Tabla: reservas
CREATE TABLE reservas (
  reserva_id INT(11) NOT NULL AUTO_INCREMENT,
  fecha DATE NOT NULL,
  cliente VARCHAR(100) NOT NULL,
  asiento_cod INT(11) NOT NULL,
  usuario_cod VARCHAR(20) NOT NULL,
  PRIMARY KEY (reserva_id),
  FOREIGN KEY (asiento_cod) REFERENCES asientos(asiento_cod) ON UPDATE CASCADE,
  FOREIGN KEY (usuario_cod) REFERENCES usuarios(usuario_cod) ON UPDATE CASCADE
);

-- Ejemplo de inserción de algunos asientos
INSERT INTO asientos (asiento, ocupado, sector_cod) VALUES
('U7', 0, 3),
('M22', 1, 2),
('K5', 0, 1),
('U45', 0, 4),
('L4', 1, 3),
('N32', 1, 3),
('W26', 1, 1),
('T49', 1, 4),
('O31', 0, 2),
('S29', 1, 2);
