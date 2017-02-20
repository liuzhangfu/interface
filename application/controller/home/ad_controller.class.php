<?php
/**
 * 广告控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class ad_controller extends common_controller {
	/**
	 * 获取广告数据
	 */
	public function index() {
		$position_id 	= isset($_REQUEST['position_id']) 	&& intval($_REQUEST['position_id']) > 0 ? intval($_REQUEST['position_id']) 	: '0'; // 广告位id
		$limit 			= isset($_REQUEST['limit']) 		&& intval($_REQUEST['limit']) > 0 		? intval($_REQUEST['limit']) 		: '5'; // 调用数目

		// 检查广告位是否有效
		if($position_id <= 0 ) {
			response::show(403, 'error', '广告位置不能为空!');
			exit();
		}

		// 获取广告列表
		$ad_list = model('ad')->get_ad($position_id, $limit);
		if( false != $result ) {
			response::show('200', 'success', $ad_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
}