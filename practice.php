<?php
include "config.php";
requireLogin();
requireSEB();      // Practice must run inside SEB
checkExamLock();
$student = currentStudent();
$sid = $student['id'];

$subjects = [];
$res = mysqli_query($conn, "SELECT * FROM subjects ORDER BY id ASC");
while ($s = mysqli_fetch_assoc($res)) {
    $subjectId = $s['id'];
    $total  = (int)mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM questions WHERE subject_id=$subjectId"))[0];
    $solved = (int)mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM user_progress up JOIN questions q ON up.question_id=q.id WHERE q.subject_id=$subjectId AND up.user_id=$sid AND up.status='solved'"))[0];
    $modules_count = (int)mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM modules WHERE subject_id=$subjectId"))[0];
    $percent = ($total > 0) ? round(($solved / $total) * 100) : 0;
    $subjects[] = array_merge($s, ['total'=>$total,'solved'=>$solved,'percent'=>$percent,'modules'=>$modules_count]);
}

$icons = ['☕','🐍','⚙️','🏗️','🚀','🗄️','📐','🎯'];
$colors = [
    'linear-gradient(135deg,#f7971e,#ffd200)',
    'linear-gradient(135deg,#43e97b,#38f9d7)',
    'linear-gradient(135deg,#74b9ff,#a29bfe)',
    'linear-gradient(135deg,#f9d423,#f7971e)',
    'linear-gradient(135deg,#ff6b81,#ee5a24)',
    'linear-gradient(135deg,#a855f7,#7c3aed)',
    'linear-gradient(135deg,#11998e,#38ef7d)',
    'linear-gradient(135deg,#2563eb,#38bdf8)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Practice Mode — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f8f9fa;color:#1a1a2e;min-height:100vh;}
.header{background:#fff;padding:14px 32px;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.header-brand{font-size:17px;font-weight:800;color:#1a1a2e;}
.header-right{display:flex;align-items:center;gap:8px;}
.header-right a{font-size:13px;font-weight:500;color:#6c757d;text-decoration:none;padding:6px 14px;border-radius:7px;transition:.15s;}
.header-right a:hover{background:#f1f3f5;color:#1a1a2e;}
.btn-exit{background:#16a34a!important;color:#fff!important;font-weight:700!important;}
.btn-exit:hover{background:#15803d!important;}
.page{max-width:1200px;margin:0 auto;padding:32px 28px 60px;}
.page-title{font-size:24px;font-weight:800;color:#1a1a2e;margin-bottom:4px;}
.page-sub{font-size:14px;color:#6c757d;margin-bottom:28px;}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;}
@media(max-width:1100px){.grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:800px){.grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.grid{grid-template-columns:1fr;}}
.card{background:#fff;border-radius:18px;overflow:hidden;border:1px solid #e9ecef;transition:transform .2s,box-shadow .2s;cursor:pointer;text-decoration:none;color:inherit;display:block;}
.card:hover{transform:translateY(-5px);box-shadow:0 14px 36px rgba(0,0,0,.12);text-decoration:none;color:inherit;}
.card-banner{width:100%;height:130px;display:flex;align-items:center;justify-content:center;position:relative;}
.card-icon-big{font-size:48px;}
.card-body{padding:16px 18px 10px;}
.card-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:2px 10px;display:inline-block;margin-bottom:8px;}
.card-title{font-size:15px;font-weight:800;color:#1a1a2e;margin-bottom:3px;}
.card-desc{font-size:11.5px;color:#6c757d;line-height:1.5;margin-bottom:10px;}
.card-modules{font-size:11px;color:#94a3b8;margin-bottom:8px;}
.progress-wrap{margin-top:8px;}
.progress-label{display:flex;justify-content:space-between;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:5px;}
.progress-bar{height:7px;background:#e5e7eb;border-radius:99px;overflow:hidden;}
.progress-fill{height:100%;border-radius:99px;background:#16a34a;transition:width .6s;}
.card-foot{padding:10px 18px 14px;font-size:12.5px;font-weight:700;color:#495057;border-top:1px solid #f1f3f5;display:flex;align-items:center;justify-content:space-between;}
.card-foot span{color:#16a34a;}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:999;backdrop-filter:blur(3px);}
.modal-overlay.show{display:flex;}
.modal{background:#fff;border-radius:16px;padding:36px 32px;max-width:440px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.modal-icon{font-size:48px;margin-bottom:14px;}
.modal h2{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:8px;}
.modal p{font-size:14px;color:#64748b;line-height:1.65;margin-bottom:22px;}
.btn-confirm{padding:10px 28px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;margin-right:8px;}
.btn-confirm:hover{background:#b91c1c;}
.btn-cancel{padding:10px 22px;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;}
</style>
</head>
<body>
<div class="header">
  <span class="header-brand">📚 SIET-LMS — Practice Mode</span>
  <div class="header-right">
    <span style="font-size:13px;color:#6c757d">👤 <?=htmlspecialchars($student['name'])?></span>
    <a href="mode.php">← Home</a>
    <a href="#" class="btn-exit" onclick="document.getElementById('exitModal').classList.add('show');return false;">Exit SEB</a>
  </div>
</div>
<div class="page">
  <div class="page-title">📖 Choose a Subject</div>
  <div class="page-sub"><?=count($subjects)?> subjects available — each with 4 modules and multiple coding problems</div>
  <div class="grid">
    <?php foreach($subjects as $i=>$s): 
      $pct = $s['percent'];
      $icon = $icons[$i % count($icons)];
      $color = $colors[$i % count($colors)];
    ?>
    <a class="card" href="subject.php?id=<?=$s['id']?>">
      <div class="card-banner" style="background:<?=$color?>">
        <span class="card-icon-big"><?=$icon?></span>
      </div>
      <div class="card-body">
        <div class="card-label">📚 Training</div>
        <div class="card-title"><?=htmlspecialchars($s['subject_name'])?></div>
        <div class="card-desc"><?=htmlspecialchars($s['description']??'')?></div>
        <div class="card-modules">📦 <?=$s['modules']?> Modules · <?=$s['total']?> Problems</div>
        <div class="progress-wrap">
          <div class="progress-label">
            <span><?=$s['solved']?>/<?=$s['total']?> solved</span>
            <span><?=$pct?>%</span>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?=$pct?>%;background:<?=$pct>=100?'#16a34a':($pct>=50?'#f59e0b':'#ef4444')?>"></div>
          </div>
        </div>
      </div>
      <div class="card-foot">View modules <span>→</span></div>
    </a>
    <?php endforeach;?>
  </div>
</div>
<div class="modal-overlay" id="exitModal">
  <div class="modal">
    <div class="modal-icon">🏁</div>
    <h2>Exit Practice Mode?</h2>
    <p>Your progress is saved automatically. Safe Exam Browser will close and return you to the normal browser.</p>
    <button class="btn-confirm" onclick="window.location.href='seb_quit.php'">Yes, Exit SEB</button>
    <button class="btn-cancel" onclick="document.getElementById('exitModal').classList.remove('show')">Cancel</button>
  </div>
</div>
</body>
</html>
