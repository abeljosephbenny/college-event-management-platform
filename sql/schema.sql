-- ============================================================
-- Campus Event Management Platform — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS campus_events
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campus_events;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(100)  UNIQUE NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    role          ENUM('Student', 'Organizer', 'Administrator') NOT NULL,
    is_verified   BOOLEAN       DEFAULT FALSE,
    phone         VARCHAR(15),
    year          INT,
    department    VARCHAR(100),
    admission_number VARCHAR(50),
    profile_pic   VARCHAR(255)  DEFAULT NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ============================================================
-- EVENTS TABLE
-- ============================================================
CREATE TABLE events (
    event_id                INT AUTO_INCREMENT PRIMARY KEY,
    title                   VARCHAR(200)  NOT NULL,
    category_id             INT           NOT NULL,
    approval_doc_path       VARCHAR(255),
    is_published            BOOLEAN       DEFAULT FALSE,
    event_date              DATE,
    start_time              TIME,
    end_time                TIME,
    application_deadline    DATETIME,
    venue                   VARCHAR(100),
    place                   VARCHAR(100),
    total_slots             INT,
    slots_left              INT,
    description             TEXT,
    created_at              TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    created_by              INT,
    organizer               VARCHAR(100),
    poc_id                  INT,
    is_volunteer_required   BOOLEAN       DEFAULT FALSE,
    registration_fee        DECIMAL(10,2) DEFAULT 0.00,
    participant_whatsapp_link VARCHAR(255),
    volunteer_whatsapp_link   VARCHAR(255),

    FOREIGN KEY (created_by)   REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (category_id)  REFERENCES categories(category_id),

    INDEX idx_events_published  (is_published),
    INDEX idx_events_date       (event_date),
    INDEX idx_events_category   (category_id),
    INDEX idx_events_created_by (created_by)
) ENGINE=InnoDB;

-- ============================================================
-- REGISTRATIONS TABLE
-- ============================================================
CREATE TABLE registrations (
    reg_id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id               INT,
    event_id              INT,
    registration_code     VARCHAR(20)   UNIQUE,
    type                  ENUM('Participant', 'Volunteer') NOT NULL,
    vol_approval_status   ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    attendance_marked     BOOLEAN       DEFAULT FALSE,
    registered_at         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)  REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,

    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_reg_event  (event_id),
    INDEX idx_reg_user   (user_id)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA — Categories
-- ============================================================
INSERT INTO categories (name) VALUES
    ('Technical'),
    ('Cultural'),
    ('Sports'),
    ('Workshop'),
    ('Seminar'),
    ('Other');

-- ============================================================
-- SEED DATA — Default Admin Account
-- Password: admin123 (bcrypt hash)
-- IMPORTANT: Change this password after first login!
-- ============================================================
INSERT INTO users (name, email, password, role, is_verified) VALUES
    ('Platform Admin',
     'admin@tkmce.ac.in',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Administrator',
     TRUE);
