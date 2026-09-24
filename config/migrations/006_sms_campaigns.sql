-- Amazing Feet — SMS Campaigns (Arkesel bulk SMS)
-- Run once: mysql -u root amazingfeet < config/migrations/006_sms_campaigns.sql

CREATE TABLE IF NOT EXISTS sms_campaigns (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(150) NULL,
    message          TEXT NOT NULL,
    audience         ENUM('all_customers','custom','mixed') NOT NULL DEFAULT 'custom',
    recipient_count  INT NOT NULL DEFAULT 0,
    sent_count       INT NOT NULL DEFAULT 0,
    failed_count     INT NOT NULL DEFAULT 0,
    status           ENUM('queued','sending','sent','partial','failed') NOT NULL DEFAULT 'queued',
    api_response     TEXT NULL,
    created_by       INT NOT NULL,
    sent_at          DATETIME NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_sms_camp_created (created_at),
    INDEX idx_sms_camp_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sms_campaign_recipients (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id  INT NOT NULL,
    phone        VARCHAR(20) NOT NULL,
    customer_id  INT NULL,
    status       ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    error_msg    VARCHAR(255) NULL,
    FOREIGN KEY (campaign_id) REFERENCES sms_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_sms_rec_camp (campaign_id),
    INDEX idx_sms_rec_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
