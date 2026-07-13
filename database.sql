-- ============================================================
--  SnowLearn LMS — Base de données complète
--  DB: snowlearn_db | Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS snowlearn_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE snowlearn_db;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100)  NOT NULL,
    prenom      VARCHAR(100)  NOT NULL,
    email       VARCHAR(191)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
    avatar      VARCHAR(255)  DEFAULT NULL,
    bio         TEXT          DEFAULT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: modules
-- ============================================================
CREATE TABLE IF NOT EXISTS modules (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    titre               VARCHAR(255)    NOT NULL,
    description         TEXT,
    thumbnail           VARCHAR(255)    DEFAULT NULL,
    passing_threshold   TINYINT UNSIGNED DEFAULT 70,
    created_by          INT             NOT NULL,
    created_at          DATETIME        DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: courses
-- ============================================================
CREATE TABLE IF NOT EXISTS courses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    module_id   INT           NOT NULL,
    teacher_id  INT           NOT NULL,
    titre       VARCHAR(255)  NOT NULL,
    description TEXT,
    thumbnail   VARCHAR(255)  DEFAULT NULL,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id)  REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: lessons
-- ============================================================
CREATE TABLE IF NOT EXISTS lessons (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    course_id   INT           NOT NULL,
    titre       VARCHAR(255)  NOT NULL,
    type        ENUM('pdf','video') NOT NULL,
    fichier     VARCHAR(500)  NOT NULL,
    ordre       INT           DEFAULT 1,
    duration    INT           DEFAULT 0,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: quizzes
-- ============================================================
CREATE TABLE IF NOT EXISTS quizzes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id     INT           NOT NULL UNIQUE,
    titre         VARCHAR(255)  NOT NULL,
    passing_score TINYINT UNSIGNED DEFAULT 50,
    created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: quiz_questions
-- ============================================================
CREATE TABLE IF NOT EXISTS quiz_questions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id         INT           NOT NULL,
    question        TEXT          NOT NULL,
    option_a        VARCHAR(500)  NOT NULL,
    option_b        VARCHAR(500)  NOT NULL,
    option_c        VARCHAR(500)  NOT NULL,
    option_d        VARCHAR(500)  NOT NULL,
    bonne_reponse   ENUM('A','B','C','D') NOT NULL,
    created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: results
-- ============================================================
CREATE TABLE IF NOT EXISTS results (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT           NOT NULL,
    quiz_id     INT           NOT NULL,
    lesson_id   INT           NOT NULL,
    score       INT           NOT NULL DEFAULT 0,
    total       INT           NOT NULL DEFAULT 0,
    pourcentage DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    passed      TINYINT(1)    NOT NULL DEFAULT 0,
    taken_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_result (student_id, quiz_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (quiz_id)    REFERENCES quizzes(id)  ON DELETE CASCADE,
    FOREIGN KEY (lesson_id)  REFERENCES lessons(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: progress
-- ============================================================
CREATE TABLE IF NOT EXISTS progress (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT           NOT NULL,
    module_id       INT           NOT NULL,
    lessons_done    INT           DEFAULT 0,
    lessons_total   INT           DEFAULT 0,
    score_moyen     DECIMAL(5,2)  DEFAULT 0.00,
    pourcentage     DECIMAL(5,2)  DEFAULT 0.00,
    updated_at      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_progress (student_id, module_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (module_id)  REFERENCES modules(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: certificates
-- ============================================================
CREATE TABLE IF NOT EXISTS certificates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT           NOT NULL,
    module_id       INT           NOT NULL,
    fichier         VARCHAR(500)  DEFAULT NULL,
    delivered_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cert (student_id, module_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (module_id)  REFERENCES modules(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: student_lessons
-- ============================================================
CREATE TABLE IF NOT EXISTS student_lessons (
    student_id   INT NOT NULL,
    lesson_id    INT NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, lesson_id),
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (lesson_id)  REFERENCES lessons(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DEMO DATA
-- Mots de passe : Admin123! / Teacher123! / Student123!
-- ============================================================

INSERT INTO users (nom, prenom, email, password, role) VALUES
('Admin',    'SnowLearn', 'admin@snowlearn.com',    '$2y$10$XngkT/SoGRX7G3PLSOZmCOPHWC8yK2fDNzgG9IlKQbEDhSpsciB.C', 'admin'),
('Dupont',   'Marie',     'prof@snowlearn.com',     '$2y$10$dVwl2uD8hFzQqJWceTrG5u3x3Hlbbo1JT.zFuM5uh4eKxGBKeOP7G', 'teacher'),
('Martin',   'Lucas',     'etudiant@snowlearn.com', '$2y$10$9OcM5qF.YM.bbtoZELVkRuLxx6Fk1hOVjznzoo9J4qMmlNh4ou2Pi', 'student');

-- NOTE : Passwords hachés avec password_hash('Admin123!', PASSWORD_BCRYPT)
-- Si les hash ci-dessus ne correspondent pas à votre version PHP,
-- allez dans register.php et créez des comptes manuellement.
