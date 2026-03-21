<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");
$eid=intval($_GET['eid']??0);
$exam=$conn->query("SELECT * FROM exams WHERE id=$eid LIMIT 1")->fetch_assoc();
if(!$exam){header("Location: exams.php");exit;}

$results=$conn->query("
    SELECT ea.*,s.name,s.regno
    FROM exam_attempts ea
    JOIN students s ON ea.student_id=s.id
    WHERE ea.exam_id=$eid
    ORDER BY ea.total_score DESC, ea.finished_at ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Exam Results — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:'Poppins',sans-serif;}body{margin:0;background:#f4f6f9;color:#0f172a;}
.nav{background:#1e293b;height:50px;padding:0 22px;display:flex;align-items:center;justify-content:space-between;}
.nb{color:#818cf8;font-weight:700;}.nav a{color:#94a3b8;font-size:12.5px;text-decoration:none;padding:5px 11px;border-radius:6px;}.nav a:hover{background:#334155;color:#fff;}
.main{max-width:1000px;margin:22px auto;padding:0 18px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:18px;}
.ch{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;}
.ch h2{font-size:14px;font-weight:700;margin:0;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;}
.sc{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;text-align:center;}
.sv{font-size:1.9rem;font-weight:800;color:#16a34a;line-height:1;}.sl{font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-top:4px;}
table{width:100%;border-collapse:collapse;font-size:12.5px;}
th{background:#f1f5f9;padding:9px 13px;text-align:left;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b;border-bottom:2px solid #e5e7eb;}
td{padding:10px 13px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tr:hover td{background:#fafbfc;}tr:last-child td{border-bottom:none;}
.bdg{font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;text-transform:uppercase;}
.bs{background:#dcfce7;color:#15803d;}.bn{background:#fee2e2;color:#991b1b;}.bp{background:#f1f5f9;color:#9ca3af;}
.prog{display:inline-block;height:6px;background:#e5e7eb;border-radius:99px;overflow:hidden;width:80px;vertical-align:middle;margin-left:6px;}
.progf{height:100%;border-radius:99px;background:#16a34a;}
</style></head><body>
<div class="nav"><span class="nb">⟨/⟩ Admin</span><div><a href="exams.php">← Exams</a><a href="dashboard.php">Dashboard</a></div></div>
<div class="main">

<?php
$submitted = array_filter($results,fn($r)=>$r['submitted']);
$avgScore  = count($submitted)>0 ? round(array_sum(array_column($submitted,'total_score'))/count($submitted)) : 0;
$maxMark   = $exam['total_marks'] ?: ($results[0]['max_marks']??20);
$passed    = count(array_filter($submitted,fn($r)=>($r['total_score']/$maxMark)*100>=60));
?>
<div class="stats">
  <div class="sc"><div class="sv"><?=count($results)?></div><div class="sl">Attempted</div></div>
  <div class="sc"><div class="sv"><?=count($submitted)?></div><div class="sl">Submitted</div></div>
  <div class="sc"><div class="sv" style="color:#16a34a"><?=$passed?></div><div class="sl">Passed (≥60%)</div></div>
  <div class="sc"><div class="sv" style="color:#2563eb"><?=$avgScore?>/<small><?=$maxMark?></small></div><div class="sl">Avg Score</div></div>
</div>

<div class="card">
  <div class="ch"><h2>Results — <?=htmlspecialchars($exam['title'])?></h2>
    <span style="font-size:12px;color:#64748b"><?=count($results)?> attempts</span>
  </div>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>Rank</th><th>Student</th><th>Reg No</th><th>MCQ</th><th>Coding</th><th>Total</th><th>%</th><th>Duration</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach($results as $i=>$r):
      $pct=$maxMark>0?round($r['total_score']/$maxMark*100):0;
      $pass=$pct>=60;
      $dur=$r['started_at']&&$r['finished_at']?floor((strtotime($r['finished_at'])-strtotime($r['started_at']))/60).'m':'—';
    ?>
    <tr>
      <td style="color:#94a3b8;font-weight:700"><?=$i+1?></td>
      <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
      <td style="font-family:monospace;font-size:12px"><?=htmlspecialchars($r['regno'])?></td>
      <td><?=$r['score_mcq']?></td>
      <td><?=$r['score_code']?></td>
      <td><strong><?=$r['total_score']?>/<?=$maxMark?></strong></td>
      <td>
        <?=$pct?>%
        <div class="prog"><div class="progf" style="width:<?=$pct?>%;background:<?=$pct>=60?'#16a34a':'#dc2626'?>"></div></div>
      </td>
      <td style="color:#64748b;font-size:12px"><?=$dur?></td>
      <td>
        <?php if(!$r['submitted']):?>
          <span class="bdg bp">In Progress</span>
        <?php elseif($pass):?>
          <span class="bdg bs">Pass</span>
        <?php else:?>
          <span class="bdg bn">Fail</span>
        <?php endif;?>
      </td>
    </tr>
    <?php endforeach;?>
    <?php if(empty($results)):?><tr><td colspan="9" style="text-align:center;padding:28px;color:#94a3b8">No attempts yet.</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div>
</div>
</body></html>
