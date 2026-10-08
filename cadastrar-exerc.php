<?php
// Protege a página da professora: se não houver login na sessão ou se o
// usuário não for professor, volta pro login e interrompe o carregamento.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
?>
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
          <p>Bem-vindo, Professor(a) <?php echo $_SESSION['usuario_nome']; ?></p>
        </nav>

        <a href="logout.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>
    <br />

    <div class="form-linha">
      <input type="text" placeholder="Nome do Exercício" />
      <select name="grupo-muscular" id="select-grupo">
        <option value="">Selecione um grupo muscular</option>
        <option value="costas">Costas</option>
        <option value="peito">Peito</option>
        <option value="biceps">Bíceps</option>
        <option value="triceps">Tríceps</option>
        <option value="ombro">Ombro</option>
        <option value="quadriceps">Quadríceps</option>
        <option value="posterior">Posterior de perna</option>
        <option value="gluteo">Glúteo</option>
        <option value="panturrilha">Panturrilha</option>
        <option value="abdomen">Abdômen</option>
      </select>
    </div>
    <button type="button" class="btn btn--primario">
      + Cadastrar Exercício
    </button>

    <div class="tabela-wrapper">
      <table>
        <tr>
          <th>Exercícios já cadastrados</th>
        </tr>
        <tr>
          <td>Peito - Supino Reto, Supino Inclinado, Cross-over</td>
        </tr>

        <tr>
          <td>Bíceps - Rosca Direta, Rosca Alternada, Rosca Scott</td>
        </tr>
        <tr>
          <td>Tríceps - Tríceps Testa, Tríceps Francês, Tríceps Corda</td>
        </tr>
        <tr>
          <td>Ombro - Elevação Lateral, Desenvolvimento, Remada Alta</td>
        </tr>
        <tr>
          <td>Costas - Puxada na Barra Fixa, Remada Unilateral, Pull-over</td>
        </tr>
        <tr>
          <td>Quadríceps - Agachamento Livre, Leg Press, Hack</td>
        </tr>
        <tr>
          <td>Posterior de perna - Stiff, Curl, Leg Curl</td>
        </tr>
        <tr>
          <td>Glúteo - Elevação de Quadril, Glute Kickback, Hip Thrust</td>
        </tr>
        <tr>
          <td>Panturrilha - Elevação de Panturrilha, Curl de Panturrilha</td>
        </tr>
        <tr>
          <td>Abdômen - Prancha</td>
        </tr>
      </table>
    </div>
  </body>
</html>
