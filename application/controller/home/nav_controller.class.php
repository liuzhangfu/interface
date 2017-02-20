<?php
/**
 * 导航控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class nav_controller extends common_controller {
	/**
	 * 获取导航数据
	 */
	public function index() {
		$position_id 	= isset($_REQUEST['position_id']) 	&& intval($_REQUEST['position_id']) > 0 ? intval($_REQUEST['position_id']) 	: '0'; // 广告位id
		$limit 			= isset($_REQUEST['limit']) 		&& intval($_REQUEST['limit']) > 0 		? intval($_REQUEST['limit']) 		: '8'; // 调用数目

		// 检查广告位是否有效
		if(intval($position_id) <= 0 ) {
			response::show(403, 'error', '导航位置不能为空!');
			exit();
		}

		// 获取广告列表
		$nav_list = model('nav')->get_navigator($position_id, $limit);
		if( false != $nav_list ) {
			response::show('200', 'success', $nav_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
}