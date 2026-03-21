<?php
include "config.php";
requireLogin();
header("Content-Type: application/json");

$student = currentStudent();
$uid = $student['id'];
$mid = intval($_POST['mid'] ?? 0);
if (!$mid) { echo json_encode(['completed'=>false]); exit; }

// Count total questions in module
$total = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM module_questions WHERE module_id=$mid"))[0];

// Count solved by student in this module
$solved = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM user_progress up
     JOIN module_questions mq ON up.question_id=mq.question_id
     WHERE mq.module_id=$mid AND up.user_id=$uid AND up.status='solved'"))[0];

if ($total > 0 && $solved === $total) {
    // Get or update module_progress
    $mp = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM module_progress WHERE user_id=$uid AND module_id=$mid LIMIT 1"));

    $timeSec = 0;
    if ($mp && $mp['start_time']) {
        $timeSec = time() - strtotime($mp['start_time']);
    }
    $marks = $solved * 10;

    mysqli_query($conn,
        "UPDATE module_progress
         SET status='completed', end_time=NOW(),
             time_taken_sec=$timeSec, marks_obtained=$marks, total_marks=".($total*10)."
         WHERE user_id=$uid AND module_id=$mid");

    $mp2 = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM module_progress WHERE user_id=$uid AND module_id=$mid LIMIT 1"));

    echo json_encode([
        'completed'   => true,
        'marks'       => $marks,
        'total_marks' => $total * 10,
        'time_sec'    => $timeSec,
        'end_time'    => date('d M Y, h:i A'),
    ]);
} else {
    echo json_encode(['completed'=>false,'solved'=>$solved,'total'=>$total]);
}
