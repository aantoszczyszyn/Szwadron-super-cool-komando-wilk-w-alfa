<?php

// require __DIR__ . '/../../../config/config.php';
require realpath($_SERVER['DOCUMENT_ROOT'] . '/../src/Apis/ScheduleApi.php');
// echo $_SERVER['DOCUMENT_ROOT'] . '/../src/Apis/ScheduleApi.php';


$events = [];
if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];



    $sApi = new App\Apis\ScheduleApi();

    $data = $sApi->getSchedule([
        'student_id' => $student_id
    ]);

    $schedule = json_decode($data, true);

//     foreach ($schedule as $lesson) {
//         echo "Subject: " . $lesson['subject_name'] . "<br>";
//         echo "Worker: " . $lesson['worker_name'] . "<br>";
//         echo "Group: " . $lesson['group_name'] . "<br>";
//         echo "Room: " . $lesson['room'] . "<br>";
//         echo "Start: " . $lesson['start_time'] . "<br>";
//         echo "End: " . $lesson['end_time'] . "<br><br><br>";
//     }


        foreach ($schedule as $lesson) {
            $events[] = [
                'title' => $lesson['subject_name'],
                'start' => $lesson['start_time'],
                'end' => $lesson['end_time'],
                'description' => "Zajęcia: " . $lesson['subject_name'],
                'color' => '#1A8238'
            ];
        }

    $events[0]['title'];
}

?>

<script>
document.getElementById('btn-search').addEventListener('submit', function (event) {
    event.preventDefault(); // Zapobiega przeładowaniu strony

    let student_id = document.getElementById('student_id').value;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "Events.php", true); // Składa żądanie do tego samego pliku
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("student_id=" + encodeURIComponent(student_id));
});
let calendar;
// Sprawdź, czy w PHP tablica $events została poprawnie wypełniona
let events = <?php echo json_encode($events); ?>;

// Sprawdź, czy events nie jest pusta
if (events.length > 0) {
    let tytul = events[0].title;  // Dostęp do tytułu pierwszego przedmiotu
    console.log("Tytuł pierwszego przedmiotu: " + tytul);

    events.forEach(function(event) {
       let eventData = {
            title: event.title,
            start: event.start,
            end: event.end,
            description: event.description,
            color: event.color
        };
        window.calendar.addEvent(eventData);

    });

} else {
    console.log("Brak danych do wyświetlenia.");
}

</script>