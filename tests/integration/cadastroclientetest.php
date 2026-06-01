<?php
// tests/Integration/CadastroClienteTest.php
// Testa o fluxo completo: POST → validação → banco de dados
// Tipo: Testes de Integração — usam banco de dados real (ambiente de teste)

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;

class CadastroClienteTest extends TestCase
{
    // ═══════════════════════════════════════════════════════
    //  HAPPY PATH — cadastro bem-sucedido
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function cadastroComDadosValidosGravaNoDb(): void
    {
        $id = $this->inserirCliente([
            'nome'          => 'Maria Silva',
            'email'         => 'maria@exemplo.pt',
            'telefone'      => '912345678',
            'telefone_full' => '+351912345678',
            'canal_whatsapp'=> 1,
            'canal_email'   => 1,
        ]);

        $this->assertGreaterThan(0, $id, 'O INSERT não retornou um ID válido');
        $this->assertSame(1, $this->contarClientes(), 'Deveria haver exatamente 1 cliente');
    }

    #[Test]
    public function clienteCadastradoTemDadosCorretos(): void
    {
        $this->inserirCliente([
            'nome'          => 'João Santos',
            'email'         => 'joao@exemplo.pt',
            'pais_id'       => 2,
            'telefone'      => '11912345678',
            'telefone_full' => '+5511912345678',
            'canal_whatsapp'=> 1,
            'canal_email'   => 0,
        ]);

        $cliente = $this->buscarPorEmail('joao@exemplo.pt');

        $this->assertNotFalse($cliente, 'Cliente não encontrado no banco após INSERT');
        $this->assertSame('João Santos',      $cliente['nome']);
        $this->assertSame('joao@exemplo.pt',  $cliente['email']);
        $this->assertSame('11912345678',      $cliente['telefone']);
        $this->assertSame('+5511912345678',   $cliente['telefone_full']);
        $this->assertEquals(1, $cliente['canal_whatsapp']);
        $this->assertEquals(0, $cliente['canal_email']);
        $this->assertEquals(1, $cliente['ativo']);
    }

    #[Test]
    public function cadastroSalvaDataDeInsercaoAutomaticamente(): void
    {
        $this->inserirCliente();

        $cliente = $this->buscarPorEmail('maria@teste.pt');

        $this->assertNotNull($cliente['created_at'], 'created_at deveria ser preenchido automaticamente');
        $this->assertNotNull($cliente['updated_at'], 'updated_at deveria ser preenchido automaticamente');
    }

    #[Test]
    public function cadastroSalvaIpDoCliente(): void
    {
        $this->inserirCliente(['ip' => '192.168.1.100']);

        $cliente = $this->buscarPorEmail('maria@teste.pt');

        $this->assertSame('192.168.1.100', $cliente['ip']);
    }

    // ═══════════════════════════════════════════════════════
    //  UNICIDADE — email e telefone não podem duplicar
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function emailDuplicadoCausaExcecao(): void
    {
        $this->inserirCliente(['email' => 'duplicado@email.pt']);

        $this->expectException(\PDOException::class);

        // Tenta inserir o mesmo e-mail novamente (deve falhar com UNIQUE KEY)
        $this->inserirCliente([
            'email'         => 'duplicado@email.pt',
            'telefone'      => '999999999',
            'telefone_full' => '+351999999999',
        ]);
    }

    #[Test]
    public function telefoneDuplicadoCausaExcecao(): void
    {
        $this->inserirCliente([
            'email'         => 'primeiro@email.pt',
            'telefone_full' => '+351911111111',
        ]);

        $this->expectException(\PDOException::class);

        $this->inserirCliente([
            'email'         => 'segundo@email.pt',    // e-mail diferente
            'telefone_full' => '+351911111111',        // telefone igual → UNIQUE KEY
        ]);
    }

    #[Test]
    public function upsertAtualizaDadosDeClienteExistente(): void
    {
        // Insere o cliente inicialmente
        $this->inserirCliente([
            'email'          => 'upsert@email.pt',
            'canal_whatsapp' => 1,
            'canal_email'    => 0,
        ]);

        // Simula ON DUPLICATE KEY UPDATE (como o salvar_cliente.php faz)
        $stmt = self::$pdo->prepare("
            INSERT INTO clientes_promocoes
                (nome, email, pais_id, telefone, telefone_full, canal_whatsapp, canal_email, ip)
            VALUES
                ('Nome Atualizado', 'upsert@email.pt', 1, '912345678', '+351912345678', 0, 1, '127.0.0.1')
            ON DUPLICATE KEY UPDATE
                nome           = VALUES(nome),
                canal_whatsapp = VALUES(canal_whatsapp),
                canal_email    = VALUES(canal_email),
                updated_at     = CURRENT_TIMESTAMP
        ");
        $stmt->execute();

        // Deve continuar com 1 cliente (não duplicou)
        $this->assertSame(1, $this->contarClientes(), 'ON DUPLICATE KEY UPDATE não deveria inserir novo registo');

        $cliente = $this->buscarPorEmail('upsert@email.pt');
        $this->assertSame('Nome Atualizado', $cliente['nome']);
        $this->assertEquals(0, $cliente['canal_whatsapp']);
        $this->assertEquals(1, $cliente['canal_email']);
    }

    // ═══════════════════════════════════════════════════════
    //  MÚLTIPLOS REGISTOS
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function multiplasInscricoesComEmailsDiferentesGravam(): void
    {
        $clientes = [
            ['nome' => 'Ana',    'email' => 'ana@email.pt',    'telefone' => '910000001', 'telefone_full' => '+351910000001'],
            ['nome' => 'Bruno',  'email' => 'bruno@email.pt',  'telefone' => '910000002', 'telefone_full' => '+351910000002'],
            ['nome' => 'Carla',  'email' => 'carla@email.pt',  'telefone' => '910000003', 'telefone_full' => '+351910000003'],
            ['nome' => 'Daniel', 'email' => 'daniel@email.pt', 'telefone' => '910000004', 'telefone_full' => '+351910000004'],
            ['nome' => 'Eva',    'email' => 'eva@email.pt',    'telefone' => '910000005', 'telefone_full' => '+351910000005'],
        ];

        foreach ($clientes as $c) {
            $this->inserirCliente($c);
        }

        $this->assertSame(5, $this->contarClientes(), 'Todos os 5 clientes deveriam estar no banco');
    }

    // ═══════════════════════════════════════════════════════
    //  CHAVE ESTRANGEIRA — país
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function paisInexistenteNaoPermiteInsercao(): void
    {
        $this->expectException(\PDOException::class);

        $this->inserirCliente(['pais_id' => 99999]); // ID inexistente
    }

    #[Test]
    public function paisPortugalPermiteInsercao(): void
    {
        $stmt = self::$pdo->prepare("SELECT id FROM paises_prefixo WHERE sigla = 'PT'");
        $stmt->execute();
        $pais = $stmt->fetch();

        $this->assertNotFalse($pais, 'Portugal deve existir na tabela paises_prefixo');

        $id = $this->inserirCliente(['pais_id' => $pais['id']]);
        $this->assertGreaterThan(0, $id);
    }

    #[Test]
    public function paisBrasilPermiteInsercao(): void
    {
        $stmt = self::$pdo->prepare("SELECT id FROM paises_prefixo WHERE sigla = 'BR'");
        $stmt->execute();
        $pais = $stmt->fetch();

        $this->assertNotFalse($pais, 'Brasil deve existir na tabela paises_prefixo');

        $id = $this->inserirCliente([
            'pais_id'       => $pais['id'],
            'email'         => 'brasileiro@email.com.br',
            'telefone'      => '11912345678',
            'telefone_full' => '+5511912345678',
        ]);

        $this->assertGreaterThan(0, $id);
    }

    // ═══════════════════════════════════════════════════════
    //  SANIDADE — estado inicial
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function bancoComeceVazioAntesDeCadaTeste(): void
    {
        // setUp() chama DELETE antes de cada teste
        $this->assertSame(
            0,
            $this->contarClientes(),
            'A tabela deveria estar vazia no início de cada teste'
        );
    }
}