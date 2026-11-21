-- =====================================================================
-- MIGRAÇÃO v3 — AC Academia / EloGym
-- Rodar UMA vez no phpMyAdmin (aba SQL) no banco ac_academia, DEPOIS da v2.
-- Pode rodar de novo sem quebrar nada (usa IF NOT EXISTS).
--
-- O que muda:
--   alunos.ativo          → aluno desativado não entra no sistema e sai da lista principal,
--                           mas as fichas e a frequência continuam guardadas (é só reativar)
--   alunos.desativado_em  → dia em que foi desativado (aparece na lista de desativados)
-- =====================================================================

ALTER TABLE `alunos`
  ADD COLUMN IF NOT EXISTS `ativo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `treino_validade`,
  ADD COLUMN IF NOT EXISTS `desativado_em` DATE DEFAULT NULL AFTER `ativo`;
