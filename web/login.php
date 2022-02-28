<?php
  require_once('config.php');
  require_once('functions.php');
  session_start();

  //DB接続
  $pdo = connectDB();

  if($_SERVER['REQUEST_METHOD']!='POST'){
    //初回画面表示時の処理

      //自動ログイン情報があるか確認
      if(@$_COOKIE['share_calendar']){

          $c_key = $_COOKIE['share_calendar'];

          $sql = 'SELECT * FROM auto_login WHERE c_key = :c_key AND expire >= :expire LIMIT 1';
          $stmt = $pdo -> prepare($sql);
          $stmt -> bindValue(':c_key',$c_key);
          $stmt -> bindValue(':expire',date('Y-m-d H:i:s'));
          $stmt -> execute();
          $result = $stmt -> fetch(PDO::FETCH_ASSOC);

          if($result){
            //自動ログイン
            //DBと照合し、一致した場合、ログイン処理。そうでない場合、エラー表示
            //POSTされた情報と、DBのユーザーテーブルを照合
            $sql ='SELECT * FROM user WHERE id = :user_id LIMIT 1';
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':user_id',$result['user_id']);
            $stmt -> execute();
            $result = $stmt -> fetch(PDO::FETCH_ASSOC);

            //セッションIDを書き換える（セッションハイジャック対策）
            session_regenerate_id(true);
            //セッションを格納
            $_SESSION['USER'] = $result;


            //DB接続解除
            unset($pdo);
            //ホーム画面へ移動
            header('Location: '.SITE_URL.'index.php');

            exit;


          }



      }



  }else{

  //POSTされた情報を受け取って変数に格納
  $user_email = $_POST['user_email'];
  $login_password = $_POST['login_password'];
  @$auto_login = $_POST['auto_login'];

  //入力チェック
  $err = array();

//ここまだできてないでええええええ
    //メールアドレス入力チェック
    if($user_email == ""){
      $err['user_email'] = 'メールアドレスを入力してください。';

    }elseif(!filter_var($user_email,FILTER_VALIDATE_EMAIL)){
      $err['user_email'] = 'メールアドレスの形式が不正です。';
    }elseif(mb_strlen($user_email)>30){
      $err['user_email'] = '文字数オーバーです。';
    }


    //パスワードの入力チェック
      //1_未入力チェック
      if($login_password == ""){
        $err['login_password'] = 'パスワードを入力してください。';
      }elseif(mb_strlen($login_password)>30){
      $err['login_password'] = '文字数オーバーです。';
    }


  //入力チェックエラーに何も保存されていない場合の処理
  if(empty($err)){

    //DBと照合し、一致した場合、ログイン処理。そうでない場合、エラー表示

      //POSTされた情報と、DBのユーザーテーブルを照合
      $sql ='SELECT * FROM user WHERE user_email = :user_email and login_password = :login_password LIMIT 1';
      $stmt = $pdo -> prepare($sql);
      $stmt -> bindValue(':user_email',$user_email);
      $stmt -> bindValue(':login_password',$login_password);
      $stmt -> execute();
      $result = $stmt -> fetch(PDO::FETCH_ASSOC);



      //もしDBに登録されていなかった場合のエラー格納処理
      if(empty($result)){
        $err['user_email'] = 'メールアドレスまたはパスワードが不正です。';
        $err['login_password'] = ' ';
      }else{
        //ログイン処理
        //セッションIDを書き換える（セッションハイジャック対策）
        session_regenerate_id(true);
          //セッションを開始
          $_SESSION['USER'] = $result;


          //自動ログイン情報を一度クリアする
          if(isset($_COOKIE['share_calendar'])){
            $c_key = $_COOKIE['share_calendar'];

            //Cookie情報をクリア
            setcookie('share_calendar','',time()-86400,'/dev/share_calendar/web');

              //DBをクリア
            $sql = "DELETE FROM auto_login WHERE c_key = :c_key";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':c_key',$c_key);
            $stmt -> execute();

          }

      //自動ログインにチェックがついていた時の処理
        if($auto_login){
          //c_keyを生成
          $c_key = sha1(uniqid(mt_rand(), true));
          //COOKIEに値を入れる
          setcookie('share_calendar',$c_key,time()+3600*24*365,'/share_calendar/web/');


          //SQL文を生成
          $sql = 'INSERT INTO auto_login (user_id,c_key,expire,created_at,updated_at) VALUES (:user_id,:c_key,:expire,now(),now())';

          $stmt = $pdo -> prepare($sql);
          $stmt -> bindValue(':user_id',$_SESSION['USER']['id']);
          $stmt -> bindValue(':c_key',$c_key);
          $stmt -> bindValue(':expire',date('Y-m-d H:i:s',time()+3600*24*365));
          $stmt -> execute();

        }

          //DB接続解除
          unset($pdo);
          //ホーム画面へ移動
          header('Location: '.SITE_URL.'index.php');
          exit;
        }//DB情報とマッチ後処理終わり
      }//入力チェック完了後処理終わり
  //DBとの接続を絶つ
  unset($pdo);
}//POST処理終わり


 ?>

<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title>ログイン | <?php echo SERVICE_NAME; ?></title>
    <meta name="description" content="コンセプトコンセプト" />		<!-- 検索結果画面で表示する内容 -->
    <meta name="keywords" content="カレンダー,シェア,予定共有" />	<!-- 検索ヒットキーワード -->

    <link href="css\bootstrap.min.css" rel="stylesheet">
    <link href="css/share_calendar.css" rel="stylesheet">
    <script src="js/bootstrap.min.js" ></script>

  </head>
  <body id="main">
    <header>
      <nav class="navbar navbar-expand-md navbar navbar-dark bg-dark navbar-fixed-top">
        <div class="container-fluid">
          <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><?php echo SERVICE_SHORT_NAME; ?></a>
        </div><!-- /.container-fluid -->
      </nav>
    </header>

    <div class="container" id="large">
      <div class="row">
        <div class="col-md-9">
            <div class="bg-light p-3 my-4 rounded"> <!--jumbotronがbootstrap5で廃止された-->
              <h1>予定をスムーズに管理・共有</h1>
              <p>あなたと大切なパートナーの予定を共有するwebサービスです。</p>
              <p><a href="signup.php" class="btn btn-success btn-lg">新規ユーザー登録（無料）</a></p>
            </div>
          <div class="row">
            <div class="col-md-4">
                <div class="card card-default">
                  <div class="card-header">
                    <h3 class="card-title">どうやって使うの？</h3>
                  </div>
                  <div class="card-body">
                    <p>(1)まずはユーザー登録</p>
                    <p>(2)ログインしたら予定を登録！</p>
                    <p>(3)画面上部のバーにある「共有」ボタンを押して、パートナー登録したいユーザーの情報を入力</p>
                    <p>(4)カレンダーページ上部の「パートナーの予定を表示」を押すと、パートナーの予定が見れるよ！</p>
                  </div>
                </div>
            </div> <!--col-md-4-->
            <div class="col-md-4">
              <div class="card card-default">
                <div class="card-header">
                  <h3 class="card-title">お金はかかりますか？</h3>
                </div>
                <div class="card-body">
                  <p>完全無料です！</p>
                </div>
              </div>
            </div> <!--col-md-4-->
            <div class="col-md-4">
              <div class="card card-default">
                <div class="card-header">
                  <h3 class="card-title">必ずパートナーの登録は必要ですか？</h3>
                </div>
                <div class="card-body">
                  <p>いいえ。自分のスケジュール管理としてもお使いいただけます！</p>
                </div>
              </div>
            </div> <!--col-md-4-->
          </div> <!--class="row"-->
        </div> <!--col-md-9-->
        <div class="col-md-3">
          <div class="card my-4 card-default">
            <div class="card-header">
              <h2 class="card-title">ログイン</h2>
            </div>

            <div class="card-body">
              <form method="POST">

                <div class="mb-3"> <!--bootstrap5でform-groupが廃止された-->
                  <label class="form-label" for="userMail">メールアドレス</label>
                  <input type="text" name="user_email" value="<?php echo h(@$user_email) ;?>" class="form-control <?php if(@$err['user_email']!=''){echo 'is-invalid';} ?>" id="userMail">

                  <div class="p-1 <?php if(@$err['user_email'] != ''){echo 'invalid-feedback';} ?>"><?php echo h(@$err['user_email']); ?></div>

                </div>

                <div class="mb-3">
                  <label class="form-label" for="login_password">パスワード</label>
                  <input type="password" name="login_password" value="" class="form-control <?php if(@$err['login_password']!=''){echo 'is-invalid';} ?>" id="userPassword">

                  <div class="p-1 <?php if(@$err['login_password'] != ''){echo 'invalid-feedback';} ?>"><?php echo h(@$err['login_password']); ?></div>

                </div>

                <div class="mb-3">
                  <input type="submit" value="ログイン" class="form-control" class="btn btn-primary btn-block">
                </div>

                <div class="form-check">
                  <input type="checkbox" class="form-check-input" name="auto_login" value="on" id="formCheckDefault">
                  <label class="form-check-label" for="formCheckDefault">次回から自動ログイン</label>
                </div>
              </form>
            </div><!--<div class="card-body">-->
          </div> <!--<div class="card my-4 card-default">-->
        </div> <!--col-md-3-->
      </div>  <!--class="row"-->
      <hr>
      <footer class="footer">
        <p><?php echo COPYRIGHT; ?></p>
      </footer>
    </div> <!--class="container"-->











  </body>


</html>
