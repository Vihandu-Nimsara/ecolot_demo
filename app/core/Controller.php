<?php

class Controller
{
    protected function model(string $model): object
    {
        $modelFile = APP_PATH . '/models/' . $model . '.php';

        if (!file_exists($modelFile)) {
            die("Model not found: {$model}");
        }

        require_once $modelFile;

        if (!class_exists($model)) {
            die("Model class not found: {$model}");
        }

        return new $model();
    }

    protected function view(string $view, array $data = []): void
    {
        $viewFile = APP_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            die("View not found: {$view}");
        }

        require_once APP_PATH . '/views/layouts/header.php';
        require_once $viewFile;
        require_once APP_PATH . '/views/layouts/footer.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . trim($path, '/'));
        exit;
    }
}