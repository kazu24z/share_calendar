
//index.phpにアクセスした時に発動
if(document.cookie.indexOf('partner=item;') != -1){
    //cookieがあった時の処理
          //index.phpのHTML情報を定数に格納する
            //パートナーの予定の<span>タグを対象にする
            	const p_in = document.getElementsByClassName('partner_index');
              const px_in = Array.prototype.slice.call(p_in);
              //パートナーの予定の<span>タグを対象にする
              const p_d = document.getElementsByClassName('partner_detail');
              const px_d = Array.prototype.slice.call(p_d);

            //カレンダー1マス内の<div>タグのscrollクラスを対象にする
              const s = document.getElementsByClassName('scrol_call');
              const sx = Array.prototype.slice.call(s);

              //チェックボックスを対象にする
              const c = document.getElementById('checkbox');

          //index.phpにあるチェックボックスを有効化する
              c.checked = true;
          //チェックボックスが有効化された時の処理
          if(c.checked){
            //チェックボックが有効になった時の処理
            //パートナーの予定があるマスにスクロールバーを表示
              for(let t=0;t<sx.length;t++){
                  if(your_item[t+1]){
                  sx[t].classList.add('scroll');
                  }
              }
              //パートナーの予定を表示
              for(let i=0;i<px_in.length;i++){
                px_in[i].style.display = 'flex';
              }
              //パートナーの予定を表示
              for(let i=0;i<px_d.length;i++){
                px_d[i].style.display = 'table-row';
              }

          }
}


//index.phpのチェックボックスの処理
function clickBtn_index(){
      //チェックボックスを対象にする
            const c = document.getElementById('checkbox');
          //パートナーの予定の<span>タグを対象にする
          	const p_in = document.getElementsByClassName('partner_index');
            const px_in = Array.prototype.slice.call(p_in);

          //カレンダー1マス内の<div>タグのscrollクラスを対象にする
            const s = document.getElementsByClassName('scrol_call');
            const sx = Array.prototype.slice.call(s);

          	if(c.checked){
              //チェックボックが有効になった時の処理
              //パートナーの予定があるマスにスクロールバーを表示
                for(let t=0;t<sx.length;t++){
                    if(your_item[t+1]){
                    sx[t].classList.add('scroll');
                  }
              }
                //パートナーの予定を表示
                for(let i=0;i<px_in.length;i++){
                  px_in[i].style.display = 'flex';
                }
              //cookieを発行
              document.cookie = "partner=item; path=/share_calendar; max-age=3600";

        }else{
              //チェックボックスが無効になった時の処理
                //パートナーの予定を非表示
              for(let i=0;i<px_in.length;i++){
                px_in[i].style.display = 'none';
              }
                //自分の予定がないマスのスクロールバーを非表示
              for(let t=0;t<sx.length;t++){
                if(!my_item[t+1]){
                  sx[t].classList.remove('scroll');
                }
              }
              //cookieを削除
              document.cookie = "partner=; path=/share_calendar; max-age=0";


        }
}




//チェックボックスの処理
function clickBtn_detail(){
    //チェックボックスを対象にする
      const c = document.getElementById('checkbox');
    //パートナーの予定の<span>タグを対象にする
    	const p_d = document.getElementsByClassName('partner_detail');
       const px_d = Array.prototype.slice.call(p_d);

    	if(c.checked){
        //チェックボックが有効になった時の処理
          //パートナーの予定を表示
          for(let i=0;i<px_d.length;i++){
            px_d[i].style.display = 'table-row';
            //cookieを発行
            document.cookie = "partner=item; path=/share_calendar; max-age=3600";
          }
      }else{
        //チェックボックスが無効になった時の処理
          //パートナーの予定を非表示
        for(let i=0;i<px_d.length;i++){
          px_d[i].style.display = 'none';
        }
        //cookieを削除
        document.cookie = "partner=; path=/share_calendar; max-age=0";
    	}
}
