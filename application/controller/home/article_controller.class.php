<?php
/**
 * 文章控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class article_controller extends common_controller {
	/**
	 * 获取指定分类下文章列表
	 */
	public function category() {
		$cat_id 	= isset($_REQUEST['cat_id']) 	&& intval($_REQUEST['cat_id'])		? intval($_REQUEST['cat_id']) 		: 0; // 商品分类id
		$page 		= isset($_REQUEST['page'])   	&& intval($_REQUEST['page'])  		? intval($_REQUEST['page'])  		: 1;
		$limit 		= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])		? intval($_REQUEST['limit'])		: 8; // 调用数目
		$keywords   = !empty($_REQUEST['keywords']) ? htmlspecialchars(trim($_REQUEST['keywords']))     					: ''; // 搜索关键字
		
		// 检查分类id是否有效
		if($cat_id <= 0 ) {
			response::show(403, 'error', '分类ID不能为空!');
			exit();
		}
		
		$artice_list = model('article')->get_cat_articles($cat_id, $limit, $page, $keywords);
		if ( false != $artice_list ) {
			response::show('200', 'success', $artice_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 获取指定文章内容
	 */
	public function content() {
		$artice_id 	= isset($_REQUEST['artice_id']) 	&& intval($_REQUEST['artice_id'])		? intval($_REQUEST['artice_id']) 		: 0; // 文章id
		
		// 检查分类id是否有效
		if($artice_id <= 0 ) {
			response::show(403, 'error', '内容ID不能为空!');
			exit();
		}

		$info = model('article')->get_article_info($artice_id);
		
		if(!empty($info)) {
			$base = sprintf('<base href="%s" />', $GLOBALS['api']['http_host']);
			
			$html = '<!DOCTYPE html><html><head><title>'.$info['title'].'</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="viewport" content="width=device-width, initial-scale=0.4"><meta name="viewport" content="initial-scale = 0.4 , minimum-scale = 0.4 , maximum-scale = 1.0" /><style>img {width: auto\9;height: auto;vertical-align: middle;border: 0;-ms-interpolation-mode: bicubic;max-width: 100%; }html { font-size:100%;margin:0 auto; } </style>'.$base.'</head><body>'.$info['content'].'</body></html>';
			
			echo $html;
		} else {
			response::show(403, 'error', '文章不存在!');
			exit();
		}
	}
}