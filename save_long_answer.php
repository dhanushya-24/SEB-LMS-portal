<?php
include "config.php";
requireLogin();
header('Content-Type: application/json');
$attemptId = (int)($_POST['attempt_id'] ?? 0);
$lqId = (int)($_POST['lq_id'] ?? 0);
$answer = trim($_POST['answer'] ?? '');
if (!$attemptId || !$lqId) { echo json_encode(['ok'=>false,'error'=>'Missing params']); exit; }
// Verify attempt belongs to student
$uid = $_SESSION['student_id'];
$at = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM exam_attempts WHERE id=$attemptId AND student_id=$uid AND submitted=0 LIMIT 1"));
if (!$at) { echo json_encode(['ok'=>false,'error'=>'Invalid attempt']); exit; }
$ans = mysqli_real_escape_string($conn, $answer);
mysqli_query($conn, "INSERT INTO exam_long_answers (attempt_id,lq_id,answer_text) VALUES ($attemptId,$lqId,'$ans')
  ON DUPLICATE KEY UPDATE answer_text='$ans'");
echo json_encode(['ok'=>true]);
