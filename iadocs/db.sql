-- 1. TABLA: USUARIOS
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL, -- Almacenar siempre encriptada
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    pais VARCHAR(100),
    tipo ENUM('Admin', 'Cliente', 'PT') NOT NULL DEFAULT 'Cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. TABLA: PROVEEDORA DE TOURS
CREATE TABLE proveedoras_tours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL, -- Opcional (Relación 1:N según diagrama)
    nombre_empresa VARCHAR(150) NOT NULL,
    descripcion TEXT,
    rfc VARCHAR(50),
    numero_contacto VARCHAR(20),
    correo VARCHAR(150),
    representante_nombre VARCHAR(150),
    representante_numero VARCHAR(20),
    representante_correo VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_proveedora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- 3. TABLA: TOUR
CREATE TABLE tours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedora_id INT NOT NULL, -- Para saber qué operador lo maneja de forma interna
    titulo VARCHAR(150) NOT NULL,
    imagen_destacada VARCHAR(255),
    galeria_imagenes_url VARCHAR(255), -- Referencia general
    descripcion_breve TEXT,
    precio_base DECIMAL(10, 2) NOT NULL, -- Precio para el cliente final
    horario VARCHAR(100), -- Día completo o por horas
    ubicacion VARCHAR(255),
    pais VARCHAR(100),
    ciudad VARCHAR(100),
    estado_tour VARCHAR(50), -- Activo, Inactivo, etc.
    duracion_tour VARCHAR(50),
    nombre_tour_operadora VARCHAR(150), -- Nombre comercial abstracto para el cliente
    tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tour_proveedora FOREIGN KEY (proveedora_id) REFERENCES proveedoras_tours(id) ON DELETE CASCADE
);

-- 4. TABLA: GALERÍA TOUR (Relación 1:1 con TOUR)
CREATE TABLE galerias_tour (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL UNIQUE, -- UNIQUE garantiza la relación 1:1
    conjunto_imagenes JSON NOT NULL, -- Formato JSON para guardar múltiples URLs de imágenes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_galeria_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

-- 5. TABLA: CALENDARIO TOUR (Relación 1:1 con TOUR)
CREATE TABLE calendarios_tour (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL UNIQUE, -- UNIQUE garantiza la relación 1:1
    titulo VARCHAR(150),
    imagen_descriptiva VARCHAR(255),
    galeria_imagenes_url VARCHAR(255),
    descripcion_breve TEXT,
    precio_base DECIMAL(10, 2),
    ubicacion VARCHAR(255),
    duracion_tour VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendario_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

-- 6. TABLA: RESERVACIONES
CREATE TABLE reservaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL, -- El cliente que reserva
    tour_id INT NOT NULL, -- El tour reservado
    individual_o_grupo VARCHAR(50), -- Individual / Grupo
    asientos INT NOT NULL, -- Cantidad de personas
    precio_final DECIMAL(10, 2) NOT NULL,
    fecha_reservado DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio_tour DATETIME NOT NULL,
    fecha_final_tour DATETIME NOT NULL,
    estado ENUM('Pagado', 'En progreso', 'Finalizado') NOT NULL DEFAULT 'Pagado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_reserva_tour FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE RESTRICT
);

-- 7. TABLA: TICKET (Relación 1:1 con RESERVACIONES)
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservacion_id INT NOT NULL UNIQUE, -- UNIQUE garantiza que una reserva solo tenga un ticket (1:1)
    fecha_generado DATETIME DEFAULT CURRENT_TIMESTAMP,
    monto_del_ticket DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_reserva FOREIGN KEY (reservacion_id) REFERENCES reservaciones(id) ON DELETE CASCADE
);