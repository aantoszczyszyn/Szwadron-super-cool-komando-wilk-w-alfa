<?php

namespace App\Apis;
use PDO;

class PlanScraper
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

     /* pobiera dane z API (lub lokalnego pliku) i zapisuje do bazy.
     */
    public function scrapeTeacherSchedule(string $teacher, string $startDate, string $endDate, int $semesterId): void
    {
        // 1. Składamy URL do API
        $url = "https://plan.zut.edu.pl/schedule_student.php?teacher=" . urlencode($teacher)
            . "&start=" . urlencode($startDate)
            . "&end=" . urlencode($endDate);

        $json = @file_get_contents($url);
        if (!$json) {
            echo "Błąd pobierania danych z API. Przełączam na dane testowe...\n";
            $json = file_get_contents(__DIR__ . '/sample_data.json'); // lokalny plik JSON
        }

        $data = json_decode($json, true);
        if (!$data) {
            throw new \Exception("Niepoprawny format danych JSON.");
        }

        //zapis do bazy
        $sql = "INSERT INTO lessons (title, date, room, teacher, semester_id)
                VALUES (:title, :date, :room, :teacher, :semester_id)";
        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $lesson) {
            $stmt->execute([
                ':title'       => $lesson['title'] ?? 'Brak tytułu',
                ':date'        => $lesson['start'] ?? null,
                ':room'        => $lesson['room'] ?? '???',
                ':teacher'     => $teacher,
                ':semester_id' => $semesterId,
            ]);
        }
        echo "Dane zostały zapisane do bazy.\n";
    }
}
