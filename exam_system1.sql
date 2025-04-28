CREATE DATABASE IF NOT EXISTS exam_system;
USE exam_system;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des examens
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    teacher_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    duration INT NOT NULL,
    attempts INT NOT NULL,
    total_points INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des questions
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('qcm', 'open', 'short') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Table des choix pour les questions QCM
CREATE TABLE choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    choice_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Table des réponses des étudiants
-- Ajouter cette table au fichier exam_system.sql pour stocker les réponses des étudiants

CREATE TABLE student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    question_id INT NOT NULL,
    answer VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Table pour suivre les étudiants inscrits aux examens
CREATE TABLE exam_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertion des utilisateurs
INSERT INTO users (name, email, password, role) VALUES
('Teacher One', 'teacher1@example.com', 'teacher123', 'teacher'),
('Student One', 'student1@example.com', 'student123', 'student');

-- Insertion d'un examen (assurez-vous que l'ID du professeur existe)
INSERT INTO exams (title, description, teacher_id, start_time, end_time, duration, attempts, total_points)
VALUES ('Test Exam', 'Just a test', 1, '2025-05-05 01:00:00', '2025-05-05 02:00:00', 60, 1, 100);

ALTER TABLE exam_students 
ADD COLUMN start_time DATETIME,
ADD COLUMN end_time DATETIME;
CREATE TABLE IF NOT EXISTS question_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    points INT NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);
ALTER TABLE exam_students 
ADD COLUMN score DECIMAL(5,2) DEFAULT NULL;
USE exam_system;

CREATE TABLE question_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    question_id INT NOT NULL,
    score DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_score (exam_id, student_id, question_id)
);

-- Add tables for groups and subjects
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
USE exam_system;
-- Add tables for mapping users to groups and subjects
CREATE TABLE IF NOT EXISTS user_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Update users table to add admin role
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student') NOT NULL;

-- Insert admin user
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@example.com', 'admin123', 'admin');
-- Note: The password hash is for 'admin123'

