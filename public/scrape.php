<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Apis/PlanScraper.php';

use App\Apis\PlanScraper;

$pdo=new PDO($config['db_dsn'],$config['db_user'],$config['db_pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
//scraper
$scraper=new PlanScraper($pdo);

//uruchamianie scrapera
$scraper->scrapeTeacherSchedule(
    'Karczmarczyk Artur',
    '2024-09-30T00:00:00+02:00',
    '2024-10-07T00:00:00+02:00',
    1
);
echo "Scrapowanie zakończone.\n";