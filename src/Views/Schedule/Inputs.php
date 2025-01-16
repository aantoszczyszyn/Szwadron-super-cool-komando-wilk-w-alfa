<?php

?>

<form id="searchForm" method="POST">
    <div class="form-group">
        <label for="student_id">Numer albumu studenta</label><br>
        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Podaj numer albumu">
    </div>

    <div class="form-group">
        <label for="worker_name">Nazwisko prowadzącego</label><br>
        <input type="text" class="form-control" id="worker_name" name="worker_name" placeholder="Podaj nazwisko">
    </div>

    <div class="form-group">
        <label for="subject_name">Przedmiot</label><br>
        <input type="text" class="form-control" id="subject_name" name="subject_name" placeholder="Podaj przedmiot">
    </div>

    <div class="form-group">
        <label for="group_name">Grupa</label><br>
        <input type="text" class="form-control" id="group_name" name="group_name" placeholder="Podaj grupę">
    </div>

    <div class="form-group">
        <label for="room">Sala</label><br>
        <input type="text" class="form-control" id="room" name="room" placeholder="Podaj salę">
    </div>

<!--     <div class="form-group">
        <label for="studyType">Typ studiów</label><br>
        <select class="form-control" id="studyType">
            <option value="">Wybierz typ studiów</option>
            <option value="stacjonarne">Stacjonarne</option>
            <option value="niestacjonarne">Niestacjonarne</option>
        </select>
    </div> -->


    <div class="form-group">
        <button id="btn-search">Prześlij</input>
        <button type="button" id="btn_print">Drukuj</button>
        <button type="button" id="btn_help">Pomoc</button>
    </div>
</form>

