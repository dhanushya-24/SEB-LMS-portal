<?php
// Clear exam lock on exit so student can restart cleanly
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['exam_locked'], $_SESSION['locked_exam_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exiting Safe Exam Browser — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Poppins', sans-serif; background: #f0f4f8;
  min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.box {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
  padding: 48px 44px; max-width: 440px; width: 100%; text-align: center;
  box-shadow: 0 8px 32px rgba(0,0,0,.08);
}
.icon { font-size: 64px; margin-bottom: 20px; display: block; }
h1 { font-size: 22px; font-weight: 800; color: #1a1a1a; margin-bottom: 10px; }
p { font-size: 14px; color: #6b7280; line-height: 1.7; margin-bottom: 16px; }
.note {
  background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px;
  padding: 12px 16px; font-size: 13px; color: #9a3412; font-weight: 600; margin-top: 16px;
}
</style>
<script>
// SEB intercepts seb://quit and closes the browser
window.onload = function() {
  window.location.href = 'seb://quit';
};
</script>
</head>
<body>
<div class="box">
  <span class="icon">🚪</span>
  <h1>Closing Safe Exam Browser</h1>
  <p>SEB is closing. You will be returned to your normal browser automatically.</p>
  <p>Your progress has been saved.</p>
  <div class="note">
    If SEB asks for a quit password, enter: <strong>exit456</strong>
  </div>
</div>
</body>
</html>
