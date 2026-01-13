<?php
class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [$method, $path, $handler];
    }

    private function match($method, $uri) {
        foreach ($this->routes as $r) {
            list($m, $path, $handler) = $r;
            if (strtoupper($m) !== strtoupper($method)) continue;
            // convert path params {id} to regex
            $regex = preg_replace('#\{[^/]+\}#', '([^/]+)', $path);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                return [$handler, $matches];
            }
        }
        return [null, []];
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        list($handler, $params) = $this->match($method, $uri);
        header('Content-Type: application/json');
        if (!$handler) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }
        if (is_array($handler) && count($handler) === 2) {
            $obj = $handler[0];
            $method = $handler[1];
            call_user_func_array([$obj, $method], $params);
            return;
        }
    }
}
