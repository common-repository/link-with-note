<?php

if ( ! defined( 'ABSPATH' ) ) exit;

#プラグインの初期設定
function lwn_add_plugin_first() {
    require_once(ABSPATH. 'wp-admin/includes/upgrade.php');
    #DB作成
    global $wpdb;
    global $lwn_db_version;
    $lwn_db_version = '1.0';
    $lwn_table_name = $wpdb->prefix . 'lwn_note_db';
    $lwn_charset_collate = $wpdb->get_charset_collate();
    $lwn_sql = "CREATE TABLE $lwn_table_name (
        id int NOT NULL AUTO_INCREMENT,
        import_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        content_key varchar(50) NOT NULL,
        content_url text NOT NULL,
        creator_info text NOT NULL,
        public_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        title varchar(1000) DEFAULT '' NOT NULL,
        body text NOT NULL,
        comment_count varchar(255) DEFAULT '' NOT NULL,
        like_count varchar(255) DEFAULT '' NOT NULL,
        thumb_url varchar(1000) DEFAULT '' NOT NULL,
        indexs text NOT NULL,
        price varchar(255) DEFAULT '' NOT NULL,
        hashtags text NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY (content_key)
    ) $lwn_charset_collate;";
    dbDelta($lwn_sql);
    #オプション値
    add_option('lwn_db_ver', $lwn_db_version);
    add_option('lwn_thumb_view', 1);
    add_option('lwn_title_count', 40);
    add_option('lwn_index_line', 5);
    add_option('lwn_text_count', 60);
    add_option('lwn_hashtag_count', 60);
    add_option('lwn_like_view', 1);
    add_option('lwn_comment_view', 1);
    add_option('lwn_creator_info', 1);
    add_option('lwn_design', 'lwn-blog-card');
    add_option('lwn_title_view', 1);
    add_option('lwn_new_tab', 1);
}

#プラグインがアンインストールされた時にDB削除
function lwn_plugin_uninstall() {
    global $wpdb;
    $lwn_table_name = $wpdb->prefix . 'lwn_note_db';
    $wpdb->query("DROP TABLE IF EXISTS $lwn_table_name");
    delete_option('lwn_db_ver');
    delete_option('lwn_thumb_view');
    delete_option('lwn_title_count');
    delete_option('lwn_index_line');
    delete_option('lwn_text_count');
    delete_option('lwn_hashtag_count');
    delete_option('lwn_like_view');
    delete_option('lwn_comment_view');
    delete_option('lwn_creator_info');
    delete_option('lwn_design');
    delete_option('lwn_title_view');
    delete_option('lwn_new_tab');
}

#メニュー開いた時の処理
function lwn_add_plugin_admin_menu() {
    wp_enqueue_style("lwn_setting", plugins_url('style/lwn-config.css', __FILE__ ));
    add_menu_page(
          'Link With Note', // page_title
          'Link With Note', // menu_title
          'manage_options', // capability
          'link-with-note', // menu_slug
          'lwn_display_plugin_admin_page', // function
          '', // icon_url
          81 // position
    );
    register_setting('lwn_setting','lwn_thumb_view');
    register_setting('lwn_setting','lwn_title_count');
    register_setting('lwn_setting','lwn_index_line');
    register_setting('lwn_setting','lwn_text_count');
    register_setting('lwn_setting','lwn_hashtag_count');
    register_setting('lwn_setting','lwn_like_view');
    register_setting('lwn_setting','lwn_comment_view');
    register_setting('lwn_setting','lwn_creator_info');
    register_setting('lwn_setting','lwn_design');
    register_setting('lwn_setting','lwn_title_view');
    register_setting('lwn_setting','lwn_new_tab');
}
add_action('admin_menu', 'lwn_add_plugin_admin_menu'); //メニュー開いた時の処理

#ラジオボタンのchecked判定
#リンクデザイン
function lwn_design_checked($template_name){
    $template_name = basename($template_name);
    if($template_name == esc_html(get_site_option('lwn_design'))){
        echo esc_html('checked');
    }
}
#チェックボックスのchecked判定
function lwn_checkbox_checked($flag){
    if(!empty($flag)){
        echo esc_html('checked');
    }
}

#ファイル読み込み-フロントエンドのみ
function lwn_add_files() {
    wp_enqueue_style("lwn_config", plugins_url('template/'.esc_html(get_site_option('lwn_design')).'/'.esc_html(get_site_option('lwn_design')).'.css', __FILE__ ));
    wp_enqueue_style('dashicons');
    }
add_action('wp_enqueue_scripts', 'lwn_add_files');

#ショートコード表示
function lwn_view($atts) { 
    extract(shortcode_atts(array(
        'key' => '',
    ), $atts));

    #db接続
    global $wpdb;
    $lwn_table_name = $wpdb->prefix . 'lwn_note_db';
    $wpdb->query( $wpdb->prepare(
    	"SELECT * FROM $lwn_table_name WHERE content_key = '%s'",
        $key
    ));
    $lwn_db_res = json_decode(json_encode($wpdb), true);
    
    #ショートコードのnoteIDが間違っていたら表示しない
    if(!empty($lwn_db_res["last_result"])){

        #クリエイター情報
        $creator_info = unserialize($lwn_db_res["last_result"][0]["creator_info"]);
        $creator_info_id = $creator_info["id"];
        $creator_info_urlname = $creator_info["urlname"];
        $creator_info_nickname = $creator_info["nickname"];
        $creator_info_note_count = $creator_info["note_count"];
        $creator_info_following_count = $creator_info["following_count"];
        $creator_info_follower_count = $creator_info["follower_count"];
        $creator_info_created_at = date('Y-m-d',  strtotime($creator_info["created_at"]));
        $creator_info_user_profile_image_path = $creator_info["user_profile_image_path"];
        $creator_info_store_url = $creator_info["store_url"];
        $creator_info_profile = $creator_info["profile"];

        #クリエイターアイコンurlのformatパラメータをpngに書き換える
        if(strpos($creator_info_user_profile_image_path,'d2l930y2yx77uc') === false){
            $creator_info_user_profile_image_path=urldecode($creator_info_user_profile_image_path);
            $url = parse_url($creator_info_user_profile_image_path);
            if(!empty($url['query'])){
                parse_str($url['query'], $parms);
                $parms["format"] = 'png';
                $url['query'] = http_build_query($parms);
                $creator_info_user_profile_image_path = $url["scheme"]."://".$url["host"].$url["path"]."?".$url["query"];    
            } else {
                $creator_info_user_profile_image_path = $creator_info_user_profile_image_path."&format=png";
            }
        }

        #記事情報
        $note_content_key = $lwn_db_res["last_result"][0]["content_key"];
        $note_content_url = $lwn_db_res["last_result"][0]["content_url"];
        $public_date = date('Y/m/d h:m',  strtotime($lwn_db_res["last_result"][0]["public_date"]));
        $note_title = $lwn_db_res["last_result"][0]["title"];
        $note_body = strip_tags($lwn_db_res["last_result"][0]["body"]);
        $note_price = $lwn_db_res["last_result"][0]["price"];

        #サムネ表示
        if(esc_html(get_site_option('lwn_thumb_view')) == 1){
            $note_thumb = '
            <div class="lwn-link-img">
                <img src="'.$lwn_db_res["last_result"][0]["thumb_url"].'">
            </div>
            ';
        } else {
            $note_thumb = '';
        }

        #タイトル表示文字数指定
        $limit = esc_html(get_site_option('lwn_title_count'));
        if($limit == 0){
            $note_title = '';
        } elseif(mb_strlen($note_title) > $limit) { 
            $note_title = mb_substr($note_title, 0, $limit);
            $note_title = '<p class="lwn-link-title">'.$note_title.'･･･</p>';
        } else { 
            $note_title = mb_substr($note_title, 0, $limit);
            $note_title = '<p class="lwn-link-title">'.$note_title.'</p>';
        }

        #本文表示文字数指定
        $limit = esc_html(get_site_option('lwn_text_count'));
        if($limit == 0){
            $note_body = '';
        }elseif(mb_strlen($note_body) > $limit) { 
            $note_body = mb_substr($note_body, 0, $limit);
            $note_body = '<p class="lwn-link-text">'.$note_body.'･･･</p>';
        } else { 
            $note_body = mb_substr($note_body, 0, $limit);
            $note_body = '<p class="lwn-link-text">'.$note_body.'</p>';
        }

        #目次のリスト化
        $note_indexs = unserialize($lwn_db_res["last_result"][0]["indexs"]);
        $note_index_list = '';
        $moq_limit = (int)min(count($note_indexs), esc_html(get_site_option('lwn_index_line')));
        if($moq_limit == 0){
            $note_index_list = '';
        }else{
            $note_index_list .= '<div class="lwn-link-index"><ul>';
            for ($count = 0; $count < $moq_limit; $count++){
                $note_index_list .= '<li>'.$note_indexs[$count]["body"].'</li>';
            }
            $note_index_list .= '</ul></div>';
        }

        #ハッシュタグのリスト化＆表示個数指定
        $note_hashtags = unserialize($lwn_db_res["last_result"][0]["hashtags"]);
        $note_hashtag_list = '';
        $moq_limit = (int)min(count($note_hashtags), esc_html(get_site_option('lwn_hashtag_count')));
        if($moq_limit == 0){
            $note_hashtag_list = '';
        }else{
            $note_hashtag_list .='<p class="lwn-link-hashtag">';
            for ($count = 0; $count < $moq_limit; $count++){
                $note_hashtag_list .= '<span class="hashtag_list">'.$note_hashtags[$count]["hashtag"]["name"].'</span>';
            }
            $note_hashtag_list .='</p>';
        }

        #コメント数表示
        if(esc_html(get_site_option('lwn_comment_view')) == 1){
            $note_comment_count = '<p class="lwn-link-comment"><span class="dashicons dashicons-testimonial"></span>'.$lwn_db_res["last_result"][0]["comment_count"].'</p>';
        } else {
            $note_comment_count = '';
        }

        #スキ数表示
        if(esc_html(get_site_option('lwn_like_view')) == 1){
            $note_like_count = '<p class="lwn-link-like"><span class="dashicons dashicons-heart"></span>'.$lwn_db_res["last_result"][0]["like_count"].'</p>';
        } else {
            $note_like_count = '';
        }

        #クリエイター情報表示
        if(esc_html(get_site_option('lwn_creator_info')) == 1){
            $creator_info_field = '
            <div class="lwn-card-creator">
                <img src="'.$creator_info["user_profile_image_path"].'">
                <p>'.$creator_info["nickname"].'</p>
                <p>'.$public_date.'</p>
            </div>
            ';
        } else {
            $creator_info_field = '';
        }

        #新しいタブで開く
        if(esc_html(get_site_option('lwn_new_tab')) == 1){
            $target_blank = 'target="_blank"';
        } else {
            $target_blank = '';
        }

        #置換
        $search = array(
            '{{creator_info_field}}',
            '{{content_key}}',
            '{{content_url}}',
            '{{public_date}}',
            '{{title}}',
            '{{body}}',
            '{{comment_count}}',
            '{{like_count}}',
            '{{thumb}}',
            '{{index_list}}',
            '{{price}}',
            '{{hashtag_list}}',
            '{{target_blank}}'
        );
        $replace = array(
            $creator_info_field,
            $note_content_key,
            $note_content_url,
            $public_date,
            $note_title,
            $note_body,
            $note_comment_count,
            $note_like_count,
            $note_thumb,
            $note_index_list,
            $note_price,
            $note_hashtag_list,
            $target_blank
        );
        $lwn_template = file_get_contents(plugins_url('template/'.esc_html(get_site_option('lwn_design')).'/'.esc_html(get_site_option('lwn_design')).'.txt', __FILE__ ));
        
        $lwn_link = str_replace($search, $replace, $lwn_template);

        return $lwn_link;
    }
}
add_shortcode('lwn', 'lwn_view');


//エラーメッセージ
function lwn_error_handle($http_status_code) {
    switch((string)$http_status_code){
        //note取込時のエラー処理
        case "0":
            echo esc_textarea("取り込み完了！");
            break;
        case "400":
            echo esc_textarea("【httpステータスコード：".$http_status_code."】 データを取得出来ませんでした、入力値を確認して下さい。");
            break;
        case "403":
            echo esc_textarea("【httpステータスコード：".$http_status_code."】 アクセスが許可されていません、理由は管理者に問い合わせて下さい");
            break;
        case "404":
            echo esc_textarea("【httpステータスコード：".$http_status_code."】 指定したページが見つかりませんでした、URLが間違っているかサーバーが落ちている可能性があります");
            break;
        case "500":
            echo esc_textarea("【httpステータスコード：".$http_status_code."】 指定したページのサーバーにエラーがあります、管理者による復旧をお待ち下さい");
            break;
        case "503":
            echo esc_textarea("【httpステータスコード：".$http_status_code."】 サービスは利用できません、しばらく経ってからアクセスして下さい");
            break;
        case "999":
            echo esc_textarea("【httpステータスコード：".$http_status_code."】 タイムエラー or URLが間違っています");
            break;
        case "not_found":
            echo esc_textarea("【APIエラーコード：".$http_status_code."】 指定のnoteが見つかりません、入力値を確認して下さい");
            break;
        //note削除字のエラー処理
        case "1":
            echo esc_textarea("noteを削除しました");
            break;
        default:
            echo esc_textarea("エラーコード：".$http_status_code."】 何らかのエラーによって処理が完了できませんでした");
    }
}

//取込済みのnote一覧を表示する
function lwn_note_list($_get_page) {
    global $wpdb;
    $lwn_table_name = $wpdb->prefix . 'lwn_note_db';
    $print_no = 15; //一度に表示する行数
    //ページネーション
    $lwn_db_count = $wpdb->get_results("SELECT COUNT(*) FROM $lwn_table_name");
    $lwn_db_count = get_object_vars($lwn_db_count[0]);
    $max_page = ceil($lwn_db_count["COUNT(*)"] / $print_no);
    $purint_start_id = ($_get_page - 1) * $print_no;
    $wpdb->query( $wpdb->prepare(
    	"SELECT * FROM $lwn_table_name ORDER BY id DESC LIMIT %d, %d",
        $purint_start_id, $print_no
    ));
    $note_list = json_decode(json_encode($wpdb), true);
    return [$max_page, $note_list["last_result"]];
}

#note取り込み
function lwn_note_create(){
    #日付取得
    $time_stamp = date("Y-m-d H:i:s");
    #noteAPIのリクエストurl生成
    $content_key = esc_url_raw(rtrim($_POST["import_note_key"], '/'));
    $content_key = substr($content_key, strrpos($content_key, '/') + 1);
    $note_url = "https://note.com/api/v1/notes/".$content_key;
    //コンテンツ取得
    $note_url_res = wp_remote_get($note_url);
    $note_json = mb_convert_encoding($note_url_res["body"], 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $note_arr = json_decode($note_json,true);
    #エラーハンドリング
    if (is_wp_error($note_url_res)) { // リクエストがエラーかどうかを判定する
        $error_message = $note_url_res->get_error_message(); // エラーメッセージを取得
        header('Location: '.admin_url().'admin.php?page=link-with-note&error_code='.$error_message);
    } elseif(wp_remote_retrieve_response_code($note_url_res) !== 200) { // レスポンスが正常(200)かどうかを判定する
        header('Location: '.admin_url().'admin.php?page=link-with-note&error_code='.wp_remote_retrieve_response_code($note_url_res));
    } elseif (!empty($note_arr["error"])) { //noteのcontent_keyが間違っている場合
        header('Location: '.admin_url().'admin.php?page=link-with-note&error_code='.$note_arr["error"]["code"]);
    } else { //正常にAPIから取得できた場合
        $content_url = $note_arr["data"]["note_url"];
        $creator_info = serialize($note_arr["data"]["user"]);
        $public_date = $note_arr["data"]["publish_at"];
        $title = $note_arr["data"]["name"];
        $body = $note_arr["data"]["body"];
        $comment_count = $note_arr["data"]["comment_count"];
        $like_count = $note_arr["data"]["like_count"];
        #サムネは記事/動画/音楽で格納場所が違うのでそれぞれ処理
        if(!empty($note_arr["data"]["eyecatch"])){
            $thumb_url = $note_arr["data"]["eyecatch"]."&format=png";
        } elseif(strpos($note_arr["data"]["external_url"], 'youtube') !== false){ #youtubeの動画が埋まってたらyoutubeのサムネを取得
            $youtube_content_key = rtrim($note_arr["data"]["external_url"], '/');
            $youtube_content_key = substr($youtube_content_key, strrpos($youtube_content_key, '/') + 1);
            $thumb_url = "https://i.ytimg.com/vi/".$youtube_content_key."/0.jpg";
        } elseif(!empty($note_arr["data"]["audio"]["cover"])){ #音楽ファイル用のサムネ確認
            $thumb_url = $note_arr["data"]["audio"]["cover"];
        } elseif(!empty($note_arr["data"]["audio"])){
            $thumb_url = "https://d291vdycu0ht11.cloudfront.net/nuxt/production/img/default_sound.28ba02e.png";
        } else {
            $thumb_url = "https://d291vdycu0ht11.cloudfront.net/nuxt/production/img/note_empty.ac2cdb0.svg";
        }
        if(!empty($note_arr["data"]["index"])){ #見出しデータがあるかチェック
            $index = serialize($note_arr["data"]["index"]);
        } else {
            $index = serialize(array());
        }
        $price = $note_arr["data"]["price"];
        if(!empty($note_arr["data"]["hashtag_notes"])){ #ハッシュタグデータがあるかチェック
            $hashtags = serialize($note_arr["data"]["hashtag_notes"]);
        } else {
            $hashtags = serialize(array());
        }
        #データベースに保存
        global $wpdb;
        $lwn_table_name = $wpdb->prefix . 'lwn_note_db';
        $wpdb->query( $wpdb->prepare( 
            "INSERT IGNORE INTO $lwn_table_name (import_date, content_key, content_url, creator_info, public_date, title, body, comment_count, like_count, thumb_url, indexs, price, hashtags) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", 
            $time_stamp, $content_key, $content_url, $creator_info, $public_date, $title, $body, $comment_count, $like_count, $thumb_url, $index, $price, $hashtags
        ));
        header('Location: '.admin_url().'admin.php?page=link-with-note&error_code=0');
    }
}

#noteDB削除
function lwn_note_delete(){
    global $wpdb;
    $lwn_table_name = $wpdb->prefix . 'lwn_note_db';
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $lwn_table_name WHERE content_key = '%s'",
        $_POST['delete_content_key']
    ));
    header('Location: '.admin_url().'admin.php?page=link-with-note&error_code=1');
}