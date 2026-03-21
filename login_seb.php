<?php
// Login page shown inside SEB when session is not active
include "config.php";
requireSEB(); // Must be in SEB

if (!empty($_SESSION['student_id'])) {
    // Already logged in — go back based on how SEB was launched
    header("Location: practice_seb_entry.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $u = $conn->real_escape_string(trim($_POST['username'] ?? ''));
    $p = trim($_POST['password'] ?? '');

    if ($u === '' || $p === '') {
        $error = "Please enter your register number and password.";
    } else {
        $row = $conn->query("SELECT * FROM students WHERE regno='$u' LIMIT 1")->fetch_assoc();
        if ($row && ($row['password'] === $p || password_verify($p, $row['password']))) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['name']       = $row['name'];
            $_SESSION['regno']      = $row['regno'];
            // Redirect to whichever SEB entry point referred here
            $ref = $_GET['from'] ?? 'practice';
            header("Location: " . ($ref === 'exam' ? 'exam_select.php' : 'practice_seb_entry.php'));
            exit;
        }
        $error = "Invalid register number or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIET-LMS | Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; display: flex; height: 100vh; overflow: hidden; }
.left {
  flex: 1; background: #004d00; color: #ffff00;
  padding: 4rem 3.5rem;
  display: flex; flex-direction: column; justify-content: center;
}
.left h1 { font-size: 2.6rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
.left p  { font-size: 1.05rem; line-height: 1.8; opacity: .88; }
.left .badge {
  display: inline-flex; align-items: center; gap: 8px; margin-top: 28px;
  background: rgba(255,255,0,.15); border: 1px solid rgba(255,255,0,.3);
  border-radius: 30px; padding: 7px 18px; font-size: 13px; font-weight: 600;
}
.seb-active-badge {
  display: inline-flex; align-items: center; gap: 8px; margin-top: 12px;
  background: rgba(34,197,94,.2); border: 1px solid rgba(34,197,94,.4);
  border-radius: 30px; padding: 7px 18px; font-size: 13px; font-weight: 600; color: #86efac;
}
.right {
  flex: 1; background: #f7e035;
  display: flex; align-items: center; justify-content: center; padding: 2rem;
}
.login-card {
  background: #e6ffe6; padding: 2.6rem 2.4rem; border-radius: 16px;
  width: 100%; max-width: 390px;
  box-shadow: 0 8px 32px rgba(0,0,0,.18); text-align: center;
}
.login-icon  { font-size: 48px; margin-bottom: 10px; }
.login-card h2 { color: #1a5c1a; font-size: 20px; font-weight: 800; margin-bottom: 6px; }
.login-card .subtitle { font-size: 12.5px; color: #4a7c4a; margin-bottom: 22px; }
.error-msg {
  background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px;
  padding: 9px 14px; color: #dc2626; font-size: 13px; font-weight: 600;
  margin-bottom: 14px; text-align: left;
}
.form-group { margin-bottom: 14px; text-align: left; }
.form-group label {
  display: block; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .06em; color: #2d5a2d; margin-bottom: 5px;
}
.form-group input {
  width: 100%; padding: 11px 14px; border-radius: 8px;
  border: 2px solid #a3c65f; font-size: 14px; font-family: inherit;
  outline: none; transition: border-color .15s, box-shadow .15s; background: #fff; color: #1a1a1a;
}
.form-group input:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.12); }
.btn-login {
  width: 100%; padding: 12px; border: none; border-radius: 8px;
  background: #16a34a; color: #fff; font-size: 15px; font-weight: 700;
  font-family: inherit; cursor: pointer; transition: background .2s, transform .1s; margin-top: 4px;
}
.btn-login:hover  { background: #15803d; }
.btn-login:active { transform: scale(.98); }
.hint { font-size: 12px; color: #4a7c4a; margin-top: 16px; line-height: 1.6; }
.hint strong { color: #1a5c1a; }
@media (max-width: 700px) {
  body { flex-direction: column; height: auto; min-height: 100vh; overflow: auto; }
  .left { padding: 2.5rem 2rem; } .left h1 { font-size: 1.9rem; }
  .right { padding: 2rem 1rem 3rem; }
}
</style>
</head>
<body>
<div class="left">
  <h1>Welcome to<br>SIET-LMS</h1>
  <p>Sri Shakthi Institute of Engineering and Technology Learning Management System — programming practice, performance tracking, and secure exams.</p>
  <div class="badge">🛡️ Safe Exam Browser Integrated</div>
  <div class="seb-active-badge">✅ Safe Exam Browser Active</div>
</div>
<div class="right">
  <div class="login-card">
    <div class="login-icon">🎓</div>
    <h2>Student Login</h2>
    <div class="subtitle">Enter your credentials to continue</div>
    <?php if ($error): ?>
    <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="login_seb.php?from=<?= htmlspecialchars($_GET['from'] ?? 'practice') ?>">
      <div class="form-group">
        <label>Register Number</label>
        <input type="text" name="username" placeholder="e.g. 2023CS001"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               autocomplete="username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password"
               autocomplete="current-password" required>
      </div>
      <button type="submit" name="login" class="btn-login">Login →</button>
    </form>
    <div class="hint">
      Demo: <strong>demo</strong> / <strong>demo</strong>
    </div>
  </div>
</div>
</body>
</html>
