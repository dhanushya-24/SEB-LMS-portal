<?php
include "config.php";
requireLogin();
header("Content-Type: application/json");
$aid   = intval($_POST['attempt_id'] ?? 0);
$mid   = intval($_POST['mcq_id']     ?? 0);
$chosen= strtoupper(trim($_POST['chosen'] ?? ''));
if (!$aid || !$mid || !in_array($chosen,['A','B','C','D'])) {
    echo json_encode(['ok'=>false]); exit;
}
// Verify attempt belongs to this student
$at = mysqli_fetch_assoc(mysqli_query($conn,"SELECT student_id FROM exam_attempts WHERE id=$aid LIMIT 1"));
if (!$at || $at['student_id'] != ($_SESSION['student_id']??0)) {
    echo json_encode(['ok'=>false]); exit;
}
// Upsert
mysqli_query($conn,
    "INSERT INTO exam_mcq_answers (attempt_id,mcq_id,chosen)
     VALUES ($aid,$mid,'$chosen')
     ON DUPLICATE KEY UPDATE chosen='$chosen'");
echo json_encode(['ok'=>true]);
