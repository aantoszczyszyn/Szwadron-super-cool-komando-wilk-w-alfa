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

    public function getSchedule(array $inputs): string
    {
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

        $whereSql = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

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

        if (empty($results)) {
            $results = $this->fetchExternalSchedule($inputs);
        }

        return json_encode($results);
    }

    private function fetchExternalSchedule(array $inputs): array
    {
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

        $params[] = 'start=' . urlencode("2025-01-13T00:00:00+01:00");
        $params[] = 'end=' . urlencode("2025-01-21T00:00:00+01:00");

        $url = $baseUrl . '?' . implode('&', $params);
        $response = @file_get_contents($url);

        if ($response === false) {
            throw new Exception("Błąd połączenia z API planu ZUT. URL: $url");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Błąd dekodowania JSON z API planu ZUT.");
        }

        $this->saveStudentToDatabase($inputs['student_id']);

        foreach ($data as $lesson) {
            if (empty($lesson)) {
                continue;
            }

            if (empty($lesson['group_name'])) {
                throw new Exception("Brak group_name w danych lekcji pobranych z API.");
            }

            $this->saveLessonToDatabase($lesson);
            $this->saveStudentGroup($inputs['student_id'], $lesson['group_name']);
        }

        return $data;
    }

    private function saveLessonToDatabase(array $lesson): void
    {
        $this->pdo->beginTransaction();
        try {
            if (empty($lesson['group_name'])) {
                throw new Exception("Brak group_name w danych lekcji. Nie można zapisać.");
            }

            $groupSql = "
                INSERT OR IGNORE INTO groups (group_name) 
                VALUES (:group_name)
            ";
            $groupStmt = $this->pdo->prepare($groupSql);
            $groupStmt->execute([':group_name' => $lesson['group_name']]);

            $subjectIdQuery = "
                SELECT subject_id 
                FROM subjects 
                WHERE subject_name = :subject_name
            ";
            $subjectIdStmt = $this->pdo->prepare($subjectIdQuery);
            $subjectIdStmt->execute([':subject_name' => $lesson['title']]);
            $subjectId = $subjectIdStmt->fetchColumn();

            if (!$subjectId) {
                $insertSubjectSql = "
                    INSERT INTO subjects (subject_name, lesson_form, lesson_form_short) 
                    VALUES (:subject_name, :lesson_form, :lesson_form_short)
                ";
                $insertSubjectStmt = $this->pdo->prepare($insertSubjectSql);
                $insertSubjectStmt->execute([
                    ':subject_name' => $lesson['title'],
                    ':lesson_form' => $lesson['lesson_form'] ?? "N/A",
                    ':lesson_form_short' => $lesson['lesson_form_short'] ?? "N/A"
                ]);
                $subjectId = $this->pdo->lastInsertId();
            }

            $workerIdQuery = "
                SELECT worker_id 
                FROM workers 
                WHERE worker_name = :worker_name
            ";
            $workerIdStmt = $this->pdo->prepare($workerIdQuery);
            $workerIdStmt->execute([':worker_name' => $lesson['worker']]);
            $workerId = $workerIdStmt->fetchColumn();

            if (!$workerId) {
                $insertWorkerSql = "
                    INSERT INTO workers (worker_name, title) 
                    VALUES (:worker_name, :title)
                ";
                $insertWorkerStmt = $this->pdo->prepare($insertWorkerSql);
                $insertWorkerStmt->execute([
                    ':worker_name' => $lesson['worker'],
                    ':title' => $lesson['worker_title'] ?? "Brak tytułu"
                ]);
                $workerId = $this->pdo->lastInsertId();
            }

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
                ':lesson_status' => $lesson['lesson_status'] ?? "planned",
                ':lesson_status_short' => $lesson['lesson_status_short'] ?? "PL",
                ':color' => $lesson['color'] ?? "#FFFFFF",
                ':border_color' => $lesson['borderColor'] ?? "#000000"
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Błąd zapisu danych: " . $e->getMessage());
        }
    }

    function saveStudentToDatabase($student_id): void
    {
        $studentSql = "
            INSERT OR REPLACE INTO students (student_id) 
            VALUES (:student_id)
        ";
        $stmt = $this->pdo->prepare($studentSql);
        $stmt->execute([':student_id' => $student_id]);
    }

    function saveStudentGroup($student_id, $group_name): void
    {
        if (empty($group_name)) {
            throw new Exception("Brak group_name. Nie można zapisać grupy studenta.");
        }

        $this->pdo->beginTransaction();
        try {
            $studentGroupSql = "
                INSERT OR REPLACE INTO student_group (student_id, group_name)
                VALUES (:student_id, :group_name)
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

try {
    $sApi = new ScheduleApi();
    $res = $sApi->getSchedule([
        'student_id' => 53967
    ]);
    echo $res;
} catch (Exception $e) {
    echo $e->getMessage();
}
