# Delícias da Sisi — Website

Site institucional com formulário de inscrição em promoções para a **Delícias da Sisi**, negócio de comida brasileira em Faro, Portugal.

---

## Tecnologias utilizadas

| Camada | Tecnologia |
|--------|------------|
| Back-end | PHP 8.1+ |
| Front-end | HTML5, CSS3, JavaScript (vanilla) |
| Base de dados | MySQL 8+ |
| Acesso ao BD | PDO (PHP Data Objects) |
| Testes | PHPUnit 11 |
| Gestão de dependências | Composer |

---

## Estrutura do projeto

```
delicias-da-sisi/
├── .env                    # Credenciais do banco (não vai ao Git)
├── .gitignore
├── composer.json
├── db.php                  # Conexão com o banco de dados
├── index.php               # Página principal
├── salvar_cliente.php      # Endpoint AJAX — grava inscrições
├── diagnostico.php         # Ferramenta de diagnóstico (remover em produção)
├── setup_db.sql            # Script de criação das tabelas
├── css/
│   ├── index.css
│   └── form.css
├── js/
│   └── index.js
└── tests/
    ├── phpunit.xml
    ├── bootstrap.php
    ├── testcase.php
    ├── units/
    │   ├── ConexaoBancoDadosTest.php
    │   └── ValidacaoTest.php
    └── integration/
        ├── CadastroClienteTest.php
        └── ApiEndpointTest.php
```

---

## Base de dados

### Configuração

As credenciais ficam no arquivo `.env` na raiz do projeto:

```env
DB_HOST=localhost
DB_NAME=delicias_sisi
DB_USER=root
DB_PASS=sua_senha
DB_PORT=3306
```

### Tabelas

**`paises_prefixo`** — prefixos internacionais (DDI)

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT UNSIGNED | Chave primária |
| nome | VARCHAR(80) | Nome do país |
| sigla | CHAR(3) | Sigla (PT, BR…) |
| prefixo | VARCHAR(10) | DDI (+351, +55…) |
| bandeira | VARCHAR(10) | Emoji da bandeira |
| ativo | TINYINT(1) | Activo/inactivo |
| ordem | SMALLINT | Ordem de exibição |

**`clientes_promocoes`** — clientes inscritos para receber promoções

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | INT UNSIGNED | Chave primária |
| nome | VARCHAR(120) | Nome do cliente |
| email | VARCHAR(180) | E-mail (único) |
| pais_id | INT UNSIGNED | FK → paises_prefixo |
| telefone | VARCHAR(20) | Número sem prefixo |
| telefone_full | VARCHAR(30) | Prefixo + número (único) |
| canal_whatsapp | TINYINT(1) | Quer receber por WhatsApp |
| canal_email | TINYINT(1) | Quer receber por e-mail |
| ativo | TINYINT(1) | Registo activo |
| ip | VARCHAR(45) | IP de origem |
| created_at | TIMESTAMP | Data de inscrição |
| updated_at | TIMESTAMP | Última atualização |

### Criar as tabelas

```bash
mysql -u root -p delicias_sisi < setup_db.sql
```

---

## Conexão com o banco — `db.php`

O arquivo `db.php` é responsável por carregar as credenciais e fornecer a conexão PDO.

### `carregarEnv(string $caminho): void`

Lê o arquivo `.env` linha a linha e popula `$_ENV` e `putenv()`. Variáveis já definidas no ambiente (ex.: phpunit.xml em testes) não são sobrescritas, o que permite usar bancos separados por ambiente.

```php
carregarEnv(__DIR__ . '/.env');
```

### `getConexao(): PDO`

Retorna a instância PDO usando o padrão **singleton estático** — a conexão é criada apenas uma vez por execução e reutilizada em todas as chamadas subsequentes.

Configurações aplicadas:

| Opção PDO | Valor | Efeito |
|-----------|-------|--------|
| `ATTR_ERRMODE` | `ERRMODE_EXCEPTION` | Lança exceção em erros SQL |
| `ATTR_DEFAULT_FETCH_MODE` | `FETCH_ASSOC` | Resultados como array associativo |
| `ATTR_EMULATE_PREPARES` | `false` | Prepared statements nativos do MySQL |

O charset `utf8mb4` é definido no DSN, garantindo suporte a emojis e caracteres especiais.

---

## Salvar cliente — `salvar_cliente.php`

Endpoint chamado via `fetch()` (AJAX) pelo formulário da página principal. Aceita apenas `POST` e retorna JSON.

### Fluxo de execução

```
POST /salvar_cliente.php
        │
        ▼
1. Valida método HTTP (só POST)
        │
        ▼
2. Coleta e sanitiza os campos:
   - nome       → FILTER_SANITIZE_SPECIAL_CHARS
   - email      → FILTER_SANITIZE_EMAIL
   - pais_id    → FILTER_SANITIZE_NUMBER_INT
   - telefone   → FILTER_SANITIZE_SPECIAL_CHARS
   - canal_*    → FILTER_VALIDATE_BOOLEAN
        │
        ▼
3. Validações de negócio:
   - nome ≥ 2 caracteres
   - e-mail válido
   - país selecionado (pais_id > 0)
   - telefone com 7–15 dígitos
   - pelo menos um canal selecionado
        │
        ├── Erros? → HTTP 422 + JSON {ok: false, erros: [...]}
        │
        ▼
4. Busca prefixo do país na tabela paises_prefixo
        │
        ├── País não encontrado? → HTTP 422
        │
        ▼
5. Monta telefone_full = prefixo + número limpo
        │
        ▼
6. INSERT INTO clientes_promocoes … ON DUPLICATE KEY UPDATE
   (e-mail ou telefone duplicado atualiza nome e canais)
        │
        ▼
7. HTTP 200 + JSON {ok: true, mensagem: "…"}
        │
        └── Exceção PDO? → HTTP 500 + JSON {ok: false, erro: "…"}
                           (detalhes internos só vão para error_log)
```

### Resposta de sucesso

```json
{
  "ok": true,
  "mensagem": "Inscrição realizada com sucesso! Em breve receberá as nossas promoções. 🎉"
}
```

### Resposta de erro de validação (HTTP 422)

```json
{
  "ok": false,
  "erros": ["O nome deve ter pelo menos 2 caracteres.", "E-mail inválido."]
}
```

---

## Testes

O projeto usa **PHPUnit 11** com dois tipos de teste.

### Configuração

O arquivo `tests/phpunit.xml` aponta para o banco de teste `delicias_sisi_test` (separado do banco de produção). Antes de correr os testes, crie esse banco e execute o `setup_db.sql` nele:

```bash
mysql -u root -p delicias_sisi_test < setup_db.sql
```

### Executar os testes

```bash
composer install
./vendor/bin/phpunit --configuration tests/phpunit.xml --testdox
```

### Suites

| Suite | Pasta | O que testa |
|-------|-------|-------------|
| Unit | `tests/units/` | Conexão PDO, leitura do `.env`, regras de validação isoladas |
| Integration | `tests/integration/` | Fluxo completo de cadastro no banco, endpoint HTTP |

---

## Desenvolvimento local

1. Instale [WAMP](https://www.wampserver.com/) (ou equivalente)
2. Clone o repositório em `wamp64/www/delicias-da-sisi`
3. Crie o arquivo `.env` com as suas credenciais
4. Execute `setup_db.sql` no MySQL
5. Acesse `http://localhost/delicias-da-sisi/`

Para diagnosticar problemas de conexão, acesse:

```
http://localhost/delicias-da-sisi/diagnostico.php?chave=sisi2025diag
```

> Remova `diagnostico.php` antes de colocar em produção.
