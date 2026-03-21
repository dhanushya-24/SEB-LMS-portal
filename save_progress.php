<?php
include "config.php";
requireLogin();
header("Content-Type: application/json");

$student = currentStudent();
$qid     = intval($_POST['qid']    ?? 0);
$code    = $_POST['code']           ?? '';
$solved  = intval($_POST['solved']  ?? 0);

if (!$qid || empty($code)) {
    echo json_encode(['ok' => false]);
    exit;
}

$status = $solved ? 'solved' : 'attempted';
$score  = $solved ? 10 : 0;
$uid    = $student['id'];
$safeCode = mysqli_real_escape_string($conn, $code);

// Upsert — don't downgrade from solved to attempted
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT status FROM user_progress WHERE user_id=$uid AND question_id=$qid LIMIT 1"));

if ($existing) {
    // Don't overwrite 'solved' with 'attempted'
    if ($existing['status'] === 'solved' && $status === 'attempted') {
        $status = 'solved';
        $score  = 10;
    }
    mysqli_query($conn,
        "UPDATE user_progress
         SET status='$status', submitted_code='$safeCode', score=$score
         WHERE user_id=$uid AND question_id=$qid");
} else {
    mysqli_query($conn,
        "INSERT INTO user_progress (user_id, question_id, status, submitted_code, score)
         VALUES ($uid, $qid, '$status', '$safeCode', $score)");
}

echo json_encode(['ok' => true, 'status' => $status]);
