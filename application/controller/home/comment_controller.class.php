<?php
/**
 * 评论控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class comment_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
		// 根据用户id检测用户是否存在
		//self::check_user_id($this->user_id);
	}
	
	/**
	 * 获取商品评论列表 
	 */
	public function index() {
		$goods_id 	= isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: 0;		// 商品id
		$page 		= isset($_REQUEST['page'])   	&& intval($_REQUEST['page'])  		? intval($_REQUEST['page'])  		: 1;
		$limit 		= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])		? intval($_REQUEST['limit'])		: 8;		// 调用数目
		$rank		= isset($_REQUEST['rank'])		&& intval($_REQUEST['rank']) 		? intval($_REQUEST['rank'])			: 0;
		
		// 检测商品是否存在
		$is_exist = model('goods')->validate_goods_exist($goods_id);
		if ( false == $is_exist ) {
			response::show('403', 'error', '商品不存在!');
			exit();
        }

		$comment_list = model('comment')->get_comment_list($goods_id, $rank, $limit, $page);

		if( false != $comment_list ) {
			response::show('200', 'success', $comment_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
}