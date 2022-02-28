<?php
require_once('config.php');
//session_start();
      //DB接続
      function connectDB(){
          try{
            $param = "mysql:dbname=".DB_NAME.";host=".DB_HOST;
            $pdo = new PDO($param,DB_USER,DB_PASSWORD);
            $pdo -> query('SET NAMES utf8');

            return $pdo;

          }catch(PDOException $e){
            echo $e -> getMessage();
            exit;
          }

        }

        //ランダムキーの生成（share_password用）
    function keyGenerate(){
              $key = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz0123456789"),0,10);
              return $key;
    }

    //CRF対策HTMLエスケープ
    function h($original_str){
        return htmlspecialchars($original_str,ENT_QUOTES,"UTF-8");
      }

      // トークンを発行する処理
      function setToken() {

          $token = sha1(uniqid(mt_rand(), true));
          $_SESSION['sstoken'] = $token;

      }

      // トークンをチェックする処理
      function checkToken() {
          if (empty($_SESSION['sstoken']) || ($_SESSION['sstoken'] != $_POST['token'])) {
              echo '<html><head><meta charset="utf-8"></head><body>不正なアクセスです。</body></html>';
              exit;
          }
      }

 ?>
