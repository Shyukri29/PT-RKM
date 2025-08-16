-- Database
CREATE DATABASE IF NOT EXISTS hrm_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrm_app;

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Departments
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Employees
CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  department_id INT NULL,
  full_name VARCHAR(120) NOT NULL,
  position VARCHAR(120) NULL,
  hire_date DATE NULL,
  base_salary DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_employees_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_employees_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Payrolls
CREATE TABLE payrolls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  base_salary DECIMAL(12,2) NOT NULL,
  allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
  deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_pay DECIMAL(12,2) AS (base_salary + allowances - deductions) STORED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payroll_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Finance accounts (per user)
CREATE TABLE finance_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  type ENUM('cash','bank','ewallet','other') NOT NULL DEFAULT 'other',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_fa_user_name (user_id, name)
) ENGINE=InnoDB;

-- Finance categories (per user)
CREATE TABLE finance_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  kind ENUM('income','expense') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_fc_user_name_kind (user_id, name, kind)
) ENGINE=InnoDB;

-- Finance transactions (per user)
CREATE TABLE finance_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_id INT NOT NULL,
  category_id INT NOT NULL,
  trx_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ft_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ft_account FOREIGN KEY (account_id) REFERENCES finance_accounts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ft_category FOREIGN KEY (category_id) REFERENCES finance_categories(id) ON DELETE CASCADE,
  INDEX idx_ft_user_date (user_id, trx_date)
) ENGINE=InnoDB;

-- View ringkasan saldo akun
CREATE OR REPLACE VIEW v_account_balances AS
SELECT
  fa.id AS account_id,
  fa.user_id,
  fa.name AS account_name,
  fa.type AS account_type,
  COALESCE(SUM(CASE WHEN fc.kind='income' THEN ft.amount ELSE -ft.amount END),0) AS balance
FROM finance_accounts fa
LEFT JOIN finance_transactions ft ON ft.account_id = fa.id
LEFT JOIN finance_categories fc ON fc.id = ft.category_id
GROUP BY fa.id, fa.user_id, fa.name, fa.type;