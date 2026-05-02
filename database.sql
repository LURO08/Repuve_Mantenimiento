-- NUEVO ESQUEMA (create / migrate)
CREATE DATABASE IF NOT EXISTS repuve_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE repuve_db;

-- materiales (ahora con foto opcional por material global)
CREATE TABLE IF NOT EXISTS materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL
);

-- ubicaciones
CREATE TABLE IF NOT EXISTS ubicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- arcos (sin material directo)
CREATE TABLE IF NOT EXISTS arcos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion_id INT NOT NULL,
    fecha_instalacion DATE,
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE CASCADE
);

-- relación arco <-> material (muchos a muchos), con foto específica para ese material en ese arco
CREATE TABLE IF NOT EXISTS arco_material (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arco_id INT NOT NULL,
    material_id INT NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    UNIQUE(arco_id, material_id),
    FOREIGN KEY (arco_id) REFERENCES arcos(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materiales(id) ON DELETE CASCADE
);

-- revisiones
CREATE TABLE IF NOT EXISTS revisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    arco_id INT NOT NULL,
    fecha_mantenimiento DATE NOT NULL,
    observaciones TEXT DEFAULT NULL,
    FOREIGN KEY (arco_id) REFERENCES arcos(id) ON DELETE CASCADE
);

-- tabla para evidencias (varias por revisión)
CREATE TABLE IF NOT EXISTS revision_evidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    revision_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    mimetype VARCHAR(100) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revision_id) REFERENCES revisiones(id) ON DELETE CASCADE
);

-- relación revision <-> material (varios materiales cambiados por revisión), con foto por material-cambio
CREATE TABLE IF NOT EXISTS revision_material (
    id INT AUTO_INCREMENT PRIMARY KEY,
    revision_id INT NOT NULL,
    material_id INT NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (revision_id) REFERENCES revisiones(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materiales(id) ON DELETE CASCADE
);
