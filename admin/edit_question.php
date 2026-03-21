<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");
$id=intval($_GET['id']??$_POST['id']??0);
$q=$conn->query("SELECT q.*,s.subject_name FROM questions q LEFT JOIN subjects s ON q.subject_id=s.id WHERE q.id=$id LIMIT 1")->fetch_assoc();
if(!$q){header("Location: dashboard.php");exit;}
$tc=$conn->query("SELECT * FROM testcases WHERE question_id=$id LIMIT 1")->fetch_assoc();
$subjects=$conn->query("SELECT * FROM subjects ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
if($_SERVER['REQUEST_METHOD']==='POST'){
    $sid=intval($_POST['subject_id']);$title=trim($_POST['title']);
    $quest=trim($_POST['question']);$tci=trim($_POST['tc_input']);$tce=trim($_POST['tc_expected']);
    $st=$conn->prepare("UPDATE questions SET subject_id=?,title=?,question=? WHERE id=?");
    $st->bind_param("issi",$sid,$title,$quest,$id);$st->execute();
    if($tc){$st2=$conn->prepare("UPDATE testcases SET input=?,expected_output=? WHERE question_id=? LIMIT 1");$st2->bind_param("ssi",$tci,$tce,$id);$st2->execute();}
    else{$st2=$conn->prepare("INSERT INTO testcases(question_id,input,expected_output)VALUES(?,?,?)");$st2->bind_param("iss",$id,$tci,$tce);$st2->execute();}
    header("Location: dashboard.php?msg=updated");exit;
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Edit Question</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#f4f6f9;}
.nav{background:#1e293b;height:50px;padding:0 22px;display:flex;align-items:center;justify-content:space-between;}
.nav-brand{color:#818cf8;font-weight:700;}.nav a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 11px;border-radius:6px;}
.nav a:hover{background:#334155;color:#fff;}
.main{max-width:780px;margin:24px auto;padding:0 18px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
.ch{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e5e7eb;}.ch h2{font-size:14px;font-weight:700;margin:0;}
.cb{padding:18px;}
.fgroup{margin-bottom:13px;}.fgroup label{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:4px;}
.fc{width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:6px;font-size:12.5px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;}textarea.fc{resize:vertical;font-family:'Courier New',monospace;font-size:12px;}
.fr2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.btn{display:inline-block;padding:7px 16px;border-radius:6px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;transition:.13s;}
.bgg{background:#16a34a;color:#fff;}.bgg:hover{background:#15803d;}.bg{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}
</style></head><body>
<div class="nav"><span class="nav-brand">⟨/⟩ Admin</span><a href="dashboard.php">← Dashboard</a></div>
<div class="main"><div class="card">
  <div class="ch"><h2>Edit Question #<?=$id?></h2></div>
  <div class="cb">
    <form method="POST">
      <input type="hidden" name="id" value="<?=$id?>">
      <div class="fr2">
        <div class="fgroup"><label>Subject</label>
          <select name="subject_id" class="fc">
            <?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=$q['subject_id']==$s['id']?'selected':''?>><?=htmlspecialchars($s['subject_name'])?></option><?php endforeach;?>
          </select></div>
        <div class="fgroup"><label>Title</label><input type="text" name="title" class="fc" value="<?=htmlspecialchars($q['title']??'')?>"></div>
      </div>
      <div class="fgroup"><label>Question</label><textarea name="question" class="fc" rows="5"><?=htmlspecialchars($q['question'])?></textarea></div>
      <div class="fr2">
        <div class="fgroup"><label>Sample Input</label><textarea name="tc_input" class="fc" rows="3"><?=htmlspecialchars($tc['input']??'')?></textarea></div>
        <div class="fgroup"><label>Expected Output</label><textarea name="tc_expected" class="fc" rows="3"><?=htmlspecialchars($tc['expected_output']??'')?></textarea></div>
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn bgg">✓ Update</button>
        <a href="dashboard.php" class="btn bg">Cancel</a>
      </div>
    </form>
  </div>
</div></div>
</body></html>
