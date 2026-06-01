-- ============================================================
--  setup_db.sql — Criação das tabelas do banco de dados
--  Execute este script uma única vez para configurar o banco
-- ============================================================

CREATE DATABASE IF NOT EXISTS delicias_sisi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE delicias_sisi;

-- Tabela de prefixos de países (DDI)
CREATE TABLE IF NOT EXISTS paises_prefixo (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(80)  NOT NULL,
    sigla       CHAR(3)      NOT NULL,
    prefixo     VARCHAR(10)  NOT NULL,      -- ex: +55, +351
    bandeira    VARCHAR(10)  NOT NULL,      -- emoji da bandeira
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,
    ordem       SMALLINT     NOT NULL DEFAULT 100,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Países disponíveis (expanda conforme necessário)
INSERT INTO paises_prefixo (nome, sigla, prefixo, bandeira, ordem) VALUES
  ('Portugal',        'PT',  '+351', '🇵🇹', 1),
  ('Brasil',          'BR',  '+55',  '🇧🇷', 2),
  ('Estados Unidos',  'US',  '+1',   '🇺🇸', 10),
  ('Reino Unido',     'GB',  '+44',  '🇬🇧', 11),
  ('França',          'FR',  '+33',  '🇫🇷', 12),
  ('Espanha',         'ES',  '+34',  '🇪🇸', 13),
  ('Alemanha',        'DE',  '+49',  '🇩🇪', 14),
  ('Itália',          'IT',  '+39',  '🇮🇹', 15),
  ('Angola',          'AO',  '+244', '🇦🇴', 20),
  ('Moçambique',      'MZ',  '+258', '🇲🇿', 21),
  ('Cabo Verde',      'CV',  '+238', '🇨🇻', 22),
  ('Suíça',           'CH',  '+41',  '🇨🇭', 30),
  ('Países Baixos',   'NL',  '+31',  '🇳🇱', 31),
  ('Bélgica',         'BE',  '+32',  '🇧🇪', 32),
  ('Luxemburgo',      'LU',  '+352', '🇱🇺', 33);

-- Tabela de clientes inscritos para promoções
CREATE TABLE IF NOT EXISTS clientes_promocoes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome            VARCHAR(120) NOT NULL,
    email           VARCHAR(180) NOT NULL,
    pais_id         INT UNSIGNED NOT NULL,
    telefone        VARCHAR(20)  NOT NULL,    -- número sem o prefixo
    telefone_full   VARCHAR(30)  NOT NULL,    -- prefixo + número
    canal_whatsapp  TINYINT(1)   NOT NULL DEFAULT 1,
    canal_email     TINYINT(1)   NOT NULL DEFAULT 1,
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    ip              VARCHAR(45)  NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_email (email),
    UNIQUE KEY uq_telefone_full (telefone_full),
    FOREIGN KEY (pais_id) REFERENCES paises_prefixo(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
