<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");

$id = intval($_GET['id']??0);
$exam = $conn->query("SELECT * FROM exams WHERE id=$id LIMIT 1")->fetch_assoc();
if(!$exam){header("Location: exams.php");exit;}

$ok=$err='';

// Handle updates
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=trim($_POST['action']??'');

    if($action==='update_exam'){
        $sid   = intval($_POST['subject_id']??0) ?: 'NULL';
        $title = $conn->real_escape_string(trim($_POST['title']??''));
        $dur   = intval($_POST['duration_min']??90);
        $tot   = intval($_POST['total_marks']??20);
        $ep    = $conn->real_escape_string(trim($_POST['entry_password']??''));
        $xp    = $conn->real_escape_string(trim($_POST['exit_password']??'quit2024'));
        $act   = intval($_POST['is_active']??1);
        $st    = trim($_POST['start_time']??'');$et=trim($_POST['end_time']??'');
        $stQ=$st?"'$st'":'NULL';$etQ=$et?"'$et'":'NULL';
        $conn->query("UPDATE exams SET subject_id=$sid,title='$title',duration_min=$dur,total_marks=$tot,is_active=$act,entry_password='$ep',exit_password='$xp',start_time=$stQ,end_time=$etQ WHERE id=$id");
        $ok='Exam updated!';$exam=$conn->query("SELECT * FROM exams WHERE id=$id LIMIT 1")->fetch_assoc();
    }
    elseif($action==='add_mcq'){
        $q=$conn->real_escape_string(trim($_POST['question']??''));
        $a=$conn->real_escape_string(trim($_POST['opt_a']??''));$b=$conn->real_escape_string(trim($_POST['opt_b']??''));
        $c=$conn->real_escape_string(trim($_POST['opt_c']??''));$d=$conn->real_escape_string(trim($_POST['opt_d']??''));
        $ans=strtoupper(trim($_POST['answer']??'A'));$marks=intval($_POST['marks']??1);
        $ord=(int)$conn->query("SELECT COUNT(*)+1 FROM exam_mcq_questions WHERE exam_id=$id")->fetch_row()[0];
        if($q&&$a&&$b&&$c&&$d&&in_array($ans,['A','B','C','D'])){
            $conn->query("INSERT INTO exam_mcq_questions(exam_id,question,opt_a,opt_b,opt_c,opt_d,answer,marks,order_num) VALUES($id,'$q','$a','$b','$c','$d','$ans',$marks,$ord)");
            $ok='MCQ question added!';
        } else {$err='Fill all MCQ fields.';}
    }
    elseif($action==='delete_mcq'){
        $mid=intval($_POST['mcq_id']??0);
        $conn->query("DELETE FROM exam_mcq_questions WHERE id=$mid AND exam_id=$id");$ok='MCQ deleted.';
    }
    elseif($action==='add_coding'){
        $qid=intval($_POST['question_id']??0);$part=trim($_POST['part']??'B');$marks=intval($_POST['marks']??7);
        $ord=(int)$conn->query("SELECT COUNT(*)+1 FROM exam_coding_questions WHERE exam_id=$id")->fetch_row()[0];
        if($qid){
            $conn->query("INSERT IGNORE INTO exam_coding_questions(exam_id,question_id,part,marks,order_num) VALUES($id,$qid,'$part',$marks,$ord)");$ok='Coding question added!';
        } else {$err='Select a question.';}
    }
    elseif($action==='delete_coding'){
        $cid=intval($_POST['coding_id']??0);
        $conn->query("DELETE FROM exam_coding_questions WHERE id=$cid AND exam_id=$id");$ok='Coding question removed.';
    }
}

$mcqQs   = $conn->query("SELECT * FROM exam_mcq_questions WHERE exam_id=$id ORDER BY order_num ASC")->fetch_all(MYSQLI_ASSOC);
$codeQs  = $conn->query("SELECT ecq.*,q.title FROM exam_coding_questions ecq JOIN questions q ON ecq.question_id=q.id WHERE ecq.exam_id=$id ORDER BY ecq.part ASC,ecq.order_num ASC")->fetch_all(MYSQLI_ASSOC);
$allQs   = $conn->query("SELECT q.*,s.subject_name FROM questions q LEFT JOIN subjects s ON q.subject_id=s.id ORDER BY q.subject_id,q.id")->fetch_all(MYSQLI_ASSOC);
$subjects= $conn->query("SELECT * FROM subjects ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Edit Exam — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#f4f6f9;color:#0f172a;}
.nav{background:#1e293b;height:50px;padding:0 22px;display:flex;align-items:center;justify-content:space-between;}
.nb{color:#818cf8;font-weight:700;}.nav a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 11px;border-radius:6px;}.nav a:hover{background:#334155;color:#fff;}
.main{max-width:1000px;margin:22px auto;padding:0 18px;}
.aok{background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;color:#166534;font-size:13px;font-weight:600;margin-bottom:14px;}
.aerr{background:#fee2e2;border:1px solid #fecaca;border-radius:8px;padding:10px 16px;color:#991b1b;font-size:13px;font-weight:600;margin-bottom:14px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:18px;}
.ch{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;}
.ch h2{font-size:14px;font-weight:700;margin:0;}.cb{padding:18px;}
.frow2{display:grid;grid-template-columns:1fr 1fr;gap:13px;}
.frow3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:13px;}
.fg{margin-bottom:13px;}
.fg label{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin-bottom:4px;}
.fc{width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:6px;font-size:12.5px;font-family:inherit;outline:none;transition:.15s;}
.fc:focus{border-color:#16a34a;}
.btn{display:inline-flex;align-items:center;padding:6px 14px;border-radius:6px;font-size:12px;font-weight:700;font-family:inherit;cursor:pointer;border:none;text-decoration:none;transition:.13s;}
.bg{background:#16a34a;color:#fff;}.bg:hover{background:#15803d;}
.bb{background:#2563eb;color:#fff;}.bb:hover{background:#1d4ed8;}
.br{background:#dc2626;color:#fff;}.br:hover{background:#b91c1c;}
.bgr{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}.bgr:hover{background:#e2e8f0;}
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{background:#f1f5f9;padding:8px 12px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:2px solid #e5e7eb;}
td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
</style></head><body>
<div class="nav"><span class="nb">⟨/⟩ Admin</span><div><a href="exams.php">← Exams</a><a href="dashboard.php">Dashboard</a></div></div>
<div class="main">
<?php if($ok):?><div class="aok">✔ <?=htmlspecialchars($ok)?></div><?php endif;?>
<?php if($err):?><div class="aerr">⚠ <?=htmlspecialchars($err)?></div><?php endif;?>

<!-- Edit Exam Details -->
<div class="card">
  <div class="ch"><h2>Edit Exam: <?=htmlspecialchars($exam['title'])?></h2></div>
  <div class="cb">
    <form method="POST">
    <input type="hidden" name="action" value="update_exam">
    <div class="frow2">
      <div class="fg"><label>Title *</label><input type="text" name="title" class="fc" value="<?=htmlspecialchars($exam['title'])?>" required></div>
      <div class="fg"><label>Subject</label>
        <select name="subject_id" class="fc"><option value="">— None —</option>
        <?php foreach($subjects as $s):?><option value="<?=$s['id']?>" <?=$exam['subject_id']==$s['id']?'selected':''?>><?=htmlspecialchars($s['subject_name'])?></option><?php endforeach;?>
        </select></div>
    </div>
    <div class="frow3">
      <div class="fg"><label>Duration (min)</label><input type="number" name="duration_min" class="fc" value="<?=$exam['duration_min']?>"></div>
      <div class="fg"><label>Total Marks</label><input type="number" name="total_marks" class="fc" value="<?=$exam['total_marks']?>"></div>
      <div class="fg"><label>Active</label><select name="is_active" class="fc"><option value="1" <?=$exam['is_active']?'selected':''?>>Yes</option><option value="0" <?=!$exam['is_active']?'selected':''?>>No</option></select></div>
    </div>
    <div class="frow2">
      <div class="fg"><label>Entry Password</label><input type="text" name="entry_password" class="fc" value="<?=htmlspecialchars($exam['entry_password']??'')?>"></div>
      <div class="fg"><label>Exit Password</label><input type="text" name="exit_password" class="fc" value="<?=htmlspecialchars($exam['exit_password']??'quit2024')?>"></div>
    </div>
    <div class="frow2">
      <div class="fg"><label>Start Time</label><input type="datetime-local" name="start_time" class="fc" value="<?=$exam['start_time']?date('Y-m-d\TH:i',strtotime($exam['start_time'])):''?>"></div>
      <div class="fg"><label>End Time</label><input type="datetime-local" name="end_time" class="fc" value="<?=$exam['end_time']?date('Y-m-d\TH:i',strtotime($exam['end_time'])):''?>"></div>
    </div>
    <button type="submit" class="btn bg">✓ Update Exam</button>
    </form>
  </div>
</div>

<!-- MCQ Questions (Part A) -->
<div class="card">
  <div class="ch"><h2>Part A — MCQ Questions (<?=count($mcqQs)?>)</h2></div>
  <div class="cb">
    <form method="POST" style="margin-bottom:16px">
    <input type="hidden" name="action" value="add_mcq">
    <div class="fg"><label>Question Text *</label><textarea name="question" class="fc" rows="2" required></textarea></div>
    <div class="frow2">
      <div class="fg"><label>Option A *</label><input type="text" name="opt_a" class="fc" required></div>
      <div class="fg"><label>Option B *</label><input type="text" name="opt_b" class="fc" required></div>
      <div class="fg"><label>Option C *</label><input type="text" name="opt_c" class="fc" required></div>
      <div class="fg"><label>Option D *</label><input type="text" name="opt_d" class="fc" required></div>
    </div>
    <div class="frow2">
      <div class="fg"><label>Correct Answer</label><select name="answer" class="fc"><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select></div>
      <div class="fg"><label>Marks</label><input type="number" name="marks" class="fc" value="1" min="1"></div>
    </div>
    <button type="submit" class="btn bb">+ Add MCQ</button>
    </form>
    <?php if(!empty($mcqQs)):?>
    <table><thead><tr><th>#</th><th>Question</th><th>Correct</th><th>Marks</th><th></th></tr></thead><tbody>
    <?php foreach($mcqQs as $i=>$mq):?>
    <tr><td><?=$i+1?></td><td style="font-size:12px;max-width:320px"><?=htmlspecialchars(mb_substr($mq['question'],0,80))?>…</td>
    <td><strong><?=$mq['answer']?>. <?=htmlspecialchars($mq['opt_'.strtolower($mq['answer'])])?></strong></td>
    <td><?=$mq['marks']?></td>
    <td><form method="POST" style="display:inline"><input type="hidden" name="action" value="delete_mcq"><input type="hidden" name="mcq_id" value="<?=$mq['id']?>"><button type="submit" class="btn br" style="padding:3px 10px;font-size:11px" onclick="return confirm('Delete?')">Del</button></form></td></tr>
    <?php endforeach;?>
    </tbody></table>
    <?php endif;?>
  </div>
</div>

<!-- Coding Questions (Part B) -->
<div class="card">
  <div class="ch"><h2>Part B — Coding Questions (<?=count($codeQs)?>)</h2></div>
  <div class="cb">
    <form method="POST" style="margin-bottom:16px">
    <input type="hidden" name="action" value="add_coding">
    <div class="frow3">
      <div class="fg"><label>Question *</label>
        <select name="question_id" class="fc" required><option value="">— Select —</option>
        <?php foreach($allQs as $q):?><option value="<?=$q['id']?>">[<?=htmlspecialchars($q['subject_name']??'')?>] <?=htmlspecialchars($q['title'])?></option><?php endforeach;?>
        </select></div>
      <div class="fg"><label>Part</label><select name="part" class="fc"><option value="B">B (Coding 7 marks)</option><option value="C">C (Long 10 marks)</option></select></div>
      <div class="fg"><label>Marks</label><input type="number" name="marks" class="fc" value="7" min="1"></div>
    </div>
    <button type="submit" class="btn bb">+ Add Coding Question</button>
    </form>
    <?php if(!empty($codeQs)):?>
    <table><thead><tr><th>#</th><th>Title</th><th>Part</th><th>Marks</th><th></th></tr></thead><tbody>
    <?php foreach($codeQs as $i=>$cq):?>
    <tr><td><?=$i+1?></td><td><?=htmlspecialchars($cq['title'])?></td><td>Part <?=$cq['part']?></td><td><?=$cq['marks']?></td>
    <td><form method="POST" style="display:inline"><input type="hidden" name="action" value="delete_coding"><input type="hidden" name="coding_id" value="<?=$cq['id']?>"><button type="submit" class="btn br" style="padding:3px 10px;font-size:11px" onclick="return confirm('Remove?')">Del</button></form></td></tr>
    <?php endforeach;?>
    </tbody></table>
    <?php endif;?>
  </div>
</div>

</div></body></html>
