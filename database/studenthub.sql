CREATE DATABASE IF NOT EXISTS studenthub
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE studenthub;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  mobile VARCHAR(24) NULL,
  password_hash VARCHAR(255) NOT NULL,
  course VARCHAR(80) NULL,
  semester TINYINT UNSIGNED NULL,
  gender VARCHAR(40) NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_password_resets_user_id (user_id),
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL UNIQUE,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  course VARCHAR(80) NOT NULL,
  semester TINYINT UNSIGNED NOT NULL,
  status ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  event_date DATE NOT NULL,
  category VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  poster VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_event (student_id, event_id),
  CONSTRAINT fk_reg_student
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_event
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  details VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demo accounts
INSERT INTO users
(name, email, mobile, password_hash, course, semester, gender, role, created_at)
VALUES
('StudentHub Admin', 'admin@studenthub.test', '9999999999',
 '$2y$12$47t72QnAgFBKourtxjTNIux8ZerCcWk8Y5gUyWdLB4uMaUAiGDvYK',
 'CSE', 8, 'Prefer not to say', 'admin', NOW()),

('Demo Student', 'student@studenthub.test', '9888888888',
 '$2y$12$NhdRo7PcQvrwGQv3JlyicOcwVGjK4XSgm8nJF67WOwJpDr2Rb8vAS',
 'AIML', 2, 'Prefer not to say', 'student', NOW());

INSERT INTO students
(user_id, full_name, email, course, semester, status)
VALUES
(1, 'StudentHub Admin', 'admin@studenthub.test', 'CSE', 8, 'active'),
(2, 'Demo Student', 'student@studenthub.test', 'AIML', 2, 'active'),
(NULL, 'Aarav Patel', 'aarav@studenthub.test', 'AIML', 1, 'active'),
(NULL, 'Diya Shah', 'diya@studenthub.test', 'IT', 2, 'active'),
(NULL, 'Rohan Desai', 'rohan@studenthub.test', 'CE', 3, 'active'),
(NULL, 'Mira Joshi', 'mira@studenthub.test', 'AIML', 4, 'active'),
(NULL, 'Krish Mehta', 'krish.mehta@studenthub.test', 'CSE', 1, 'active'),
(NULL, 'Anaya Trivedi', 'anaya@studenthub.test', 'CE', 2, 'active'),
(NULL, 'Vivaan Patel', 'vivaan@studenthub.test', 'IT', 3, 'active'),
(NULL, 'Isha Shah', 'isha@studenthub.test', 'CSE', 4, 'active'),
(NULL, 'Kabir Desai', 'kabir@studenthub.test', 'AIML', 1, 'active'),
(NULL, 'Sara Joshi', 'sara@studenthub.test', 'IT', 2, 'active'),
(NULL, 'Arjun Mehta', 'arjun.mehta@studenthub.test', 'CSE', 3, 'active'),
(NULL, 'Myra Trivedi', 'myra.trivedi@studenthub.test', 'CE', 4, 'active'),
(NULL, 'Reyansh Patel', 'reyansh@studenthub.test', 'IT', 1, 'active'),
(NULL, 'Aadhya Shah', 'aadhya.shah@studenthub.test', 'AIML', 2, 'active'),
(NULL, 'Advait Desai', 'advait.desai@studenthub.test', 'CSE', 3, 'active'),
(NULL, 'Kiara Joshi', 'kiara.joshi@studenthub.test', 'CE', 4, 'active'),
(NULL, 'Dhruv Mehta', 'dhruv.mehta@studenthub.test', 'IT', 1, 'active'),
(NULL, 'Pari Trivedi', 'pari.trivedi@studenthub.test', 'AIML', 2, 'active');

INSERT INTO events
(title, event_date, category, description, created_at)
VALUES
('Welcome Week Meetup','2026-11-02','Culture','Meet classmates and learn about student services.',NOW()),
('Intro to Web Design','2026-11-04','Workshop','Build your first responsive webpage.',NOW()),
('Python Practice Lab','2026-11-06','Technology','Practice variables, loops, and lists.',NOW()),
('Campus Badminton Cup','2026-11-08','Sports','Friendly doubles matches for all levels.',NOW()),
('UI Design Challenge','2026-11-10','Competition','Design a clear interface for a campus problem.',NOW()),
('Career Readiness Talk','2026-11-12','Workshop','Prepare a resume and practice interview basics.',NOW()),
('Robotics Showcase','2026-11-14','Technology','Explore student-built robots and prototypes.',NOW()),
('Cultural Evening','2026-11-16','Culture','Enjoy performances, music, and art.',NOW()),
('Cyber Safety Session','2026-11-18','Workshop','Learn safe passwords and online privacy.',NOW()),
('Mini Hackathon','2026-11-20','Competition','Build a prototype with a small team.',NOW()),
('Football Friendly','2026-11-22','Sports','Join a friendly campus football match.',NOW()),
('Database Design Lab','2026-11-24','Technology','Learn tables, keys, and relationships.',NOW()),
('Poster Making Contest','2026-11-26','Competition','Create a poster on a positive campus theme.',NOW()),
('Photography Walk','2026-11-28','Culture','Capture campus life with a camera or phone.',NOW()),
('First Aid Awareness','2026-11-30','Workshop','Understand basic emergency response.',NOW());