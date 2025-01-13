<?php

namespace App\Controller;
class Controller{

    private ServiceApi $serviceApi;

    public function __construct(){
//         $this->serviceApi = new ServiceApi;
    }

    public function showIndex(){
        include __DIR__ . '/../Views/Header/Header.php';
        include __DIR__ . '/../Views/Schedule/Schedule.php';
        include __DIR__ . '/../Views/Schedule/Inputs.php';

    }

}

?>