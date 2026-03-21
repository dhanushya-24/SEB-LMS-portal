<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn = new mysqli("localhost","root","","seb_lms");
$msg = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action = $_POST['action']??'';

    if($action==='add_exam'){
        $sid   = intval($_POST['subject_id']??0) ?: 'NULL';
        $title = trim($_POST['title']);
        $dur   = intval($_POST['duration_min']??90);
        $tot   = intval($_POST['total_marks']??100);
        $ep    = trim($_POST['entry_password']??'');
        $xp    = trim($_POST['exit_password']??'quit2024');
        $st    = trim($_POST['start_time']??'');
        $et    = trim($_POST['end_time']??'');
        $stQ   = $st ? "'$st'" : 'NULL';
        $etQ   = $et ? "'$et'" : 'NULL';
        if($title){
            $conn->query("INSERT INTO exams(subject_id,title,duration_min,total_marks,is_active,entry_password,exit_password,start_time,end_time)
              VALUES($sid,'".addslashes($title)."',$dur,$tot,1,'".addslashes($ep)."','".addslashes($xp)."',$stQ,$etQ)");
            $msg = "✔ Exam created!";
        }
    }

    if($action==='add_mcq'){
        $eid  = intval($_POST['exam_id']);
        $q    = trim($_POST['question']);
        $oa   = trim($_POST['opt_a']); $ob=trim($_POST['opt_b']);
        $oc   = trim($_POST['opt_c']); $od=trim($_POST['opt_d']);
        $ans  = strtoupper(substr(trim($_POST['answer']??'A'),0,1));
        $ord  = intval($_POST['order_num']??1);
        if($eid&&$q){
            $st=$conn->prepare("INSERT INTO exam_mcq_questions(exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num)VALUES(?,?,?,?,?,?,?,1,?)");
            $st->bind_param("issssssi",$eid,$q,$oa,$ob,$oc,$od,$ans,$ord);
            $st->execute();
            $msg = "✔ MCQ added!";
        }
    }

    if($action==='add_coding_q'){
        $eid  = intval($_POST['exam_id']);
        $qid  = intval($_POST['question_id']);
        $part = trim($_POST['part']??'B');
        $marks= intval($_POST['marks']??7);
        $ord  = intval($_POST['order_num']??1);
        if($eid&&$qid){
            $conn->query("INSERT IGNORE INTO exam_coding_questions(exam_id,question_id,part,marks,order_num)VALUES($eid,$qid,'$part',$marks,$ord)");
            $msg = "✔ Coding question added to exam!";
        }
    }

    if($action==='toggle_exam'){
        $eid   = intval($_POST['exam_id']);
        $isAct = intval($_POST['is_active']);
        $conn->query("UPDATE exams SET is_active=$isAct WHERE id=$eid");
        $msg = "✔ Exam status updated.";
    }
}

$subjects = $conn->query("SELECT * FROM subjects ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$exams    = $conn->query("SELECT e.*,s.subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id=s.id ORDER BY e.id DESC")->fetch_all(MYSQLI_ASSOC);
$allQs    = $conn->query("SELECT q.*,s.subject_name FROM questions q JOIN subjects s ON q.subject_id=s.id ORDER BY q.subject_id,q.id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Manage Exams</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#f4f6f9;}
.nav{background:#1e293b;height:52px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;}
.nav-brand{color:#818cf8;font-weight:700;font-size:16px;text-decoration:none;}
.nav a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 12px;border-radius:6px;}
.nav a:hover{background:#334155;color:#fff;}
.main{max-width:1100px;margin:24px auto;padding:0 20px;}
.alert-ok{background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;color:#166534;font-size:13px;font-weight:600;margin-bottom:16px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:20px;}
.ch{padding:13px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;}
.ch h2{font-size:14px;font-weight:700;margin:0;}
.cb{padding:20px;}
.frow2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.frow3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
.fgroup{margin-bottom:13px;}
.fgroup label{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:4px;}
.fc{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:12.5px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;}textarea.fc{resize:vertical;min-height:65px;}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:7px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;transition:.13s;}
.bgg{background:#16a34a;color:#fff;}.bgg:hover{background:#15803d;}
.bbb{background:#2563eb;color:#fff;}.bbb:hover{background:#1d4ed8;}
.bgr{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{background:#f1f5f9;padding:8px 12px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:2px solid #e5e7eb;}
td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:hover td{background:#fafbfc;}tr:last-child td{border-bottom:none;}
</style></head><body>
<nav class="nav">
  <a href="dashboard.php" class="nav-brand">⟨/⟩ Admin</a>
  <a href="dashboard.php">← Dashboard</a>
</nav>
<div class="main">
  <?php if($msg):?><div class="alert-ok"><?=htmlspecialchars($msg)?></div><?php endif;?>

  <!-- Create Exam -->
  <div class="card">
    <div class="ch"><h2>➕ Create Exam</h2></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="add_exam">
        <div class="frow2">
          <div class="fgroup"><label>Subject</label>
            <select name="subject_id" class="fc">
              <option value="">— All subjects —</option>
              <?php foreach($subjects as $s):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['subject_name'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Exam Title *</label>
            <input type="text" name="title" class="fc" placeholder="e.g. Python Mid-Term" required></div>
        </div>
        <div class="frow3">
          <div class="fgroup"><label>Duration (minutes)</label>
            <input type="number" name="duration_min" class="fc" value="90" min="1"></div>
          <div class="fgroup"><label>Entry Password</label>
            <input type="text" name="entry_password" class="fc" placeholder="Leave blank = no password"></div>
          <div class="fgroup"><label>Exit Password</label>
            <input type="text" name="exit_password" class="fc" value="quit2024"></div>
        </div>
        <div class="frow2">
          <div class="fgroup"><label>Exam Opens (optional)</label>
            <input type="datetime-local" name="start_time" class="fc"></div>
          <div class="fgroup"><label>Exam Closes (optional)</label>
            <input type="datetime-local" name="end_time" class="fc"></div>
        </div>
        <button type="submit" class="btn bgg">✓ Create Exam</button>
      </form>
    </div>
  </div>

  <!-- Add MCQ to Exam -->
  <div class="card">
    <div class="ch"><h2>➕ Add MCQ Question to Exam</h2></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="add_mcq">
        <div class="frow2">
          <div class="fgroup"><label>Exam *</label>
            <select name="exam_id" class="fc" required>
              <option value="">— Select exam —</option>
              <?php foreach($exams as $e):?><option value="<?=$e['id']?>"><?=htmlspecialchars($e['title'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Order Number</label>
            <input type="number" name="order_num" class="fc" value="1" min="1"></div>
        </div>
        <div class="fgroup"><label>Question *</label>
          <textarea name="question" class="fc" rows="2" required></textarea></div>
        <div class="frow2">
          <div class="fgroup"><label>Option A *</label><input type="text" name="opt_a" class="fc" required></div>
          <div class="fgroup"><label>Option B *</label><input type="text" name="opt_b" class="fc" required></div>
          <div class="fgroup"><label>Option C *</label><input type="text" name="opt_c" class="fc" required></div>
          <div class="fgroup"><label>Option D *</label><input type="text" name="opt_d" class="fc" required></div>
        </div>
        <div class="fgroup" style="max-width:200px"><label>Correct Answer *</label>
          <select name="answer" class="fc" required>
            <option value="A">A</option><option value="B">B</option>
            <option value="C">C</option><option value="D">D</option>
          </select></div>
        <button type="submit" class="btn bgg">✓ Add MCQ</button>
      </form>
    </div>
  </div>

  <!-- Add Coding Question to Exam -->
  <div class="card">
    <div class="ch"><h2>➕ Add Coding Question to Exam</h2></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="add_coding_q">
        <div class="frow3">
          <div class="fgroup"><label>Exam *</label>
            <select name="exam_id" class="fc" required>
              <option value="">— Select —</option>
              <?php foreach($exams as $e):?><option value="<?=$e['id']?>"><?=htmlspecialchars($e['title'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Question *</label>
            <select name="question_id" class="fc" required>
              <option value="">— Select —</option>
              <?php foreach($allQs as $q):?><option value="<?=$q['id']?>">[<?=htmlspecialchars($q['subject_name'])?>] <?=htmlspecialchars($q['title'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Part</label>
            <select name="part" class="fc"><option value="B">B (coding)</option><option value="C">C (long)</option></select></div>
        </div>
        <div class="frow2">
          <div class="fgroup"><label>Marks</label><input type="number" name="marks" class="fc" value="7" min="1"></div>
          <div class="fgroup"><label>Order</label><input type="number" name="order_num" class="fc" value="1" min="1"></div>
        </div>
        <button type="submit" class="btn bgg">✓ Add Coding Question</button>
      </form>
    </div>
  </div>

  <!-- Exam list -->
  <div class="card">
    <div class="ch"><h2>All Exams (<?=count($exams)?>)</h2></div>
    <div style="overflow-x:auto"><table>
      <thead><tr><th>#</th><th>Title</th><th>Subject</th><th>Duration</th><th>Entry Pwd</th><th>Window</th><th>Status</th><th>Toggle</th></tr></thead>
      <tbody>
      <?php foreach($exams as $e):
        $mcqc = $conn->query("SELECT COUNT(*) FROM exam_mcq_questions WHERE exam_id={$e['id']}")->fetch_row()[0];
        $codc = $conn->query("SELECT COUNT(*) FROM exam_coding_questions WHERE exam_id={$e['id']}")->fetch_row()[0];
      ?>
      <tr>
        <td><?=$e['id']?></td>
        <td><strong><?=htmlspecialchars($e['title'])?></strong><br>
          <span style="font-size:11px;color:#94a3b8"><?=$mcqc?> MCQ · <?=$codc?> coding</span></td>
        <td style="color:#64748b"><?=htmlspecialchars($e['subject_name']??'All')?></td>
        <td><?=$e['duration_min']?> min</td>
        <td><code><?=htmlspecialchars($e['entry_password'])?:'-'?></code></td>
        <td style="font-size:11px;color:#64748b">
          <?=$e['start_time']?date('d M H:i',strtotime($e['start_time'])):'Any'?>
          <?=$e['end_time']?' → '.date('d M H:i',strtotime($e['end_time'])):''?>
        </td>
        <td><span style="color:<?=$e['is_active']?'#16a34a':'#dc2626'?>;font-weight:700"><?=$e['is_active']?'Active':'Inactive'?></span></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="toggle_exam">
            <input type="hidden" name="exam_id" value="<?=$e['id']?>">
            <input type="hidden" name="is_active" value="<?=$e['is_active']?0:1?>">
            <button type="submit" class="btn bgr"><?=$e['is_active']?'Deactivate':'Activate'?></button>
          </form>
        </td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div>
  </div>

</div></body></html>
