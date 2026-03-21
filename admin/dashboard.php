<?php
session_start();
if (empty($_SESSION['admin'])) { header("Location: index.php"); exit; }
$conn = new mysqli("localhost","root","","seb_lms");

$totalQ  = $conn->query("SELECT COUNT(*) FROM questions")->fetch_row()[0];
$totalS  = $conn->query("SELECT COUNT(*) FROM subjects")->fetch_row()[0];
$totalSt = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$totalP  = $conn->query("SELECT COUNT(*) FROM user_progress WHERE status='solved'")->fetch_row()[0];

$questions = $conn->query("SELECT q.*,s.subject_name FROM questions q LEFT JOIN subjects s ON q.subject_id=s.id ORDER BY q.id DESC")->fetch_all(MYSQLI_ASSOC);
$subjects  = $conn->query("SELECT * FROM subjects ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$students  = $conn->query("SELECT * FROM students ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$results   = $conn->query("
    SELECT er.*, s.name, s.regno, e.title AS exam_title
    FROM exam_results er
    JOIN students s ON er.student_id=s.id
    JOIN exams e ON er.exam_id=e.id
    WHERE er.submitted=1
    ORDER BY er.finished_at DESC
    LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Admin Dashboard — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{margin:0;background:#f4f6f9;color:#0f172a;}
.nav{background:#1e293b;height:52px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;}
.nav-brand{color:#818cf8;font-weight:700;font-size:16px;text-decoration:none;}
.nav-links{display:flex;gap:4px;}
.nav-links a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 12px;border-radius:6px;transition:.14s;}
.nav-links a:hover{background:#334155;color:#fff;}
.nav-links a.red{color:#f87171;}
.main{max-width:1100px;margin:24px auto;padding:0 20px;}
.alert-ok{background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;color:#166534;font-size:13px;font-weight:600;margin-bottom:16px;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.sc{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;text-align:center;}
.sc-val{font-size:2.2rem;font-weight:800;color:#16a34a;line-height:1;}
.sc-lbl{font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-top:5px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:20px;}
.card-head{padding:13px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;}
.card-head h2{font-size:14px;font-weight:700;margin:0;}
.card-body{padding:20px;}
.frow2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.fgroup{margin-bottom:14px;}
.fgroup label{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:5px;}
.fgroup small{font-size:10px;color:#94a3b8;font-weight:400;text-transform:none;}
.fc{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:13px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1);}
textarea.fc{resize:vertical;font-family:'Courier New',monospace;font-size:12.5px;}
.hint-box{background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:14px;line-height:1.6;}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 15px;border-radius:7px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;transition:.13s;}
.btn:active{transform:scale(.97);}
.btn-green{background:#16a34a;color:#fff;}.btn-green:hover{background:#15803d;color:#fff;text-decoration:none;}
.btn-blue{background:#2563eb;color:#fff;}.btn-blue:hover{background:#1d4ed8;color:#fff;text-decoration:none;}
.btn-red{background:#dc2626;color:#fff;}.btn-red:hover{background:#b91c1c;color:#fff;text-decoration:none;}
.btn-gray{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}.btn-gray:hover{background:#e2e8f0;text-decoration:none;}
.tbl-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{background:#f1f5f9;padding:9px 13px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:2px solid #e5e7eb;}
td{padding:10px 13px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:hover td{background:#fafbfc;}
tr:last-child td{border-bottom:none;}
</style></head><body>
<nav class="nav">
  <a href="dashboard.php" class="nav-brand">⟨/⟩ SIET-LMS Admin</a>
  <div class="nav-links">
    <a href="add_subject.php">+ Subject</a>
    <a href="manage_modules.php">Modules</a>
    <a href="exams.php">Exams</a>
    <a href="../practice.php" target="_blank">View Site</a>
    <a href="logout.php" class="red">Logout</a>
  </div>
</nav>
<div class="main">
  <?php if($msg==='added'):?><div class="alert-ok">✔ Question added!</div><?php endif;?>
  <?php if($msg==='updated'):?><div class="alert-ok">✔ Updated.</div><?php endif;?>
  <?php if($msg==='deleted'):?><div class="alert-ok">✔ Deleted.</div><?php endif;?>
  <?php if($msg==='subject'):?><div class="alert-ok">✔ Subject added.</div><?php endif;?>
  <div class="stats">
    <div class="sc"><div class="sc-val"><?=$totalS?></div><div class="sc-lbl">Subjects</div></div>
    <div class="sc"><div class="sc-val"><?=$totalQ?></div><div class="sc-lbl">Questions</div></div>
    <div class="sc"><div class="sc-val"><?=$totalSt?></div><div class="sc-lbl">Students</div></div>
    <div class="sc"><div class="sc-val"><?=$totalP?></div><div class="sc-lbl">Questions Solved</div></div>
  </div>

  <!-- Add Question -->
  <div class="card">
    <div class="card-head"><h2>➕ Add Question</h2></div>
    <div class="card-body">
      <div class="hint-box">💡 Use <code>\n</code> for newlines in Sample Input. Example: <code>6\nA 5\nB 9</code></div>
      <form method="POST" action="save_question.php">
        <div class="frow2">
          <div class="fgroup"><label>Subject *</label>
            <select name="subject_id" class="fc" required><option value="">— Select —</option>
            <?php foreach($subjects as $s):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['subject_name'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Title *</label><input type="text" name="title" class="fc" required></div>
        </div>
        <div class="fgroup"><label>Problem Description *</label><textarea name="question" class="fc" rows="4" required></textarea></div>
        <div class="frow2">
          <div class="fgroup"><label>Sample Input <small>(use \n for newlines)</small></label><textarea name="tc_input" class="fc" rows="3"></textarea></div>
          <div class="fgroup"><label>Expected Output * <small style="color:#dc2626">(exact)</small></label><textarea name="tc_expected" class="fc" rows="3" required></textarea></div>
        </div>
        <button type="submit" class="btn btn-green">✓ Save Question</button>
      </form>
    </div>
  </div>

  <!-- Questions table -->
  <div class="card">
    <div class="card-head"><h2>All Questions (<?=count($questions)?>)</h2></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>#</th><th>Title</th><th>Subject</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($questions as $q):?>
      <tr>
        <td style="color:#94a3b8"><?=$q['id']?></td>
        <td><strong><?=htmlspecialchars($q['title'])?></strong></td>
        <td style="color:#64748b"><?=htmlspecialchars($q['subject_name']??'—')?></td>
        <td style="white-space:nowrap;display:flex;gap:5px">
          <a href="../editor.php?qid=<?=$q['id']?>" target="_blank" class="btn btn-gray">View</a>
          <a href="edit_question.php?id=<?=$q['id']?>" class="btn btn-blue">Edit</a>
          <a href="delete_question.php?id=<?=$q['id']?>" class="btn btn-red" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div>
  </div>

  <!-- Students & Progress -->
  <div class="card">
    <div class="card-head"><h2>Student Progress</h2></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>#</th><th>Name</th><th>Reg No</th><th>Questions Solved</th><th>Attempted</th></tr></thead>
      <tbody>
      <?php foreach($students as $st):
        $solved   = $conn->query("SELECT COUNT(*) FROM user_progress WHERE user_id={$st['id']} AND status='solved'")->fetch_row()[0];
        $att      = $conn->query("SELECT COUNT(*) FROM user_progress WHERE user_id={$st['id']} AND status='attempted'")->fetch_row()[0];
      ?>
      <tr>
        <td style="color:#94a3b8"><?=$st['id']?></td>
        <td><strong><?=htmlspecialchars($st['name'])?></strong></td>
        <td style="font-family:monospace"><?=htmlspecialchars($st['regno'])?></td>
        <td><span style="color:#16a34a;font-weight:700"><?=$solved?></span></td>
        <td><span style="color:#f59e0b;font-weight:700"><?=$att?></span></td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div>
  </div>

  <!-- Exam Results -->
  <?php if(!empty($results)):?>
  <div class="card">
    <div class="card-head"><h2>Exam Results</h2></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>#</th><th>Student</th><th>Exam</th><th>Score</th><th>Submitted</th></tr></thead>
      <tbody>
      <?php foreach($results as $r):?>
      <tr>
        <td style="color:#94a3b8"><?=$r['id']?></td>
        <td><?=htmlspecialchars($r['name'])?> <span style="color:#94a3b8;font-size:11px">(<?=htmlspecialchars($r['regno'])?>)</span></td>
        <td><?=htmlspecialchars($r['exam_title'])?></td>
        <td><strong style="color:#16a34a"><?=$r['score']?>/<?=$r['total']?></strong></td>
        <td style="font-size:11.5px;color:#64748b"><?=date('d M Y H:i',strtotime($r['finished_at']??''))?></td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div>
  </div>
  <?php endif;?>

  <!-- Subjects -->
  <div class="card">
    <div class="card-head"><h2>Subjects (<?=count($subjects)?>)</h2>
      <a href="add_subject.php" class="btn btn-green" style="padding:4px 12px;font-size:11.5px">+ Add</a></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>#</th><th>Subject</th><th>Description</th></tr></thead>
      <tbody>
      <?php foreach($subjects as $s):?>
      <tr><td style="color:#94a3b8"><?=$s['id']?></td><td><strong><?=htmlspecialchars($s['subject_name'])?></strong></td><td style="color:#64748b"><?=htmlspecialchars($s['description']??'')?></td></tr>
      <?php endforeach;?>
      </tbody>
    </table></div>
  </div>
</div>
</body></html>
