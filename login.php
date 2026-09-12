<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Login</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <!-- HEADER -->
  <header class="header">
    <div class="container header__inner">
      <a href="index.html" class="header__logo">AC <span>Academia</span></a>
    </div>
  </header>
  <body>
    <div class="login-pagina">
      <div class="login-card">
        <h2 class="card-title">AC Academia</h2>
        <form action="#" method="post">
          <div class="mb-3">
            <label for="email" class="form-label"></label>
            <input
              type="email"
              class="form-control"
              id="email"
              name="email"
              required
              placeholder="CPF"
            />
          </div>
          <br />
          <div class="mb-3">
            <label for="senha" class="form-label"></label>
            <input
              type="password"
              class="form-control"
              id="senha"
              name="senha"
              required
              placeholder="Senha"
            />
          </div>
          <br />
          <a href="professor.html" class="btn btn--primario">Entrar</a>
          <!--SUBSTITUIR DEPOIS <button type="submit">Entrar</button>-->
        </form>
        <br />
        <a href="#" class="link-secundario">Esqueci minha senha</a>
      </div>
    </div>

    <script src="js/script.js"></script>
  </body>
</html>
