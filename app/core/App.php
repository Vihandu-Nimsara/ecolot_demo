<?php

class App
{
    // Controller එකේ නම (string) පමණක් තබා ගැනීමට
    protected string $controllerName = 'HomeController';
    
    // Instantiate කරන ලද Controller Object එක තබා ගැනීමට
    protected object $activeController;
    
    protected string $method = 'index';
    protected array $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        if (!empty($url[0])) {
            $controllerName = $this->formatControllerName($url[0]);

            if (file_exists(APP_PATH . '/controllers/' . $controllerName . '.php')) {
                $this->controllerName = $controllerName;
                unset($url[0]);
            }
        }

        require_once APP_PATH . '/controllers/' . $this->controllerName . '.php';

        if (!class_exists($this->controllerName)) {
            die("Controller class not found: {$this->controllerName}");
        }

        // $this->controller වෙනුවට අලුත් object variable එකට assign කිරීම
        $this->activeController = new $this->controllerName();

        if (!empty($url[1])) {
            $methodName = $this->formatMethodName($url[1]);

            // method_exists එකට දැන් pass කරන්නේ string එකක් නෙවෙයි, activeController object එකයි
            if (method_exists($this->activeController, $methodName)) {
                $this->method = $methodName;
                unset($url[1]);
            }
        }

        $this->params = $url ? array_values($url) : [];

        // Callback එකට activeController object එක ලබා දීම
        call_user_func_array([$this->activeController, $this->method], $this->params);
    }

    private function parseUrl(): array
    {
        if (isset($_GET['url'])) {
            $url = trim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }

        return [];
    }

    private function formatControllerName(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', strtolower($name));
        $name = str_replace(' ', '', ucwords($name));

        return $name . 'Controller';
    }

    private function formatMethodName(string $name): string
    {
        $parts = explode('-', str_replace('_', '-', strtolower($name)));

        $method = array_shift($parts);

        foreach ($parts as $part) {
            $method .= ucfirst($part);
        }

        return $method;
    }
}