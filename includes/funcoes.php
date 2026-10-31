<?php
// =====================================================================
// FUNÇÕES COMPARTILHADAS — usadas por várias telas do sistema
// =====================================================================

// Todas as datas do sistema no horário de Brasília (senão o "hoje" vira o dia
// seguinte às 21h, porque o servidor costuma estar em UTC).
date_default_timezone_set('America/Sao_Paulo');

// ---------- CPF ----------
// Confere os 2 dígitos verificadores do CPF (algoritmo oficial da Receita).
// Não consulta nenhum serviço externo: só garante que o número foi digitado certo.
// Ex: 529.982.247-25 → true | 123.456.789-00 → false
function cpf_valido(string $cpf): bool {
  $cpf = preg_replace('/\D/', '', $cpf);

  // Precisa ter 11 números e não pode ser tudo igual (111.111.111-11 passa na conta, mas não existe)
  if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
    return false;
  }

  // Calcula o 10º e depois o 11º dígito e compara com o que foi digitado
  for ($posicao = 9; $posicao < 11; $posicao++) {
    $soma = 0;
    for ($i = 0; $i < $posicao; $i++) {
      $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
    }
    $digito = ((10 * $soma) % 11) % 10;
    if ((int) $cpf[$posicao] !== $digito) {
      return false;
    }
  }
  return true;
}

// "52998224725" → "529.982.247-25"
function formatar_cpf(string $cpf): string {
  $cpf = preg_replace('/\D/', '', $cpf);
  if (strlen($cpf) !== 11) return $cpf;
  return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// ---------- STATUS DO TREINO ----------
// Quantos dias antes de vencer o treino já aparece o aviso "Vence em X dias"
const DIAS_AVISO_VENCIMENTO = 14;

// Decide o status da ficha de um aluno, na ordem de prioridade:
//   Sem treino   → nenhuma ficha criada
//   Em andamento → existe ficha (A, B...) sem nenhum exercício
//   Vencido      → a validade já passou
//   Vence logo   → faltam até 14 dias
//   Em dia       → tudo certo
// Devolve ['classe' => sufixo do CSS (badge--...), 'texto' => o que aparece na tela, 'chave' => usada no filtro do painel]
function status_treino(int $total_treinos, int $treinos_vazios, ?string $validade): array {
  if ($total_treinos === 0) {
    return ['classe' => 'pendente', 'texto' => 'Sem treino', 'chave' => 'sem-treino'];
  }
  if ($treinos_vazios > 0) {
    return ['classe' => 'andamento', 'texto' => 'Em andamento', 'chave' => 'andamento'];
  }
  if ($validade) {
    $dias = dias_ate($validade);
    if ($dias < 0) {
      return ['classe' => 'vencido', 'texto' => 'Vencido', 'chave' => 'vencido'];
    }
    if ($dias <= DIAS_AVISO_VENCIMENTO) {
      return ['classe' => 'alerta', 'texto' => 'Vence logo', 'chave' => 'vencendo'];
    }
  }
  return ['classe' => 'ok', 'texto' => 'Em dia', 'chave' => 'em-dia'];
}

// Quantos dias faltam até a data (negativo = já passou). Ex: amanhã → 1, ontem → -1
function dias_ate(string $data): int {
  $hoje = new DateTime('today');
  $alvo = new DateTime($data);
  return (int) $hoje->diff($alvo)->format('%r%a');
}

// Texto da coluna "Validade": "até 28/12/2026 · faltam 58 dias" / "venceu há 3 dias"
function texto_validade(?string $validade): string {
  if (!$validade) return '—';
  $dias = dias_ate($validade);
  $data = date('d/m/Y', strtotime($validade));
  if ($dias < 0)   return 'venceu em ' . $data;
  if ($dias === 0) return 'vence hoje';
  if ($dias === 1) return 'vence amanhã';
  return 'até ' . $data . ' · ' . $dias . ' dias';
}

// Soma meses sem "pular" o mês: 31/10 + 4 meses = 28/02 (e não 03/03, que é o que o PHP faz sozinho)
function somar_meses(string $data, int $meses): string {
  $d = new DateTime($data);
  $dia = (int) $d->format('j');
  $d->modify('first day of this month')->modify("+{$meses} months");
  $d->setDate((int) $d->format('Y'), (int) $d->format('n'), min($dia, (int) $d->format('t')));
  return $d->format('Y-m-d');
}

// ---------- FREQUÊNCIA ----------
// Texto do "último treino": "Hoje", "Ontem", "Há 5 dias" ou "Nunca"
function texto_ultimo_treino(?string $data): string {
  if (!$data) return 'Nunca';
  $dias = -dias_ate($data);
  if ($dias <= 0) return 'Hoje';
  if ($dias === 1) return 'Ontem';
  return 'Há ' . $dias . ' dias';
}

// Nome do mês em português (date() do PHP só fala inglês)
function nome_mes(int $mes): string {
  $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
  return $meses[$mes - 1];
}
