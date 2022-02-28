<?php
  require_once('config.php');
  session_start();

  if(!isset($_SESSION['USER'])){
    header('Location: '.SITE_URL.'login.php');
    exit;
  }

 ?>

<!DOCTYPE html>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title>登録完了 | <?php echo SERVICE_NAME; ?></title>
    <meta name="description" content="コンセプトコンセプト" />		<!-- 検索結果画面で表示する内容 -->
    <meta name="keywords" content="カレンダー,シェア,予定共有" />	<!-- 検索ヒットキーワード -->
    <link href="css\bootstrap.min.css" rel="stylesheet">
    <link href="css/share_calendar.css" rel="stylesheet">
    <script src="js/bootstrap.min.js" ></script>
  </head>
  <body>
    <header>
      <nav class="navbar navbar-expand-md navbar navbar-dark bg-dark navbar-fixed-top">
        <div class="container-fluid">
          <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><?php echo SERVICE_SHORT_NAME; ?></a>
        </div><!-- /.container-fluid -->
      </nav>
    </header>
    <div class="container">
      <h1>ユーザー登録完了</h1>
      <div class="alert alert-success">
        登録が完了しました。
      </div>
      <p>登録したメールアドレスに、スケジュール共有のための「ユーザーID」「スケジュールパスワード」を送信しています。ご確認ください。</p>
      <a href="index.php">トップページへ</a>

      <hr>
      <footer class="footer">
        <p><?php echo COPYRIGHT; ?></p>
      </footer>
    </div> <!--container-->


  </body>


</html>
