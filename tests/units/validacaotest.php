<?php
// tests/Unit/ValidacaoTest.php
// Testa as regras de validação de entrada de dados
// Tipo: Testes Unitários — isolados, sem banco de dados

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Replica as regras de validação do salvar_cliente.php
 * para testá-las de forma isolada (sem banco, sem HTTP).
 */
class ValidacaoTest extends TestCase
{
    // ═══════════════════════════════════════════════════════
    //  Lógica de validação extraída do salvar_cliente.php
    // ═══════════════════════════════════════════════════════

    private function validarNome(string $nome): bool
    {
        return mb_strlen(trim($nome)) >= 2;
    }

    private function validarEmail(string $email): bool
    {
        return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    private function validarTelefone(string $tel): bool
    {
        $limpo = preg_replace('/\D/', '', $tel);
        return mb_strlen($limpo) >= 7 && mb_strlen($limpo) <= 15;
    }

    private function validarCanais(bool $whatsapp, bool $email): bool
    {
        return $whatsapp || $email;
    }

    private function limparTelefone(string $tel): string
    {
        return preg_replace('/\D/', '', $tel);
    }

    private function montarTelefoneFull(string $prefixo, string $telLimpo): string
    {
        return $prefixo . $telLimpo;
    }

    // ═══════════════════════════════════════════════════════
    //  TESTES DE NOME
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function nomeValidoComDuasLetrasOuMais(): void
    {
        $this->assertTrue($this->validarNome('Jo'));
        $this->assertTrue($this->validarNome('Maria Silva'));
        $this->assertTrue($this->validarNome('Ana'));
    }

    #[Test]
    public function nomeVazioEInvalido(): void
    {
        $this->assertFalse($this->validarNome(''));
        $this->assertFalse($this->validarNome('   '));
    }

    #[Test]
    public function nomeComUmaLetraEInvalido(): void
    {
        $this->assertFalse($this->validarNome('A'));
        $this->assertFalse($this->validarNome(' A '));
    }

    #[Test]
    public function nomeComCaracteresEspeciaisPortuguesEValido(): void
    {
        $this->assertTrue($this->validarNome('José António'));
        $this->assertTrue($this->validarNome('Ângela'));
        $this->assertTrue($this->validarNome('François'));
    }

    // ═══════════════════════════════════════════════════════
    //  TESTES DE E-MAIL
    // ═══════════════════════════════════════════════════════

    #[Test]
    #[DataProvider('emailsValidos')]
    public function emailsValidosSaoAceites(string $email): void
    {
        $this->assertTrue($this->validarEmail($email), "Email '$email' deveria ser válido");
    }

    public static function emailsValidos(): array
    {
        return [
            'email simples'           => ['usuario@dominio.com'],
            'email PT'                => ['joao@exemplo.pt'],
            'email com subdomínio'    => ['a@b.co.uk'],
            'email com números'       => ['user123@gmail.com'],
            'email com ponto no nome' => ['joao.silva@empresa.com.br'],
            'email com hífen'         => ['ana-paula@email.org'],
        ];
    }

    #[Test]
    #[DataProvider('emailsInvalidos')]
    public function emailsInvalidosSaoRejeitados(string $email): void
    {
        $this->assertFalse($this->validarEmail($email), "Email '$email' deveria ser inválido");
    }

    public static function emailsInvalidos(): array
    {
        return [
            'sem @'            => ['usuariodominio.com'],
            'sem domínio'      => ['usuario@'],
            'sem usuário'      => ['@dominio.com'],
            'sem TLD'          => ['usuario@dominio'],
            'vazio'            => [''],
            'com espaço'       => ['user name@email.com'],
            'duplo @'          => ['a@@b.com'],
        ];
    }

    // ═══════════════════════════════════════════════════════
    //  TESTES DE TELEFONE
    // ═══════════════════════════════════════════════════════

    #[Test]
    #[DataProvider('telefonesValidos')]
    public function telefonesValidosSaoAceites(string $tel): void
    {
        $this->assertTrue($this->validarTelefone($tel), "Telefone '$tel' deveria ser válido");
    }

    public static function telefonesValidos(): array
    {
        return [
            'PT simples'          => ['924272532'],
            'PT com espaços'      => ['924 272 532'],
            'BR com formatação'   => ['(11) 91234-5678'],
            'número mínimo 7 dig' => ['1234567'],
            'número máximo 15'    => ['123456789012345'],
            'com hífens'          => ['91234-5678'],
        ];
    }

    #[Test]
    #[DataProvider('telefonesInvalidos')]
    public function telefonesInvalidosSaoRejeitados(string $tel): void
    {
        $this->assertFalse($this->validarTelefone($tel), "Telefone '$tel' deveria ser inválido");
    }

    public static function telefonesInvalidos(): array
    {
        return [
            'muito curto (6 dígitos)' => ['123456'],
            'vazio'                   => [''],
            'só letras'               => ['abcdef'],
            'muito longo (16 dígitos)'=> ['1234567890123456'],
        ];
    }

    #[Test]
    public function limpezaDeTelefoneRemoveNaoDigitos(): void
    {
        $this->assertSame('924272532', $this->limparTelefone('924 272 532'));
        $this->assertSame('11912345678', $this->limparTelefone('(11) 91234-5678'));
        $this->assertSame('912345678', $this->limparTelefone('+351 912 345 678'));
    }

    #[Test]
    public function montarTelefoneFullConcatenaCorretamente(): void
    {
        $this->assertSame('+351924272532', $this->montarTelefoneFull('+351', '924272532'));
        $this->assertSame('+5511912345678', $this->montarTelefoneFull('+55', '11912345678'));
    }

    // ═══════════════════════════════════════════════════════
    //  TESTES DE CANAIS
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function pelomenosUmCanalDeveEstarSelecionado(): void
    {
        $this->assertTrue($this->validarCanais(true, false));
        $this->assertTrue($this->validarCanais(false, true));
        $this->assertTrue($this->validarCanais(true, true));
    }

    #[Test]
    public function nenhumCanalSelecionadoEInvalido(): void
    {
        $this->assertFalse($this->validarCanais(false, false));
    }

    // ═══════════════════════════════════════════════════════
    //  TESTE DE REGRA COMPLETA (todos os campos juntos)
    // ═══════════════════════════════════════════════════════

    #[Test]
    public function formularioComTodosOsCamposValidosEhValido(): void
    {
        $nome     = 'Maria Silva';
        $email    = 'maria@exemplo.pt';
        $telefone = '924272532';
        $paisId   = 1;

        $this->assertTrue($this->validarNome($nome));
        $this->assertTrue($this->validarEmail($email));
        $this->assertTrue($this->validarTelefone($telefone));
        $this->assertGreaterThan(0, $paisId);
        $this->assertTrue($this->validarCanais(true, true));
    }

    #[Test]
    public function formularioComEmailInvalidoFalhaAValidacao(): void
    {
        $this->assertFalse($this->validarEmail('nao-e-um-email'));
    }

    #[Test]
    public function formularioComTelefoneVazioFalhaAValidacao(): void
    {
        $this->assertFalse($this->validarTelefone(''));
    }
}