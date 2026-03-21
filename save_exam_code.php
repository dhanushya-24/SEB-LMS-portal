<?php
include "config.php";
requireLogin();
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attemptId = intval($_POST['attempt_id'] ?? 0);
    $ecqId     = intval($_POST['ecq_id'] ?? 0);
    $code      = $_POST['code'] ?? '';
    // Accept both 'language' and 'lang' parameter names
    $lang = $_POST['language'] ?? $_POST['lang'] ?? 'python';
    $lang = in_array($lang, ['python','java','c','cpp']) ? $lang : 'python';

    if ($attemptId && $ecqId) {
        $code = mysqli_real_escape_string($conn, $code);
        mysqli_query($conn,
            "INSERT INTO exam_code_answers (attempt_id,ecq_id,language,submitted_code)
             VALUES ($attemptId,$ecqId,'$lang','$code')
             ON DUPLICATE KEY UPDATE language='$lang',submitted_code='$code'");
    }
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false]);
}
