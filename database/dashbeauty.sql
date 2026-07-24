-- ============================================
-- DASHBEAUTY - BANCO DE DADOS COMPLETO
-- ============================================

DROP DATABASE IF EXISTS dashbeauty;
CREATE DATABASE dashbeauty;
USE dashbeauty;

-- ============================================
-- TABELA DE USUÁRIOS
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    photo VARCHAR(500),
    user_type ENUM('client', 'business') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA DE EMPRESAS (com logo, descrição, etc)
-- ============================================
CREATE TABLE businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    description TEXT,
    logo VARCHAR(500),
    banner VARCHAR(500),
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    rating DECIMAL(2, 1) DEFAULT 0,
    total_ratings INT DEFAULT 0,
    whatsapp VARCHAR(20),
    instagram VARCHAR(100),
    facebook VARCHAR(100),
    website VARCHAR(200),
    is_featured BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FULLTEXT INDEX idx_search (business_name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA DE HORÁRIOS
-- ============================================
CREATE TABLE business_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Segunda, 1=Terça, 2=Quarta, 3=Quinta, 4=Sexta, 5=Sábado, 6=Domingo',
    open_time TIME NULL,
    close_time TIME NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_business_day (business_id, day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA DE SERVIÇOS
-- ============================================
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration_minutes INT NOT NULL,
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA DE AGENDAMENTOS
-- ============================================
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    client_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    price DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (client_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA DE AVALIAÇÕES
-- ============================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT UNIQUE NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA DE FAVORITOS
-- ============================================
CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DADOS DE EXEMPLO
-- ============================================

-- Usuários
INSERT INTO users (email, password, name, phone, user_type) VALUES
('admin@dashbeauty.com', MD5('123456'), 'Administrador', '(11) 99999-0000', 'business'),
('salao.maria@email.com', MD5('123456'), 'Maria Silva', '(11) 99999-1111', 'business'),
('ana.cliente@email.com', MD5('123456'), 'Ana Oliveira', '(11) 99999-3333', 'client');

-- Empresas (com logo, descrição, etc)
INSERT INTO businesses (user_id, business_name, description, logo, address, whatsapp, instagram, is_featured, rating, total_ratings) VALUES
(1, 'Salão da Maria', 'Salão completo com os melhores profissionais. Especialistas em cortes modernos, coloração e tratamentos capilares. Ambiente aconchegante e produtos de alta qualidade.', '/assets/images/salao-maria-logo.png', 'Rua das Flores, 123 - São Paulo, SP', '(11) 98888-1111', '@salaodamaria', 1, 4.8, 120),
(2, 'Studio de Beleza', 'Especializado em cabelos e unhas. Atendimento personalizado e produtos de alta qualidade. Profissionais experientes e ambiente moderno.', '/assets/images/studio-beleza-logo.png', 'Av. Paulista, 1000 - São Paulo, SP', '(11) 97777-2222', '@studiobeleza', 0, 4.5, 85);

-- Horários
INSERT INTO business_hours (business_id, day_of_week, open_time, close_time, is_closed) VALUES
(1, 0, '09:00:00', '18:00:00', 0),
(1, 1, '09:00:00', '18:00:00', 0),
(1, 2, '09:00:00', '18:00:00', 0),
(1, 3, '09:00:00', '18:00:00', 0),
(1, 4, '09:00:00', '18:00:00', 0),
(1, 5, '09:00:00', '16:00:00', 0),
(1, 6, NULL, NULL, 1);

-- Serviços
INSERT INTO services (business_id, name, description, price, duration_minutes, category) VALUES
(1, 'Corte de Cabelo', 'Corte moderno com design personalizado, inclui lavagem e finalização.', 50.00, 45, 'Cabelo'),
(1, 'Coloração', 'Coloração completa com produtos premium.', 120.00, 120, 'Cabelo'),
(1, 'Hidratação', 'Tratamento intensivo de hidratação.', 80.00, 60, 'Cabelo'),
(2, 'Manicure', 'Alongamento de unhas em gel com esmaltação.', 70.00, 90, 'Unhas'),
(2, 'Pedicure', 'Pé completo com esmaltação e massagem.', 60.00, 60, 'Unhas');

-- Agendamentos
INSERT INTO appointments (service_id, client_id, appointment_date, appointment_time, price, status) VALUES
(1, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 50.00, 'scheduled'),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 120.00, 'scheduled');

-- Avaliações
INSERT INTO reviews (appointment_id, rating, comment) VALUES
(1, 5, 'Excelente atendimento! A Maria é muito profissional e atenciosa.'),
(2, 4, 'Muito bom, o resultado ficou lindo.');

-- Favoritos
INSERT INTO favorites (user_id, business_id) VALUES
(3, 1);

-- ============================================
-- VERIFICAÇÃO
-- ============================================
SELECT 'BANCO DE DADOS CRIADO COM SUCESSO!' as STATUS;
SELECT 'Usuários' as Tabela, COUNT(*) as Total FROM users
UNION ALL SELECT 'Empresas', COUNT(*) FROM businesses
UNION ALL SELECT 'Serviços', COUNT(*) FROM services;