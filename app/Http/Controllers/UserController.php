<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users', [
            'users' => User::query()->orderBy('username')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'display_name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::query()->create([
            'username' => $data['username'],
            'display_name' => $data['display_name'],
            'password_hash' => $data['password'],
        ]);

        return redirect('/users')->with('status', 'เพิ่มผู้ใช้แล้ว');
    }
}
