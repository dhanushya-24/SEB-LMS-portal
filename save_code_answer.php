<?php
include "config.php";
requireLogin();
header("Content-Type: application/json");

$attemptId = intval($_POST['attempt_id'] ?? 0);
$ecqId     = intval($_POST['ecq_id']     ?? 0);
$code      = $_POST['code']              ?? '';
$lang      = strtolower(trim($_POST['language'] ?? 'python'));
$passed    = intval($_POST['passed']     ?? 0);

if (!$attemptId || !$ecqId) { echo json_encode(['ok'=>false]); exit; }

$uid = currentStudent()['id'];
$att = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM exam_attempts WHERE id=$attemptId AND student_id=$uid AND submitted=0 LIMIT 1"));
if (!$att) { echo json_encode(['ok'=>false]); exit; }

$ecq = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT marks FROM exam_coding_questions WHERE id=$ecqId LIMIT 1"));
$score = $passed ? ($ecq['marks'] ?? 7) : 0;

$safeCode = mysqli_real_escape_string($conn, $code);
mysqli_query($conn,
    "INSERT INTO exam_code_answers (attempt_id,ecq_id,language,submitted_code,passed,score)
     VALUES ($attemptId,$ecqId,'$lang','$safeCode',$passed,$score)
     ON DUPLICATE KEY UPDATE language='$lang', submitted_code='$safeCode', passed=$passed, score=$score");

echo json_encode(['ok'=>true,'passed'=>(bool)$passed,'score'=>$score]);
