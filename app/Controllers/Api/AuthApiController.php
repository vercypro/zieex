<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use Zieex\Http\Controller;
use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Auth\Auth;
use Zieex\Auth\JWT;
use Zieex\Database\DB;

class AuthApiController extends Controller
{
    public function login(Request $request): Response
    {
        $data = $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        if (!$user || !Auth::verify($data['password'], $user['password'])) {
            return $this->error('Invalid credentials.', 401);
        }

        $token = JWT::encode([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);

        unset($user['password']);
        return $this->success(['token' => $token, 'user' => $user], 'Login successful.');
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'username' => 'required|max:100|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        $id = DB::table('users')->insert([
            'id'         => uuid(),
            'username'   => $data['username'],
            'email'      => $data['email'],
            'password'   => Auth::hash($data['password']),
            'role'       => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->find($id);
        unset($user['password']);

        $token = JWT::encode(['sub' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
        return $this->success(['token' => $token, 'user' => $user], 'Registered.', 201);
    }

    public function me(Request $request): Response
    {
        $payload = $_SERVER['JWT_PAYLOAD'] ?? null;
        if (!$payload) return $this->error('Unauthorized.', 401);

        $user = DB::table('users')->find($payload['sub']);
        if (!$user) return $this->error('User not found.', 404);

        unset($user['password']);
        return $this->success($user);
    }

    public function logout(Request $request): Response
    {
        return $this->success(null, 'Logged out. Please discard your token.');
    }
}
