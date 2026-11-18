<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ผู้ใช้งาน · BP Fleet</title>
<style>
body{margin:0;background:#11272d;color:#e7f2f1;font-family:Segoe UI,Tahoma,sans-serif}
main{max-width:980px;margin:0 auto;padding:28px 16px 48px}
a{color:#8ee0d2}
h1{margin:0 0 16px}
form.panel,table{background:#18383f;border:1px solid #2d5c63;border-radius:16px}
form.panel{padding:18px;display:grid;gap:10px;margin-bottom:18px}
label{font-size:14px}
input{border:1px solid #2d5c63;background:#0f2429;color:#fff;border-radius:10px;padding:10px}
button{border:0;border-radius:10px;padding:10px 12px;background:#2f9e8f;color:#fff;font-weight:700;cursor:pointer}
button.muted{background:#3d555b}
button.warn{background:#8a4a44}
table{width:100%;border-collapse:collapse;overflow:hidden}
th,td{text-align:left;padding:12px 14px;border-bottom:1px solid #2d5c63;vertical-align:top}
.actions{display:grid;gap:8px}
.actions form{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.error{color:#ffb4a8;font-size:13px}
.status{color:#8ee0d2}
.off{opacity:.55}
</style>
</head>
<body>
<main>
<p><a href="/">กลับไปหน้ารถ</a></p>
<h1>ผู้ใช้งาน</h1>
@if (session('status'))<p class="status">{{ session('status') }}</p>@endif
@error('user')<p class="error">{{ $message }}</p>@enderror
<form class="panel" method="post" action="/users">
@csrf
<label>ชื่อผู้ใช้</label>
<input name="username" value="{{ old('username') }}" required>
@error('username')<div class="error">{{ $message }}</div>@enderror
<label>ชื่อที่แสดง</label>
<input name="display_name" value="{{ old('display_name') }}" required>
@error('display_name')<div class="error">{{ $message }}</div>@enderror
<label>รหัสผ่าน</label>
<input name="password" type="password" required>
@error('password')<div class="error">{{ $message }}</div>@enderror
<label><input type="checkbox" name="admin" value="1" style="width:auto"> ให้เป็นผู้ดูแลระบบ</label>
<button type="submit">เพิ่มผู้ใช้</button>
</form>
<table>
<tr><th>ชื่อผู้ใช้</th><th>ชื่อที่แสดง</th><th>สิทธิ์</th><th>สถานะ</th><th>จัดการ</th></tr>
@foreach ($users as $user)
<tr class="{{ $user->active ? '' : 'off' }}">
<td>{{ $user->username }}</td>
<td>{{ $user->display_name }}</td>
<td>{{ $user->isAdmin() ? 'ผู้ดูแล' : 'ผู้ใช้' }}</td>
<td>{{ $user->active ? 'ใช้งาน' : 'ปิดแล้ว' }}</td>
<td class="actions">
<form method="post" action="/users/{{ $user->id }}/password">
@csrf
<input name="password" type="password" placeholder="รหัสผ่านใหม่" required>
<button type="submit">ตั้งรหัสใหม่</button>
</form>
<form method="post" action="/users/{{ $user->id }}/role">
@csrf
<input type="hidden" name="role" value="{{ $user->isAdmin() ? 'user' : 'admin' }}">
<button class="muted" type="submit">{{ $user->isAdmin() ? 'เป็นผู้ใช้ทั่วไป' : 'เป็นผู้ดูแล' }}</button>
</form>
@unless ($user->is(auth()->user()))
<form method="post" action="/users/{{ $user->id }}/active">
@csrf
<input type="hidden" name="active" value="{{ $user->active ? '0' : '1' }}">
<button class="warn" type="submit">{{ $user->active ? 'ปิดการใช้งาน' : 'เปิดการใช้งาน' }}</button>
</form>
@endunless
</td>
</tr>
@endforeach
</table>
</main>
</body>
</html>
