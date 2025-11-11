USE ecopoints_db;

-- Recomendación: asegurar engine y charset
SET default_storage_engine = INNODB;
SET NAMES utf8mb4;

-- 1. Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    puntos INT DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 2. Códigos QR
CREATE TABLE IF NOT EXISTS codigos_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) UNIQUE NOT NULL,
    estado ENUM('PENDIENTE', 'CANJEADO', 'EXPIRADO') DEFAULT 'PENDIENTE',
    botellas_recicladas INT DEFAULT 0,
    valor_puntos INT DEFAULT 5,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dispositivo_id VARCHAR(50),
    INDEX (estado)
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 3. Empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    logo_url VARCHAR(255),
    web_url VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    UNIQUE (nombre)
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 4. Convenios (catálogo)
CREATE TABLE IF NOT EXISTS convenios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    puntos_requeridos INT NOT NULL,
    tipo_entrega ENUM('CODIGO', 'URL') DEFAULT 'CODIGO',
    base_url VARCHAR(255) NULL,
    stock INT DEFAULT 0,
    imagen_url VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX (empresa_id),
    INDEX (tipo_entrega)
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 5. Códigos de convenio (deben existir antes de crear canjes con FK)
CREATE TABLE IF NOT EXISTS codigos_convenio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT NOT NULL,
    codigo VARCHAR(255) UNIQUE NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    fecha_asignacion TIMESTAMP NULL,
    FOREIGN KEY (convenio_id) REFERENCES convenios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX (convenio_id),
    INDEX (usado)
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 6. Transacciones (scan / redeem)
CREATE TABLE IF NOT EXISTS transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo_qr_id INT NULL,
    canje_id INT NULL,
    puntos INT NOT NULL,
    tipo ENUM('scan', 'redeem') DEFAULT 'scan',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (codigo_qr_id) REFERENCES codigos_qr(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (canje_id) REFERENCES canjes(id)
        ON DELETE SET NULL ON UPDATE CASCADE
    INDEX (usuario_id),
    INDEX (tipo)
) ENGINE=InnoDB CHARSET=utf8mb4;

-- 7. Canjes realizados por usuarios (tiene FK a codigos_convenio)
CREATE TABLE IF NOT EXISTS canjes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    convenio_id INT NOT NULL,
    puntos_usados INT NOT NULL,
    codigo_convenio_id INT NULL,
    estado ENUM('PENDIENTE', 'ENTREGADO', 'CANCELADO') DEFAULT 'PENDIENTE',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (convenio_id) REFERENCES convenios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (codigo_convenio_id) REFERENCES codigos_convenio(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX (usuario_id),
    INDEX (convenio_id),
    INDEX (estado)
) ENGINE=InnoDB CHARSET=utf8mb4;