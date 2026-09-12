<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Treine perto de casa</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER -->
    <header class="header">
      <div class="container header__inner">
        <a href="index.html" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <p>Bem-vindo, Professor(a) Daniela Albuquerque</p>
        </nav>

        <a href="login.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>
    <br />
    <div class="cadastrar-exercicio card">
      <div class="cadastrar-exercicio card-body painel-cabecalho">
        <h2 class="card-title">Meus Alunos</h2>
        <a href="cadastrar-exerc.php" class="btn btn--exercicio"
          >+ Cadastrar Exercício</a
        >
      </div>
    </div>
    <br />

    <div class="tabela-wrapper">
      <table>
        <tr>
          <th>Nome</th>
          <th>CPF</th>
          <th>Treino (Status)</th>
          <th>Ação</th>
        </tr>
        <tr>
          <td>Zézinho da Silva</td>
          <td>123.456.789-00</td>
          <td>Sem Treino</td>
          <td>
            <a href="montar-treino.php" class="btn btn--montar"
              >Montar Treino</a
            >
          </td>
        </tr>
        <tr>
          <td>Mariazinha Oliveira</td>
          <td>987.654.321-00</td>
          <td>Atualizado</td>
          <td>
            <a href="editar-aluno.php" class="btn btn--editar">Editar</a>
          </td>
        </tr>
        <tr>
          <td>João da Silva</td>
          <td>111.222.333-44</td>
          <td>Sem Treino</td>
          <td>
            <a href="montar-treino.php" class="btn btn--montar"
              >Montar Treino</a
            >
          </td>
        </tr>
        <tr>
          <td>Ana Souza</td>
          <td>555.666.777-88</td>
          <td>Atualizado</td>
          <td>
            <a href="editar-aluno.php" class="btn btn--editar">Editar</a>
          </td>
        </tr>
      </table>
    </div>
    <script src="js/script.js"></script>
  </body>
</html>
