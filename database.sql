CREATE DATABASE IF NOT EXISTS clothing_store;
USE clothing_store;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') DEFAULT 'customer',
    `address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `image` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `price` DECIMAL(10, 2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `sizes` VARCHAR(255), -- Comma separated like 'S,M,L,XL'
    `image` VARCHAR(255),
    `is_featured` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `shipping_address` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `size` VARCHAR(20),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
);

CREATE TABLE `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `size` VARCHAR(20),
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);


INSERT INTO `categories` (`name`, `slug`, `description`) VALUES
('Tops', 'tops', 'T-shirts, blouses, and shirts.'),
('Bottoms', 'bottoms', 'Pants, skirts, and shorts.'),
('Outerwear', 'outerwear', 'Jackets and coats.'),
('Knitwear', 'knitwear', 'Sweaters and cardigans.');

INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `stock`, `sizes`, `image`, `is_featured`)
VALUES
(1, 'Classic Linen Shirt', 'classic-linen-shirt', 'A lightweight, breathable linen shirt perfect for summer.', 45.00, 50, 'S,M,L,XL', 'linen-shirt.jpg', TRUE),
(2, 'Pleated Trousers', 'pleated-trousers', 'Relaxed fit pleated trousers for a timeless look.', 60.00, 40, '30,32,34,36', 'pleated-trousers.jpg', TRUE),
(4, 'Chunky Knit Sweater', 'chunky-knit-sweater', 'Stay warm with this oversized chunky knit sweater.', 75.00, 30, 'S,M,L', 'knit-sweater.jpg', FALSE),
(3, 'Wool Blend Coat', 'wool-blend-coat', 'Elegant wool blend coat for cold weather.', 120.00, 20, 'M,L,XL', 'wool-coat.jpg', TRUE),
(1, 'Silk Blouse', 'silk-blouse', 'A soft, elegant silk blouse suitable for any occasion.', 55.00, 25, 'XS,S,M,L', 'silk-blouse.jpg', FALSE);
