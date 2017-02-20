<?php
/**
 * 数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class model {
	/**
	 * 分页
	 */
	public $page;
	/**
	 * 表名称
	 */
	public $table_name;
	/**
	 * sql语句组
	 */
	private $sql = array();
	/**
	 * 数据表前缀
	 */
	public $table_prefix;
	
	/**
	 * 构造函数
	 */
	public function __construct($table_name = null) {
		if($table_name) 
			$this->table_name = $table_name;
		else 
			$this->table_name = $GLOBALS['db']['db_prefix'] . $this->table_name;
		
		$this->table_prefix = $GLOBALS['db']['db_prefix'];
	}
	
	/**
	 * 从数据表中查找记录
	 *
	 * @param conditions    查找条件，数组array("字段名"=>"查找值")或字符串，
	 * @param sort    排序，等同于“ORDER BY ”
	 * @param fields    返回的字段范围，默认为返回全部字段的值
	 * @param limit    返回的结果数量限制，等同于“LIMIT ”，如$limit = " 3, 5"，即是从第3条记录（从0开始计算）开始获取，共获取5条记录
	 *                 如果limit值只有一个数字，则是指代从0条记录开始。
	 */
	public function findAll($conditions = array(), $sort = null, $fields = '*', $limit = null) {
		$sort = !empty($sort) ? ' ORDER BY '.$sort : '';
		$conditions = $this->_where($conditions);

		$sql = ' FROM '.$this->table_name.$conditions["_where"];
		if(is_array($limit)) {
			$total = $this->query('SELECT COUNT(*) as M_COUNTER '.$sql, $conditions["_bindParams"]);
			if(!isset($total[0]['M_COUNTER']) || $total[0]['M_COUNTER'] == 0)return false;
			
			$limit = $limit + array(1, 10, 10);
			$limit = $this->pager($limit[0], $limit[1], $limit[2], $total[0]['M_COUNTER']);
			$limit = empty($limit) ? '' : ' LIMIT '.$limit['offset'].','.$limit['limit'];			
		} else {
			$limit = !empty($limit) ? ' LIMIT '.$limit : '';
		}
		return $this->query('SELECT '. $fields . $sql . $sort . $limit, $conditions["_bindParams"]);
	}
	
	/**
	 * 从数据表中查找一条记录
	 *
	 * @param $conditions   查找条件，数组array("字段名"=>"查找值")或字符串，
	 * @param $sort    		排序，等同于“ORDER BY ”
	 * @param $fields    	返回的字段范围，默认为返回全部字段的值
	 */
	public function find($conditions = array(), $sort = null, $fields = '*') {
		$res = $this->findAll($conditions, $sort, $fields, 1);
		return !empty($res) ? array_pop($res) : false;
	}
	
	/**
	 * 修改数据，该函数将根据参数中设置的条件而更新表中数据
	 * 
	 * @param $conditions   数组形式，查找条件
	 * @param $row    		数组形式，修改的数据，
	 */
	public function update($conditions, $row) {
		$values = array();
		foreach ($row as $k=>$v){
			$values[":M_UPDATE_".$k] = $v;
			$setstr[] = "`{$k}` = ".":M_UPDATE_".$k;
		}
		$conditions = $this->_where( $conditions );
		return $this->execute("UPDATE ".$this->table_name." SET ".implode(', ', $setstr).$conditions["_where"], $conditions["_bindParams"] + $values);
	}

	/**
	 * 为设定的字段值增加
	 *
	 * @param $conditions   数组形式，查找条件
	 * @param $field    	字符串，需要增加的字段名称，该字段务必是数值类型
	 * @param $optval    	增加的值
	 */
	public function incr($conditions, $field, $optval = 1) {
		$conditions = $this->_where( $conditions );
		return $this->execute("UPDATE ".$this->table_name." SET `{$field}` = `{$field}` + :M_INCR_VAL ".$conditions["_where"], $conditions["_bindParams"] + array(":M_INCR_VAL" => $optval));
	}
	
	/**
	 * 为设定的字段值减少
	 *
	 * @param $conditions   数组形式，查找条件
	 * @param $field    	字符串，需要减少的字段名称，该字段务必是数值类型
	 * @param $optval    	减少的值
	 */
	public function decr($conditions, $field, $optval = 1) {
		return $this->incr($conditions, $field, - $optval);
	}
	
	/**
	 * 按条件删除记录
	 *
	 * @param conditions 数组形式，查找条件，此参数的格式用法与find/findAll的查找条件参数是相同的。
	 */
	public function delete($conditions) {
		$conditions = $this->_where( $conditions );
		return $this->execute("DELETE FROM ".$this->table_name.$conditions["_where"], $conditions["_bindParams"]);
	}
	
	/**
	 * 在数据表中新增一行数据
	 *
	 * @param row 数组形式，数组的键是数据表中的字段名，键对应的值是需要新增的数据。
	 */
	public function create($row) {
		$values = array();
		foreach($row as $k=>$v){
			$keys[] = "`{$k}`"; 
			$values[":".$k] = $v; 
			$marks[] = ":".$k;
		}
		$this->execute("INSERT INTO ".$this->table_name." (".implode(', ', $keys).") VALUES (".implode(', ', $marks).")", $values);
		return $this->dbInstance($GLOBALS['db'], 'master')->lastInsertId();
	}
	
	/**
	 * 计算符合条件的记录数量
	 *
	 * @param conditions 查找条件，数组array("字段名"=>"查找值")或字符串，
	 */
	public function findCount($conditions) {
		$conditions = $this->_where( $conditions );
		$count = $this->query("SELECT COUNT(*) AS M_COUNTER FROM ".$this->table_name.$conditions["_where"], $conditions["_bindParams"]);
		return isset($count[0]['M_COUNTER']) && $count[0]['M_COUNTER'] ? $count[0]['M_COUNTER'] : 0;
	}
	
	/**
	 * 返回最后执行的SQL语句供分析
	 */
	public function dumpSql(){ return $this->sql; }
	
	/** 
	 * 生成分页数据
	 */
	public function pager($page, $pageSize = 10, $scope = 10, $total) {
		$this->page = null;
		if($total > $pageSize) {
			$total_page = ceil($total / $pageSize);
			$page = min(intval(max($page, 1)), $total);
			$this->page = array(
				'total_count' => $total, 								// 总记录数
				'page_size'   => $pageSize,								// 分页大小
				'total_page'  => $total_page,							// 总页数
				'first_page'  => 1,										// 第一页
				'prev_page'   => ( ( 1 == $page ) ? 1 : ($page - 1) ),	// 上一页
				'next_page'   => ( ( $page == $total_page ) ? $total_page : ($page + 1)),// 下一页
				'last_page'   => $total_page,							// 最后一页
				'current_page'=> $page,									// 当前页
				'all_pages'   => array(),								// 全部页码
				'offset'      => ($page - 1) * $pageSize,
				'limit'       => $pageSize,
			);
			$scope = (int)$scope;
			if($total_page <= $scope ) {
				$this->page['all_pages'] = range(1, $total_page);
			}elseif( $page <= $scope/2) {
				$this->page['all_pages'] = range(1, $scope);
			}elseif( $page <= $total_page - $scope/2 ){
				$right = $page + (int)($scope/2);
				$this->page['all_pages'] = range($right-$scope+1, $right);
			}else{
				$this->page['all_pages'] = range($total_page-$scope+1, $total_page);
			}
		}
		return $this->page;
	}
	
	/**
	 * 查询SQL语句，返回查询结果集
	 *
	 * @param $sql 		字符串，需要执行的SQL语句
	 * @param $params 	绑定参数
	 */
	public function query($sql, $params = array()) {
		return $this->execute($sql, $params, true);
	}
	
	/**
	 * 执行SQL语句，相等于执行新增，修改，删除等操作。
	 *
	 * @param $sql 		字符串，需要执行的SQL语句
	 * @param $params 	绑定参数
	 * @param $readonly 返回获取结果集或影响的行数，true：结果集，false:影响行数
	 */
	public function execute($sql, $params = array(), $readonly = false){
		$this->sql[] = $sql;

		if($readonly && !empty($GLOBALS['db']['MYSQL_SLAVE'])){
			$slave_key = array_rand($GLOBALS['db']['MYSQL_SLAVE']);
			$sth = $this->dbInstance($GLOBALS['db']['MYSQL_SLAVE'][$slave_key], 'slave_'.$slave_key)->prepare($sql);
		}else{
			$sth = $this->dbInstance($GLOBALS['db'], 'master')->prepare($sql);
		}
		
		if(is_array($params) && !empty($params)){
			foreach($params as $k => &$v){
				if(is_int($v)){
					$data_type = PDO::PARAM_INT;
				}elseif(is_bool($v)){
					$data_type = PDO::PARAM_BOOL;
				}elseif(is_null($v)){
					$data_type = PDO::PARAM_NULL;
				}else{
					$data_type = PDO::PARAM_STR;
				}
				$sth->bindParam($k, $v, $data_type);
			}
		}

		if($sth->execute()) return $readonly ? $sth->fetchAll(PDO::FETCH_ASSOC) : $sth->rowCount();
		$err = $sth->errorInfo();
		halt('Database SQL: "' . $sql. '", ErrorInfo: '. $err[2]);
	}
	
	/** 
	 * 单例模式
	 */
	public function dbInstance($db_config, $db_config_key, $force_replace = false){
		if($force_replace || empty($GLOBALS['mysql_instances'][$db_config_key])){
			try {
				$GLOBALS['mysql_instances'][$db_config_key] = new PDO('mysql:dbname='.$db_config['db_name'].';host='.$db_config['db_host'].';port='.$db_config['db_port'], $db_config['db_user'], $db_config['db_pwd'], array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES \''.$db_config['db_charset'].'\''));
			} catch (PDOException $e) {
				halt('Database error: '.$e->getMessage());
			}
		}
		return $GLOBALS['mysql_instances'][$db_config_key];
	}
	
	/** 
	 * 组装条件语句
	 *
	 * @param conditions 查找条件，数组array("字段名"=>"查找值")或字符串
	 */
	private function _where($conditions){
		$result = array( "_where" => " ","_bindParams" => array());
		if(is_array($conditions) && !empty($conditions)){
			$fieldss = array(); $sql = null; $join = array();
			if(isset($conditions[0]) && $sql = $conditions[0]) unset($conditions[0]);
			foreach( $conditions as $key => $condition ){
				if(substr($key, 0, 1) != ":"){
					unset($conditions[$key]);
					$conditions[":".$key] = $condition;
				}
				$join[] = "`{$key}` = :{$key}";
			}
			if(!$sql) $sql = join(" AND ",$join);

			$result["_where"] = " WHERE ". $sql;
			$result["_bindParams"] = $conditions;
		}
		return $result;
	}
}