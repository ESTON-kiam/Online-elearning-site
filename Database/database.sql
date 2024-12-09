
USE elearning_platform;


CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin') DEFAULT 'super_admin',
    profile_image VARCHAR(255),
    last_login DATETIME,
    last_logout Datetime,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
   YearOfStudent ENUM('1', '2', '3', '4'),
 categories (category_name) VALUES
('SCHOOL OF CO-OPERATIVES AND COMMUNITY DEVELOPMENT'),
('SCHOOL OF BUSINESS AND ECONOMICS'),
('SCHOOL OF COMPUTING AND MATHEMATICS'),
('SCHOOL OF NURSING'),
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT(title, description)
);

CREATE TABLE instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    expertise VARCHAR(255),
    bio TEXT,
    last_login Datetime,
    last_logout Datetime,
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



CREATE TABLE course_instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    instructor_id INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_instructor (course_id, instructor_id)
);

ALTER TABLE instructors
add COLUMN last_login Datetime,
add COLUMN last_logout Datetime;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    last_login DATETIME,
    last_logout DATETIME,
);
ALTER TABLE students 
ADD COLUMN current_session_token VARCHAR(255) NULL,
ADD COLUMN last_login_time DATETIME NULL,
ADD COLUMN is_logged_in BOOLEAN DEFAULT FALSE;

ALTER TABLE courses 
ADD COLUMN allocation_status ENUM('pending', 'allocated') DEFAULT 'pending';

CREATE TABLE admin_remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE course_activity_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    activity_id INT NOT NULL,
    enrollment_date DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (activity_id) REFERENCES course_activities(id)
);

ALTER TABLE course_activities
ADD COLUMN activity_name VARCHAR(255) NOT NULL,
ADD COLUMN activity_date DATE NOT NULL,
ADD COLUMN activity_time TIME NOT NULL,
ADD COLUMN location VARCHAR(255),
ADD COLUMN description TEXT;
