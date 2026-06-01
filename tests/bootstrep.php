<?php
// tests/bootstrap.php
// Carregado automaticamente pelo PHPUnit antes de qualquer teste.
//
// ESTRUTURA ESPERADA:
//   /raiz-do-projeto/
//   ├── .env                  ← as suas credenciais (no .gitignore)
//   ├── db.php                ← lê o .env e cria a conexão PDO
//   ├── salvar_cliente.php
//   ├── vendor/               ← criado pelo "composer install"
//   │   └── autoload.php      ← gerado pelo Composer, NÃO tem senha nenhuma
//   └── tests/
//       └── bootstrap.php     ← este arquivo

declare(strict_types=1);

// Garante que erros aparecem durante os testes
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define a raiz do projeto (um nível acima de /tests)
define('PROJECT_ROOT', dirname(__DIR__));

// ── vendor/autoload.php ──────────────────────────────────────
// Este arquivo é gerado pelo Composer ao rodar "composer install".
// NÃO contém senhas — apenas registra as classes do PHPUnit
// para que o PHP saiba onde encontrá-las automaticamente.
// Se ainda não rodou "composer install", faça isso primeiro.
$autoload = PROJECT_ROOT . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die(
        "\n[ERRO] vendor/autoload.php não encontrado.\n" .
        "Execute na raiz do projeto:\n\n  composer install\n\n"
    );
}
require_once $autoload;

// ── db.php ───────────────────────────────────────────────────
// O db.php é quem lê o .env com as credenciais do banco.
// Os testes usam as variáveis definidas no phpunit.xml,
// que sobrepõem o .env e apontam para o banco de TESTE
// (delicias_sisi_test), não o banco de produção.
require_once PROJECT_ROOT . '/db.php';