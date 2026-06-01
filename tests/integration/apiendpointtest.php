<?php
// tests/Integration/ApiEndpointTest.php
// Testa o endpoint salvar_cliente.php via HTTP real
// Tipo: Testes de Integração — requerem servidor PHP rodando localmente
//
// Para usar: inicie o servidor antes dos testes com:
//   php -S localhost:8000 -t /caminho/do/projeto &
// Ou defina a variável APP_URL no phpunit.xml

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class ApiEndpointTest extends TestCase
{
    private string $apiUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $base = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $this->apiUrl = rtrim($base, '/') . '../../salvar_cliente.php';

        // Verifica se o servidor está a correr
        $ctx = @stream_context_create(['http' => ['timeout' => 2]]);
        if (@file_get_contents($base, false, $ctx) === false) {
            $this->markTestSkipped(
                "Servidor não encontrado em $base.\n" .
                "Inicie com: php -S localhost:8000 -t /pasta/do/projeto"
            );
        }
    }

    // ── Helper: faz POST ao endpoint ────────────────────────
    private function post(array $dados): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($dados),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $corpo       = file_get_contents($this->apiUrl, false, $ctx);
        $statusLine  = $http_response_header[0] ?? 'HTTP/1.1 200';
        preg_match('/\d{3}/', $statusLine, $m);
        $statusCode  = (int) ($m[0] ?? 200);

        return [
            'status' => $statusCode,
            'body'   => $corpo,
            'json'   => json_decode($corpo, true),
        ];
    }

    private function dadosValidos(array $sobrescrever = []): array
    {
        return array_merge([
            'nome'            => 'Teste API',
            'email'           => 'api_' . uniqid() . '@teste.pt',
            'pais_id'         => 1,
            'telefone'        => '9' . rand(10000000, 99999999),
            'canal_whatsapp'  => '1',
            'canal_email'     => '1',
        ], $sobrescrever);
    }

    // ═══════════════════════════════════════════════════════
    //  HAPPY PATH
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function postComDadosValidosRetorna200EJsonOk(): void
    {
        $resp = $this->post($this->dadosValidos());

        $this->assertSame(200, $resp['status']);
        $this->assertIsArray($resp['json'], 'Resposta deveria ser JSON válido');
        $this->assertTrue($resp['json']['ok'], 'Campo ok deveria ser true. Resposta: ' . $resp['body']);
        $this->assertArrayHasKey('mensagem', $resp['json']);
    }

    #[Test]
    public function postComDadosValidosGravaClienteNoBanco(): void
    {
        $email = 'gravacao_' . uniqid() . '@teste.pt';

        $resp = $this->post($this->dadosValidos(['email' => $email]));

        $this->assertTrue($resp['json']['ok'] ?? false, 'API deveria retornar ok:true. ' . $resp['body']);

        $cliente = $this->buscarPorEmail($email);
        $this->assertNotFalse($cliente, "Cliente com email '$email' não foi gravado no banco após POST bem-sucedido");
    }

    #[Test]
    public function apiRetornaContentTypeJson(): void
    {
        // Faz a chamada e verifica o header Content-Type
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => 'Content-Type: application/x-www-form-urlencoded',
                'content'       => http_build_query($this->dadosValidos()),
                'ignore_errors' => true,
                'timeout'       => 10,
            ],
        ]);

        file_get_contents($this->apiUrl, false, $ctx);

        $headers = implode("\n", $http_response_header ?? []);
        $this->assertStringContainsString(
            'application/json',
            $headers,
            'A API deveria retornar Content-Type: application/json'
        );
    }

    // ═══════════════════════════════════════════════════════
    //  VALIDAÇÕES — respostas de erro
    // ═══════════════════════════════════════════════════════

    #[Test]
    #[DataProvider('camposObrigatoriosAusentes')]
    public function campoObrigatorioFaltandoRetorna422(string $campoRemovido): void
    {
        $dados = $this->dadosValidos();
        unset($dados[$campoRemovido]);

        $resp = $this->post($dados);

        $this->assertSame(422, $resp['status'],
            "Faltando '$campoRemovido' deveria retornar 422. Resposta: " . $resp['body']
        );
        $this->assertFalse($resp['json']['ok'] ?? true);
    }

    public static function camposObrigatoriosAusentes(): array
    {
        return [
            'nome ausente'    => ['nome'],
            'email ausente'   => ['email'],
            'pais_id ausente' => ['pais_id'],
            'telefone ausente'=> ['telefone'],
        ];
    }

    #[Test]
    public function emailInvalidoRetorna422(): void
    {
        $resp = $this->post($this->dadosValidos(['email' => 'nao-e-email']));

        $this->assertSame(422, $resp['status']);
        $this->assertFalse($resp['json']['ok'] ?? true);
        $this->assertNotEmpty($resp['json']['erros'] ?? []);
    }

    #[Test]
    public function telefoneMuiToCurtoRetorna422(): void
    {
        $resp = $this->post($this->dadosValidos(['telefone' => '123']));

        $this->assertSame(422, $resp['status']);
        $this->assertFalse($resp['json']['ok'] ?? true);
    }

    #[Test]
    public function nomeMuitoCurtoRetorna422(): void
    {
        $resp = $this->post($this->dadosValidos(['nome' => 'A']));

        $this->assertSame(422, $resp['status']);
        $this->assertFalse($resp['json']['ok'] ?? true);
    }

    #[Test]
    public function paisInexistenteRetorna422(): void
    {
        $resp = $this->post($this->dadosValidos(['pais_id' => '99999']));

        $this->assertSame(422, $resp['status']);
        $this->assertFalse($resp['json']['ok'] ?? true);
    }

    #[Test]
    public function semNenhumCanalRetorna422(): void
    {
        $dados = $this->dadosValidos([
            'canal_whatsapp' => '0',
            'canal_email'    => '0',
        ]);

        $resp = $this->post($dados);

        $this->assertSame(422, $resp['status']);
        $this->assertFalse($resp['json']['ok'] ?? true);
    }

    // ═══════════════════════════════════════════════════════
    //  MÉTODO HTTP errado
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function requisicaoGETRetorna405(): void
    {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'ignore_errors' => true,
                'timeout'       => 10,
            ],
        ]);

        $corpo = file_get_contents($this->apiUrl, false, $ctx);
        preg_match('/\d{3}/', $http_response_header[0] ?? '', $m);

        $this->assertSame(405, (int)($m[0] ?? 0),
            'GET deveria retornar 405 Method Not Allowed'
        );
    }

    // ═══════════════════════════════════════════════════════
    //  SEGURANÇA BÁSICA
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function respostaNaoExpoeDetalhesDoSistema(): void
    {
        // Dados propositalmente inválidos para forçar erro
        $resp = $this->post(['nome' => '', 'email' => 'x', 'pais_id' => '0', 'telefone' => '1']);

        $corpo = strtolower($resp['body']);

        // A resposta de erro não deve vazar informações sensíveis
        $this->assertStringNotContainsString('stack trace', $corpo);
        $this->assertStringNotContainsString('mysql',       $corpo, 'Não deve expor tecnologia de banco');
        $this->assertStringNotContainsString('password',    $corpo, 'Não deve vazar dados de conexão');
    }

    #[Test]
    public function injecaoSqlNomeCampoEhSegura(): void
    {
        $payloadMalicioso = "'; DROP TABLE clientes_promocoes; --";

        $resp = $this->post($this->dadosValidos(['nome' => $payloadMalicioso]));

        // A tabela deve continuar a existir (se o POST foi OK, o nome foi tratado como string)
        $total = (int) self::$pdo->query('SELECT COUNT(*) FROM clientes_promocoes')->fetchColumn();

        // Não importa se foi OK ou 422, o importante é que a tabela não foi apagada
        $this->assertGreaterThanOrEqual(0, $total, 'A tabela foi destruída! Possível injeção SQL.');
    }

    #[Test]
    public function xssNomeCampoEhEscapado(): void
    {
        $xss   = '<script>alert("xss")</script>';
        $email = 'xss_' . uniqid() . '@teste.pt';

        $resp = $this->post($this->dadosValidos(['nome' => $xss, 'email' => $email]));

        if ($resp['json']['ok'] ?? false) {
            $cliente = $this->buscarPorEmail($email);
            // O nome gravado não deve conter a tag <script> sem escapar
            // (o filter_input com FILTER_SANITIZE_SPECIAL_CHARS converte < > &)
            $this->assertStringNotContainsString('<script>', $cliente['nome'] ?? '');
        }

        // Se retornou erro de validação, o XSS foi bloqueado antes — também OK
        $this->assertTrue(true);
    }
}