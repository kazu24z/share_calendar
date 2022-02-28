<?php
require_once('config.php');
require_once('functions.php');
session_start();

$pdo = connectDB();

  //クッキーの無効化
  if(isset($_COOKIE['share_calendar'])){

    $auto_login = $_COOKIE['share_calendar'];

    setcookie('share_calendar','',time()-86400,'/dev/share_calendar/web/');
    // DB情報をクリア

  	$sql = "DELETE FROM auto_login where c_key = :c_key";
  	$stmt = $pdo->prepare($sql);
  	$stmt->execute(array(":c_key" => $auto_login));

  }

  //セッション内のデータ削除
  $_SESSION = array();

  if (isset($_COOKIE[session_name()])) {
  	setcookie(session_name(), '', time()-86400, '/share_calendar/web/');
  }

  session_destroy();

  unset($pdo);

  header('Location:'.SITE_URL.'login.php');

 ?>
