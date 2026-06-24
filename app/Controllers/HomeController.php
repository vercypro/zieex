<?php
declare(strict_types=1);

namespace App\Controllers;

use Zieex\Http\Controller;
use Zieex\Http\Request;
use Zieex\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home', [
            'title' => config('app.name'),
        ]);
    }

    public function dashboard(Request $request): Response
    {
        return $this->view('dashboard', [
            'title' => 'Dashboard',
            'user'  => \Zieex\Auth\Auth::user(),
        ]);
    }
}
