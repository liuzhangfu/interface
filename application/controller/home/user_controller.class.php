<?php
/**
 * 会员控制器
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class user_controller extends common_controller {
	/**
	 * 构造函数
	 */
	public function init() {
		parent::init();
		// 根据用户id检测用户是否存在
		if(! in_array(ACTION_NAME, array('login', 'register', 'chgpw', 'is_mobile_exist', 'sms'))) {
			self::check_user_id($this->user_id);
		}
	}
	/**
	 * 会员登录
	 */
	public function login() {
		$username = !empty($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
		$password = !empty($_REQUEST['password']) ? trim($_REQUEST['password']) : '';

		$result = model('user')->check_login($username, $password);
		if( false != $result ) {
			response::show('200', 'success', '登录成功!');
		} else {
			response::show('403', 'error', $result);
		}
	}
	
	/**
	 * 会员注册
	 */
	public function register() {
 		$mobile 	= !empty($_REQUEST['mobile']) 	? 	trim($_REQUEST['mobile']) 	: '';
		$password 	= !empty($_REQUEST['password']) ? 	trim($_REQUEST['password']) : '';
		
		$result = model('user')->register($mobile, $password);
		if( false != $result ) {
			response::show('200', 'success', '恭喜你，注册成功!');
		} else {
			response::show('403', 'error', $result);
		}
	}
	
	/**
	 * 修改密码
	 */
	public function chgpw() {
 		$mobile 	= !empty($_REQUEST['mobile']) 	? 	trim($_REQUEST['mobile']) 	: '';
		$password 	= !empty($_REQUEST['password']) ? 	trim($_REQUEST['password']) : '';
		
		$result = model('user')->change_password($mobile, $password);
		if( false != $result ) {
			response::show('200', 'success', '恭喜你，密码修改成功!');
		} else {
			response::show('403', 'error', '密码修改失败!');
		}
	}
	
	/**
	 * 判断手机号码是否存在
	 */
	public function is_mobile_exist() {
 		$mobile 	= !empty($_REQUEST['mobile']) 	? 	trim($_REQUEST['mobile']) 	: '';
		
		if(empty($mobile)) {
			response::show('403', 'error', '手机号码不能为空!');
		}
		
		$result = model('user')->validate_mobile_exist($mobile);
		if( false != $result ) {
			response::show('403', 'error', '该手机号码己存在!');
		} else {
			response::show('200', 'success', '该手机号码不存在!');
		}
	}
	
	/**
	 * 发送短信
	 */
	public function sms() {
 		$mobile 	= !empty($_REQUEST['mobile']) 	? 	trim($_REQUEST['mobile']) 	: '';
		
		$verify = mt_rand(1000, 9999);
		$req = new alidayu();
		$result = $req -> send_sms($mobile, 'SMS_10555008', '身份验证', "{'code':'" . $verify . "','product':'你好啊'}");
		// 短信发送成功
		if(isset($result['alibaba_aliqin_fc_sms_num_send_response'])) {
			response::show('200', 'success', $verify);
			exit();
		} else {
			response::show('403', 'error', '您的操作太频繁，请稍候再试!');
			exit();
		}
	}
	
	/**
	 * 获取用户收货地址信息
	 */
	public function address_list() {
		$address_list = model('address')->get_address_list($this->user_id);
		if( false != $address_list ) {
			response::show('200', 'success', $address_list);
			exit();
        } else {
			response::show('200', 'success', '没有数据!');
			exit();
        }
	}
	
	/**
	 * 添加收货地址
	 */ 
	public function add_address() {
		// 变量初始化
		$data = array(
			'user_id' 	=> $this->user_id,
			'consignee' => isset($_REQUEST['consignee']) 	? trim($_REQUEST['consignee']) 					: '',	// 收货人姓名
			'mobile' 	=> isset($_REQUEST['mobile']) 		? trim($_REQUEST['mobile']) 					: '',	// 收货人手机号码
			'country'	=> isset($_REQUEST['country']) 		? intval($_REQUEST['country']) 					: 0,	// 收货人的国家
			'province' 	=> isset($_REQUEST['province']) 	? intval($_REQUEST['province']) 				: 0,	// 收货人的省份
			'city' 		=> isset($_REQUEST['city']) 		? intval($_REQUEST['city']) 					: 0,	// 收货人城市
			'district'	=> isset($_REQUEST['district']) 	? intval($_REQUEST['district']) 				: 0,	// 收货人的地区
			'address' 	=> isset($_REQUEST['address']) 		? htmlspecialchars(trim($_REQUEST['address'])) 	: ''	// 收货人的详细地址
		);
		
        if (! preg_match("/^\d{11}$/", $data['mobile'])) {
			response::show('403', 'error', '手机号码不正确！');	
			exit();
        }
		
		$insert_id = model('address')->create($data);
		
		if( false != $insert_id ) {
			response::show('200', 'success', '收货地址添加成功!');
			exit();
        } else {
			response::show('403', 'error', '收货地址添加失败!');
			exit();
        }
	}
	
	/**
	 * 删除收货人地址
	 */
	public function del_address() {
		$address_id = isset($_REQUEST['address_id']) 	? intval($_REQUEST['address_id']) 	: '0';
		
		// 检查分类id是否有效
		if($address_id <= 0 ) {
			response::show(403, 'error', '地址ID不能为空!');
			exit();
		}
		
		$result = model('address')->delete( array('user_id' =>$this->user_id, 'address_id'=>$address_id) );
		
		if(false != $result) {
			response::show('200', 'success', '收货地址删除成功!');
			exit();
		} else {
			response::show('403', 'error', '收货地址删除失败!');
			exit();
		}
	}
}