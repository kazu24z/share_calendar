<?php
require_once('config.php');
require_once('functions.php');
session_start();

//セッションがなかったときの処理
if(!isset($_SESSION['USER'])){
  header('Location:'.SITE_URL.'login.php');
  exit;
}

if(!isset($_GET['id'])&&$_GET['ymd']){
  header('Location:'.SITE_URL.'index.php');
  exit;
}else{


  $id = $_GET['id'];
  $date = $_GET['ymd'];

  //対象データの削除実行
  $pdo = connectDB();
  $sql = "DELETE FROM item WHERE id = :id";
  $stmt = $pdo -> prepare($sql);
  $stmt -> bindValue(':id',$id);
  $stmt -> execute();

  unset($pdo);

  header('Location:'.SITE_URL.'detail.php?ymd='.$_GET['ymd']);
}


 ?>
