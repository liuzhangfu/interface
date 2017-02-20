<?php
/**
 * 购物车数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class cart_model extends common_model {
	// 数据表名
	public $table_name = "cart";
	
	/**
	 * 获得购物车中的商品
	 */
	public function get_cart_goods_list($user_id = 0) {
		// 初始化
		$goods_list = array();
		$total = array(
			'goods_price'  => 0, // 本店售价合计（有格式）
			'market_price' => 0, // 市场售价合计（有格式）
			'saving'       => 0, // 节省金额（有格式）
			'save_rate'    => 0, // 节省百分比
			'goods_amount' => 0, // 本店售价合计（无格式）
		);
		
		// 用于统计购物车中实体商品和虚拟商品的个数
		$virtual_goods_count = 0;
		$real_goods_count    = 0;
		
		// 循环、统计
		$sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid" .
				" FROM " . $this->table_prefix . "cart" .
				" WHERE user_id = :user_id" .
				" AND rec_type = '" . CART_GENERAL_GOODS . "'" .
				" ORDER BY pid, parent_id";

		if($row = $this->query($sql, array(':user_id' => $user_id))) {
			foreach($row as $k => $v) {
				$total['goods_price']  += $v['goods_price'] * $v['goods_number'];
				$total['market_price'] += $v['market_price'] * $v['goods_number'];

				$v['subtotal']     = price_format($v['goods_price'] * $v['goods_number'], false);
				$v['goods_price']  = price_format($v['goods_price'], false);
				$v['market_price'] = price_format($v['market_price'], false);

				// 统计实体商品和虚拟商品的个数
				if ($v['is_real'])
				{
					$real_goods_count++;
				}
				else
				{
					$virtual_goods_count++;
				}
				
				// 查询规格
				if (trim($v['goods_attr']) != '')
				{
					$v['goods_attr'] = addslashes($v['goods_attr']);
					$sql = "SELECT attr_value FROM " . $this->table_prefix . "goods_attr WHERE goods_attr_id " . db_create_in($v['goods_attr']);
					
					$attr_list = $this->query($sql);
					foreach ($attr_list AS $attr)
					{
						$v['goods_name'] .= ' [' . $attr . '] ';
					}
				}
				
				// 增加是否在购物车里显示商品图
				$sql = "SELECT goods_thumb FROM " . $this->table_prefix . "goods WHERE goods_id = :goods_id";
				$goods_thumb = $this->query($sql, array(':goods_id'=>$v['goods_id']));
				$v['goods_thumb'] = isset($goods_thumb[0]['goods_thumb']) ? $GLOBALS['api']['http_host'].get_image_path($v['goods_id'], $goods_thumb[0]['goods_thumb'], true) : '';
				
				if ($v['extension_code'] == 'package_buy')
				{
					$v['package_goods_list'] = get_package_goods($v['goods_id']);
				}
				
				$goods_list[] = $v;
			}

		}
		$total['goods_amount'] = $total['goods_price'];
		$total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);
		if ($total['market_price'] > 0)
		{
			$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) * 100 / $total['market_price']).'%' : 0;
		}
		$total['goods_price']  = price_format($total['goods_price'], false);
		$total['market_price'] = price_format($total['market_price'], false);
		$total['real_goods_count']    = $real_goods_count;
		$total['virtual_goods_count'] = $virtual_goods_count;

		return array('goods_list' => $goods_list, 'total' => $total);
	}
	
    /**
     * 添加商品到购物车
     *
     * @access  public
     * @param   integer $goods_id   商品编号
     * @param   integer $num        商品数量
     * @param   array   $spec       规格值对应的id数组
     * @param   integer $parent     基本件
     * @return  boolean
     */
	public function add_cart($goods_id, $num = 1, $user_id, $spec = array(), $parent = 0) {
        $_parent_id = $parent;

        /* 取得商品信息 */
        $sql = "SELECT g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, " .
                "g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date, " .
                "g.promote_end_date, g.goods_weight, g.integral, g.extension_code, " .
                "g.goods_number, g.is_alone_sale, g.is_shipping," .
                "IFNULL(mp.user_price, g.shop_price * '".$GLOBALS['user']['discount']."') AS shop_price " .
                " FROM " . $this->table_prefix . "goods AS g " .
                " LEFT JOIN " . $this->table_prefix . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '".$GLOBALS['user']['user_rank']."' " .
                " WHERE g.goods_id = '$goods_id'" .
                " AND g.is_delete = 0";
        $goods = $this->query($sql); 

		// 商品不存在
		if (empty($goods) || count($goods) <= 0) {
			response::show('403', 'error', '对不起，指定的商品不存在!');
			return false;
		}

        // 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件
        if ($parent > 0) {
            $sql = "SELECT COUNT(*) as count FROM " . $this->table_prefix .
                    "cart WHERE goods_id='$parent' AND user_id='$user_id' AND extension_code <> 'package_buy'";
					
            $res = $this->query($sql);
            if ($res[0]['count'] == 0) {
				response::show('403', 'error', '对不起，您希望将该商品做为配件购买，可是购物车中还没有该商品的基本件。');
                return false;
            }
        }

        // 是否正在销售
        if ($goods[0]['is_on_sale'] == 0) {
			response::show('403', 'error', '对不起，该商品已经下架。');
            return false;
        }

        // 不是配件时检查是否允许单独销售
        if (empty($parent) && $goods[0]['is_alone_sale'] == 0) {
			response::show('403', 'error', '对不起，该商品不能单独销售。');
            return false;
        }

        /* 如果商品有规格则取规格商品信息 配件除外 */
        $sql = "SELECT * FROM " . $this->table_prefix . "products WHERE goods_id = '$goods_id' LIMIT 0, 1";
        $prod = $this->query($sql);

        if ($this->is_spec($spec) && !empty($prod)) {
            $product_info = $this->get_products_info($goods_id, $spec);
        }

        if (empty($product_info)) {
            $product_info = array('product_number' => '', 'product_id' => 0);
        }

        // 检查：库存
		if($GLOBALS['api']['use_storage'] == 1) {
            //检查：商品购买数量是否大于总库存
            if ($num > $goods[0]['goods_number']) {
				$msg = sprintf('对不起，该商品已经库存不足暂停销售', $goods[0]['goods_number']);
				response::show('403', 'error', $msg);
                return false;
            }
            //商品存在规格 是货品 检查该货品库存
            if ($this->is_spec($spec) && !empty($prod)) {
                if (!empty($spec)) {
                    /* 取规格的货品库存 */
                    if ($num > $product_info['product_number']) {
						$msg = sprintf('对不起，该商品已经库存不足。', $product_info['product_number']);
						response::show('403', 'error', $msg);
                        return false;
                    }
                }
            }
        }

        // 计算商品的促销价格
        $spec_price = $this->spec_price($spec); // 获得指定的规格的价格
        $goods_price = $this->get_final_price($goods_id, $num, true, $spec);
        $goods[0]['market_price'] += $spec_price;
        $goods_attr = $this->get_goods_attr_info($spec);
        $goods_attr_id = join(',', $spec);
        /* 初始化要插入购物车的基本件数据 */
        $parent = array(
            'user_id' => $user_id,
            'goods_id' => $goods_id,
            'goods_sn' => addslashes($goods[0]['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods[0]['goods_name']),
            'market_price' => $goods[0]['market_price'],
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods[0]['is_real'],
            'extension_code' => $goods[0]['extension_code'],
            'is_gift' => 0,
            'is_shipping' => $goods[0]['is_shipping'],
            'rec_type' => CART_GENERAL_GOODS
        );
//return $goods;
//return $parent;
        /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
        /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
        /* 受此优惠 */
        $basic_list = array();
        $sql = "SELECT parent_id, goods_price FROM " . $this->table_prefix."group_goods".
				" WHERE goods_id = '$goods_id'" .
                " AND goods_price < '$goods_price'" .
                " AND parent_id = '$_parent_id'" .
                " ORDER BY goods_price";
				
        $res = $this->query($sql);
        foreach ($res as $row) {
            $basic_list[$row['parent_id']] = $row['goods_price'];
        }
        // 取得购物车中该商品每个基本件的数量
        $basic_count_list = array();
        if ($basic_list) {
            $sql = "SELECT goods_id, SUM(goods_number) AS count FROM " . $this->table_prefix."cart".
					" WHERE user_id = '$user_id'" .
                    " AND parent_id = 0" .
                    " AND extension_code <> 'package_buy' " .
                    " AND goods_id " . db_create_in(array_keys($basic_list)) .
                    " GROUP BY goods_id";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $basic_count_list[$row['goods_id']] = $row['count'];
            }
        }

        /* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
        /* 一个基本件对应一个该商品配件 */
        if ($basic_count_list) {
            $sql = "SELECT parent_id, SUM(goods_number) AS count " .
                    "FROM " . $this->table_prefix .
                    "cart WHERE session_id = '" . SESS_ID . "'" .
                    " AND goods_id = '$goods_id'" .
                    " AND extension_code <> 'package_buy' " .
                    " AND parent_id " . db_create_in(array_keys($basic_count_list)) .
                    " GROUP BY parent_id";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $basic_count_list[$row['parent_id']] -= $row['count'];
            }
        }

        // 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件
        foreach ($basic_list as $parent_id => $fitting_price) {
            /* 如果已全部插入，退出 */
            if ($num <= 0) {
                break;
            }

            /* 如果该基本件不再购物车中，执行下一个 */
            if (!isset($basic_count_list[$parent_id])) {
                continue;
            }

            /* 如果该基本件的配件数量已满，执行下一个基本件 */
            if ($basic_count_list[$parent_id] <= 0) {
                continue;
            }

            /* 作为该基本件的配件插入 */
            $parent['goods_price'] = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
            $parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
            $parent['parent_id'] = $parent_id;

            /* 添加 */
            $this->table = 'cart';
            $this->insert($parent);
            /* 改变数量 */
            $num -= $parent['goods_number'];
        }

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */
            $sql = "SELECT goods_number FROM " . $this->table_prefix ."cart".
					" WHERE user_id = '$user_id'".
					" AND goods_id = '$goods_id'".
                    " AND parent_id = 0".
					" AND goods_attr = '" . Model('common')->get_goods_attr_info($spec) . "' " .
                    " AND extension_code <> 'package_buy' " .
                    " AND rec_type = 'CART_GENERAL_GOODS'";

            $row = $this->query($sql);

            if ($row) { //如果购物车已经有此物品，则更新
                $num += $row[0]['goods_number'];
                if ($this->is_spec($spec) && !empty($prod)) {
                    $goods_storage = $product_info['product_number'];
                } else {
                    $goods_storage = $goods[0]['goods_number'];
                }
                if ($GLOBALS['api']['use_storage'] == 0 || $num <= $goods_storage) {
                    $goods_price = Model('goods')->get_final_price($goods_id, $num, true, $spec);
                    $sql = "UPDATE " . $this->table_prefix . "cart SET goods_number = '$num'" .
                            " , goods_price = '$goods_price'" .
                            " WHERE user_id = '$user_id'".
							" AND goods_id = '$goods_id'".
                            " AND parent_id = 0 AND goods_attr = '" . Model('common')->get_goods_attr_info($spec) . "' " .
                            " AND extension_code <> 'package_buy'" .
                            " AND rec_type = 'CART_GENERAL_GOODS'";
                    $this->query($sql);
                } else {
					response::show('403', 'error', sprintf('对不起，该商品已经库存不足。', $num));
                    return false;
                }
            } else { //购物车没有此物品，则插入
                $goods_price = $this->get_final_price($goods_id, $num, true, $spec);
                $parent['goods_price'] = max($goods_price, 0);
                $parent['goods_number'] = $num;
                $parent['parent_id'] = 0;

				$this->create($parent);
            }
        }

        /* 把赠品删除 */
        $sql = "DELETE FROM " . $this->table_prefix . "cart WHERE user_id = '$user_id' AND is_gift <> 0";
        $this->query($sql);

        return true;
	}
	
	/**
	 * 更新购物车
	 */
	public function update_cart($rec_id = 0, $number = 0, $user_id = 0) {
		// 获取购物车信息
		$sql = "SELECT goods_id,goods_attr_id,product_id,extension_code FROM ".$this->table_prefix."cart".
				" WHERE rec_id = :rec_id".
				" AND user_id = :user_id";
		$row = $this->query($sql, array(':rec_id'=>$rec_id, ':user_id'=>$user_id));
		$goods = isset($row[0]) && !empty($row[0]) ? $row[0] : '';
		unset($row);
		
		if (empty($goods) || count($goods) <= 0) {
			response::show('403', 'error', '您的购物车中没有商品!');
			return false;
		}
		
		// 获取商品信息
		$sql = "SELECT g.goods_name,g.goods_number FROM " . $this->table_prefix . "goods AS g, " 
				. $this->table_prefix . "cart AS c" . 
				" WHERE g.goods_id = c.goods_id".
				" AND c.rec_id = '$rec_id'";
		$result = $this->query($sql);
		$row = isset($result[0]) && !empty($result[0]) ? $result[0] : '';
		unset($result);
		
        // 查询：系统启用了库存，检查输入的商品数量是否有效
        if (intval($GLOBALS['api']['use_storage']) > 0 && $goods['extension_code'] != 'package_buy') {
            if ($row['goods_number'] < $number) {
				$msg = sprintf('非常抱歉，您选择的商品: %s 的库存数量只有 %d，您最多只能购买 %d 件。', $row['goods_name'],$row['goods_number'], $row['goods_number']);
				response::show('403', 'error', $msg);
                return false;
            }
            /* 是货品 */
            $goods['product_id'] = trim($goods['product_id']);
            if (!empty($goods['product_id'])) {
                $sql = "SELECT product_number FROM " .$this->table_prefix."products WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $goods['product_id'] . "'";
				$row = $this->query($sql);
                $product_number = isset($row[0]['product_number']) && !empty($row[0]['product_number']) ? $row[0]['product_number'] : '0';
                if ($product_number < $number) {
					$msg = sprintf('非常抱歉，您选择的商品: %s 的库存数量只有 %d，您最多只能购买 %d 件。', $row['goods_name'],$product_number['product_number'], $product_number['product_number']);
					response::show('403', 'error', $msg);
					return false;
                }
            }
		} elseif (intval($GLOBALS['api']['use_storage']) > 0 && $goods['extension_code'] == 'package_buy') {
            if (Model('common')->judge_package_stock($goods['goods_id'], $number))  {
				response::show('403', 'error', '非常抱歉，您选择的超值礼包数量已经超出库存。请您减少购买量或联系商家。');
				return false;
                exit;
            }
        }
        /* 查询：检查该项是否为基本件 以及是否存在配件 */
        /* 此处配件是指添加商品时附加的并且是设置了优惠价格的配件 此类配件都有parent_id goods_number为1 */
        $sql = "SELECT b.goods_number, b.rec_id".
                " FROM " .$this->table_prefix."cart a, " .$this->table_prefix."cart b".
                " WHERE a.rec_id = '$rec_id'".
                " AND a.user_id = '$user_id'".
                " AND a.extension_code <> 'package_buy'".
                " AND b.parent_id = a.goods_id".
                " AND b.user_id = '$user_id'";
		$offers_accessories_res = $this->query($sql);

		//订货数量大于0
		if ($number > 0) {
			/* 判断是否为超出数量的优惠价格的配件 删除*/
			$row_num = 1;
			if($offers_accessories_res) {

				foreach($offers_accessories_res as $offers_accessories_row) {
					
					if ($row_num > $number){
						$sql = "DELETE FROM " . $this->table_prefix . "cart" .
								" WHERE user_id = '$user_id' " .
								"AND rec_id = '" . $offers_accessories_row['rec_id'] ."' LIMIT 1";
								
						$this->query($sql);
					}

					$row_num ++;
				}
			}

			/* 处理超值礼包 */
			if (!empty($goods) && $goods['extension_code'] == 'package_buy') {
				
				// 更新购物车中的商品数量
				$sql = "UPDATE " .$this->table_prefix . "cart SET goods_number = '$number' WHERE rec_id='$rec_id' AND user_id='$user_id'";
				$this->query($sql);
				return true;
			}
			/* 处理普通商品或非优惠的配件 */
			else
			{
				$attr_id    = empty($goods['goods_attr_id']) ? array() : explode(',', $goods['goods_attr_id']);
				$goods_price =  Model('goods')->get_final_price($goods['goods_id'], $number, true, $attr_id);

				//更新购物车中的商品数量
				$sql = "UPDATE " .$this->table_prefix . "cart SET goods_number = '$number', goods_price = '$goods_price' WHERE rec_id='$rec_id' AND user_id='$user_id'";
				$this->query($sql);
				return true;
			}
		}
	}
	
	/**
	 * 删除购物车中的商品
	 *
	 * @access  public
	 * @param   integer $id
	 * @return  void
	 */
	public function del_cart($user_id, $rec_id) {
		/* 取得商品id */
		$sql = "SELECT * FROM " .$this->table_prefix . "cart WHERE rec_id = '$rec_id' AND user_id = :user_id";
		$row = $this->query($sql, array(':user_id'=>$user_id));
		if ($row) {
			// 如果是超值礼包
			if ($row[0]['extension_code'] == 'package_buy') {
				$sql = "DELETE FROM " . $this->table_prefix . "cart" .
						" WHERE user_id = '$user_id' " .
						"AND rec_id = '$rec_id' LIMIT 1";
			}

			// 如果是普通商品，同时删除所有赠品及其配件
			elseif ($row[0]['parent_id'] == 0 && $row[0]['is_gift'] == 0)
			{
				/* 检查购物车中该普通商品的不可单独销售的配件并删除 */
				$sql = "SELECT c.rec_id
						FROM " . $this->table_prefix. "cart AS c, " . $this->table_prefix."group_goods AS gg, " .  $this->table_prefix ."goods AS g
						WHERE gg.parent_id = '" . $row[0]['goods_id'] . "'
						AND c.goods_id = gg.goods_id
						AND c.parent_id = '" . $row[0]['goods_id'] . "'
						AND c.extension_code <> 'package_buy'
						AND gg.goods_id = g.goods_id
						AND g.is_alone_sale = 0";
						
				$res = $this->query($sql);
				$_del_str = $rec_id . ',';
				foreach($res as $id_alone_sale_goods) {
					$_del_str .= $id_alone_sale_goods['rec_id'] . ',';
				}

				$_del_str = trim($_del_str, ',');

				$sql = "DELETE FROM " . $this->table_prefix . "cart" .
						" WHERE user_id = '$user_id' " .
						"AND (rec_id IN ($_del_str) OR parent_id = ".$row[0]['goods_id']." OR is_gift <> 0)";
				
			}

			//如果不是普通商品，只删除该商品即可
			else
			{
				$sql = "DELETE FROM " . $this->table_prefix . "cart" .
						" WHERE user_id = '$user_id' " .
						"AND rec_id = '$rec_id' LIMIT 1";
			}
			$this->query($sql);
			return true;
		} else {
			response::show('403', 'error', '您的购物车中没有商品！');
			return false;
		}
	}
	
	/** 
	 * 清空购物车
	 */
	public function clear_cart($user_id, $type = CART_GENERAL_GOODS) {
		$sql = "DELETE FROM " . $this->table_prefix . "cart" .
            " WHERE user_id = :user_id" .
			" AND rec_type = :type";

		return $this->execute($sql, array(':user_id'=>$user_id, ':type'=>$type));
	}
}