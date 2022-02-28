<?php
  require_once('config.php');
  require_once('functions.php');
  session_start();

  if($_SERVER['REQUEST_METHOD']!='POST'){
    //初回画面表示時の処理
  }else{
    //フォームのサブミットボタンが押された時の処理------------------------------

      //入力されたユーザーネーム、メールアドレス、パスワードを受け取り、「変数」に入れる。
      $user_name = $_POST['user_name'];
      $user_email = $_POST['user_email'];
      $login_password = $_POST['login_password'];
      //共有用パスワードを生成し、変数に格納する。
      $schedule_password = keyGenerate();
    //入力エラーチェック
      $err = array();

      //ユーザーネーム入力チェック
        //1_未入力チェック
        if($user_name == ""){
          $err['user_name'] = 'ユーザーネームを入力してください。';
        }
        //2_空白チェック（半角、全角のスペースを弾く）
        if(preg_match('/( |　)+/',$user_name)){
          $err['user_name'] = '半角・全角のスペースは入力できません。';
        }

        //3_文字数チェック(30byte)
        if(mb_strlen($user_name) > 30){
          $err['user_name'] = '30文字以下で入力してください。';
        }

        //4_既存チェック（すでに使われていないか）
        $pdo = connectDB();
        $sql = 'SELECT user_name FROM user WHERE user_name=:user_name LIMIT 1';
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':user_name',$user_name);
        $stmt -> execute();
        $result = $stmt -> fetch(PDO::FETCH_ASSOC);

//var_dump($result);
//exit;

        if(!empty($result)){
          $err['user_name'] = ' この名前は既に使われています。';
        }

      //メールアドレス入力チェック
        //1_未入力チェック→2_形式チェック
        if($user_email == ""){
          $err['user_email'] = 'メールアドレスを入力してください。';
        }elseif(!filter_var($user_email,FILTER_VALIDATE_EMAIL)){
          $err['user_email'] = 'メールアドレスの形式が不正です。';
        }elseif(mb_strlen($user_email) > 200){
          $err['user_email'] = '200文字以下で入力してください。';
        }
        //4_既存チェック(すでに登録されているアドレスでないか)
        $pdo = connectDB();
        $sql = 'SELECT user_email FROM user WHERE user_email=:user_email LIMIT 1';
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':user_email',$user_email);
        $stmt -> execute();
        $result = $stmt -> fetch(PDO::FETCH_ASSOC);

        if(!empty($result)){
          $err['user_email'] = ' このメールアドレスは既に登録されています。';
        }
      //パスワード入力チェック
        //1_未入力チェック
        if($login_password == ""){
          $err['login_password'] = 'パスワードを入力してください。';
        }
        //2_空白チェック（半角、全角のスペースを弾く）
        if(preg_match('/( |　)+/',$login_password)){
          $err['login_password'] = '半角・全角のスペースは入力できません。';
        }
        //3_文字数チェック(30byte)
        if(mb_strlen($login_password) > 30){
          $err['login_password'] = '30文字以下で入力してください。';
        }

    //各入力チェックがOKだった後の処理---------------------------------------------
    if(empty(@$err)){
      //データベースに接続する（PDO方式）
        $pdo = connectDB();

      //データベース（userテーブル）に入力データを新規登録する。
        $sql ="INSERT INTO user (user_name,user_email,login_password,schedule_password,created_at,updated_at,delivery_hour) VALUES (:user_name,:user_email,:login_password,:schedule_password,now(),now(),99)";

        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':user_name',$user_name);
        $stmt -> bindValue(':user_email',$user_email);
        $stmt -> bindValue(':login_password',$login_password);
        $stmt -> bindValue(':schedule_password',$schedule_password);

        $stmt -> execute();


      //登録したデータをセッションに格納する
       $sql ="SELECT * FROM user WHERE user_name = :user_name and user_email = :user_email LIMIT 1";
       $stmt = $pdo -> prepare($sql);
       $stmt -> execute(array(":user_name" => $user_name,":user_email" => $user_email));
       //ユーザー情報を変数に格納
       $user = $stmt -> fetch(PDO::FETCH_ASSOC);

       //セッションハイジャック対策
        session_regenerate_id(true);
        //セッションにユーザー情報を保存
        $_SESSION['USER'] = $user;


     //データベース（pairテーブル）に作成したユーザーの情報を登録する。

        $sql = "INSERT INTO pair
        (user_id_me,created_at,updated_at) VALUES
        (:user_id_me,now(),now())";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':user_id_me',$user['id']);
        //$stmt -> bindValue(':user_id_me',$user['id']);
        $stmt -> execute();

        unset($pdo);

      //新規ユーザー情報を管理者に通知
      mb_language("japanese");
      mb_internal_encoding("UTF-8");

      $mail_subject = '【シェア・カレンダー】に新規ユーザー登録がありました';
      $mail_body = 'ユーザー名:'.$user['user_name'].PHP_EOL;
      $mail_body.= 'メールアドレス:'.$user['user_email'];

      mb_send_mail(ADMIN_MAIL_ADDRESS,$mail_subject,$mail_body);

      //登録完了メールをユーザーに送付(この時「ユーザーID」「共有パスワード」送付)

      $mail_subject = '【御礼と通知】シェア・カレンダーに登録いただきありがとうございます。';

      $mail_body = 'この度はシェア・カレンダーにご登録いただきありがとうございます。'.PHP_EOL;
      $mail_body.= '本アプリを通じてパートナー様との仲をより深めていただければ幸いです。'.PHP_EOL.PHP_EOL;
      $mail_body.= '【ユーザーIDとスケジュールパスワードの通知】'.PHP_EOL;
      $mail_body.= $user['user_name'].'様のユーザーIDとスケジュールパスワードは以下になります。'.PHP_EOL;
      $mail_body.= 'パートナーを登録する際にご利用ください。'.PHP_EOL;
      $mail_body.= 'ユーザーID：'.$user['id'].PHP_EOL;
      $mail_body.= 'スケジュールパスワード:'.$user['schedule_password'].PHP_EOL.PHP_EOL;
      $mail_body.='以上、よろしくお願いいたします。';

      mb_send_mail($user['user_email'],$mail_subject,$mail_body);


      //登録完了画面に遷移(signup_complete.php)
      header('Location: '.SITE_URL.'signup_complete.php');

      exit;

    } //DB登録処理終わり
  } //POST処理終わり
 ?>


<!DOCTYPE html>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title>ユーザー登録 | <?php echo SERVICE_NAME; ?></title>
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

    <div class="container">
      <h1>ユーザー登録</h1>

      <form method="POST" class="card card-default card-body" novalidate>
        <!--ユーザーネーム-->
        <div class="mb-3 mt-3">
          <input type="text" name="user_name" value="" required class="form-control <?php if(@$err['user_name']!=''){echo 'is-invalid';} ?>"  placeholder="ユーザーネーム" >
          <div class="p-1 <?php if(@$err['user_name'] != ''){echo 'invalid-feedback';} ?>"><?php echo h(@$err['user_name']); ?></div>
        </div> <!--mb-3-->

        <!--メールアドレス-->
        <div class="mb-3 mt-3">
          <input type="email" name="user_email" value="" class="form-control <?php if(@$err['user_email']!=''){echo 'is-invalid';} ?>" placeholder="メールアドレス">
          <div class="p-1 <?php if(@$err['user_email'] != ''){echo 'invalid-feedback';} ?>"><?php echo h(@$err['user_email']); ?></div>
        </div> <!--mb-3-->

        <!--パスワード-->
        <div class="mb-3 mt-3">
          <input type="password" name="login_password" value="" class="form-control <?php if(@$err['login_password']!=''){echo 'is-invalid';} ?>" placeholder="パスワード">
          <div class="p-1 <?php if(@$err['login_password'] != ''){echo 'invalid-feedback';} ?>"><?php echo h(@$err['login_password']); ?></div>
        </div> <!--mb-3-->

        <div class="mb-3">
          <input type="submit" value="登録" class="btn btn-success btn-block">
        </div> <!--mb-3-->
      </form>


  </body>


</html>
