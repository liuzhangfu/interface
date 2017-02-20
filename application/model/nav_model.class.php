<?php
/**
 * 自定义导航数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class nav_model extends common_model {
	// 数据表名
	public $table_name = "nav";
	
	/**
	 * 取得自定义导航栏列表
	 *
	 * @param   string      $type    	位置，如1:top、2:bottom、3:middle
	 * @param   integer     $limit      获取列表数目或分页大小
	 * @return  array       			导航栏列表
	 */
	public function get_navigator($position_id = '', $limit = 8) {
		if( ! in_array($position_id, array('1', '2', '3') ) ) {
			return array();
		}

		$where = " WHERE 1 = 1 ";
		switch ($position_id) {
			case '1':
				$where .= "AND type = 'app_home'";
				break;
				
			case '2':
				$where .= "AND type = 'top'";
				break;
				
			case '3':
				$where .= "AND type = 'bottom'";
				break;
		}

		$sql = "SELECT id,name,url,ico FROM " . $this->table_prefix . "nav".$where;

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

		$nav_list = array();
		if($row = $this->query($sql)) {
			foreach($row as $k => $v) {
				if(! empty($v['ico']))
					$v['ico'] 	= $GLOBALS['api']['http_host'].$v['ico'];
				$nav_list[$k] = $v;	
			}
		}
		return $nav_list;
	}
}