<?php
// Encerra o login: apaga os dados da sessão (usuario_id, nome, tipo) e
// manda o usuário de volta pra tela de login.
session_start();
session_destroy();
header('Location: login.php');
exit;
?>