<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");

$sid   = intval($_POST['subject_id']??0) ?: 'NULL';
$title = $conn->real_escape_string(trim($_POST['title']??''));
$dur   = intval($_POST['duration_min']??90);
$tot   = intval($_POST['total_marks']??20);
$ep    = $conn->real_escape_string(trim($_POST['entry_password']??''));
$xp    = $conn->real_escape_string(trim($_POST['exit_password']??'quit2024'));
$act   = intval($_POST['is_active']??1);
$st    = trim($_POST['start_time']??'');
$et    = trim($_POST['end_time']??'');
$stQ   = $st ? "'$st'" : 'NULL';
$etQ   = $et ? "'$et'" : 'NULL';

if(!$title){header("Location: exams.php");exit;}

$conn->query("INSERT INTO exams(subject_id,title,duration_min,total_marks,is_active,entry_password,exit_password,start_time,end_time)
  VALUES($sid,'$title',$dur,$tot,$act,'$ep','$xp',$stQ,$etQ)");

header("Location: exams.php?msg=added");exit;
