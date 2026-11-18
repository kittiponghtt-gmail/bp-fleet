<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เปลี่ยนรหัสผ่าน · BP Fleet</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#11272d;color:#e7f2f1;font-family:Segoe UI,Tahoma,sans-serif}
form{width:min(460px,calc(100% - 32px));background:#18383f;border:1px solid #2d5c63;border-radius:16px;padding:28px}
a{color:#8ee0d2}
h1{margin:8px 0 6px;font-size:22px}
p{margin:0 0 8px;color:#9db3b7}
label{display:block;margin:12px 0 6px;font-size:14px}
input{width:100%;box-sizing:border-box;border:1px solid #2d5c63;background:#0f2429;color:#fff;border-radius:10px;padding:12px}
button{margin-top:18px;width:100%;border:0;border-radius:10px;padding:12px;background:#2f9e8f;color:#fff;font-weight:700;cursor:pointer}
.error{color:#ffb4a8;font-size:13px}
.status{color:#8ee0d2}
</style>
</head>
<body>
<form method="post" action="/password">
@csrf
<p><a href="/">กลับไปหน้ารถ</a></p>
<h1>เปลี่ยนรหัสผ่าน</h1>
<p>ใส่รหัสผ่านเดิมก่อน แล้วตั้งรหัสใหม่อย่างน้อย 6 ตัว</p>
@if (session('status'))<p class="status">{{ session('status') }}</p>@endif
<label for="current_password">รหัสผ่านเดิม</label>
<input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
@error('current_password')<div class="error">{{ $message }}</div>@enderror
<label for="password">รหัสผ่านใหม่</label>
<input id="password" name="password" type="password" autocomplete="new-password" required>
@error('password')<div class="error">{{ $message }}</div>@enderror
<label for="password_confirmation">ยืนยันรหัสผ่านใหม่</label>
<input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
<button type="submit">บันทึกรหัสผ่านใหม่</button>
</form>
</body>
</html>
