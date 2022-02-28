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
    //CSRF対策 トークン発行
    setToken();

  }else{

    checkToken();

      //入力された値を変数に格納
      $start_at = $_POST['start_at'];
      $end_at = $_POST['end_at'];
      $item_text = $_POST['item_text'];

      //検索結果を格納する変巣(初期値０)
      $count = 0;

      //入力チェック
      $err = array();
      if($start_at != '' && !preg_match('/\d{4}-\d{2}-\d{2}/u',$start_at)){
        $err['start_at'] = '入力形式が不正です。';
      }

      if($end_at != '' && !preg_match('/\d{4}-\d{2}-\d{2}/u',$end_at)){
        $err['end_at'] = '入力形式が不正です。';
      }

      if($start_at && $end_at != '' && $start_at > $end_at){
        $err['start>end'] = '検索条件が不正です。';
      }

      if(mb_strlen($item_text) > 100){
        $err['item_text'] = '100文字以下で入力してください。';
      }
      //エラーがなかった場合
      if(empty(@$err)){

        //セッションを変数で受け取る
        $user = $_SESSION['USER'];

        //POSTを変数で受ける
        //開始日時の項目入力有無で分岐
        if($start_at == ''){
          $start_at_A = '0000-01-01 00:00';
          $start_at_B = '9999-12-31 23:59';
        }else{
          $start_at_A = $start_at.' 00:00';
          $start_at_B = '9999-12-31 23:59';
        }

        //終了日時の項目入力有無で分岐
        if($end_at == ''){
          $end_at_A = '0000-01-01 00:00';
          $end_at_B = '9999-12-31 23:59';
        }else{
          $end_at_A = '0000-01-01 00:00';
          $end_at_B = $end_at.' 23:59';
        }

        //DB接続
        $pdo = connectDB();
        $sql = "SELECT user_id,item_text,start_at,end_at
                FROM item
                WHERE user_id = :user_id
                  AND
                      (  (start_at BETWEEN :start_at_A AND :start_at_B) AND (end_at BETWEEN :end_at_A AND :end_at_B) AND  item_text LIKE :item_text   )
                ORDER BY start_at";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':user_id',$user['id']);
        $stmt -> bindValue(':start_at_A',$start_at_A);
        $stmt -> bindValue(':start_at_B',$start_at_B);
        $stmt -> bindValue(':end_at_A',$end_at_A);
        $stmt -> bindValue(':end_at_B',$end_at_B);
        $stmt -> bindValue(':item_text','%'.$item_text.'%');
        $stmt -> execute();
        $result_item = $stmt -> fetchAll(PDO::FETCH_ASSOC);

        $count = count($result_item);

        //ユーザー名を取得
        $sql = "SELECT user_name FROM user WHERE id = :user_id";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue('user_id',$user['id']);
        $stmt -> execute();
        $result_user = $stmt -> fetch(PDO::FETCH_ASSOC);

        //DB解除
        unset($pdo);

    }//入力チェック後

}//GET・POST分岐

 ?>

<!DOCTYPE html>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title>検索 | <?php echo SERVICE_NAME; ?></title>
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
          <h3 class="text-center mb-3 mt-3">予定の検索</h3>
          <!--フォーム欄-->
          <form method="POST" class="card card-default card-body" novalidate>
              <div class="mb-3">
                <!--開始日の入力フォーム-->
                  <label for="start_at" class="form-label">開始日</label>
                  <input type="date" id="start_at" name="start_at" placeholder="開始日" value="<?php echo h(@$_POST['start_at']);?>" class="form-control  <?php if(@$err['start_at']||@$err['start>end']){echo 'is-invalid';}?>">
                <!--エラー表示-->
                  <div class="p-1 <?php if(@$err['start_at']  || @$err['start>end'] != ''){echo 'invalid-feedback';} ?>">
                    <?php if(@$err['start_at'] != ''){echo h(@$err['start_at']);}elseif(@$err['start>end'] != ''){echo h(@$err['start>end']);}?>
                  </div>
              </div> <!--開始字入力フォーム終わり-->

              <!--終了日の入力フォーム-->
              <div class="mb-3">
                  <label for="end_at" class="form-label">終了日</label>
                  <input type="date" id="end_at" name="end_at" placeholder="終了日" value="<?php echo @$_POST['end_at'];?>" class="form-control  <?php if(@$err['end_at']||@$err['start>end']){echo 'is-invalid';}?>">
                  <!--エラー表示-->
                  <div class="p-1 <?php if(@$err['end_at']  || @$err['start>end'] != ''){echo 'invalid-feedback';} ?>">
                    <?php if(@$err['end_at'] != ''){echo h(@$err['end_at']);}elseif(@$err['start>end'] != ''){echo h(@$err['start>end']);}?>
                  </div>
              </div> <!--終了日の入力フォーム終わり-->

              <!--キーワードの入力フォーム-->
              <div class="mb-3">
                  <label for="start_at" id="item_text" class="form-label">キーワード</label>
                  <input type="text" id="item_text" name="item_text"  value="<?php echo @$_POST['item_text'];?>" class="form-control  <?php if(@$err['item_text']){echo 'is-invalid';}?>">
                  <!--エラー表示-->
                  <div class="p-1 <?php if(@$err['item_text'] != ''){echo 'invalid-feedback';} ?>">
                    <?php if(@$err['item_text'] != ''){echo h(@$err['item_text']);}?>
                  </div>
              </div> <!--予定の入力フォーム終わり-->

              <!--検索ボタン-->
              <div class="mb-3 mt-3 d-grid gap-2">
                <input type="submit" value="検索" class="btn btn-success btn-block">
              </div> <!--mb-3-->

              <input type="hidden" name="token" value="<?php echo h($_SESSION['sstoken']); ?>" />

          </form>

          <div class="container mb-3 mt-4">
            <p><?php if(@$err != '' ){echo '検索結果:'.h(@$count).'件';} ?></p>
          </div>

        <?php if(!empty(@$result_item) && empty($err)){
              echo
              '<table class="table">
                <thead>
                  <tr>
                    <th scope="col">開始日時</th>
                    <th scope="col">終了日時</th>
                    <th scope="col">人</th>
                    <th scope="col">予定</th>
                  </tr>
                </thead>
                <tbody>';

              for($i=0;$i<count(@$result_item);$i++){
              echo '<tr>';
              echo '<td>'.h(@$result_item[$i]['start_at']).'</td>';
              echo '<td>'.h(@$result_item[$i]['end_at']).'</td>';
              echo '<td>'.h(@$result_user['user_name']).'</td>';
              echo '<td>'.h(@$result_item[$i]['item_text']).'</td>';
              echo '</tr>';
              }

          }elseif($_SERVER['REQUEST_METHOD']!='GET' && empty(@$result_item) && empty($err)){
            echo
            '<div class="alert alert-danger" role="alert">
              予定が見つかりませんでした。
              </div>';
          } ?>


            </tbody>
          </table>

          <div class="d-inline-block p-2">
            <a href="index.php">戻る</a>
          </div>

        <?php if($_SERVER['REQUEST_METHOD']!='GET'){
          echo '
          <div class="d-inline-block p-2">
          <a href="search.php">検索条件をクリア</a>
          </div>';

        } ?>



          <hr>
          <footer class="footer">
            <p><?php echo COPYRIGHT; ?></p>
          </footer>


        </div><!--class=col-md-6 offset-md-3-->
      </div><!--class=row-->
    </div> <!--container-->


  </body>


</html>
