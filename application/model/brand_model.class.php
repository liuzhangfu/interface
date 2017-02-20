<?php
/**
 * 商品品牌模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class brand_model extends common_model {
	public $table_name = "brand";
	 
	/**
	 * 获得某个分类下的品牌列表
	 *
	 * @param   integer     $cat_id     指定分类id
	 * @param   integer     $limit      获取列表数目
	 * @return  array   				品牌列表
	 */
	public function get_brand_list($cat_id = 0, $limit = 5) {
		// 获取当前分类下的所有子分类
		if($cat_id > 0) {
			$sql = "SELECT `cat_id` FROM ".$this->table_prefix."category WHERE parent_id = :cat_id";
			$child_cat_id = array();
			if($row = $this->query($sql, array(':cat_id'=>$cat_id))) {
				foreach($row as $val) {
					$child_cat_id[] = $val['cat_id'];
				}
			}
		}
		// 取出所有子分类下的产品
		$children = !empty($child_cat_id) ? ' AND g.cat_id ' . db_create_in($child_cat_id) : '';
		
		$sql = "SELECT b.brand_id, b.brand_name, b.brand_logo,".
				" COUNT(g.goods_id) AS goods_num,".
				" IF(b.brand_logo > '', '1', '0') AS tag".
				" FROM " . $this->table_prefix . "brand AS b,".
				$this->table_prefix."goods AS g".
				" WHERE g.brand_id = b.brand_id $children ".
				" GROUP BY b.brand_id HAVING goods_num > 0".
				" ORDER BY tag DESC, b.sort_order ASC";
		
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
		
		$brand_list = array();
		if($row = $this->query($sql)) {
			foreach($row as $val) {
				$val['brand_logo'] = $GLOBALS['api']['http_host'].'data/brandlogo/' . $val['brand_logo'];
				unset($val['tag']);
				$brand_list[] = $val;
			}
		}
		
		return $brand_list;
	}
}