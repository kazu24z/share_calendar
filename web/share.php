<?php
    require_once('config.php');
    require_once('functions.php');
    session_start();

    if(!isset($_SESSION['USER'])){
      header('Location:'.SITE_URL.'login.php');
      exit;
    }

    if($_SERVER['REQUEST_METHOD']!='POST'){
      //初回画面表示時の処理

      //CSRF対策 トークン発行
      setToken();

    }else{

      //CSRF対策　トークンチェック
      checkToken();

      //セッション変数を受け取る
      $user = $_SESSION['USER'];

      //POST送信を変数で受け取る
      $user_id_you = $_POST['user_id_you'];
      $schedule_password = $_POST['schedule_password'];

      //DBへ接続
      $pdo = connectDB();

      //入力チェック
        //入力エラー格納用配列を定義
        $err = array();
        //パートナーのユーザーID　入力チェック

          //そのユーザーが存在しているかどうか
          $sql = 'SELECT * FROM user WHERE id = :id LIMIT 1';
          $stmt = $pdo -> prepare($sql);
          $stmt -> bindValue(':id',$user_id_you);
          $stmt -> execute();
          $result = $stmt -> fetch(PDO::FETCH_ASSOC);

          if(empty($result)){
            $err['user_id_you'] = 'ユーザーIDが不正です。';
          }

          //自分のユーザーIDを登録しようとした場合
          //もしヒットした場合の処理
          if($user_id_you == $user['id']){
            $err['user_id_you'] = '自分のIDは登録できません';
          }

          //既にpairテーブルに登録されていないか
          $sql = 'SELECT * FROM pair WHERE user_id_you = :user_id_you LIMIT 1';
          $stmt = $pdo -> prepare($sql);
          $stmt -> execute(array(':user_id_you' => $user_id_you));
          $result = $stmt -> fetch(PDO::FETCH_ASSOC);

          //もしヒットした場合の処理
          if(!empty($result)){
            $err['user_id_you'] = 'このIDは既にパートナー登録されています。';
          }

        //パートナーの共有パスワード　入力チェック
          //入力欄が空白かどうか
          if($schedule_password == ''){
            $err['schedule_password'] = 'スケジュールパスワードを入力してください。';
          }else{
          //そのユーザーに付与したスケジュールパスワードと一致しているかどうか
            $sql = 'SELECT * FROM user WHERE schedule_password = :schedule_password AND id = :user_id_you LIMIT 1';
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':schedule_password',$schedule_password);
            $stmt -> bindValue(':user_id_you',$user_id_you);
            $stmt -> execute();
            $result = $stmt -> fetch(PDO::FETCH_ASSOC);

            if(empty($result)){
              $err['schedule_password'] = 'パスワードが不正です。';
            }
          }

        //入力エラーがなかった場合の処理
         //パートナー情報をpairテーブルに登録

         if(empty($err)){
          //登録成功メッセージを格納
          $success = array();
          $success['content'] = 'パートナーを登録しました。';
          $success['html'] ='"alert alert-success" role="alert"';

          //user_id_meが自分のユーザーIDである行にパートナーのユーザーIDを追加
          $sql = 'UPDATE pair SET user_id_you = :user_id_you,updated_at = now() WHERE user_id_me = :user_id_me LIMIT 1';
          $stmt = $pdo -> prepare($sql);
          $stmt -> bindValue(':user_id_you',$user_id_you);
          $stmt -> bindValue(':user_id_me',$user['id']);
          $flag1 = $stmt -> execute();

          //user_id_meがパートナーのユーザーIDである行に、自分のユーザーIDを追加
          $sql = 'UPDATE pair SET user_id_you = :user_id_me,updated_at = now() WHERE user_id_me = :user_id_you LIMIT 1';
          $stmt = $pdo -> prepare($sql);
          $stmt -> bindValue(':user_id_me',$user['id']);
          $stmt -> bindValue(':user_id_you',$user_id_you);
          $flag2 = $stmt -> execute();

       }//エラーがなかった時の登録処理

       unset($pdo);

    }//POST処理終わり



 ?>


<!DOCTYPE html>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title>共有 | <?php echo SERVICE_NAME; ?></title>
    <meta name="description" content="コンセプトコンセプト" />		<!-- 検索結果画面で表示する内容 -->
    <meta name="keywords" content="カレンダー,シェア,予定共有" />	<!-- 検索ヒットキーワード -->
    <link href="css\bootstrap.min.css" rel="stylesheet">
    <link href="css/share_calendar.css" rel="stylesheet">
    <link href="css\all.min.css" rel="stylesheet">
    <script src="js/bootstrap.min.js" ></script>

  </head>
  <body>
    <header>
      <nav class="navbar navbar-expand-md navbar navbar-dark bg-dark navbar-fixed-top">
        <div class="container-fluid">
          <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><?php echo SERVICE_SHORT_NAME; ?></a>
          <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#Navber" aria-controls="Navber" aria-expanded="false" aria-label="レスポンシブ・ナビゲーションバー">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="Navber">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <a class="nav-link" href="add.php">
                  <i class="fas fa-plus"></i>追加
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="search.php">
                  <i class="fas fa-search"></i>検索
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="share.php">共有</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="setting.php">設定</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="logout.php">ログアウト</a>
              </li>
            </ul>
            </ul>
            <form method="GET" action="index.php" class="d-flex">
              <input type="month" class="form-control me-2" name="search" varlue="" type="search" placeholder="Search" aria-label="Search">
              <button class="btn btn-outline-success flex-shrink-0" type="submit">表示</button>
            </form>
          </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
      </nav>
    </header>

    <div class="container">
      <div class="row">
        <div class="col-md-6 offset-md-3">
          <h3 class="text-center mb-3 mt-3">パートナーの登録</h3>

          <!--フォーム送信後のメッセージを表示-->
            <div class=<?php if(empty(@$err)){echo @$success['html'] ;} ?> >
              <?php if(empty(@$err)){echo h(@$success['content']);} ?>
            </div>

          <form method="POST" class="card card-default card-body">

            <div class="mb-3">
              <input type="text" name="user_id_you" value="" class="form-control <?php if(@$err['user_id_you'] != ''){echo 'is-invalid';} ?>" placeholder="パートナーのユーザーID">
            <!--エラーメッセージを表示-->
              <div class="p-1 <?php if(@$err['user_id_you'] != ''){echo 'invalid-feedback';} ?>">
                <?php if(@$err['user_id_you'] != ''){echo h(@$err['user_id_you']);}?>
              </div>
            </div> <!--ユーザーID入力欄終わりmb-3-->

            <div class="mb-3">
              <input type="text" name="schedule_password" value="" class="form-control <?php if(@$err['schedule_password'] != ''){echo 'is-invalid';} ?>" placeholder="パートナーのスケジュールパスワード※">
              <!--エラーメッセージを表示-->
                <div class="p-1 <?php if(@$err['schedule_password'] != ''){echo 'invalid-feedback';} ?>">
                  <?php if(@$err['schedule_password'] != ''){echo h(@$err['schedule_password']);}?>
                </div>
            </div> <!--パートナーのスケジュールパスワード入力欄終わりmb-3-->

            <div class="mb-3 mt-3 d-grid gap-2">
              <input type="submit" value="登録" class="btn btn-success btn-block">
            </div> <!--mb-3-->

            <input type="hidden" name="token" value="<?php echo h($_SESSION['sstoken']); ?>" />

        </form>
        <div class="container mb-3 mt-1">
          <p>※ログインパスワードとは異なります。<br>ユーザー登録時に送信された「登録完了メール」に記載された情報をパートナーに教えてもらってください。</p>

          <a href="index.php">ホームに戻る</a>

        </div>

        <hr>
        <footer class="footer">
          <p><?php echo COPYRIGHT; ?></p>
        </footer>
      </div>
     </div>
    </div> <!--container-->


  </body>


</html>
