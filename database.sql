-- ============================================================
--  RosaPay – Hub Central de Pagamentos e CRM
--  Arquivo: database.sql
--  Descrição: Estrutura completa do banco de dados
--  Execute este script no seu servidor MySQL/MariaDB
-- ============================================================

-- Cria o banco caso não exista
CREATE DATABASE IF NOT EXISTS rosapay
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rosapay;

-- ============================================================
--  Tabela principal: crm_vendas
-- ============================================================
CREATE TABLE IF NOT EXISTS crm_vendas (

  -- ── Identificação ─────────────────────────────────────────
  id            INT UNSIGNED       NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(200)       NOT NULL DEFAULT '',
  email         VARCHAR(254)       NOT NULL DEFAULT '',
  cpf           VARCHAR(14)        NOT NULL DEFAULT '',   -- formato: 000.000.000-00
  whatsapp      VARCHAR(20)        NOT NULL DEFAULT '',   -- formato: (00) 00000-0000

  -- ── Origem ────────────────────────────────────────────────
  origem_url    VARCHAR(500)       NOT NULL DEFAULT '',   -- URL do site que enviou o lead

  -- ── Endereço ──────────────────────────────────────────────
  cep           VARCHAR(9)         NOT NULL DEFAULT '',   -- 00000-000
  rua           VARCHAR(300)       NOT NULL DEFAULT '',
  numero        VARCHAR(20)        NOT NULL DEFAULT '',
  complemento   VARCHAR(100)       NOT NULL DEFAULT '',
  bairro        VARCHAR(150)       NOT NULL DEFAULT '',
  cidade        VARCHAR(150)       NOT NULL DEFAULT '',
  estado        CHAR(2)            NOT NULL DEFAULT '',   -- UF ex: SP

  -- ── Tronfy ────────────────────────────────────────────────
  tronfy_transaction_id VARCHAR(120) NOT NULL DEFAULT '',
  tronfy_metodo    ENUM('PIX','CARTAO','')  NOT NULL DEFAULT '',
  tronfy_valor     INT UNSIGNED    NOT NULL DEFAULT 0,    -- em centavos
  tronfy_pix_code  TEXT,                                  -- pix_qr_code_text retornado pela Tronfy
  tronfy_pix_qr    VARCHAR(500)    NOT NULL DEFAULT '',   -- URL da imagem QR Code (se houver)
  status ENUM(
    'Iniciado',
    'Pendente',
    'Aprovado',
    'Abandonado',
    'Recusado'
  ) NOT NULL DEFAULT 'Iniciado',

  -- ── Timestamps ────────────────────────────────────────────
  created_at    DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,

  -- ── Chaves ────────────────────────────────────────────────
  PRIMARY KEY (id),
  INDEX idx_cpf           (cpf),
  INDEX idx_email         (email),
  INDEX idx_status        (status),
  INDEX idx_tronfy_txid   (tronfy_transaction_id),
  INDEX idx_created       (created_at),
  INDEX idx_whatsapp      (whatsapp)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Hub Central RosaPay – Leads e Transações';

-- ============================================================
--  Usuário administrador do painel
-- ============================================================
CREATE TABLE IF NOT EXISTS painel_usuarios (
  id         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  usuario    VARCHAR(80)    NOT NULL UNIQUE,
  senha_hash VARCHAR(255)   NOT NULL,            -- bcrypt via password_hash()
  created_at DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insere usuário padrão: admin / rosapay2024
-- TROQUE a senha após o primeiro login!
INSERT IGNORE INTO painel_usuarios (usuario, senha_hash)
VALUES ('admin', '$2y$12$pjVf7hEqHLZ3RZBcKb9/yewk5.e6HUZF4zq13cklKt3b6QkiuIPhq');
-- senha acima corresponde a: rosapay2024

-- ============================================================
--  View auxiliar: funil de vendas
-- ============================================================
CREATE OR REPLACE VIEW vw_funil AS
SELECT
  COUNT(*)                                              AS total_leads,
  SUM(cep   <> '')                                      AS com_endereco,
  SUM(status = 'Aprovado')                              AS aprovados,
  SUM(status = 'Pendente')                              AS pendentes,
  SUM(status = 'Abandonado')                            AS abandonados,
  SUM(status = 'Recusado')                              AS recusados,
  COALESCE(SUM(CASE WHEN status='Aprovado'
               THEN tronfy_valor END), 0)               AS receita_centavos
FROM crm_vendas;
