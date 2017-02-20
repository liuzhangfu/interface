<?php
/**
 * 商品分类模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class category_model extends common_model {
	public $table_name = "category";
	
	public function get_child($cat_id = 0) {
		
	}
	
	/**
	 * 获得商品分类的所有信息
	 *
	 * @param   integer     $cat_id     指定的分类ID
	 * @return  mix
	 */
	public function get_cat_info($cat_id = 0) {
		$row = $this->find(array('cat_id'=>$cat_id));
		return !empty($row) ? $row : array();
	}
	
	/**
	 * 取得分类的品牌
	 *
	 * @param   int     $cat_id    当前的cat_id
	 * @return 	int
	 */
	public function get_cat_brand($cat_id = 0) {
		$brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
		
		if($cat_id > 0) {
			// 获取当前分类下的所有子分类
			$sql = "SELECT `cat_id` FROM ".$this->table_prefix."category WHERE parent_id = :cat_id";
			$child_cat_id = array();
			if($row = $this->query($sql, array(':cat_id'=>$cat_id))) {
				foreach($row as $val) {
					$child_cat_id[] = $val['cat_id'];
				}
			}
			
			$children = !empty($child_cat_id) ? 'AND g.cat_id ' . db_create_in($child_cat_id) : '';
			
			$sql = "SELECT b.brand_id, b.brand_name, COUNT(*) AS goods_num".
					" FROM " . $this->table_prefix . "brand AS b,".
						$this->table_prefix . "goods AS g".
					" LEFT JOIN ". $this->table_prefix . "goods_cat AS gc".
					" ON g.goods_id = gc.goods_id" .
					" WHERE g.brand_id = b.brand_id" .
					" $children".
					" AND b.is_show = 1" .
					" AND g.is_on_sale = 1".
					" AND g.is_alone_sale = 1".
					" AND g.is_delete = 0".
					" GROUP BY b.brand_id HAVING goods_num > 0".
					" ORDER BY b.sort_order, b.brand_id ASC";
					
			$brands = $this->query($sql);
			
			foreach ($brands AS $key => $val) {
				$temp_key 							= $key + 1;
				$brands[$temp_key]['brand_name'] 	= $val['brand_name'];
				//$brands[$temp_key]['url'] 			= build_uri('category', array('cid' => $cat_id, 'bid' => $val['brand_id'], 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);

				/* 判断品牌是否被选中 */
				if ($brand == $brands[$key]['brand_id']) {
					$brands[$temp_key]['selected'] = 1;
				} else {
					$brands[$temp_key]['selected'] = 0;
				}
			}
			$brands[0]['brand_name'] 	= '全部';
			//$brands[0]['url'] 			= build_uri('category', array('cid' => $cat_id, 'bid' => 0, 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);
			$brands[0]['selected'] 		= empty($brand) ? 1 : 0;
			
			return $brands;
		}
		return array();
	}
	
	/**
	 * 取得最近的上级分类的grade值
	 *
	 * @param   int     $cat_id    当前的cat_id
	 * @return 	mix
	 */
	public function get_parent_grade($cat_id = 0) {
		$sql = "SELECT parent_id, cat_id, grade FROM " . $this->table_prefix . "category";
		$res = $this->query($sql);
		if (false != $res) {
			$parent_arr = array();
			$grade_arr = array();

			foreach ($res as $val) {
				$parent_arr[$val['cat_id']] = $val['parent_id'];
				$grade_arr[$val['cat_id']] 	= $val['grade'];
			}

			while ($parent_arr[$cat_id] >0 && $grade_arr[$cat_id] == 0) {
				$cat_id = $parent_arr[$cat_id];
			}
			return $grade_arr[$cat_id];
			
		} else {
			return 0;
		}
	}
	
	/**
	 * 获得商品分类的价格分级
	 *
	 * @param   integer     $cat_id     指定的分类ID
	 * @return  mix
	 */
	public function get_cat_grade($cat_id = 0) {
		// 获得分类的相关信息
		$cat_info = $this->get_cat_info($cat_id);
		if (!empty($cat_info)) {
			// 获取价格分级
			if ($cat_info['grade'] == 0  && $cat_info['parent_id'] != 0) {
				$cat_info['grade'] = $this->get_parent_grade($cat_id); //如果当前分类级别为空，取最近的上级分类
			}
			
			if ($cat_info['grade'] > 1) {
				// 获取当前分类下的所有子分类
				$sql = "SELECT `cat_id` FROM ".$this->table_prefix."category WHERE parent_id = :cat_id";
				$child_cat_id = array();
				if($row = $this->query($sql, array(':cat_id'=>$cat_id))) {
					foreach($row as $val) {
						$child_cat_id[] = $val['cat_id'];
					}
				}
				$children = !empty($child_cat_id) ? 'AND g.cat_id ' . db_create_in($child_cat_id) : '';

				// 获得当前分类下商品价格的最大值、最小值
				$sql = "SELECT min(g.shop_price) AS min, max(g.shop_price) as max".
					   " FROM " . $this->table_prefix . "goods AS g".
					   " WHERE g.is_delete = 0".
					   " $children".
					   " AND g.is_on_sale = 1".
					   " AND g.is_alone_sale = 1";
					   
				$res = $this->query($sql);
				$row = !empty($res[0]) ? $res[0] : '';
				
				if(false != $row) {
					//return $row;
					// 取得价格分级最小单位级数，比如，千元商品最小以100为级数
					$price_grade = 0.0001;
					for($i=-2; $i<= log10($row['max']); $i++) {
						$price_grade *= 10;
					}

					// 跨度
					$dx = ceil(($row['max'] - $row['min']) / ($cat_info['grade']) / $price_grade) * $price_grade;
					if($dx == 0){
						$dx = $price_grade;
					}
					
					for($i = 1; $row['min'] > $dx * $i; $i ++);

					for($j = 1; $row['min'] > $dx * ($i-1) + $price_grade * $j; $j++);
					$row['min'] = $dx * ($i-1) + $price_grade * ($j - 1);

					for(; $row['max'] >= $dx * $i; $i ++);
					$row['max'] = $dx * ($i) + $price_grade * ($j - 1);
					
					$sql = "SELECT (FLOOR((g.shop_price - $row[min]) / $dx)) AS sn, COUNT(*) AS goods_num".
						   " FROM " . $this->table_prefix . "goods AS g".
						   " WHERE g.is_delete = 0".
						   " $children".
						   " AND g.is_on_sale = 1".
						   " AND g.is_alone_sale = 1".
						   " GROUP BY sn";
					$price_grade = $this->query($sql);
					
					foreach ($price_grade as $key=>$val) {
						$temp_key = $key + 1;
						$price_grade[$temp_key]['goods_num'] 	= $val['goods_num'];
						$price_grade[$temp_key]['start'] 		= $row['min'] + round($dx * $val['sn']);
						$price_grade[$temp_key]['end'] 			= $row['min'] + round($dx * ($val['sn'] + 1));
						$price_grade[$temp_key]['price_range'] 	= $price_grade[$temp_key]['start'] . '&nbsp;-&nbsp;' . $price_grade[$temp_key]['end'];
						$price_grade[$temp_key]['formated_start'] = price_format($price_grade[$temp_key]['start']);
						$price_grade[$temp_key]['formated_end'] = price_format($price_grade[$temp_key]['end']);
						
						//$price_grade[$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_grade[$temp_key]['start'], 'price_max'=> $price_grade[$temp_key]['end'], 'filter_attr'=>$filter_attr_str), $cat['cat_name']);

						/* 判断价格区间是否被选中 */
						if (isset($_REQUEST['price_min']) && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max) {
							$price_grade[$temp_key]['selected'] = 1;
						} else {
							$price_grade[$temp_key]['selected'] = 0;
						}
					}
					
					$price_grade[0]['start'] = 0;
					$price_grade[0]['end'] = 0;
					$price_grade[0]['price_range'] = '全部';
					//$price_grade[0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>0, 'price_max'=> 0, 'filter_attr'=>$filter_attr_str), $cat['cat_name']);
					$price_grade[0]['selected'] = empty($price_max) ? 1 : 0;
		
					return $price_grade;
				} else {
					return array();
				}
			}
			//return $cat_info;
		} else {
			return array();
		}
	}
}