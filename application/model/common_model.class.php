<?php
/**
 * 公共数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class common_model extends model {
    /**
     *
     * 是否存在规格
     *
     * @access      public
     * @param       array       $goods_attr_id_array        一维数组
     *
     * @return      string
     */
    public function is_spec($goods_attr_id_array, $sort = 'asc') {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        //重新排序
        $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " . $this->table_prefix . "attribute AS a
            LEFT JOIN " . $this->table_prefix . "goods_attr AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
			
        $row = $this->query($sql);
        $return_arr = array();
        foreach ($row as $value) {
            $return_arr['sort'][] = $value['goods_attr_id'];
            $return_arr['row'][$value['goods_attr_id']] = $value;
        }
		
        if (!empty($return_arr)) {
            return true;
        } else {
            return false;
        }
    }
	
    /**
     * 获得指定的规格的价格
     *
     * @access  public
     * @param   mix     $spec   规格ID的数组或者逗号分隔的字符串
     * @return  void
     */
    public function spec_price($spec) {
        if (!empty($spec)) {
            if (is_array($spec)) {
                foreach ($spec as $key => $val) {
                    $spec[$key] = addslashes($val);
                }
            } else {
                $spec = addslashes($spec);
            }

            $where = db_create_in($spec, 'goods_attr_id');

            $sql = 'SELECT SUM(attr_price) AS attr_price FROM ' . $this->table_prefix . "goods_attr WHERE $where";
            $row = $this->query($sql);
			$res = isset($row[0]) && !empty($row[0]) ? $row[0] : array();
			
            $price = floatval($res['attr_price']);
        } else {
            $price = 0;
        }
        return $price;
    }
	
    /**
     * 取得商品最终使用价格
     *
     * @param   string  $goods_id      商品编号
     * @param   string  $goods_num     购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix     $spec          规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    public function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array()) {
        $final_price = '0'; //商品最终购买价格
        $volume_price = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price = '0'; //商品会员价格
        //取得商品优惠价格列表
        $price_list = $this->get_volume_price_list($goods_id, '1');

        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        //取得商品促销价格列表
        /* 取得商品信息 */
        $sql = "SELECT g.promote_price, g.promote_start_date, g.promote_end_date, " .
                "IFNULL(mp.user_price, g.shop_price * '".$GLOBALS['user']['discount']."') AS shop_price " .
                " FROM " . $this->table_prefix . "goods AS g " .
                " LEFT JOIN " . $this->table_prefix . "member_price AS mp" .
                " ON mp.goods_id = g.goods_id AND mp.user_rank = '".$GLOBALS['user']['user_rank']."' " .
                " WHERE g.goods_id = '" . $goods_id . "'" .
                " AND g.is_delete = 0";
        $row = $this->query($sql);
		$goods = isset($row[0]) && !empty($row[0]) ? $row[0] : '';

        /* 计算商品的促销价格 */
        if ($goods['promote_price'] > 0) {
            $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
        } else {
            $promote_price = 0;
        }

        //取得商品会员价格列表
        $user_price = $goods['shop_price'];

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && empty($promote_price)) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && empty($promote_price)) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && !empty($promote_price)) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && !empty($promote_price)) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($spec)) {
                $spec_price = $this->spec_price($spec);
                $final_price += $spec_price;
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }
	
    /**
     * 取得商品优惠价格列表
     *
     * @param   string  $goods_id    商品编号
     * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
     *
     * @return  优惠价格列表
     */
    public function get_volume_price_list($goods_id, $price_type = '1') {
        $volume_price = array();
        $temp_index = '0';

        $sql = "SELECT `volume_number` , `volume_price`" .
                " FROM " . $this->table_prefix . "volume_price".
				" WHERE `goods_id` = '$goods_id'".
				" AND `price_type` = '" . $price_type . "'" .
                " ORDER BY `volume_number`";

        $res = $this->query($sql);
		
        foreach ($res as $k => $v) {
            $volume_price[$temp_index] = array();
            $volume_price[$temp_index]['number'] = $v['volume_number'];
            $volume_price[$temp_index]['price'] = $v['volume_price'];
            $volume_price[$temp_index]['format_price'] = price_format($v['volume_price']);
            $temp_index++;
        }
        return $volume_price;
    }
	
    /**
     * 获得指定的商品属性
     *
     * @access      public
     * @param       array       $arr        规格、属性ID数组
     * @param       type        $type       设置返回结果类型：pice，显示价格，默认；no，不显示价格
     *
     * @return      string
     */
    public function get_goods_attr_info($arr, $type = 'pice') {
        $attr = '';

        if (!empty($arr)) {
            $fmt = "%s:%s[%s] \n";

            $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price " .
                    "FROM " . $this->table_prefix . "goods_attr AS ga, " .
                    $this->table_prefix . "attribute AS a " .
                    "WHERE " . db_create_in($arr, 'ga.goods_attr_id') . " AND a.attr_id = ga.attr_id";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $attr_price = round(floatval($row['attr_price']), 2);
                $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
            }
            $attr = str_replace('[0]', '', $attr);
        }

        return $attr;
    }
}