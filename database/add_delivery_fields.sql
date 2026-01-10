-- Add delivery order fields to orders table
-- Run this SQL to add new columns

ALTER TABLE orders 
ADD COLUMN order_type ENUM('dine_in', 'delivery') DEFAULT 'dine_in' AFTER status,
ADD COLUMN customer_name VARCHAR(100) NULL AFTER order_type,
ADD COLUMN customer_phone VARCHAR(20) NULL AFTER customer_name,
ADD COLUMN customer_address TEXT NULL AFTER customer_phone;

-- Add index for better performance
ALTER TABLE orders ADD INDEX idx_order_type (order_type);
