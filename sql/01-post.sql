CREATE TABLE students (
                        student_id INT PRIMARY KEY
);

CREATE TABLE groups (
                        group_id INT AUTO_INCREMENT PRIMARY KEY,
                        group_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE subjects (
                          subject_id INT AUTO_INCREMENT PRIMARY KEY,
                          subject_name VARCHAR(100) NOT NULL,
                          lesson_form VARCHAR(50),
                          lesson_form_short VARCHAR(10)
);

CREATE TABLE workers (
                         worker_id INT AUTO_INCREMENT PRIMARY KEY,
                         worker_name VARCHAR(100) NOT NULL,
                         title VARCHAR(100)
);

CREATE TABLE schedule (
                          schedule_id INT AUTO_INCREMENT PRIMARY KEY,
                          subject_id INT NOT NULL,
                          worker_id INT NOT NULL,
                          group_id INT,
                          room VARCHAR(50),
                          start_time DATETIME NOT NULL,
                          end_time DATETIME NOT NULL,
                          lesson_status VARCHAR(50),
                          lesson_status_short VARCHAR(10),
                          color VARCHAR(10),
                          border_color VARCHAR(10),
                          FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
                          FOREIGN KEY (worker_id) REFERENCES workers(worker_id),
                          FOREIGN KEY (group_id) REFERENCES groups(group_id)
);

CREATE TABLE student_group (
                               student_id INT NOT NULL,
                               group_id INT NOT NULL,
                               PRIMARY KEY (student_id, group_id),
                               FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                               FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE
);