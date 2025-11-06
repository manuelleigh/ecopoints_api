USE ecopoints_db;

-- =======================================================
-- Tabla de Usuarios
-- =======================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    puntos INT DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =======================================================
-- Códigos QR generados
-- =======================================================
CREATE TABLE codigos_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) UNIQUE NOT NULL,
    estado ENUM('PENDIENTE', 'CANJEADO', 'EXPIRADO') DEFAULT 'PENDIENTE',
    botellas_recicladas INT DEFAULT 0,
    valor_puntos INT DEFAULT 5,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dispositivo_id VARCHAR(50)
);

-- =======================================================
-- 3. Transacciones de puntos por escaneo de QR
-- =======================================================
CREATE TABLE transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo_qr_id INT NOT NULL,
    puntos INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (codigo_qr_id) REFERENCES codigos_qr(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- =======================================================
-- 4. Empresas asociadas
-- =======================================================
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    logo_url VARCHAR(255),
    web_url VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE
);

-- =======================================================
-- 5. Convenios
-- =======================================================
CREATE TABLE convenios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    puntos_requeridos INT NOT NULL,
    tipo_entrega ENUM('CODIGO', 'URL') DEFAULT 'CODIGO',
    base_url VARCHAR(255) NULL,
    stock INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- =======================================================
-- 6. Canjes realizados por usuarios
-- =======================================================
CREATE TABLE canjes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    convenio_id INT NOT NULL,
    puntos_usados INT NOT NULL,
    codigo_entrega VARCHAR(255) NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (convenio_id) REFERENCES convenios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);
