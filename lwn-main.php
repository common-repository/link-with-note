<?php
/*
Plugin Name: Link With Note
Plugin URI: 
Description: wp→noteへの埋め込みリンクをカスタマイズできるプラグイン、noteのマネタイズポイントへユーザーを効率よく誘導できます。
Version: 1.0.0
Author:smile
Author URI: https://diy-programming.site/
License: GPL2
*/

/*  Copyright 2021 smile (email : https://diy-programming.site)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit;

#関数読み込み
require_once(dirname( __FILE__ ) . '/lwn-functions.php');

#noteDBの作成・削除
register_activation_hook(__FILE__, 'lwn_add_plugin_first'); //プラグインが有効化された時にDB作成
register_uninstall_hook( __FILE__, 'lwn_plugin_uninstall' ); //プラグインがアンインストールされた時にDB削除

/* formの処理 */
if(isset($_POST['import_note_key'])){
	lwn_note_create(); #note取り込み
}
if(isset($_POST['delete_content_key'])){
	lwn_note_delete(); #noteDB削除
}

#管理画面出力
function lwn_display_plugin_admin_page() {	

?>	
	<h1>Link With Note</h1>
	<div class="lwn_tab_wrap">
		<div class="lwn_message">
			<p>Link With Noteの詳しい使い方はこちらの<a href="https://diy-programming.site/link-with-note/manual/" target="_blank">マニュアル</a>をご確認下さい。</p>
		</div>
		<input id="lwn_tab1" type="radio" name="lwn_tab_btn" checked>
		<input id="lwn_tab2" type="radio" name="lwn_tab_btn">
		<div class="lwn_tab_area">
			<label class="lwn_tab1_label" for="lwn_tab1">リンク一覧</label>
			<label class="lwn_tab2_label" for="lwn_tab2">デザイン</label>
		</div>
		<div class="lwn_panel_area">
			<div id="lwn_panel1" class="lwn_tab_panel">
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<div class="lwn_flex_box">
									<div class="lwn_flex_item">
										<h2>note取り込み</h2>
											<form action="" method="post">
												<input type="text" name="import_note_key" class="lwn_note_key" placeholder="noteURLを入力">
												<input type="submit" value="実行">
											</form>
									</div>
									<div class="lwn_flex_item">
										<h2>処理結果</h2>
										<?php #エラー判定
											if(isset($_GET['error_code'])){lwn_error_handle($_GET['error_code']);}
										?>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<div class="lwn_page_list">
								<?php
								if(empty($_GET["page_no"])){$_get_page = 1;} else {$_get_page = (int)$_GET["page_no"];}
								list($max_page, $note_db) = lwn_note_list($_get_page);
								$page_width = 2; //現在のページから表示する幅
								?>
								<?php if($_get_page > 1){ //現在のページが1より大きい場合にPrev表示 ?>
									<a href="<?php echo esc_url(admin_url());?>admin.php?page=link-with-note&page_no=<?php echo esc_html($_get_page-1);?>">Prev</a>
								<?php } ?>
								<?php if(( $_get_page - $page_width ) > 1 ){  //ページ幅が1より大きい場合に1表示?>
									<a href="<?php echo esc_url(admin_url());?>admin.php?page=link-with-note&page_no=1">1</a>
								<?php } ?>
								<?php if(( $_get_page - $page_width ) > 2 ){ //ページ幅が1より大きい場合に...表示?>
									...
								<?php }  ?>
								<?php for( $i = $page_width; $i > 0; $i--){ //中心ページより小さいページ番号を表示?>
									<?php if(( $_get_page - $i ) < 1) continue; ?>
									<a href="<?php echo esc_url(admin_url());?>admin.php?page=link-with-note&page_no=<?php echo esc_html($_get_page-$i);?>"><?php echo esc_html($_get_page-$i);?></a>
								<?php } ?>
								<span class = "lwn_page_center"><?php echo esc_html($_get_page);?></span>
								<?php for( $i = 1; $i <= $page_width; $i++){ //中心ページより大きいページ番号を表示?>
									<?php if(( $_get_page + $i ) > $max_page ) continue; ?>
									<a href="<?php echo esc_url(admin_url());?>admin.php?page=link-with-note&page_no=<?php echo esc_html($_get_page+$i);?>"><?php echo esc_html($_get_page+$i);?></a>
								<?php } ?>
								<?php if(( $_get_page + $page_width ) < $max_page-1 ){ //ページ幅が最終ページより小さい場合に...表示?>
									...
								<?php } ?>
								<?php if(( $_get_page + $page_width ) < $max_page ){ //ページ幅が最終ページより小さい場合に最終ページ番号を表示?>
									<a href="<?php echo esc_url(admin_url());?>admin.php?page=link-with-note&page_no=<?php echo esc_html($max_page);?>"><?php echo esc_html($max_page);?></a>
								<?php }  ?>
								<?php if( $_get_page < $max_page ){ // ページが最終ページより小さい場合にNext表示?>
									<a href="<?php echo esc_url(admin_url());?>admin.php?page=link-with-note&page_no=<?php echo esc_html($_get_page+1);?>">Next</a>
								<?php } ?>
								</div>
								<table class="lwn_movie_list">
									<tr>
										<th>ID</th>
										<th>notekey</th>
										<th>取込日</th>
										<th>title</th>
										<th>ショートコード</th>
										<th>処理</th>
									</tr>
									<?php foreach($note_db as $note_db) { ?>
										<tr>
											<td><?php echo esc_html($note_db["id"]); ?></td>
											<td><?php echo esc_html($note_db["content_key"]); ?></td>
											<td><?php echo esc_html($note_db["import_date"]); ?></td>
											<td><a href="<?php echo esc_url($note_db["content_url"]); ?>" target="_blank"><?php echo esc_html($note_db["title"]); ?></a></td>
											<td><input type="text" value="<?php echo esc_html("[lwn key=".$note_db["content_key"]."]");?>"></td>
											<td class ="process_button">
												<form method="post" action="">
													<input type="hidden" name="delete_content_key" value="<?php echo esc_html($note_db["content_key"]); ?>">
													<input type="submit" value="削除">
												</form>
											</td>
										</tr>
									<?php } ?>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div id="lwn_panel2" class="lwn_tab_panel">
				<form method="post" action="options.php">
					<?php
						settings_fields( 'lwn_setting' );
						do_settings_sections( 'lwn_setting' );
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">noteリンクデザイン</th>
								<td>
									<?php $results = glob(dirname(__FILE__).'/template/*');
									foreach($results as $result){ #templateフォルダからデザインセット読み込み ?>
										<div><input type="radio" id="lwn_design_<?php echo esc_html(basename($result));?>" name="lwn_design" value="<?php echo esc_html(basename($result));?>"<?php lwn_design_checked($result);?>><?php echo esc_html(basename($result));?></div>
										<label for="lwn_design_<?php echo esc_html(basename($result));?>"><img class="lwn_design_img" src="<?php echo esc_html(plugins_url());?>/link-with-note/template/<?php echo esc_html(basename($result));?>/<?php echo esc_html(basename($result));?>.png"></label>
									<?php } ?>
								</td>
							</tr>
							<tr>
								<th scope="row">サムネイル表示</th>
								<td>
									<input type="checkbox" name="lwn_thumb_view" size="10" value="1" <?php lwn_checkbox_checked(esc_attr(get_site_option('lwn_thumb_view'))) ;?>>
								</td>
							</tr>
							<tr>
								<th scope="row">タイトル文字数</th>
								<td>
									<input type="number" name="lwn_title_count" size="10" value="<?php echo esc_attr(get_site_option('lwn_title_count')) ?>" required>
								</td>
							</tr>
							<tr>
								<th scope="row">目次行数</th>
								<td>
									<input type="number" name="lwn_index_line" size="10" value="<?php echo esc_attr(get_site_option('lwn_index_line')) ?>" required>
								</td>
							</tr>
							<tr>
								<th scope="row">本文文字数</th>
								<td>
									<input type="number" name="lwn_text_count" size="10" value="<?php echo esc_attr(get_site_option('lwn_text_count')) ?>" required>
								</td>
							</tr>
							<tr>
								<th scope="row">ハッシュタグ個数</th>
								<td>
									<input type="number" name="lwn_hashtag_count" size="10" value="<?php echo esc_attr(get_site_option('lwn_hashtag_count')) ?>" required>
								</td>
							</tr>
							<tr>
								<th scope="row">スキ数表示</th>
								<td>
									<input type="checkbox" name="lwn_like_view" size="10" value="1" <?php lwn_checkbox_checked(esc_attr(get_site_option('lwn_like_view'))) ;?>>
								</td>
							</tr>
							<tr>
								<th scope="row">コメント数表示</th>
								<td>
									<input type="checkbox" name="lwn_comment_view" size="10" value="1"<?php lwn_checkbox_checked(esc_attr(get_site_option('lwn_comment_view'))) ;?>>
								</td>
							</tr>
							<tr>
								<th scope="row">クリエイター表示</th>
								<td>
									<input type="checkbox" name="lwn_creator_info" size="10" value="1"<?php lwn_checkbox_checked(esc_attr(get_site_option('lwn_creator_info'))) ;?>>
								</td>
							</tr>
							<tr>
								<th scope="row">新しいタブで開く</th>
								<td>
									<input type="checkbox" name="lwn_new_tab" size="10" value="1"<?php lwn_checkbox_checked(esc_attr(get_site_option('lwn_new_tab'))) ;?>>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button(); // 送信ボタン ?>
				</form>
			</div>
		</div>
	</div>
<?php } ;?>