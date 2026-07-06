<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $data = [
            'title' => 'Welcome to EcoLot LK',
            'description' => 'Role-based e-waste collection and recycling management prototype.'
        ];

        $this->view('home/index', $data);
    }

    public function dbTest(): void
    {
        $db = new Database();

        $db->query("SELECT COUNT(*) AS total_categories FROM ewaste_categories");
        $result = $db->single();

        $data = [
            'title' => 'Database Test',
            'total_categories' => $result->total_categories ?? 0
        ];

        $this->view('home/index', $data);
    }
}