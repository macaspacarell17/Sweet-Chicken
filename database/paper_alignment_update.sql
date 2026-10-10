-- ==========================================================================
-- Sweet Chicken — paper-alignment migration
-- Run ONCE, after the base database and connected_update.sql are imported.
-- Targets MariaDB (the database bundled with XAMPP). Safe to re-run.
-- ==========================================================================
USE sweet_chicken_db;

-- 1. Dine-in table number (used by the Cashier POS and the Kitchen display)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS table_number VARCHAR(20) NULL AFTER order_type;

-- 2. Payment methods: Cash and GCash only (cards are outside the study's scope).
--    Any legacy 'Card' rows are converted to 'Cash' so the column can be narrowed.
UPDATE orders   SET payment_method = 'Cash' WHERE payment_method = 'Card';
UPDATE payments SET payment_method = 'Cash' WHERE payment_method = 'Card';
ALTER TABLE orders   MODIFY payment_method ENUM('Cash','GCash') DEFAULT NULL;
ALTER TABLE payments MODIFY payment_method ENUM('Cash','GCash') DEFAULT NULL;

-- 3. Audit logs (who did what, to which resource, from which IP, and when)
CREATE TABLE IF NOT EXISTS audit_logs (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT NULL,
  action_performed VARCHAR(100) NOT NULL,
  target_resource  VARCHAR(255) NULL,
  ip_address       VARCHAR(45)  NULL,
  details          TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_created (created_at),
  KEY idx_audit_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Account status, so the Administrator can deactivate employee accounts
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('Active','Deactivated') NOT NULL DEFAULT 'Active' AFTER role;

-- Note: the legacy orders.address column is no longer written by the application
-- (the restaurant does not offer delivery). It is kept so historical rows are not lost.
