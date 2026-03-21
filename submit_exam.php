<?php
include "config.php";
requireLogin();
header("Content-Type: application/json");
$attemptId = intval($_POST['attempt_id'] ?? 0);
$eid       = intval($_POST['eid']        ?? 0);
$uid       = currentStudent()['id'];
if (!$attemptId || !$eid) { echo json_encode(['ok'=>false]); exit; }
$at = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM exam_attempts WHERE id=$attemptId AND student_id=$uid LIMIT 1"));
if (!$at) { echo json_encode(['ok'=>false]); exit; }
require_once 'exam_submit_handler.php';
doSubmitExam($conn, $attemptId, $eid, $uid);
echo json_encode(['ok'=>true]);
