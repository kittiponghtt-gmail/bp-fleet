<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ผู้ใช้งาน · BP Fleet</title>
<style>
body{margin:0;background:#11272d;color:#e7f2f1;font-family:Segoe UI,Tahoma,sans-serif}
main{max-width:760px;margin:0 auto;padding:28px 16px 48px}
a{color:#8ee0d2}
h1{margin:0 0 16px}
form,table{background:#18383f;border:1px solid #2d5c63;border-radius:16px}
form{padding:18px;display:grid;gap:10px;margin-bottom:18px}
label{font-size:14px}
input{border:1px solid #2d5c63;background:#0f2429;color:#fff;border-radius:10px;padding:10px}
button{border:0;border-radius:10px;padding:12px;background:#2f9e8f;color:#fff;font-weight:700;cursor:pointer}
table{width:100%;border-collapse:collapse;overflow:hidden}
th,td{text-align:left;padding:12px 14px;border-bottom:1px solid #2d5c63}
.error{color:#ffb4a8;font-size:13px}
.status{color:#8ee0d2}
</style>
</head>
<body>
<main>
<p><a href="/">กลับไปหน้ารถ</a></p>
<h1>ผู้ใช้งาน</h1>
@if (session('status'))<p class="status">{{ session('status') }}</p>@endif
<form method="post" action="/users">
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
<button type="submit">เพิ่มผู้ใช้</button>
</form>
<table>
<tr><th>ชื่อผู้ใช้</th><th>ชื่อที่แสดง</th></tr>
@foreach ($users as $user)
<tr><td>{{ $user->username }}</td><td>{{ $user->display_name }}</td></tr>
@endforeach
</table>
</main>
</body>
</html>
