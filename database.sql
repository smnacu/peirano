-- 1. Tabla de Usuarios (Acá se guardan los proveedores)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuit VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Turnos (Acá se guardan las reservas)
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    needs_forklift TINYINT(1) DEFAULT 0, -- 0 es No, 1 es Sí
    quantity INT NOT NULL,
    observations TEXT,
    outlook_event_id VARCHAR(255), -- Para conectar con Microsoft después
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) -- Esto une el turno con el usuario
) ENGINE=InnoDB;
