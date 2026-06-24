<?php
declare(strict_types=1);

namespace App\Controllers;

use Zieex\Http\Controller;
use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Auth\Auth;
use Zieex\Database\DB;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) return $this->redirect('/dashboard');
        return $this->view('auth.login', ['title' => 'Login']);
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (!Auth::attempt($data['email'], $data['password'])) {
            flash('error', 'Invalid credentials.');
            return $this->back();
        }

        flash('success', 'Welcome back!');
        return $this->redirect('/dashboard');
    }

    public function showRegister(Request $request): Response
    {
        if (Auth::check()) return $this->redirect('/dashboard');
        return $this->view('auth.register', ['title' => 'Register']);
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'username' => 'required|max:100|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        DB::table('users')->insert([
            'id'         => uuid(),
            'username'   => $data['username'],
            'email'      => $data['email'],
            'password'   => Auth::hash($data['password']),
            'role'       => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::attempt($data['email'], $data['password']);
        flash('success', 'Account created successfully!');
        return $this->redirect('/dashboard');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        return $this->redirect('/login');
    }
}
