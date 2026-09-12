<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Troca de Senha</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <div class="login-pagina">
      <div class="login-card">
        <h2 class="card-title">AC Academia</h2>
        <p>
          Primeira vez acessando o sistema? Crie uma nova senha para continuar.
        </p>
        <br />
        <form action="#" method="post">
          <div>
            <label for="email" class="form-label"></label>
            <input
              type="password"
              class="form-control"
              id="email"
              name="email"
              required
              placeholder="Nova Senha"
            />
          </div>
          <br />
          <div>
            <label for="senha" class="form-label"></label>
            <input
              type="password"
              class="form-control"
              id="senha"
              name="senha"
              required
              placeholder="Confirmar Nova Senha"
            />
          </div>
          <br />
          <a href="meu-treino.php" class="btn btn--primario"
            >Confirmar Nova Senha</a
          >
          <!--SUBSTITUIR DEPOIS <button type="submit">Entrar</button>-->
        </form>
        <br />
      </div>
    </div>

    <script src="js/script.js"></script>
  </body>
</html>
