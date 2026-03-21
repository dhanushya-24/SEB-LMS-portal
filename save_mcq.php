<?php
include "config.php";
requireLogin();
header("Content-Type: application/json");

$attemptId = intval($_POST['attempt_id'] ?? 0);
$mcqId     = intval($_POST['mcq_id']     ?? 0);
$chosen    = strtoupper(trim($_POST['chosen'] ?? ''));

if (!$attemptId || !$mcqId || !in_array($chosen, ['A','B','C','D'])) {
    echo json_encode(['ok'=>false]); exit;
}

// Verify attempt belongs to this student
$uid = currentStudent()['id'];
$att = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM exam_attempts WHERE id=$attemptId AND student_id=$uid AND submitted=0 LIMIT 1"));
if (!$att) { echo json_encode(['ok'=>false,'err'=>'Invalid attempt']); exit; }

// Get correct answer
$mcq = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT answer FROM exam_mcq_questions WHERE id=$mcqId LIMIT 1"));
$isCorrect = ($mcq && $mcq['answer'] === $chosen) ? 1 : 0;

mysqli_query($conn,
    "INSERT INTO exam_mcq_answers (attempt_id,mcq_id,chosen,is_correct)
     VALUES ($attemptId,$mcqId,'$chosen',$isCorrect)
     ON DUPLICATE KEY UPDATE chosen='$chosen', is_correct=$isCorrect");

echo json_encode(['ok'=>true,'correct'=>(bool)$isCorrect]);
