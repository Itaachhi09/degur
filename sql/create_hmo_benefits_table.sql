-- Create HMO & Benefits table used by php/api/hmo_benefits.php
-- Run this in the hr441 database (phpMyAdmin or `mysql -u root -p hr441 < create_hmo_benefits_table.sql`)

CREATE TABLE IF NOT EXISTS hmo_benefits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  hmo_provider VARCHAR(255) NOT NULL,
  benefit_type VARCHAR(150) NOT NULL,
  benefit_details TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_hmo_employee (employee_id),
  CONSTRAINT fk_hmo_employee FOREIGN KEY (employee_id) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional sample data
-- INSERT INTO hmo_benefits (employee_id, hmo_provider, benefit_type, benefit_details) VALUES (1, 'Acme HMO', 'Primary', 'Primary coverage for family');
