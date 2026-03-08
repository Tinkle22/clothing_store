-- migrations/003_add_phone_to_orders.sql
ALTER TABLE orders ADD COLUMN phone VARCHAR(20) AFTER user_id;
