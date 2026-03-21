<?php
// ============================================================
//  config.php — Database + Session + SEB detection
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = mysqli_connect("localhost", "root", "", "seb_lms");
if (!$conn) die("DB connection failed: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8mb4");

// ── SEB Detection ─────────────────────────────────────────────
function isSEB(): bool {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (stripos($ua, 'SEB') !== false || stripos($ua, 'SafeExamBrowser') !== false);
}

// ── Enforce SEB — redirects to seb_required.php if not in SEB ─
// Only call this on pages that MUST run inside SEB (practice/exam content pages)
function requireSEB(): void {
    if (!isSEB()) {
        header("Location: seb_required.php");
        exit;
    }
}

// ── Auth helpers ──────────────────────────────────────────────
function requireLogin(): void {
    if (empty($_SESSION['student_id'])) {
        header("Location: login.php"); exit;
    }
}
function requireAdmin(): void {
    if (empty($_SESSION['admin'])) {
        header("Location: admin/index.php"); exit;
    }
}
function currentStudent(): array {
    return [
        'id'    => $_SESSION['student_id'] ?? 0,
        'name'  => $_SESSION['name'] ?? '',
        'regno' => $_SESSION['regno'] ?? ''
    ];
}

// ── Exam Lock ─────────────────────────────────────────────────
function checkExamLock(): void {
    if (!empty($_SESSION['exam_locked']) && !empty($_SESSION['locked_exam_id'])) {
        $current = basename($_SERVER['PHP_SELF']);
        $eid = (int)$_SESSION['locked_exam_id'];
        $allowed = [
            'exam.php',
            'save_exam_mcq.php',
            'save_exam_code.php',
            'save_long_answer.php',
            'exam_submit_handler.php',
            'run.php',
            'logout.php',
        ];
        if (!in_array($current, $allowed)) {
            header("Location: exam.php?eid=$eid");
            exit;
        }
    }
}
