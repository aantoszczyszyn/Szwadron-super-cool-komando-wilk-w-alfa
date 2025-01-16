let eventData = {
    title: 'Przykład, test',
    start: '2025-01-16T15:00:00',
    end: '2025-01-16T16:00:00',
    description: 'event.description',
    color: 'event.color'
};

let calendar;
let calendarEl;

// załadowanie kalendarza
document.addEventListener('DOMContentLoaded', function () {
    calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
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

    });
    window.calendar = calendar;
    calendar.render();

    calendar.addEvent(eventData);

});








