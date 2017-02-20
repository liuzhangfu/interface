<?php
/**
 * 订单模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class order_model extends common_model {
	// 数据表名
	public $table_name = "order";
	
	/**
	 *  获取用户指定范围的订单列表
	 *
	 * @access  public
	 * @param   int         $user_id        用户ID号
	 * @param   int     	$limit      	获取列表数目或分页大小
	 * @param   int     	$page      		当前分页数
	 *
	 * @return  array       $order_list     订单列表
	 */
	public function get_user_orders($user_id = 0, $limit = 0, $page = 0) {
		// 获取收藏总数目
    	$sql = "SELECT COUNT(*) AS count FROM ".$this->table_prefix."order_info WHERE user_id = :user_id";
		$row = $this->query($sql, array(':user_id' => $user_id));
		$total = isset($row[0]['count']) && !empty($row[0]['count']) ? $row[0]['count'] : 0;
		unset($row);

		$sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time," .
			   " (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee".
			   " FROM " .$this->table_prefix."order_info" .
			   " WHERE user_id = :user_id ORDER BY add_time DESC";
			   
		$page_count = ceil($total / $limit);// 总分页数
		$max_page = ($page_count > 0) ? $page_count : 1;// 最大分页数
		$page = ($page > $max_page) ? $max_page : $page;// 当前分页数
		
        if( ( $page - 1 ) * $limit == 0 )  {
            $sql .= ' LIMIT ' . $limit;
        } else {
            $sql .= ' LIMIT ' . ( $page - 1 ) * $limit. ', ' . $limit;
        }

		// 取得订单列表
		$arr    = array();
		
		if($row = $this->query($sql, array('user_id'=>$user_id))) {
			foreach ($row as $val) {
                $val['shipping_status'] = ($val['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $val['shipping_status'];

                // 订单状态
				switch($val['order_status']) {
					case 0:
						$val['order_status'] = '未确认';
						break;
					case 1:
						$val['order_status'] = '已确认';
						break;
					case 2:
						$val['order_status'] = '已取消';
						break;
					case 3:
						$val['order_status'] = '无效';
						break;
					case 4:
						$val['order_status'] = '退货';
						break;
				}

	
                // 支付状态
				switch($val['pay_status']) {
					case 0:
						$val['pay_status'] = '未付款';
						break;
					case 1:
						$val['pay_status'] = '付款中';
						break;
					case 2:
						$val['pay_status'] = '已付款';
						break;
				}
				
                // 配送状态
                $val['shipping_status']          = ($val['shipping_status'] == 0)   ? '未发货'      : '';
                $val['shipping_status']          .= ($val['shipping_status'] == 1)  ? '已发货'   : '';
                $val['shipping_status']          .= ($val['shipping_status'] == 2)  ? '已收货'  : '';
                $val['shipping_status']          .= ($val['shipping_status'] == 4)  ? '退货'  : '';


                $arr[] = array('order_id'           => $val['order_id'],
                                'order_sn'          => $val['order_sn'],
                                'order_time'        => local_date('Y-m-d H:i:s', $val['add_time']),
                                'order_status'      => $val['order_status'],
                                'pay_status'        => $val['pay_status'],
                                'shipping_status'   => $val['shipping_status'],
                                'total_fee'         => price_format($val['total_fee'], false),
                                'total_page'         => $page_count,
                                'total'				=> $total

                            );
			}
		}
		
		return $arr;
		
	}
	
	/**
	 * 删除订单
	 */
	public function del_order($user_id = 0, $order_id = 0) {
		// 检查订单是否属于该用户
		$sql = "SELECT user_id FROM " .$this->table_prefix. "order_info WHERE order_id = :order_id";
		$row = $this->query($sql, array(':order_id'=>$order_id));
		$order_user = isset($row[0]['user_id']) && !empty($row[0]['user_id']) ? $row[0]['user_id'] : '';
		
		if (empty($order_user)) {
			response::show('403', 'error', '该订单不存在!');
			exit();
		} else {
			if ($order_user != $user_id) {
				response::show('403', 'error', '你没有权限操作他人订单!');
				exit();
			}
		}
		
		// 删除订单
		$this->execute("DELETE FROM ".$this->table_prefix."order_info WHERE order_id = '$order_id'");
		$this->execute("DELETE FROM ".$this->table_prefix."order_goods WHERE order_id = '$order_id'");
		$this->execute("DELETE FROM ".$this->table_prefix."order_action WHERE order_id = '$order_id'");

		return true;
	}
}