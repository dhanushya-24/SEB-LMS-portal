<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");
$sid=intval($_POST['subject_id']??0);$title=trim($_POST['title']??'');
$q=trim($_POST['question']??'');$tci=trim($_POST['tc_input']??'');$tce=trim($_POST['tc_expected']??'');
if(!$sid||!$title||!$q||!$tce){header("Location: dashboard.php");exit;}
$st=$conn->prepare("INSERT INTO questions(subject_id,title,question)VALUES(?,?,?)");
$st->bind_param("iss",$sid,$title,$q);$st->execute();$qid=$conn->insert_id;
$st2=$conn->prepare("INSERT INTO testcases(question_id,input,expected_output)VALUES(?,?,?)");
$st2->bind_param("iss",$qid,$tci,$tce);$st2->execute();
header("Location: dashboard.php?msg=added");exit;
