<?php
/**
 * 广告数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class ad_model extends common_model {
	/**
	 * 获取广告列表
	 *
	 * @param	integer 	$position_id	广告位id
	 * @param	integer 	$limit			调用数目
	 * @return 	array						广告列表
	 */
	public function get_ad($position_id = 0, $limit = 5) {
		// 获得广告数据
		$sql = "SELECT ad_name, ad_link, ad_code".
				" FROM " .$this->table_prefix."ad".
				" WHERE position_id = :position_id ".
				" ORDER by ad_id DESC".
				" LIMIT $limit";

		$arr = array();

		if ($row = $this->query($sql, array(':position_id' => $position_id))) {
			foreach ($row  as $v) {
				$v['ad_code'] 	= $GLOBALS['api']['http_host'].'data/afficheimg/'.$v['ad_code'];
				$arr[] 			= $v;
			}
		}

		return $arr;
	}
}