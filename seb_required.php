<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Safe Exam Browser Required — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Poppins', sans-serif;
  background: #f0f4f8;
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.box {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
  padding: 48px 44px; max-width: 520px; width: 100%; text-align: center;
  box-shadow: 0 8px 32px rgba(0,0,0,.08);
}
.shield { font-size: 68px; margin-bottom: 20px; display: block; }
h1 { font-size: 22px; font-weight: 800; color: #1a1a1a; margin-bottom: 10px; }
.sub { font-size: 14px; color: #6b7280; line-height: 1.8; margin-bottom: 24px; }
.warn-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: #fff7ed; border: 1px solid #fed7aa;
  border-radius: 8px; padding: 10px 18px;
  font-size: 13px; color: #9a3412; font-weight: 600; margin-bottom: 24px;
}
.steps {
  text-align: left; background: #f9fafb; border: 1px solid #e5e7eb;
  border-radius: 10px; padding: 20px 22px;
  font-size: 13px; color: #374151; line-height: 2.2; margin-bottom: 24px;
}
.steps code {
  background: #e5e7eb; padding: 2px 7px; border-radius: 4px;
  font-family: monospace; color: #7c3aed; font-size: 12px;
}
.steps strong { color: #1a1a1a; }
.method { margin-bottom: 14px; }
.method:last-child { margin-bottom: 0; }
.method-label {
  display: inline-block; font-size: 10px; font-weight: 800;
  padding: 2px 10px; border-radius: 20px;
  text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px;
}
.label-green { background: #dcfce7; color: #15803d; }
.label-blue  { background: #dbeafe; color: #1e40af; }
.label-gray  { background: #f1f5f9; color: #475569; }
.divider { border: none; border-top: 1px solid #e5e7eb; margin: 20px 0; }
.btn {
  display: inline-block; padding: 11px 28px; border-radius: 8px;
  color: #fff; font-size: 14px; font-weight: 700;
  text-decoration: none; font-family: 'Poppins', sans-serif;
  transition: background .15s;
}
.btn-green { background: #16a34a; } .btn-green:hover { background: #15803d; color:#fff; text-decoration:none; }
.foot { font-size: 12px; color: #94a3b8; margin-top: 16px; }
</style>
</head>
<body>
<div class="box">
  <span class="shield">🛡️</span>
  <h1>Safe Exam Browser Required</h1>
  <p class="sub">
    This page must be accessed through <strong>Safe Exam Browser (SEB)</strong>.<br>
    Please close this browser and launch SEB using one of the methods below.
  </p>
  <div class="warn-badge">⚠️ You are currently in a regular browser</div>

  <div class="steps">
    <div class="method">
      <span class="method-label label-green">Method 1 — Easiest</span><br>
      <strong>Double-click the BAT file in your project folder:</strong><br>
      • Practice: <code>open_practice.bat</code><br>
      • Exam: <code>open_exam.bat</code><br>
      <span style="font-size:12px;color:#9ca3af;">Located at: C:\xampp\htdocs\SEB-LMS-portal\</span>
    </div>
    <div class="method" style="border-top:1px solid #e5e7eb;padding-top:14px;margin-top:14px;">
      <span class="method-label label-blue">Method 2 — Config File</span><br>
      <strong>Double-click the .seb file directly:</strong><br>
      • <code>seb_configs\practice_mode.seb</code><br>
      • <code>seb_configs\exam_mode.seb</code><br>
      <span style="font-size:12px;color:#9ca3af;">When asked, choose to open with Safe Exam Browser</span>
    </div>
  </div>

  <hr class="divider">
  <p style="font-size:13px;color:#6b7280;margin-bottom:14px;font-weight:600;">SEB not installed?</p>
  <a href="https://safeexambrowser.org/download_en.html" class="btn btn-green" target="_blank">
    ⬇ Download Safe Exam Browser
  </a>
  <p class="foot">After installing, close this tab and use one of the methods above.</p>
</div>
</body>
</html>
