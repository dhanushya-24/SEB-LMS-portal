<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn = new mysqli("localhost","root","","seb_lms");

$msg = '';

// Handle POST actions
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action = $_POST['action'] ?? '';

    if($action==='add_module'){
        $sid   = intval($_POST['subject_id']);
        $title = trim($_POST['title']);
        $desc  = trim($_POST['description']??'');
        $order = intval($_POST['order_num']??1);
        if($sid && $title){
            $st=$conn->prepare("INSERT INTO modules(subject_id,title,description,order_num)VALUES(?,?,?,?)");
            $st->bind_param("issi",$sid,$title,$desc,$order);
            $st->execute();
            $msg = "✔ Module added!";
        }
    }

    if($action==='assign_question'){
        $mid = intval($_POST['module_id']);
        $qid = intval($_POST['question_id']);
        $ord = intval($_POST['order_num']??1);
        if($mid && $qid){
            $conn->query("INSERT IGNORE INTO module_questions(module_id,question_id,order_num)VALUES($mid,$qid,$ord)");
            $msg = "✔ Question assigned to module!";
        }
    }

    if($action==='remove_mq'){
        $mid = intval($_POST['module_id']);
        $qid = intval($_POST['question_id']);
        $conn->query("DELETE FROM module_questions WHERE module_id=$mid AND question_id=$qid");
        $msg = "✔ Removed from module.";
    }

    if($action==='delete_module'){
        $mid = intval($_POST['module_id']);
        $conn->query("DELETE FROM modules WHERE id=$mid");
        $msg = "✔ Module deleted.";
    }
}

$subjects = $conn->query("SELECT * FROM subjects ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$modules  = $conn->query(
    "SELECT m.*,s.subject_name FROM modules m JOIN subjects s ON m.subject_id=s.id ORDER BY m.subject_id,m.order_num"
)->fetch_all(MYSQLI_ASSOC);

// Questions not yet in any module
$allQs = $conn->query(
    "SELECT q.*,s.subject_name FROM questions q JOIN subjects s ON q.subject_id=s.id ORDER BY q.subject_id,q.id"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Manage Modules</title>
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
.fgroup{margin-bottom:13px;}
.fgroup label{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:4px;}
.fc{width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:12.5px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:7px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;transition:.13s;}
.bgg{background:#16a34a;color:#fff;}.bgg:hover{background:#15803d;}
.brr{background:#dc2626;color:#fff;}.brr:hover{background:#b91c1c;}
.bgr{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{background:#f1f5f9;padding:8px 12px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:2px solid #e5e7eb;}
td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:hover td{background:#fafbfc;}
tr:last-child td{border-bottom:none;}
</style></head><body>
<nav class="nav">
  <a href="dashboard.php" class="nav-brand">⟨/⟩ Admin</a>
  <a href="dashboard.php">← Dashboard</a>
</nav>
<div class="main">
  <?php if($msg):?><div class="alert-ok"><?=htmlspecialchars($msg)?></div><?php endif;?>

  <!-- Add Module -->
  <div class="card">
    <div class="ch"><h2>➕ Add Module</h2></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="add_module">
        <div class="frow2">
          <div class="fgroup"><label>Subject *</label>
            <select name="subject_id" class="fc" required>
              <option value="">— Select —</option>
              <?php foreach($subjects as $s):?><option value="<?=$s['id']?>"><?=htmlspecialchars($s['subject_name'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Module Title *</label>
            <input type="text" name="title" class="fc" placeholder="e.g. Module 1 — Basics" required></div>
        </div>
        <div class="frow2">
          <div class="fgroup"><label>Description</label>
            <input type="text" name="description" class="fc" placeholder="Brief description"></div>
          <div class="fgroup"><label>Order Number</label>
            <input type="number" name="order_num" class="fc" value="1" min="1"></div>
        </div>
        <button type="submit" class="btn bgg">✓ Add Module</button>
      </form>
    </div>
  </div>

  <!-- Assign Question to Module -->
  <div class="card">
    <div class="ch"><h2>🔗 Assign Question to Module</h2></div>
    <div class="cb">
      <form method="POST">
        <input type="hidden" name="action" value="assign_question">
        <div class="frow2">
          <div class="fgroup"><label>Module *</label>
            <select name="module_id" class="fc" required>
              <option value="">— Select —</option>
              <?php foreach($modules as $m):?><option value="<?=$m['id']?>">[<?=htmlspecialchars($m['subject_name'])?>] <?=htmlspecialchars($m['title'])?></option><?php endforeach;?>
            </select></div>
          <div class="fgroup"><label>Question *</label>
            <select name="question_id" class="fc" required>
              <option value="">— Select —</option>
              <?php foreach($allQs as $q):?><option value="<?=$q['id']?>">[<?=htmlspecialchars($q['subject_name'])?>] <?=htmlspecialchars($q['title'])?></option><?php endforeach;?>
            </select></div>
        </div>
        <div class="fgroup" style="max-width:200px"><label>Order in Module</label>
          <input type="number" name="order_num" class="fc" value="1" min="1"></div>
        <button type="submit" class="btn bgg">✓ Assign</button>
      </form>
    </div>
  </div>

  <!-- All Modules -->
  <div class="card">
    <div class="ch"><h2>All Modules (<?=count($modules)?>)</h2></div>
    <div style="overflow-x:auto"><table>
      <thead><tr><th>#</th><th>Subject</th><th>Title</th><th>Order</th><th>Questions</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($modules as $m):
        $qcount = $conn->query("SELECT COUNT(*) FROM module_questions WHERE module_id={$m['id']}")->fetch_row()[0];
      ?>
      <tr>
        <td style="color:#94a3b8"><?=$m['id']?></td>
        <td style="color:#64748b"><?=htmlspecialchars($m['subject_name'])?></td>
        <td><strong><?=htmlspecialchars($m['title'])?></strong><br><span style="font-size:11px;color:#94a3b8"><?=htmlspecialchars($m['description']??'')?></span></td>
        <td><?=$m['order_num']?></td>
        <td><?=$qcount?> questions</td>
        <td>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this module?')">
            <input type="hidden" name="action" value="delete_module">
            <input type="hidden" name="module_id" value="<?=$m['id']?>">
            <button type="submit" class="btn brr">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table></div>
  </div>

</div></body></html>
