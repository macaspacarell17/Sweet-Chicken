-- ==========================================
-- Sweet Chicken Restaurant Management System
-- Database
-- ==========================================

CREATE DATABASE IF NOT EXISTS sweet_chicken_db;
USE sweet_chicken_db;

-- ==========================
-- USERS
-- ==========================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','cashier','kitchen','customer')
        NOT NULL DEFAULT 'customer',

    otp VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==================================
-- DEFAULT ADMIN ACCOUNT
-- Password: Admin123!
-- ==================================

INSERT INTO users
(fullname,email,password,role)
VALUES
(
'System Administrator',
'admin@sweetchicken.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9Ll8mGJrV8LZ4YFQFj6N7a',
'admin'
);

-- ==========================
-- MENU
-- ==========================

CREATE TABLE menu (

    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    category ENUM(
        'Chicken',
        'Burger',
        'Rice Meal',
        'Drink',
        'Dessert',
        'Sides'
    ),

    description TEXT,

    price DECIMAL(10,2) NOT NULL,

    image VARCHAR(255),

    stock INT DEFAULT 0,

    status ENUM(
        'Available',
        'Unavailable'
    ) DEFAULT 'Available'
);

INSERT INTO menu
(name,category,description,price,image,stock)

VALUES

('2pc Fried Chicken','Chicken','Crispy Fried Chicken',180,'chicken1.png',100),

('Chicken Burger','Burger','Chicken Burger Deluxe',150,'burger1.png',100),

('Chicken Rice Meal','Rice Meal','Chicken with Rice',170,'meal1.png',100),

('French Fries','Sides','Regular Fries',80,'fries.png',100),

('Soft Drinks','Drink','Coke',50,'coke.png',100);

-- ==========================
-- CART
-- ==========================

CREATE TABLE cart (

    id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT,

    menu_id INT,

    quantity INT DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(customer_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY(menu_id)
        REFERENCES menu(id)
        ON DELETE CASCADE
);

-- ==========================
-- ORDERS
-- ==========================

CREATE TABLE orders (

    id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT,

    total DECIMAL(10,2),

    payment_method ENUM(
        'Cash',
        'GCash'
    ),

    status ENUM(

        'Pending',

        'Preparing',

        'Ready',

        'Completed',

        'Cancelled'

    ) DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(customer_id)
        REFERENCES users(id)
);

-- ==========================
-- ORDER ITEMS
-- ==========================

CREATE TABLE order_items (

    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT,

    menu_id INT,

    quantity INT,

    price DECIMAL(10,2),

    FOREIGN KEY(order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    FOREIGN KEY(menu_id)
        REFERENCES menu(id)
);

-- ==========================
-- PAYMENTS
-- ==========================

CREATE TABLE payments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT,

    amount DECIMAL(10,2),

    payment_method ENUM(
        'Cash',
        'GCash'
    ),

    payment_status ENUM(
        'Paid',
        'Unpaid'
    ) DEFAULT 'Paid',

    payment_date TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(order_id)
        REFERENCES orders(id)
);

-- ==========================
-- INVENTORY
-- ==========================

CREATE TABLE inventory (

    id INT AUTO_INCREMENT PRIMARY KEY,

    ingredient VARCHAR(100),

    quantity INT,

    unit VARCHAR(30),

    updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO inventory
(ingredient,quantity,unit)

VALUES

('Chicken',100,'pcs'),
('Cooking Oil',50,'liters'),
('Burger Bun',100,'pcs'),
('French Fries',80,'packs'),
('Soft Drinks',100,'bottles');

-- ==========================
-- SALES REPORT
-- ==========================

CREATE TABLE sales (

    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT,

    total DECIMAL(10,2),

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(order_id)
        REFERENCES orders(id)
);