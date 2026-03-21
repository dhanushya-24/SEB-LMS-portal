<?php
include "config.php";
requireLogin();
requireSEB();      // Must run inside SEB
$uid = $_SESSION['student_id'];
$name = htmlspecialchars($_SESSION['name'] ?? 'Student');
$eid = (int)($_GET['eid'] ?? 0);
if (!$eid) { header("Location: launch_exam.php"); exit; }

$exam = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT e.*, s.subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id=s.id WHERE e.id=$eid"));
if (!$exam) { header("Location: launch_exam.php"); exit; }

$attempt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM exam_attempts WHERE exam_id=$eid AND student_id=$uid LIMIT 1"));
if (!$attempt || !$attempt['submitted']) {
    header("Location: exam.php?eid=$eid"); exit;
}

// Calculate duration
$startDt = new DateTime($attempt['started_at']);
$endDt   = new DateTime($attempt['finished_at']);
$diff    = $startDt->diff($endDt);
$durationMin = ($diff->h * 60) + $diff->i;
$durationStr = ($diff->h > 0 ? $diff->h . 'h ' : '') . $diff->i . 'm ' . $diff->s . 's';

$total   = (int)$attempt['total_score'];
$maxM    = (int)($attempt['max_marks'] ?: 30);
$pct     = $maxM > 0 ? round($total / $maxM * 100) : 0;

// Grade
function calcGrade($pct) {
    if ($pct >= 90) return ['S', '#15803d', 'Outstanding'];
    if ($pct >= 80) return ['A', '#0284c7', 'Excellent'];
    if ($pct >= 70) return ['B', '#7c3aed', 'Good'];
    if ($pct >= 60) return ['C', '#d97706', 'Average'];
    if ($pct >= 50) return ['D', '#ea580c', 'Below Average'];
    return ['F', '#dc2626', 'Fail'];
}
[$gradeLetter, $gradeColor, $gradeDesc] = calcGrade($pct);

// MCQ breakdown
$mcqData = [];
$res = mysqli_query($conn, "SELECT m.*, ma.chosen, ma.is_correct FROM exam_mcq_questions m
    LEFT JOIN exam_mcq_answers ma ON ma.mcq_id=m.id AND ma.attempt_id={$attempt['id']}
    WHERE m.exam_id=$eid ORDER BY m.order_num");
while ($r = mysqli_fetch_assoc($res)) $mcqData[] = $r;

// Code breakdown
$codeData = [];
$res = mysqli_query($conn, "SELECT ecq.*, q.title, ca.submitted_code, ca.score FROM exam_coding_questions ecq
    LEFT JOIN questions q ON q.id=ecq.question_id
    LEFT JOIN exam_code_answers ca ON ca.ecq_id=ecq.id AND ca.attempt_id={$attempt['id']}
    WHERE ecq.exam_id=$eid AND ecq.part='B' ORDER BY ecq.order_num");
while ($r = mysqli_fetch_assoc($res)) $codeData[] = $r;

// Long breakdown
$longData = [];
$res = mysqli_query($conn, "SELECT lq.*, la.answer_text, la.score FROM exam_long_questions lq
    LEFT JOIN exam_long_answers la ON la.lq_id=lq.id AND la.attempt_id={$attempt['id']}
    WHERE lq.exam_id=$eid ORDER BY lq.order_num");
while ($r = mysqli_fetch_assoc($res)) $longData[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Exam Result — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#f0f4f8;min-height:100vh;}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 28px;height:54px;display:flex;align-items:center;gap:14px;}
.brand{font-size:15px;font-weight:800;color:#0f172a;}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.btn-home{padding:7px 16px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;}
.btn-home:hover{background:#1d4ed8;text-decoration:none;color:#fff;}
.btn-logout{padding:7px 16px;background:#fff;border:1.5px solid #e2e8f0;color:#374151;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;}
.btn-logout:hover{border-color:#fca5a5;color:#dc2626;text-decoration:none;}

.page{max-width:860px;margin:0 auto;padding:32px 20px 60px;}

/* Hero result card */
.result-hero{background:#fff;border-radius:20px;padding:36px;text-align:center;margin-bottom:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);}
.exam-name{font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.status-pill{display:inline-block;padding:6px 18px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:22px;background:#dcfce7;color:#15803d;}
.status-pill.fail{background:#fee2e2;color:#dc2626;}
.marks-circle{width:140px;height:140px;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 auto 22px;border:6px solid;font-weight:800;}
.marks-score{font-size:36px;line-height:1;}
.marks-max{font-size:14px;color:#64748b;margin-top:3px;}
.grade-badge{display:inline-block;padding:8px 24px;border-radius:10px;font-size:22px;font-weight:900;margin-bottom:6px;color:#fff;}
.grade-desc{font-size:14px;color:#64748b;margin-bottom:22px;}
.percent-bar-wrap{max-width:400px;margin:0 auto;}
.percent-bar-bg{height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden;}
.percent-bar-fill{height:100%;border-radius:5px;transition:width 1s;}
.percent-label{font-size:13px;color:#64748b;margin-top:6px;}

/* Stats row */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
@media(max-width:640px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:#fff;border-radius:14px;padding:18px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.stat-icon{font-size:24px;margin-bottom:8px;}
.stat-val{font-size:18px;font-weight:800;color:#0f172a;margin-bottom:3px;}
.stat-lbl{font-size:11.5px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}

/* Section cards */
.section-card{background:#fff;border-radius:16px;padding:22px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.sec-title{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.sec-score{margin-left:auto;font-size:13px;color:#64748b;font-weight:600;}

/* MCQ table */
.mcq-table{width:100%;border-collapse:collapse;font-size:13px;}
.mcq-table th{text-align:left;padding:8px 12px;color:#64748b;font-weight:600;font-size:11.5px;text-transform:uppercase;border-bottom:2px solid #f1f5f9;}
.mcq-table td{padding:10px 12px;border-bottom:1px solid #f8fafc;vertical-align:top;}
.mcq-table tr:last-child td{border-bottom:none;}
.correct{color:#15803d;font-weight:700;}
.wrong{color:#dc2626;font-weight:700;}
.notans{color:#94a3b8;}
.tick{color:#16a34a;font-size:16px;}
.cross{color:#dc2626;font-size:16px;}

/* Code & long */
.code-question{margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;}
.code-question:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0;}
.cq-title{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:8px;}
.cq-code{background:#1e293b;color:#e2e8f0;font-family:monospace;font-size:12px;padding:12px;border-radius:8px;white-space:pre-wrap;word-break:break-all;max-height:180px;overflow-y:auto;}
.cq-score{margin-top:8px;font-size:12.5px;font-weight:700;}
.cq-score.awarded{color:#15803d;}
.cq-score.zero{color:#dc2626;}
.long-answer{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:13px;color:#374151;line-height:1.7;white-space:pre-wrap;word-break:break-word;max-height:200px;overflow-y:auto;}

.btn-print{padding:10px 24px;background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;font-weight:700;font-family:inherit;cursor:pointer;margin-right:10px;}
.btn-print:hover{background:#e2e8f0;}
@media print{.no-print{display:none!important;}.page{padding:10px;}}
</style>
</head>
<body>
<div class="topbar no-print">
  <span class="brand">🎓 SIET-LMS</span>
  <div class="topbar-right">
    <button onclick="exitSEB()" class="btn-home" style="cursor:pointer;border:none;font-family:inherit;font-size:13px;font-weight:700;">✕ Exit SEB</button>
    <a href="logout.php" class="btn-logout">Logout</a>
  </div>
</div>

<div class="page">

  <!-- Hero -->
  <div class="result-hero">
    <div class="exam-name"><?= htmlspecialchars($exam['title']) ?></div>
    <div class="status-pill <?= $gradeLetter==='F' ? 'fail' : '' ?>">
      Status: Finished ✅
    </div>
    <div class="marks-circle" style="border-color:<?= $gradeColor ?>;color:<?= $gradeColor ?>">
      <div class="marks-score"><?= $total ?></div>
      <div class="marks-max">/ <?= $maxM ?></div>
    </div>
    <div class="grade-badge" style="background:<?= $gradeColor ?>"><?= $gradeLetter ?></div>
    <div class="grade-desc"><?= $gradeDesc ?></div>
    <div class="percent-bar-wrap">
      <div class="percent-bar-bg">
        <div class="percent-bar-fill" style="width:<?= $pct ?>%;background:<?= $gradeColor ?>"></div>
      </div>
      <div class="percent-label"><?= $pct ?>% — Grade <?= $gradeLetter ?> (<?= $gradeDesc ?>)</div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon">📅</div>
      <div class="stat-val"><?= date('d M Y', strtotime($attempt['started_at'])) ?></div>
      <div class="stat-lbl">Date</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🕐</div>
      <div class="stat-val"><?= date('h:i:s A', strtotime($attempt['started_at'])) ?></div>
      <div class="stat-lbl">Start Time</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏁</div>
      <div class="stat-val"><?= date('h:i:s A', strtotime($attempt['finished_at'])) ?></div>
      <div class="stat-lbl">End Time</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⏱</div>
      <div class="stat-val"><?= $durationStr ?></div>
      <div class="stat-lbl">Duration Taken</div>
    </div>
  </div>

  <!-- Score breakdown row -->
  <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-icon">📝</div>
      <div class="stat-val"><?= $attempt['score_mcq'] ?> / <?= count($mcqData) ?></div>
      <div class="stat-lbl">Part A — MCQ</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💻</div>
      <div class="stat-val"><?= $attempt['score_code'] ?> / <?= array_sum(array_column($codeData,'marks')) ?></div>
      <div class="stat-lbl">Part B — Coding</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📄</div>
      <div class="stat-val"><?= $attempt['score_long'] ?? 0 ?> / <?= array_sum(array_column($longData,'marks')) ?></div>
      <div class="stat-lbl">Part C — Long</div>
    </div>
  </div>

  <!-- Part A Detail -->
  <div class="section-card">
    <div class="sec-title">📝 Part A — MCQ Questions
      <span class="sec-score"><?= $attempt['score_mcq'] ?> / <?= count($mcqData) ?> marks</span>
    </div>
    <table class="mcq-table">
      <thead><tr><th>#</th><th>Question</th><th>Your Answer</th><th>Correct</th><th>Result</th></tr></thead>
      <tbody>
      <?php foreach ($mcqData as $i => $m): 
        $chosen = $m['chosen'] ?? null;
        $correct = $m['answer'];
        $isCorrect = $chosen === $correct;
      ?>
      <tr>
        <td style="font-weight:700;color:#64748b;">Q<?= $i+1 ?></td>
        <td><?= htmlspecialchars(mb_substr($m['question'],0,80)) ?><?= strlen($m['question'])>80 ? '...' : '' ?></td>
        <td><?php if($chosen): ?><strong><?= $chosen ?></strong><?php else: ?><span class="notans">—</span><?php endif; ?></td>
        <td><strong class="correct"><?= $correct ?></strong></td>
        <td><?php if(!$chosen): ?>
          <span class="notans">Not Answered</span>
        <?php elseif($isCorrect): ?>
          <span class="tick">✓</span> <span class="correct">+1</span>
        <?php else: ?>
          <span class="cross">✗</span> <span class="wrong">0</span>
        <?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Part B Detail -->
  <div class="section-card">
    <div class="sec-title">💻 Part B — Coding Questions
      <span class="sec-score"><?= $attempt['score_code'] ?> / <?= array_sum(array_column($codeData,'marks')) ?> marks</span>
    </div>
    <?php foreach ($codeData as $i => $cq): ?>
    <div class="code-question">
      <div class="cq-title">Q<?= count($mcqData)+$i+1 ?> — <?= htmlspecialchars($cq['title'] ?? 'Coding Question') ?> <span style="color:#64748b;font-weight:400;">(<?= $cq['marks'] ?> marks)</span></div>
      <?php if ($cq['submitted_code']): ?>
        <pre class="cq-code"><?= htmlspecialchars($cq['submitted_code']) ?></pre>
        <div class="cq-score awarded">✅ Score awarded: <?= $cq['score'] ?> / <?= $cq['marks'] ?></div>
      <?php else: ?>
        <div style="color:#94a3b8;font-size:13px;">No code submitted.</div>
        <div class="cq-score zero">Score: 0 / <?= $cq['marks'] ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Part C Detail -->
  <div class="section-card">
    <div class="sec-title">📄 Part C — Long Question
      <span class="sec-score"><?= $attempt['score_long'] ?? 0 ?> / <?= array_sum(array_column($longData,'marks')) ?> marks</span>
    </div>
    <?php foreach ($longData as $i => $lq): ?>
    <div class="code-question">
      <div class="cq-title">Q<?= count($mcqData)+count($codeData)+$i+1 ?> — Long Answer <span style="color:#64748b;font-weight:400;">(<?= $lq['marks'] ?> marks)</span></div>
      <div style="font-size:13px;color:#64748b;margin-bottom:8px;"><?= nl2br(htmlspecialchars(mb_substr($lq['question'],0,120))) ?>...</div>
      <?php if ($lq['answer_text']): ?>
        <div class="long-answer"><?= htmlspecialchars($lq['answer_text']) ?></div>
        <div class="cq-score awarded">✅ Score awarded: <?= $lq['score'] ?> / <?= $lq['marks'] ?></div>
      <?php else: ?>
        <div style="color:#94a3b8;font-size:13px;">No answer submitted.</div>
        <div class="cq-score zero">Score: 0 / <?= $lq['marks'] ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Actions -->
  <div style="text-align:center;margin-top:10px;" class="no-print">
    <button class="btn-print" onclick="window.print()">🖨 Print Result</button>
    <button onclick="exitSEB()" class="btn-home" style="display:inline-block;padding:10px 28px;cursor:pointer;border:none;font-family:inherit;font-size:14px;font-weight:700;">✕ Exit Safe Exam Browser</button>
  </div>

</div>
</body>
</html>
