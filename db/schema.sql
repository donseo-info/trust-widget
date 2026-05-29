-- Trust Widget Platform — MySQL Schema
-- Run once: mysql -u root -p trust_widget < schema.sql

CREATE DATABASE IF NOT EXISTS trust_widget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trust_widget;

-- ─── Users (stub — multi-tenancy ready) ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(255)                      NOT NULL,
    password     VARCHAR(255)                      NOT NULL,
    name         VARCHAR(100)                      NOT NULL DEFAULT '',
    role         ENUM('admin','manager')           NOT NULL DEFAULT 'manager',
    is_active    TINYINT(1)                        NOT NULL DEFAULT 1,
    created_at   TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email (email)
) ENGINE=InnoDB;

-- Default admin (password: admin123 — change immediately)
INSERT IGNORE INTO users (email, password, name, role)
VALUES ('admin@localhost', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin');

-- ─── Sites ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sites (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED                      DEFAULT NULL,
    name         VARCHAR(255)                      NOT NULL,
    domain       VARCHAR(255)                      NOT NULL,
    api_key      VARCHAR(64)                       NOT NULL,
    is_active    TINYINT(1)                        NOT NULL DEFAULT 1,
    debug_mode   TINYINT(1)                        NOT NULL DEFAULT 0,
    created_at   TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_api_key (api_key),
    INDEX idx_domain (domain),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- ─── Widget Types (extensible registry) ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS widget_types (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(50)                       NOT NULL,
    name         VARCHAR(100)                      NOT NULL,
    description  TEXT,
    icon         VARCHAR(50)                       NOT NULL DEFAULT 'bi-puzzle',
    is_active    TINYINT(1)                        NOT NULL DEFAULT 1,
    sort_order   INT                               NOT NULL DEFAULT 0,
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB;

INSERT IGNORE INTO widget_types (slug, name, description, icon, sort_order) VALUES
('exit_popup',  'Exit Popup',       'Попап с A/B/C вариантами при выходе мышью за верхнюю границу', 'bi-door-open',    10),
('callback',    'Обратный звонок',  'Виджет обратного звонка с авто-открытием по времени и прокрутке', 'bi-telephone',    20);

-- ─── Site Widgets (per-site widget config) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS site_widgets (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id        INT UNSIGNED                   NOT NULL,
    widget_type_id INT UNSIGNED                   NOT NULL,
    config         JSON                           NOT NULL,
    is_active      TINYINT(1)                     NOT NULL DEFAULT 1,
    created_at     TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_site_widget (site_id, widget_type_id),
    CONSTRAINT fk_sw_site   FOREIGN KEY (site_id)        REFERENCES sites(id)        ON DELETE CASCADE,
    CONSTRAINT fk_sw_type   FOREIGN KEY (widget_type_id) REFERENCES widget_types(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── Site Integrations ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS site_integrations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id      INT UNSIGNED                      NOT NULL,
    type         ENUM('telegram','bitrix24','yandex_metrika') NOT NULL,
    config       JSON                              NOT NULL,
    is_active    TINYINT(1)                        NOT NULL DEFAULT 1,
    created_at   TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP                         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_site_integ (site_id, type),
    CONSTRAINT fk_si_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── Leads (unified for all widgets) ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS leads (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id        INT UNSIGNED                   NOT NULL,
    widget_type_id INT UNSIGNED                   NOT NULL,
    phone          VARCHAR(20)                    NOT NULL DEFAULT '',
    extra          JSON                           NOT NULL COMMENT 'widget-specific data: variant, messenger, etc.',
    page_url       TEXT,
    referrer       TEXT,
    utm_source     VARCHAR(255)                   NOT NULL DEFAULT '',
    utm_medium     VARCHAR(255)                   NOT NULL DEFAULT '',
    utm_campaign   VARCHAR(255)                   NOT NULL DEFAULT '',
    utm_content    VARCHAR(255)                   NOT NULL DEFAULT '',
    utm_term       VARCHAR(255)                   NOT NULL DEFAULT '',
    ip             VARCHAR(45)                    NOT NULL DEFAULT '',
    user_agent     TEXT,
    ym_client_id   VARCHAR(100)                   NOT NULL DEFAULT '',
    trigger_type   VARCHAR(50)                    NOT NULL DEFAULT 'manual',
    created_at     TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_id    (site_id),
    INDEX idx_widget_type(widget_type_id),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_l_site FOREIGN KEY (site_id)        REFERENCES sites(id)        ON DELETE CASCADE,
    CONSTRAINT fk_l_type FOREIGN KEY (widget_type_id) REFERENCES widget_types(id)
) ENGINE=InnoDB;

-- ─── Popup Events (exit_popup opens/impressions tracking) ────────────────────
CREATE TABLE IF NOT EXISTS popup_events (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id        INT UNSIGNED                   NOT NULL,
    action         ENUM('open','lead')            NOT NULL,
    variant        VARCHAR(10)                    NOT NULL DEFAULT '',
    phone          VARCHAR(20)                    NOT NULL DEFAULT '',
    messenger      VARCHAR(20)                    NOT NULL DEFAULT '',
    ym_client_id   VARCHAR(100)                   NOT NULL DEFAULT '',
    has_ym         TINYINT(1)                     NOT NULL DEFAULT 0,
    url            TEXT,
    referrer       TEXT,
    ip             VARCHAR(45)                    NOT NULL DEFAULT '',
    created_at     TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_id    (site_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action     (action),
    CONSTRAINT fk_pe_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;
