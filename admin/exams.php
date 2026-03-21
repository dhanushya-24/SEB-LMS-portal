<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");

$msg=$_GET['msg']??'';
$exams=$conn->query("SELECT e.*,s.subject_name FROM exams e LEFT JOIN subjects s ON e.subject_id=s.id ORDER BY e.id DESC")->fetch_all(MYSQLI_ASSOC);
$subjects=$conn->query("SELECT * FROM subjects ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$questions=$conn->query("SELECT q.*,s.subject_name FROM questions q LEFT JOIN subjects s ON q.subject_id=s.id ORDER BY q.subject_id,q.id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Manage Exams — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#f4f6f9;color:#0f172a;}
.nav{background:#1e293b;height:52px;padding:0 24px;display:flex;align-items:center;justify-content:space-between;}
.nb{color:#818cf8;font-weight:700;font-size:16px;text-decoration:none;}
.nl{display:flex;gap:4px;}.nl a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 12px;border-radius:6px;transition:.14s;}
.nl a:hover{background:#334155;color:#fff;}.nl a.red{color:#f87171;}
.main{max-width:1100px;margin:24px auto;padding:0 20px;}
.aok{background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;color:#166534;font-size:13px;font-weight:600;margin-bottom:16px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:20px;}
.ch{padding:13px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;}
.ch h2{font-size:14px;font-weight:700;margin:0;}
.cb{padding:20px;}
.frow2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.frow3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
.fg{margin-bottom:14px;}
.fg label{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:5px;}
.fg small{font-size:10px;color:#94a3b8;font-weight:400;text-transform:none;}
.fc{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:13px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1);}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 15px;border-radius:7px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;transition:.13s;}
.btn:active{transform:scale(.97);}
.bg{background:#16a34a;color:#fff;}.bg:hover{background:#15803d;color:#fff;text-decoration:none;}
.bb{background:#2563eb;color:#fff;}.bb:hover{background:#1d4ed8;color:#fff;text-decoration:none;}
.br{background:#dc2626;color:#fff;}.br:hover{background:#b91c1c;color:#fff;text-decoration:none;}
.bgr{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}.bgr:hover{background:#e2e8f0;text-decoration:none;}
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{background:#f1f5f9;padding:9px 13px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:2px solid #e5e7eb;}
td{padding:10px 13px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:hover td{background:#fafbfc;}tr:last-child td{border-bottom:none;}
.bdg{font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;text-transform:uppercase;}
.bda{background:#dcfce7;color:#15803d;}.bdi{background:#f1f5f9;color:#9ca3af;}
.hb{background:#fffbeb;border:1px solid #fde68a;border-radius:7px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:14px;line-height:1.6;}
</style></head><body>
<nav class="nav">
  <a href="dashboard.php" class="nb">⟨/⟩ SIET-LMS Admin</a>
  <div class="nl">
    <a href="dashboard.php">← Dashboard</a>
    <a href="modules.php">Modules</a>
    <a href="../practice.php" target="_blank">View Site</a>
    <a href="logout.php" class="red">Logout</a>
  </div>
</nav>
<div class="main">
<?php if($msg==='added'):?><div class="aok">✔ Exam created!</div><?php endif;?>
<?php if($msg==='deleted'):?><div class="aok">✔ Exam deleted.</div><?php endif;?>

<!-- Create Exam -->
<div class="card">
  <div class="ch"><h2>➕ Create New Exam</h2></div>
  <div class="cb">
    <div class="hb">💡 After creating an exam, click <strong>Edit</strong> to add MCQ and coding questions.</div>
    <form method="POST" action="save_exam.php">
      <div class="frow2">
        <div class="fg"><label>Title *</label><input type="text" name="title" class="fc" required></div>
        <div class="fg"><label>Subject</label>
          <select name="subject_id" class="fc"><option value="">— None —</option>
          <?php foreach($subjects as $s):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['subject_name'])?></option><?php endforeach;?>
          </select></div>
      </div>
      <div class="frow3">
        <div class="fg"><label>Duration (minutes)</label><input type="number" name="duration_min" class="fc" value="90" min="1"></div>
        <div class="fg"><label>Total Marks</label><input type="number" name="total_marks" class="fc" value="20" min="1"></div>
        <div class="fg"><label>Active</label>
          <select name="is_active" class="fc"><option value="1">Yes</option><option value="0">No</option></select></div>
      </div>
      <div class="frow2">
        <div class="fg"><label>Entry Password <small>(leave blank = no password)</small></label><input type="text" name="entry_password" class="fc" placeholder="e.g. py123"></div>
        <div class="fg"><label>Exit Password</label><input type="text" name="exit_password" class="fc" value="quit2024"></div>
      </div>
      <div class="frow2">
        <div class="fg"><label>Start Time <small>(blank = always open)</small></label><input type="datetime-local" name="start_time" class="fc"></div>
        <div class="fg"><label>End Time <small>(blank = no deadline)</small></label><input type="datetime-local" name="end_time" class="fc"></div>
      </div>
      <button type="submit" class="btn bg">✓ Create Exam</button>
    </form>
  </div>
</div>

<!-- Exams list -->
<div class="card">
  <div class="ch"><h2>All Exams (<?=count($exams)?>)</h2></div>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>#</th><th>Title</th><th>Subject</th><th>Duration</th><th>Marks</th><th>Entry Pwd</th><th>Start / End</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($exams as $e):?>
    <tr>
      <td style="color:#94a3b8"><?=$e['id']?></td>
      <td><strong><?=htmlspecialchars($e['title'])?></strong></td>
      <td style="color:#64748b"><?=htmlspecialchars($e['subject_name']??'—')?></td>
      <td><?=$e['duration_min']?> min</td>
      <td><?=$e['total_marks']?></td>
      <td><code style="font-size:11px"><?=htmlspecialchars($e['entry_password']?:'(none)')?></code></td>
      <td style="font-size:11.5px;color:#64748b">
        <?=$e['start_time']?date('d M, h:i A',strtotime($e['start_time'])):'—'?><br>
        <?=$e['end_time']?date('d M, h:i A',strtotime($e['end_time'])):'—'?>
      </td>
      <td><span class="bdg <?=$e['is_active']?'bda':'bdi'?>"><?=$e['is_active']?'Active':'Inactive'?></span></td>
      <td style="white-space:nowrap;display:flex;gap:4px">
        <a href="edit_exam.php?id=<?=$e['id']?>" class="btn bb">Edit</a>
        <a href="exam_results_admin.php?eid=<?=$e['id']?>" class="btn bgr">Results</a>
        <a href="delete_exam.php?id=<?=$e['id']?>" class="btn br" onclick="return confirm('Delete this exam?')">Delete</a>
      </td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($exams)):?><tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8">No exams yet.</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>
</div>
</body></html>
