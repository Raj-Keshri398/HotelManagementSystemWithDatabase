
-- =======================
-- CREATE DATABASE
-- =======================
DROP DATABASE IF EXISTS hotel;
CREATE DATABASE hotel;
USE hotel;
-- =======================
-- HOTEL TABLE
-- =======================
CREATE TABLE hotel (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_code VARCHAR(20) NOT NULL UNIQUE,
    hotel_name VARCHAR(100) NOT NULL,
    address VARCHAR(200),
    pincode VARCHAR(10),
    city VARCHAR(50),
    country VARCHAR(50),
    contact_name VARCHAR(50),
    phone_no VARCHAR(20),
    email VARCHAR(100),
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO hotel (hotel_code, hotel_name, address, pincode, city, country, contact_name, phone_no, email, password)
VALUES
('H001', 'Heritage Palace', 'MG Road, City Center', '800001', 'Patna', 'India', 'Rajesh Kumar', '9876543210', 'heritage@hotel.com', 'pass123'),
('H002', 'Grand Plaza', 'Station Road', '800002', 'Patna', 'India', 'Suman Singh', '9876543211', 'grand@hotel.com', 'pass123');

-- =======================
-- GUEST TABLE
-- =======================
CREATE TABLE guest (
    guest_id INT AUTO_INCREMENT PRIMARY KEY,
    id_type VARCHAR(50),
    guest_id_no VARCHAR(50) UNIQUE,
    guest_photo VARCHAR(255),
    title VARCHAR(10),
    name VARCHAR(50),
    gender ENUM('Male','Female','Other'),
    dob DATE,
    phone_no VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(200),
    city VARCHAR(50),
    country VARCHAR(50),
    guest_status ENUM('welcome','bye') DEFAULT 'welcome',
    hotel_id INT,
    companions TEXT NULL,
    companion_photos TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO guest (id_type, guest_id_no, title, name, gender, dob, phone_no, email, address, city, country, hotel_id)
VALUES
('Passport', 'G001', 'Mr.', 'Amit Kumar', 'Male', '1990-05-10', '9876501234', 'amit@gmail.com', 'Street 1, Patna', 'Patna', 'India', 1),
('Aadhar', 'G002', 'Ms.', 'Neha Singh', 'Female', '1995-08-20', '9876501235', 'neha@gmail.com', 'Street 2, Patna', 'Patna', 'India', 1);

-- =======================
-- ROOM TABLE
-- =======================
CREATE TABLE room (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT,
    room_no VARCHAR(10),
    room_type VARCHAR(50),
    room_charge DECIMAL(10,2),
    description VARCHAR(100),
    booking_status ENUM('Booked','Available') DEFAULT 'Available',
    cleaning_status ENUM('Cleaned','Uncleaned') DEFAULT 'Cleaned',
    room_image VARCHAR(200),
    UNIQUE (hotel_id, room_no)
);

INSERT INTO room (hotel_id, room_no, room_type, room_charge, description, booking_status, cleaning_status, room_image)
VALUES
(1, '101', 'Deluxe', 2500.00, 'Spacious room with AC', 'Available', 'Uncleaned', 'pic1.jpg'),
(1, '102', 'Standard', 1500.00, 'Standard room with fan', 'Available', 'Cleaned', 'pic2.jpg'),
(2, '201', 'Suite', 5000.00, 'Luxury suite with king bed', 'Available', 'Uncleaned', 'pic3.jpg');

-- =======================
-- ROLE TABLE
-- =======================
CREATE TABLE role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE,
    role_description VARCHAR(100),
    hotel_id VARCHAR(20) NOT NULL
);

INSERT INTO role (role_name, role_description) VALUES
('Manager', 'Manages hotel operations'),
('Receptionist', 'Handles check-ins and bookings'),
('Cleaner', 'Clean the hotel room and floor');

-- =======================
-- STAFF TABLE
-- =======================
CREATE TABLE staff (
    staff_id VARCHAR(50) PRIMARY KEY,
    hotel_id INT NOT NULL,
    hotel_name VARCHAR(50) NOT NULL,
    role_id VARCHAR(30) NOT NULL,
    name VARCHAR(50) NOT NULL,
    dob DATE,
    gender ENUM('Male','Female','Other'),
    phone_no VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    salary DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO staff (hotel_id, role_id, first_name, last_name, dob, gender, phone_no, email, password, salary)
VALUES
(1, 1, 'Ramesh', 'Kumar', '1985-01-15', 'Male', '9000000001', 'ramesh@hotel.com', 'pass123', 50000.00),
(1, 3, 'Sita', 'Devi', '1992-03-20', 'Female', '9000000002', 'sita@hotel.com', 'pass123', 20000.00);

-- =======================
-- BOOKING TABLE
-- =======================
CREATE TABLE booking (
    booking_id VARCHAR(50) PRIMARY KEY,
    hotel_id INT NOT NULL,
    guest_id VARCHAR(100) NOT NULL,
    phone_no VARCHAR(20) NOT NULL,
    room_id VARCHAR(50) NOT NULL,
    deposit_payment DECIMAL(10,2) DEFAULT 0.00,
    room_charge DECIMAL(10,2) DEFAULT 0.00,
    check_in DATETIME NOT NULL,
    check_out DATETIME DEFAULT NULL,
    num_of_adults INT DEFAULT 1,
    num_of_children INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO booking (booking_id, hotel_id, guest_id, phone_no, room_id, deposit_payment, room_charge, check_in, check_out, num_of_adults, num_of_children)
VALUES
('BK001', 1, 1, '9876501234', 1, 1000.00, 2500.00, '2025-09-10 14:00:00', '2025-09-12 11:00:00', 2, 0),
('BK002', 1, 2, '9876501235', 2, 500.00, 1500.00, '2025-09-11 15:00:00', '2025-09-13 10:00:00', 1, 1);

-- =======================
-- BILL TABLE
-- =======================
CREATE TABLE bill (
    bill_id VARCHAR(20) PRIMARY KEY,
    booking_id VARCHAR(50) NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    room_no VARCHAR(20) NOT NULL,     
    room_charge DECIMAL(10,2) NOT NULL,     
    deposit_payment DECIMAL(10,2) DEFAULT 0.00, 
    service_charge DECIMAL(10,2) DEFAULT 0.00, 
    food_charge DECIMAL(10,2) DEFAULT 0.00,
    check_in DATETIME NOT NULL,         
    check_out DATETIME DEFAULT NULL,     
    stay_duration VARCHAR(100) DEFAULT NULL,  
    total_payment DECIMAL(10,2) NOT NULL,
    cgst DECIMAL(10,2) NULL,
    sgst DECIMAL(10,2) NULL,
    discount DECIMAL(10,2) NULL,
    final_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER sgst,
    payment_method VARCHAR(50) NOT NULL,
    transaction_no VARCHAR(100) NULL,
    hotel_id VARCHAR(20) NOT NULL,
    hotel_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO bill (bill_id, booking_id, guest_name, room_no, room_charge, deposit_payment, service_charge, food_charge, check_in, check_out, stay_duration, total_payment)
VALUES
('B001', 'BK001', 'Amit Kumar', '101', 2500.00, 1000.00, 200.00, 500.00, '2025-09-10 14:00:00', '2025-09-12 11:00:00', '2 nights', 3200.00),
('B002', 'BK002', 'Neha Singh', '102', 1500.00, 500.00, 100.00, 200.00, '2025-09-11 15:00:00', '2025-09-13 10:00:00', '2 nights', 1500.00);

-- =======================
-- ONLINE BOOKING TABLE
-- =======================

CREATE TABLE online_booking (
    online_booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    mobile_no VARCHAR(20) NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    room_price DECIMAL(10,2) NOT NULL,
    description TEXT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    number_of_people INT NOT NULL
);

INSERT INTO online_booking (name, mobile_no, aadhar_no, room_type, booking_date, booking_time, num_of_people)
VALUES
('Rohit Sharma', '9876501236', '123456789012', 'Deluxe', '2025-09-09', '10:30:00', 2),
('Priya Verma', '9876501237', '123456789013', 'Suite', '2025-09-10', '11:00:00', 3);

-- =======================
-- ROOM STATUS TABLE
-- =======================
CREATE TABLE room_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50),
    staff_id VARCHAR(100),
    hotel_id INT,
    cleaning_date_time DATETIME DEFAULT NULL,
    cleaning_status ENUM('Cleaned','Uncleaned') DEFAULT 'Cleaned'
);

INSERT INTO room_status (room_id, staff_id, status_date, status_time, cleaning_status)
VALUES
(1, 2, '2025-09-09', '09:00:00', 'Cleaned'),
(2, 2, '2025-09-09', '10:00:00', 'Cleaned');

-- =======================
-- LOGIN TABLE
-- =======================
CREATE TABLE login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) DEFAULT NULL,
    phone_no VARCHAR(20),
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    total_logins INT DEFAULT 0,
    today_logins INT DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    last_login_date DATE DEFAULT NULL,
    hotel_id INT,
    hotel_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO login (name, phone_no, email, password, hotel_id, hotel_name)
VALUES
('Ramesh Kumar', '9000000001', 'ramesh@hotel.com', 'pass123', 1, 'Heritage Palace'),
('Sita Devi', '9000000002', 'sita@hotel.com', 'pass123', 2, 'Grand Palace');
