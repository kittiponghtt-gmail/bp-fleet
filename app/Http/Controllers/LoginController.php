<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('login', [
            'setup' => ! User::query()->exists(),
        ]);
    }

    public function setup(Request $request): RedirectResponse
    {
        if (User::query()->exists()) {
            abort(404);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'display_name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::query()->create([
            'username' => $data['username'],
            'display_name' => $data['display_name'],
            'password_hash' => $data['password'],
            'role' => 'admin',
            'active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $account = User::query()->where('username', $credentials['username'])->first();
        if ($account && ! $account->active) {
            return back()->withErrors(['username' => 'บัญชีนี้ถูกปิดการใช้งาน'])->onlyInput('username');
        }

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            return back()->withErrors(['username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
