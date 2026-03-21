<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");
$ok=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $n=trim($_POST['subject_name']??'');$d=trim($_POST['description']??'');
    if(!$n){$err='Name is required.';}
    else{$st=$conn->prepare("INSERT INTO subjects(subject_name,description)VALUES(?,?)");$st->bind_param("ss",$n,$d);$st->execute();$ok="Subject added!";}
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Add Subject</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#f4f6f9;}
.nav{background:#1e293b;height:50px;padding:0 22px;display:flex;align-items:center;justify-content:space-between;}
.nav-brand{color:#818cf8;font-weight:700;}.nav a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 11px;border-radius:6px;}.nav a:hover{background:#334155;color:#fff;}
.main{max-width:540px;margin:24px auto;padding:0 18px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
.ch{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e5e7eb;}.ch h2{font-size:14px;font-weight:700;margin:0;}
.cb{padding:18px;}
.fgroup{margin-bottom:13px;}.fgroup label{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:4px;}
.fc{width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:6px;font-size:12.5px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;}textarea.fc{resize:vertical;min-height:65px;}
.btn{display:inline-block;padding:7px 16px;border-radius:6px;font-size:12.5px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;}
.bgg{background:#16a34a;color:#fff;}.bg{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}
.al{background:#dcfce7;border:1px solid #bbf7d0;border-radius:7px;padding:9px 14px;color:#166534;font-size:12.5px;font-weight:600;margin-bottom:12px;}
.ae{background:#fee2e2;border:1px solid #fecaca;border-radius:7px;padding:9px 14px;color:#991b1b;font-size:12.5px;font-weight:600;margin-bottom:12px;}
</style></head><body>
<div class="nav"><span class="nav-brand">⟨/⟩ Admin</span><a href="dashboard.php">← Dashboard</a></div>
<div class="main"><div class="card">
  <div class="ch"><h2>➕ Add Subject</h2></div>
  <div class="cb">
    <?php if($ok):?><div class="al">✔ <?=htmlspecialchars($ok)?></div><?php endif;?>
    <?php if($err):?><div class="ae">⚠ <?=htmlspecialchars($err)?></div><?php endif;?>
    <form method="POST">
      <div class="fgroup"><label>Subject Name *</label><input type="text" name="subject_name" class="fc" required></div>
      <div class="fgroup"><label>Description</label><textarea name="description" class="fc"></textarea></div>
      <div style="display:flex;gap:8px"><button type="submit" class="btn bgg">✓ Add</button><a href="dashboard.php" class="btn bg">Cancel</a></div>
    </form>
  </div>
</div></div>
</body></html>
