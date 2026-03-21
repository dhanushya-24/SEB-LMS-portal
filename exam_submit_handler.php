<?php
include "config.php";
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['confirmed'])) {
    header("Location: launch_exam.php"); exit;
}
$eid = (int)($_POST['eid'] ?? 0);
$attemptId = (int)($_POST['attempt_id'] ?? 0);
$uid = $_SESSION['student_id'];
if (!$eid || !$attemptId) { header("Location: launch_exam.php"); exit; }

// Verify
$attempt = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM exam_attempts WHERE id=$attemptId AND student_id=$uid AND exam_id=$eid LIMIT 1"));
if (!$attempt || $attempt['submitted']) {
    header("Location: exam_result.php?eid=$eid"); exit;
}

// Score Part A: MCQ
$scoreMcq = 0;
$mcqs = [];
$res = mysqli_query($conn, "SELECT id, answer, marks FROM exam_mcq_questions WHERE exam_id=$eid");
while ($r = mysqli_fetch_assoc($res)) $mcqs[] = $r;
foreach ($mcqs as $m) {
    $ans = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT chosen FROM exam_mcq_answers WHERE attempt_id=$attemptId AND mcq_id={$m['id']} LIMIT 1"));
    if ($ans && $ans['chosen'] === $m['answer']) {
        $scoreMcq += (int)$m['marks'];
        mysqli_query($conn, "UPDATE exam_mcq_answers SET is_correct=1 WHERE attempt_id=$attemptId AND mcq_id={$m['id']}");
    }
}

// Score Part B: Coding — full marks if code was submitted (manual check placeholder)
$scoreCode = 0;
$res = mysqli_query($conn, "SELECT id, marks FROM exam_coding_questions WHERE exam_id=$eid AND part='B'");
while ($cq = mysqli_fetch_assoc($res)) {
    $ca = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT submitted_code, score FROM exam_code_answers WHERE attempt_id=$attemptId AND ecq_id={$cq['id']} LIMIT 1"));
    if ($ca && trim($ca['submitted_code'])) {
        // Award marks based on whether code exists (teacher reviews later; give 5/7 as default)
        $given = $ca['score'] > 0 ? $ca['score'] : 5;
        $scoreCode += $given;
        mysqli_query($conn, "UPDATE exam_code_answers SET score=$given WHERE attempt_id=$attemptId AND ecq_id={$cq['id']}");
    }
}

// Score Part C: Long — manual check; give 7/10 if answered
$scoreLong = 0;
$res = mysqli_query($conn, "SELECT id, marks FROM exam_long_questions WHERE exam_id=$eid");
while ($lq = mysqli_fetch_assoc($res)) {
    $la = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT answer_text, score FROM exam_long_answers WHERE attempt_id=$attemptId AND lq_id={$lq['id']} LIMIT 1"));
    if ($la && trim($la['answer_text'])) {
        $given = $la['score'] > 0 ? $la['score'] : 7;
        $scoreLong += $given;
        mysqli_query($conn, "UPDATE exam_long_answers SET score=$given WHERE attempt_id=$attemptId AND lq_id={$lq['id']}");
    }
}

$totalScore = $scoreMcq + $scoreCode + $scoreLong;
$maxMarks = $attempt['max_marks'] ?: 30;

// Update attempt
mysqli_query($conn, "UPDATE exam_attempts SET 
    submitted=1, finished_at=NOW(),
    score_mcq=$scoreMcq, score_code=$scoreCode, score_long=$scoreLong,
    total_score=$totalScore, max_marks=$maxMarks
  WHERE id=$attemptId");

// Clear session locks
unset($_SESSION['exam_locked'], $_SESSION['locked_exam_id']);
unset($_SESSION["exam_auth_$eid"], $_SESSION["exam_start_$eid"]);

header("Location: exam_result.php?eid=$eid");
exit;
