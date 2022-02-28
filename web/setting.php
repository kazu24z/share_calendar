<?php
require_once('config.php');
require_once('functions.php');
session_start();

//セッションデータがなかった時の処理
  if(!isset($_SESSION['USER'])){
    header('Location:'.SITE_URL.'login.php');
    exit;
  }
  //セッション情報を変数で受け取る
  $user = $_SESSION['USER'];
  //初回アクセス表示
  //選択タブに表示する選択項目を格納

  if($_SERVER['REQUEST_METHOD']!='POST'){

    //CSRF対策 トークン発行
    setToken();

    //現在登録している通知時間設定を表示
    $pdo = connectDB();
    //DB登録処理
    $sql = 'SELECT delivery_hour FROM user WHERE id = :id LIMIT 1';
    $stmt = $pdo ->prepare($sql);
    $stmt -> bindValue('id',$user['id']);
    $stmt -> execute();
    $result = $stmt -> fetch(PDO::FETCH_ASSOC);

  }else{

    //CRSF対策　トークンチェック
    checkToken();

    //POST情報を変数で受け取る
    $delivery_hour = $_POST['delivery_hour'];

    //DB接続
    $pdo = connectDB();
    //DB登録処理
    $sql = 'UPDATE user SET delivery_hour = :delivery_hour WHERE id = :id LIMIT 1';
    $stmt = $pdo ->prepare($sql);
    $stmt -> bindValue('delivery_hour',$delivery_hour);
    $stmt -> bindValue('id',$user['id']);
    $flag = $stmt -> execute();

    //登録した値を画面に表示するために取得
    $sql = 'SELECT delivery_hour FROM user WHERE id = :id LIMIT 1';
    $stmt = $pdo ->prepare($sql);
    $stmt -> bindValue('id',$user['id']);
    $stmt -> execute();
    $result = $stmt -> fetch(PDO::FETCH_ASSOC);

    //完了通知メッセージ生成
    if($flag){
      $success = '通知時間の登録が完了しました。';
    }
    //DB接続解除
    unset($pdo);
  }

 ?>
<!DOCTYPE html>

<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>設定 | <?php echo SERVICE_NAME; ?></title>
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
      <div class="col-md-6 offset-3">
        <h3 class="text-center mb-3 mt-3">当日のスケジュール通知時間</h3>

        <div class=<?php if(@$success){echo '"alert alert-success" role="alert"';} ?> >
          <?php if(@$success){echo h(@$success);} ?>
        </div>

        <form method="POST" class="card card-default card-body">
          <select name="delivery_hour">
          <?php
            echo '<option value="99"';
            if(@$result['delivery_hour'] == '99'){
              echo ' selected>しない</option>';
            }else{echo '>しない</option>' ;}

            for($i=0;$i<=23;$i++){
              echo "<option value=".$i ;
              if(@$result['delivery_hour'] == "$i"){echo ' selected>'.h($i).'時</option>';}else{
                echo '>'.h($i).'時</option>';
              }
            }
           ?>

          </select>

          <div class="mb-3 mt-3 d-grid gap-2">
            <input type="submit" value="登録" class="btn btn-success btn-block">
          </div> <!--mb-3-->

          <input type="hidden" name="token" value="<?php echo h($_SESSION['sstoken']); ?>" />

        </form>

      </div>
    </div>
    <hr>
  <footer class="footer">
        <p><?php echo COPYRIGHT; ?></p>
  </footer>
  </div>


  </body>


</html>
