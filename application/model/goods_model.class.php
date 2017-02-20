<?php
/**
 * 商品数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class goods_model extends common_model {
	// 数据表名
	public $table_name = "goods";
	
	/**
	 * 获得推荐商品
	 *
	 * @access  public
	 * @param   string      $type       推荐类型，可以是 best:精品, new:新品, hot:热销,默认精品
	 * @param   integer     $limit      获取列表数目或分页大小
	 * @param   integer     $page      	当前分页数
	 * @param   integer     $cat_id     指定分类id下推荐商品
	 * @return  array
	 */
	public function get_recommend_goods($type, $limit = 5, $page = 1, $cat_id = 0) {
		if( ! in_array($type, array('best', 'hot', 'new') ) ) {
			return array();
		}
		
		$where = 'WHERE 1 ';
		switch ($type) {
			case 'best':
				$where .= ' AND g.is_best = 1';
				break;
				
			case 'new':
				$where .= ' AND g.is_new = 1';
				break;
				
			case 'hot':
				$where .= ' AND g.is_hot = 1';
				break;
		}
		if(!empty($cat_id)) {
			$where .= ' AND g.cat_id = :cat_id';
		}
		
        // 取出所有符合条件的商品数据总数
		$sql = "SELECT COUNT(*) AS count FROM ".$this->table_prefix."goods AS g ".$where.
				" AND g.is_on_sale = 1".
				" AND g.is_alone_sale = 1".
				" AND g.is_delete = 0";
		$count = $this->query($sql, array(':cat_id' => $cat_id));
		$total = isset($count[0]['count']) && !empty($count[0]['count']) ? $count[0]['count'] : 0;
		
        // 取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
        $sql = "SELECT g.goods_id, g.cat_id,g.goods_name, g.market_price, g.shop_price AS org_price, g.promote_price, g.click_count,".
                " IFNULL(mp.user_price, g.shop_price * '".$GLOBALS['user']['discount']."') AS shop_price,".
                " promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img".
                " FROM ".$this->table_prefix."goods AS g" .
                " LEFT JOIN " . $this->table_prefix. "member_price AS mp".
                " ON mp.goods_id = g.goods_id AND mp.user_rank = ".$GLOBALS['user']['user_rank']." ".$where.
				" AND g.is_on_sale = 1".
				" AND g.is_alone_sale = 1".
				" AND g.is_delete = 0".
				" ORDER BY g.sort_order, g.last_update DESC";
				
		$page_count = ceil($total / $limit);// 总分页数
		$max_page = ($page_count > 0) ? $page_count : 1;// 最大分页数
		$page = ($page > $max_page) ? $max_page : $page;// 当前分页数
		
        if( ( $page - 1 ) * $limit == 0 )  {
            $sql .= ' LIMIT ' . $limit;
        } else {
            $sql .= ' LIMIT ' . ( $page - 1 ) * $limit. ', ' . $limit;
        }
		
		$arr = array();
		
		if($row = $this->query($sql, array(':cat_id' => $cat_id))) {
			foreach($row as $k => $v) {
				$arr[$k] = $v;
				// 促销价格
				if ($v['promote_price'] > 0) {
					$promote_price 				= bargain_price($v['promote_price'], $v['promote_start_date'], $v['promote_end_date']);
					$arr[$k]['promote_price'] 	= $promote_price > 0 ? $promote_price : '';
				} else {
					$arr[$k]['promote_price'] 	= '';
				}           

				$arr[$k]['shop_price']    		= $arr[$k]['org_price'];
				$arr[$k]['goods_img']    		= $GLOBALS['api']['http_host'].get_image_path($v['goods_id'], $v['goods_img']);
				$arr[$k]['goods_thumb']    		= $GLOBALS['api']['http_host'].get_image_path($v['goods_id'], $v['goods_thumb']);
				
				// 附加信息
				$arr[$k]['page_count']    		= $page_count; // 总分页数
				$arr[$k]['click_count'] 		= $v['click_count']; // 商品点击数
				$arr[$k]['total'] 				= $total; // 数据列表总数
				$arr[$k]['sales_count']			= $this->get_sales_count($v['goods_id']);  // 销量
				$arr[$k]['collect_count']		= model('collect')->get_goods_collect($v['goods_id']); // 收藏量
				$arr[$k]['is_collect'] 			= model('collect')->get_goods_is_collect($v['goods_id'], $GLOBALS['user']['user_id']); // 是否收藏
				
				unset($arr[$k]['org_price']);
/* 				unset($arr[$k]['promote_start_date']);
				unset($arr[$k]['promote_end_date']); */
			}
		}
		
		return $arr;
	}
	
	/**
	 * 获取单个商品的累积销量
	 *
	 * @param 	integer 	$goods_id	商品id
	 * @return	bool|integer			销售数量
	 */
	public function get_sales_count($goods_id) {
		if( intval($goods_id) <= 0 ) {
			return '';
		}
		// 统计时间段
		$period = intval($GLOBALS['api']['top10_time']);
		if ($period == 1) {
			$ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";// 一年
		} elseif ($period == 2) {
			$ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";// 半年
		} elseif ($period == 3) {
			$ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";// 三个月
		} elseif ($period == 4) {
			$ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";// 一个月
		} else {
			$ext = '';
		}
		
		// 查询该商品销量,条件：订单-已确认,配送状态-已发货、已收货,支付状态-已付款、付款中
		$sql = "SELECT IFNULL(SUM(g.goods_number), 0) AS count".
			" FROM ". $this->table_prefix ."order_info AS o, ". $this->table_prefix ."order_goods AS g".
			" WHERE o.order_id = g. order_id " .
			" AND o.order_status = 1" .
			" AND o.shipping_status " . db_create_in(array(1, 2)) .
			" AND o.pay_status " . db_create_in(array(2, 1)) .
			" AND g.goods_id = :goods_id";
		
		$row = $this->query($sql, array(':goods_id'=>$goods_id));
		
		return isset($row[0]['count']) && !empty($row[0]['count']) ? $row[0]['count'] : 0;
	}
	
	/**
	 * 获取单个商品的相册列表
	 *
	 * @param 	integer 	$goods_id	商品id
	 * @param   integer     $limit      获取列表数目或分页大小
	 * @return	arr						商品相册
	 */
	public function get_album_list($goods_id = 0, $limit = 5) {
		$sql = "SELECT img_id, img_url, thumb_url, img_desc".
				" FROM ". $this->table_prefix ."goods_gallery".
				" WHERE goods_id = :goods_id".
				" LIMIT :limit";
				
		$row = $this->query($sql, array(":goods_id"=>$goods_id, ":limit"=>$limit));
		// 格式化相册图片路径
        foreach ($row as $key => $gallery_img) {
            $row[$key]['img_url'] 	= $GLOBALS['api']['http_host'].get_image_path($goods_id, $gallery_img['img_url'], false, 'gallery');
            $row[$key]['thumb_url'] = $GLOBALS['api']['http_host'].get_image_path($goods_id, $gallery_img['thumb_url'], true, 'gallery');
            $row[$key]['img_desc'] 	= $gallery_img['img_desc'];
        }
        return $row;
	}
	
    /**
     * 获得商品的图文描述信息
     *
     * @param   integer $goods_id 商品id
     * @return  array
     */
	public function get_goods_desc($goods_id = 0) {
		$sql = "SELECT goods_name,goods_desc".
				" FROM ".$this->table_prefix."goods".
				" WHERE goods_id = :goods_id";
				
		$row = $this->query($sql, array(':goods_id'=>$goods_id));
		
		return isset($row[0]) && !empty($row[0]) ? $row[0] : '';
	}
	
	/**
	 * 获得商品的详细信息
	 *
	 * @access  public
	 * @param   integer     $goods_id
	 * @return  void
	 */
	public function get_goods_info($goods_id) {
		$time = gmtime();
		$sql = 'SELECT g.*, c.measure_unit, b.brand_id, b.brand_name AS goods_brand, m.type_money AS bonus_money, ' .
					'IFNULL(AVG(r.comment_rank), 0) AS comment_rank, ' .
					"IFNULL(mp.user_price, g.shop_price * '".$GLOBALS['user']['discount']."') AS rank_price " .
				'FROM ' . $this->table_prefix.'goods AS g ' .
				'LEFT JOIN ' . $this->table_prefix.'category AS c ON g.cat_id = c.cat_id ' .
				'LEFT JOIN ' . $this->table_prefix.'brand AS b ON g.brand_id = b.brand_id ' .
				'LEFT JOIN ' . $this->table_prefix.'comment AS r '.
					'ON r.id_value = g.goods_id AND comment_type = 0 AND r.parent_id = 0 AND r.status = 1 ' .
				'LEFT JOIN ' . $this->table_prefix.'bonus_type AS m ' .
					"ON g.bonus_type_id = m.type_id AND m.send_start_date <= '$time' AND m.send_end_date >= '$time'" .
				" LEFT JOIN " . $this->table_prefix."member_price AS mp ".
						"ON mp.goods_id = g.goods_id AND mp.user_rank = '".$GLOBALS['user']['user_rank']."' ".
				"WHERE g.goods_id = :goods_id".
				" AND g.is_delete = 0 " .
				"GROUP BY g.goods_id";
		$row = $this->query($sql, array(':goods_id'=>$goods_id));
		if (false !== $row) {
			/* 用户评论级别取整 */
			$row[0]['comment_rank']  = ceil($row[0]['comment_rank']) == 0 ? 5 : ceil($row[0]['comment_rank']);

			/* 获得商品的销售价格 */
			$row[0]['market_price']        = price_format($row[0]['market_price']);
			$row[0]['shop_price_formated'] = price_format($row[0]['shop_price']);

			/* 修正促销价格 */
			if ($row[0]['promote_price'] > 0) {
				$promote_price = bargain_price($row[0]['promote_price'], $row[0]['promote_start_date'], $row[0]['promote_end_date']);
			} else {
				$promote_price = 0;
			}

			/* 处理商品水印图片 */
			$watermark_img = '';

			if ($promote_price != 0) {
				$watermark_img = "watermark_promote";
			} elseif ($row[0]['is_new'] != 0) {
				$watermark_img = "watermark_new";
			} elseif ($row[0]['is_best'] != 0) {
				$watermark_img = "watermark_best";
			} elseif ($row[0]['is_hot'] != 0) {
				$watermark_img = 'watermark_hot';
			}

			if ($watermark_img != '') {
				$row[0]['watermark_img'] =  $watermark_img;
			}

			$row[0]['promote_price_org'] =  $promote_price;
			$row[0]['promote_price'] =  price_format($promote_price);

			/* 修正重量显示 */
			$row[0]['goods_weight']  = (intval($row[0]['goods_weight']) > 0) ?
				$row[0]['goods_weight'] . '千克' :
				($row[0]['goods_weight'] * 1000) . '克';

			/* 修正上架时间显示 */
			$row[0]['add_time']      = local_date($GLOBALS['api']['date_format'], $row[0]['add_time']);

			/* 促销时间倒计时 */
			$time = gmtime();
			if ($time >= $row[0]['promote_start_date'] && $time <= $row[0]['promote_end_date'])
			{
				 $row[0]['gmt_end_time']  = $row[0]['promote_end_date'];
			}
			else
			{
				$row[0]['gmt_end_time'] = 0;
			}

			/* 是否显示商品库存数量 */
			$row[0]['goods_number']  = ($GLOBALS['api']['use_storage'] == 1) ? $row[0]['goods_number'] : '';

			/* 修正积分：转换为可使用多少积分（原来是可以使用多少钱的积分） */
			$row[0]['integral']      = $GLOBALS['api']['integral_scale'] ? round($row[0]['integral'] * 100 / $GLOBALS['api']['integral_scale']) : 0;

			/* 修正优惠券 */
			$row[0]['bonus_money']   = ($row[0]['bonus_money'] == 0) ? 0 : price_format($row[0]['bonus_money'], false);

			/* 修正商品图片 */
			$row[0]['goods_img']   			= $GLOBALS['api']['http_host'].get_image_path($goods_id, $row[0]['goods_img']);
			$row[0]['goods_thumb'] 			= $GLOBALS['api']['http_host'].get_image_path($goods_id, $row[0]['goods_thumb'], true);
			$row[0]['original_img'] 		= $GLOBALS['api']['http_host'].get_image_path($goods_id, $row[0]['original_img'], true);
			unset($row[0]['goods_desc']);
			
				// 附加信息
			$row[0]['collect_count']		= model('collect')->get_goods_collect($row[0]['goods_id']); // 收藏量
			$row[0]['sales_count']			= $this->get_sales_count($row[0]['goods_id']);  // 销量
			$row[0]['is_collect'] 			= model('collect')->get_goods_is_collect($row[0]['goods_id'], $GLOBALS['user']['user_id']); // 是否收藏
				
			return $row[0];
		} else {
			return false;
		}
	}
	
	/**
	 * 获得商品的属性和规格
	 *
	 * @access  public
	 * @param   integer $goods_id
	 * @return  array
	 */
	public function get_goods_properties($goods_id = 0) {
		// 获得商品的规格
		$sql = "SELECT a.attr_id, a.attr_name, a.attr_group, a.is_linked, a.attr_type,".
                "g.goods_attr_id, g.attr_value, g.attr_price" .
            " FROM " . $this->table_prefix . "goods_attr AS g" .
            " LEFT JOIN " . $this->table_prefix ."attribute AS a" .
			" ON a.attr_id = g.attr_id" .
            " WHERE g.goods_id = :goods_id" .
            " ORDER BY a.sort_order, g.attr_price, g.goods_attr_id";
		
		$row = $this->query($sql, array(':goods_id'=>$goods_id));
		
		$arr = array();
		if(false != $row) {
			foreach ($row as $key => $val) {
				$arr[$key]['name']  = $val['attr_name'];
				$arr[$key]['value'] = $val['attr_value'];
			}
		}
        return $arr;
	}
	
	/**
	 * 检查商品是否真实存在
	 */
    public function validate_goods_exist($goods_id = 0) {
		return $this->find(array('goods_id'=>$goods_id)) ? true : false;
    }
	
	/**
	 * 获得所有扩展分类属于指定分类的所有商品ID
	 *
	 * @param   string $cat_id     分类查询字符串
	 * @return  string
	 */
	public function get_extension_goods($cats) {
		$extension_goods_array = '';
		$sql = 'SELECT goods_id FROM ' . $this->table_prefix . "goods_cat AS g WHERE $cats";
		$row = $this->query($sql);
		$extension_goods_array = !empty($row[0]['goods_id']) ? $row[0]['goods_id'] : '';
		return db_create_in($extension_goods_array, 'g.goods_id');
	}
	
	/**
	 * 获得分类下的商品
	 *
	 * @param 	integer 	$cat_id 			商品分类ID
	 * @param 	integer 	$brand 				品牌ID
	 * @param 	integer 	$price_max			最大价格
	 * @param 	integer 	$price_min 			最小价格
	 * @param 	string  	$sort 				排序字段:goods_id,shop_price,last_update,click_count
	 * @param 	string  	$order 				正反排序:asc or desc
	 * @param 	string  	$filter_attr_str 	商品属性
	 * @return  array
	 */
	public function get_category_goods($cat_id = 0, $brand = 0, $price_min = 0, $price_max = 0, $filter_attr_str = 0, $limit = 8, $page = 1, $sort = 'goods_id', $order = 'DESC') {
		// 获得分类的相关信息
		$cat = model('category')->get_cat_info($cat_id);
		if( empty($cat) ) {
			return 0;
		}
		
		// 获取当前分类下的所有子分类
		$sql = "SELECT `cat_id` FROM ".$this->table_prefix."category WHERE parent_id = :cat_id";
		$child_cat_id = array();
		if($row = $this->query($sql, array(':cat_id'=>$cat_id))) {
			foreach($row as $val) {
				$child_cat_id[] = $val['cat_id'];
			}
		}
		$children = !empty($child_cat_id) ? 'AND g.cat_id ' . db_create_in($child_cat_id) : '';
	
		$where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 $children";
		
		if ($brand > 0) {
			$where .=  "AND g.brand_id = $brand ";
		}

		if ($price_min > 0) {
			$where .= " AND g.shop_price >= $price_min ";
		}

		if ($price_max > 0) {
			$where .= " AND g.shop_price <= $price_max ";
		}
		
		// 属性处理
		$filter_attr_str = trim(urldecode($filter_attr_str));
		$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
		$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
		$ext = ''; //商品查询条件扩展
        // 扩展商品查询条件
        if (!empty($filter_attr)) {
            $ext_sql = "SELECT DISTINCT(b.goods_id) FROM " . $this->table_prefix ."goods_attr AS a, " . $this->table_prefix ."goods_attr AS b WHERE ";
            $ext_group_goods = array();

			//提取出此分类的筛选属性
			$cat_filter_attr = explode(',', $cat['filter_attr']);
	
			// 查出符合所有筛选属性条件的商品id
            foreach ($filter_attr AS $k => $v) {
                if (is_numeric($v) && $v != 0 && isset($cat_filter_attr[$k])) {
                    $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] ." AND a.goods_attr_id = " . $v;
					$ext_group_row = $this->query($sql);
					foreach($ext_group_row as $ext_group) {
						$ext_group_goods[] = $ext_group['goods_id'];
					}
					//print_r($ext_group_goods);die;
                    $ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                }
            }
        }
		
        // 取出所有符合条件的商品数据总数
		$sql = "SELECT COUNT(*) AS count FROM ".$this->table_prefix."goods AS g WHERE $where $ext ORDER BY $sort $order";
		$count = $this->query($sql);
		$total = isset($count[0]['count']) && !empty($count[0]['count']) ? $count[0]['count'] : 0;
		//return $total;
		
		// 获得商品列表
		$sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '".$GLOBALS['user']['discount']."') AS shop_price, g.promote_price, g.goods_type, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $this->table_prefix .'goods AS g ' .
            'LEFT JOIN ' . $this->table_prefix .'member_price AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '".$GLOBALS['user']['user_rank']."' " .
            "WHERE $where $ext ORDER BY $sort $order";
			
		$page_count = ceil($total / $limit);// 总分页数
		$max_page = ($page_count > 0) ? $page_count : 1;// 最大分页数
		$page = ($page > $max_page) ? $max_page : $page;// 当前分页数
		
        if( ( $page - 1 ) * $limit == 0 ) {
            $sql .= ' LIMIT ' . $limit;
        } else {
            $sql .= ' LIMIT ' . ( $page - 1 ) * $limit. ', ' . $limit;
        }
		
		$arr = array();
		if($res = $this->query($sql)) {
			foreach($res as $row) {
				if ($row['promote_price'] > 0) {
					$promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
				} else {
					$promote_price = 0;
				}

/* 				// 处理商品水印图片
				$watermark_img = '';

				if ($promote_price != 0) {
					$watermark_img = "watermark_promote_small";
				} elseif ($row['is_new'] != 0) {
					$watermark_img = "watermark_new_small";
				} elseif ($row['is_best'] != 0) {
					$watermark_img = "watermark_best_small";
				} elseif ($row['is_hot'] != 0) {
					$watermark_img = 'watermark_hot_small';
				}

				if ($watermark_img != '') {
					$arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
				} */

				$arr[$row['goods_id']]['goods_id']         	= $row['goods_id'];
				$arr[$row['goods_id']]['goods_name']   	   	= $row['goods_name'];
				$arr[$row['goods_id']]['name']             	= $row['goods_name'];
				$arr[$row['goods_id']]['goods_brief']      	= $row['goods_brief'];
				//$arr[$row['goods_id']]['goods_style_name'] = $row['goods_name_style'];
				$arr[$row['goods_id']]['market_price']     	= price_format($row['market_price']);
				$arr[$row['goods_id']]['shop_price']       	= price_format($row['shop_price']);
				$arr[$row['goods_id']]['type']             	= $row['goods_type'];
				$arr[$row['goods_id']]['promote_price']    	= ($promote_price > 0) ? price_format($promote_price) : '';
				$arr[$row['goods_id']]['goods_thumb']      	= $GLOBALS['api']['http_host'].get_image_path($row['goods_id'], $row['goods_thumb'], true);
				$arr[$row['goods_id']]['goods_img']        	= $GLOBALS['api']['http_host'].get_image_path($row['goods_id'], $row['goods_img']);
				
				// 附加信息
				$arr[$row['goods_id']]['page_count']    	= $page_count; // 总分页数
				$arr[$row['goods_id']]['click_count'] 		= $v['click_count']; // 商品点击数
				$arr[$row['goods_id']]['total'] 			= $total; // 数据列表总数
				$arr[$row['goods_id']]['collect_count']		= model('collect')->get_goods_collect($row['goods_id']); // 收藏量
				$arr[$row['goods_id']]['sales_count']		= $this->get_sales_count($row['goods_id']);  // 销量
				$arr[$row['goods_id']]['is_collect'] 		= model('collect')->get_goods_is_collect($row['goods_id'], $GLOBALS['user']['user_id']); // 是否收藏
			}
		}

		return $arr;
	}
}