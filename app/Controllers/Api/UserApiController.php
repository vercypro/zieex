<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use Zieex\Http\Controller;
use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Database\DB;

class UserApiController extends Controller
{
    public function index(Request $request): Response
    {
        $page    = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 15);
        $result  = DB::table('users')->paginate($perPage, $page);

        // Hide passwords
        $result['data'] = array_map(function($u) { unset($u['password']); return $u; }, $result['data']);
        return $this->success($result);
    }

    public function show(Request $request): Response
    {
        $user = DB::table('users')->find($request->param('id'));
        if (!$user) return $this->error('User not found.', 404);
        unset($user['password']);
        return $this->success($user);
    }

    public function store(Request $request): Response
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
            'password'   => \Zieex\Auth\Auth::hash($data['password']),
            'role'       => $request->input('role', 'user'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->find($id);
        unset($user['password']);
        return $this->success($user, 'User created.', 201);
    }

    public function update(Request $request): Response
    {
        $user = DB::table('users')->find($request->param('id'));
        if (!$user) return $this->error('User not found.', 404);

        $allowed = $request->only('username', 'email', 'role');
        if (empty($allowed)) return $this->error('Nothing to update.');

        DB::table('users')->where('id', $user['id'])->update(array_merge($allowed, ['updated_at' => now()]));
        return $this->success(null, 'User updated.');
    }

    public function destroy(Request $request): Response
    {
        $user = DB::table('users')->find($request->param('id'));
        if (!$user) return $this->error('User not found.', 404);

        DB::table('users')->where('id', $user['id'])->delete();
        return $this->success(null, 'User deleted.');
    }
}
