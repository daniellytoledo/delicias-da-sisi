<?php
require_once __DIR__ . '/db.php';

// Carrega prefixos do banco para o select de países
$paises = [];
try {
    $pdo    = getConexao();
    $stmt   = $pdo->query('SELECT id, nome, sigla, prefixo, bandeira FROM paises_prefixo WHERE ativo = 1 ORDER BY ordem, nome');
    $paises = $stmt->fetchAll();
} catch (Exception $e) {
    // Se o banco falhar, usa lista mínima como fallback
    $paises = [
        ['id' => 1, 'nome' => 'Portugal',  'sigla' => 'PT', 'prefixo' => '+351', 'bandeira' => '🇵🇹'],
        ['id' => 2, 'nome' => 'Brasil',    'sigla' => 'BR', 'prefixo' => '+55',  'bandeira' => '🇧🇷'],
    ];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Delícias da Sisi</title>
  <link rel="stylesheet" href="css/index.css" />
  <link rel="stylesheet" href="css/form.css">
  <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
</head>
<body>

  <!-- HEADER / NAV -->
  <header class="header">
    <div class="header-inner">
      <div class="logo">
        <span class="logo-icon">🍡</span>
        <div class="logo-text">
          <span class="logo-name">Delícias da Sisi</span>
          <span class="logo-sub">sabores do Brasil em Portugal</span>
        </div>
      </div>

      <nav class="nav">
        <a href="#localizacao" class="nav-link">📍 Localização</a>
        <a href="#avaliacoes" class="nav-link">⭐ Avaliações</a>
        <a href="#promocoes" class="nav-link">🎉 Promoções</a>
      </nav>

      <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">☰</button>
    </div>

    <div class="mobile-nav" id="mobileNav">
      <a href="#localizacao" class="mobile-nav-link">📍 Localização</a>
      <a href="#avaliacoes" class="mobile-nav-link">⭐ Avaliações</a>
      <a href="#promocoes" class="mobile-nav-link">🎉 Promoções</a>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg-pattern"></div>
    <div class="hero-content">
      <p class="hero-eyebrow">✨ feito com amor e carinho</p>
      <h1 class="hero-title">O seu momento<br /><em>com um sabor especial</em></h1>
      <p class="hero-text">
        Não pense muito! Veja o cardápio e faça logo sua encomenda.<br />
        Temos salgados, bolos, açaí, kits para festa...<br />
        Entre em nosso Instagram e confira nossas fotos.<br />
        <strong>Estamos aguardando você!</strong>
      </p>
      <div class="hero-cta">
        <a href="https://www.instagram.com/deliciasdasisipt/" target="_blank" class="btn btn-primary">
          <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          Instagram
        </a>
        <a href="https://api.whatsapp.com/send?phone=351924272532&text=Ol%C3%A1!%20Gostaria%20de%20fazer%20um%20pedido%20ou%20reserva." target="_blank" class="btn btn-outline">💬 Fazer Encomenda</a>
      </div>
    </div>
    <div class="hero-decoration">
      <div class="deco-circle deco-1"></div>
      <div class="deco-circle deco-2"></div>
      <div class="deco-circle deco-3"></div>
    </div>
  </section>

  <!-- CATEGORIAS -->
  <section class="categories">
    <div class="container">
      <p class="section-eyebrow">o que temos para você</p>
      <h2 class="section-title">Nossos Produtos</h2>
      <div class="category-grid">
        <div class="category-card">
          <div class="category-icon">🍰</div>
          <h3>Bolos</h3>
          <p>Bolos para todas as ocasiões</p>
        </div>
        <div class="category-card">
          <div class="category-icon">🥟</div>
          <h3>Salgados</h3>
          <p>Coxinha, pastel e muito mais. Perfeitos para festas e eventos</p>
        </div>
        <div class="category-card">
          <div class="category-icon">🫐</div>
          <h3>Açaí</h3>
          <p>Açaí cremoso e fresquinho com as melhores coberturas e frutas</p>
        </div>
        <div class="category-card">
          <div class="category-icon">🍬</div>
          <h3>Doces</h3>
          <p>Brigadeiros, beijinhos e muito mais sabor brasileiro</p>
        </div>
        <div class="category-card">
          <div class="category-icon">🎉</div>
          <h3>Kits para Festa</h3>
          <p>Tudo que você precisa para uma festa incrível num único lugar</p>
        </div>
        <div class="category-card">
          <div class="category-icon">🛒</div>
          <h3>Encomendas</h3>
          <p>Personalizamos tudo! Entre em contacto e diga o que precisa</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PROMOÇÕES -->
  <section class="promocoes" id="promocoes">
    <div class="container">
      <p class="section-eyebrow section-eyebrow--light">ofertas especiais</p>
      <h2 class="section-title section-title--light">Promoções</h2>
      <div class="promo-grid">
        <div class="promo-card">
          <div class="promo-badge">🔥 Oferta</div>
          <h3>Kit Festa Completo</h3>
          <p>Salgados + bolo + docinhos + bebida. Encomende com antecedência!</p>
          <a href="https://api.whatsapp.com/send?phone=351924272532&text=Ol%C3%A1!%20Gostaria%20de%20fazer%20um%20pedido%20ou%20reserva." target="_blank" class="promo-link">Consultar preço →</a>
        </div>
        <div class="promo-card promo-card--highlight">
          <div class="promo-badge">⭐ +Popular</div>
          <h3>Caixinha de Brigadeiros</h3>
          <p>Vários sabores!</p>
          <a href="https://api.whatsapp.com/send?phone=351924272532&text=Ol%C3%A1!%20Gostaria%20de%20fazer%20um%20pedido%20ou%20reserva." target="_blank" class="promo-link">Consultar preço →</a>
        </div>
        <div class="promo-card">
          <div class="promo-badge">🎂 Especial</div>
          <h3>Bolo Personalizado</h3>
          <p>Bolo decorado com o tema que você quiser.</p>
          <a href="https://api.whatsapp.com/send?phone=351924272532&text=Ol%C3%A1!%20Gostaria%20de%20fazer%20um%20pedido%20ou%20reserva." target="_blank" class="promo-link">Consultar preço →</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       FORMULÁRIO DE INSCRIÇÃO EM PROMOÇÕES
  ════════════════════════════════════════════════════════ -->
  <section class="inscricao" id="inscricao">
    <div class="container">
      <p class="section-eyebrow section-eyebrow--brand">fique por dentro</p>
      <h2 class="section-title">Receba as Nossas Promoções</h2>
      <p class="inscricao-subtitulo">
        Cadastre-se e receba ofertas exclusivas direto no seu WhatsApp ou e-mail. Prometemos não enviar spam! 🤝
      </p>

      <!-- Mensagem de feedback (escondida por padrão) -->
      <div class="form-feedback" id="formFeedback" role="alert" aria-live="polite"></div>

      <form class="inscricao-form" id="inscricaoForm" novalidate>

        <!-- Nome -->
        <div class="form-group">
          <label for="nome" class="form-label">Nome <span class="obrigatorio">*</span></label>
          <input
            type="text"
            id="nome"
            name="nome"
            class="form-input"
            placeholder="O seu nome completo"
            autocomplete="name"
            maxlength="120"
            required
          />
          <span class="form-error" id="erroNome"></span>
        </div>

        <!-- E-mail -->
        <div class="form-group">
          <label for="email" class="form-label">E-mail <span class="obrigatorio">*</span></label>
          <input
            type="email"
            id="email"
            name="email"
            class="form-input"
            placeholder="exemplo@email.com"
            autocomplete="email"
            maxlength="180"
            required
          />
          <span class="form-error" id="erroEmail"></span>
        </div>

        <!-- Telefone com prefixo de país -->
        <div class="form-group">
          <label for="telefone" class="form-label">Telefone / WhatsApp <span class="obrigatorio">*</span></label>
          <div class="telefone-wrapper">

            <!-- Select de país / prefixo -->
            <div class="prefixo-wrapper">
              <select name="pais_id" id="paisId" class="prefixo-select" required aria-label="Prefixo do país">
                <?php foreach ($paises as $p): ?>
                  <option
                    value="<?= htmlspecialchars($p['id']) ?>"
                    data-prefixo="<?= htmlspecialchars($p['prefixo']) ?>"
                    <?= ($p['sigla'] === 'PT') ? 'selected' : '' ?>
                  >
                    <?= htmlspecialchars($p['bandeira']) ?>
                    <?= htmlspecialchars($p['sigla']) ?>
                    <?= htmlspecialchars($p['prefixo']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <!-- Prefixo visível ao lado do input -->
              <span class="prefixo-display" id="prefixoDisplay">🇵🇹 +351</span>
            </div>

            <input
              type="tel"
              id="telefone"
              name="telefone"
              class="form-input telefone-input"
              placeholder="924 272 532"
              autocomplete="tel-national"
              maxlength="15"
              required
            />
          </div>
          <span class="form-error" id="erroTelefone"></span>
        </div>

        <!-- Canais desejados -->
        <div class="form-group form-group--canais">
          <label class="form-label">Quero receber por: <span class="obrigatorio">*</span></label>
          <div class="canais-wrapper">
            <label class="canal-option">
              <input type="checkbox" name="canal_whatsapp" value="1" checked />
              <span class="canal-box">
                <span class="canal-icon">💬</span>
                <span class="canal-nome">WhatsApp</span>
              </span>
            </label>
            <label class="canal-option">
              <input type="checkbox" name="canal_email" value="1" checked />
              <span class="canal-box">
                <span class="canal-icon">✉️</span>
                <span class="canal-nome">E-mail</span>
              </span>
            </label>
          </div>
          <span class="form-error" id="erroCanal"></span>
        </div>

        <!-- Botão -->
        <button type="submit" class="btn btn-primary btn-form" id="btnEnviar">
          <span id="btnTexto">🎉 Quero Receber Promoções!</span>
          <span id="btnLoader" class="btn-loader" hidden>Enviando…</span>
        </button>

        <p class="form-aviso">
          🔒 Os seus dados estão seguros e nunca serão partilhados com terceiros.
        </p>

      </form>
    </div>
  </section>

  <!-- AVALIAÇÕES -->
  <section class="avaliacoes" id="avaliacoes">
    <div class="container">
      <p class="section-eyebrow">o que dizem os clientes</p>
      <h2 class="section-title">Avaliações</h2>
      <div class="reviews-grid">
        <div class="review-card">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"Comemos pastéis de frango com catupiry que estavam fantásticos, salivo só de lembrar. O casal que nos atendeu era muito simpático. Toda vez que vier para Faro com certeza vou passar por aqui de novo."</p>
          <span class="review-author">— Leonardo Campos</span>
        </div>
        <div class="review-card">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"I came across this little place by chance on Google, and what a wonderful discovery it turned out to be! The owners were incredibly welcoming and made the whole experience even more enjoyable from the moment I walked in. The food was absolutely delicious and incredibly fresh — every bite felt like a trip straight to Brazil."</p>
          <span class="review-author">— Carolina</span>
        </div>
        <div class="review-card">
          <div class="review-stars">★★★★★</div>
          <p class="review-text">"O melhor pastel da minha vida 😳, e olha que já provei muitos no Brasil. Super recomendado! O pessoal é muito amigável e a comida é excelente!"</p>
          <span class="review-author">— Bernhard Streit</span>
        </div>
      </div>
    </div>
  </section>

  <!-- LOCALIZAÇÃO -->
  <section class="localizacao" id="localizacao">
    <div class="container localizacao-inner">
      <div class="localizacao-info">
        <p class="section-eyebrow section-eyebrow--light">onde nos encontrar</p>
        <h2 class="section-title section-title--light">Largo da Mota, 7.</h2>
        <p class="localizacao-text">Entre em contacto para verificar disponibilidade na sua área.</p>
        <div class="contact-list">
          <a href="tel:+351924272532" class="contact-item">
            <span class="contact-icon">📞</span>
            <div>
              <span class="contact-label">Telefone / WhatsApp</span>
              <span class="contact-value">(+351) 924 272 532</span>
            </div>
          </a>
          <a href="https://www.instagram.com/deliciasdasisipt/" target="_blank" class="contact-item">
            <span class="contact-icon">📸</span>
            <div>
              <span class="contact-label">Instagram</span>
              <span class="contact-value">@deliciasdasisipt</span>
            </div>
          </a>
          <div class="contact-item">
            <span class="contact-icon">🕐</span>
            <div>
              <span class="contact-label">Atendimento</span>
              <span class="contact-value">Segunda a Sábado, 12h–20h</span>
            </div>
          </div>
        </div>
      </div>
      <div class="localizacao-map">
        <div class="map-placeholder">
          <span>📍</span>
          <p>Faro, Portugal</p>
          <p class="map-note">Entregas a combinar</p>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container footer-inner">
      <div class="footer-logo">
        <span>🍡</span> Delícias da Sisi
      </div>
      <p class="footer-tagline">Sabores do Brasil em Portugal 🇧🇷🇵🇹</p>
      <div class="footer-links">
        <a href="https://www.instagram.com/deliciasdasisipt/" target="_blank">Instagram</a>
        <a href="tel:+351924272532">Contacto</a>
        <a href="#promocoes">Promoções</a>
        <a href="#inscricao">Newsletter</a>
      </div>
      <p class="footer-copy">© 2025 Delícias da Sisi. Todos os direitos reservados.</p>
    </div>
  </footer>

  <script src="js/index.js"></script>
</body>
</html>