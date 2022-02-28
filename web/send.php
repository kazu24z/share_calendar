<?php

        require_once('config.php');
        require_once('functions.php');
        session_start();

      //GETもPOSTも拒否
      if($_SERVER['REQUEST_METHOD'] == 'GET' OR $_SERVER['REQUEST_METHOD'] == 'POST'){
        echo '<html><head><meta charset="utf-8"></head><body>不正なアクセスです。</body></html>';
        exit;

      }else{

        //DB接続
        $pdo = connectDB();

        //ユーザー情報を取得(ID,user_name,user_mail,delivery_hour)
        $sql = "SELECT id,user_name,user_email,delivery_hour FROM user WHERE delivery_hour = :delivery_hour";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':delivery_hour',date('G'));
        $stmt -> execute();

        while($result_user = $stmt -> fetch(PDO::FETCH_ASSOC)){
          //ユーザーのその日の予定を取得
            $sql = "SELECT user_id,start_at,end_at,item_text FROM item WHERE start_at BETWEEN :start_at AND :end_at AND user_id = :user_id ORDER BY start_at ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':start_at',date("Y-m-d").' 00:00');
            $stmt -> bindValue(':end_at',date("Y-m-d").' 23:59');
            $stmt -> bindValue(':user_id',$result_user['id']);
            $stmt -> execute();

            //そのユーザーの当日の予定を全部引っ張る
            $result_item = $stmt -> fetchAll(PDO::FETCH_ASSOC);
            if($result_item){

            //宛先を指定
            $to = $result_user['user_email'];

            //タイトルを変数に格納
            $subject = '【通知】本日の予定';

            //メール本文を変数に格納
              $mail_body .= '<html>
                                <body>
                              				<h1>本日の予定をお知らせいたします。</h1>
                              				   <table border="1" style="border-collapse: collapse">
                              				   <thead><tr>
                              					    <th style="width:100px;">開始時刻</th>
                                            <th style="width:100px;">終了時刻</th>
                                            <th style="width:100px;">予定</th>
                                          </tr>
                                  </thead>
                                  <tbody>';

            for($i=0;$i<count($result_item);$i++){
                @$table_data .= '<tr><td align="center" style="width:100px;">'.substr($result_item[$i]['start_at'],11,5).'</td>'.
                               '<td align="center" style="width:100px;">'.substr($result_item[$i]['end_at'],11,5).'</td>'.
                               '<td align="center" style="width:100px;">'.$result_item[$i]['item_text'].'</td></tr>';

                   }

            $mail_body .= $table_data;
            $mail_body .=  '</tbody>
                                </table>
                                  </body>
                                    </html>';
            $header = "From: ".ADMIN_MAIL_ADDRESS;
            $header .= PHP_EOL;
            $header .= "Content-type: text/html; charset=UTF-8";

            //文字をエンコード
            mb_language('Japanese/ja');
            mb_internal_encoding('UTF-8');

            //ユーザーにメールを送信
            mb_send_mail($to,$subject,$mail_body,$header);
           exit;
          }

          unset($pdo);
          exit;

        }

     }


 ?>