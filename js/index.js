// ── Menu mobile ─────────────────────────────────────────────
const menuToggle = document.getElementById('menuToggle');
const mobileNav  = document.getElementById('mobileNav');

menuToggle.addEventListener('click', () => {
  mobileNav.classList.toggle('open');
  menuToggle.textContent = mobileNav.classList.contains('open') ? '✕' : '☰';
});

document.querySelectorAll('.mobile-nav-link').forEach(link => {
  link.addEventListener('click', () => {
    mobileNav.classList.remove('open');
    menuToggle.textContent = '☰';
  });
});

// ── Scroll suave com offset ──────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', (e) => {
    const target = document.querySelector(anchor.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const headerH = document.querySelector('.header').offsetHeight;
      const top = target.getBoundingClientRect().top + window.scrollY - headerH - 16;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});

// ── Animação de entrada dos cards ───────────────────────────
const style = document.createElement('style');
style.textContent = `.visible { opacity: 1 !important; transform: translateY(0) !important; }`;
document.head.appendChild(style);

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => entry.target.classList.add('visible'), i * 80);
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.category-card, .promo-card, .review-card').forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(20px)';
  el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
  observer.observe(el);
});

// ── Prefixo de país — atualiza o display visual ─────────────
const paisSelect      = document.getElementById('paisId');
const prefixoDisplay  = document.getElementById('prefixoDisplay');

function atualizarPrefixo() {
  const opcao = paisSelect.options[paisSelect.selectedIndex];
  const bandeira = opcao.text.trim().split(' ')[0];          // emoji
  const sigla    = opcao.text.trim().split(' ')[1];          // PT / BR …
  const prefixo  = opcao.dataset.prefixo;
  prefixoDisplay.textContent = `${bandeira} ${prefixo}`;
}

if (paisSelect) {
  paisSelect.addEventListener('change', atualizarPrefixo);
  atualizarPrefixo(); // estado inicial
}

// ── Formulário de inscrição — AJAX ───────────────────────────
const form       = document.getElementById('inscricaoForm');
const feedback   = document.getElementById('formFeedback');
const btnEnviar  = document.getElementById('btnEnviar');
const btnTexto   = document.getElementById('btnTexto');
const btnLoader  = document.getElementById('btnLoader');

function mostrarFeedback(msg, tipo /* 'sucesso' | 'erro' */) {
  feedback.textContent = msg;
  feedback.className   = `form-feedback ${tipo}`;
  feedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function limparErros() {
  document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
  document.querySelectorAll('.form-input').forEach(el => el.classList.remove('input-erro'));
  feedback.className = 'form-feedback';
  feedback.textContent = '';
}

function marcarErro(inputId, erroId, msg) {
  const input = document.getElementById(inputId);
  const span  = document.getElementById(erroId);
  if (input) input.classList.add('input-erro');
  if (span)  span.textContent = msg;
}

function validarFront() {
  let valido = true;
  limparErros();

  const nome     = document.getElementById('nome').value.trim();
  const email    = document.getElementById('email').value.trim();
  const telefone = document.getElementById('telefone').value.trim();
  const whatsapp = document.querySelector('[name="canal_whatsapp"]')?.checked;
  const emailCan = document.querySelector('[name="canal_email"]')?.checked;

  if (nome.length < 2) {
    marcarErro('nome', 'erroNome', 'O nome deve ter pelo menos 2 caracteres.');
    valido = false;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    marcarErro('email', 'erroEmail', 'Introduza um e-mail válido.');
    valido = false;
  }
  const telLimpo = telefone.replace(/\D/g, '');
  if (telLimpo.length < 7 || telLimpo.length > 15) {
    marcarErro('telefone', 'erroTelefone', 'Número de telefone inválido.');
    valido = false;
  }
  if (!whatsapp && !emailCan) {
    document.getElementById('erroCanal').textContent = 'Selecione pelo menos um canal.';
    valido = false;
  }

  return valido;
}

if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!validarFront()) return;

    // Bloqueia botão
    btnEnviar.disabled   = true;
    btnTexto.hidden      = true;
    btnLoader.hidden     = false;

    const dados = new FormData(form);
    // Checkboxes não marcados não aparecem no FormData, forçamos 0
    if (!form.querySelector('[name="canal_whatsapp"]').checked) dados.set('canal_whatsapp', '0');
    if (!form.querySelector('[name="canal_email"]').checked)    dados.set('canal_email', '0');

    try {
      const resp = await fetch('salvar_cliente.php', {
        method: 'POST',
        body: dados,
      });

      const json = await resp.json();

      if (json.ok) {
        mostrarFeedback(json.mensagem, 'sucesso');
        form.reset();
        atualizarPrefixo();
      } else {
        const msgs = json.erros ? json.erros.join(' ') : (json.erro ?? 'Erro desconhecido.');
        mostrarFeedback(msgs, 'erro');
      }
    } catch (err) {
      mostrarFeedback('Não foi possível enviar. Verifique a sua ligação e tente novamente.', 'erro');
    } finally {
      btnEnviar.disabled = false;
      btnTexto.hidden    = false;
      btnLoader.hidden   = true;
    }
  });
}