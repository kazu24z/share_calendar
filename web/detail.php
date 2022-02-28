<?php
  require_once('config.php');
  require_once('functions.php');
  session_start();

//セッションデータがなかった時の処理
  if(!isset($_SESSION['USER'])){
    header('Location:'.SITE_URL.'login.php');
    exit;
  }

  //セッションを保持しているユーザー情報を格納
  $user = ($_SESSION['USER']);

  //GETなしでdetail.phpにアクセスされた時、inde.phpに遷移する。
  if(!isset($_GET['ymd'])){
    header('Location:'.SITE_URL.'index.php');
    exit;
  }

  //ymdパラメータの書式チェック(yyyy-mm-ddかどうか)
  if(!preg_match('/^([1-9][0-9]{3})-([1-9]{1}|1[0-2]{1})-([1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})/',$_GET['ymd'])){
    header('Location:'.SITE_URL.'index.php');
    exit;
  }

  //GETで受け取った文字列を「年」「月」「日」で分解
  $the_date = explode('-',$_GET['ymd']);
  //連想配列のキーとなる年月日配列を定義
  $keys = ["year","month","day"];
  //各年月日をキーとした配列を生成
  $date_parts = array_combine($keys,$the_date);//$date_parts['year'],$date_parts['month'],$date_parts['day']

  //yyyy-mm-dd（0あり）に変換
  $date_const = new DateTime($_GET['ymd']);
  $date = $date_const -> format('Y-m-d');
  //その日の開始と終わりの情報を持った変数を定義
  $date_start = $date.' 00:00:00';
  $date_end = $date.' 23:59:59';

  //該当する日付に登録した予定を表示
    //DBに接続
    $pdo = connectDB();

    //パートナーのユーザーIDを取得
    $sql = 'SELECT user_id_me ,user_id_you FROM pair WHERE user_id_me = :user_id_me LIMIT 1';
    $stmt = $pdo -> prepare($sql);
    $stmt ->bindValue('user_id_me',$user['id']);
    $stmt ->execute();
    $pair_table = $stmt -> fetch(PDO::FETCH_ASSOC);

    //自分とパートナーのユーザー名を取得
    $sql = 'SELECT id,user_name FROM user WHERE (id = :user_id_me OR id = :user_id_you) ';
    $stmt = $pdo -> prepare($sql);
    $stmt ->bindValue('user_id_me',$pair_table['user_id_me']);
    $stmt ->bindValue('user_id_you',$pair_table['user_id_you']);
    $stmt ->execute();
    $user_table = $stmt -> fetchAll(PDO::FETCH_KEY_PAIR);


    //GETで受け取った値と一致する「開始日」のデータを持つ行を取得
    //$sql = "SELECT * FROM item WHERE start_at >= '2021-07-01 00:00:00' AND start_at <='2021-07-01 23:59:59'";
    $sql = 'SELECT * FROM item WHERE start_at BETWEEN :date_start AND :date_end AND( user_id = :user_id_me OR user_id =  :user_id_you) ORDER BY start_at';
    $stmt = $pdo -> prepare($sql);
    $stmt -> bindValue(':user_id_me',$user['id']);
    $stmt ->bindValue(':user_id_you',$pair_table['user_id_you']);
    $stmt -> bindValue(':date_start',$date_start);
    $stmt -> bindValue(':date_end',$date_end);
    $stmt -> execute();
    $result = $stmt -> fetchAll(PDO::FETCH_ASSOC);

    unset($pdo);

    //$resultの配列要素数を取得
    $array_number = count($result);
    //開始・終了の「時刻」を入れる配列を定義
      $start_time = array();
      $end_time = array();

  //var_dump(count($result));
    //その日の予定が何もなかった時の処理（画面表示文章を生成）
    if(empty($result)){
      $no_schedule_message = '<p class="alert alert-dark mt-5">予定が登録されていません。登録は<a href="add.php">こちら</a></p>';
    }

?>

<!DOCTYPE html>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title><?php echo h($date_parts['year']).'年'.h($date_parts['month']).'月'.h($date_parts['day']).'日' ?>の予定 | <?php echo SERVICE_NAME; ?></title>
    <meta name="description" content="コンセプトコンセプト" />		<!-- 検索結果画面で表示する内容 -->
    <meta name="keywords" content="カレンダー,シェア,予定共有" />	<!-- 検索ヒットキーワード -->
    <link href="css\bootstrap.min.css" rel="stylesheet">
    <link href="css/share_calendar.css" rel="stylesheet">
    <link href="css\all.min.css" rel="stylesheet">
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

  <body>
    <div class="container">
      <div class="row">
        <div class="col-md-6 offset-md-3">

      <?php
       if(!@$no_schedule_message){
         echo
          '<input type="checkbox" id="checkbox" onchange="clickBtn_detail();">パートナーの予定を表示'
          //ここチェック入れたら自動で下に表示されるようにする
          ;
        }
      ?>
          <h3 class="text-center mb-3 mt-3"><?php echo h($date_parts['year']).'年'.h($date_parts['month']).'月'.h($date_parts['day']).'日' ?></h3>

    <!--ここから繰り返し文と条件分岐で予定を表示-->
      <?php if(!empty($result)){
        echo
        '<table class="table">'.
          '<thead>'.
          '<tr>'.
          '<th>色</th>'.
          '<th style="width: 20%;">時刻</th>'.
          '<th>人</th>'.
          '<th style="width: 40%;">予定</th>'.
          '<th></th>'.
          '</tr>'.
          '</thead>'.
          '<tbody>'; ?>
        <?php for($i=0;$i<count($result);$i++){
          echo
          '<tr class="';if($result[$i]['user_id'] == $pair_table['user_id_you']){echo 'partner_detail" style="display:none;">';}else{echo '">';}
          echo
          '<td>'.'<button disabled class="btn btn-'.h($result[$i]['label_color']).'"></button>'.'</td>'.
          '<td>'.substr(h($result[$i]['start_at']),11,5).'～'.substr(h($result[$i]['end_at']),11,5).'</td>'.
          '<td>'.h($user_table[$result[$i]['user_id']]).'</td>'.
          '<td>'.h($result[$i]['item_text']).'</td>'.
          '<td>';
          if($result[$i]['user_id']==$user['id']){
            echo
          '<a href="edit.php?id='.h($result[$i]['id']).'&user_id='.h($result[$i]['user_id'])."\">編集</a>".' <a href="delete.php?id='.h($result[$i]['id']).'&ymd='.h($_GET['ymd'])."\">削除</a>";
          }
          echo
          '</td>'.
          '</tr>';
            }?>
          <?php
            echo
            '</tbody>'.
            '</table>'.
            '<div class="d-inline-block p-2">'.
            '<a href="index.php">戻る</a>'.
            '</div>'  ;
           ?>
  <?php }else{
          echo $no_schedule_message;
        }?>

          <hr>
          <footer class="footer">
            <p><?php echo COPYRIGHT; ?></p>
          </footer>
        </div><!--class=col-md-6 offset-md-3-->
      </div><!--class=row-->
    </div> <!--container-->
    <script src="js/bootstrap.min.js" ></script>
    <script src="js/main.js" ></script>
  </body>
</html>
