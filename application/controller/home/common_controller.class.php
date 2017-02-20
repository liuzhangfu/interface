<?php require (APP_PATH.DS.'config'.DS.'constant.php');
/**
 * 公共控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class common_controller extends controller {
	/**
	 * 用户id
	 */
	public $user_id;
	
	/**
	 * 析构函数
	 */
	public function init() {
		self::verify();
		self::init_user();
	}
	
	/**
	 * 接口加密认证
	 */
	private function verify() {
		$controller	= isset($_REQUEST['c']) 	&& !empty($_REQUEST['c']) 		? trim($_REQUEST['c']) 		: '';
		$token 		= isset($_REQUEST['token']) && !empty($_REQUEST['token']) 	? trim($_REQUEST['token']) 	: '';
		
		if (empty($token)) {
			response::show(403, 'error', '缺少必要的参数!');
			exit();
		}

		$hash_code = $GLOBALS['api']['hash_code'];
		if ( $token != sha1($controller.$hash_code) ) {
			response::show(403, 'error', '数据来源不合法，请返回!');
			exit();
		}
	}
	
	/**
	 * 会员设置初始化
	 */
	private function init_user() {
		// 获取用户id
		$this->user_id = isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) > 0 ? intval($_REQUEST['user_id']) : '0';
		
		if(! isset($GLOBALS['user']) || empty($GLOBALS['user'])) {
			// 获取用户信息
			$user_info = model('user')->init_user($this->user_id);
			// 初始化用户信息
			if( is_array($user_info) && count($user_info) > 0 ) {
				$GLOBALS['user'] = array();
				$GLOBALS['user'] = $user_info;
			} else {
				$GLOBALS['user'] = array();
				$GLOBALS['user']['user_id']     = 0;
				$GLOBALS['user']['user_name']   = '';
				$GLOBALS['user']['email']       = '';
				$GLOBALS['user']['user_rank']   = 0;
				$GLOBALS['user']['discount']    = 1.00;
			}
		}
	}
	
	/**
	 * 根据用户id验证用户是否存在
	 */
	public function check_user_id($user_id = 0) {
		$result = model('user')->validate_user_id_exist($user_id);
		if( false === $result ) {
			response::show('403', 'error', '非法操作!');
		}
	}
}