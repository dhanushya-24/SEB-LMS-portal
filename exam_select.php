<?php
// This page is the SEB start URL for Exam Mode.
// SEB lands here → if not logged in, redirects to login_seb.php
// If logged in, shows exam selection
include "config.php";
requireSEB(); // Must be in SEB
checkExamLock();

if (empty($_SESSION['student_id'])) {
    header("Location: login_seb.php");
    exit;
}

$student = currentStudent();
$name    = htmlspecialchars($student['name']);
$uid     = (int)$student['id'];

$exams = [];
$res = mysqli_query($conn,
    "SELECT e.*, s.subject_name FROM exams e
     LEFT JOIN subjects s ON e.subject_id=s.id
     WHERE e.is_active=1 ORDER BY e.id ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $att = mysqli_fetch_row(mysqli_query($conn,
        "SELECT submitted FROM exam_attempts
         WHERE exam_id={$row['id']} AND student_id=$uid LIMIT 1"));
    $row['submitted'] = $att ? (int)$att[0] : 0;
    $row['in_progress'] = ($att && !$att[0]) ? 1 : 0;
    $exams[] = $row;
}

function getIcon($name) {
    foreach (['Java'=>'☕','Python'=>'🐍','C Programming'=>'⚙️',
               'Data Struct'=>'🏗️','Advanced DSA'=>'🚀','Database'=>'🗄️',
               'Design'=>'📐','Placement'=>'🎯'] as $k=>$v)
        if (stripos($name,$k) !== false) return $v;
    return '📝';
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Exam Mode — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#f0f4f8;min-height:100vh;}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 28px;
  height:54px;display:flex;align-items:center;gap:14px;}
.brand{font-size:15px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:8px;}
.hdr-r{margin-left:auto;display:flex;align-items:center;gap:10px;font-size:13px;}
.hdr-r span{color:#64748b;}
.seb-pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
  background:#dcfce7;border:1px solid #86efac;color:#15803d;border-radius:20px;
  padding:4px 12px;}
.btn-exit-seb{padding:6px 16px;background:#fee2e2;border:1.5px solid #fca5a5;
  color:#dc2626;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;
  font-family:inherit;}
.btn-exit-seb:hover{background:#fecaca;}
.main{max-width:960px;margin:0 auto;padding:30px 22px 60px;}
.bc{font-size:12.5px;color:#6b7280;margin-bottom:18px;}
.ptitle{font-size:26px;font-weight:800;margin-bottom:4px;color:#0f172a;}
.psub{font-size:14px;color:#64748b;margin-bottom:22px;}
.info-bar{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;
  padding:13px 18px;font-size:13px;color:#1e40af;margin-bottom:24px;line-height:1.7;}
.exams-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;}
@media(max-width:680px){.exams-grid{grid-template-columns:1fr;}}
.ec{background:#fff;border:1.5px solid #e5e7eb;border-radius:14px;
  padding:22px;transition:.2s;display:flex;flex-direction:column;gap:12px;}
.ec:hover{box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-2px);}
.ec-top{display:flex;align-items:flex-start;gap:14px;}
.ec-icon{width:52px;height:52px;border-radius:12px;background:#eff6ff;
  display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;}
.ec-title{font-size:15px;font-weight:800;color:#0f172a;margin-bottom:3px;}
.ec-sub{font-size:12px;color:#64748b;margin-bottom:8px;}
.ec-meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center;font-size:11.5px;color:#6b7280;}
.bdg{font-size:10px;font-weight:800;padding:3px 9px;border-radius:20px;text-transform:uppercase;}
.bdg-open{background:#dcfce7;color:#15803d;}
.bdg-progress{background:#fef9c3;color:#92400e;}
.bdg-done{background:#dbeafe;color:#1e40af;}
.ec-pw{background:#fefce8;border:1px solid #fde047;border-radius:8px;
  padding:9px 14px;font-size:11.5px;color:#713f12;line-height:2.1;}
.ec-pw strong{color:#0f172a;}
.ec-actions{display:flex;gap:8px;}
.btn-enter{padding:10px 20px;background:#2563eb;border:none;border-radius:8px;
  color:#fff;font-size:13px;font-weight:800;font-family:inherit;cursor:pointer;
  text-decoration:none;transition:.15s;flex:1;text-align:center;display:block;}
.btn-enter:hover{background:#1d4ed8;color:#fff;text-decoration:none;}
.btn-continue{padding:10px 20px;background:#f59e0b;border:none;border-radius:8px;
  color:#fff;font-size:13px;font-weight:800;font-family:inherit;cursor:pointer;
  text-decoration:none;flex:1;text-align:center;display:block;}
.btn-continue:hover{background:#d97706;color:#fff;text-decoration:none;}
.btn-result{padding:10px 20px;background:#f1f5f9;border:1px solid #e2e8f0;
  border-radius:8px;color:#374151;font-size:13px;font-weight:700;
  text-decoration:none;flex:1;text-align:center;display:block;}
.btn-result:hover{background:#e2e8f0;text-decoration:none;}
.help-box{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;
  padding:18px;font-size:13px;color:#92400e;margin-top:28px;line-height:2.1;}
</style>
</head><body>
<div class="topbar">
  <span class="brand">🛡️ SIET-LMS</span>
  <div class="hdr-r">
    <span class="seb-pill">🔒 SEB Active</span>
    <span>👤 <?=$name?></span>
    <button class="btn-exit-seb" onclick="exitSEB()">✕ Exit SEB</button>
  </div>
</div>
<div class="main">
  <div class="bc">Home › Exam Mode</div>
  <div class="ptitle">📝 Exam Mode</div>
  <div class="psub">Select your subject exam. Each exam runs 90 minutes and requires entry + exit password.</div>
  <div class="info-bar">
    ℹ️ <strong>How it works:</strong> Click "Enter Exam" → type the entry password given by your invigilator → exam starts.
    Once inside, you are <strong>locked to the exam</strong> until you submit with the exit password.
  </div>
  <div class="exams-grid">
    <?php foreach($exams as $e):
      $icon  = getIcon($e['subject_name'] ?? '');
      $isDone = (bool)$e['submitted'];
      $inProg = (bool)$e['in_progress'];
    ?>
    <div class="ec">
      <div class="ec-top">
        <div class="ec-icon"><?=$icon?></div>
        <div style="flex:1;">
          <div class="ec-title"><?=htmlspecialchars($e['title'])?></div>
          <div class="ec-sub"><?=htmlspecialchars($e['subject_name']??'General')?></div>
          <div class="ec-meta">
            <span class="bdg <?=$isDone?'bdg-done':($inProg?'bdg-progress':'bdg-open')?>">
              <?=$isDone?'Completed':($inProg?'In Progress':'Open')?>
            </span>
            <span>⏱ <?=$e['duration_min']?> min</span>
            <span>📊 <?=$e['total_marks']?> marks</span>
          </div>
        </div>
      </div>
      <?php if(!$isDone):?>
      <div class="ec-pw">
        <strong>Entry Password:</strong> <code><?=htmlspecialchars($e['entry_password'])?></code>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Exit Password:</strong> <code><?=htmlspecialchars($e['exit_password'])?></code>
      </div>
      <?php endif;?>
      <div class="ec-actions">
        <?php if($isDone):?>
          <a href="exam_result.php?eid=<?=$e['id']?>" class="btn-result">📊 View Result</a>
        <?php elseif($inProg):?>
          <a href="exam.php?eid=<?=$e['id']?>" class="btn-continue">▶ Continue Exam</a>
        <?php else:?>
          <a href="exam.php?eid=<?=$e['id']?>" class="btn-enter">🚀 Enter Exam</a>
        <?php endif;?>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <div class="help-box">
    <strong>📋 Exam Structure per subject:</strong><br>
    Part A → 6 MCQ questions × 1 mark = 6 marks<br>
    Part B → 2 Coding questions × 7 marks = 14 marks<br>
    Part C → 1 Long Answer question × 10 marks = 10 marks<br>
    <strong>Total: 30 marks</strong>
  </div>
</div>
<script>
function exitSEB() {
  if (confirm('Are you sure you want to exit Safe Exam Browser?')) {
    window.location.href = 'seb_quit.php';
  }
}
</script>
</body></html>
