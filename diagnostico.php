<?php
// diagnostico.php
// ============================================================
//  DIAGNÓSTICO — identifica por que o cadastro não está gravando
//  Acesse este ficheiro no browser: http://seusite.com/diagnostico.php
//  APAGUE este ficheiro após resolver o problema!
// ============================================================

// Segurança mínima: só roda localmente ou com a chave certa
$chave = $_GET['chave'] ?? '';
if ($chave !== 'sisi2025diag') {
    http_response_code(403);
    die('Acesso negado. Adicione ?chave=sisi2025diag na URL.');
}

$ok    = '✅';
$erro  = '❌';
$aviso = '⚠️';

$resultados = [];

function checar(string $titulo, callable $fn): array {
    try {
        $resultado = $fn();
        return ['titulo' => $titulo, 'status' => 'ok', 'msg' => $resultado];
    } catch (Throwable $e) {
        return ['titulo' => $titulo, 'status' => 'erro', 'msg' => $e->getMessage()];
    }
}

// ── 1. PHP e extensões ──────────────────────────────────────
$resultados[] = checar('Versão do PHP (mínimo 8.1)', function() {
    $v = phpversion();
    if (version_compare($v, '8.1', '<')) throw new Exception("PHP $v está abaixo do mínimo 8.1");
    return "PHP $v ✓";
});

$resultados[] = checar('Extensão PDO instalada', function() {
    if (!extension_loaded('pdo')) throw new Exception('PDO não está disponível');
    return 'PDO disponível ✓';
});

$resultados[] = checar('Extensão PDO_MySQL instalada', function() {
    if (!extension_loaded('pdo_mysql')) throw new Exception('pdo_mysql não está disponível. Habilite no php.ini');
    return 'pdo_mysql disponível ✓';
});

$resultados[] = checar('Extensão mbstring instalada', function() {
    if (!extension_loaded('mbstring')) throw new Exception('mbstring não está disponível');
    return 'mbstring disponível ✓';
});

// ── 2. Ficheiro .env ────────────────────────────────────────
$envPath = __DIR__ . '/.env';
$resultados[] = checar('Arquivo .env existe', function() use ($envPath) {
    if (!file_exists($envPath)) throw new Exception(".env não encontrado em $envPath");
    return ".env encontrado em $envPath ✓";
});

$resultados[] = checar('Arquivo .env é legível', function() use ($envPath) {
    if (!is_readable($envPath)) throw new Exception(".env existe mas não pode ser lido. Verifique permissões (chmod 644)");
    return '.env legível ✓';
});

// ── 3. Variáveis do .env ────────────────────────────────────
if (file_exists($envPath)) {
    $linhas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#')) continue;
        [$chaveEnv, $valor] = array_map('trim', explode('=', $linha, 2));
        $_ENV[$chaveEnv] = $valor;
    }

    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'] as $k) {
        $resultados[] = checar("Variável $k no .env", function() use ($k) {
            if (empty($_ENV[$k]) && $_ENV[$k] !== '0') {
                throw new Exception("$k está vazia ou não definida no .env");
            }
            // Não exibe a senha
            $val = ($k === 'DB_PASS') ? str_repeat('*', strlen($_ENV[$k])) : $_ENV[$k];
            return "$k = \"$val\" ✓";
        });
    }
}

// ── 4. Conexão com o banco ──────────────────────────────────
$resultados[] = checar('Conectar ao MySQL', function() {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $nome = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';

    if (empty($nome)) throw new Exception('DB_NAME está vazia no .env');
    if (empty($user)) throw new Exception('DB_USER está vazia no .env');

    $dsn = "mysql:host=$host;port=$port;dbname=$nome;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return "Conexão estabelecida com '{$nome}' em {$host}:{$port} ✓";
});

// ── 5. Tabelas ──────────────────────────────────────────────
$resultados[] = checar("Tabela 'paises_prefixo' existe", function() {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $nome = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $pdo  = new PDO("mysql:host=$host;port=$port;dbname=$nome;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $t = $pdo->query("SHOW TABLES LIKE 'paises_prefixo'")->fetch();
    if (!$t) throw new Exception("Tabela 'paises_prefixo' não existe. Execute o setup_db.sql.");

    $total = $pdo->query('SELECT COUNT(*) FROM paises_prefixo')->fetchColumn();
    if ($total == 0) throw new Exception("Tabela 'paises_prefixo' existe mas está vazia! Execute os INSERTs do setup_db.sql.");

    return "paises_prefixo existe com $total país(es) ✓";
});

$resultados[] = checar("Tabela 'clientes_promocoes' existe", function() {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $nome = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $pdo  = new PDO("mysql:host=$host;port=$port;dbname=$nome;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $t = $pdo->query("SHOW TABLES LIKE 'clientes_promocoes'")->fetch();
    if (!$t) throw new Exception("Tabela 'clientes_promocoes' não existe. Execute o setup_db.sql.");

    $total = $pdo->query('SELECT COUNT(*) FROM clientes_promocoes')->fetchColumn();
    return "clientes_promocoes existe com $total registo(s) ✓";
});

// ── 6. Teste real de INSERT ─────────────────────────────────
$resultados[] = checar('Inserir registo de teste no banco', function() {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $nome = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $pdo  = new PDO("mysql:host=$host;port=$port;dbname=$nome;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Busca o ID de Portugal
    $pais = $pdo->query("SELECT id FROM paises_prefixo WHERE sigla = 'PT' LIMIT 1")->fetch();
    if (!$pais) throw new Exception("País PT não encontrado. Execute o setup_db.sql.");

    $emailTeste = 'diagnostico_test_' . time() . '@sisi.local';

    $stmt = $pdo->prepare("
        INSERT INTO clientes_promocoes
            (nome, email, pais_id, telefone, telefone_full, canal_whatsapp, canal_email, ip)
        VALUES
            ('Diagnóstico Teste', :email, :pais_id, '900000000', '+351900000000', 1, 1, '127.0.0.1')
    ");
    $stmt->execute([':email' => $emailTeste, ':pais_id' => $pais['id']]);
    $id = $pdo->lastInsertId();

    // Limpa o registo de teste
    $pdo->prepare('DELETE FROM clientes_promocoes WHERE email = ?')->execute([$emailTeste]);

    return "INSERT bem-sucedido! ID gerado: $id. Registo de teste removido. ✓";
});

// ── 7. Função carregarEnv() em db.php ───────────────────────
$resultados[] = checar('Função carregarEnv() no db.php funciona', function() {
    $dbPath = __DIR__ . '/db.php';
    if (!file_exists($dbPath)) throw new Exception("db.php não encontrado em $dbPath");

    $conteudo = file_get_contents($dbPath);
    if (!str_contains($conteudo, 'carregarEnv') && !str_contains($conteudo, 'dotenv')) {
        throw new Exception("db.php não contém a função carregarEnv(). Versão diferente?");
    }
    return 'db.php encontrado e contém carregarEnv() ✓';
});

// ── 8. salvar_cliente.php ───────────────────────────────────
$resultados[] = checar('salvar_cliente.php existe', function() {
    $p = __DIR__ . '/salvar_cliente.php';
    if (!file_exists($p)) throw new Exception("salvar_cliente.php não encontrado em $p");
    return 'salvar_cliente.php encontrado ✓';
});

$resultados[] = checar('salvar_cliente.php usa prepared statements', function() {
    $conteudo = file_get_contents(__DIR__ . '/salvar_cliente.php');
    if (!str_contains($conteudo, 'prepare(')) {
        throw new Exception('salvar_cliente.php não usa PDO::prepare() — risco de SQL injection!');
    }
    return 'Usa PDO::prepare() ✓';
});

// ── Calcula totais ─────────────────────────────────────────
$totalOk   = count(array_filter($resultados, fn($r) => $r['status'] === 'ok'));
$totalErro = count(array_filter($resultados, fn($r) => $r['status'] === 'erro'));

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Diagnóstico — Delícias da Sisi</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 32px; color: #222; }
    h1 { color: #A73D95; margin-bottom: 8px; font-size: 1.8rem; }
    .subtitulo { color: #666; margin-bottom: 32px; font-size: 0.95rem; }
    .resumo { display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
    .card { padding: 20px 32px; border-radius: 12px; font-size: 1.1rem; font-weight: bold; }
    .card-ok   { background: #d1fae5; color: #065f46; border: 2px solid #6ee7b7; }
    .card-erro { background: #fee2e2; color: #991b1b; border: 2px solid #fca5a5; }
    .item { background: #fff; border-radius: 8px; padding: 14px 18px; margin-bottom: 10px;
            border-left: 4px solid #ddd; display: flex; align-items: flex-start; gap: 12px; }
    .item.ok   { border-left-color: #10b981; }
    .item.erro { border-left-color: #ef4444; background: #fff7f7; }
    .icone { font-size: 1.3rem; flex-shrink: 0; margin-top: 1px; }
    .titulo-item { font-weight: 700; margin-bottom: 4px; }
    .msg { font-size: 0.88rem; color: #555; font-family: 'Courier New', monospace; }
    .msg.erro { color: #b91c1c; }
    .aviso { background: #fffbeb; border: 2px solid #f59e0b; border-radius: 10px;
             padding: 16px 20px; margin-top: 32px; color: #92400e; font-size: 0.9rem; }
    h2 { margin-bottom: 16px; color: #444; font-size: 1.2rem; }
  </style>
</head>
<body>

<h1>🍡 Diagnóstico — Delícias da Sisi</h1>
<p class="subtitulo">Verificando por que o cadastro não está a gravar no banco de dados…</p>

<div class="resumo">
  <div class="card card-ok">✅ <?= $totalOk ?> verificações OK</div>
  <?php if ($totalErro > 0): ?>
  <div class="card card-erro">❌ <?= $totalErro ?> erro(s) encontrado(s)</div>
  <?php endif; ?>
</div>

<h2>Resultados detalhados</h2>

<?php foreach ($resultados as $r): ?>
  <div class="item <?= $r['status'] ?>">
    <div class="icone"><?= $r['status'] === 'ok' ? '✅' : '❌' ?></div>
    <div>
      <div class="titulo-item"><?= htmlspecialchars($r['titulo']) ?></div>
      <div class="msg <?= $r['status'] === 'erro' ? 'erro' : '' ?>"><?= htmlspecialchars($r['msg']) ?></div>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($totalErro > 0): ?>
<div class="aviso">
  <strong>⚠️ Como resolver:</strong><br><br>
  <strong>Se o erro for no .env:</strong> Verifique se o arquivo existe na raiz do projeto e se DB_NAME, DB_USER e DB_PASS estão preenchidos corretamente.<br><br>
  <strong>Se o erro for de conexão:</strong> Confirme que o MySQL está a correr e que o utilizador tem permissões no banco <code><?= htmlspecialchars($_ENV['DB_NAME'] ?? 'delicias_sisi') ?></code>.<br><br>
  <strong>Se a tabela não existir:</strong> Execute o arquivo <code>setup_db.sql</code> no MySQL:<br>
  <code>mysql -u root -p &lt; setup_db.sql</code><br><br>
  <strong>Se o INSERT falhar:</strong> Verifique se o utilizador MySQL tem permissão INSERT na tabela.
</div>
<?php endif; ?>

<div class="aviso" style="margin-top:16px; background:#fce7f3; border-color:#A73D95; color:#6b21a8;">
  🔒 <strong>Lembre-se de apagar este ficheiro (diagnostico.php) após resolver o problema!</strong><br>
  Nunca deixe scripts de diagnóstico em produção.
</div>

</body>
</html>