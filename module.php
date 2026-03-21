<?php
include "config.php";
requireLogin();
requireSEB();      // Must run inside SEB
$student = currentStudent();
$uid     = $student['id'];

$mid  = intval($_GET['mid']  ?? 0);
$qidx = intval($_GET['qidx'] ?? 0);  // 0-based question index inside module

if (!$mid) { header("Location: practice.php"); exit; }

// Load module
$module = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT m.*, s.subject_name, s.id AS subject_id
     FROM modules m JOIN subjects s ON m.subject_id=s.id
     WHERE m.id=$mid LIMIT 1"));
if (!$module) { header("Location: practice.php"); exit; }

// Load all questions in this module (ordered)
$qRes = mysqli_query($conn,
    "SELECT q.id,q.title,q.question,mq.order_num
     FROM module_questions mq
     JOIN questions q ON mq.question_id=q.id
     WHERE mq.module_id=$mid ORDER BY mq.order_num ASC");
$allQs = [];
while ($r = mysqli_fetch_assoc($qRes)) $allQs[] = $r;

$totalQs = count($allQs);
if ($totalQs === 0) { header("Location: subject.php?id=".$module['subject_id']); exit; }

// Clamp index
$qidx = max(0, min($qidx, $totalQs - 1));
$currentQ = $allQs[$qidx];
$qid      = $currentQ['id'];

// Check module is unlocked for this student
$mp = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM module_progress WHERE user_id=$uid AND module_id=$mid LIMIT 1"));
$modStatus = $mp['status'] ?? 'locked';
if ($modStatus === 'locked') { header("Location: subject.php?id=".$module['subject_id']); exit; }

// Start timer: if no start_time yet, set it now
if (!$mp || !$mp['start_time']) {
    mysqli_query($conn,
        "INSERT INTO module_progress (user_id,module_id,status,start_time,total_marks)
         VALUES ($uid,$mid,'unlocked',NOW(),$totalQs*10)
         ON DUPLICATE KEY UPDATE start_time=IF(start_time IS NULL,NOW(),start_time),
         total_marks=$totalQs*10");
    $mp = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM module_progress WHERE user_id=$uid AND module_id=$mid LIMIT 1"));
}

// Student progress for all questions in this module
$progressMap = [];
$pRes = mysqli_query($conn,
    "SELECT up.question_id,up.status,up.submitted_code
     FROM user_progress up
     JOIN module_questions mq ON up.question_id=mq.question_id
     WHERE mq.module_id=$mid AND up.user_id=$uid");
while ($r = mysqli_fetch_assoc($pRes)) $progressMap[$r['question_id']] = $r;

// Current question's saved code + status
$savedCode   = $progressMap[$qid]['submitted_code'] ?? '';
$savedStatus = $progressMap[$qid]['status']         ?? 'not_started';

// First test case for example
$firstTC = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM testcases WHERE question_id=$qid LIMIT 1"));

// Check if ALL questions in module are solved → mark completed
$solvedCount = count(array_filter($progressMap, fn($p) => $p['status']==='solved'));
$allSolved   = ($solvedCount === $totalQs);

if ($allSolved && $modStatus !== 'completed') {
    $timeSec = $mp['start_time'] ? (time() - strtotime($mp['start_time'])) : 0;
    $marks   = $solvedCount * 10;
    mysqli_query($conn,
        "UPDATE module_progress
         SET status='completed', end_time=NOW(),
             time_taken_sec=$timeSec, marks_obtained=$marks
         WHERE user_id=$uid AND module_id=$mid");
    $modStatus = 'completed';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Q<?= $qidx+1 ?>: <?= htmlspecialchars($currentQ['title']) ?> — <?= htmlspecialchars($module['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#eef2f7;min-height:100vh;font-size:14px;color:#1a202c;}

.topbar{background:#fff;border-bottom:1px solid #d8e2ec;padding:10px 20px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:100;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.topbar-left{display:flex;align-items:center;gap:10px;flex:1;overflow:hidden;}
.topbar-title{font-size:14px;font-weight:700;color:#1a202c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.btn-back{padding:4px 12px;border:1px solid #c8d6e0;border-radius:5px;background:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;color:#4a5568;cursor:pointer;text-decoration:none;transition:.15s;flex-shrink:0;}
.btn-back:hover{background:#f0f5fa;}

/* Progress dots */
.prog-dots{display:flex;align-items:center;gap:5px;flex-shrink:0;}
.pdot{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;border:2px solid transparent;cursor:pointer;text-decoration:none;transition:.15s;}
.pdot:hover{opacity:.8;}
.pd-current{background:#2563eb;color:#fff;border-color:#2563eb;}
.pd-solved{background:#dcfce7;color:#15803d;border-color:#86efac;}
.pd-attempted{background:#fef9c3;color:#92400e;border-color:#fde68a;}
.pd-not{background:#f1f5f9;color:#94a3b8;border-color:#e2e8f0;}

.shell{display:flex;height:calc(100vh - 45px);overflow:hidden;}

/* LEFT: sidebar */
.sidebar{width:210px;flex-shrink:0;background:#fff;border-right:1px solid #d8e2ec;overflow-y:auto;display:flex;flex-direction:column;}
.sb-head{padding:10px 14px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#718096;border-bottom:1px solid #e8eef4;background:#f7fafc;}
.sb-q{display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:12px;font-weight:500;color:#4a5568;text-decoration:none;border-left:3px solid transparent;transition:.12s;}
.sb-q:hover{background:#f0f5fa;color:#1a202c;}
.sb-q.active{border-left-color:#2563eb;background:#eff6ff;color:#2563eb;font-weight:700;}
.sb-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.sbd-s{background:#16a34a;} .sbd-a{background:#f59e0b;} .sbd-n{background:#d1d5db;} .sbd-cur{background:#2563eb;}

/* RIGHT: main */
.main{flex:1;overflow-y:auto;padding:20px 24px 40px;}

/* Question card (light blue box) */
.q-card{background:#fff;border:1px solid #c8d6e0;border-radius:8px;margin-bottom:16px;overflow:hidden;}
.q-hdr{display:flex;align-items:center;justify-content:space-between;padding:9px 16px;background:#f0f5fa;border-bottom:1px solid #d0dde8;}
.q-hdr-left{display:flex;align-items:center;gap:10px;}
.q-nbadge{font-size:11px;font-weight:700;color:#2d6a9f;background:#dbeafe;border:1px solid #bfdbfe;border-radius:12px;padding:2px 10px;}
.q-sbadge{font-size:11px;font-weight:700;border-radius:12px;padding:2px 9px;}
.qsb-s{color:#15803d;background:#dcfce7;border:1px solid #bbf7d0;}
.qsb-a{color:#92400e;background:#fef9c3;border:1px solid #fde68a;}
.qsb-n{color:#6b7280;background:#f1f5f9;border:1px solid #e2e8f0;}
.q-body{padding:16px 18px;}
.q-text{font-size:13px;line-height:1.75;color:#2d3748;white-space:pre-wrap;margin-bottom:12px;}

/* Example block */
.ex-block{border:1px solid #c8d6e0;border-radius:7px;overflow:hidden;margin:12px 0;background:#f7fbff;}
.ex-hdr{background:#dce8f4;padding:5px 14px;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#2d6a9f;border-bottom:1px solid #c4d9ee;}
.ex-row{display:flex;}
.ex-col{flex:1;padding:9px 14px;}
.ex-col:first-child{border-right:1px solid #c8d6e0;}
.ex-col-lbl{font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#718096;margin-bottom:4px;}
.ex-col pre{font-family:'Fira Code',monospace;font-size:12px;color:#1a202c;white-space:pre-wrap;word-break:break-all;}
.ans-line{font-size:12px;font-weight:600;color:#1a202c;margin-top:8px;}
.ans-line .pen{font-weight:400;color:#718096;}

/* Editor */
.ed-section{background:#fff;border:1px solid #c8d6e0;border-radius:8px;margin-bottom:14px;}
.lang-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:#f0f5fa;border-bottom:1px solid #d0dde8;}
.lang-bar-left{display:flex;align-items:center;gap:10px;}
.lang-lbl{font-size:12px;font-weight:600;color:#4a5568;}
select#language{padding:4px 10px;border:1px solid #c8d6e0;border-radius:5px;font-family:'Poppins',sans-serif;font-size:12px;color:#1a202c;background:#fff;outline:none;cursor:pointer;}
select#language:focus{border-color:#16a34a;}
.ace-wrap{position:relative;}
#aceEditor{width:100%;height:240px;font-size:13px;}
.resize-h{height:10px;background:#1e293b;border-top:1px solid #334155;cursor:ns-resize;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;}
.rgrip{width:16px;height:6px;background-image:repeating-linear-gradient(0deg,#4a5568,#4a5568 1px,transparent 1px,transparent 3px);border-radius:1px;}
.check-bar{display:flex;align-items:center;padding:9px 14px;background:#f7fafc;border-bottom:1px solid #e2eaf2;gap:10px;}
.btn-check{padding:7px 22px;background:#16a34a;border:none;border-radius:6px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:.15s;}
.btn-check:hover{background:#15803d;} .btn-check:disabled{opacity:.55;cursor:not-allowed;}

/* Results */
.res-section{border:1px solid #c8d6e0;border-top:none;border-radius:0 0 8px 8px;overflow:hidden;display:none;}
.res-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.res-table th{background:#f0f5fa;padding:7px 12px;text-align:left;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#718096;border-bottom:1px solid #d0dde8;}
.res-table td{padding:8px 12px;border-bottom:1px solid #eaf0f7;font-family:'Fira Code',monospace;font-size:12px;color:#1a202c;white-space:pre-wrap;word-break:break-word;}
.res-table tr:last-child td{border-bottom:none;}
.res-table tr.rpass td{background:#f0fdf4;} .res-table tr.rfail td{background:#fef9f9;}
.sc-pass{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#dcfce7;color:#16a34a;border:1.5px solid #86efac;font-size:11px;font-weight:700;}
.sc-fail{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;font-size:11px;font-weight:700;}
.pass-banner{display:none;align-items:center;gap:8px;padding:10px 14px;font-size:13px;font-weight:700;}
.pass-banner.show{display:flex;}
.pb-pass{background:#f0fdf4;color:#15803d;border-top:1px solid #bbf7d0;}
.pb-fail{background:#fef2f2;color:#dc2626;border-top:1px solid #fecaca;}
.err-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:7px;padding:10px 14px;color:#dc2626;font-size:12.5px;font-weight:600;margin-bottom:10px;display:none;}

/* Module complete overlay */
.mod-complete-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:999;backdrop-filter:blur(3px);}
.mod-complete-overlay.show{display:flex;}
.mco-box{background:#fff;border-radius:16px;padding:36px 32px;max-width:460px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.mco-icon{font-size:52px;margin-bottom:14px;}
.mco-title{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:6px;}
.mco-stats{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;margin:16px 0;text-align:left;}
.mco-stat{display:flex;justify-content:space-between;font-size:13px;padding:4px 0;border-bottom:1px solid #dcfce7;}
.mco-stat:last-child{border-bottom:none;}
.mco-stat .mk{color:#374151;font-weight:600;} .mco-stat .mv{color:#15803d;font-weight:700;}
.mco-btns{display:flex;gap:10px;justify-content:center;margin-top:8px;}
.mco-btn-next{padding:10px 24px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;text-decoration:none;transition:.15s;}
.mco-btn-next:hover{background:#15803d;}
.mco-btn-back{padding:10px 20px;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;text-decoration:none;transition:.15s;}

/* Prev / Next nav */
.nav-row{display:flex;align-items:center;justify-content:space-between;margin-top:16px;}
.nav-btn{padding:8px 20px;border-radius:7px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:1px solid #c8d6e0;background:#fff;color:#4a5568;text-decoration:none;transition:.15s;display:inline-flex;align-items:center;gap:5px;}
.nav-btn:hover:not(.disabled){background:#f0f5fa;color:#1a202c;}
.nav-btn.next-btn{background:#16a34a;border-color:#16a34a;color:#fff;}
.nav-btn.next-btn:hover{background:#15803d;}
.nav-btn.disabled{opacity:.4;pointer-events:none;}

/* Loading */
.loading-overlay{position:fixed;inset:0;background:rgba(15,20,35,.55);display:none;align-items:center;justify-content:center;flex-direction:column;gap:14px;z-index:998;backdrop-filter:blur(2px);}
.loading-overlay.show{display:flex;}
.spinner{width:38px;height:38px;border:3px solid rgba(255,255,255,.2);border-top-color:#fff;border-radius:50%;animation:spin .65s linear infinite;}
.spin-txt{color:#fff;font-size:13px;font-weight:600;}
@keyframes spin{to{transform:rotate(360deg);}}
::-webkit-scrollbar{width:5px;} ::-webkit-scrollbar-thumb{background:#c8d6e0;border-radius:99px;}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="subject.php?id=<?= $module['subject_id'] ?>" class="btn-back">← Back</a>
    <span class="topbar-title">
      📦 <?= htmlspecialchars($module['title']) ?> — Q<?= $qidx+1 ?>/<?= $totalQs ?>
    </span>
  </div>
  <!-- Progress dots -->
  <div class="prog-dots">
    <?php foreach ($allQs as $qi => $q2):
      $st2 = $progressMap[$q2['id']]['status'] ?? 'not_started';
      $cls = ($qi===$qidx) ? 'pdot pd-current'
           : match($st2){'solved'=>'pdot pd-solved','attempted'=>'pdot pd-attempted',default=>'pdot pd-not'};
    ?>
    <a href="module.php?mid=<?= $mid ?>&qidx=<?= $qi ?>" class="<?= $cls ?>" title="Q<?= $qi+1 ?>">
      <?= $qi+1 ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="shell">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sb-head">Questions</div>
    <?php foreach ($allQs as $qi => $q2):
      $st3 = $progressMap[$q2['id']]['status'] ?? 'not_started';
      $isCur = ($qi===$qidx);
      $dc = $isCur ? 'sbd-cur' : match($st3){'solved'=>'sbd-s','attempted'=>'sbd-a',default=>'sbd-n'};
    ?>
    <a href="module.php?mid=<?= $mid ?>&qidx=<?= $qi ?>"
       class="sb-q <?= $isCur?'active':'' ?>">
      <span class="sb-dot <?= $dc ?>"></span>
      Q<?= $qi+1 ?>. <?= htmlspecialchars($q2['title']) ?>
    </a>
    <?php endforeach; ?>
  </aside>

  <div class="main">
    <div class="err-banner" id="errBanner"></div>

    <!-- Question card -->
    <div class="q-card">
      <div class="q-hdr">
        <div class="q-hdr-left">
          <span class="q-nbadge">Question <?= $qidx+1 ?> of <?= $totalQs ?></span>
          <?php
            $qsbc=match($savedStatus){'solved'=>'qsb-s','attempted'=>'qsb-a',default=>'qsb-n'};
            $qsbl=match($savedStatus){'solved'=>'● Solved','attempted'=>'● Attempted',default=>'○ Not Started'};
          ?>
          <span class="q-sbadge <?= $qsbc ?>" id="statusBadge"><?= $qsbl ?></span>
        </div>
        <span style="font-size:11px;color:#a0aec0">📦 <?= htmlspecialchars($module['title']) ?></span>
      </div>
      <div class="q-body">
        <div class="q-text"><?= htmlspecialchars($currentQ['question']) ?></div>
        <?php if ($firstTC): ?>
        <div class="ex-block">
          <div class="ex-hdr">For Example</div>
          <div class="ex-row">
            <div class="ex-col">
              <div class="ex-col-lbl">Input</div>
              <pre><?= htmlspecialchars(str_replace('\n',"\n",$firstTC['input']??'(no input)')) ?></pre>
            </div>
            <div class="ex-col">
              <div class="ex-col-lbl">Expected Output</div>
              <pre><?= htmlspecialchars($firstTC['expected_output']) ?></pre>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="ans-line">Answer: <span class="pen">(penalty regime: 0%)</span></div>
      </div>
    </div>

    <!-- Editor -->
    <div class="ed-section">
      <div class="lang-bar">
        <div class="lang-bar-left">
          <span class="lang-lbl">Language:</span>
          <select id="language" onchange="onLangChange()">
            <option value="python">Python 3</option>
            <option value="c">C</option>
            <option value="cpp">C++</option>
            <option value="java">Java</option>
          </select>
        </div>
        <button onclick="clearEd()" style="background:transparent;border:1px solid #c8d6e0;border-radius:5px;padding:4px 11px;font-family:'Poppins',sans-serif;font-size:11px;font-weight:600;color:#718096;cursor:pointer;">↺ Clear</button>
      </div>
      <div class="ace-wrap"><div id="aceEditor"></div></div>
      <div class="resize-h" id="resizeH" title="Drag to resize"><div class="rgrip"></div></div>
      <div class="check-bar">
        <button class="btn-check" id="btnCheck" onclick="runCode()">✓ Check</button>
      </div>
      <div class="res-section" id="resSection">
        <table class="res-table">
          <thead><tr><th>Input</th><th>Expected</th><th>Got</th><th></th></tr></thead>
          <tbody id="resBody"></tbody>
        </table>
        <div class="pass-banner" id="passBanner"></div>
      </div>
    </div>

    <!-- Prev / Next -->
    <div class="nav-row">
      <?php if ($qidx > 0): ?>
        <a href="module.php?mid=<?= $mid ?>&qidx=<?= $qidx-1 ?>" class="nav-btn">← Previous</a>
      <?php else: ?>
        <a href="subject.php?id=<?= $module['subject_id'] ?>" class="nav-btn">← All Modules</a>
      <?php endif; ?>

      <span style="font-size:12px;color:#94a3b8;font-weight:600">
        <?= $solvedCount ?>/<?= $totalQs ?> solved
      </span>

      <?php if ($qidx < $totalQs - 1): ?>
        <a href="module.php?mid=<?= $mid ?>&qidx=<?= $qidx+1 ?>" class="nav-btn next-btn">Next →</a>
      <?php else: ?>
        <button class="nav-btn next-btn" onclick="showModComplete()">Finish Module ✓</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Module complete overlay -->
<div class="mod-complete-overlay <?= $allSolved?'show':'' ?>" id="modCompleteOverlay">
  <div class="mco-box">
    <div class="mco-icon">🎉</div>
    <div class="mco-title">Module Completed!</div>
    <p style="font-size:13.5px;color:#64748b;margin-bottom:4px">
      You have solved all questions in <strong><?= htmlspecialchars($module['title']) ?></strong>
    </p>
    <div class="mco-stats" id="mcoStats">
      <div class="mco-stat"><span class="mk">🏆 Marks</span><span class="mv" id="mcoMarks"><?= $solvedCount*10 ?>/<?= $totalQs*10 ?></span></div>
      <div class="mco-stat"><span class="mk">✅ Questions Solved</span><span class="mv"><?= $solvedCount ?>/<?= $totalQs ?></span></div>
      <?php if ($mp && $mp['start_time']): ?>
      <div class="mco-stat"><span class="mk">🕐 Started</span><span class="mv"><?= date('d M Y, h:i A',strtotime($mp['start_time'])) ?></span></div>
      <?php endif; ?>
      <div class="mco-stat"><span class="mk">🕓 Completed</span><span class="mv" id="mcoEnd"><?= date('d M Y, h:i A') ?></span></div>
    </div>
    <div class="mco-btns">
      <a href="subject.php?id=<?= $module['subject_id'] ?>" class="mco-btn-next">Next Module →</a>
      <a href="subject.php?id=<?= $module['subject_id'] ?>" class="mco-btn-back">Back to Modules</a>
    </div>
  </div>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <div class="spin-txt">Running your code…</div>
</div>

<script src="assets/ace.js"></script>
<script src="assets/theme-monokai.js"></script>
<script src="assets/mode-python.js"></script>
<script src="assets/mode-c_cpp.js"></script>
<script>
const QID = <?= $qid ?>;
const MID = <?= $mid ?>;
const SAVED_CODE = <?= json_encode($savedCode) ?>;
const TOTAL_QS   = <?= $totalQs ?>;
const QIDX       = <?= $qidx ?>;

// Ace
const editor = ace.edit("aceEditor");
editor.setTheme("ace/theme/monokai");
editor.session.setMode("ace/mode/python");
editor.setOptions({fontSize:"13px",fontFamily:"'Fira Code',monospace",showPrintMargin:false,tabSize:4,useSoftTabs:true,scrollPastEnd:.2});
editor.setValue(SAVED_CODE||"",-1);
editor.focus();

const MODES={python:'ace/mode/python',c:'ace/mode/c_cpp',cpp:'ace/mode/c_cpp',java:'ace/mode/java'};
function onLangChange(){editor.session.setMode(MODES[document.getElementById('language').value]||'ace/mode/python');}
function clearEd(){if(editor.getValue().trim()&&!confirm("Clear editor?"))return;editor.setValue("",-1);editor.focus();hide('resSection');hide('errBanner');}

// Resize
(function(){
  const h=document.getElementById('resizeH'),a=document.getElementById('aceEditor');
  let drag=false,sy=0,sh=0;
  h.addEventListener('mousedown',e=>{drag=true;sy=e.clientY;sh=a.offsetHeight;document.body.style.userSelect='none';e.preventDefault();});
  document.addEventListener('mousemove',e=>{if(!drag)return;a.style.height=Math.max(120,Math.min(500,sh+(e.clientY-sy)))+'px';editor.resize();});
  document.addEventListener('mouseup',()=>{drag=false;document.body.style.userSelect='';});
})();

const id=s=>document.getElementById(s);
const show=s=>{const e=id(s);if(e)e.style.display='block';};
const hide=s=>{const e=id(s);if(e)e.style.display='none';};
const esc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

function runCode(){
  const code=editor.getValue();
  if(!code.trim()){showErr("Please write your code first.");return;}
  const lang=id('language').value;
  id('loadingOverlay').classList.add('show');
  id('btnCheck').disabled=true;
  hide('errBanner'); hide('resSection');
  const pb=id('passBanner');pb.className='pass-banner';pb.innerHTML='';

  fetch('run.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'code='+encodeURIComponent(code)+'&language='+encodeURIComponent(lang)+'&qid='+QID})
  .then(r=>r.text())
  .then(raw=>{
    let d;try{d=JSON.parse(raw);}catch(e){throw new Error('Bad response: '+raw.slice(0,200));}
    if(d.error){showErr(d.error);return;}
    const rows=d.tests||[];
    if(!rows.length){showErr("No test cases found.");return;}
    let html='';
    rows.forEach(t=>{
      const p=t.pass===true;
      html+=`<tr class="${p?'rpass':'rfail'}">
        <td>${esc(t.input||'(none)')}</td><td>${esc(t.expected||'')}</td>
        <td>${esc(t.got||'(no output)')}</td>
        <td><span class="${p?'sc-pass':'sc-fail'}">${p?'✓':'✗'}</span></td></tr>`;
    });
    id('resBody').innerHTML=html;
    id('resSection').style.display='block';
    const allPass=d.all_passed===true;
    pb.className='pass-banner show '+(allPass?'pb-pass':'pb-fail');
    pb.innerHTML=allPass?'✔ Passed all tests ☺':'✗ Some tests failed';

    // Save progress
    saveProgress(code,allPass);

    if(allPass){
      const b=id('statusBadge');b.className='q-sbadge qsb-s';b.textContent='● Solved';
      // Update dot
      const dot=document.querySelector('.pdot:nth-child('+(QIDX+1)+')');
      if(dot){dot.className='pdot pd-solved';}
      // Check if all done → show completion overlay after short delay
      fetch('check_module_complete.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'mid='+MID})
      .then(r=>r.json())
      .then(res=>{
        if(res.completed){
          setTimeout(()=>showModComplete(res.marks,res.total_marks,res.end_time,res.time_sec),1200);
        }
      });
    } else {
      const b=id('statusBadge');
      if(!b.classList.contains('qsb-s')){b.className='q-sbadge qsb-a';b.textContent='● Attempted';}
    }
  })
  .catch(e=>showErr(e.message))
  .finally(()=>{id('loadingOverlay').classList.remove('show');id('btnCheck').disabled=false;});
}

function saveProgress(code,solved){
  fetch('save_progress.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'qid='+QID+'&code='+encodeURIComponent(code)+'&solved='+(solved?1:0)});
}

function showErr(msg){const e=id('errBanner');e.textContent='⚠ '+msg;e.style.display='block';}

function showModComplete(marks,totalMarks,endTime,timeSec){
  if(marks!==undefined) id('mcoMarks').textContent=(marks||0)+'/'+(totalMarks||0);
  if(endTime) id('mcoEnd').textContent=endTime;
  id('modCompleteOverlay').classList.add('show');
}

editor.commands.addCommand({name:'check',bindKey:{win:'Ctrl-Enter',mac:'Cmd-Enter'},exec:()=>runCode()});
</script>
</body>
</html>
