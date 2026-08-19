-- ============================================================
-- 001_create_roles.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 002_create_departments.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_departments_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 003_create_users.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    primary_department_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(50) NULL,
    company VARCHAR(190) NULL,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_users_primary_department
        FOREIGN KEY (primary_department_id) REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_users_role (role_id),
    INDEX idx_users_primary_department (primary_department_id),
    INDEX idx_users_active (is_active),
    INDEX idx_users_company (company)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 004_create_department_users.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS department_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_department_users_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_department_users_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY uq_department_user (department_id, user_id),
    INDEX idx_department_users_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 005_create_categories.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_categories_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    UNIQUE KEY uq_category_slug_per_department (department_id, slug),
    INDEX idx_categories_department (department_id),
    INDEX idx_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 006_create_priorities.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS priorities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_priorities_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 007_create_sla_rules.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS sla_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    priority_id BIGINT UNSIGNED NOT NULL,
    first_response_minutes INT UNSIGNED NOT NULL,
    resolution_minutes INT UNSIGNED NOT NULL,
    pause_on_waiting_customer TINYINT(1) NOT NULL DEFAULT 1,
    use_business_hours TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sla_rules_priority
        FOREIGN KEY (priority_id) REFERENCES priorities(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY uq_sla_rule_priority (priority_id),
    INDEX idx_sla_rules_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 008_create_tickets.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    priority_id BIGINT UNSIGNED NOT NULL,
    status ENUM(
        'new',
        'open',
        'assigned',
        'in_progress',
        'waiting_for_customer',
        'resolved',
        'closed',
        'cancelled'
    ) NOT NULL DEFAULT 'new',
    assigned_agent_id BIGINT UNSIGNED NULL,

    first_response_due_at DATETIME NULL,
    resolution_due_at DATETIME NULL,
    first_responded_at DATETIME NULL,

    resolved_at DATETIME NULL,
    closed_at DATETIME NULL,
    cancelled_at DATETIME NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tickets_customer
        FOREIGN KEY (customer_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_tickets_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_tickets_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_tickets_priority
        FOREIGN KEY (priority_id) REFERENCES priorities(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_tickets_assigned_agent
        FOREIGN KEY (assigned_agent_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_tickets_customer (customer_id),
    INDEX idx_tickets_department (department_id),
    INDEX idx_tickets_category (category_id),
    INDEX idx_tickets_priority (priority_id),
    INDEX idx_tickets_status (status),
    INDEX idx_tickets_assigned_agent (assigned_agent_id),
    INDEX idx_tickets_created_at (created_at),
    INDEX idx_tickets_updated_at (updated_at),
    INDEX idx_tickets_status_priority (status, priority_id),
    INDEX idx_tickets_department_status (department_id, status),
    INDEX idx_tickets_agent_status (assigned_agent_id, status),
    INDEX idx_tickets_first_response_due_at (first_response_due_at),
    INDEX idx_tickets_resolution_due_at (resolution_due_at),
    FULLTEXT INDEX ftx_tickets_subject_description (subject, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 009_create_ticket_messages.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS ticket_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    message LONGTEXT NOT NULL,
    message_type ENUM(
        'customer_reply',
        'agent_reply',
        'internal_note'
    ) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_ticket_messages_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_ticket_messages_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_ticket_messages_ticket (ticket_id),
    INDEX idx_ticket_messages_user (user_id),
    INDEX idx_ticket_messages_type (message_type),
    INDEX idx_ticket_messages_created (ticket_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 010_create_ticket_attachments.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS ticket_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    ticket_message_id BIGINT UNSIGNED NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ticket_attachments_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_ticket_attachments_message
        FOREIGN KEY (ticket_message_id) REFERENCES ticket_messages(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_ticket_attachments_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_ticket_attachments_ticket (ticket_id),
    INDEX idx_ticket_attachments_message (ticket_message_id),
    INDEX idx_ticket_attachments_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 011_create_ticket_activities.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS ticket_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ticket_activities_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_ticket_activities_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_ticket_activities_ticket (ticket_id),
    INDEX idx_ticket_activities_user (user_id),
    INDEX idx_ticket_activities_action (action),
    INDEX idx_ticket_activities_created (ticket_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 012_create_notifications.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ticket_id BIGINT UNSIGNED NULL,
    type VARCHAR(100) NOT NULL,
    channel ENUM('web', 'email') NOT NULL DEFAULT 'web',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    sent_at DATETIME NULL,
    failed_at DATETIME NULL,
    failure_reason TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_notifications_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    INDEX idx_notifications_user_read (user_id, is_read),
    INDEX idx_notifications_ticket (ticket_id),
    INDEX idx_notifications_channel (channel),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 013_create_audit_logs.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(150) NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_audit_logs_user (user_id),
    INDEX idx_audit_logs_action (action),
    INDEX idx_audit_logs_entity (entity_type, entity_id),
    INDEX idx_audit_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 014_create_system_settings.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(190) NOT NULL UNIQUE,
    value LONGTEXT NULL,
    value_type ENUM('string', 'integer', 'boolean', 'json', 'secret') NOT NULL DEFAULT 'string',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_system_settings_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 015_create_password_reset_tokens.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(190) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_reset_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 016_seed_default_roles_priorities_sla.sql
-- ============================================================
START TRANSACTION;

INSERT INTO roles (name, slug)
VALUES
    ('Customer', 'customer'),
    ('Staff / Agent', 'agent'),
    ('Manager', 'manager'),
    ('Administrator', 'administrator')
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT INTO priorities (name, slug, sort_order, is_active)
VALUES
    ('Low', 'low', 10, 1),
    ('Medium', 'medium', 20, 1),
    ('High', 'high', 30, 1),
    ('Critical / Urgent', 'critical', 40, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

INSERT INTO sla_rules (
    priority_id,
    first_response_minutes,
    resolution_minutes,
    pause_on_waiting_customer,
    use_business_hours,
    is_active
)
SELECT id, 480, 2880, 1, 0, 1
FROM priorities
WHERE slug = 'low'
ON DUPLICATE KEY UPDATE
    first_response_minutes = VALUES(first_response_minutes),
    resolution_minutes = VALUES(resolution_minutes),
    pause_on_waiting_customer = VALUES(pause_on_waiting_customer),
    use_business_hours = VALUES(use_business_hours),
    is_active = VALUES(is_active);

INSERT INTO sla_rules (
    priority_id,
    first_response_minutes,
    resolution_minutes,
    pause_on_waiting_customer,
    use_business_hours,
    is_active
)
SELECT id, 240, 1440, 1, 0, 1
FROM priorities
WHERE slug = 'medium'
ON DUPLICATE KEY UPDATE
    first_response_minutes = VALUES(first_response_minutes),
    resolution_minutes = VALUES(resolution_minutes),
    pause_on_waiting_customer = VALUES(pause_on_waiting_customer),
    use_business_hours = VALUES(use_business_hours),
    is_active = VALUES(is_active);

INSERT INTO sla_rules (
    priority_id,
    first_response_minutes,
    resolution_minutes,
    pause_on_waiting_customer,
    use_business_hours,
    is_active
)
SELECT id, 60, 480, 1, 0, 1
FROM priorities
WHERE slug = 'high'
ON DUPLICATE KEY UPDATE
    first_response_minutes = VALUES(first_response_minutes),
    resolution_minutes = VALUES(resolution_minutes),
    pause_on_waiting_customer = VALUES(pause_on_waiting_customer),
    use_business_hours = VALUES(use_business_hours),
    is_active = VALUES(is_active);

INSERT INTO sla_rules (
    priority_id,
    first_response_minutes,
    resolution_minutes,
    pause_on_waiting_customer,
    use_business_hours,
    is_active
)
SELECT id, 30, 240, 1, 0, 1
FROM priorities
WHERE slug = 'critical'
ON DUPLICATE KEY UPDATE
    first_response_minutes = VALUES(first_response_minutes),
    resolution_minutes = VALUES(resolution_minutes),
    pause_on_waiting_customer = VALUES(pause_on_waiting_customer),
    use_business_hours = VALUES(use_business_hours),
    is_active = VALUES(is_active);

INSERT INTO system_settings (`key`, value, value_type, is_public)
VALUES
    ('ticket_prefix', 'TKT', 'string', 0),
    ('ticket_start_number', '10000', 'integer', 0),
    ('attachment_max_size_mb', '10', 'integer', 0),
    ('notification_web_enabled', 'true', 'boolean', 0),
    ('notification_email_enabled', 'true', 'boolean', 0),
    ('allow_customer_reopen_resolved', 'true', 'boolean', 0)
ON DUPLICATE KEY UPDATE
    value = VALUES(value),
    value_type = VALUES(value_type),
    is_public = VALUES(is_public);

COMMIT;

-- ============================================================
-- 017_create_ticket_number_sequence.sql
-- ============================================================
CREATE TABLE IF NOT EXISTS ticket_number_sequences (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    current_value BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ticket_number_sequences (id, current_value)
VALUES (1, 10000)
ON DUPLICATE KEY UPDATE current_value = current_value;

-- Recommended backend strategy:
-- 1. START TRANSACTION
-- 2. SELECT current_value FROM ticket_number_sequences WHERE id = 1 FOR UPDATE
-- 3. Increment current_value
-- 4. UPDATE ticket_number_sequences SET current_value = ?
-- 5. Generate ticket_number, e.g. TKT-10001
-- 6. Insert ticket
-- 7. COMMIT
--
-- This avoids duplicate ticket numbers under concurrent requests.

