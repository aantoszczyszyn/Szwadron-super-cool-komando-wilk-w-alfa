<?php
require_once __DIR__ . '/../src/Model/Router.php';
require_once __DIR__ . '/../src/Controller/Controller.php';
require_once __DIR__ . '/../src/Apis/ServiceApi.php';

use App\Model\Router;
use App\Controller\Controller;

// Front-Controller

$router = new Router();
$router->addRoute('/', Controller::class, 'showIndex');

?>


<!doctype html>
<html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nowy lepszy plan ZUT 2025</title>
        <link rel="stylesheet" href="styles/Style.css">
        <link rel="stylesheet" href="styles/Schedule.css">
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    </head>
    <body>

        <?php
            try {
                // Przekierowanie żądania na odpowiednią akcję kontrolera
                $router->dispatch($_SERVER['REQUEST_URI']);
            } catch (Exception $e) {
                die('Wystąpił błąd: ' . $e->getMessage());
            }
        ?>

        </main>
        <footer>
            ZUT 2025
        </footer>
        <script type="module" src="/scripts/Calendar.js"></script>
    </body>
</html>