<?php
// =====================================================================
// FREQUÊNCIA DOS ALUNOS (dados dos check-ins que o aluno faz ao concluir o treino)
//   frequencia.php          → visão geral: todos os alunos, últimos 14 dias em quadradinhos
//   frequencia.php?id=3     → calendário do mês de um aluno, com números do período
//   frequencia.php?id=3&mes=2026-10 → mesmo calendário, em outro mês
// =====================================================================
require_once 'includes/protecao-professor.php';
require_once 'includes/layout.php';

$hoje = date('Y-m-d');
$aluno_id = (int) ($_GET['id'] ?? 0);

// =====================================================================
// VISÃO DE UM ALUNO
// =====================================================================
if ($aluno_id) {
  $stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE id = :id");
  $stmt->execute(['id' => $aluno_id]);
  $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$aluno) {
    header('Location: frequencia.php');
    exit;
  }

  // ---------- MÊS MOSTRADO ----------
  // Vem da URL (?mes=2026-10); se for inválido ou no futuro, mostra o mês atual.
  $mes_atual = date('Y-m');
  $mes = $_GET['mes'] ?? $mes_atual;
  if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) || $mes > $mes_atual) {
    $mes = $mes_atual;
  }
  $primeiro_dia = new DateTime($mes . '-01');
  $dias_no_mes = (int) $primeiro_dia->format('t');
  $mes_anterior = (clone $primeiro_dia)->modify('-1 month')->format('Y-m');
  $mes_seguinte = (clone $primeiro_dia)->modify('+1 month')->format('Y-m');

  // ---------- CHECK-INS DO MÊS ----------
  // Vira um "mapa" data → letra do treino, pra cada quadradinho do calendário saber se tem treino.
  $stmt = $pdo->prepare("
    SELECT data, letra FROM checkins
    WHERE alunos_id = :aluno AND data BETWEEN :inicio AND :fim
  ");
  $stmt->execute([
    'aluno' => $aluno_id,
    'inicio' => $mes . '-01',
    'fim' => $mes . '-' . $dias_no_mes,
  ]);
  $treinou = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $treinou[$c['data']] = $c['letra'] ?: '✓';
  }

  // ---------- NÚMEROS DO ALUNO ----------
  // Últimos 30 dias, média por semana (últimas 4 semanas) e o último treino
  $stmt = $pdo->prepare("
    SELECT
      (SELECT COUNT(*) FROM checkins WHERE alunos_id = :a1 AND data > :d30) AS ultimos_30,
      (SELECT COUNT(*) FROM checkins WHERE alunos_id = :a2 AND data > :d28) AS ultimos_28,
      (SELECT MAX(data) FROM checkins WHERE alunos_id = :a3) AS ultimo,
      (SELECT COUNT(*) FROM checkins WHERE alunos_id = :a4) AS total
  ");
  $stmt->execute([
    'a1' => $aluno_id, 'd30' => date('Y-m-d', strtotime('-30 days')),
    'a2' => $aluno_id, 'd28' => date('Y-m-d', strtotime('-28 days')),
    'a3' => $aluno_id, 'a4' => $aluno_id,
  ]);
  $numeros = $stmt->fetch(PDO::FETCH_ASSOC);
  $media_semana = round($numeros['ultimos_28'] / 4, 1);

  // ---------- MONTAGEM DO CALENDÁRIO ----------
  // Semana começa na segunda. "N" = 1 (segunda) a 7 (domingo): quantos quadradinhos vazios antes do dia 1.
  $vazios_antes = (int) $primeiro_dia->format('N') - 1;

  topo_professor('Frequência de ' . $aluno['nome'], 'frequencia');
  ?>
      <div class="painel-cabecalho">
        <div>
          <h2>Frequência de <?= htmlspecialchars($aluno['nome']) ?></h2>
          <p class="subtitulo">Dias em que o aluno concluiu o treino pelo app (check-in).</p>
        </div>
        <div class="painel-acoes">
          <a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--sm btn--editar">Ver treino</a>
          <a href="frequencia.php" class="btn btn--sm btn--secundario">← Todos os alunos</a>
        </div>
      </div>

      <!-- NÚMEROS DO ALUNO -->
      <div class="resumo-cards">
        <div class="resumo-card">
          <span class="resumo-card__numero"><?= count($treinou) ?></span>
          <span class="resumo-card__rotulo">Treinos em <?= nome_mes((int) $primeiro_dia->format('n')) ?></span>
        </div>
        <div class="resumo-card">
          <span class="resumo-card__numero"><?= (int) $numeros['ultimos_30'] ?></span>
          <span class="resumo-card__rotulo">Últimos 30 dias</span>
        </div>
        <div class="resumo-card">
          <span class="resumo-card__numero"><?= str_replace('.', ',', (string) $media_semana) ?></span>
          <span class="resumo-card__rotulo">Média por semana</span>
        </div>
        <div class="resumo-card <?= $numeros['ultimo'] && -dias_ate($numeros['ultimo']) >= 7 ? 'resumo-card--vencido' : '' ?>">
          <span class="resumo-card__numero resumo-card__numero--texto"><?= texto_ultimo_treino($numeros['ultimo']) ?></span>
          <span class="resumo-card__rotulo">Último treino</span>
        </div>
      </div>

      <!-- CALENDÁRIO DO MÊS -->
      <div class="card calendario">
        <div class="calendario__topo">
          <a href="frequencia.php?id=<?= $aluno['id'] ?>&mes=<?= $mes_anterior ?>" class="btn btn--sm btn--editar" aria-label="Mês anterior">‹</a>
          <h3><?= nome_mes((int) $primeiro_dia->format('n')) ?> de <?= $primeiro_dia->format('Y') ?></h3>
          <?php if ($mes < $mes_atual): ?>
            <a href="frequencia.php?id=<?= $aluno['id'] ?>&mes=<?= $mes_seguinte ?>" class="btn btn--sm btn--editar" aria-label="Próximo mês">›</a>
          <?php else: ?>
            <span class="btn btn--sm btn--editar calendario__desativado" aria-hidden="true">›</span>
          <?php endif; ?>
        </div>

        <div class="calendario__grade">
          <?php foreach (['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $dia_semana): ?>
            <span class="calendario__semana"><?= $dia_semana ?></span>
          <?php endforeach; ?>

          <?php for ($i = 0; $i < $vazios_antes; $i++): ?>
            <span class="calendario__dia calendario__dia--fora"></span>
          <?php endfor; ?>

          <?php for ($d = 1; $d <= $dias_no_mes; $d++):
            $data = sprintf('%s-%02d', $mes, $d);
            $classes = 'calendario__dia';
            if (isset($treinou[$data])) $classes .= ' calendario__dia--treinou';
            if ($data === $hoje) $classes .= ' calendario__dia--hoje';
            if ($data > $hoje) $classes .= ' calendario__dia--futuro';
          ?>
            <span class="<?= $classes ?>"
                  title="<?= date('d/m', strtotime($data)) ?><?= isset($treinou[$data]) ? ' · Treino ' . htmlspecialchars($treinou[$data]) : '' ?>">
              <span class="calendario__numero"><?= $d ?></span>
              <?php if (isset($treinou[$data])): ?>
                <span class="calendario__letra"><?= htmlspecialchars($treinou[$data]) ?></span>
              <?php endif; ?>
            </span>
          <?php endfor; ?>
        </div>

        <p class="calendario__legenda">
          <span class="calendario__amostra calendario__amostra--treinou"></span> Treinou (letra = treino feito)
          <span class="calendario__amostra calendario__amostra--hoje"></span> Hoje
        </p>
      </div>
  <?php
  rodape_professor();
  exit;
}

// =====================================================================
// VISÃO GERAL (todos os alunos)
// =====================================================================
const DIAS_FAIXA = 14;
$inicio_faixa = date('Y-m-d', strtotime('-' . (DIAS_FAIXA - 1) . ' days'));

// Alunos com o último treino e quantos treinos nos últimos 7 e 30 dias
$stmt = $pdo->prepare("
  SELECT a.id, a.nome,
         (SELECT MAX(c.data) FROM checkins c WHERE c.alunos_id = a.id) AS ultimo,
         (SELECT COUNT(*) FROM checkins c WHERE c.alunos_id = a.id AND c.data > :d7) AS ultimos_7,
         (SELECT COUNT(*) FROM checkins c WHERE c.alunos_id = a.id AND c.data > :d30) AS ultimos_30
  FROM alunos a
  ORDER BY a.nome
");
$stmt->execute(['d7' => date('Y-m-d', strtotime('-7 days')), 'd30' => date('Y-m-d', strtotime('-30 days'))]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check-ins dos últimos 14 dias de todos os alunos de uma vez: [aluno_id][data] = true
$stmt = $pdo->prepare("SELECT alunos_id, data FROM checkins WHERE data >= :inicio");
$stmt->execute(['inicio' => $inicio_faixa]);
$faixa = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
  $faixa[$c['alunos_id']][$c['data']] = true;
}

// Lista dos 14 dias (do mais antigo pro de hoje), usada pra desenhar os quadradinhos
$dias_faixa = [];
for ($i = DIAS_FAIXA - 1; $i >= 0; $i--) {
  $dias_faixa[] = date('Y-m-d', strtotime("-{$i} days"));
}
$letras_semana = ['', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D']; // índice = date('N')

$treinaram_hoje = count(array_filter($alunos, fn($a) => $a['ultimo'] === $hoje));
$sumidos = count(array_filter($alunos, fn($a) => !$a['ultimo'] || -dias_ate($a['ultimo']) >= 7));

topo_professor('Frequência', 'frequencia');
?>
      <div class="painel-cabecalho">
        <div>
          <h2>Frequência</h2>
          <p class="subtitulo">Cada quadradinho é um dia; vermelho = o aluno concluiu o treino (check-in no app).</p>
        </div>
      </div>

      <div class="resumo-cards">
        <div class="resumo-card resumo-card--ok">
          <span class="resumo-card__numero"><?= $treinaram_hoje ?></span>
          <span class="resumo-card__rotulo">Treinaram hoje</span>
        </div>
        <div class="resumo-card resumo-card--vencido">
          <span class="resumo-card__numero"><?= $sumidos ?></span>
          <span class="resumo-card__rotulo">7 dias ou mais sem treinar</span>
        </div>
      </div>

      <?php if (!$alunos): ?>
        <div class="card estado-vazio">
          <h3>Nenhum aluno cadastrado ainda</h3>
        </div>
      <?php else: ?>
        <div class="tabela-wrapper">
          <table class="tabela-cards tabela-frequencia">
            <tr>
              <th>Aluno</th>
              <th>Últimos <?= DIAS_FAIXA ?> dias</th>
              <th>7 dias</th>
              <th>30 dias</th>
              <th>Último treino</th>
              <th>Ação</th>
            </tr>
            <?php foreach ($alunos as $a): ?>
              <tr>
                <td class="td-titulo"><?= htmlspecialchars($a['nome']) ?></td>
                <td data-label="Últimos <?= DIAS_FAIXA ?> dias">
                  <div class="faixa-dias">
                    <?php foreach ($dias_faixa as $dia): ?>
                      <span class="faixa-dias__dia <?= isset($faixa[$a['id']][$dia]) ? 'faixa-dias__dia--treinou' : '' ?> <?= $dia === $hoje ? 'faixa-dias__dia--hoje' : '' ?>"
                            title="<?= date('d/m', strtotime($dia)) ?><?= isset($faixa[$a['id']][$dia]) ? ' · treinou' : '' ?>">
                        <?= $letras_semana[(int) date('N', strtotime($dia))] ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </td>
                <td data-label="7 dias"><?= (int) $a['ultimos_7'] ?></td>
                <td data-label="30 dias"><?= (int) $a['ultimos_30'] ?></td>
                <td data-label="Último treino"
                    class="<?= !$a['ultimo'] || -dias_ate($a['ultimo']) >= 7 ? 'texto-alerta' : '' ?>">
                  <?= texto_ultimo_treino($a['ultimo']) ?>
                </td>
                <td class="td-acoes">
                  <div class="painel-acoes">
                    <a href="frequencia.php?id=<?= $a['id'] ?>" class="btn btn--sm btn--editar">Calendário</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>
<?php rodape_professor(); ?>
