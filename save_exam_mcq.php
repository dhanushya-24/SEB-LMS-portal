<?php
include "config.php";
requireLogin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $attemptId = intval($_POST['attempt_id'] ?? 0);
    $mcqId     = intval($_POST['mcq_id'] ?? 0);
    $chosen    = strtoupper(substr(trim($_POST['chosen'] ?? ''),0,1));
    if ($attemptId && $mcqId && in_array($chosen,['A','B','C','D'])) {
        mysqli_query($conn,"INSERT INTO exam_mcq_answers (attempt_id,mcq_id,chosen) VALUES ($attemptId,$mcqId,'$chosen')
            ON DUPLICATE KEY UPDATE chosen='$chosen'");
    }
    echo json_encode(['ok'=>true]);
}
