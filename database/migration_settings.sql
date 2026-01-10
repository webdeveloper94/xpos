-- Settings System Database Migration
-- Add settings table and update orders table

-- 1. Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    setting_type ENUM('percentage', 'fixed', 'text') DEFAULT 'text',
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- 2. Insert initial settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('service_charge_percentage', '0', 'percentage', 'Qo\'shimcha xizmat haqi (%)'),
('delivery_fee_type', 'fixed', 'text', 'Yetkazib berish to\'lovi turi: fixed/percentage'),
('delivery_fee_value', '0', 'fixed', 'Yetkazib berish to\'lovi qiymati'),
('discount_percentage', '0', 'percentage', 'Chegirma (%)');

-- 3. Update orders table - add new columns
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS service_charge DECIMAL(10,2) DEFAULT 0 AFTER total_amount,
ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) DEFAULT 0 AFTER service_charge,
ADD COLUMN IF NOT EXISTS discount DECIMAL(10,2) DEFAULT 0 AFTER delivery_fee,
ADD COLUMN IF NOT EXISTS grand_total DECIMAL(10,2) DEFAULT 0 AFTER discount;

-- 4. Update existing orders to set grand_total = total_amount (backward compatibility)
UPDATE orders SET grand_total = total_amount WHERE grand_total = 0;
