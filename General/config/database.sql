-- Election System Database Schema
-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS message_replies;
DROP TABLE IF EXISTS admin_messages;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS answers;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS gallery;
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS application_logs;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS pin_resets;
DROP TABLE IF EXISTS email_verifications;
DROP TABLE IF EXISTS elected_officials;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS election_periods;
DROP TABLE IF EXISTS updates;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS current_candidates;
DROP TABLE IF EXISTS deleted_items;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS news_images;
DROP TABLE IF EXISTS updates_images;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    gender VARCHAR(10),
    course_level VARCHAR(50),
    role ENUM('student', 'admin', 'super_admin', 'election_officer') DEFAULT 'student',
    status ENUM('active', 'inactive', 'suspended', 'pending', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_number VARCHAR(50) UNIQUE,
    two_factor_pin VARCHAR(10) DEFAULT NULL
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    student_id VARCHAR(30) UNIQUE,
    email VARCHAR(100) UNIQUE,
    id_number VARCHAR(50) UNIQUE,
    phone_number VARCHAR(20),
    department VARCHAR(100),
    gender VARCHAR(10),
    course_level VARCHAR(50),
    password VARCHAR(255),
    agreed_terms TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive', 'suspended', 'pending', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profile_picture VARCHAR(255),
    two_factor_pin VARCHAR(10) DEFAULT NULL
);

-- Election periods table
CREATE TABLE IF NOT EXISTS election_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('upcoming', 'active', 'ended') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Positions table
CREATE TABLE IF NOT EXISTS positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    student_id VARCHAR(20),
    admission_number VARCHAR(30),
    year_of_admission YEAR,
    year_of_graduation YEAR,
    hometown VARCHAR(100),
    phone VARCHAR(20),
    position_id INT,
    biography TEXT,
    goals TEXT,
    experience TEXT,
    skills TEXT,
    image1 VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    vetting_status ENUM('pending','verified','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id)
);

-- Application logs table
CREATE TABLE IF NOT EXISTS application_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    action VARCHAR(50),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id)
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    position_id INT,
    status ENUM('active', 'inactive', 'disqualified') DEFAULT 'active',
    vetting_status ENUM('pending','verified','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (position_id) REFERENCES positions(id)
);

-- Votes table
CREATE TABLE IF NOT EXISTS votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voter_id INT,
    election_id INT,
    candidate_id INT,
    position_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES students(id),
    FOREIGN KEY (election_id) REFERENCES election_periods(id),
    FOREIGN KEY (candidate_id) REFERENCES applications(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    UNIQUE KEY unique_vote (voter_id, position_id, election_id)
);

-- Gallery table
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- News table with all enhanced features
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255), -- legacy single image, optional
    author_id INT NOT NULL DEFAULT 1,
    status ENUM('draft','published','archived') DEFAULT 'draft',
    publish_date DATE DEFAULT NULL, -- for scheduling
    is_important TINYINT(1) DEFAULT 0, -- for newsletter
    newsletter_sent TINYINT(1) DEFAULT 0, -- track if newsletter sent
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Updates table
CREATE TABLE IF NOT EXISTS updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    author VARCHAR(100),
    additional_info TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 1,
    question_text TEXT NOT NULL,
    status ENUM('active','inactive','deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Answers table
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    author_id INT NOT NULL DEFAULT 1,
    answer_text TEXT NOT NULL,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY question_unique (question_id)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(30) NOT NULL,
    content TEXT NOT NULL,
    rating INT DEFAULT 5,
    status ENUM('pending','approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Current candidates table
CREATE TABLE IF NOT EXISTS current_candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    hierarchy_order INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User sessions table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    user_type ENUM('student','admin') NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    breach_flag TINYINT(1) DEFAULT 0
);

-- Elected officials table
CREATE TABLE IF NOT EXISTS elected_officials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    position_name VARCHAR(255) NOT NULL,
    election_title VARCHAR(255) NOT NULL,
    term_start_date DATE,
    term_end_date DATE,
    elected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Email verifications table
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pin resets table
CREATE TABLE IF NOT EXISTS pin_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_type ENUM('student', 'user') NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    sender_student_id INT,
    recipient_admin_id INT,
    parent_message_id INT,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Admin messages table
CREATE TABLE IF NOT EXISTS admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_admin_id INT NOT NULL,
    recipient_admin_id INT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Message replies table
CREATE TABLE IF NOT EXISTS message_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_type ENUM('student','admin') NOT NULL,
    message_id INT NOT NULL,
    sender_admin_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_admin_id) REFERENCES users(id) ON DELETE CASCADE
    -- message_id references messages.id or admin_messages.id depending on message_type
);

-- Insert sample positions
INSERT INTO positions (position_name, description, requirements, responsibilities, status) VALUES
('Student Body President', 
'Lead the student body and represent student interests at the highest level.',
'Must be a full-time student\nMinimum GPA of 3.0\nAt least one year of leadership experience\nGood communication skills',
'Preside over student council meetings\nRepresent students in administrative meetings\nOrganize major campus events\nManage student council budget',
'active'),

('Vice President', 
'Support the President and oversee specific areas of student government.',
'Must be a full-time student\nMinimum GPA of 3.0\nLeadership experience preferred\nStrong organizational skills',
'Assist the President\nOversee committee operations\nCoordinate with student organizations\nHandle presidential duties when needed',
'active'),

('Secretary', 
'Maintain official records and handle communications for the student government.',
'Must be a full-time student\nMinimum GPA of 2.8\nStrong writing and organizational skills\nAttention to detail',
'Record meeting minutes\nMaintain official documents\nHandle official communications\nManage student government records',
'active'),

('Treasurer', 
'Manage the financial affairs of the student government.',
'Must be a full-time student\nMinimum GPA of 2.8\nBasic accounting knowledge\nStrong organizational skills',
'Manage student government budget\nTrack expenses and income\nPrepare financial reports\nOversee fundraising activities',
'active'),

('Public Relations Officer', 
'Handle communications and promote student government activities.',
'Must be a full-time student\nMinimum GPA of 2.8\nStrong communication skills\nSocial media experience',
'Manage social media accounts\nCreate promotional materials\nHandle press releases\nOrganize publicity events',
'active');

-- Insert sample election period
INSERT INTO election_periods (title, start_date, end_date, status) VALUES
('Spring 2024 Student Government Elections', 
'2024-03-01 00:00:00', 
'2024-03-15 23:59:59', 
'upcoming');

-- Insert sample news/updates
INSERT INTO updates (title, content, author, status) VALUES
('Election Period Announced', 
'The Spring 2024 Student Government Elections will be held from March 1 to March 15, 2024. All eligible students are encouraged to participate.',
'Admin User',
'published'),

('Application Period Open', 
'Applications for student government positions are now being accepted. Visit the Positions page to learn more about available roles and requirements.',
'Admin User',
'published'),

('Important Dates', 
'Key dates for the upcoming elections:\n- Application Deadline: February 15, 2024\n- Campaign Period: February 16-29, 2024\n- Voting Period: March 1-15, 2024\n- Results Announcement: March 16, 2024',
'Admin User',
'published'); 

-- Insert sample data from gallery_sample.sql
INSERT INTO gallery (filename, description, uploaded_at) VALUES
('election_booth.jpg', 'Students casting their votes at the election booth.', NOW()),
('campaign_posters.jpg', 'Colorful campaign posters displayed around campus.', NOW()),
('vote_counting.jpg', 'Officials counting votes after the election.', NOW()),
('candidate_speech.jpg', 'A candidate delivering a speech to fellow students.', NOW()),
('election_results.jpg', 'Announcement of the election results in the main hall.', NOW()),
('voter_education.jpg', 'Voter education session for first-time voters.', NOW());

-- Insert sample data from news_qa_tables_fixed.sql
INSERT INTO `news` (`title`, `content`, `author_id`, `status`, `created_at`) VALUES
('Election System Launch', 'We are excited to announce the launch of our new online election system! This system will provide a more efficient and transparent way for students to participate in the election process. All registered students can now apply for positions and cast their votes online.', 1, 'published', NOW()),
('Important Update: Application Deadline Extended', 'Due to high demand and technical considerations, we have extended the application deadline for all positions by one week. The new deadline is now March 15th, 2024. Please ensure all applications are submitted before this date.', 1, 'published', NOW()),
('New Features Added to the System', 'We have added several new features to improve your experience: 1) Real-time application status updates, 2) Enhanced security measures, 3) Mobile-responsive design, 4) News and announcements section. Stay tuned for more updates!', 1, 'published', NOW());

INSERT INTO `questions` (`user_id`, `question_text`, `status`, `created_at`) VALUES
(1, 'What are the requirements to apply for the President position?', 'active', NOW()),
(1, 'How long does it take to process an application?', 'active', NOW()),
(1, 'Can I apply for multiple positions at the same time?', 'active', NOW());

INSERT INTO `answers` (`question_id`, `author_id`, `answer_text`, `answered_at`) VALUES
(1, 1, 'To apply for the President position, you must meet the following requirements: 1) Be a registered student in good standing, 2) Have a minimum GPA of 3.0, 3) Have completed at least 2 semesters, 4) Submit a complete application with all required documents. Please refer to the positions page for detailed requirements.', NOW()),
(2, 1, 'Application processing typically takes 3-5 business days. You will receive an email notification once your application has been reviewed. If additional information is required, we will contact you directly.', NOW());

-- Insert sample data for reviews
INSERT INTO reviews (student_name, student_id, content, status, created_at) VALUES
('John Doe', 'S12345', 'Great experience!', 'approved', NOW()),
('Jane Smith', 'S54321', 'I had a great time participating in the election process.', 'approved', NOW());

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(30) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending','approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rating INT DEFAULT 5
);

-- Table for student messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    sender_student_id INT NULL DEFAULT NULL,
    recipient_admin_id INT NULL DEFAULT NULL,
    parent_message_id INT NULL DEFAULT NULL,
    CONSTRAINT fk_parent_message FOREIGN KEY (parent_message_id) REFERENCES messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_sender_student FOREIGN KEY (sender_student_id) REFERENCES students(id) ON DELETE SET NULL,
    CONSTRAINT fk_recipient_admin FOREIGN KEY (recipient_admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table for admin-to-admin messages
CREATE TABLE IF NOT EXISTS admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_admin_id INT NOT NULL,
    recipient_admin_id INT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for threaded replies to messages (admin replies only)
CREATE TABLE IF NOT EXISTS message_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_type ENUM('student','admin') NOT NULL,
    message_id INT NOT NULL,
    sender_admin_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_admin_id) REFERENCES users(id) ON DELETE CASCADE
    -- message_id references messages.id or admin_messages.id depending on message_type
);

-- Table for storing elected officials (winners)
CREATE TABLE IF NOT EXISTS elected_officials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    position_name VARCHAR(255) NOT NULL,
    election_title VARCHAR(255) NOT NULL,
    term_start_date DATE,
    term_end_date DATE,
    elected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Table for email verification codes
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for PIN reset requests
CREATE TABLE IF NOT EXISTS pin_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('student', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for deleted items
CREATE TABLE IF NOT EXISTS deleted_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type VARCHAR(50),
    item_identifier VARCHAR(100),
    item_data TEXT,
    deleted_by_user_id INT,
    deleted_by_user_name VARCHAR(100),
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default value for application portal status if not present
INSERT INTO system_settings (setting_key, setting_value)
VALUES ('application_portal_status', 'closed')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Table to store candidate likes (by user or guest IP)
CREATE TABLE IF NOT EXISTS candidate_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (candidate_id, user_id, ip_address),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Table to store candidate bookmarks (only for logged-in users)
CREATE TABLE IF NOT EXISTS candidate_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bookmark (candidate_id, user_id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add is_important and newsletter_sent columns to news table
ALTER TABLE news ADD COLUMN is_important TINYINT(1) DEFAULT 0;
ALTER TABLE news ADD COLUMN newsletter_sent TINYINT(1) DEFAULT 0;

-- Table for multiple images per news item (gallery support)
CREATE TABLE IF NOT EXISTS news_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
);

-- Table for multiple images per update (updates_images)
CREATE TABLE IF NOT EXISTS updates_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    update_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (update_id) REFERENCES updates(id) ON DELETE CASCADE
);