<?php
// tests/Unit/ConexaoBancoDadosTest.php
// Testa o módulo db.php isoladamente
// Tipo: Testes Unitários

declare(strict_types=1);

namespace Tests\Unit;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConexaoBancoDadosTest extends TestCase
{
    private string $envPath;

    protected function setUp(): void
    {
        $this->envPath = PROJECT_ROOT . '../../.env';
    }

    // ── Testa existência do .env ─────────────────────────────

    #[Test]
    public function arquivoEnvExiste(): void
    {
        $this->assertFileExists(
            $this->envPath,
            'O arquivo .env não foi encontrado. Crie-o com as credenciais do banco.'
        );
    }

    #[Test]
    public function arquivoEnvTemAsChavesNecessarias(): void
    {
        if (!file_exists($this->envPath)) {
            $this->markTestSkipped('.env não encontrado.');
        }

        $conteudo = file_get_contents($this->envPath);

        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'] as $chave) {
            $this->assertStringContainsString(
                $chave,
                $conteudo,
                "A chave '$chave' está faltando no arquivo .env"
            );
        }
    }

    #[Test]
    public function arquivoEnvNaoEstaNoGit(): void
    {
        $gitignorePath = PROJECT_ROOT . '../../.gitignore';

        if (!file_exists($gitignorePath)) {
            $this->markTestSkipped('.gitignore não encontrado.');
        }

        $conteudo = file_get_contents($gitignorePath);

        $this->assertStringContainsString(
            '.env',
            $conteudo,
            'PERIGO: o .env não está listado no .gitignore! As credenciais podem vazar no Git.'
        );
    }

    // ── Testa a função getConexao() ──────────────────────────

    #[Test]
    public function getConexaoRetornaInstanciaPDO(): void
    {
        // Carrega db.php com as variáveis de teste já definidas no bootstrap
        require_once PROJECT_ROOT . '../../db.php';

        $pdo = getConexao();

        $this->assertInstanceOf(
            PDO::class,
            $pdo,
            'getConexao() deveria retornar uma instância de PDO'
        );
    }

    #[Test]
    public function getConexaoRetornaAMesmaInstancia(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo1 = getConexao();
        $pdo2 = getConexao();

        $this->assertSame(
            $pdo1,
            $pdo2,
            'getConexao() deveria retornar sempre a mesma instância (singleton/static)'
        );
    }

    #[Test]
    public function conexaoUsaCharsetUtf8mb4(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo     = getConexao();
        $charset = $pdo->query("SELECT @@character_set_client")->fetchColumn();

        $this->assertSame(
            'utf8mb4',
            $charset,
            'A conexão deveria usar charset utf8mb4 para suportar emojis e caracteres especiais'
        );
    }

    #[Test]
    public function conexaoComCredenciaisErradasLancaExcecao(): void
    {
        $this->expectException(PDOException::class);

        new PDO(
            'mysql:host=localhost;dbname=banco_inexistente',
            'usuario_errado',
            'senha_errada',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    // ── Testa estrutura do banco ─────────────────────────────

    #[Test]
    public function tabelaClientesExiste(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo = getConexao();

        $tabelas = $pdo->query("SHOW TABLES LIKE 'clientes_promocoes'")->fetchAll();

        $this->assertNotEmpty(
            $tabelas,
            "A tabela 'clientes_promocoes' não existe no banco. Execute o setup_db.sql."
        );
    }

    #[Test]
    public function tabelaPaisesExiste(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo    = getConexao();
        $result = $pdo->query("SHOW TABLES LIKE 'paises_prefixo'")->fetchAll();

        $this->assertNotEmpty(
            $result,
            "A tabela 'paises_prefixo' não existe. Execute o setup_db.sql."
        );
    }

    #[Test]
    public function tabelaPaisesTEMRegistos(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo   = getConexao();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM paises_prefixo')->fetchColumn();

        $this->assertGreaterThan(
            0,
            $total,
            'A tabela paises_prefixo está vazia. Execute o setup_db.sql com os INSERTs.'
        );
    }

    #[Test]
    public function paisPortugalEstaNosBanco(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo  = getConexao();
        $stmt = $pdo->prepare("SELECT * FROM paises_prefixo WHERE sigla = 'PT'");
        $stmt->execute();
        $pais = $stmt->fetch();

        $this->assertNotFalse($pais, "Portugal não encontrado na tabela paises_prefixo");
        $this->assertSame('+351', $pais['prefixo']);
    }

    #[Test]
    public function paisBrasilEstaNosBanco(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo  = getConexao();
        $stmt = $pdo->prepare("SELECT * FROM paises_prefixo WHERE sigla = 'BR'");
        $stmt->execute();
        $pais = $stmt->fetch();

        $this->assertNotFalse($pais, "Brasil não encontrado na tabela paises_prefixo");
        $this->assertSame('+55', $pais['prefixo']);
    }

    // ── Testa colunas da tabela clientes ────────────────────

    #[Test]
    public function tabelaClientesTemTodasAsColunasNecessarias(): void
    {
        require_once PROJECT_ROOT . '../../db.php';

        $pdo     = getConexao();
        $colunas = $pdo->query('DESCRIBE clientes_promocoes')->fetchAll(PDO::FETCH_COLUMN);

        $esperadas = [
            'id', 'nome', 'email', 'pais_id',
            'telefone', 'telefone_full',
            'canal_whatsapp', 'canal_email',
            'ativo', 'ip', 'created_at', 'updated_at',
        ];

        foreach ($esperadas as $col) {
            $this->assertContains(
                $col,
                $colunas,
                "A coluna '$col' não existe na tabela clientes_promocoes"
            );
        }
    }
}