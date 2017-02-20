<?php
/**
 * 商品分类控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class category_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
	}
	
	/**
	 * 获取商品分类
	 */
	public function index() {
		
	}
	
	/**
	 * 获取商品分类属性
	 */
	public function attribute() {
		$cat_id = isset($_REQUEST['cat_id']) 	&& intval($_REQUEST['cat_id'])	? intval($_REQUEST['cat_id']) 	: '0';		// 商品分类id
		
		// 检查广告位是否有效
		if($cat_id <= 0 ) {
			response::show(403, 'error', '商品分类id不能为空!');
			exit();
		}
		
		$cate_attr = model('category')->get_cat_grade($cat_id);
		
		print_r($cate_attr);
		
/* 		$children = get_children($cat_id);
		print_r($children); */
	}
}