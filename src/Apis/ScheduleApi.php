<?php

//skrypt nie skonczony szkielet
namespace App\Apis;
use PDO;

class ScheduleApi
{
    private PDO $pdo;

    public function __construct() {
        // Inicjalizacja połączenia z bazą danych
        try {
            $this->pdo = new PDO(
                "mysql:host=localhost;dbname=schedule_db;charset=utf8",
                "username",
                "password",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Błąd połączenia z bazą: " . $e->getMessage());
        }
    }

    public function getSchedule($studentNumber, $startDate, $endDate) {
        try {
            // Najpierw sprawdzamy czy mamy dane w lokalnej bazie
            $schedule = $this->getLocalSchedule($studentNumber, $startDate, $endDate);

            if ($schedule === false) {
                // Jeśli nie ma w bazie, pobieramy z zewnętrznego API
                $schedule = $this->fetchFromExternalAPI($studentNumber, $startDate, $endDate);

                // Zapisujemy pobrane dane do lokalnej bazy
                if ($schedule !== false) {
                    $this->saveToLocalDB($studentNumber, $schedule);
                }
            }

            return $schedule;

        } catch (Exception $e) {
            throw new Exception("Błąd podczas pobierania planu: " . $e->getMessage());
        }
    }

    private function getLocalSchedule($studentNumber, $startDate, $endDate) {
        $query = "SELECT * FROM schedules 
                 WHERE student_number = :student_number 
                 AND date BETWEEN :start_date AND :end_date";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':student_number' => $studentNumber,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return !empty($result) ? $result : false;
    }

    private function fetchFromExternalAPI($studentNumber, $startDate, $endDate) {
        $url = sprintf(
            'https://plan.zut.edu.pl/schedule_student.php?number=%s&start=%s&end=%s',
            urlencode($studentNumber),
            urlencode($startDate),
            urlencode($endDate)
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Błąd pobierania danych z zewnętrznego API: HTTP $httpCode");
        }

        return json_decode($response, true);
    }

    private function saveToLocalDB($studentNumber, $schedule) {
        $this->pdo->beginTransaction();

        //dosotosowac do 01-post.sql data.db
        try {
            foreach ($schedule as $lesson) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO schedules (
                        student_number, 
                        date,
                        subject,
                        room,
                        teacher,
                        group_name
                    ) VALUES (
                        :student_number,
                        :date,
                        :subject,
                        :room,
                        :teacher,
                        :group_name
                    )
                ");

                $stmt->execute([
                    ':student_number' => $studentNumber,
                    ':date' => $lesson['date'],
                    ':subject' => $lesson['subject'],
                    ':room' => $lesson['room'],
                    ':teacher' => $lesson['teacher'],
                    ':group_name' => $lesson['group_name']
                ]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Błąd zapisywania do bazy: " . $e->getMessage());
        }
    }
}

try {
    $api = new ScheduleAPI();
    $schedule = $api->getSchedule(
        "53967",
        "2025-01-20T00:00:00+01:00",
        "2025-01-27T00:00:00+01:00"
    );
    echo json_encode($schedule);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}