<?php

namespace App\Apis;

require __DIR__ . '/../../config/config.php';

use PDO;
use PDOException;
use Exception;

class ScheduleApi
{
    private PDO $pdo;

    public function __construct()
    {
        global $config;
        // Inicjalizacja połączenia z bazą danych
        try {
            $this->pdo = new PDO(
                $config['db_dsn'],
                $config['db_user'],
                $config['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Błąd połączenia z bazą: " . $e->getMessage());
        }
    }

    /**
     * Główna metoda obsługująca zapytania użytkownika.
     * @param array $inputs Tablica wejściowych danych (filtry).
     * @return string JSON z wynikami zapytania.
     */
    public function getSchedule(array $inputs): string
    {
        // Przygotowanie filtrów
        $whereClauses = [];
        $params = [];

        if (!empty($inputs['teacher'])) {
            $whereClauses[] = "w.worker_name LIKE :teacher";
            $params[':teacher'] = '%' . $inputs['teacher'] . '%';
        }

        if (!empty($inputs['student_id'])) {
            $whereClauses[] = "sg.student_id = :student_id";
            $params[':student_id'] = $inputs['student_id'];
        }

        if (!empty($inputs['subject'])) {
            $whereClauses[] = "subj.subject_name LIKE :subject";
            $params[':subject'] = '%' . $inputs['subject'] . '%';
        }

        if (!empty($inputs['group_name'])) {
            $whereClauses[] = "g.group_name LIKE :group_name";
            $params[':group_name'] = '%' . $inputs['group_name'] . '%';
        }

        // Sprawdzenie czy są filtry
        $whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        if($whereSql === ''){
            return '';
        }

        // Zapytanie do bazy danych
        $sql = "
            SELECT s.schedule_id, subj.subject_name, w.worker_name, g.group_name, s.room, 
                   s.start_time, s.end_time, s.lesson_status, s.color 
            FROM schedule s
            INNER JOIN subjects subj ON s.subject_id = subj.subject_id
            INNER JOIN workers w ON s.worker_id = w.worker_id
            INNER JOIN groups g ON s.group_name = g.group_name
            INNER JOIN student_group sg ON sg.group_name = g.group_name
            $whereSql
            ORDER BY s.start_time;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Jeśli brak wyników, wykonaj zapytanie zewnętrzne
        if (empty($results)) {
            $results = $this->fetchExternalSchedule($inputs);
        }

        return json_encode($results);
    }

    /**
     * Pobranie danych z zewnętrznego API, zapisanie ich do bazy i zwrócenie.
     * @param array $inputs Filtry (np. teacher, student_id).
     * @return array Wyniki jako tablica.
     */
    private function fetchExternalSchedule(array $inputs): array
    {
        // Przygotowanie URL na podstawie dostępnych danych
        $baseUrl = "https://plan.zut.edu.pl/schedule_student.php";
        $params = [];

        if (!empty($inputs['teacher'])) {
            $params[] = 'teacher=' . urlencode($inputs['teacher']);
        }

        if (!empty($inputs['room'])) {
            $params[] = 'room=' . urlencode($inputs['room']);
        }

        if (!empty($inputs['subject'])) {
            $params[] = 'subject=' . urlencode($inputs['subject']);
        }

        if (!empty($inputs['group'])) {
            $params[] = 'group=' . urlencode($inputs['group']);
        }

        if (!empty($inputs['student_id'])) {
            $params[] = 'number=' . urlencode($inputs['student_id']);
        }

        //trzeba jakos czas z forntu przekazyawc
//        $params[] = 'start=' . urlencode($inputs['start_time']."T00:00:00+01:00");
//        $params[] = 'end=' . urlencode($inputs['end_time']."T00:00:00+01:00");

        $params[] = 'start=' . urlencode("2024-01-13T00:00:00+01:00");
        $params[] = 'end=' . urlencode("2024-01-21T00:00:00+01:00");

        // Tworzenie URL-a z zachowaną kolejnością
        $url = $baseUrl . '?' . implode('&', $params);

        echo $url;

        // Wykonanie zapytania HTTP
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        // Zapisanie do bazy danych
        $this->saveStudentToDatabase($inputs['student_id']);

        // Sprawdzanie, co zawiera odpowiedź
//        echo '<pre>';
//        print_r($data);
//        echo '</pre>';

        foreach ($data as $lesson) {
            if (empty($lesson)) {
                continue; // na jakies dziwne puste miejsce jako pierwsze
            }

            $this->saveLessonToDatabase($lesson);
            $this->saveStudentGroup($inputs['student_id'], $lesson['group_id']);
        }

        return $data;
    }

    /**
     * Zapisanie danych z zewnętrznego API do bazy danych.
     * @param array $lesson Dane pojedynczych zajęć.
     */
    private function saveLessonToDatabase(array $lesson): void
    {
        $this->pdo->beginTransaction();
        try {
            // Dodawanie grupy
            $groupSql = "
        INSERT OR IGNORE INTO groups (group_name) 
        VALUES (:group_name)
        ";
            $groupStmt = $this->pdo->prepare($groupSql);
            $groupStmt->execute([':group_name' => $lesson['group_name']]);

            // Dodawanie przedmiotu
            $subjectSql = "
        INSERT OR IGNORE INTO subjects (subject_name, lesson_form, lesson_form_short) 
        VALUES (:subject_name, :lesson_form, :lesson_form_short)
        ";
            $subjectStmt = $this->pdo->prepare($subjectSql);
            $subjectStmt->execute([
                ':subject_name' => $lesson['title'],
                ':lesson_form' => $lesson['lesson_form'],
                ':lesson_form_short' => $lesson['lesson_form_short']
            ]);

            // Pobieranie subject_id
            $subjectIdQuery = "
        SELECT subject_id 
        FROM subjects 
        WHERE subject_name = :subject_name
        ";
            $subjectIdStmt = $this->pdo->prepare($subjectIdQuery);
            $subjectIdStmt->execute([':subject_name' => $lesson['title']]);
            $subjectId = $subjectIdStmt->fetchColumn();

            if (!$subjectId) {
                throw new Exception("Nie znaleziono subject_id dla przedmiotu: " . $lesson['title']);
            }

            // Dodawanie pracownika
            $workerSql = "
        INSERT OR IGNORE INTO workers (worker_name, title) 
        VALUES (:worker_name, :title)
        ";
            $workerStmt = $this->pdo->prepare($workerSql);
            $workerStmt->execute([
                ':worker_name' => $lesson['worker'],
                ':title' => $lesson['worker_title']
            ]);

            // Pobieranie worker_id
            $workerIdQuery = "
        SELECT worker_id 
        FROM workers 
        WHERE worker_name = :worker_name
        ";
            $workerIdStmt = $this->pdo->prepare($workerIdQuery);
            $workerIdStmt->execute([':worker_name' => $lesson['worker']]);
            $workerId = $workerIdStmt->fetchColumn();

            if (!$workerId) {
                throw new Exception("Nie znaleziono worker_id dla pracownika: " . $lesson['worker']);
            }

            // Dodawanie do harmonogramu
            $scheduleSql = "
        INSERT INTO schedule (
            subject_id, worker_id, group_name, room, 
            start_time, end_time, lesson_status, 
            lesson_status_short, color, border_color
        )
        VALUES (
            :subject_id, :worker_id, :group_name, :room, 
            :start_time, :end_time, :lesson_status, 
            :lesson_status_short, :color, :border_color
        )
        ";
            $scheduleStmt = $this->pdo->prepare($scheduleSql);
            $scheduleStmt->execute([
                ':subject_id' => $subjectId,
                ':worker_id' => $workerId,
                ':group_name' => $lesson['group_name'],
                ':room' => $lesson['room'],
                ':start_time' => $lesson['start'],
                ':end_time' => $lesson['end'],
                ':lesson_status' => $lesson['lesson_status'],
                ':lesson_status_short' => $lesson['lesson_status_short'],
                ':color' => $lesson['color'],
                ':border_color' => $lesson['borderColor']
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Błąd zapisu danych: " . $e->getMessage());
        }
    }


    function saveStudentToDatabase($student_id): void
    {
        $this->pdo->beginTransaction();
        try {
            // Dodawanie studenta jeśli jeszcze nie istnieje
            $studentSql = "
            INSERT OR REPLACE INTO students (student_id) 
            VALUES (:student_id)
        ";
            $studentStmt = $this->pdo->prepare($studentSql);
            $studentStmt->execute([':student_id' => $student_id]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Błąd zapisu danych studenta: " . $e->getMessage());
        }
    }

    function saveStudentGroup($student_id, $group_name): void
    {
        $this->pdo->beginTransaction();
        try {
            // Powiązanie studenta z grupą
            $studentGroupSql = "
            INSERT OR REPLACE INTO student_group (student_id, group_name)
            VALUES (:student_id, (SELECT group_name FROM groups WHERE group_name = :group_name))
        ";
            $studentGroupStmt = $this->pdo->prepare($studentGroupSql);
            $studentGroupStmt->execute([
                ':student_id' => $student_id,
                ':group_name' => $group_name
            ]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Błąd zapisu grupy studenta: " . $e->getMessage());
        }
    }
}

try{
    $sApi = new ScheduleApi();
    $res = $sApi->getSchedule([
        'student_id' => 53967
    ]);

    echo $res;
} catch (PDOException $e) {
    echo $e->getMessage();
}
