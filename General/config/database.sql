-- Election System Database Schema
-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS application_logs;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS election_periods;
DROP TABLE IF EXISTS updates;
DROP TABLE IF EXISTS reviews;

-- Users table (for admin users)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('student', 'admin', 'super_admin', 'election_officer') DEFAULT 'student',
    status ENUM('active', 'inactive', 'suspended', 'pending', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    student_id VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    id_number VARCHAR(50) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    department VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    agreed_terms TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    profile_picture VARCHAR(255) NULL
);

-- Password resets table (for both users and students)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_type ENUM('student', 'user') NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    admission_number VARCHAR(30) NOT NULL,
    year_of_admission YEAR NOT NULL,
    year_of_graduation YEAR NOT NULL,
    hometown VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    position_id INT NOT NULL,
    biography TEXT NOT NULL,
    goals TEXT NOT NULL,
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
    application_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id)
);

-- Candidates table
CREATE TABLE IF NOT EXISTS candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    position_id INT NOT NULL,
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
    voter_id INT NOT NULL,
    election_id INT NOT NULL,
    candidate_id INT NOT NULL,
    position_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES students(id),
    FOREIGN KEY (election_id) REFERENCES election_periods(id),
    FOREIGN KEY (candidate_id) REFERENCES applications(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    UNIQUE KEY unique_vote (voter_id, position_id, election_id)
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

-- News/Updates table
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

-- Merged from gallery_sample.sql
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Merged from user_sessions_table.sql
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

-- Merged from news_qa_tables_fixed.sql
CREATE TABLE IF NOT EXISTS `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author_id` int(11) NOT NULL DEFAULT 1,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 1,
  `question_text` text NOT NULL,
  `status` enum('active','inactive','deleted') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL DEFAULT 1,
  `answer_text` text NOT NULL,
  `answered_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  UNIQUE KEY `question_unique` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for current candidates (for homepage and admin management)
CREATE TABLE IF NOT EXISTS current_candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    hierarchy_order INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
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