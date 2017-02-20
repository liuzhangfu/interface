<?php
/**
 * 商品收藏模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class collect_model extends common_model {
	public $table_name = "collect_goods";
	
	/**
	 * 获取单个商品的累积收藏量
	 *
	 * @param 	integer 	$goods_id	商品id
	 * @return	bool|integer			收藏数量
	 */
	public function get_goods_collect($goods_id) {
		if(intval($goods_id) <= 0) {
			return '';
		}
		
		// 统计时间段
		$period = intval($GLOBALS['api']['top10_time']);
		if ($period == 1) {
			$ext = " AND add_time > '" . local_strtotime('-1 years') . "'";// 一年
		} elseif ($period == 2) {
			$ext = " AND add_time > '" . local_strtotime('-6 months') . "'";// 半年
		} elseif ($period == 3) {
			$ext = " AND add_time > '" . local_strtotime('-3 months') . "'";// 三个月
		} elseif ($period == 4) {
			$ext = " AND add_time > '" . local_strtotime('-1 months') . "'";// 一个月
		} else {
			$ext = '';
		}
		
		$sql = "SELECT COUNT(*) AS count FROM ".$this->table_prefix."collect_goods".
				" WHERE goods_id = :goods_id".$ext;
		$row = $this->query($sql, array(':goods_id'=>$goods_id));
		
		return isset($row[0]['count']) && !empty($row[0]['count']) ? $row[0]['count'] : 0;
	}
	
	/**
	 * 判断某个商品是否被当前用户收藏
	 *
	 * @param 		integer		$goods_id	商品id
	 * @user_id 	integer		$user_id	用户id
	 * @return		bool|					是否被收藏,1:己收藏,0:未收藏
	 */
	public function get_goods_is_collect($goods_id, $user_id) {
		if( intval($goods_id) <= 0 || intval($user_id) <= 0) {
			return 0;
		}

		$sql = "SELECT COUNT(*) AS count FROM ".$this->table_prefix."collect_goods".
				" WHERE goods_id = :goods_id".
				" AND user_id = :user_id";
				
		$row = $this->query($sql, array(':goods_id'=>$goods_id, ':user_id'=>$user_id));
		
		return isset($row[0]['count']) && !empty($row[0]['count']) ? 1 : 0;
	}
	
	/**
	 * 判断某个商品是否被当前用户收藏
	 *
	 * @param 	integer		$user_id	用户id
	 * @param   integer     $limit      获取列表数目或分页大小
	 * @param   integer     $page      	当前分页数
	 * @return  array
	 */
	public function get_collect_list($user_id = 0, $limit = 5, $page = 1) {
		// 获取收藏总数目
    	$sql = "SELECT COUNT(*) AS count FROM ".$this->table_prefix."collect_goods WHERE user_id = :user_id";
		$row = $this->query($sql, array(':user_id' => $user_id));
		$total = isset($row[0]['count']) && !empty($row[0]['count']) ? $row[0]['count'] : 0;
		unset($row);
				
        // 取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中
        $sql = "SELECT g.goods_id, g.cat_id,g.goods_name, g.market_price, g.shop_price AS org_price, g.promote_price, g.click_count,".
                " IFNULL(mp.user_price, g.shop_price * '".$GLOBALS['user']['discount']."') AS shop_price,".
                " promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img".
                " FROM ".$this->table_prefix."collect_goods AS c" .
                " LEFT JOIN ".$this->table_prefix."goods AS g" .
				" ON g.goods_id = c.goods_id".
                " LEFT JOIN " . $this->table_prefix. "member_price AS mp".
                " ON mp.goods_id = g.goods_id".
				" AND mp.user_rank = ".$GLOBALS['user']['user_rank'].
				" WHERE c.user_id = :user_id".
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
		if($row = $this->query($sql, array(':user_id' => $user_id))) {
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
				$arr[$k]['sales_count']			= model('goods')->get_sales_count($v['goods_id']);  // 销量
				$arr[$k]['collect_count']		= model('collect')->get_goods_collect($v['goods_id']); // 收藏量
				$arr[$k]['is_collect'] 			= model('collect')->get_goods_is_collect($v['goods_id'], $user_id); // 是否收藏
				
				unset($arr[$k]['org_price']);
/* 				unset($arr[$k]['promote_start_date']);
				unset($arr[$k]['promote_end_date']); */
			}
		}
		
		return $arr;
	}
	
	/**
	 * 添加商品收藏
	 */
	public function add_collect($goods_id = 0, $user_id = 0) {
		// 检测商品是否存在
		$is_exist = model('goods')->validate_goods_exist($goods_id);
		if ( false == $is_exist ) {
			response::show('403', 'error', '商品不存在!');
			exit();
        }
		
		// 检查商品是否己被收藏
		$row = model('collect')->findCount(array('user_id'=>$user_id, 'goods_id'=>$goods_id));
		// 商品己被用户收藏
		if ($row > 0)
		{
			$id = model('collect')->delete(array('user_id'=>$user_id, 'goods_id'=>$goods_id));
			if($id) {
				response::show('200', 'success', '该商品已经成功的从收藏夹中删除。');
				exit();
			} else {
				response::show('403', 'error', '未知错误!');
				exit();
			}
		}
		// 商品未被用户收藏
		else
		{
			$data = array(
				'user_id'	=> $user_id,
				'goods_id'	=> $goods_id,
				'add_time'	=> gmtime()
			);
			if($id = model('collect')->create($data)) {
				response::show('200', 'success', '该商品已经成功的加入了您的收藏夹。');
				exit();
			} else {
				response::show('403', 'error', '未知错误!');
				exit();
			}
		}
	}
}