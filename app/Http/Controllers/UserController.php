<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'role' => $request->boolean('admin') ? 'admin' : 'user',
            'active' => true,
        ]);

        return redirect('/users')->with('status', 'เพิ่มผู้ใช้แล้ว');
    }

    public function passwordForm(): View
    {
        return view('password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password_hash)) {
            return back()->withErrors(['current_password' => 'รหัสผ่านเดิมไม่ถูกต้อง']);
        }

        $user->password_hash = $data['password'];
        $user->save();

        return back()->with('status', 'เปลี่ยนรหัสผ่านแล้ว');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->password_hash = $data['password'];
        $user->save();

        return back()->with('status', 'ตั้งรหัสผ่านใหม่ให้ '.$user->username.' แล้ว');
    }

    public function setActive(Request $request, User $user): RedirectResponse
    {
        $active = $request->boolean('active');
        if ($user->is($request->user()) && ! $active) {
            return back()->withErrors(['user' => 'ปิดบัญชีของตัวเองไม่ได้']);
        }
        if ($user->isAdmin() && $user->active && ! $active && $this->otherActiveAdmins($user) < 1) {
            return back()->withErrors(['user' => 'ต้องเหลือผู้ดูแลระบบที่ใช้งานอยู่อย่างน้อย 1 คน']);
        }

        $user->active = $active;
        $user->save();

        return back()->with('status', $active ? 'เปิดใช้งานบัญชีแล้ว' : 'ปิดการใช้งานบัญชีแล้ว');
    }

    public function setRole(Request $request, User $user): RedirectResponse
    {
        $role = $request->validate([
            'role' => ['required', 'in:admin,user'],
        ])['role'];

        if ($user->isAdmin() && $role !== 'admin' && $this->otherActiveAdmins($user) < 1) {
            return back()->withErrors(['user' => 'ต้องเหลือผู้ดูแลระบบที่ใช้งานอยู่อย่างน้อย 1 คน']);
        }

        $user->role = $role;
        $user->save();

        return back()->with('status', 'ปรับสิทธิ์ของ '.$user->username.' แล้ว');
    }

    private function otherActiveAdmins(User $user): int
    {
        return User::query()
            ->where('role', 'admin')
            ->where('active', true)
            ->whereKeyNot($user->id)
            ->count();
    }
}
