<?php
namespace App\Model;

class Router{
    private $routes = [];

    public function addRoute($path, $controller, $action){
        $this->routes[$path] = ['controller' => $controller, 'action' => $action];
    }

    // Metoda dispatch odpowiada za przekierowanie żądania na odpowiednią akcję kontrolera
    public function dispatch($uri){
        $path = parse_url($uri, PHP_URL_PATH);

        if (isset($this->routes[$path])) {
            try {
                $controller = new $this->routes[$path]['controller']();
                $action = $this->routes[$path]['action'];
                return $controller->$action();
            }
            catch (\Exception $e){
                throw new \Exception('Strona nie została znaleziona', 404, $e);
            }
        }
    }
}