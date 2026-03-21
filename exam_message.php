<!DOCTYPE html><html><head><meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;800&display=swap" rel="stylesheet">
<style>*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.box{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:48px 40px;max-width:480px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4);}
.icon{font-size:52px;margin-bottom:14px;}h2{font-size:20px;font-weight:800;color:#e2e8f0;margin-bottom:12px;}
p{font-size:14px;color:#94a3b8;line-height:1.7;}
a{display:inline-block;margin-top:22px;padding:10px 24px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;font-weight:700;}
a:hover{background:#1d4ed8;}</style></head>
<body>
<div class="box">
  <div class="icon">⏳</div>
  <h2>Exam Unavailable</h2>
  <p><?= htmlspecialchars($msg ?? 'This exam is not available right now.') ?></p>
  <a href="mode.php">← Back to Home</a>
</div>
</body></html>
