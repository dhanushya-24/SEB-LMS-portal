<?php
// This page is the SEB start URL for Practice Mode.
// SEB lands here → if not logged in, redirects to login_seb.php
// If logged in, goes straight to practice.php
include "config.php";
requireSEB(); // Must be in SEB

if (empty($_SESSION['student_id'])) {
    header("Location: login_seb.php");
    exit;
}
header("Location: practice.php");
exit;
