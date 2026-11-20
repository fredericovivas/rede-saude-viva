CREATE DATABASE IF NOT EXISTS saude_viva
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saude_viva;

CREATE TABLE especialidades (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilizadores (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  papel ENUM('utente','medico','rececionista','admin') NOT NULL,
  ativo TINYINT(1) DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medicos (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  utilizador_id INT NOT NULL,
  especialidade_id INT NOT NULL,
  numero_cedula VARCHAR(20) DEFAULT NULL,
  FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id),
  FOREIGN KEY (especialidade_id) REFERENCES especialidades(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utentes (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  utilizador_id INT NOT NULL,
  nif VARCHAR(9) DEFAULT NULL UNIQUE,
  data_nascimento DATE DEFAULT NULL,
  telefone VARCHAR(20) DEFAULT NULL,
  morada VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE horarios (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  medico_id INT NOT NULL,
  `data` DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fim TIME NOT NULL,
  disponivel TINYINT(1) DEFAULT 1,
  FOREIGN KEY (medico_id) REFERENCES medicos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consultas (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  utente_id INT NOT NULL,
  medico_id INT NOT NULL,
  horario_id INT NOT NULL,
  estado ENUM('agendada','realizada','cancelada') DEFAULT 'agendada',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utente_id) REFERENCES utentes(id),
  FOREIGN KEY (medico_id) REFERENCES medicos(id),
  FOREIGN KEY (horario_id) REFERENCES horarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notas_clinicas (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT NOT NULL,
  diagnostico TEXT DEFAULT NULL,
  sintomas TEXT DEFAULT NULL,
  tratamento TEXT DEFAULT NULL,
  observacoes TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (consulta_id) REFERENCES consultas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;