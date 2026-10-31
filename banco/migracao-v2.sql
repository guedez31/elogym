-- =====================================================================
-- MIGRAÇÃO v2 — AC Academia / EloGym
-- Rodar UMA vez no phpMyAdmin (aba SQL) no banco ac_academia.
-- Pode rodar de novo sem quebrar nada (usa IF NOT EXISTS).
--
-- O que muda:
--   1. alunos.treino_validade     → até quando a ficha de treino vale (aviso de vencido)
--   2. professores.admin          → quem pode cadastrar outros professores
--      professores.ativo          → professor desativado não entra mais no sistema
--      professores.primeiro_acesso→ professor novo troca a senha no 1º login
--   3. tabela checkins            → dias em que o aluno concluiu o treino (frequência)
-- =====================================================================

-- ---------- 1. VALIDADE DA FICHA ----------
ALTER TABLE `alunos`
  ADD COLUMN IF NOT EXISTS `treino_validade` DATE DEFAULT NULL AFTER `data_nascimento`;

-- Alunos que já têm treino ganham validade de 3 meses contados do primeiro treino criado.
UPDATE `alunos` a
JOIN (SELECT alunos_id, MIN(data_criacao) AS inicio FROM `treinos` GROUP BY alunos_id) t
  ON t.alunos_id = a.id
SET a.treino_validade = DATE_ADD(DATE(t.inicio), INTERVAL 3 MONTH)
WHERE a.treino_validade IS NULL;

-- ---------- 2. PROFESSORES: ADMIN, ATIVO E PRIMEIRO ACESSO ----------
ALTER TABLE `professores`
  ADD COLUMN IF NOT EXISTS `admin` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `primeiro_acesso` TINYINT(1) NOT NULL DEFAULT 0;

-- A Daniela (dona da academia) vira administradora.
-- Confira o CPF: se no seu banco for outro, troque aqui antes de rodar.
UPDATE `professores` SET `admin` = 1 WHERE `cpf` = '22233344455';

-- ---------- 3. CHECK-IN (TREINO CONCLUÍDO) ----------
-- Uma linha por dia que o aluno concluiu o treino. A chave única (aluno + data)
-- impede dois check-ins no mesmo dia. A letra é guardada à parte pra o histórico
-- continuar mostrando "Treino A" mesmo se a professora apagar esse treino depois.
CREATE TABLE IF NOT EXISTS `checkins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `alunos_id` INT(11) NOT NULL,
  `treinos_id` INT(11) DEFAULT NULL,
  `letra` CHAR(1) DEFAULT NULL,
  `data` DATE NOT NULL,
  `criado_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aluno_dia` (`alunos_id`, `data`),
  KEY `treinos_id` (`treinos_id`),
  CONSTRAINT `checkins_ibfk_1` FOREIGN KEY (`alunos_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `checkins_ibfk_2` FOREIGN KEY (`treinos_id`) REFERENCES `treinos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
