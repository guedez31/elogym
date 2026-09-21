<?php
$senha_teste = "123456"; // escolhe uma senha de teste simples
echo password_hash($senha_teste, PASSWORD_DEFAULT);