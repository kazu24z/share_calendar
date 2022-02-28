<?php
  require_once('config.php');
  require_once('functions.php');
  session_start();

  //セッションデータがなかった時の処理
    if(!isset($_SESSION['USER'])){
      header('Location:'.SITE_URL.'login.php');
      exit;
    }

    if($_SERVER['REQUEST_METHOD']!='POST'){
      //初回画面表示時の処理

      //CSRF対策　トークン発行
      setToken();

      //セッションを保持しているユーザー情報を格納
      $user = $_SESSION['USER'];

      //GETなしでdetail.phpにアクセスされた時、inde.phpに遷移する。
      if(empty($_GET['id']) or empty($_GET['user_id'])){
        header('Location:'.SITE_URL.'index.php');
        exit;
      }

      //GETで送信されたuser_idとセッションに保存しているuser_idが一致していることを確認
      if($user['id'] != $_GET['user_id']){
        header('Location:'.SITE_URL.'index.php');
        exit;
      }

      //該当するデータを取得させる処理
      //DBへ接続する
      $pdo = connectDB();
      //SQL文を実行
      $sql ='SELECT * FROM item WHERE id = :id and user_id = :user_id LIMIT 1';
      $stmt = $pdo -> prepare($sql);
      $stmt -> bindValue(':id',$_GET['id']);
      $stmt -> bindValue(':user_id',$user['id']);
      $stmt -> execute();
      //該当の予定を配列に格納
      $result = $stmt -> fetch(PDO::FETCH_ASSOC);

      $label_color = $result['label_color'];
      $item_text = $result['item_text'];

      //input type='datetime-local'のvalueに合わせるために、$result['start_at']＆$result['end_at']を分割
      $exploded_start = explode(" ",$result['start_at']);
      $start_at = $exploded_start[0].'T'.$exploded_start[1];

      $exploded_end = explode(" ",$result['end_at']);
      $end_at = $exploded_end[0].'T'.$exploded_end[1];

      //DB接続を解除
      unset($pdo);
    }else{
      //フォームのサブミットボタンが押された時の処理------------------------------

      //CSRF対策 トークンチェック
      checkToken();

        //入力されたユーザーネーム、メールアドレス、パスワードを受け取り、「変数」に入れる。
        $start_at = $_POST['start_at'];
        $end_at = $_POST['end_at'];
        $item_text = $_POST['item_text'];
        $label_color = $_POST['label_color'];


        //セッション情報からユーザー情報（ユーザーid)を格納
        $user = $_SESSION['USER'];
        $user_id = $user['id'];

        //対象のitemテーブルのIDを取得
        $item_id = $_POST['item_id'];

      //入力エラーチェック ------------------------------------------------------
        //エラー格納変数定義
        $err = array();

        //var_dump($start_at); string型＝文字列 YYYY-MM-DDThh:mm
        //exit;

        //開始時刻未入力チェック
        if($start_at ==""){
          $err['start_at'] = '開始日時を選択してください。';
        }elseif(!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):[0-5][0-9]/',$start_at)){
          //開始日時刻入力形式チェック
          //  $format ='%Y-%m-%d %T';
              $err['start_at'] = '開始日時の形式が不正です。';

          }else{


              //開始日時刻下限値チェック（1960-01-01T00:00以降か）
                //下限値インスタンス生成
                $min_time = '1960-01-01T00:00';
                $min_time_inst = new DateTime($min_time);
                  //POST日付のインスタンス生成
                $start_at_inst = new DateTime($start_at);
                  //フォーマット変更
                $min_time = $min_time_inst -> format('Y-m-d H:i');
                $start_at = $start_at_inst -> format('Y-m-d H:i');
                //チェック条件式
              if($min_time > $start_at){
                $err['start_at'] = '開始日時の値が不正です。';
              }
          }


        //終了時刻未入力チェック
        if($end_at ==""){
          $err['end_at'] = '終了日時を選択してください。';
        }elseif(!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):[0-5][0-9]/',$end_at)){

            $err['end_at'] = '終了日時の形式が不正です。';
            $flag = '1';

         }elseif(@$flag!='1'){
             //終了日時刻下限値チェック（1960-01-01T00:00以降か）
             //下限値インスタンス生成
             $min_time = '1960-01-01T00:00';
             $min_time_inst = new DateTime($min_time);
             //POST日付のインスタンス生成
             $end_at_inst = new DateTime($end_at);
             //フォーマット変更
             $min_time = $min_time_inst -> format('Y-m-d H:i');
             $end_at = $end_at_inst -> format('Y-m-d H:i');
             //チェック条件式
             if($min_time > $end_at){
               $err['end_at'] = '終了日時の値が不正です。';
             }

         }elseif(@$err['start_at']==''&&@$err['end_at']==''){
             //時間幅正常性チェック（終了が開始より早くなっていないか）
             //POST開始日時のインスタンス生成
             $start_at_inst = new DateTime($start_at);
             //POST終了日時のインスタンス生成
             $end_at_inst = new DateTime($end_at);
             //フォーマット変更
             $start_at = $start_at_inst -> format('Y-m-d H:i');
             $end_at = $end_at_inst -> format('Y-m-d H:i');
             //チェック条件式
             if($end_at <= $start_at){
               $err['time_wrap'] = '開始日時が終了日時を超えています。';
             }

         }


        //予定未入力チェック
        if($item_text ==""){
          $err['item_text'] = '予定を入力してください。';
        }
        //予定文字数チェック（100文字）
        if(mb_strlen($item_text) > 100){
          $err['item_text'] = '予定は100文字以下で入力してください。';
        }
        //ラベルカラー入力チェック（万が一空白で送られた時）
        if($label_color == ""){
          $err['label_color'] = 'カラーを選択してください。';
        }


      //各入力チェックがOKだった後の処理---------------------------------------------
      if(empty(@$err)){
          //seccessメッセージ格納
          $success = array();
          $success['content'] = '予定を編集しました。';
          $success['html'] ='"alert alert-success" role="alert"';
        //データベースに接続する（PDO方式）
          $pdo = connectDB();

        //データベース（userテーブル）に入力データを新規登録する。
          $sql ="UPDATE item
                 SET start_at =:start_at ,end_at = :end_at ,item_text = :item_text ,label_color = :label_color ,updated_at = now()
                 WHERE user_id = :user_id AND id = :item_id";

          $stmt = $pdo -> prepare($sql);
          $stmt -> bindValue(':start_at',$start_at);
          $stmt -> bindValue(':end_at',$end_at);
          $stmt -> bindValue(':item_text',$item_text);
          $stmt -> bindValue(':label_color',$label_color);
          $stmt -> bindValue(':user_id',$user_id);
          $stmt -> bindValue(':item_id',$item_id);
          $stmt -> execute();

          $edit_end = 'finished';

          unset($pdo);
      } //DB登録処理終わり

    }

 ?>


<!DOCTYPE html>

<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>予定の編集 | <?php echo SERVICE_NAME; ?></title>
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
              <input type="month" pattern="^[0-9]{4}-(0[1-9]|1[0-2])$" title="YYYY-MM" class="form-control me-2" name="search" varlue="" type="search" placeholder="YYYY-MM" aria-label="Search" min="1960-01" max="9999-12">
              <button class="btn btn-outline-success flex-shrink-0" type="submit">表示</button>
            </form>
          </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
      </nav>
    </header>
    <div class="container">
      <div class="row">
        <div class="col-md-6 offset-md-3">
          <h3  class="text-center mt-3 mb-3">予定の編集</h3>

          <!--フォーム送信後のメッセージを表示-->
      <?php
      if(empty(@$err) && @$edit_end == 'finished' ){
        echo '<div class='.@$success['html'].'>';
                echo h(@$success['content']);
        echo '</div>';

        echo '<a href="index.php">ホームに戻る</a>';
        exit;
      }
      ?>


          <form method="POST" class="card card-default card-body" novalidate>
          <!--入力フォーム-->

            <!--開始日時-->
            <div class="mb-3">
              <label for="start_at" class="form-label">開始日時</label>
              <input type="datetime-local" pattern="^[0-9]{4}-(0[1-9]|1[0-2])$" id="start_at" name="start_at" value="<?php if(@$start_at !=''){ echo substr_replace(h(@$start_at),'T','10',1);}?>" class="form-control <?php if(@$err['start_at'] || @$err['time_wrap'] != ''){echo 'is-invalid';} ?>" min="1960-01-01T00:00" max="9999-12-31T23:59" >
                <!--エラー表示-->
              <div class="p-1 <?php if(@$err['start_at']  || @$err['time_wrap'] != ''){echo 'invalid-feedback';} ?>">
                <?php if(@$err['start_at'] != ''){echo h(@$err['start_at']);}elseif(@$err['time_wrap'] != ''){echo h(@$err['time_wrap']);}?>
              </div>
            </div> <!--mb-3-->

            <!--終了日時-->
            <div class="mb-3">
              <label for="end_at" class="form-label">終了日時</label>
              <input type="datetime-local" id="end_at" name="end_at" value="<?php if(@$end_at !=''){ echo substr_replace(h(@$end_at),'T','10',1);}?>" class="form-control <?php if(@$err['end_at'] || @$err['time_wrap'] != ''){echo 'is-invalid';} ?>" min="1960-01-01T00:00" max="9999-12-31T23:59" >
                  <!--エラー表示-->
              <div class="p-1 <?php if(@$err['end_at'] != ''){echo 'invalid-feedback';} ?>">
                <?php echo h(@$err['end_at']); ?>
              </div>
            </div> <!--mb-3-->

            <div class="mb-3 <?php if(@$err['time_wrap'] != ''){echo 'invalid-feedback';} ?>">
              <?php echo h(@$err['time_wrap']); ?>
            </div>

            <!--予定-->
            <div class="mb-3">
              <label for="item_text" class="form-label">予定</label>
              <input type="text" id="item_text" name="item_text" value="<?php echo h(@$item_text); ?>" class="form-control <?php if(@$err['item_text']!=''){echo 'is-invalid';} ?>">
              <div class="p-1 <?php if(@$err['item_text'] != ''){echo 'invalid-feedback';} ?>">
                <?php echo h(@$err['item_text']); ?>
              </div>
            </div> <!--mb-3-->

            <!--ラベルカラー-->
            <label for="label_color" class="form-label ">ラベルカラー</label>
            <select id="label_color" class="form-select mb-3 <?php if(@$err['label_color']!=''){echo 'is-invalid';} ?>" name="label_color" aria-label="Default select example" >
              <option value="light" <?php if(h(@$label_color) == 'light'){echo 'selected';}?>>灰色</option>
              <option value="danger" <?php if(h(@$label_color) == 'danger'){echo 'selected';}?>>赤</option>
              <option value="info" <?php if(h(@$label_color) == 'info'){echo 'selected';}?>>水色</option>
              <option value="success" <?php if(h(@$label_color) == 'success'){echo 'selected';}?>>緑</option>
              <option value="dark" <?php if(h(@$label_color) == 'dark'){echo 'selected';}?>>黒</option>
              <div class="p-1 <?php if(@$err['label_color'] != ''){echo 'invalid-feedback';} ?>">
                <?php echo h(@$err['label_color']); ?>
              </div>
            </select>



            <input type="hidden" name="item_id" value="<?php echo h(@$result['id']);?>">

            <!--送信ボタン-->
            <div class="mb-3 mt-3 d-grid gap-2">
              <input type="submit" value="登録" class="btn btn-success btn-block">
            </div> <!--mb-3-->

            <input type="hidden" name="token" value="<?php echo h($_SESSION['sstoken']); ?>" />

          </form>

          <hr>
          <footer class="footer">
            <p><?php echo COPYRIGHT; ?></p>
          </footer>
        </div> <!--container-->


        </div><!--class=col-md offset-md-3-->
      </div><!--class=row-->

  </body>


</html>
