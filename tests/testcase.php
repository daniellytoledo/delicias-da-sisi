<?php
// tests/TestCase.php — Classe base para todos os testes

declare(strict_types=1);

namespace Tests;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected static PDO $pdo;

    // ── Conexão compartilhada ────────────────────────────────
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $nome = $_ENV['DB_NAME'] ?? 'delicias_sisi_test';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';

        $dsn = "mysql:host=$host;port=$port;dbname=$nome;charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            self::markTestSkipped(
                "Banco de teste indisponível: {$e->getMessage()}\n" .
                "Configure o banco 'delicias_sisi_test' e as variáveis do phpunit.xml."
            );
        }
    }

    // ── Limpa as tabelas antes de cada teste ─────────────────
    protected function setUp(): void
    {
        parent::setUp();
        if (isset(self::$pdo)) {
            self::$pdo->exec('DELETE FROM clientes_promocoes');
            // Reseta o auto-increment para resultados previsíveis
            self::$pdo->exec('ALTER TABLE clientes_promocoes AUTO_INCREMENT = 1');
        }
    }

    // ── Helper: insere um cliente diretamente no BD ──────────
    protected function inserirCliente(array $dados = []): int
    {
        $defaults = [
            'nome'          => 'Maria Teste',
            'email'         => 'maria@teste.pt',
            'pais_id'       => 1,
            'telefone'      => '912345678',
            'telefone_full' => '+351912345678',
            'canal_whatsapp'=> 1,
            'canal_email'   => 1,
            'ip'            => '127.0.0.1',
        ];
        $d = array_merge($defaults, $dados);

        $stmt = self::$pdo->prepare("
            INSERT INTO clientes_promocoes
                (nome, email, pais_id, telefone, telefone_full, canal_whatsapp, canal_email, ip)
            VALUES
                (:nome, :email, :pais_id, :telefone, :telefone_full, :whatsapp, :email_can, :ip)
        ");
        $stmt->execute([
            ':nome'          => $d['nome'],
            ':email'         => $d['email'],
            ':pais_id'       => $d['pais_id'],
            ':telefone'      => $d['telefone'],
            ':telefone_full' => $d['telefone_full'],
            ':whatsapp'      => $d['canal_whatsapp'],
            ':email_can'     => $d['canal_email'],
            ':ip'            => $d['ip'],
        ]);

        return (int) self::$pdo->lastInsertId();
    }

    // ── Helper: conta registos na tabela ────────────────────
    protected function contarClientes(): int
    {
        return (int) self::$pdo->query('SELECT COUNT(*) FROM clientes_promocoes')->fetchColumn();
    }

    // ── Helper: busca cliente por e-mail ────────────────────
    protected function buscarPorEmail(string $email): array|false
    {
        $stmt = self::$pdo->prepare('SELECT * FROM clientes_promocoes WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}