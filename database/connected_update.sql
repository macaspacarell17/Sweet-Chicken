USE sweet_chicken_db;

-- Add a stable product code so the customer, cashier, kitchen and admin
-- all refer to the same menu item.
ALTER TABLE menu ADD COLUMN code VARCHAR(60) NULL UNIQUE AFTER id;
ALTER TABLE menu MODIFY category VARCHAR(60) NULL;

-- Add order information needed by cashier/kitchen/customer tracking.
ALTER TABLE orders ADD COLUMN customer_name VARCHAR(100) NULL AFTER customer_id;
ALTER TABLE orders ADD COLUMN phone VARCHAR(30) NULL AFTER customer_name;
ALTER TABLE orders ADD COLUMN address VARCHAR(255) NULL AFTER phone;
ALTER TABLE orders ADD COLUMN order_type VARCHAR(30) NOT NULL DEFAULT 'Dine-In' AFTER address;
ALTER TABLE orders ADD COLUMN gcash_reference VARCHAR(100) NULL AFTER payment_method;

UPDATE menu SET code='legacy-2pc-fried-chicken' WHERE name='2pc Fried Chicken' AND code IS NULL;
UPDATE menu SET code='legacy-chicken-burger' WHERE name='Chicken Burger' AND code IS NULL;
UPDATE menu SET code='legacy-chicken-rice-meal' WHERE name='Chicken Rice Meal' AND code IS NULL;
UPDATE menu SET code='legacy-french-fries' WHERE name='French Fries' AND code IS NULL;
UPDATE menu SET code='legacy-soft-drinks' WHERE name='Soft Drinks' AND code IS NULL;

INSERT INTO menu(code,name,category,description,price,image,stock,status) VALUES
('barkada-7','Barkada Feast Combo - 7 pcs','Barkada Meals','7 pcs • Choose 3 flavors • 2 rice • 2 drinks',439,'chicken.png',100,'Available'),
('barkada-4','Barkada Feast Combo - 4 pcs','Barkada Meals','4 pcs • Choose 2 flavors • 2 rice • 2 drinks',299,'chicken.png',100,'Available'),
('chicken-3','3pcs Chicken Combo','Flavored Chicken Combo','Choose 1 flavor',209,'chicken.png',100,'Available'),
('chicken-2','2pcs Chicken Combo','Flavored Chicken Combo','Choose 1 flavor',169,'chicken.png',100,'Available'),
('chicken-pops','Chicken Pops Combo','Flavored Chicken Combo','Choose 1 flavor',119,'chicken.png',100,'Available'),
('soda-15l','Soda 1.5L','Soda','Coke / Sprite / Royal',85,'fries.png',100,'Available'),
('soda-mismo','Soda Mismo','Soda','Coke / Sprite / Royal',25,'fries.png',100,'Available'),
('graham-blueberry','Graham Ice Cream - Blueberry','Dessert','Blueberry',39,'pasta.png',100,'Available'),
('graham-strawberry','Graham Ice Cream - Strawberry','Dessert','Strawberry',39,'pasta.png',100,'Available'),
('graham-mango','Graham Ice Cream - Mango','Dessert','Mango',39,'pasta.png',100,'Available'),
('chao-fan','Chao Fan Rice','Add Ons','Add-on',30,'pasta.png',100,'Available'),
('plain-rice','Plain Rice','Add Ons','Add-on',20,'pasta.png',100,'Available'),
('garlic-mayo','Garlic Mayo Dip','Add Ons','Add-on',20,'pasta.png',100,'Available'),
('extra-chicken','Extra Chicken','Add Ons','Add-on',59,'chicken.png',100,'Available'),
('extra-sauce','Extra Sauce','Add Ons','Add-on',15,'pasta.png',100,'Available'),
('juice-pitcher','Juice Pitcher','Juice','Iced Tea / Red Tea',99,'pasta.png',100,'Available'),
('juice-cup','Juice Cup','Juice','Iced Tea / Red Tea',25,'pasta.png',100,'Available')
ON DUPLICATE KEY UPDATE name=VALUES(name),category=VALUES(category),description=VALUES(description),price=VALUES(price),image=VALUES(image),status=VALUES(status);
