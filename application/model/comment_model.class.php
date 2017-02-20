<?php
/**
 * 评论数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class comment_model extends common_model {
	/**
	 *  获取商品详情列表
	 *
	 * @access  public
	 * @param   int     $goods_id       商品id
	 * @param   string  $comment_rank   评价星级
	 * @param   int     $limit      	列表最大数量
	 * @param   int     $page          	列表起始页
	 * @return  array
	 */
	public function get_comment_list($goods_id = 0, $comment_rank = 0, $limit = 5, $page = 1) {
		$where = '';
		if( $comment_rank > 0 ) {
			$where .= " AND c.comment_rank = '$comment_rank'";
		}
		
		// 取出所有符合条件的商品数据总数
		$sql = "SELECT COUNT(comment_id) AS count FROM ".$this->table_prefix."comment as c".
			" WHERE c.id_value = :goods_id".$where;
		
		$count = $this->query($sql, array(":goods_id"=>$goods_id));
		$total = isset($count[0]['count']) && !empty($count[0]['count']) ? $count[0]['count'] : 0;

		
        // 取出所有符合条件的商品数据，并将结果存入对应的推荐类型数组中

		$sql = "SELECT c.user_name,c.comment_id,c.add_time as comment_time, c.comment_rank,c.pics,".
			" c.content,oi.add_time as buy_time,oi.order_id" .
            " FROM " . $this->table_prefix ."comment AS c".
            " LEFT JOIN " . $this->table_prefix . "goods AS g ON g.goods_id = c.id_value" .
            " LEFT JOIN " . $this->table_prefix . "order_goods AS og ON og.goods_id = c.id_value" .
            " LEFT JOIN " . $this->table_prefix . "order_info AS oi ON oi.order_id = og.order_id" .
            " WHERE c.id_value = :goods_id".$where.
			" GROUP BY c.comment_id";
			
		$page_count = ceil($total / $limit);// 总分页数
		$max_page = ($page_count > 0) ? $page_count : 1;// 最大分页数
		$page = ($page > $max_page) ? $max_page : $page;// 当前分页数
		
        if( ( $page - 1 ) * $limit == 0 )  {
            $sql .= ' LIMIT ' . $limit;
        } else {
            $sql .= ' LIMIT ' . ( $page - 1 ) * $limit. ', ' . $limit;
        }
		
		$arr = array();
		
		if($row = $this->query($sql, array(":goods_id"=>$goods_id))) {
			foreach ($row as $val) {
				$val['comment_time'] 	= local_date($GLOBALS['api']['time_format'], $val['comment_time']);
				$val['buy_time'] 		= local_date($GLOBALS['api']['time_format'], $val['buy_time']);
				$val['pics'] 			= json_decode($val['pics'], true);
				$arr[]					= $val;
			}
		}
		
		return $arr;
	}
}