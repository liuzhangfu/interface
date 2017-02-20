<?php
/**
 * 文章数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class article_model extends common_model {
	/**
	 * 获取指定分类下文章列表
	 */
	public function get_cat_articles($cat_id = 0, $limit = 5, $page = 1, $keywords = null) {
		$where = '';
		// 获取当前分类下的所有子分类
		if($cat_id > 0) {
			$sql = "SELECT `cat_id` FROM ".$this->table_prefix."article_cat WHERE parent_id = :cat_id";
			$child_cat_id = array();
			if($row = $this->query($sql, array(':cat_id'=>$cat_id))) {
				foreach($row as $val) {
					$child_cat_id[] = $val['cat_id'];
				}
			}
			$where .= !empty($child_cat_id) ? ' AND cat_id ' . db_create_in($child_cat_id) : '';
		}
		
		// 搜索关键字
		if(! empty($keywords)) {
			$where .= " AND title like \"%" . $keywords . "%\" ";
		}
		
		$sql = "SELECT COUNT(article_id) AS count" .
               " FROM " .$this->table_prefix. "article" .
               " WHERE is_open = 1".$where.
               " ORDER BY article_type DESC, article_id DESC";
			   
		$count = $this->query($sql);
		$total = isset($count[0]['count']) && !empty($count[0]['count']) ? $count[0]['count'] : 0;
		
		
		$sql = "SELECT article_id, title, author, add_time, file_url, open_type" .
               " FROM " .$this->table_prefix. "article" .
               " WHERE is_open = 1".$where.
               " ORDER BY article_type DESC, article_id DESC";
			   
		$page_count = ceil($total / $limit);// 总分页数
		$max_page = ($page_count > 0) ? $page_count : 1;// 最大分页数
		$page = ($page > $max_page) ? $max_page : $page;// 当前分页数
		
        if( ( $page - 1 ) * $limit == 0 )  {
            $sql .= ' LIMIT ' . $limit;
        } else {
            $sql .= ' LIMIT ' . ( $page - 1 ) * $limit. ', ' . $limit;
        }

		$arr = array();
		if ($row = $this->query($sql)) {
			foreach($row as $val) {
				$arr[$val['article_id']]['article_id']          = $val['article_id'];
				$arr[$val['article_id']]['title']       = $val['title'];
				$arr[$val['article_id']]['short_title'] = $GLOBALS['api']['article_title_length'] > 0 ? sub_str($val['title'], $GLOBALS['api']['article_title_length']) : $val['title'];
				$arr[$val['article_id']]['author']      = empty($val['author']) ? $GLOBALS['api']['shop_name'] : $val['author'];
				$arr[$val['article_id']]['add_time']    = date($GLOBALS['api']['date_format'], $val['add_time']);
			}
		}

		return $arr;
	}
	
	/**
	 * 获得指定的文章的详细信息
	 *
	 * @access  private
	 * @param   integer     $article_id
	 * @return  array
	 */
	public function get_article_info($article_id = 0) {
		// 获得文章的信息
		$sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank ".
				"FROM " .$this->table_prefix. "article AS a ".
				"LEFT JOIN " .$this->table_prefix. "comment AS r ON r.id_value = a.article_id AND comment_type = 1 ".
				"WHERE a.is_open = 1 AND a.article_id = :article_id GROUP BY a.article_id";
				
		$res = $this->query($sql, array(':article_id'=>$article_id));
		$row = !empty($res[0]) ? $res[0] : '';
		
		if (false != $row) {
			$row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
			$row['add_time']     = local_date($GLOBALS['api']['date_format'], $row['add_time']); // 修正添加时间显示

			// 作者信息如果为空，则用网站名称替换
			if ( empty($row['author']) ) {
				$row['author'] = $GLOBALS['api']['shop_name'];
			}
		}

		return $row;
	}
}