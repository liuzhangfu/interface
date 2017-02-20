<?php
/**
 * 购物车控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class cart_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
		// 根据用户id检测用户是否存在
		self::check_user_id($this->user_id);
	}
	
	/**
	 * 获取购物车中商品列表
	 */
	public function index() {
		$cart_goods_list = model('cart')->get_cart_goods_list($this->user_id);
		if ( false != $cart_goods_list ) {
			response::show('200', 'success', $cart_goods_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 添加商品到购物车
	 */
	public function add() {
		$goods_id 	= isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id']) > 0 	? intval($_REQUEST['goods_id']) : '0';// 商品id
		$number 	= isset($_REQUEST['number']) 	&& intval($_REQUEST['number']) > 0 		? intval($_REQUEST['number']) 	: '0';// 商品数量
		
		if(intval($goods_id) <= 0) {
			response::show('403', 'error', '商品不存在!');
			exit();
		}
		
		// 检查：商品数量是否合法
		if(intval($number) <= 0) {
			response::show('403', 'error', '对不起，您输入了一个非法的商品数量。');
			exit();
		}
		
		$result = model('cart')->add_cart($goods_id, $number, $this->user_id);
        if(! empty($result)) {
			response::show('200', 'success', '该商品已成功添加到购物车!');
			exit();
        } else {
			response::show('200', 'success', '添加失败!');
			exit();
        }
	}
	
	/**
	 * 更新购物车
	 */
	public function update() {
		$rec_id 	= isset($_REQUEST['rec_id']) 	&& intval($_REQUEST['rec_id']) > 0 		? intval($_REQUEST['rec_id']) 	: '0';// 购物车id
		$number 	= isset($_REQUEST['number']) 	&& intval($_REQUEST['number']) > 0 		? intval($_REQUEST['number']) 	: '0';// 商品数量
		
		if(intval($rec_id) <= 0) {
			response::show('403', 'error', '购物车不存在!');
			exit();
		}

		// 检查：商品数量是否合法
		if(intval($number) <= 0) {
			response::show('403', 'error', '对不起，您输入了一个非法的商品数量。');
			exit();
		}
		
		$result = model('cart')->update_cart($rec_id, $number, $this->user_id);
        if(! empty($result)) {
			response::show('200', 'success', '购物车更新成功!');
			exit();
        } else {
			response::show('200', 'success', '更新失败!');
			exit();
        }
	}
	
	
	/**
	 * 删除购物车
	 */
	public function del() {
		$rec_id 	= isset($_REQUEST['rec_id']) 	&& intval($_REQUEST['rec_id']) > 0 		? intval($_REQUEST['rec_id']) 	: '0';// 购物车id

		if(intval($rec_id) <= 0) {
			response::show('403', 'error', '购物车不存在!');
			exit();
		}

		$result = model('cart')->del_cart($this->user_id, $rec_id);

        if(! empty($result)) {
			response::show('200', 'success', '购物车删除成功!');
			exit();
        } else {
			response::show('200', 'success', '删除失败!');
			exit();
        }
	}
	
	/**
	 * 清空购物车
	 */
	public function clear() {
		$result = model('cart')->clear_cart($this->user_id);
        if(! empty($result)) {
			response::show('200', 'success', '清空购物车成功!');
			exit();
        } else {
			response::show('200', 'success', '清空失败!');
			exit();
		}
	}
}