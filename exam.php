<?php
include "config.php";
requireLogin();
requireSEB();

$student = currentStudent();
$uid  = (int)$student['id'];
$name = htmlspecialchars($student['name']);

// Redirect if locked to a different exam
if (!empty($_SESSION['exam_locked']) && !empty($_SESSION['locked_exam_id'])) {
    $lockedEid = (int)$_SESSION['locked_exam_id'];
    $eid = (int)($_GET['eid'] ?? 0);
    if ($eid && $eid !== $lockedEid) {
        header("Location: exam.php?eid=$lockedEid"); exit;
    }
}

$eid = (int)($_GET['eid'] ?? 0);
if (!$eid) { header("Location: exam_select.php"); exit; }

$exam = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT e.*, s.subject_name FROM exams e
     LEFT JOIN subjects s ON e.subject_id=s.id
     WHERE e.id=$eid AND e.is_active=1 LIMIT 1"));
if (!$exam) { header("Location: exam_select.php"); exit; }

// Entry password
$authKey    = "exam_auth_$eid";
$entryError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_password'])) {
    if (trim($_POST['entry_password']) === trim($exam['entry_password'])) {
        $_SESSION[$authKey] = true;
        $ex = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id, started_at FROM exam_attempts WHERE exam_id=$eid AND student_id=$uid LIMIT 1"));
        if (!$ex) {
            mysqli_query($conn,
                "INSERT INTO exam_attempts (exam_id,student_id,started_at,max_marks)
                 VALUES ($eid,$uid,NOW(),{$exam['total_marks']})");
            $_SESSION["exam_start_$eid"] = time();
        } else {
            if (!isset($_SESSION["exam_start_$eid"]))
                $_SESSION["exam_start_$eid"] = strtotime($ex['started_at']);
        }
        $_SESSION['exam_locked']    = true;
        $_SESSION['locked_exam_id'] = $eid;
    } else {
        $entryError = 'Incorrect entry password. Please try again.';
    }
}

// Already submitted → result
$attempt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM exam_attempts WHERE exam_id=$eid AND student_id=$uid LIMIT 1"));
if ($attempt && $attempt['submitted']) {
    unset($_SESSION['exam_locked'], $_SESSION['locked_exam_id'],
          $_SESSION[$authKey], $_SESSION["exam_start_$eid"]);
    header("Location: exam_result.php?eid=$eid"); exit;
}

$isAuthed  = !empty($_SESSION[$authKey]);
$attemptId = $attempt ? (int)$attempt['id'] : 0;

// Load questions (only when authed)
$mcqList  = [];
$partBList = []; // Part B coding (7 marks each)
$partCList = []; // Part C coding (10 marks each)
$savedMcq  = [];
$savedCode = [];

if ($isAuthed && $attemptId) {
    // Part A — MCQ
    $res = mysqli_query($conn, "SELECT * FROM exam_mcq_questions WHERE exam_id=$eid ORDER BY order_num ASC");
    while ($r = mysqli_fetch_assoc($res)) $mcqList[] = $r;

    // Part B — coding (7 marks)
    $res = mysqli_query($conn, "SELECT ecq.*, q.title, q.question as qtext
        FROM exam_coding_questions ecq
        JOIN questions q ON ecq.question_id=q.id
        WHERE ecq.exam_id=$eid AND ecq.part='B' ORDER BY ecq.order_num ASC");
    while ($r = mysqli_fetch_assoc($res)) $partBList[] = $r;

    // Part C — coding (10 marks)
    $res = mysqli_query($conn, "SELECT ecq.*, q.title, q.question as qtext
        FROM exam_coding_questions ecq
        JOIN questions q ON ecq.question_id=q.id
        WHERE ecq.exam_id=$eid AND ecq.part='C' ORDER BY ecq.order_num ASC");
    while ($r = mysqli_fetch_assoc($res)) $partCList[] = $r;

    // All coding questions combined for navigation
    $codeList = array_merge($partBList, $partCList);

    // Saved answers
    $res = mysqli_query($conn, "SELECT mcq_id, chosen FROM exam_mcq_answers WHERE attempt_id=$attemptId");
    while ($r = mysqli_fetch_assoc($res)) $savedMcq[$r['mcq_id']] = $r['chosen'];
    $res = mysqli_query($conn, "SELECT ecq_id, submitted_code, language FROM exam_code_answers WHERE attempt_id=$attemptId");
    while ($r = mysqli_fetch_assoc($res)) $savedCode[$r['ecq_id']] = $r;
} else {
    $codeList = [];
}

$nMcq   = count($mcqList);
$nCode  = count($codeList);
$nTotal = $nMcq + $nCode;

$durationSec = ($exam['duration_min'] ?? 90) * 60;
$startTs     = $_SESSION["exam_start_$eid"] ?? time();
$elapsed     = time() - $startTs;
$remaining   = max(0, $durationSec - $elapsed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($exam['title']) ?> — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── Base ───────────────────────────────────────────────── */
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#f8fafc;color:#1a1a2e;min-height:100vh;}
a{text-decoration:none;color:inherit;}

/* ── Entry Gate ──────────────────────────────────────── */
.gate{position:fixed;inset:0;background:linear-gradient(135deg,#004d00 0%,#1a5c1a 100%);
  display:flex;align-items:center;justify-content:center;z-index:9999;}
.gate-box{background:#fff;border-radius:20px;padding:44px 40px;width:100%;max-width:440px;
  text-align:center;box-shadow:0 32px 64px rgba(0,0,0,.25);}
.gate-icon{font-size:52px;margin-bottom:16px;}
.gate-title{font-size:20px;font-weight:800;color:#1a1a1a;margin-bottom:6px;}
.gate-sub{font-size:13px;color:#6b7280;margin-bottom:24px;line-height:1.7;}
.gate-meta{display:flex;justify-content:center;gap:24px;margin-bottom:24px;
  background:#f3f4f6;border-radius:12px;padding:14px;}
.gate-meta-val{font-size:20px;font-weight:800;color:#16a34a;}
.gate-meta-lbl{font-size:10px;color:#6b7280;text-transform:uppercase;font-weight:600;margin-top:2px;}
.gate-input{width:100%;padding:13px 16px;background:#f9fafb;border:2px solid #d1fae5;
  border-radius:10px;font-size:15px;font-family:inherit;text-align:center;
  letter-spacing:3px;color:#1a1a1a;outline:none;transition:.2s;}
.gate-input:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.12);}
.gate-btn{width:100%;margin-top:14px;padding:13px;background:#16a34a;color:#fff;
  border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:inherit;
  cursor:pointer;transition:.15s;}
.gate-btn:hover{background:#15803d;}
.gate-error{background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;
  border-radius:8px;padding:10px;font-size:13px;margin-top:12px;font-weight:600;}
.gate-warn{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;
  padding:12px 14px;font-size:12px;color:#9a3412;margin-top:18px;text-align:left;line-height:1.8;}

/* ── Exam Shell ──────────────────────────────────────── */
.shell{display:flex;flex-direction:column;height:100vh;overflow:hidden;}

/* Topbar */
.topbar{background:#fff;border-bottom:2px solid #e5e7eb;height:52px;
  display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0;z-index:100;
  box-shadow:0 1px 4px rgba(0,0,0,.06);}
.tb-logo{display:flex;align-items:center;gap:8px;}
.tb-dot{width:10px;height:10px;border-radius:50%;background:#16a34a;}
.tb-exam-name{font-size:13px;font-weight:700;color:#1a1a1a;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:360px;}
.tb-student{font-size:12px;color:#6b7280;margin-left:auto;font-weight:500;}
.timer{display:flex;align-items:center;gap:6px;background:#f3f4f6;
  border:1.5px solid #d1d5db;border-radius:8px;padding:5px 12px;
  font-size:14px;font-weight:800;letter-spacing:.04em;color:#1a1a1a;
  font-variant-numeric:tabular-nums;margin-left:10px;}
.timer.warn{border-color:#f59e0b;color:#d97706;background:#fffbeb;}
.timer.danger{border-color:#ef4444;color:#dc2626;background:#fee2e2;animation:blink .7s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.5;}}

/* Body */
.body{display:flex;flex:1;overflow:hidden;}

/* Sidebar */
.sidebar{width:158px;background:#fff;border-right:1px solid #e5e7eb;
  display:flex;flex-direction:column;overflow-y:auto;flex-shrink:0;}
.sb-section{padding:10px 12px 4px;font-size:9.5px;font-weight:700;
  color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;}
.sb-btn{display:flex;align-items:center;gap:7px;padding:7px 10px;
  border:none;background:none;cursor:pointer;font-family:inherit;
  font-size:12px;color:#6b7280;width:100%;text-align:left;transition:.1s;font-weight:500;}
.sb-btn:hover{background:#f3f4f6;color:#1a1a1a;}
.sb-btn.active{background:#f0fdf4;color:#15803d;font-weight:700;border-right:3px solid #16a34a;}
.sb-btn.saved .sb-num{background:#16a34a;color:#fff;}
.sb-num{width:22px;height:22px;border-radius:5px;background:#f3f4f6;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;font-weight:700;flex-shrink:0;color:#6b7280;transition:.1s;}
.sb-btn.active .sb-num{background:#16a34a;color:#fff;}
.sb-legend{padding:8px 10px 10px;display:flex;flex-direction:column;gap:5px;
  border-top:1px solid #e5e7eb;margin-top:auto;}
.sb-leg-row{display:flex;align-items:center;gap:6px;font-size:10.5px;color:#9ca3af;}
.sb-leg-dot{width:8px;height:8px;border-radius:2px;flex-shrink:0;}

/* Content area */
.content{flex:1;overflow-y:auto;padding:24px 28px 90px;background:#f8fafc;}
.q-panel{display:none;}
.q-panel.active{display:block;}

/* Question card */
.q-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;
  padding:22px 24px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04);}
.q-top{display:flex;align-items:center;gap:10px;margin-bottom:16px;}
.q-part-badge{font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
  text-transform:uppercase;letter-spacing:.06em;}
.part-a{background:#fef3c7;color:#92400e;}
.part-b{background:#ede9fe;color:#5b21b6;}
.part-c{background:#dcfce7;color:#166534;}
.q-number{font-size:12px;color:#9ca3af;font-weight:600;}
.q-marks{margin-left:auto;background:#f0fdf4;border:1px solid #bbf7d0;
  border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700;color:#15803d;}
.q-title{font-size:17px;font-weight:700;color:#1a1a1a;margin-bottom:10px;}
.q-body{font-size:13.5px;color:#374151;line-height:1.8;white-space:pre-wrap;}

/* MCQ Options */
.options{display:flex;flex-direction:column;gap:9px;margin-top:18px;}
.opt{display:flex;align-items:center;gap:12px;padding:12px 16px;
  background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:10px;
  cursor:pointer;transition:.15s;font-size:13.5px;color:#374151;}
.opt:hover{border-color:#16a34a;background:#f0fdf4;}
.opt.selected{border-color:#16a34a;background:#f0fdf4;color:#1a1a1a;font-weight:600;}
.opt input[type=radio]{display:none;}
.opt-letter{width:28px;height:28px;border-radius:7px;background:#e5e7eb;
  border:1.5px solid #d1d5db;display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;color:#6b7280;flex-shrink:0;transition:.15s;}
.opt.selected .opt-letter{background:#16a34a;border-color:#16a34a;color:#fff;}

/* Code editor area */
.code-wrap{background:#f8fafc;border:1.5px solid #e5e7eb;border-radius:12px;
  overflow:hidden;margin-top:18px;}
.code-bar{background:#fff;border-bottom:1px solid #e5e7eb;padding:8px 14px;
  display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.lang-sel{background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;
  padding:5px 10px;font-size:12px;font-family:inherit;color:#1a1a1a;
  cursor:pointer;outline:none;}
.btn-run{padding:6px 14px;background:#7c3aed;border:none;border-radius:6px;
  color:#fff;font-size:11.5px;font-weight:700;font-family:inherit;cursor:pointer;transition:.15s;}
.btn-run:hover{background:#6d28d9;}
.btn-save-code{padding:6px 14px;background:#0284c7;border:none;border-radius:6px;
  color:#fff;font-size:11.5px;font-weight:700;font-family:inherit;cursor:pointer;transition:.15s;}
.btn-save-code:hover{background:#0369a1;}
.saved-inline{font-size:11px;color:#16a34a;font-weight:700;display:none;}
.saved-inline.show{display:inline;}
.code-editor{width:100%;min-height:220px;padding:14px;
  font-family:'Courier New',monospace;font-size:13px;
  background:#1e1e2e;color:#cdd6f4;
  border:none;outline:none;resize:vertical;line-height:1.7;}

/* Run output — test cases, SAME as practice mode */
.test-results{padding:12px 14px;border-top:1px solid #e5e7eb;background:#fff;}
.test-case{border:1px solid #e5e7eb;border-radius:8px;margin-bottom:10px;overflow:hidden;font-size:13px;}
.test-case:last-child{margin-bottom:0;}
.tc-header{display:flex;align-items:center;gap:8px;padding:8px 12px;font-weight:700;font-size:12px;}
.tc-pass .tc-header{background:#f0fdf4;color:#15803d;}
.tc-fail .tc-header{background:#fef2f2;color:#dc2626;}
.tc-body{display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;}
.tc-col{padding:8px 12px;border-top:1px solid #f3f4f6;}
.tc-col:not(:last-child){border-right:1px solid #f3f4f6;}
.tc-col-lbl{font-size:10px;font-weight:700;text-transform:uppercase;
  letter-spacing:.06em;color:#9ca3af;margin-bottom:3px;}
.tc-col-val{font-family:'Courier New',monospace;font-size:12px;color:#1a1a1a;
  white-space:pre-wrap;word-break:break-all;}
.run-loading{padding:12px 14px;color:#6b7280;font-size:13px;font-style:italic;}
.run-error{padding:12px 14px;background:#fef2f2;color:#dc2626;font-size:13px;
  border-top:1px solid #fee2e2;font-weight:600;}
.all-pass-banner{padding:10px 14px;background:#f0fdf4;border-top:1px solid #bbf7d0;
  color:#15803d;font-weight:700;font-size:13px;text-align:center;}
.all-fail-banner{padding:10px 14px;background:#fef2f2;border-top:1px solid #fecaca;
  color:#dc2626;font-weight:700;font-size:13px;text-align:center;}

/* Nav row */
.q-nav{display:flex;align-items:center;gap:10px;margin-top:18px;
  padding-top:16px;border-top:1px solid #f3f4f6;}
.btn-prev,.btn-next{padding:8px 20px;background:#f3f4f6;border:1px solid #d1d5db;
  border-radius:7px;color:#374151;font-size:12.5px;font-weight:600;
  font-family:inherit;cursor:pointer;transition:.15s;}
.btn-prev:hover,.btn-next:hover{background:#e5e7eb;color:#1a1a1a;}
.btn-submit-nav{margin-left:auto;padding:8px 22px;background:#16a34a;border:none;
  border-radius:7px;color:#fff;font-size:12.5px;font-weight:700;
  font-family:inherit;cursor:pointer;transition:.15s;}
.btn-submit-nav:hover{background:#15803d;}

/* Bottom bar */
.submit-bar{position:fixed;bottom:0;left:158px;right:0;
  background:linear-gradient(135deg,#dc2626,#b91c1c);
  padding:14px 28px;display:flex;align-items:center;justify-content:center;
  cursor:pointer;font-size:15px;font-weight:800;color:#fff;
  letter-spacing:.03em;border-top:2px solid #ef4444;transition:.15s;z-index:50;}
.submit-bar:hover{background:linear-gradient(135deg,#b91c1c,#991b1b);}

/* Review overlay */
.rev-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;
  display:none;align-items:center;justify-content:center;padding:20px;}
.rev-overlay.open{display:flex;}
.rev-box{background:#fff;border:1px solid #e5e7eb;border-radius:20px;
  padding:32px;width:100%;max-width:580px;max-height:88vh;overflow-y:auto;
  box-shadow:0 16px 48px rgba(0,0,0,.15);}
.rev-title{font-size:18px;font-weight:800;color:#1a1a1a;margin-bottom:20px;}
.rev-sec-lbl{font-size:10px;font-weight:700;text-transform:uppercase;
  letter-spacing:.07em;color:#9ca3af;margin-bottom:10px;}
.rev-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px;margin-bottom:18px;}
.rev-item{display:flex;align-items:center;gap:8px;padding:8px 12px;
  border-radius:8px;border:1px solid #e5e7eb;cursor:pointer;transition:.1s;font-size:12px;color:#374151;}
.rev-item:hover{border-color:#d1d5db;background:#f9fafb;}
.rev-item.done{border-color:#16a34a;background:#f0fdf4;color:#15803d;}
.rev-item.miss{border-color:#ef4444;background:#fef2f2;color:#dc2626;}
.rev-num-badge{width:22px;height:22px;border-radius:5px;display:flex;
  align-items:center;justify-content:center;font-size:10px;font-weight:700;
  background:#f3f4f6;flex-shrink:0;}
.rev-summary{font-size:13px;color:#6b7280;margin-bottom:20px;}
.rev-actions{display:flex;gap:10px;}
.btn-rev-back{flex:1;padding:12px;background:#f3f4f6;border:1px solid #d1d5db;
  border-radius:9px;color:#374151;font-size:13.5px;font-weight:600;
  font-family:inherit;cursor:pointer;transition:.15s;}
.btn-rev-back:hover{background:#e5e7eb;}
.btn-rev-submit{flex:2;padding:12px;background:#dc2626;border:none;
  border-radius:9px;color:#fff;font-size:14px;font-weight:800;
  font-family:inherit;cursor:pointer;transition:.15s;}
.btn-rev-submit:hover{background:#b91c1c;}

/* Exit password modal */
.exit-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;
  display:none;align-items:center;justify-content:center;}
.exit-overlay.open{display:flex;}
.exit-box{background:#fff;border:2px solid #fca5a5;border-radius:18px;
  padding:38px 36px;width:100%;max-width:400px;text-align:center;
  box-shadow:0 16px 48px rgba(220,38,38,.15);}
.exit-icon{font-size:44px;margin-bottom:14px;}
.exit-title{font-size:18px;font-weight:800;color:#1a1a1a;margin-bottom:6px;}
.exit-sub{font-size:13px;color:#6b7280;margin-bottom:22px;line-height:1.6;}
.exit-input{width:100%;padding:13px;background:#f9fafb;border:2px solid #d1d5db;
  border-radius:9px;font-size:15px;font-family:inherit;text-align:center;
  letter-spacing:3px;color:#1a1a1a;outline:none;transition:.2s;}
.exit-input:focus{border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.1);}
.exit-btn{width:100%;margin-top:12px;padding:13px;background:#dc2626;border:none;
  border-radius:9px;color:#fff;font-size:14px;font-weight:800;
  font-family:inherit;cursor:pointer;transition:.15s;}
.exit-btn:hover{background:#b91c1c;}
.exit-cancel{width:100%;margin-top:8px;padding:10px;background:transparent;
  border:1px solid #d1d5db;border-radius:8px;color:#6b7280;font-size:13px;
  font-weight:600;font-family:inherit;cursor:pointer;}
.exit-cancel:hover{background:#f3f4f6;}
.exit-err{min-height:18px;font-size:12.5px;color:#dc2626;font-weight:600;margin-top:8px;}
</style>
</head>
<body>

<?php if (!$isAuthed): ?>
<!-- ════════ ENTRY PASSWORD GATE ════════ -->
<div class="gate">
  <div class="gate-box">
    <div class="gate-icon">🔐</div>
    <div class="gate-title"><?= htmlspecialchars($exam['title']) ?></div>
    <div class="gate-sub"><?= htmlspecialchars($exam['subject_name'] ?? '') ?> Examination</div>
    <div class="gate-meta">
      <div><div class="gate-meta-val">90</div><div class="gate-meta-lbl">Minutes</div></div>
      <div><div class="gate-meta-val"><?= $exam['total_marks'] ?></div><div class="gate-meta-lbl">Marks</div></div>
      <div><div class="gate-meta-val">9 Qs</div><div class="gate-meta-lbl">Questions</div></div>
    </div>
    <form method="POST">
      <input type="password" name="entry_password" class="gate-input"
             placeholder="Enter entry password" autofocus autocomplete="off" required>
      <?php if ($entryError): ?>
      <div class="gate-error">❌ <?= htmlspecialchars($entryError) ?></div>
      <?php endif; ?>
      <button type="submit" class="gate-btn">🚀 Start Exam</button>
    </form>
    <div class="gate-warn">
      ⚠️ Once started, you are <strong>locked to this exam</strong>. You cannot leave until you submit with the exit password.
    </div>
  </div>
</div>

<?php else: ?>
<!-- ════════ MAIN EXAM INTERFACE ════════ -->
<div class="shell">

  <div class="topbar">
    <div class="tb-logo">
      <div class="tb-dot"></div>
      <span class="tb-exam-name">🛡️ <?= htmlspecialchars($exam['title']) ?></span>
    </div>
    <div class="tb-student">👤 <?= $name ?></div>
    <div class="timer" id="timerBox">⏱ <span id="timerTxt">--:--</span></div>
  </div>

  <div class="body">

    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sb-section">Part A — MCQ</div>
      <?php foreach ($mcqList as $i => $m):
        $sv = isset($savedMcq[$m['id']]); ?>
      <button class="sb-btn <?= ($i===0?'active ':'') ?><?= $sv?'saved':'' ?>"
              id="sb-mcq-<?= $m['id'] ?>"
              onclick="showQ('mcq-<?= $m['id'] ?>',this)">
        <span class="sb-num"><?= $i+1 ?></span> Q<?= $i+1 ?>
      </button>
      <?php endforeach; ?>

      <div class="sb-section" style="margin-top:6px">Part B — Coding</div>
      <?php foreach ($partBList as $i => $c):
        $sv = isset($savedCode[$c['id']]); ?>
      <button class="sb-btn <?= $sv?'saved':'' ?>"
              id="sb-code-<?= $c['id'] ?>"
              onclick="showQ('code-<?= $c['id'] ?>',this)">
        <span class="sb-num"><?= $nMcq+$i+1 ?></span> Q<?= $nMcq+$i+1 ?>
      </button>
      <?php endforeach; ?>

      <div class="sb-section" style="margin-top:6px">Part C — Coding</div>
      <?php foreach ($partCList as $i => $c):
        $sv = isset($savedCode[$c['id']]); ?>
      <button class="sb-btn <?= $sv?'saved':'' ?>"
              id="sb-code-<?= $c['id'] ?>"
              onclick="showQ('code-<?= $c['id'] ?>',this)">
        <span class="sb-num"><?= $nMcq+count($partBList)+$i+1 ?></span> Q<?= $nMcq+count($partBList)+$i+1 ?>
      </button>
      <?php endforeach; ?>

      <div class="sb-legend">
        <div class="sb-leg-row"><span class="sb-leg-dot" style="background:#16a34a"></span>Active</div>
        <div class="sb-leg-row"><span class="sb-leg-dot" style="background:#4ade80"></span>Attempted</div>
        <div class="sb-leg-row"><span class="sb-leg-dot" style="background:#e5e7eb"></span>Not visited</div>
      </div>
    </div>

    <!-- Question Content -->
    <div class="content" id="qContent">

      <!-- PART A: MCQ -->
      <?php foreach ($mcqList as $i => $m):
        $qn     = $i+1;
        $chosen = $savedMcq[$m['id']] ?? null;
        $opts   = ['A'=>$m['opt_a'],'B'=>$m['opt_b'],'C'=>$m['opt_c'],'D'=>$m['opt_d']];
        $prevId = $i > 0 ? 'mcq-'.$mcqList[$i-1]['id'] : null;
        $nextId = $i < $nMcq-1 ? 'mcq-'.$mcqList[$i+1]['id']
                : (count($codeList)>0 ? 'code-'.$codeList[0]['id'] : null);
      ?>
      <div class="q-panel <?= $i===0?'active':'' ?>" id="panel-mcq-<?= $m['id'] ?>">
        <div class="q-card">
          <div class="q-top">
            <span class="q-part-badge part-a">Part A</span>
            <span class="q-number">QUESTION <?= $qn ?> OF <?= $nTotal ?></span>
            <span class="q-marks"><?= $m['marks'] ?> mark</span>
          </div>
          <div class="q-body"><?= nl2br(htmlspecialchars($m['question'])) ?></div>
          <div class="options" id="opts-<?= $m['id'] ?>">
            <?php foreach ($opts as $letter => $text): ?>
            <label class="opt <?= $chosen===$letter?'selected':'' ?>" id="optlbl-<?= $m['id'] ?>-<?= $letter ?>">
              <input type="radio" name="mcq_<?= $m['id'] ?>" value="<?= $letter ?>"
                     <?= $chosen===$letter?'checked':'' ?>
                     onchange="saveMcq(<?= $m['id'] ?>,'<?= $letter ?>')">
              <span class="opt-letter"><?= $letter ?></span>
              <?= htmlspecialchars($text) ?>
            </label>
            <?php endforeach; ?>
          </div>
          <div class="q-nav">
            <?php if ($prevId): ?><button class="btn-prev" onclick="showQ('<?= $prevId ?>',document.getElementById('sb-<?= $prevId ?>'))">← Prev</button><?php endif; ?>
            <?php if ($nextId): ?><button class="btn-next" onclick="showQ('<?= $nextId ?>',document.getElementById('sb-<?= $nextId ?>'))">Next →</button>
            <?php else: ?><button class="btn-submit-nav" onclick="openReview()">Review & Submit</button><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- PART B: CODING (7 marks each) -->
      <?php foreach ($partBList as $i => $c):
        $qn      = $nMcq + $i + 1;
        $saved   = $savedCode[$c['id']] ?? null;
        $codeVal = $saved ? htmlspecialchars($saved['submitted_code']) : '';
        $lang    = $saved['language'] ?? 'python';
        $prevId  = $i > 0 ? 'code-'.$partBList[$i-1]['id'] : ($nMcq>0?'mcq-'.$mcqList[$nMcq-1]['id']:null);
        $nextId  = $i < count($partBList)-1 ? 'code-'.$partBList[$i+1]['id']
                 : (count($partCList)>0 ? 'code-'.$partCList[0]['id'] : null);
      ?>
      <div class="q-panel" id="panel-code-<?= $c['id'] ?>">
        <div class="q-card">
          <div class="q-top">
            <span class="q-part-badge part-b">Part B</span>
            <span class="q-number">QUESTION <?= $qn ?> OF <?= $nTotal ?></span>
            <span class="q-marks"><?= $c['marks'] ?> marks</span>
          </div>
          <div class="q-title"><?= htmlspecialchars($c['title']) ?></div>
          <div class="q-body"><?= nl2br(htmlspecialchars($c['qtext'])) ?></div>
          <div class="code-wrap">
            <div class="code-bar">
              <span style="font-size:11.5px;color:#6b7280;font-weight:600;">Language:</span>
              <select class="lang-sel" id="lang-<?= $c['id'] ?>">
                <option value="python" <?= $lang==='python'?'selected':'' ?>>Python 3</option>
                <option value="java"   <?= $lang==='java'?'selected':'' ?>>Java</option>
                <option value="c"      <?= $lang==='c'?'selected':'' ?>>C</option>
                <option value="cpp"    <?= $lang==='cpp'?'selected':'' ?>>C++</option>
              </select>
              <button class="btn-run" onclick="runCode(<?= $c['id'] ?>,<?= $c['question_id'] ?>)">▶ Run & Check</button>
              <button class="btn-save-code" onclick="saveCode(<?= $c['id'] ?>)">💾 Save</button>
              <span class="saved-inline <?= $saved?'show':'' ?>" id="sv-code-<?= $c['id'] ?>">✅ Saved</span>
            </div>
            <textarea class="code-editor" id="code-<?= $c['id'] ?>"
                      placeholder="# Write your solution here..."><?= $codeVal ?></textarea>
            <div id="out-<?= $c['id'] ?>"></div>
          </div>
          <div class="q-nav">
            <?php if ($prevId): ?><button class="btn-prev" onclick="showQ('<?= $prevId ?>',document.getElementById('sb-<?= $prevId ?>'))">← Prev</button><?php endif; ?>
            <?php if ($nextId): ?><button class="btn-next" onclick="showQ('<?= $nextId ?>',document.getElementById('sb-<?= $nextId ?>'))">Next →</button>
            <?php else: ?><button class="btn-submit-nav" onclick="openReview()">Review & Submit</button><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- PART C: CODING (10 marks each) -->
      <?php foreach ($partCList as $i => $c):
        $qn      = $nMcq + count($partBList) + $i + 1;
        $saved   = $savedCode[$c['id']] ?? null;
        $codeVal = $saved ? htmlspecialchars($saved['submitted_code']) : '';
        $lang    = $saved['language'] ?? 'python';
        $prevId  = $i > 0 ? 'code-'.$partCList[$i-1]['id'] : (count($partBList)>0?'code-'.$partBList[count($partBList)-1]['id']:($nMcq>0?'mcq-'.$mcqList[$nMcq-1]['id']:null));
      ?>
      <div class="q-panel" id="panel-code-<?= $c['id'] ?>">
        <div class="q-card">
          <div class="q-top">
            <span class="q-part-badge part-c">Part C</span>
            <span class="q-number">QUESTION <?= $qn ?> OF <?= $nTotal ?></span>
            <span class="q-marks"><?= $c['marks'] ?> marks</span>
          </div>
          <div class="q-title"><?= htmlspecialchars($c['title']) ?></div>
          <div class="q-body"><?= nl2br(htmlspecialchars($c['qtext'])) ?></div>
          <div class="code-wrap">
            <div class="code-bar">
              <span style="font-size:11.5px;color:#6b7280;font-weight:600;">Language:</span>
              <select class="lang-sel" id="lang-<?= $c['id'] ?>">
                <option value="python" <?= $lang==='python'?'selected':'' ?>>Python 3</option>
                <option value="java"   <?= $lang==='java'?'selected':'' ?>>Java</option>
                <option value="c"      <?= $lang==='c'?'selected':'' ?>>C</option>
                <option value="cpp"    <?= $lang==='cpp'?'selected':'' ?>>C++</option>
              </select>
              <button class="btn-run" onclick="runCode(<?= $c['id'] ?>,<?= $c['question_id'] ?>)">▶ Run & Check</button>
              <button class="btn-save-code" onclick="saveCode(<?= $c['id'] ?>)">💾 Save</button>
              <span class="saved-inline <?= $saved?'show':'' ?>" id="sv-code-<?= $c['id'] ?>">✅ Saved</span>
            </div>
            <textarea class="code-editor" id="code-<?= $c['id'] ?>"
                      placeholder="# Write your solution here..."><?= $codeVal ?></textarea>
            <div id="out-<?= $c['id'] ?>"></div>
          </div>
          <div class="q-nav">
            <?php if ($prevId): ?><button class="btn-prev" onclick="showQ('<?= $prevId ?>',document.getElementById('sb-<?= $prevId ?>'))">← Prev</button><?php endif; ?>
            <button class="btn-submit-nav" onclick="openReview()" style="margin-left:auto;">Review & Submit</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div><!-- /content -->
  </div><!-- /body -->

  <div class="submit-bar" onclick="openReview()">✅ Submit Exam</div>

</div><!-- /shell -->

<!-- REVIEW OVERLAY -->
<div class="rev-overlay" id="revOverlay">
  <div class="rev-box">
    <div class="rev-title">📋 Review & Submit</div>
    <div class="rev-sec-lbl">Part A — MCQ (<?= $nMcq ?> × 1 mark)</div>
    <div class="rev-grid" id="revMcqGrid"></div>
    <div class="rev-sec-lbl">Part B — Coding (<?= count($partBList) ?> × 7 marks)</div>
    <div class="rev-grid" id="revBGrid"></div>
    <div class="rev-sec-lbl">Part C — Coding (<?= count($partCList) ?> × 10 marks)</div>
    <div class="rev-grid" id="revCGrid"></div>
    <div class="rev-summary" id="revSummary"></div>
    <div class="rev-actions">
      <button class="btn-rev-back" onclick="closeReview()">← Go Back</button>
      <button class="btn-rev-submit" onclick="openExitModal()">Submit Exam →</button>
    </div>
  </div>
</div>

<!-- EXIT PASSWORD MODAL -->
<div class="exit-overlay" id="exitOverlay">
  <div class="exit-box">
    <div class="exit-icon">🔒</div>
    <div class="exit-title">Enter Exit Password</div>
    <div class="exit-sub">Enter the exit password provided by your invigilator to submit your exam.</div>
    <input type="password" id="exitInp" class="exit-input" placeholder="Exit password" autocomplete="off">
    <div class="exit-err" id="exitErr"></div>
    <button class="exit-btn" onclick="verifyExit()">Submit Exam</button>
    <button class="exit-cancel" onclick="closeExitModal()">Cancel</button>
  </div>
</div>

<form id="submitForm" method="POST" action="exam_submit_handler.php" style="display:none;">
  <input type="hidden" name="eid" value="<?= $eid ?>">
  <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
  <input type="hidden" name="confirmed" value="1">
</form>

<script>
const EID        = <?= $eid ?>;
const ATTEMPT_ID = <?= $attemptId ?>;
const EXIT_PASS  = <?= json_encode($exam['exit_password']) ?>;
let remainSec    = <?= $remaining ?>;

/* Timer */
(function tick(){
  const m=Math.floor(remainSec/60), s=remainSec%60;
  const pad=n=>String(n).padStart(2,'0');
  const txt=document.getElementById('timerTxt');
  const box=document.getElementById('timerBox');
  if(txt) txt.textContent=pad(m)+':'+pad(s);
  if(box){
    box.className='timer';
    if(remainSec<=300) box.classList.add('danger');
    else if(remainSec<=600) box.classList.add('warn');
  }
  if(remainSec<=0){ document.getElementById('submitForm').submit(); return; }
  remainSec--;
  setTimeout(tick,1000);
})();

/* Question nav */
function showQ(id,navEl){
  document.querySelectorAll('.q-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.sb-btn').forEach(b=>b.classList.remove('active'));
  const panel=document.getElementById('panel-'+id);
  const nav=navEl||document.getElementById('sb-'+id);
  if(panel) panel.classList.add('active');
  if(nav)   nav.classList.add('active');
  document.getElementById('qContent').scrollTop=0;
}

/* Save MCQ */
const mcqSaved={};
<?php foreach($savedMcq as $mid=>$ch): ?>mcqSaved[<?=$mid?>]='<?=$ch?>';<?php endforeach; ?>

function saveMcq(mcqId,letter){
  document.querySelectorAll(`#opts-${mcqId} .opt`).forEach(o=>o.classList.remove('selected'));
  const lbl=document.getElementById(`optlbl-${mcqId}-${letter}`);
  if(lbl) lbl.classList.add('selected');
  fetch('save_exam_mcq.php',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`attempt_id=${ATTEMPT_ID}&mcq_id=${mcqId}&chosen=${letter}`
  }).then(r=>r.json()).then(()=>{
    mcqSaved[mcqId]=letter;
    const sb=document.getElementById(`sb-mcq-${mcqId}`);
    if(sb) sb.classList.add('saved');
  });
}

/* Save Code */
const codeSaved={};
<?php foreach($savedCode as $cid=>$sc): ?>codeSaved[<?=$cid?>]=true;<?php endforeach; ?>

function saveCode(cqId){
  const code=document.getElementById(`code-${cqId}`)?.value||'';
  const lang=document.getElementById(`lang-${cqId}`)?.value||'python';
  const sv=document.getElementById(`sv-code-${cqId}`);
  if(sv){sv.textContent='⏳';sv.classList.add('show');}
  fetch('save_exam_code.php',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`attempt_id=${ATTEMPT_ID}&ecq_id=${cqId}&code=${encodeURIComponent(code)}&language=${lang}`
  }).then(r=>r.json()).then(()=>{
    codeSaved[cqId]=true;
    if(sv) sv.textContent='✅ Saved';
    if(code.trim()){
      const sb=document.getElementById(`sb-code-${cqId}`);
      if(sb) sb.classList.add('saved');
    }
  }).catch(()=>{if(sv) sv.textContent='❌ Error';});
}

/* Run Code — passes question_id so testcases can be found */
function runCode(cqId, questionId){
  const code=document.getElementById(`code-${cqId}`)?.value||'';
  const lang=document.getElementById(`lang-${cqId}`)?.value||'python';
  const out=document.getElementById(`out-${cqId}`);
  if(out) out.innerHTML='<div class="run-loading">⏳ Running your code...</div>';
  fetch('run.php',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`code=${encodeURIComponent(code)}&language=${lang}&qid=${questionId}&input=`
  }).then(r=>r.json()).then(d=>{
    if(!out) return;
    if(d.error){
      out.innerHTML=`<div class="run-error">❌ ${escHtml(d.error)}</div>`;
      return;
    }
    if(!d.tests||d.tests.length===0){
      out.innerHTML='<div class="run-loading">No test cases found.</div>';
      return;
    }
    let html='<div class="test-results">';
    d.tests.forEach((t,i)=>{
      const cls=t.pass?'tc-pass':'tc-fail';
      const icon=t.pass?'✅ Passed':'❌ Failed';
      html+=`<div class="test-case ${cls}">
        <div class="tc-header">${icon} &nbsp; Test Case ${i+1}</div>
        <div class="tc-body">
          <div class="tc-col"><div class="tc-col-lbl">Input</div><div class="tc-col-val">${escHtml(t.input||'(none)')}</div></div>
          <div class="tc-col"><div class="tc-col-lbl">Expected</div><div class="tc-col-val">${escHtml(t.expected)}</div></div>
          <div class="tc-col"><div class="tc-col-lbl">Got</div><div class="tc-col-val">${escHtml(t.got)}</div></div>
        </div>
      </div>`;
    });
    if(d.all_passed)
      html+='<div class="all-pass-banner">🎉 All test cases passed!</div>';
    else
      html+='<div class="all-fail-banner">Some test cases failed. Check your logic and try again.</div>';
    html+='</div>';
    out.innerHTML=html;
  }).catch(()=>{ if(out) out.innerHTML='<div class="run-error">❌ Error contacting code runner.</div>'; });
}

function escHtml(s){
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* Review */
function openReview(){
  const mg=document.getElementById('revMcqGrid');
  mg.innerHTML='';
  <?php foreach($mcqList as $i=>$m): ?>
  (()=>{
    const done=!!mcqSaved[<?=$m['id']?>];
    const d=document.createElement('div');
    d.className='rev-item '+(done?'done':'miss');
    d.innerHTML=`<span class="rev-num-badge"><?=$i+1?></span>${done?'Q<?=$i+1?> ✓':'Q<?=$i+1?> —'}`;
    d.onclick=()=>{closeReview();showQ('mcq-<?=$m['id']?>',document.getElementById('sb-mcq-<?=$m['id']?>'));};
    mg.appendChild(d);
  })();
  <?php endforeach; ?>

  const bg=document.getElementById('revBGrid');
  bg.innerHTML='';
  <?php foreach($partBList as $i=>$c): ?>
  (()=>{
    const done=codeSaved[<?=$c['id']?>]||(document.getElementById('code-<?=$c['id']?>')?.value?.trim()||'').length>0;
    const d=document.createElement('div');
    d.className='rev-item '+(done?'done':'miss');
    d.innerHTML=`<span class="rev-num-badge"><?=$nMcq+$i+1?></span>${done?'Q<?=$nMcq+$i+1?> ✓':'Q<?=$nMcq+$i+1?> —'}`;
    d.onclick=()=>{closeReview();showQ('code-<?=$c['id']?>',document.getElementById('sb-code-<?=$c['id']?>'));};
    bg.appendChild(d);
  })();
  <?php endforeach; ?>

  const cg=document.getElementById('revCGrid');
  cg.innerHTML='';
  <?php foreach($partCList as $i=>$c): $qn=$nMcq+count($partBList)+$i+1; ?>
  (()=>{
    const done=codeSaved[<?=$c['id']?>]||(document.getElementById('code-<?=$c['id']?>')?.value?.trim()||'').length>0;
    const d=document.createElement('div');
    d.className='rev-item '+(done?'done':'miss');
    d.innerHTML=`<span class="rev-num-badge"><?=$qn?></span>${done?'Q<?=$qn?> ✓':'Q<?=$qn?> —'}`;
    d.onclick=()=>{closeReview();showQ('code-<?=$c['id']?>',document.getElementById('sb-code-<?=$c['id']?>'));};
    cg.appendChild(d);
  })();
  <?php endforeach; ?>

  const totalAns=Object.keys(mcqSaved).length
    +Object.values(codeSaved).filter(Boolean).length;
  document.getElementById('revSummary').textContent=
    `${totalAns} of ${<?=$nTotal?>} questions attempted. Click any question to edit.`;
  document.getElementById('revOverlay').classList.add('open');
}
function closeReview(){ document.getElementById('revOverlay').classList.remove('open'); }

/* Exit password */
function openExitModal(){
  closeReview();
  document.getElementById('exitOverlay').classList.add('open');
  setTimeout(()=>document.getElementById('exitInp').focus(),100);
}
function closeExitModal(){
  document.getElementById('exitOverlay').classList.remove('open');
  document.getElementById('exitInp').value='';
  document.getElementById('exitErr').textContent='';
}
function verifyExit(){
  const val=document.getElementById('exitInp').value.trim();
  if(val===EXIT_PASS){
    document.getElementById('exitErr').textContent='';
    document.getElementById('exitOverlay').classList.remove('open');
    document.getElementById('submitForm').submit();
  } else {
    document.getElementById('exitErr').textContent='❌ Incorrect exit password. Ask your invigilator.';
    document.getElementById('exitInp').value='';
    document.getElementById('exitInp').focus();
  }
}
document.getElementById('exitInp')?.addEventListener('keydown',e=>{if(e.key==='Enter') verifyExit();});

/* Block shortcuts */
document.addEventListener('keydown',e=>{
  const k=e.key?.toLowerCase();
  if(e.key==='F12'||e.key==='F5'
     ||(e.ctrlKey&&['r','u','p','s'].includes(k))
     ||(e.altKey&&(e.key==='F4'||e.key==='Tab'))
     ||e.key==='Escape'){
    e.preventDefault(); e.stopPropagation();
  }
});
document.addEventListener('contextmenu',e=>e.preventDefault());
</script>
<?php endif; ?>
</body>
</html>
