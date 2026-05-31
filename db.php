<?php
// ============================================================
//  db.php — Conexão com o banco de dados
//  Este arquivo lê as credenciais do .env (que está no .gitignore)
// ============================================================

function carregarEnv(string $caminho): void {
    if (!file_exists($caminho)) {
        throw new RuntimeException("Arquivo .env não encontrado em: $caminho");
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        // ignora comentários
        if ($linha === '' || str_starts_with($linha, '#')) continue;

        [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
        if (!array_key_exists($chave, $_ENV)) {
            $_ENV[$chave]    = $valor;
            putenv("$chave=$valor");
        }
    }
}

// Carrega o .env (ajuste o caminho se necessário)
carregarEnv(__DIR__ . '/.env');

function getConexao(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $nome = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';

        $dsn = "mysql:host=$host;port=$port;dbname=$nome;charset=utf8mb4";

        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $opcoes);
    }

    return $pdo;
}