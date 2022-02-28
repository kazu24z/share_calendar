<?php
  require_once('config.php');
  require_once('functions.php');
  session_start();

//セッションデータがなかった時の処理
  if(!isset($_SESSION['USER'])){
    header('Location:'.SITE_URL.'login.php');
    exit;
  }

  //表示要求された時の「月」のカレンダーの日付生成処理
      //初回画面表示時の処理
    //もし＜＞が押された時、生成するインスタンスを1か月前後の初日で生成する。$DateTime = new DateTime('first day of last month');
    if(@$_GET['parameter']!=='' OR @$_GET['search']!==''){
        if(@$_GET['parameter'] == 'last_month'){
          //1か月前のカレンダーを表示
          $last_month_get = $_GET['month'];  //$_GET['month']=yyyy-mm-01
          $DateTime = new DateTime("$last_month_get");

        }elseif(@$_GET['parameter'] == 'next_month'){
          //1か月後のカレンダーを表示

          $next_month_get = $_GET['month'];//$_GET['month']=yyyy-mm-01
          $DateTime = new DateTime("$next_month_get");
        }elseif(preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/',@$_GET['search'])){
          $search_month_get = $_GET['search'];
          $DateTime = new DateTime("$search_month_get");

    }else{
        //日付操作のできるDateTImeクラスのインスタンスを生成
        $DateTime = new DateTime('first day of now');
        //現時点の「年」を変数に格納
      }
    }
      //その年の値を格納　　　//2021
      $year = $DateTime -> format('Y');
      //次月・前月用の年の値を定義
      $year_next = $year;//「<」ボタンに付与するパラメータ
      $year_back = $year;//「>」ボタンに付与するパラメータ
      //現時点の「月」を変数に格納　　　　//7（例）
      $month = $DateTime ->format('n');

      //その月の日数を変数に格納 //31(例)
      $days = $DateTime ->format('t');

      //1日の曜日を変数に格納
      $fstDay_Date = $DateTime -> format('w');

      //その月の日を格納する配列を定義
      //キーは「1」から利用する。後述
      $get_days = array();
      //各日の曜日番号を取得するための配列を定義
      //キーは「1」から利用する。後述
      $get_date_number = array();
      //その月の日付と曜日番号を生成する。
      $DateTime -> modify('-1 day');
      for($i=1; $i<=$days; $i++){
        $get_days[$i] = $i;
        $DateTime -> modify("+1 day");
        $get_date_number[$i] = $DateTime -> format('w');
      }

      //先月の「月」の値を取得 //6（例）
      //※月数が「０」になった場合、DateTiemオブジェクトの仕様上、前年に戻る
        $last_month = $month - 1 ;

      //来月の「月」の値を取得//8月
      if(1 <= $month && $month <= 11){
        $next_month = $month + 1;
      }elseif($month =12){
        $year_next = $year_next + 1;
        $next_month = 1;
      }

    //--------------画面に登録済みの予定を表示させるための処理---------------
      $user = $_SESSION['USER'];
      //DBへ接続
      $pdo = connectDB();

      //ログインユーザーのパートナー登録情報をチェック
      $sql='SELECT user_id_you FROM pair WHERE user_id_me = :user_id_me LIMIT 1';
      $stmt = $pdo -> prepare($sql);
      $stmt -> execute(array(":user_id_me" => $user['id']));
      $result_pair = $stmt -> fetch(PDO::FETCH_ASSOC);

      //その月の予定を格納する配列を定義
      $items = array();
      $items_you = array();
      //その月に登録した予定を取得
      //1日ごとの予定を取得し、items配列に格納
      for($i=1;$i<=$days;$i++){

      $start_time = $year.'-'.$month.'-'.$i.' 00:00:00';
      $end_time   = $year.'-'.$month.'-'.$i.' 23:59:59';

      $sql = 'SELECT user_id,start_at,item_text,label_color FROM item WHERE start_at BETWEEN :the_day_of_start AND :the_day_of_end AND (user_id = :user_id OR user_id = :user_id_pair) ORDER BY start_at';
      $stmt = $pdo -> prepare($sql);
      $stmt -> bindValue(':the_day_of_start',$start_time);
      $stmt -> bindValue(':the_day_of_end',$end_time);
      $stmt -> bindValue(':user_id',$user['id'] );
      $stmt -> bindValue(':user_id_pair',$result_pair['user_id_you']);
      $stmt -> execute();
      $result = $stmt -> fetchAll(PDO::FETCH_ASSOC);

      //その月の各日の開始時刻・予定を含んでいる
      $items[$i] =$result;

      //自分とパートナーの予定がその日に存在するかチェック
      $your_item[$i] = in_array($result_pair['user_id_you'],array_column($items[$i],'user_id'));

      $my_item[$i] = in_array($user['id'],array_column($items[$i],'user_id'));

      //var_dump($items);
      }


      $json_your_item = json_encode($your_item);
      $json_my_item = json_encode($my_item);

 ?>

<!DOCTYPE html>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <title>テスト | <?php echo SERVICE_NAME; ?></title>
    <meta name="description" content="コンセプトコンセプト" />		<!-- 検索結果画面で表示する内容 -->
    <meta name="keywords" content="カレンダー,シェア,予定共有" />	<!-- 検索ヒットキーワード -->
    <link href="css/share_calendar.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">

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
      <div class="mt-3 mb-3">

          <input type="checkbox" id="checkbox"  onchange="clickBtn_index();">パートナーの予定を表示 <!--ここチェック入れたら自動で下のカレンダーに表示されるようにする-->

      </div>
    </div>

        <div class="container">

          <table class="table table-bordered calendar">
            <thead class="">
              <tr class="border-0">
                <th colspan="1" class="text-center fs-4 border-0" >
                  <a class="text-decoration-none text-dark" href="<?php echo SITE_URL.'index.php'.'?parameter=last_month&month='; ?><?php echo h($year_back).'-'.h($last_month).'-1'; ?>">&lt;</a>
                </th>
                <th colspan="5" class="text-center fs-4 border-0"><?php echo h($year).'年'.h($month).'月'; ?></th>
                <th colspan="1" class="text-center fs-4 border-0">
                  <a class="text-decoration-none text-dark" href="<?php echo SITE_URL.'index.php'.'?parameter=next_month&month='; ?><?php echo h($year_next).'-'.h($next_month).'-1'; ?>">&gt;</a>
                </th>
              </tr>
              <tr>
                <th class="text-danger text-center">日</th>
                <th class="text-center">月</th>
                <th class="text-center">火</th>
                <th class="text-center">水</th>
                <th class="text-center">木</th>
                <th class="text-center">金</th>
                <th class="text-primary text-center">土</th>
              </tr>
            </thead>

            <tbody>
            <!--1週間文の列-->
              <tr style="height: 119px;">
                <!--カレンダーを生成-->
                  <!--1日の曜日の所まで空白をつくる。-->
                <?php for($i=0;$i<$fstDay_Date;$i++){
                  echo '<td>&nbsp;</td>';
                }?>
                  <!--日付を生成-->
                <?php for($day=1;$day<=$days;$day++){
                  echo '<td class="ps-1 pt-1 cell-link">'.
                          "<a href='detail.php?ymd=".h($year)."-".h($month)."-".h($day)."'>".
                          h($day).
                          '<div class="scrol_call badges ';if(!empty($items[$day]) && $my_item[$day]=='true'){echo 'scroll';}echo '">';?>
                      <?php for($i=0;$i<count($items[$day]);$i++){
                        echo
                        '<span ';if($items[$day][$i]['user_id']==$result_pair['user_id_you']){echo 'style="display:none;"';}echo ' class="badge text-wrap bg-';if(!empty($items[$day])){echo h($items[$day][$i]['label_color']);}if($items[$day][$i]['user_id']==$result_pair['user_id_you']){echo ' partner_index';}echo '">'.
                          substr(h($items[$day][$i]['start_at']),11,5).' '.h($items[$day][$i]['item_text'])
                        .'</span>'
                      ;} ?>
                <?php echo
                          '</div>'.
                          '</a>'.
                        '</td>';
                  //曜日が土曜日の場合折り返す
                    if($get_date_number[$day] == 6){
                      echo '</tr><tr class="ps-1 pt-1" style="height: 119px;">';
                    }
                  } ?>
                <?php
                    //その月が終わったあまりのテーブルデータを空白で埋める処理
                      for($t=(int)$get_date_number[$days];$t<6;$t++){
                        echo '<td class="ps-1 pt-1">&nbsp;</td>';
                      }
                ?>

              </tr>
            </tbody>
          </table>
      <hr>
      <footer class="footer">
        <p><?php echo COPYRIGHT; ?></p>
      </footer>
    </div> <!--container-->

    <script type="text/javascript">const your_item = <?php echo $json_your_item;?></script>
    <script type="text/javascript">const my_item = <?php echo $json_my_item;?></script>

    <script  src="js/bootstrap.min.js" ></script>
    <script  src="js/main.js" ></script>

  </body>
</html>
