<?php
/**
 * 商品收藏控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class collect_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
		// 根据用户id检测用户是否存在
		self::check_user_id($this->user_id);
	}
	
	/**
	 * 收藏列表
	 */
	public function index() {
		$limit 	= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit']) > 0	? intval($_REQUEST['limit'])	: '5'; // 调用数目
		$page 	= isset($_REQUEST['page'])		&& intval($_REQUEST['page'])  > 0 	? intval($_REQUEST['page'])		: '1'; // 当前分页码
		// 获取收藏列表
		$collect_list = model('collect')->get_collect_list($this->user_id, $limit, $page);
		if ( false != $collect_list ) {
			response::show('200', 'success', $collect_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 添加收藏
	 */
	public function add() {
		$goods_id = isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: '0'; // 商品id
		// 添加商品收藏
		$result = model('collect')->add_collect($goods_id, $this->user_id);
		if ( false != $result ) {
			response::show('200', 'success', $result);
			exit();
        } else {
			response::show('403', 'error', $result);
			exit();
        }
	}
	
	/**
	 * 取消收藏
	 */
	public function del() {
		$goods_id = isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: '0'; // 商品id
		$result = model('collect')->delete(array('user_id'=>$this->user_id, 'goods_id'=>$goods_id));
		if(false != $result) {
			response::show('200', 'success', '该商品已经成功的从收藏夹中删除。');
			exit();
		} else {
			response::show('403', 'error', '未知错误!');
			exit();
		}
	}
}