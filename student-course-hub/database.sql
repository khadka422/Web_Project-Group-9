CREATE DATABASE student_hub;
USE student_hub;

CREATE TABLE programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    level ENUM('Undergraduate', 'Postgraduate') NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,  -- Stores the image file path
    published BOOLEAN DEFAULT 1
);


CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role ENUM('Programme Leader', 'Module Leader') NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL
);

CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programme_id INT NOT NULL,
    module_name VARCHAR(255) NOT NULL,
    year INT NOT NULL CHECK (year BETWEEN 1 AND 4),
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
);

CREATE TABLE programme_staff (
    programme_id INT,
    staff_id INT,
    role ENUM('Programme Leader', 'Module Leader') NOT NULL,
    PRIMARY KEY (programme_id, staff_id),
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);
-- Module-Staff Mapping (For Module Leaders and Lecturers)
CREATE TABLE module_staff (
    module_id INT,
    staff_id INT,
    role ENUM('Module Leader', 'Lecturer') NOT NULL,
    PRIMARY KEY (module_id, staff_id),
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);


CREATE TABLE student_interest (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  programme_id INT NOT NULL,
  FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
);

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

INSERT INTO admins (username, password) VALUES ('admin', MD5('admin123'));
