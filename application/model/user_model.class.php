<?php
/**
 * 用户数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class user_model extends common_model {
	// 数据表名
	public $table_name = "users";
    
	/**
	 * 获取用户基本信息并初始化折扣、会员等级
	 *
	 * @access  public
	 * @return  void
	 */
	public function init_user($user_id) {
		if(!isset($user_id) || intval($user_id) <= 0) {
			return false;
		}

		// 查询会员信息
		$time = date('Y-m-d');
		
		$sql = "SELECT u.user_id, u.email, u.user_money, u.pay_points, u.user_rank, u.rank_points,".
				" IFNULL(b.type_money, 0) AS user_bonus, u.last_login, u.last_ip".
				" FROM " .$this->table_prefix. "users AS u".
				" LEFT JOIN " .$this->table_prefix. "user_bonus AS ub".
				" ON ub.user_id = u.user_id AND ub.used_time = 0".
				" LEFT JOIN " .$this->table_prefix. "bonus_type AS b".
				" ON b.type_id = ub.bonus_type_id AND b.use_start_date <= '$time'".
				" AND b.use_end_date >= '$time'".
				" WHERE u.user_id = '$user_id'";

		$result = $this->query($sql);
		$row = isset($result[0]) && !empty($result[0]) ? $result[0] : '';
		unset($result);
		$user = array();;
		if ($row) {
			$user['user_id']	= $row['user_id'];
			$user['last_time']	= $row['last_login'];
			$user['last_ip']	= $row['last_ip'];
			$user['email'] 		= $row['email'];

			// 判断是否是特殊等级，可能后台把特殊会员组更改普通会员组
			if($row['user_rank'] >0) {
				$sql = "SELECT special_rank from ".$this->table_prefix."user_rank WHERE rank_id='$row[user_rank]'";
				
				$result = $this->query($sql);
				$row = isset($result[0]) && !empty($result[0]) ? $result[0] : '0';
				unset($result);
				if($row['special_rank'] === '0' || $row['special_rank'] === NULL) {  
				
					$sql = "UPDATE " .$this->table_prefix. "users SET".
						   " user_rank = '0'".
						   " WHERE user_id = '$user_id'";

					$this->query($sql);
					$row['user_rank'] = 0;
				}
			}

			// 取得用户等级和折扣
			if ($row['user_rank'] == 0) {
				// 非特殊等级，根据等级积分计算用户等级（注意：不包括特殊等级）
				$sql = "SELECT rank_id, discount FROM ".$this->table_prefix."user_rank".
						" WHERE special_rank = '0'".
						" AND min_points <= '" . intval($row['rank_points']) . "'".
						" AND max_points > '" . intval($row['rank_points'])."'";

				$result = $this->query($sql);
				$row = isset($result[0]) && !empty($result[0]) ? $result[0] : '0';
				unset($result);
				if ($row) {
					$user['user_rank'] = $row['rank_id'];
					$user['discount']  = $row['discount'] / 100.00;
				} else {
					$user['user_rank'] = 0;
					$user['discount']  = 1;
				}
				
			} else {
				// 特殊等级
				$sql = "SELECT rank_id, discount FROM " .$this->table_prefix. "user_rank WHERE rank_id = '$row[user_rank]'";
				$result = $this->query($sql);
				$row = isset($result[0]) && !empty($result[0]) ? $result[0] : '0';
				unset($result);
				if ($row) {
					$user['user_rank'] = $row['rank_id'];
					$user['discount']  = $row['discount'] / 100.00;
				} else {
					$user['user_rank'] = 0;
					$user['discount']  = 1;
				}
			}
		}
		return $user;
	}
	
    /**
     * 登录检测
	 *
     * @param 	string 	$username 用户名或邮箱或手机号码
     * @param 	string 	$password 密码
     * @return 	array|bool
     */
	public function check_login($username = null, $password = null) {
		if(empty($username) || empty($password)) {
			response::show('403', 'error', '用户名或密码不能为空!');
    		return false;
    	}
		
		$validate = new validate();

		// 用户名是邮箱格式
		if( $validate->is_email($username)) {
			$condition['email'] = $username;
			$row = $this->find($condition);
			$username = !empty($row['user_name']) ? $row['user_name'] : '';
		}
		
		// 用户名是手机格式
		if($validate->is_phone($username)) {
			$condition['mobile_phone'] = $username;
			$row = $this->find($condition);
			$username = !empty($row['user_name']) ? $row['user_name'] : '';
		}
		
		// 获取加密因子
		$salt = $this->get_salt($username);
		
    	// 组装用户密码
		if(!empty($salt)) {
			$password = md5(md5($password).$salt); 
		} else {
			$password = md5($password); 
		}
		
		// 获取用户信息
		$user_info = $this->find(array('user_name'=>$username), null, 'user_id,password');
		if( false != $user_info ) {
			if($user_info['password'] != $password) {
				response::show('403', 'error', '密码错误!');
				return false;
			} else {
				return true;
			}
		} else {
			response::show('403', 'error', '用户不存在!');
    		return false;
		}
	}
	
    /**
     * 会员注册,只支持app端手机号码注册
	 *
     * @param 	string 	$mobile 	手机号码
     * @param 	string 	$password 	密码
     * @return 	array|bool
     */
	public function register($mobile = null, $password = null) {
		if(empty($mobile) || empty($password)) {
			response::show('403', 'error', '手机号码或密码不能为空!');
    		return false;
    	}
		// 检查手机号码格式
		if(! $this->validate_mobile_format($mobile)) {
			response::show('403', 'error', '手机号码格式不正确!');
    		return false;
		}
		
		// 检查手机号码是否己使用
		if(! $this->validate_mobile_exist($mobile)) {
			response::show('403', 'error', '手机号码己使用!');
    		return false;
		}
		
		// 检查密码是否符合规则,可包含字母、数字或特殊符号，长度为6-32个字符
		if(! $this->validate_password_format($password)) {
			response::show('403', 'error', '密码格式不正确!');
    		return false;
		}
		
    	// 随机四位加密因子
    	$salt = rand(1,9999);
    	// 插入注册用户数据
    	$data = array(
    		'user_name' 	=> '百次幂_'.random(8,3),
    		'password' 		=> md5(md5($password).$salt),
    		'mobile_phone' 	=> $mobile,
    		'ec_salt'		=> $salt,
			'reg_time'		=> time()
    	);
    	if($insert_id = $this->create($data)) {
    		return true;
    	} else {
    		return false;
    	}
	}
	
	/**
	 * 修改密码，通过手机号码修改密码
	 *
     * @param 	string 	$mobile 	手机号码
     * @param 	string 	$password 	密码
     * @return 	array|bool
	 */
	public function change_password($mobile = null, $password = null) {
		if(empty($mobile) || empty($password)) {
			response::show('403', 'error', '手机号码或密码不能为空!');
    		return false;
    	}
		
		// 检查密码是否符合规则,可包含字母、数字或特殊符号，长度为6-32个字符
		if(! $this->validate_password_format($password)) {
			response::show('403', 'error', '密码格式不正确!');
    		return false;
		}
		
    	// 随机四位加密因子
    	$salt = rand(1,9999);
		
    	// 组装用户密码
		if(!empty($salt)) {
			$password = md5(md5($password).$salt); 
		} else {
			$password = md5($password); 
		}
		
		// 修改密码及密码因子
		$sql = "UPDATE ".$this->table_prefix."users".
				" SET password = :password,".
				" ec_salt = :salt".
				" WHERE mobile_phone = :mobile";
				
		return $this->execute($sql, array(':password' => $password, ':salt' => $salt, ':mobile' => $mobile)) ? true : false;
	}
	
    /**
     * 获取加密因子
     *
     * @param 	$username 	用户名
     * @return 	string|bool 加密因子信息
     */
	public function get_salt($username) {
		$row = $this->find(array('user_name' => $username), null, 'ec_salt');
		return $row ? $row['ec_salt'] : '';
	}
	
	/**
	 * 检查手机号码是否合法
	 */
    public function validate_mobile_format($val) {
        return preg_match('/^\d{11}$/', $val) != 0;
    }
	
	/**
	 * 检查手机号码是否存在
	 */
    public function validate_mobile_exist($val) {
		return $this->find(array('mobile_phone'=>$val)) ? true : false;
    }

	/**
	 * 检查密码格式(可包含字母、数字或特殊符号，长度为6-32个字符)
	 */
    public function validate_password_format($val) {
        return preg_match('/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{5,31}$/', $val) != 0;
    }
	
	/**
	 * 检测用户id是否存在
	 */
	public function validate_user_id_exist($user_id = 0) {
		return $this->find(array('user_id'=>$user_id)) ? true : false;
	}
}