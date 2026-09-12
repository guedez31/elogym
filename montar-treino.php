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
          <h3>Zézinho da Silva</h3>
        </nav>
      </div>
    </header>
    <br />
    <div class="abas-treino">
      <a href="#.html" class="aba aba--ativa">Treino A</a>
      <a href="#.html" class="aba">Treino B</a>
      <a href="#.html" class="aba">Treino C</a>
      <a href="#.html" class="aba">Treino D</a>
      <a href="#.html" class="aba aba--add">+</a>
    </div>
    <br />
    <div class="tabela-wrapper">
      <table>
        <tr>
          <th>Exercício</th>
          <th>Ação</th>
        </tr>
        <tr>
          <td>
            <h4>Supino reto barra</h4>
            4x6-8
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>

        <tr>
          <td>
            <h4>Supino Inclinado com halteres</h4>
            3x8-10
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>

        <tr>
          <td>
            <h4>Crucifixo Maquina</h4>
            3x10-12 + DROP
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>

        <tr>
          <td>
            <h4>Crossover (baixo → alto)</h4>
            3x12-15
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>

        <tr>
          <td>
            <h4>Tríceps corda</h4>
            3x10-12 + REST-PAUSE
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>

        <tr>
          <td>
            <h4>Tríceps testa</h4>
            3x8-10
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>

        <tr>
          <td>
            <h4>Paralelas</h4>
            3x FALHA
          </td>
          <td>
            <a href="#" class="btn btn--remover">Remover</a>
          </td>
        </tr>
      </table>
    </div>

    <button type="button" class="btn btn--secundario" id="btn-toggle-form">
      + Adicionar Exercício
    </button>
    <div class="formulario-exercicio" id="form-exercicio">
      <div class="form-linha">
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
        <select name="exercicio" id="select-exercicio">
          <option value="">Selecione um exercício</option>
        </select>
      </div>

      <div class="form-linha">
        <input type="number" placeholder="Séries" />
        <input type="text" placeholder="Repetições (ex: 8-10)" />
      </div>
      <div class="form-linha">
        <input type="text" placeholder="Carga (opcional)" />
        <input type="text" placeholder="Observação (ex: DROP, FALHA)" />
      </div>

      <button type="button" class="btn btn--primario">
        + Adicionar Exercício
      </button>
    </div>

    <script src="js/script.js"></script>
  </body>
</html>
