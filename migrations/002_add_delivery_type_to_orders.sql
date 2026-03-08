-- migrations/002_add_delivery_type_to_orders.sql
ALTER TABLE orders ADD COLUMN delivery_type ENUM('pickup', 'yango') DEFAULT 'pickup' AFTER status;
