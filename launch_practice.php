<?php
include "config.php";
requireLogin();
$student = currentStudent();
$name    = htmlspecialchars($student['name']);

$startUrl     = "http://localhost/SEB-LMS-portal/practice_seb_entry.php";
$sebLaunchUrl = str_replace("http://", "seb://", $startUrl);
$configFile   = "seb_configs/practice_mode.seb";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Practice Mode — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', Arial, sans-serif; background: #fff; color: #1a1a1a; min-height: 100vh; }

.site-header {
  border-bottom: 1px solid #ddd; padding: 10px 24px;
  display: flex; align-items: center; gap: 14px; background: #fff;
}
.site-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #1a1a1a; }
.logo-icon {
  width: 48px; height: 48px;
  background: linear-gradient(135deg, #16a34a, #22c55e);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 22px; flex-shrink: 0;
}
.logo-text { font-size: 16px; font-weight: 700; color: #1a1a1a; line-height: 1; }
.logo-sub  { font-size: 10px; color: #16a34a; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }
.header-right { margin-left: auto; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #6b7280; }
.header-right a { color: #374151; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 13px; transition: .15s; }
.header-right a:hover { background: #f3f4f6; }
.btn-logout-sm { background: #fee2e2 !important; color: #dc2626 !important; border: 1px solid #fecaca; border-radius: 6px; }
.btn-logout-sm:hover { background: #fecaca !important; }

.main-wrap { max-width: 960px; margin: 0 auto; padding: 32px 24px 60px; }
.breadcrumb { font-size: 12.5px; color: #6b7280; margin-bottom: 24px; }
.breadcrumb a { color: #2563eb; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb span { margin: 0 6px; }
.page-title { font-size: 26px; font-weight: 800; color: #1a1a1a; margin-bottom: 22px; letter-spacing: -.3px; }

.info-box {
  background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px;
  padding: 18px 22px; margin-bottom: 28px; font-size: 14px; line-height: 1.8;
}
.info-box strong { font-weight: 700; }

.detail-list { list-style: none; margin-bottom: 28px; padding: 0; }
.detail-list li { font-size: 14px; color: #374151; padding: 5px 0; border-bottom: 1px solid #f3f4f6; line-height: 1.65; }
.detail-list li:last-child { border-bottom: none; }

.action-buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.btn-action {
  padding: 10px 22px; border: 1px solid #9ca3af; border-radius: 5px;
  background: #e5e7eb; color: #374151; font-family: 'Poppins', sans-serif;
  font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none;
  display: inline-flex; align-items: center; gap: 7px; transition: background .15s, border-color .15s;
}
.btn-action:hover { background: #d1d5db; border-color: #6b7280; text-decoration: none; color: #1a1a1a; }
.btn-action.primary { background: #16a34a; border-color: #15803d; color: #fff; font-weight: 700; }
.btn-action.primary:hover { background: #15803d; color: #fff; }
.btn-action.blue { background: #2563eb; border-color: #1d4ed8; color: #fff; font-weight: 700; }
.btn-action.blue:hover { background: #1d4ed8; color: #fff; }

.btn-back { padding: 9px 20px; border: 1px solid #9ca3af; border-radius: 5px; background: #e5e7eb; color: #374151; font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .15s; }
.btn-back:hover { background: #d1d5db; text-decoration: none; color: #1a1a1a; }

.seb-warning { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 14px 18px; font-size: 13.5px; color: #9a3412; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start; line-height: 1.65; }

.help-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-top: 24px; line-height: 1.7; }
.help-box strong { color: #78350f; }
</style>
</head>
<body>

<div class="site-header">
  <a href="mode.php" class="site-logo">
    <div class="logo-icon">🎓</div>
    <div>
      <div class="logo-text">SIET – LMS</div>
      <div class="logo-sub">Sri Shakthi Institute</div>
    </div>
  </a>
  <div class="header-right">
    <span>👤 <?= $name ?></span>
    <a href="mode.php">← Home</a>
    <a href="logout.php" class="btn-logout-sm">Logout</a>
  </div>
</div>

<div class="main-wrap">
  <div class="breadcrumb">
    <a href="mode.php">Home</a><span>›</span><span>Practice Mode</span>
  </div>

  <div class="page-title">PRACTICE MODE — CODING</div>

  <div class="seb-warning">
    <span style="font-size:20px;flex-shrink:0">⚠️</span>
    <div>
      <strong>Safe Exam Browser not detected.</strong><br>
      To open Practice Mode, you must launch it through Safe Exam Browser. Click <strong>"Launch Safe Exam Browser"</strong> below, or download and open the <strong>configuration file</strong>.
    </div>
  </div>

  <div class="info-box">
    <strong>Opened:</strong> <?= date("l, d F Y, g:i A") ?><br>
    <strong>Mode:</strong> Practice — no time limit, attempt as many times as you like
  </div>

  <ul class="detail-list">
    <li>Attempts allowed: <strong>Unlimited</strong></li>
    <li>To open this practice session you must launch Safe Exam Browser using the button below.</li>
    <li>This practice environment has been configured so that students may only use it inside Safe Exam Browser.</li>
    <li>Time limit: <strong>None</strong></li>
    <li>Progress tracking: <strong>Enabled</strong> — your solved questions are saved automatically</li>
    <li>Languages available: <strong>Python 3, C, C++, Java</strong></li>
  </ul>

  <div class="action-buttons">
    <a href="https://safeexambrowser.org/download_en.html" target="_blank" class="btn-action">
      ⬇ Download Safe Exam Browser
    </a>
    <a href="<?= htmlspecialchars($sebLaunchUrl) ?>" class="btn-action blue" id="btnLaunch">
      🚀 Launch Safe Exam Browser
    </a>
    <a href="<?= htmlspecialchars($configFile) ?>" download="practice_mode.seb" class="btn-action">
      📥 Download configuration
    </a>
  </div>

  <div style="margin-top:14px">
    <a href="mode.php" class="btn-back">← Back to home</a>
  </div>

  <div class="help-box">
    <strong>How to open Practice Mode in Safe Exam Browser:</strong><br><br>
    <strong>Method 1 — Launch button (easiest):</strong><br>
    1. Click <em>"Launch Safe Exam Browser"</em> above<br>
    2. Your browser will ask permission to open SEB — click <strong>Open</strong><br>
    3. SEB will open and load the login page automatically<br><br>
    <strong>Method 2 — Configuration file:</strong><br>
    1. Click <em>"Download configuration"</em> to download <code>practice_mode.seb</code><br>
    2. Double-click the downloaded file<br>
    3. Windows may ask "How do you want to open this?" — select <strong>Safe Exam Browser</strong><br>
    4. SEB opens and loads the login page<br><br>
    <strong>Method 3 — Open .seb file directly:</strong><br>
    1. Go to <code>C:\xampp\htdocs\SEB-LMS-portal\seb_configs\</code><br>
    2. Double-click <code>practice_mode.seb</code><br>
    3. If asked, choose to open with <strong>Safe Exam Browser</strong>
  </div>
</div>

<script>
document.getElementById('btnLaunch').addEventListener('click', function(e) {
  setTimeout(function() {
    var msg = document.createElement('div');
    msg.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;padding:14px 20px;border-radius:10px;font-size:13px;font-family:Poppins,sans-serif;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);max-width:320px;line-height:1.5';
    msg.innerHTML = '🚀 If SEB did not open:<br><small style="font-weight:400">Try downloading the <b>configuration file</b> below and double-clicking it.</small>';
    document.body.appendChild(msg);
    setTimeout(function(){ msg.remove(); }, 7000);
  }, 2000);
});
</script>
</body>
</html>
