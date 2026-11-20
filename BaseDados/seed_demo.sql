USE saude_viva;

INSERT INTO especialidades (nome) VALUES
('Medicina Geral e Familiar'), ('Cardiologia'), ('Dermatologia'),
('Pediatria'), ('Ortopedia');

-- Contas de DEMONSTRAÇÃO (dados fictícios). Password de todas: teste123
INSERT INTO utilizadores (nome, email, password, papel) VALUES
('Admin Sistema',      'admin@saudeviva.pt',      '$2y$10$9b9KRQ9GC5GA55JGO6P6b.fHK2bxz7/d.PUCQ4hWCpQlRTrbVW13u', 'admin'),
('Dr. João Silva',     'joao.silva@saudeviva.pt', '$2y$10$9b9KRQ9GC5GA55JGO6P6b.fHK2bxz7/d.PUCQ4hWCpQlRTrbVW13u', 'medico'),
('Dra. Ana Costa',     'ana.costa@saudeviva.pt',  '$2y$10$9b9KRQ9GC5GA55JGO6P6b.fHK2bxz7/d.PUCQ4hWCpQlRTrbVW13u', 'medico'),
('Maria Santos',       'maria@saudeviva.pt',      '$2y$10$9b9KRQ9GC5GA55JGO6P6b.fHK2bxz7/d.PUCQ4hWCpQlRTrbVW13u', 'utente'),
('Rececionista Carla', 'carla@saudeviva.pt',      '$2y$10$9b9KRQ9GC5GA55JGO6P6b.fHK2bxz7/d.PUCQ4hWCpQlRTrbVW13u', 'rececionista');

INSERT INTO medicos (utilizador_id, especialidade_id, numero_cedula) VALUES
(2, 1, 'CED001'), (3, 2, 'CED002');

INSERT INTO utentes (utilizador_id, nif, data_nascimento, telefone) VALUES
(4, '123456789', '1990-05-15', '912345678');

-- Horários: próximos 31 dias, 12 por dia e por médico (sempre datas futuras)
INSERT INTO horarios (medico_id, `data`, hora_inicio, hora_fim, disponivel)
SELECT m.id, CURDATE() + INTERVAL d.n DAY, h.t, ADDTIME(h.t, '00:30:00'), 1
FROM medicos m
CROSS JOIN (
    SELECT a.i + b.i * 10 AS n
    FROM (SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
          UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
    CROSS JOIN (SELECT 0 AS i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) b
) d
CROSS JOIN (
    SELECT '09:00:00' AS t UNION SELECT '09:30:00' UNION SELECT '10:00:00'
    UNION SELECT '10:30:00' UNION SELECT '11:00:00' UNION SELECT '11:30:00'
    UNION SELECT '14:00:00' UNION SELECT '14:30:00' UNION SELECT '15:00:00'
    UNION SELECT '15:30:00' UNION SELECT '16:00:00' UNION SELECT '16:30:00'
) h
WHERE d.n <= 30;