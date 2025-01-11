<?php

?>

<form id="searchForm">
    <div class="form-group">
        <label for="studentNumber">Numer albumu studenta</label><br>
        <input type="text" class="form-control" id="studentNumber" placeholder="Podaj numer albumu">
    </div>

    <div class="form-group">
        <label for="teacherName">Nazwisko prowadzącego</label><br>
        <input type="text" class="form-control" id="teacherName" placeholder="Podaj nazwisko">
    </div>

    <div class="form-group">
        <label for="subject">Przedmiot</label><br>
        <input type="text" class="form-control" id="subject" placeholder="Podaj przedmiot">
    </div>

    <div class="form-group">
        <label for="group">Grupa</label><br>
        <input type="text" class="form-control" id="group" placeholder="Podaj grupę">
    </div>

    <div class="form-group">
        <label for="room">Sala</label><br>
        <input type="text" class="form-control" id="room" placeholder="Podaj salę">
    </div>

    <div class="form-group">
        <label for="studyType">Typ studiów</label><br>
        <select class="form-control" id="studyType">
            <option value="">Wybierz typ studiów</option>
            <option value="stacjonarne">Stacjonarne</option>
            <option value="niestacjonarne">Niestacjonarne</option>
        </select>
    </div>
    <div class="form-group">
        <button type="submit" id="btn_search">Szukaj</button>
        <button type="button" id="btn_print">Drukuj</button>
        <button type="button" id="btn_help">Pomoc</button>
    </div>
</form>

<div id='calendar'></div>
