require realpath($_SERVER['DOCUMENT_ROOT'] . '/../src/Apis/ScheduleApi.php');

if (isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];

    $sApi = new App\Apis\ScheduleApi();

    $data = $sApi->getSchedule([
        'student_id' => $student_id
]);

    $schedule = json_decode($data, true);

    foreach ($schedule as $lesson) {
        echo "Subject: " . $lesson['subject_name'] . "<br>";
        echo "Worker: " . $lesson['worker_name'] . "<br>";
        echo "Group: " . $lesson['group_name'] . "<br>";
        echo "Room: " . $lesson['room'] . "<br>";
        echo "Start: " . $lesson['start_time'] . "<br>";
        echo "End: " . $lesson['end_time'] . "<br><br><br>";
    }
}