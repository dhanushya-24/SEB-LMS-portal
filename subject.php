<?php
include "config.php";
requireLogin();
requireSEB();      // Must run inside SEB
checkExamLock();
$student = currentStudent();
$uid = $student['id'];
$sid = intval($_GET['id'] ?? 0);
$subj = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM subjects WHERE id=$sid LIMIT 1"));
if (!$subj) { header("Location: practice.php"); exit; }

$modRes  = mysqli_query($conn,"SELECT * FROM modules WHERE subject_id=$sid ORDER BY order_num ASC");
$modules = [];
while ($m = mysqli_fetch_assoc($modRes)) $modules[] = $m;

foreach ($modules as &$m) {
    $mid = $m['id'];
    $qRes = mysqli_query($conn,"SELECT q.id,q.title,q.question,COALESCE(up.status,'not_started') AS status,mq.order_num AS qorder FROM module_questions mq JOIN questions q ON mq.question_id=q.id LEFT JOIN user_progress up ON up.question_id=q.id AND up.user_id=$uid WHERE mq.module_id=$mid ORDER BY mq.order_num ASC");
    $m['questions'] = [];
    while ($r = mysqli_fetch_assoc($qRes)) $m['questions'][] = $r;
    $total  = count($m['questions']);
    $solved = count(array_filter($m['questions'], fn($q) => $q['status']==='solved'));
    $m['total']=$total; $m['solved']=$solved;
    $m['percent']=$total>0?round($solved/$total*100):0;
    $m['all_solved']=($total>0&&$solved===$total);
    $mp = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM module_progress WHERE user_id=$uid AND module_id=$mid LIMIT 1"));
    $m['progress']=$mp; $m['db_status']=$mp['status']??'locked';
}
unset($m);

foreach ($modules as $i => &$m) {
    $mid=$m['id'];
    if ($i===0) {
        mysqli_query($conn,"INSERT INTO module_progress (user_id,module_id,status) VALUES ($uid,$mid,'unlocked') ON DUPLICATE KEY UPDATE status=IF(status='locked','unlocked',status)");
        $m['db_status']=$m['all_solved']?'completed':'unlocked';
        if($m['all_solved']) mysqli_query($conn,"UPDATE module_progress SET status='completed' WHERE user_id=$uid AND module_id=$mid");
    } else {
        if($modules[$i-1]['all_solved']) {
            mysqli_query($conn,"INSERT INTO module_progress (user_id,module_id,status) VALUES ($uid,$mid,'unlocked') ON DUPLICATE KEY UPDATE status=IF(status='locked','unlocked',status)");
            $mp2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM module_progress WHERE user_id=$uid AND module_id=$mid LIMIT 1"));
            $m['db_status']=$mp2['status']??'unlocked'; $m['progress']=$mp2;
            if($m['all_solved']){mysqli_query($conn,"UPDATE module_progress SET status='completed' WHERE user_id=$uid AND module_id=$mid");$m['db_status']='completed';}
        } else {
            $m['db_status']='locked';
        }
    }
}
unset($m);
$allTotal=array_sum(array_column($modules,'total'));
$allSolved=array_sum(array_column($modules,'solved'));
$subPct=$allTotal>0?round($allSolved/$allTotal*100):0;

$icons=['Java'=>'☕','Python'=>'🐍','C Programming'=>'⚙️','Data Structures'=>'🏗️','Advanced DSA'=>'🚀','Database'=>'🗄️','Design'=>'📐','Placement'=>'🎯'];
$sicon='📚';
foreach($icons as $k=>$v){if(stripos($subj['subject_name'],$k)!==false){$sicon=$v;break;}}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($subj['subject_name'])?> — SIET-LMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}body{font-family:'Poppins',sans-serif;background:#f8f9fa;color:#1a1a2e;min-height:100vh;}
.header{background:#fff;padding:14px 32px;border-bottom:1px solid #e9ecef;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.header-brand{font-size:17px;font-weight:800;color:#1a1a2e;}
.header-right{display:flex;align-items:center;gap:8px;}
.header-right a{font-size:13px;font-weight:500;color:#6c757d;text-decoration:none;padding:6px 14px;border-radius:7px;transition:.15s;}
.header-right a:hover{background:#f1f3f5;}
.page{max-width:920px;margin:0 auto;padding:32px 28px 60px;}
.bc{font-size:12.5px;color:#6b7280;margin-bottom:20px;}.bc a{color:#2563eb;text-decoration:none;}
.subject-banner{background:linear-gradient(135deg,#1e3a5f,#1d4ed8);border-radius:18px;padding:28px 32px;margin-bottom:32px;display:flex;align-items:center;gap:20px;}
.sb-icon{font-size:52px;width:80px;height:80px;background:rgba(255,255,255,.15);border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sb-info{flex:1;}
.sb-name{font-size:24px;font-weight:800;color:#fff;margin-bottom:4px;}
.sb-desc{font-size:13px;color:rgba(255,255,255,.75);margin-bottom:14px;}
.sb-pbar{background:rgba(255,255,255,.2);border-radius:99px;height:8px;overflow:hidden;margin-bottom:6px;}
.sb-pfill{height:100%;border-radius:99px;background:#22c55e;transition:width .6s;}
.sb-pct{font-size:12px;color:rgba(255,255,255,.8);font-weight:600;}
.modules{display:flex;flex-direction:column;gap:20px;}
.module-card{background:#fff;border-radius:16px;border:2px solid #e9ecef;overflow:hidden;transition:.2s;}
.module-card.unlocked{border-color:#e9ecef;}
.module-card.completed{border-color:#16a34a;background:linear-gradient(135deg,#f0fdf4,#fff);}
.module-card.locked{border-color:#e9ecef;opacity:.65;}
.mod-header{padding:20px 24px;display:flex;align-items:center;gap:14px;cursor:pointer;}
.mod-header:hover{background:#f8fafc;}
.mod-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.mod-unlocked .mod-icon{background:#dbeafe;}.mod-completed .mod-icon{background:#dcfce7;}.mod-locked .mod-icon{background:#f1f5f9;}
.mod-info{flex:1;}
.mod-title{font-size:15px;font-weight:800;color:#1a1a2e;margin-bottom:3px;}
.mod-desc{font-size:12px;color:#6c757d;margin-bottom:8px;}
.mod-progress{display:flex;align-items:center;gap:12px;}
.mod-pbar{flex:1;height:6px;background:#e5e7eb;border-radius:99px;overflow:hidden;}
.mod-pfill{height:100%;border-radius:99px;background:#16a34a;transition:width .6s;}
.mod-pct{font-size:11.5px;font-weight:700;color:#374151;white-space:nowrap;}
.mod-status{flex-shrink:0;}.mod-badge{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;padding:4px 12px;border-radius:20px;}
.badge-unlocked{background:#dbeafe;color:#1e40af;}.badge-completed{background:#dcfce7;color:#15803d;}.badge-locked{background:#f1f5f9;color:#94a3b8;}
.mod-chevron{font-size:18px;color:#94a3b8;transition:.3s;}
.mod-chevron.open{transform:rotate(180deg);}
.mod-body{border-top:1px solid #f1f3f5;padding:0;max-height:0;overflow:hidden;transition:max-height .4s ease,padding .3s;}
.mod-body.open{max-height:600px;padding:16px 24px 20px;}
.questions-list{display:flex;flex-direction:column;gap:8px;}
.q-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #f1f3f5;text-decoration:none;color:inherit;transition:.15s;}
.q-item:hover{background:#f8fafc;border-color:#dbeafe;text-decoration:none;color:inherit;}
.q-item.solved{background:#f0fdf4;border-color:#bbf7d0;}
.q-item.attempted{background:#fffbeb;border-color:#fde68a;}
.q-num{font-size:12px;font-weight:800;width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.q-num-solved{background:#16a34a;color:#fff;}.q-num-attempted{background:#f59e0b;color:#fff;}.q-num-not{background:#e5e7eb;color:#6b7280;}
.q-title{flex:1;font-size:13.5px;font-weight:600;color:#1a1a2e;}
.q-status-icon{font-size:16px;flex-shrink:0;}
.lock-msg{text-align:center;padding:20px;color:#94a3b8;font-size:13px;}
.lock-msg strong{color:#374151;}
</style>
</head>
<body>
<div class="header">
  <span class="header-brand">📚 SIET-LMS</span>
  <div class="header-right">
    <a href="practice.php">← Subjects</a>
    <a href="practice.php">Home</a>
  </div>
</div>
<div class="page">
  <div class="bc"><a href="practice.php">Home</a> › <a href="practice.php">Practice</a> › <span><?=htmlspecialchars($subj['subject_name'])?></span></div>
  <div class="subject-banner">
    <div class="sb-icon"><?=$sicon?></div>
    <div class="sb-info">
      <div class="sb-name"><?=htmlspecialchars($subj['subject_name'])?></div>
      <div class="sb-desc"><?=htmlspecialchars($subj['description']??'')?> · <?=count($modules)?> modules · <?=$allTotal?> problems</div>
      <div class="sb-pbar"><div class="sb-pfill" style="width:<?=$subPct?>%"></div></div>
      <div class="sb-pct"><?=$allSolved?>/<?=$allTotal?> solved — <?=$subPct?>% complete</div>
    </div>
  </div>

  <div class="modules">
  <?php foreach($modules as $mi=>$m):
    $status=$m['db_status'];
    $cardClass=$status==='completed'?'completed':($status==='unlocked'?'unlocked':'locked');
    $modHeaderClass='mod-'.$cardClass;
    $icon=$status==='completed'?'✅':($status==='unlocked'?'📖':'🔒');
    $badge=['completed'=>'<span class="mod-badge badge-completed">✓ Completed</span>','unlocked'=>'<span class="mod-badge badge-unlocked">▶ Active</span>','locked'=>'<span class="mod-badge badge-locked">🔒 Locked</span>'];
  ?>
  <div class="module-card <?=$cardClass?>" id="mod-<?=$m['id']?>">
    <div class="mod-header <?=$modHeaderClass?>" onclick="toggleModule('<?=$m['id']?>')">
      <div class="mod-icon"><span style="font-size:20px"><?=$icon?></span></div>
      <div class="mod-info">
        <div class="mod-title"><?=htmlspecialchars($m['title'])?></div>
        <div class="mod-desc"><?=htmlspecialchars($m['description']??'')?></div>
        <div class="mod-progress">
          <div class="mod-pbar"><div class="mod-pfill" style="width:<?=$m['percent']?>%;background:<?=$m['percent']>=100?'#16a34a':($m['percent']>=50?'#f59e0b':'#3b82f6')?>"></div></div>
          <span class="mod-pct"><?=$m['solved']?>/<?=$m['total']?> (<?=$m['percent']?>%)</span>
        </div>
      </div>
      <div class="mod-status"><?=$badge[$status]?></div>
      <div class="mod-chevron <?=$status!=='locked'?'open':''?>" id="chev-<?=$m['id']?>">▾</div>
    </div>
    <div class="mod-body <?=$status!=='locked'&&($mi===0||$modules[$mi-1]['all_solved']||$status==='completed')?' open':''?>" id="body-<?=$m['id']?>">
      <?php if($status==='locked'):?>
      <div class="lock-msg">🔒 <strong>Locked</strong> — Complete all problems in the previous module to unlock this one.</div>
      <?php else:?>
      <div class="questions-list">
        <?php foreach($m['questions'] as $qi=>$q):
          $st=$q['status'];
          $itemClass=$st==='solved'?'solved':($st==='attempted'?'attempted':'');
          $numClass=$st==='solved'?'q-num-solved':($st==='attempted'?'q-num-attempted':'q-num-not');
          $statusIcon=$st==='solved'?'✅':($st==='attempted'?'🔄':'○');
        ?>
        <a href="module.php?mid=<?=$m['id']?>&qid=<?=$q['id']?>&sid=<?=$sid?>" class="q-item <?=$itemClass?>">
          <div class="q-num <?=$numClass?>"><?=($qi+1)?></div>
          <div class="q-title"><?=htmlspecialchars($q['title'])?></div>
          <div class="q-status-icon"><?=$statusIcon?></div>
        </a>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
  </div>
  <?php endforeach;?>
  </div>
</div>
<script>
function toggleModule(mid){
  var body=document.getElementById('body-'+mid);
  var chev=document.getElementById('chev-'+mid);
  if(body){body.classList.toggle('open'); if(chev)chev.classList.toggle('open');}
}
</script>
</body>
</html>
