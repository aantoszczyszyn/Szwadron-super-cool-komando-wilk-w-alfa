-- Tabela dla postów (przykładowa)
CREATE TABLE IF NOT EXISTS post (
                                    id INTEGER NOT NULL
                                        CONSTRAINT post_pk PRIMARY KEY AUTOINCREMENT,
                                    subject TEXT NOT NULL,
                                    content TEXT NOT NULL
);

-- Tabela dla semestrów
CREATE TABLE IF NOT EXISTS semester (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        name TEXT NOT NULL,
                                        start_date TEXT NOT NULL,
                                        end_date TEXT NOT NULL
);

-- Tabela dla lekcji
CREATE TABLE IF NOT EXISTS lessons (
                                       id INTEGER PRIMARY KEY AUTOINCREMENT,
                                       title TEXT NOT NULL,
                                       date TEXT NOT NULL,
                                       room TEXT NOT NULL,
                                       teacher TEXT NOT NULL,
                                       semester_id INTEGER NOT NULL,
                                       FOREIGN KEY (semester_id) REFERENCES semester(id)
);
