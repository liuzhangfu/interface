<?php
/**
 * 项目自定义函数库
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
 
/**
 * 判断某个商品是否正在特价促销期
 *
 * @access  public
 * @param   float   $price      促销价格
 * @param   string  $start      促销开始日期
 * @param   string  $end        促销结束日期
 * @return  float   如果还在促销期则返回促销价，否则返回0
 */
function bargain_price($price, $start, $end) {
    if ($price == 0) {
        return 0;
    } else {
        $time = gmtime();
        if ($time >= $start && $time <= $end) {
            return $price;
        } else {
            return 0;
        }
    }
}

/**
 * 获得当前格林威治时间的时间戳
 *
 * @return  integer
 */
function gmtime() {
    return (time() - date('Z'));
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function price_format($price, $change_price = true) {
    if ($price === '') {
        $price = 0;
    }

	switch ($GLOBALS['api']['price_format']) {
		case 0:
			$price = number_format($price, 2, '.', '');
			break;

		case 1: // 保留不为 0 的尾数
			$price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));
			if (substr($price, -1) == '.') {
				$price = substr($price, 0, -1);
			}
			break;

		case 2: // 不四舍五入，保留1位
			$price = substr(number_format($price, 2, '.', '') , 0, -1);
			break;

		case 3: // 直接取整
			$price = intval($price);
			break;

		case 4: // 四舍五入，保留 1 位
			$price = number_format($price, 1, '.', '');
			break;

		case 5: // 先四舍五入，不保留小数
			$price = round($price);
			break;
	}

    return sprintf($GLOBALS['api']['currency_format'], $price);
}

/**
 * 重新获得商品图片与商品相册的地址
 *
 * @param int $goods_id 商品ID
 * @param string $image 原商品相册图片地址
 * @param boolean $thumb 是否为缩略图
 * @param string $call 调用方法(商品图片还是商品相册)
 * @param boolean $del 是否删除图片
 *
 * @return string   $url
 */
function get_image_path($goods_id, $image = '', $thumb = false, $call = 'goods', $del = false) {
    return empty($image) ? $GLOBALS['api']['no_picture'] : $image;
}

/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '') {
    if (empty($item_list)) {
        return $field_name . " IN ('') ";
    } else {
        if (!is_array($item_list)) {
            $item_list = explode(',', $item_list);
        }
        $item_list = array_unique($item_list);
        $item_list_tmp = '';
        foreach ($item_list AS $item) {
            if ($item !== '') {
                $item_list_tmp.= $item_list_tmp ? ",'$item'" : "'$item'";
            }
        }
        if (empty($item_list_tmp)) {
            return $field_name . " IN ('') ";
        } else {
            return $field_name . ' IN (' . $item_list_tmp . ') ';
        }
    }
}

/**
 *  将一个用户自定义时区的日期转为GMT时间戳
 *
 * @access  public
 * @param   string      $str
 *
 * @return  integer
 */
function local_strtotime($str) {
    $timezone = $GLOBALS['api']['timezone'];
    /**
     * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
     * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
     *
     */
    $time = strtotime($str) - $timezone * 3600;
    return $time;
}


/**
 * 将GMT时间戳格式化为用户自定义时区日期
 *
 * @param  string       $format
 * @param  integer      $time       该参数必须是一个GMT的时间戳
 *
 * @return  string
 */

function local_date($format, $time = NULL) {
    $timezone = $GLOBALS['api']['timezone'];

    if ($time === NULL) {
        $time = gmtime();
    } elseif ($time <= 0) {
        return '';
    }

    $time += ($timezone * 3600);
    return date($format, $time);
}

/**
 * 截取字符串
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   string      $end     	结尾符
 *
 * @return  string
 */
function sub_str($str, $length = 0, $end = '...') {
    $con = mb_substr($str, 0, $length, 'utf-8');
    if ($con != $str) {
        $con .= $end;
    }
    return $con;
}