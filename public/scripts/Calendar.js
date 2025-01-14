// załadowanie kalendarza

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',         // Widok tygodniowy
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,dayGridMonth',
        },
        slotMinTime: '08:00:00',
        slotMaxTime: '21:00:00',
        slotDuration: '00:30:00',
        allDaySlot: false,
        firstDay: 1,
        height: 626,
        events: [
            {
                title: 'Przykład, test',
                start: '2025-01-06T10:00:00',
                end: '2025-01-06T12:00:00',
            },
        ],
    });
    calendar.render();
});


// pobranie danych z formularza

document.getElementById('btn_search').onclick = function () {
    var student_id = document.getElementById('studentNumber').value;
    var teacher = document.getElementById('teacherName').value;
    var subject = document.getElementById('subject').value;
    var group = document.getElementById('group').value;
    var room = document.getElementById('room').value;
    var s_type = document.getElementById('studyType').value;

    // Tworzymy tablicę z danymi
    var requestData = [
        student_id,
        teacher,
        subject,
        group,
        room,
        s_type
    ];

    var requestDataString = requestData.join(";");


    fetch("/../src/Apis/ScheduleApi.php", {
        method: "POST",
        headers: {
            "Content-Type": "text/plain",
        },
        body: requestDataString,
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Błąd sieci");
            }
            return response.text();
        })
        .then((data) => {
            console.log("Odpowiedź serwera:", data);
            document.getElementById('schedule-result').innerText = data;
        })
        .catch((error) => {
            console.error("Wystąpił błąd:", error);
        });
};
