-- Database Schema for Ticketing, Admission and Ride Management ERP

CREATE DATABASE IF NOT EXISTS funfair_erp;
USE funfair_erp;

-- 1. Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

-- 3. Role Permissions Mapping
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- 4. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- 5. Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    capacity INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Tickets Table
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT, -- If booked by a specific user/student
    ticket_code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('paid', 'used', 'cancelled') DEFAULT 'paid',
    booked_by INT, -- Salesperson ID
    customer_name VARCHAR(100) DEFAULT 'Walk-in',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booked_by) REFERENCES users(id)
);

-- 7. Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT,
    ride_ticket_id INT, -- Optional, if for a ride
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

-- 8. Admission Logs
CREATE TABLE IF NOT EXISTS admission_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    operator_id INT NOT NULL,
    entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (operator_id) REFERENCES users(id)
);

-- 9. Swings/Rides Table
CREATE TABLE IF NOT EXISTS swings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    duration INT, -- in minutes
    capacity INT,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 10. Ride Tickets
CREATE TABLE IF NOT EXISTS ride_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    swing_id INT NOT NULL,
    user_id INT,
    ticket_code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('paid', 'used', 'cancelled') DEFAULT 'paid',
    booked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (swing_id) REFERENCES swings(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booked_by) REFERENCES users(id)
);

-- 11. Ride Usage Logs
CREATE TABLE IF NOT EXISTS ride_usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_ticket_id INT NOT NULL,
    operator_id INT NOT NULL,
    usage_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_ticket_id) REFERENCES ride_tickets(id),
    FOREIGN KEY (operator_id) REFERENCES users(id)
);

-- 12. Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Seed Initial Data
INSERT IGNORE INTO roles (name) VALUES ('Super Admin'), ('Admin'), ('Event Manager'), ('Salesperson'), ('Gate Operator'), ('Ride Operator'), ('User/Student');

-- Default Super Admin (password: admin123)
INSERT IGNORE INTO users (username, password, full_name, role_id) VALUES ('admin', '$2y$10$GnGAcbwoUOw.rxpy64/JtuL318B0N.gcvBggbsY5EMS6NVsetlQSS', 'Super Admin', 1);

-- Permissions (Basic examples)
INSERT IGNORE INTO permissions (name, slug) VALUES 
('Manage Users', 'manage_users'),
('Manage Roles', 'manage_roles'),
('Manage Events', 'manage_events'),
('Book Tickets', 'book_tickets'),
('Validate Entry', 'validate_entry'),
('Manage Swings', 'manage_swings'),
('Validate Rides', 'validate_rides'),
('View Reports', 'view_reports');

-- Assign all permissions to Super Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 1, id FROM permissions;

-- Sample Data
INSERT IGNORE INTO events (title, description, event_date, price, capacity, status) VALUES 
('Annual Funfair 2024', 'The biggest funfair of the year with amazing attractions.', '2024-12-25', 50.00, 1000, 'active'),
('Spring Festival', 'Celebrate the spring with music and games.', '2024-04-15', 25.00, 500, 'active');

INSERT IGNORE INTO swings (name, price, duration, capacity, image, status) VALUES 
('Giant Ferris Wheel', 15.00, 15, 60, 'ferris_wheel.png', 'active'),
('Speed Roller Coaster', 25.00, 5, 20, 'roller_coaster.png', 'active'),
('Neon Bumper Cars', 10.00, 10, 16, 'bumper_cars.png', 'active'),
('Royal Carousel', 8.00, 8, 30, 'carousel.png', 'active'),
('Pirate Revenge Ship', 12.00, 7, 40, 'pirate_ship.png', 'active'),
('Sky Drop Tower', 20.00, 3, 12, 'drop_tower.png', 'active'),
('Crazy Spinning Teacups', 7.00, 6, 24, 'teacups.png', 'active'),
('Wave Swinger', 10.00, 8, 32, 'wave_swinger.png', 'active'),
('Jungle Log Flume', 18.00, 10, 4, 'log_flume.png', 'active'),
('Haunted Ghost Train', 12.00, 12, 8, 'horror_house.png', 'active'),
('Panoramic Sky Ride', 15.00, 20, 2, 'sky_ride.png', 'active'),
('Giant Pendulum Frisbee', 22.00, 6, 24, 'giant_frisbee.png', 'active');
