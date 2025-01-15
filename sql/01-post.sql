-- 01-post.sql

CREATE TABLE students (
                          student_id INTEGER PRIMARY KEY
);

CREATE TABLE groups (
                        group_name TEXT NOT NULL,
                        PRIMARY KEY (group_name)
);

CREATE TABLE subjects (
                          subject_id INTEGER PRIMARY KEY AUTOINCREMENT,
                          subject_name TEXT NOT NULL,
                          lesson_form TEXT,
                          lesson_form_short TEXT
);

CREATE TABLE workers (
                         worker_id INTEGER PRIMARY KEY AUTOINCREMENT,
                         worker_name TEXT NOT NULL,
                         title TEXT
);

CREATE TABLE schedule (
                          schedule_id INTEGER PRIMARY KEY AUTOINCREMENT,
                          subject_id INTEGER NOT NULL,
                          worker_id INTEGER NOT NULL,
                          group_name TEXT NOT NULL,
                          room TEXT,
                          start_time TEXT NOT NULL,
                          end_time TEXT NOT NULL,
                          lesson_status TEXT,
                          lesson_status_short TEXT,
                          color TEXT,
                          border_color TEXT,
                          FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
                          FOREIGN KEY (worker_id) REFERENCES workers(worker_id),
                          FOREIGN KEY (group_name) REFERENCES groups(group_name)
);

CREATE TABLE student_group (
                               student_id INTEGER NOT NULL,
                               group_name TEXT NOT NULL,
                               PRIMARY KEY (student_id, group_name),
                               FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                               FOREIGN KEY (group_name) REFERENCES groups(group_name) ON DELETE CASCADE
);
