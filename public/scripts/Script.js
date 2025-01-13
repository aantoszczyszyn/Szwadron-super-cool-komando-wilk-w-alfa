document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',         // Widok tygodniowy
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,dayGridMonth',
        },
        slotMinTime: '08:00:00',             // Start dnia
        slotMaxTime: '21:00:00',             // Koniec dnia
        slotDuration: '00:30:00',            // Podział godzin co 30 minut
        allDaySlot: false, // Ukrycie sekcji "Cały dzień"
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