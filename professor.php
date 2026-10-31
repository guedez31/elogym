<?php
// ---------- PROTEÇÃO ----------
// Só professor logado (e ativo) acessa o painel; o include também carrega conexão e funções.
require_once 'includes/protecao-professor.php';
require_once 'includes/layout.php';

$hoje = date('Y-m-d');

// ---------- LISTA DE ALUNOS ----------
// Uma linha por aluno, com: idade, siglas de saúde, quantos treinos ativos ele tem, quantos deles
// estão VAZIOS (sem exercício → "Em andamento"), a validade da ficha e o dia do último check-in.
// Os totais vêm de subconsultas (SELECT dentro do SELECT) pra um JOIN não multiplicar o outro.
$stmt = $pdo->prepare("
  SELECT a.id, a.nome, a.treino_validade,
         TIMESTAMPDIFF(YEAR, a.data_nascimento, :hoje1) AS idade,
         (SELECT GROUP_CONCAT(l.sigla ORDER BY l.sigla SEPARATOR ', ')
            FROM aluno_limitacoes al JOIN limitacoes_saude l ON l.id = al.limitacoes_saude_id
           WHERE al.alunos_id = a.id) AS siglas,
         (SELECT GROUP_CONCAT(CONCAT(l.sigla, ' = ', l.descricao) ORDER BY l.sigla SEPARATOR ' | ')
            FROM aluno_limitacoes al JOIN limitacoes_saude l ON l.id = al.limitacoes_saude_id
           WHERE al.alunos_id = a.id) AS detalhe,
         (SELECT COUNT(*) FROM treinos t WHERE t.alunos_id = a.id AND t.ativo = 1) AS total_treinos,
         (SELECT COUNT(*) FROM treinos t
           WHERE t.alunos_id = a.id AND t.ativo = 1
             AND NOT EXISTS (SELECT 1 FROM treino_exercicios te WHERE te.treinos_id = t.id)) AS treinos_vazios,
         (SELECT MAX(c.data) FROM checkins c WHERE c.alunos_id = a.id) AS ultimo_checkin
  FROM alunos a
  ORDER BY a.nome
");
$stmt->execute(['hoje1' => $hoje]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o status de cada aluno uma vez só (usado na tabela, nos cards e no filtro)
foreach ($alunos as &$aluno) {
  $aluno['status'] = status_treino((int) $aluno['total_treinos'], (int) $aluno['treinos_vazios'], $aluno['treino_validade']);
}
unset($aluno);

// ---------- CARDS DE RESUMO ----------
// Contam quantos alunos caem em cada situação. Cada card é um link que filtra a tabela.
$filtros = [
  'atencao'    => ['Vencidos ou vencendo', fn($a) => in_array($a['status']['chave'], ['vencido', 'vencendo'])],
  'incompleto' => ['Sem treino ou em andamento', fn($a) => in_array($a['status']['chave'], ['sem-treino', 'andamento'])],
  'hoje'       => ['Treinaram hoje', fn($a) => $a['ultimo_checkin'] === $hoje],
];
$contagem = [];
foreach ($filtros as $chave => [$rotulo, $regra]) {
  $contagem[$chave] = count(array_filter($alunos, $regra));
}

// ---------- FILTRO ATIVO ----------
// ?filtro=atencao na URL mostra só os alunos daquele card
$filtro = $_GET['filtro'] ?? '';
$alunos_tabela = isset($filtros[$filtro]) ? array_filter($alunos, $filtros[$filtro][1]) : $alunos;

// ---------- LEGENDA DAS SIGLAS ----------
$legenda = $pdo->query("SELECT sigla, descricao FROM limitacoes_saude ORDER BY sigla")->fetchAll(PDO::FETCH_ASSOC);

topo_professor('Meus Alunos', 'alunos');
?>
  <!-- AVISO DE SUCESSO: aparece quando acabou de cadastrar um aluno -->
  <?php if (isset($_GET['cadastrado'])): ?>
    <p class="alert alert--sucesso">Aluno cadastrado com sucesso!</p>
  <?php endif; ?>

  <!-- CABEÇALHO DO PAINEL: título e atalho pro cadastro (o resto fica no menu lateral) -->
  <div class="painel-cabecalho">
    <div>
      <h2>Meus Alunos</h2>
      <p class="subtitulo">Olá, <?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'])[0]) ?>! Hoje é <?= date('d/m/Y') ?>.</p>
    </div>
    <a href="cadastrar-aluno.php" class="btn btn--primario">+ Cadastrar Aluno</a>
  </div>

  <!-- CARDS DE RESUMO: tocar filtra a tabela; tocar de novo (ou em "Todos") tira o filtro -->
  <div class="resumo-cards">
    <a href="professor.php" class="resumo-card <?= $filtro === '' ? 'resumo-card--ativo' : '' ?>">
      <span class="resumo-card__numero"><?= count($alunos) ?></span>
      <span class="resumo-card__rotulo">Todos os alunos</span>
    </a>
    <a href="professor.php<?= $filtro === 'atencao' ? '' : '?filtro=atencao' ?>"
       class="resumo-card resumo-card--vencido <?= $filtro === 'atencao' ? 'resumo-card--ativo' : '' ?>">
      <span class="resumo-card__numero"><?= $contagem['atencao'] ?></span>
      <span class="resumo-card__rotulo"><?= $filtros['atencao'][0] ?></span>
    </a>
    <a href="professor.php<?= $filtro === 'incompleto' ? '' : '?filtro=incompleto' ?>"
       class="resumo-card resumo-card--andamento <?= $filtro === 'incompleto' ? 'resumo-card--ativo' : '' ?>">
      <span class="resumo-card__numero"><?= $contagem['incompleto'] ?></span>
      <span class="resumo-card__rotulo"><?= $filtros['incompleto'][0] ?></span>
    </a>
    <a href="professor.php<?= $filtro === 'hoje' ? '' : '?filtro=hoje' ?>"
       class="resumo-card resumo-card--ok <?= $filtro === 'hoje' ? 'resumo-card--ativo' : '' ?>">
      <span class="resumo-card__numero"><?= $contagem['hoje'] ?></span>
      <span class="resumo-card__rotulo"><?= $filtros['hoje'][0] ?></span>
    </a>
  </div>

  <?php if (!$alunos): ?>
    <!-- ESTADO VAZIO: nenhum aluno cadastrado ainda -->
    <div class="card estado-vazio">
      <h3>Nenhum aluno cadastrado ainda</h3>
      <p>Clique em "+ Cadastrar Aluno" pra começar.</p>
    </div>
  <?php elseif (!$alunos_tabela): ?>
    <!-- FILTRO SEM RESULTADO -->
    <div class="card estado-vazio">
      <h3>Nenhum aluno nessa situação 🎉</h3>
      <p><a href="professor.php" class="link-secundario">Ver todos os alunos</a></p>
    </div>
  <?php else: ?>
    <!-- TABELA DE ALUNOS: vira um card por aluno no celular (classe tabela-cards + data-label) -->
    <div class="tabela-wrapper">
      <table class="tabela-cards tabela-alunos">
        <tr>
          <th>Nome</th>
          <th>Idade</th>
          <th>Saúde</th>
          <th>Treino</th>
          <th>Validade</th>
          <th>Último treino</th>
          <th>Ação</th>
        </tr>
        <?php foreach ($alunos_tabela as $aluno): ?>
          <tr>
            <td class="td-titulo"><?= htmlspecialchars($aluno['nome']) ?></td>

            <!-- Idade: "—" se o aluno não tem data de nascimento cadastrada -->
            <td data-label="Idade"><?= $aluno['idade'] !== null ? (int) $aluno['idade'] : '—' ?></td>

            <!-- Siglas: ao passar o mouse aparece o significado -->
            <td data-label="Saúde">
              <?php if ($aluno['siglas']): ?>
                <span title="<?= htmlspecialchars($aluno['detalhe']) ?>"><?= htmlspecialchars($aluno['siglas']) ?></span>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>

            <!-- Status em badge: Sem treino / Em andamento / Em dia / Vence logo / Vencido -->
            <td data-label="Treino">
              <span class="badge badge--<?= $aluno['status']['classe'] ?>"
                    <?= $aluno['status']['chave'] === 'andamento' ? 'title="' . (int) $aluno['treinos_vazios'] . ' ficha(s) ainda sem exercício"' : '' ?>>
                <?= $aluno['status']['texto'] ?>
              </span>
            </td>

            <!-- Validade: só faz sentido pra quem já tem treino -->
            <td data-label="Validade" class="td-validade td-validade--<?= $aluno['status']['chave'] ?>">
              <?= $aluno['total_treinos'] > 0 ? htmlspecialchars(texto_validade($aluno['treino_validade'])) : '—' ?>
            </td>

            <!-- Último check-in do aluno, com link pro calendário de frequência -->
            <td data-label="Último treino">
              <a href="frequencia.php?id=<?= $aluno['id'] ?>" class="link-frequencia">
                <?= texto_ultimo_treino($aluno['ultimo_checkin']) ?>
              </a>
            </td>

            <!-- Ações: treino (Editar/Montar Treino) e dados do aluno -->
            <td class="td-acoes">
              <div class="painel-acoes">
                <?php if ($aluno['total_treinos'] > 0): ?>
                  <a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--sm btn--editar">Editar Treino</a>
                <?php else: ?>
                  <a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--sm btn--montar">Montar Treino</a>
                <?php endif; ?>
                <a href="editar-aluno.php?id=<?= $aluno['id'] ?>" class="btn btn--sm btn--editar">Dados</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>

  <!-- LEGENDA: significado das siglas e dos status (útil no celular, onde não existe "passar o mouse") -->
  <div class="legenda-painel">
    <?php if ($legenda): ?>
      <p>
        <strong>Siglas:</strong>
        <?= htmlspecialchars(implode(' · ', array_map(fn($l) => $l['sigla'] . ' = ' . $l['descricao'], $legenda))) ?>
      </p>
    <?php endif; ?>
    <p>
      <strong>Treino:</strong>
      <span class="badge badge--andamento">Em andamento</span> tem ficha sem exercício ·
      <span class="badge badge--alerta">Vence logo</span> faltam até <?= DIAS_AVISO_VENCIMENTO ?> dias ·
      <span class="badge badge--vencido">Vencido</span> precisa de ficha nova
    </p>
  </div>
<?php rodape_professor(); ?>
