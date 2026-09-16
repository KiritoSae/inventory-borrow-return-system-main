CREATE DATABASE IF NOT EXISTS inventory_system;

USE inventory_system;

-- ==========================================
-- DEPARTMENTS
-- ==========================================

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- USERS
-- System administrators and staff
-- ==========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Staff') DEFAULT 'Staff',
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- BORROWERS
-- Employees who borrow inventory
-- ==========================================

CREATE TABLE borrowers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrower_code VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    position VARCHAR(100),
    contact_number VARCHAR(50),
    email VARCHAR(150),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ==========================================
-- CATEGORIES
-- ==========================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- INVENTORY ITEMS
-- ==========================================

CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    item_code VARCHAR(50) NOT NULL UNIQUE,

    item_name VARCHAR(150) NOT NULL,

    category_id INT NOT NULL,

    serial_number VARCHAR(150),

    description TEXT,

    location VARCHAR(150),

    item_condition ENUM(
        'Excellent',
        'Good',
        'Fair',
        'Damaged'
    ) DEFAULT 'Good',

    status ENUM(
        'Available',
        'Borrowed',
        'Maintenance',
        'Lost',
        'Retired'
    ) DEFAULT 'Available',

    qr_code VARCHAR(255) UNIQUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ==========================================
-- BORROWING TRANSACTIONS
-- ==========================================

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    transaction_code VARCHAR(50) NOT NULL UNIQUE,

    item_id INT NOT NULL,

    borrower_id INT NOT NULL,

    processed_by INT NOT NULL,

    borrowed_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    due_date DATETIME NOT NULL,

    returned_date DATETIME NULL,

    condition_before ENUM(
        'Excellent',
        'Good',
        'Fair',
        'Damaged'
    ) DEFAULT 'Good',

    condition_after ENUM(
        'Excellent',
        'Good',
        'Fair',
        'Damaged'
    ) NULL,

    remarks TEXT,

    status ENUM(
        'Borrowed',
        'Returned',
        'Overdue',
        'Lost'
    ) DEFAULT 'Borrowed',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id)
        REFERENCES items(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (borrower_id)
        REFERENCES borrowers(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    FOREIGN KEY (processed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- ==========================================
-- INITIAL CATEGORIES
-- ==========================================

INSERT INTO categories
    (category_name, description)
VALUES
    ('Computer Equipment', 'Laptops, desktops and computer equipment'),
    ('Office Equipment', 'Printers, scanners and office equipment'),
    ('Audio Visual', 'Projectors, speakers and AV equipment'),
    ('Tools', 'Company tools and equipment'),
    ('Other', 'Other inventory items');


-- ==========================================
-- INITIAL DEPARTMENTS
-- ==========================================

INSERT INTO departments
    (department_name, description)
VALUES
    ('Information Technology', 'IT Department'),
    ('Human Resources', 'HR Department'),
    ('Finance', 'Finance Department'),
    ('Administration', 'Administration Department'),
    ('Operations', 'Operations Department');
