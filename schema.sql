DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;


-- USERS Table [cite: 2]
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY, -- [cite: 3]
    name VARCHAR(100) NOT NULL, -- [cite: 3]
    email VARCHAR(100) UNIQUE NOT NULL, -- [cite: 3]
    password VARCHAR(255) NOT NULL, -- [cite: 3]
    role ENUM('Student', 'Organizer', 'Administrator') NOT NULL, -- [cite: 3]
    is_verified BOOLEAN DEFAULT FALSE, -- [cite: 3]
    phone VARCHAR(15), -- [cite: 3]
    year INT, -- [cite: 3]
    department VARCHAR(100), -- [cite: 3]
    admission_number VARCHAR(50) -- [cite: 3]
);

-- CATEGORIES Table 
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- EVENTS Table [cite: 5]
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY, -- [cite: 6]
    title VARCHAR(200) NOT NULL, -- [cite: 6]
    category_id INT(50) NOT NULL,
    approval_doc_path VARCHAR(255), -- [cite: 6]
    is_published BOOLEAN DEFAULT FALSE, -- [cite: 6]
    event_date DATE, -- [cite: 6]
    start_time TIME, -- [cite: 6]
    end_time TIME, -- [cite: 6]
    application_deadline DATETIME, -- [cite: 6]
    venue VARCHAR(100), -- [cite: 6]
    place VARCHAR(100), -- [cite: 6]
    total_slots INT, -- [cite: 6]
    slots_left INT, -- [cite: 6]
    description TEXT, -- [cite: 6]
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- [cite: 6]
    created_by INT, -- [cite: 6]
    organizer VARCHAR(100), -- [cite: 6]
    poc_id INT, -- [cite: 6]
    is_volunteer_required BOOLEAN DEFAULT FALSE, -- [cite: 6]
    participant_whatsapp_link VARCHAR(255), -- [cite: 6]
    volunteer_whatsapp_link VARCHAR(255), -- [cite: 6]
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)

);

-- REGISTRATIONS Table [cite: 7]
CREATE TABLE registrations (
    reg_id INT AUTO_INCREMENT PRIMARY KEY, -- [cite: 8]
    user_id INT, -- [cite: 8]
    event_id INT, -- [cite: 8]
    type ENUM('Participant', 'Volunteer') NOT NULL, -- [cite: 8]
    vol_approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending', -- [cite: 8]
    attendance_marked BOOLEAN DEFAULT FALSE, -- [cite: 8]
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);