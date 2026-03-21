<?php
include "config.php";
requireLogin();
requireSEB();      // Must run inside SEB
$student = currentStudent();

$qid = intval($_GET['qid'] ?? 0);
if (!$qid) { header("Location: practice.php"); exit; }

$stmt = mysqli_prepare($conn,
    "SELECT q.*, s.subject_name, s.id AS sid
     FROM questions q
     LEFT JOIN subjects s ON q.subject_id = s.id
     WHERE q.id = ?");
mysqli_stmt_bind_param($stmt, "i", $qid);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$data) { echo "Question not found."; exit; }

// Prev / Next
$prevRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM questions WHERE subject_id={$data['subject_id']} AND id < $qid ORDER BY id DESC LIMIT 1"));
$nextRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM questions WHERE subject_id={$data['subject_id']} AND id > $qid ORDER BY id ASC  LIMIT 1"));

// Example test case
$firstTC = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM testcases WHERE question_id=$qid LIMIT 1"));

// Sidebar questions with status
$allQ = mysqli_query($conn,
    "SELECT q.id, q.title,
            COALESCE(up.status,'not_started') AS status
     FROM questions q
     LEFT JOIN user_progress up ON up.question_id=q.id AND up.user_id={$student['id']}
     WHERE q.subject_id={$data['subject_id']}
     ORDER BY q.id ASC");
$sidebarQ = [];
while ($r = mysqli_fetch_assoc($allQ)) $sidebarQ[] = $r;

$qNum = 1;
foreach ($sidebarQ as $i => $sq) {
    if ($sq['id'] == $qid) { $qNum = $i + 1; break; }
}

// Last saved code for this question
$savedRow = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT submitted_code, status FROM user_progress
     WHERE user_id={$student['id']} AND question_id=$qid LIMIT 1"));
$savedCode   = $savedRow['submitted_code'] ?? '';
$savedStatus = $savedRow['status']         ?? 'not_started';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($data['title']) ?> — Practice</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── EXACT theme from working editor.php ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Poppins',sans-serif;background:#eef2f7;min-height:100vh;font-size:14px;color:#1a202c;}

.topbar{background:#fff;border-bottom:1px solid #d8e2ec;padding:10px 20px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:100;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.topbar-title{font-size:15px;font-weight:700;color:#1a202c;display:flex;align-items:center;gap:8px;}
.btn-back{padding:4px 14px;border:1px solid #c8d6e0;border-radius:5px;background:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:600;color:#4a5568;cursor:pointer;text-decoration:none;transition:.15s;}
.btn-back:hover{background:#f0f5fa;}

.shell{display:flex;height:calc(100vh - 45px);overflow:hidden;}

/* Sidebar */
.sidebar{width:220px;flex-shrink:0;background:#fff;border-right:1px solid #d8e2ec;overflow-y:auto;display:flex;flex-direction:column;}
.sidebar-head{padding:10px 14px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#718096;border-bottom:1px solid #e8eef4;background:#f7fafc;}
.sq-item{display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:12px;font-weight:500;color:#4a5568;text-decoration:none;border-left:3px solid transparent;transition:.12s;}
.sq-item:hover{background:#f0f5fa;color:#1a202c;}
.sq-item.active{border-left-color:#16a34a;background:#f0fdf4;color:#15803d;font-weight:700;}
.sq-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.dot-solved{background:#16a34a;} .dot-attempted{background:#f59e0b;} .dot-not{background:#d1d5db;}

/* Main */
.main{flex:1;overflow-y:auto;padding:20px 24px 40px;}

/* Question card */
.q-card{background:#fff;border:1px solid #c8d6e0;border-radius:8px;margin-bottom:18px;overflow:hidden;}
.q-header{display:flex;align-items:center;justify-content:space-between;padding:9px 16px;background:#f0f5fa;border-bottom:1px solid #d0dde8;}
.q-header-left{display:flex;align-items:center;gap:10px;}
.q-num-badge{font-size:11px;font-weight:700;color:#2d6a9f;background:#dbeafe;border:1px solid #bfdbfe;border-radius:12px;padding:2px 10px;}
.q-status-badge{font-size:11px;font-weight:700;border-radius:12px;padding:2px 9px;}
.qsb-solved{color:#15803d;background:#dcfce7;border:1px solid #bbf7d0;}
.qsb-attempted{color:#92400e;background:#fef9c3;border:1px solid #fde68a;}
.qsb-not{color:#6b7280;background:#f1f5f9;border:1px solid #e2e8f0;}
.q-marks{font-size:11px;color:#718096;}
.q-body{padding:16px 18px;}
.q-text{font-size:13px;line-height:1.75;color:#2d3748;white-space:pre-wrap;margin-bottom:14px;}

.example-block{border:1px solid #c8d6e0;border-radius:7px;overflow:hidden;margin:14px 0 10px;background:#f7fbff;}
.example-header{background:#dce8f4;padding:5px 14px;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#2d6a9f;border-bottom:1px solid #c4d9ee;}
.example-row{display:flex;}
.example-col{flex:1;padding:10px 14px;}
.example-col:first-child{border-right:1px solid #c8d6e0;}
.example-col-label{font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#718096;margin-bottom:5px;}
.example-col pre{font-family:'Fira Code',monospace;font-size:12px;color:#1a202c;white-space:pre-wrap;word-break:break-all;}
.answer-line{font-size:12px;font-weight:600;color:#1a202c;margin-top:8px;}
.answer-line .penalty{font-weight:400;color:#718096;}

/* Editor section */
.editor-section{background:#fff;border:1px solid #c8d6e0;border-radius:8px;margin-bottom:16px;}
.lang-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:#f0f5fa;border-bottom:1px solid #d0dde8;}
.lang-bar-left{display:flex;align-items:center;gap:10px;}
.lang-label{font-size:12px;font-weight:600;color:#4a5568;}
select#language{padding:4px 10px;border:1px solid #c8d6e0;border-radius:5px;font-family:'Poppins',sans-serif;font-size:12px;color:#1a202c;background:#fff;outline:none;cursor:pointer;}
select#language:focus{border-color:#16a34a;}
.ace-wrap{position:relative;}
#aceEditor{width:100%;height:260px;font-size:13px;font-family:'Fira Code',monospace;}
.resize-handle{height:10px;background:#1e293b;border-top:1px solid #334155;cursor:ns-resize;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;user-select:none;}
.resize-grip{width:16px;height:6px;background-image:repeating-linear-gradient(0deg,#4a5568,#4a5568 1px,transparent 1px,transparent 3px);border-radius:1px;}

.check-bar{display:flex;align-items:center;padding:10px 14px;background:#f7fafc;border-bottom:1px solid #e2eaf2;}
.btn-check{padding:7px 24px;background:#16a34a;border:none;border-radius:6px;color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s,transform .08s;display:inline-flex;align-items:center;gap:6px;}
.btn-check:hover{background:#15803d;}
.btn-check:active{transform:scale(.97);}
.btn-check:disabled{opacity:.55;cursor:not-allowed;}

/* Results */
.results-section{border:1px solid #c8d6e0;border-top:none;border-radius:0 0 8px 8px;overflow:hidden;display:none;}
.results-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.results-table th{background:#f0f5fa;padding:8px 14px;text-align:left;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#718096;border-bottom:1px solid #d0dde8;border-right:1px solid #e2eaf2;}
.results-table th:last-child{border-right:none;}
.results-table td{padding:9px 14px;border-bottom:1px solid #eaf0f7;border-right:1px solid #eaf0f7;vertical-align:top;font-family:'Fira Code',monospace;font-size:12px;color:#1a202c;white-space:pre-wrap;word-break:break-word;}
.results-table td:last-child{border-right:none;font-family:'Poppins',sans-serif;font-size:13px;}
.results-table tr:last-child td{border-bottom:none;}
.results-table tr.row-pass td{background:#f0fdf4;}
.results-table tr.row-fail td{background:#fef9f9;}
.status-circle{width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;}
.sc-pass{background:#dcfce7;color:#16a34a;border:1.5px solid #86efac;}
.sc-fail{background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;}

.passed-banner{display:none;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:700;}
.passed-banner.show{display:flex;}
.passed-banner.pass{background:#f0fdf4;color:#15803d;border-top:1px solid #bbf7d0;}
.passed-banner.fail{background:#fef2f2;color:#dc2626;border-top:1px solid #fecaca;}

.error-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:7px;padding:10px 16px;color:#dc2626;font-size:12.5px;font-weight:600;margin-bottom:12px;display:none;}

/* Footer nav */
.footer-nav{display:flex;align-items:center;justify-content:space-between;margin-top:20px;}
.nav-btn{padding:7px 18px;border-radius:6px;font-family:'Poppins',sans-serif;font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:none;border:1px solid #c8d6e0;background:#fff;color:#4a5568;transition:.15s;display:inline-flex;align-items:center;gap:5px;}
.nav-btn:hover:not(.disabled){background:#f0f5fa;color:#1a202c;}
.nav-btn.next{background:#16a34a;border-color:#16a34a;color:#fff;}
.nav-btn.next:hover{background:#15803d;}
.nav-btn.disabled{opacity:.4;pointer-events:none;cursor:default;}

/* Loading */
.loading-overlay{position:fixed;inset:0;background:rgba(15,20,35,.55);display:none;align-items:center;justify-content:center;flex-direction:column;gap:14px;z-index:999;backdrop-filter:blur(2px);}
.loading-overlay.show{display:flex;}
.spinner{width:40px;height:40px;border:3px solid rgba(255,255,255,.2);border-top-color:#fff;border-radius:50%;animation:spin .65s linear infinite;}
.loading-text{color:#fff;font-size:13px;font-weight:600;}
@keyframes spin{to{transform:rotate(360deg);}}
::-webkit-scrollbar{width:5px;height:5px;}
::-webkit-scrollbar-thumb{background:#c8d6e0;border-radius:99px;}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-title">
    📋 <?= htmlspecialchars($data['subject_name']) ?> — <?= htmlspecialchars($data['title']) ?>
  </div>
  <a href="subject.php?id=<?= $data['subject_id'] ?>" class="btn-back">← Back</a>
</div>

<div class="shell">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-head">Questions</div>
    <?php foreach ($sidebarQ as $i => $sq): ?>
    <?php $dotCls = match($sq['status']) { 'solved'=>'dot-solved','attempted'=>'dot-attempted',default=>'dot-not' }; ?>
    <a href="editor.php?qid=<?= $sq['id'] ?>"
       class="sq-item <?= $sq['id'] == $qid ? 'active' : '' ?>">
      <span class="sq-dot <?= $dotCls ?>"></span>
      Q<?= $i+1 ?>. <?= htmlspecialchars($sq['title']) ?>
    </a>
    <?php endforeach; ?>
  </aside>

  <div class="main">
    <div class="error-banner" id="errorBanner"></div>

    <!-- Question card -->
    <div class="q-card">
      <div class="q-header">
        <div class="q-header-left">
          <span class="q-num-badge">Question <?= $qNum ?></span>
          <?php
          $qsbCls = match($savedStatus) { 'solved'=>'qsb-solved','attempted'=>'qsb-attempted',default=>'qsb-not' };
          $qsbLbl = match($savedStatus) { 'solved'=>'● Solved','attempted'=>'● Attempted',default=>'○ Not Started' };
          ?>
          <span class="q-status-badge <?= $qsbCls ?>" id="statusBadge"><?= $qsbLbl ?></span>
          <span class="q-marks">Marked out of 10</span>
        </div>
        <span style="font-size:11px;color:#a0aec0;cursor:pointer">⚑ Flag</span>
      </div>
      <div class="q-body">
        <div class="q-text"><?= htmlspecialchars($data['question']) ?></div>
        <?php if ($firstTC): ?>
        <div class="example-block">
          <div class="example-header">For Example</div>
          <div class="example-row">
            <div class="example-col">
              <div class="example-col-label">Input</div>
              <pre><?= htmlspecialchars(str_replace('\n',"\n",$firstTC['input']??'(no input)')) ?></pre>
            </div>
            <div class="example-col">
              <div class="example-col-label">Expected Output</div>
              <pre><?= htmlspecialchars($firstTC['expected_output']) ?></pre>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="answer-line">Answer: <span class="penalty">(penalty regime: 0%)</span></div>
      </div>
    </div>

    <!-- Editor card -->
    <div class="editor-section" id="editorCard">
      <div class="lang-bar">
        <div class="lang-bar-left">
          <span class="lang-label">Language:</span>
          <select id="language" onchange="onLangChange()">
            <option value="python">Python 3</option>
            <option value="c">C</option>
            <option value="cpp">C++</option>
            <option value="java">Java</option>
          </select>
        </div>
        <button onclick="clearEditor()" style="background:transparent;border:1px solid #c8d6e0;border-radius:5px;padding:4px 12px;font-family:'Poppins',sans-serif;font-size:11px;font-weight:600;color:#718096;cursor:pointer;">
          ↺ Clear
        </button>
      </div>
      <div class="ace-wrap"><div id="aceEditor"></div></div>
      <div class="resize-handle" id="resizeHandle" title="Drag to resize">
        <div class="resize-grip"></div>
      </div>
      <div class="check-bar">
        <button class="btn-check" id="btnCheck" onclick="runCode()">✓ Check</button>
      </div>
      <div class="results-section" id="resultsSection">
        <table class="results-table">
          <thead><tr><th>Input</th><th>Expected</th><th>Got</th><th></th></tr></thead>
          <tbody id="resultsBody"></tbody>
        </table>
        <div class="passed-banner" id="passedBanner"></div>
      </div>
    </div>

    <!-- Footer nav -->
    <div class="footer-nav">
      <?php if ($prevRow): ?>
        <a href="editor.php?qid=<?= $prevRow['id'] ?>" class="nav-btn">← Previous</a>
      <?php else: ?>
        <span class="nav-btn disabled">← Previous</span>
      <?php endif; ?>
      <a href="subject.php?id=<?= $data['subject_id'] ?>" class="nav-btn">All Questions</a>
      <?php if ($nextRow): ?>
        <a href="editor.php?qid=<?= $nextRow['id'] ?>" class="nav-btn next">Next Page →</a>
      <?php else: ?>
        <span class="nav-btn next disabled">Next Page →</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner"></div>
  <div class="loading-text">Running your code…</div>
</div>

<script src="assets/ace.js"></script>
<script src="assets/theme-monokai.js"></script>
<script src="assets/mode-python.js"></script>
<script src="assets/mode-c_cpp.js"></script>
<script>
const SAVED_CODE = <?= json_encode($savedCode) ?>;
const QID        = <?= $qid ?>;

const editor = ace.edit("aceEditor");
editor.setTheme("ace/theme/monokai");
editor.session.setMode("ace/mode/python");
editor.setOptions({ fontSize:"13px", fontFamily:"'Fira Code',monospace", showPrintMargin:false, tabSize:4, useSoftTabs:true, wrap:false, scrollPastEnd:.2 });
editor.setValue(SAVED_CODE || "", -1);
editor.focus();

const MODES = { python:'ace/mode/python', c:'ace/mode/c_cpp', cpp:'ace/mode/c_cpp', java:'ace/mode/java' };
function onLangChange() {
  const lang = document.getElementById("language").value;
  editor.session.setMode(MODES[lang]||'ace/mode/python');
}
function clearEditor() {
  if (editor.getValue().trim() && !confirm("Clear the editor?")) return;
  editor.setValue("", -1); editor.focus();
  hide("resultsSection"); hide("errorBanner");
  const pb = id("passedBanner"); pb.className="passed-banner"; pb.innerHTML="";
}

// Resize handle
(function(){
  const handle=document.getElementById("resizeHandle");
  const aceEl=document.getElementById("aceEditor");
  let dragging=false,startY=0,startH=0;
  handle.addEventListener("mousedown",e=>{dragging=true;startY=e.clientY;startH=aceEl.offsetHeight;document.body.style.userSelect="none";e.preventDefault();});
  document.addEventListener("mousemove",e=>{if(!dragging)return;const newH=Math.max(120,Math.min(600,startH+(e.clientY-startY)));aceEl.style.height=newH+"px";editor.resize();});
  document.addEventListener("mouseup",()=>{dragging=false;document.body.style.userSelect="";});
})();

const id=s=>document.getElementById(s);
const show=s=>{const el=id(s);if(el)el.style.display="block";};
const hide=s=>{const el=id(s);if(el)el.style.display="none";};
const esc=s=>String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");

function runCode() {
  const code=editor.getValue();
  if(!code.trim()){showError("Please write your code first.");return;}
  const lang=id("language").value;

  id("loadingOverlay").classList.add("show");
  id("btnCheck").disabled=true;
  hide("errorBanner"); hide("resultsSection");
  const pb=id("passedBanner"); pb.className="passed-banner"; pb.innerHTML="";

  fetch("run.php",{
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body:"code="+encodeURIComponent(code)+"&language="+encodeURIComponent(lang)+"&qid="+QID
  })
  .then(r=>r.text())
  .then(raw=>{
    let data;
    try{data=JSON.parse(raw);}
    catch(e){throw new Error("Bad server response: "+raw.slice(0,200));}
    if(data.error){showError(data.error);return;}

    const rows=data.tests||[];
    if(!rows.length){showError("No test cases found.");return;}

    let html="";
    rows.forEach(t=>{
      const pass=t.pass===true;
      const rc=pass?"row-pass":"row-fail";
      const icon=pass?'<span class="status-circle sc-pass">✓</span>':'<span class="status-circle sc-fail">✗</span>';
      html+=`<tr class="${rc}"><td>${esc(t.input||"(none)")}</td><td>${esc(t.expected||"")}</td><td>${esc(t.got||"(no output)")}</td><td>${icon}</td></tr>`;
    });
    id("resultsBody").innerHTML=html;
    id("resultsSection").style.display="block";

    const allPass=data.all_passed===true;
    pb.className="passed-banner show "+(allPass?"pass":"fail");
    pb.innerHTML=allPass?"✔ Passed all tests ☺":"✗ Some tests failed — check expected vs your output";
    id("resultsSection").scrollIntoView({behavior:"smooth",block:"nearest"});

    // Save progress
    saveProgress(code, allPass);
    if(allPass){
      const badge=id("statusBadge");
      badge.className="q-status-badge qsb-solved";
      badge.textContent="● Solved";
    } else {
      const badge=id("statusBadge");
      if(!badge.classList.contains("qsb-solved")){
        badge.className="q-status-badge qsb-attempted";
        badge.textContent="● Attempted";
      }
    }
  })
  .catch(err=>showError(err.message))
  .finally(()=>{id("loadingOverlay").classList.remove("show");id("btnCheck").disabled=false;});
}

function saveProgress(code, solved) {
  fetch("save_progress.php",{
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body:"qid="+QID+"&code="+encodeURIComponent(code)+"&solved="+(solved?1:0)
  });
}

function showError(msg){const el=id("errorBanner");el.textContent="⚠ "+msg;el.style.display="block";}
editor.commands.addCommand({name:"check",bindKey:{win:"Ctrl-Enter",mac:"Cmd-Enter"},exec:()=>runCode()});
</script>
</body>
</html>
