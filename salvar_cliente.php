<?php
// ============================================================
//  salvar_cliente.php — Recebe e grava os dados do formulário
//  Chamado via fetch() (AJAX) pelo index.php
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
    exit;
}

require_once __DIR__ . '/db.php';

// ── Coleta e sanitiza os dados ──────────────────────────────
$nome      = trim(filter_input(INPUT_POST, 'nome',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email     = trim(filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL)         ?? '');
$paisId    = (int) (filter_input(INPUT_POST, 'pais_id',   FILTER_SANITIZE_NUMBER_INT) ?? 0);
$telefone  = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$whatsapp  = filter_input(INPUT_POST, 'canal_whatsapp', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
$emailCan  = filter_input(INPUT_POST, 'canal_email',    FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

// ── Validações ──────────────────────────────────────────────
$erros = [];

if (mb_strlen($nome) < 2) {
    $erros[] = 'O nome deve ter pelo menos 2 caracteres.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'E-mail inválido.';
}
if ($paisId <= 0) {
    $erros[] = 'Selecione um país.';
}
// Remove tudo que não seja dígito para validar tamanho
$telefoneLimpo = preg_replace('/\D/', '', $telefone);
if (mb_strlen($telefoneLimpo) < 7 || mb_strlen($telefoneLimpo) > 15) {
    $erros[] = 'Número de telefone inválido.';
}
if (!$whatsapp && !$emailCan) {
    $erros[] = 'Selecione pelo menos um canal de contacto.';
}

if (!empty($erros)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'erros' => $erros]);
    exit;
}

// ── Busca o prefixo do país ─────────────────────────────────
try {
    $pdo = getConexao();

    $stmtPais = $pdo->prepare('SELECT prefixo FROM paises_prefixo WHERE id = ? AND ativo = 1');
    $stmtPais->execute([$paisId]);
    $pais = $stmtPais->fetch();

    if (!$pais) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'erros' => ['País não encontrado.']]);
        exit;
    }

    $telefoneFull = $pais['prefixo'] . $telefoneLimpo;

    // ── Insere (ignora duplicado de e-mail ou telefone) ─────
    $stmt = $pdo->prepare("
        INSERT INTO clientes_promocoes
            (nome, email, pais_id, telefone, telefone_full, canal_whatsapp, canal_email, ip)
        VALUES
            (:nome, :email, :pais_id, :telefone, :telefone_full, :whatsapp, :email_can, :ip)
        ON DUPLICATE KEY UPDATE
            nome           = VALUES(nome),
            canal_whatsapp = VALUES(canal_whatsapp),
            canal_email    = VALUES(canal_email),
            updated_at     = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        ':nome'          => $nome,
        ':email'         => $email,
        ':pais_id'       => $paisId,
        ':telefone'      => $telefoneLimpo,
        ':telefone_full' => $telefoneFull,
        ':whatsapp'      => $whatsapp,
        ':email_can'     => $emailCan,
        ':ip'            => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    echo json_encode(['ok' => true, 'mensagem' => 'Inscrição realizada com sucesso! Em breve receberá as nossas promoções. 🎉']);

} catch (PDOException $e) {
    // Log interno — não expõe detalhes ao utilizador
    error_log('[Sisi] Erro BD: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro interno. Tente novamente mais tarde.']);
}