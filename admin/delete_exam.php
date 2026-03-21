<?php
session_start();
if(empty($_SESSION['admin'])){header("Location: index.php");exit;}
$conn=new mysqli("localhost","root","","seb_lms");
$id=intval($_GET['id']??0);
if($id>0) $conn->query("DELETE FROM exams WHERE id=$id");
header("Location: exams.php?msg=deleted");exit;
