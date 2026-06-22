-- Create database
CREATE DATABASE IF NOT EXISTS smart_meal_planner;
USE smart_meal_planner;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    age TINYINT UNSIGNED NULL,
    weight DECIMAL(5,2) NULL,
    height DECIMAL(5,2) NULL,
    gender ENUM('male', 'female', 'prefer_not') NULL,
    goal ENUM('lose_weight', 'maintain', 'gain_muscle', 'improve_health') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Foods table
CREATE TABLE IF NOT EXISTS foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    calories INT NOT NULL,
    protein DECIMAL(5,2) NOT NULL,
    carbs DECIMAL(5,2) NOT NULL,
    fat DECIMAL(5,2) NOT NULL,
    meal_type VARCHAR(20) NOT NULL
);

-- Insert sample foods
INSERT INTO foods (name, calories, protein, carbs, fat, meal_type) VALUES
    ('Firfir (Injera with Berbere)', 310, 9, 52, 8, 'breakfast'),
    ('Chechebsa (Kita Firfir)', 420, 11, 58, 16, 'breakfast'),
    ('Kinche (Cracked Wheat Porridge)', 280, 9, 52, 5, 'breakfast'),
    ('Genfo (Barley Porridge with Niter Kibbeh)', 350, 10, 55, 11, 'breakfast'),
    ('Enqulal Firfir (Ethiopian Scrambled Eggs)', 390, 20, 30, 22, 'breakfast'),
    ('Doro Wat (Spiced Chicken Stew) with Injera', 580, 42, 48, 22, 'lunch'),
    ('Shiro Wat (Chickpea Stew) with Injera', 460, 18, 72, 12, 'lunch'),
    ('Misir Wat (Red Lentil Stew) with Injera', 420, 16, 70, 8, 'lunch'),
    ('Tibs (Sautéed Beef with Vegetables)', 520, 38, 18, 30, 'lunch'),
    ('Gomen Besiga (Collard Greens with Beef)', 390, 28, 22, 18, 'lunch'),
    ('Kitfo (Ethiopian Beef Tartare) with Injera', 610, 48, 38, 28, 'dinner'),
    ('Yetsom Beyaynetu (Fasting Platter)', 480, 18, 68, 16, 'dinner'),
    ('Zigni (Beef Stew) with Injera', 560, 40, 45, 22, 'dinner'),
    ('Awaze Tibs (Spicy Pan-fried Lamb)', 590, 44, 12, 38, 'dinner'),
    ('Atkilt Wat (Cabbage, Potato & Carrot Stew)', 320, 8, 55, 9, 'dinner'),
    ('Kolo (Roasted Barley & Groundnut Mix)', 210, 8, 28, 9, 'snack'),
    ('Sambusa (Ethiopian Pastry with Lentils)', 280, 9, 34, 13, 'snack'),
    ('Besso (Roasted Barley Flour Drink)', 160, 5, 30, 3, 'snack'),
    ('Dabo Kolo (Crunchy Wheat Snack)', 195, 5, 32, 6, 'snack'),
    ('Fresh Mango & Papaya Plate', 130, 2, 32, 0.5, 'snack');




-- Meal plan table
CREATE TABLE IF NOT EXISTS meal_plan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    day VARCHAR(10) NOT NULL,
    meal_type VARCHAR(20) NOT NULL,
    food_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- Progress table
CREATE TABLE IF NOT EXISTS progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);