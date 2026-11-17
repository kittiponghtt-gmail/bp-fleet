<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ · BP Fleet</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#11272d;color:#e7f2f1;font-family:Segoe UI,Tahoma,sans-serif}
form{width:min(420px,calc(100% - 32px));background:#18383f;border:1px solid #2d5c63;border-radius:16px;padding:28px}
h1{margin:0 0 6px;font-size:22px}
p{margin:0 0 18px;color:#9db3b7}
label{display:block;margin:12px 0 6px;font-size:14px}
input{width:100%;box-sizing:border-box;border:1px solid #2d5c63;background:#0f2429;color:#fff;border-radius:10px;padding:12px}
button{margin-top:18px;width:100%;border:0;border-radius:10px;padding:12px;background:#2f9e8f;color:#fff;font-weight:700;cursor:pointer}
.error{color:#ffb4a8;font-size:14px;margin-top:8px}
</style>
</head>
<body>
@if (!empty($setup))
<form method="post" action="/setup">
@csrf
<h1>BP Fleet</h1>
<p>สร้างผู้ใช้คนแรก แล้วจะเข้าสู่ระบบให้ทันที</p>
<label for="display_name">ชื่อที่แสดง</label>
<input id="display_name" name="display_name" value="{{ old('display_name') }}" required>
@error('display_name')<div class="error">{{ $message }}</div>@enderror
<label for="username">ชื่อผู้ใช้</label>
<input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required>
@error('username')<div class="error">{{ $message }}</div>@enderror
<label for="password">รหัสผ่าน</label>
<input id="password" name="password" type="password" autocomplete="new-password" required>
@error('password')<div class="error">{{ $message }}</div>@enderror
<button type="submit">สร้างผู้ใช้และเข้าสู่ระบบ</button>
</form>
@else
<form method="post" action="/login">
@csrf
<h1>BP Fleet</h1>
<p>เข้าสู่ระบบเพื่อใช้ข้อมูลรถชุดเดียวกัน</p>
<label for="username">ชื่อผู้ใช้</label>
<input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required>
@error('username')<div class="error">{{ $message }}</div>@enderror
<label for="password">รหัสผ่าน</label>
<input id="password" name="password" type="password" autocomplete="current-password" required>
<button type="submit">เข้าสู่ระบบ</button>
</form>
@endif
</body>
</html>
