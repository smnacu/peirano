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

-- 3. Datos de Prueba (Usuarios)
-- Password para todos: 123456 (Hash generado para pruebas)
-- NOTA: Si este hash no funciona, generar uno nuevo con password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO users (cuit, password_hash, company_name, phone) VALUES
('20111111112', '$2y$10$abcdefghijklmnopqrstuv', 'Transporte El Rápido', '11-1111-1111'),
('20222222223', '$2y$10$abcdefghijklmnopqrstuv', 'Logística Sur', '11-2222-2222'),
('20333333334', '$2y$10$abcdefghijklmnopqrstuv', 'Fletes Buenos Aires', '11-3333-3333'),
('20444444445', '$2y$10$abcdefghijklmnopqrstuv', 'Distribuidora Norte', '11-4444-4444'),
('20555555556', '$2y$10$abcdefghijklmnopqrstuv', 'Camiones y Cargas SA', '11-5555-5555'),
('20666666667', '$2y$10$abcdefghijklmnopqrstuv', 'Expreso Patagónico', '11-6666-6666'),
('20777777778', '$2y$10$abcdefghijklmnopqrstuv', 'Transportes Gómez', '11-7777-7777'),
('20888888889', '$2y$10$abcdefghijklmnopqrstuv', 'Logística Integral', '11-8888-8888'),
('20999999990', '$2y$10$abcdefghijklmnopqrstuv', 'Cargas Express', '11-9999-9999'),
('23101010109', '$2y$10$abcdefghijklmnopqrstuv', 'Transporte Azul', '11-1010-1010'),
('23121212129', '$2y$10$abcdefghijklmnopqrstuv', 'Fletes Rápidos', '11-1212-1212'),
('23131313139', '$2y$10$abcdefghijklmnopqrstuv', 'Logística Oeste', '11-1313-1313'),
('23141414149', '$2y$10$abcdefghijklmnopqrstuv', 'Transporte Seguro', '11-1414-1414'),
('23151515159', '$2y$10$abcdefghijklmnopqrstuv', 'Cargas Pesadas SRL', '11-1515-1515'),
('23161616169', '$2y$10$abcdefghijklmnopqrstuv', 'Distribución Total', '11-1616-1616');
