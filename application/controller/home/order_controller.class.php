<?php
/**
 * 订单控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class order_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
		// 根据用户id检测用户是否存在
		self::check_user_id($this->user_id);
	}
	
	/**
	 * 获取订单列表
	 */
	public function index() {
		$page 		= isset($_REQUEST['page'])   	&& intval($_REQUEST['page'])  		? intval($_REQUEST['page'])  		: 1;
		$limit 		= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])		? intval($_REQUEST['limit'])		: 8;		// 调用数目
		
		$order_list = model('order')->get_user_orders($this->user_id, $limit, $page);
		
		if ( false != $order_list ) {
			response::show('200', 'success', $order_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 删除指定订单
	 */
	public function del() {
		$order_id 	= isset($_REQUEST['order_id']) 	&& intval($_REQUEST['order_id']) 	? intval($_REQUEST['order_id']) 	: '0';// 订单id
		
		// 检查订单id是否有效
		if($order_id <= 0 ) {
			response::show(403, 'error', '订单不存在!');
			exit();
		}
		
		$result = model('order')->del_order($this->user_id, $order_id);
		
		if( false != $result ) {
			response::show('200', 'success', '订单删除成功!');
			exit();
		} else {
			response::show('403', 'error', '订单删除失败!');
			exit();
		}
	}
}	