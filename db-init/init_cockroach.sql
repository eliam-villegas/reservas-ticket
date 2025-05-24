-- Re‑created schema following the new integer PK model
-- Database
CREATE DATABASE IF NOT EXISTS tickets;
USE tickets;

------------------------------------------------------------
-- Table: usuarios  (id SERIAL PK instead of usuario_cod)
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
                                        id          SERIAL PRIMARY KEY,
                                        usuario     STRING UNIQUE NOT NULL,
                                        contrasena  STRING NOT NULL,
                                        estado      BOOL   NOT NULL DEFAULT true
);

------------------------------------------------------------
-- Table: sectores (kept identical)
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sectores (
                                        sector_cod INT PRIMARY KEY,
                                        sector     STRING NOT NULL
);

------------------------------------------------------------
-- Table: asientos (kept identical)
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asientos (
                                        asiento_cod SERIAL PRIMARY KEY,
                                        asiento     STRING NOT NULL,
                                        ocupado     BOOL   NOT NULL DEFAULT false,
                                        sector_cod  INT    NOT NULL REFERENCES sectores(sector_cod)
    );

------------------------------------------------------------
-- Table: reservas (FK now points to usuarios.id)
------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservas (
                                        reserva_id  SERIAL PRIMARY KEY,
                                        fecha       DATE   NOT NULL,
                                        cliente     STRING NOT NULL,
                                        asiento_cod INT    NOT NULL REFERENCES asientos(asiento_cod),
    usuario_id  INT    NOT NULL REFERENCES usuarios(id)
    );

------------------------------------------------------------
-- Seed data: sectores & asientos
------------------------------------------------------------
INSERT INTO sectores (sector_cod, sector) VALUES
                                              (1, 'Platea'),
                                              (2, 'Galería'),
                                              (3, 'Cancha'),
                                              (4, 'VIP');

INSERT INTO asientos (asiento, ocupado, sector_cod) VALUES
                                                        ('U7',  false, 3),
                                                        ('M22', false, 2),
                                                        ('K5',  false, 1),
                                                        ('U45', false, 4),
                                                        ('L4',  false, 3),
                                                        ('N32', false, 3),
                                                        ('W26', false, 1),
                                                        ('T49', false, 4),
                                                        ('O31', false, 2),
                                                        ('S29', false, 2);
