<?php
include "config.php";
requireLogin();
checkExamLock();
$name = htmlspecialchars($_SESSION['name'] ?? 'Student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mode Selection — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#f0f4f8;min-height:100vh;}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 32px;height:58px;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:10px;}
.brand-logo{width:34px;height:34px;background:linear-gradient(135deg,#f97316,#ef4444);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:17px;}
.brand-name{font-size:16px;font-weight:800;color:#1e293b;letter-spacing:-.3px;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.user-label{font-size:13px;color:#64748b;font-weight:500;display:flex;align-items:center;gap:6px;}
.btn-logout{padding:7px 18px;background:#fff;border:1.5px solid #e2e8f0;color:#374151;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:.15s;}
.btn-logout:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626;text-decoration:none;}
.page{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:calc(100vh - 58px);padding:40px 20px;}
.welcome-block{text-align:left;width:100%;max-width:740px;margin-bottom:32px;}
.welcome-title{font-size:28px;font-weight:800;color:#0f172a;}
.welcome-sub{font-size:14px;color:#64748b;margin-top:5px;}
.cards{display:grid;grid-template-columns:1fr 1fr;gap:24px;width:100%;max-width:740px;}
@media(max-width:600px){.cards{grid-template-columns:1fr;}}
.card{border-radius:20px;padding:36px 28px;text-decoration:none;display:block;transition:.25s;position:relative;overflow:hidden;}
.card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,.18);text-decoration:none;}
.card-practice{background:linear-gradient(135deg,#ec4899 0%,#f97316 100%);}
.card-exam{background:linear-gradient(135deg,#3b82f6 0%,#6366f1 100%);}
.card-icon-wrap{width:58px;height:58px;background:rgba(255,255,255,.22);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:18px;}
.card h2{font-size:22px;font-weight:800;color:#fff;margin-bottom:8px;}
.card p{font-size:13px;color:rgba(255,255,255,.88);line-height:1.6;}
.card-arrow{position:absolute;bottom:26px;right:26px;font-size:20px;color:rgba(255,255,255,.5);}
footer{text-align:center;font-size:12px;color:#94a3b8;padding:20px;}
</style>
</head>
<body>
<div class="topbar">
  <div class="brand">
    <div class="brand-logo">🎓</div>
    <span class="brand-name">SIET-LMS</span>
  </div>
  <div class="topbar-right">
    <span class="user-label">👤 <?= $name ?></span>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</div>
<div class="page">
  <div class="welcome-block">
    <div class="welcome-title">Welcome, <?= $name ?> 👋</div>
    <div class="welcome-sub">Select your working environment to get started</div>
  </div>
  <div class="cards">
    <!-- Practice Mode: opens SEB launch page -->
    <a class="card card-practice" href="launch_practice.php">
      <div class="card-icon-wrap">💻</div>
      <h2>Practice Mode</h2>
      <p>Learning &amp; coding practice environment</p>
      <span class="card-arrow">→</span>
    </a>
    <!-- Exam Mode: opens SEB launch page -->
    <a class="card card-exam" href="launch_exam.php">
      <div class="card-icon-wrap">🛡️</div>
      <h2>Exam Mode</h2>
      <p>Secure Safe Exam Browser environment</p>
      <span class="card-arrow">→</span>
    </a>
  </div>
</div>
<footer>© 2026 SIET-LMS | Safe Exam Browser Integrated</footer>
</body>
</html>
