<?php
// admin/index.php
session_start();
if (!empty($_SESSION['admin'])) { header("Location: dashboard.php"); exit; }
$conn  = new mysqli("localhost","root","","seb_lms");
$error = "";
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])) {
    $u = $conn->real_escape_string(trim($_POST['username']??''));
    $row = $conn->query("SELECT * FROM admin_users WHERE username='$u' LIMIT 1")->fetch_assoc();
    if ($row && password_verify(trim($_POST['password']??''), $row['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $row['username'];
        header("Location: dashboard.php"); exit;
    }
    $error = "Invalid credentials.";
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin Login — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#1e293b;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.box{background:#fff;border-radius:14px;padding:34px 30px;width:350px;box-shadow:0 20px 50px rgba(0,0,0,.4);}
h2{font-size:19px;font-weight:800;text-align:center;margin-bottom:22px;color:#0f172a;}
.err{color:#dc2626;font-size:13px;font-weight:600;margin-bottom:12px;text-align:center;}
input{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:13px;font-family:inherit;margin-bottom:12px;outline:none;transition:.15s;}
input:focus{border-color:#16a34a;}
button{width:100%;padding:10px;background:#16a34a;color:#fff;border:none;border-radius:7px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;}
button:hover{background:#15803d;}
.hint{text-align:center;font-size:11px;color:#94a3b8;margin-top:12px;}</style></head>
<body><div class="box"><h2>🔐 Admin Login</h2>
<?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="POST"><input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Sign In</button></form>
<div class="hint">admin / admin123</div></div></body></html>
