<?php
/**
 * 商品控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class goods_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
	}
	
	/**
	 * 获取商品列表
	 */
	public function index() {
		$cat_id 	= isset($_REQUEST['cat_id']) 	&& intval($_REQUEST['cat_id'])		? intval($_REQUEST['cat_id']) 		: 0;		// 商品分类id
		$page 		= isset($_REQUEST['page'])   	&& intval($_REQUEST['page'])  		? intval($_REQUEST['page'])  		: 1;
		$limit 		= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])		? intval($_REQUEST['limit'])		: 8;		// 调用数目
		$brand 		= isset($_REQUEST['brand']) 	&& intval($_REQUEST['brand'])		? intval($_REQUEST['brand']) 		: 0;
		$price_max 	= isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) 	? intval($_REQUEST['price_max'])	: 0;
		$price_min 	= isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) 	? intval($_REQUEST['price_min']) 	: 0;
		
		$sort 		= (isset($_REQUEST['sort'])  	&& in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : 'goods_id';
		$order 		= (isset($_REQUEST['order']) 	&& in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                          ? trim($_REQUEST['order']) : 'DESC';
		
		$filter_attr_str = isset($_REQUEST['filter_attr']) ? htmlspecialchars(trim($_REQUEST['filter_attr'])) : '0';
		

		$goods_list = model('goods')->get_category_goods($cat_id, $brand, $price_min, $price_max, $filter_attr_str, $limit, $page, $sort, $order);
		if ( false != $goods_list ) {
			response::show('200', 'success', $goods_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 获取推荐的商品列表
	 */
	public function recommend() {
		$type 	= isset($_REQUEST['type']) 		&& !empty($_REQUEST['type']) 	? trim($_REQUEST['type']) 		: 'best'; 	// 推荐类型,默认best
		$cat_id = isset($_REQUEST['cat_id']) 	&& intval($_REQUEST['cat_id'])	? intval($_REQUEST['cat_id']) 	: '0';		// 商品分类id
		$limit 	= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])	? intval($_REQUEST['limit'])	: '5';		// 调用数目
		$page 	= isset($_REQUEST['page'])		&& intval($_REQUEST['page'])	? intval($_REQUEST['page'])		: '1';		// 当前分页码
		
		$goods_list = model('goods')->get_recommend_goods($type, $limit, $page, $cat_id);
		if ( false != $goods_list ) {
			response::show('200', 'success', $goods_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
    /**
     * 获得指定的分类下的品牌列表
     */
	public function brand() {
		$cat_id = isset($_REQUEST['cat_id']) 	&& intval($_REQUEST['cat_id'])	? intval($_REQUEST['cat_id']) 	: 0;		// 商品分类id
		$limit 	= isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])	? intval($_REQUEST['limit'])	: 0;		// 调用数目
		
		$brand_list = model('brand')->get_brand_list($cat_id, $limit);
		if( false != $brand_list ) {
			response::show('200', 'success', $brand_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 获取商品相册
	 */
	public function album() {
		$goods_id = isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: 0;		// 商品id
		$limit 	  = isset($_REQUEST['limit'])		&& intval($_REQUEST['limit'])		? intval($_REQUEST['limit'])		: 0;		// 调用数目
		
		// 检测商品是否存在
		$is_exist = model('goods')->validate_goods_exist($goods_id);
		if ( false == $is_exist ) {
			response::show('403', 'error', '商品不存在!');
			exit();
        }
		
		$album_list = model('goods')->get_album_list($goods_id, $limit);
		if( false != $album_list ) {
			response::show('200', 'success', $album_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 获取商品详情
	 */
	public function info() {
		$goods_id = isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: '0';		// 商品id
		// 检测商品是否存在
		$is_exist = model('goods')->validate_goods_exist($goods_id);
		if ( false == $is_exist ) {
			response::show('403', 'error', '商品不存在!');
			exit();
        }
		
		$goods_info = model('goods')->get_goods_info($goods_id);
		if( false != $goods_info ) {
			response::show('200', 'success', $goods_info);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
		
	}
	
	/**
	 * 获取商品图文描述详情
	 */
	public function desc() {
		$goods_id = isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: '0';		// 商品id
		
		// 检测商品是否存在
		$is_exist = model('goods')->validate_goods_exist($goods_id);
		if ( false == $is_exist ) {
			response::show('403', 'error', '商品不存在!');
			exit();
        }
		$desc = model('goods')->get_goods_desc($goods_id);
		if(!empty($desc)) {
			$base = sprintf('<base href="%s" />', $GLOBALS['api']['http_host']);
			
			$html = '<!DOCTYPE html><html><head><title>'.$desc['goods_name'].'</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="viewport" content="width=device-width, initial-scale=0.4"><meta name="viewport" content="initial-scale = 0.4 , minimum-scale = 0.4 , maximum-scale = 1.0" /><style>img {width: auto\9;height: auto;vertical-align: middle;border: 0;-ms-interpolation-mode: bicubic;max-width: 100%; }html { font-size:100%;margin:0 auto;text-align:center; } </style>'.$base.'</head><body>'.$desc['goods_desc'].'</body></html>';
			
			echo $html;
		}
	}
	
	/**
	 * 获取商品属性 
	 */
	public function attribute() {
		$goods_id = isset($_REQUEST['goods_id']) 	&& intval($_REQUEST['goods_id'])	? intval($_REQUEST['goods_id']) 	: '0';		// 商品id
		
		// 检测商品是否存在
		$is_exist = model('goods')->validate_goods_exist($goods_id);
		if ( false == $is_exist ) {
			response::show('403', 'error', '商品不存在!');
			exit();
        }
		
		$properties = model('goods')->get_goods_properties($goods_id);
		if( false != $properties ) {
			response::show('200', 'success', $properties);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
}