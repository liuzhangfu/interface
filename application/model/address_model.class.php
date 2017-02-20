<?php
/**
 * 用户收货地址数据模型
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class address_model extends common_model {
	public $table_name = "user_address";
	
    /**
     * 获取用户收货人地址列表
     */
    public function get_address_list($user_id = 0) {
		// 获取用户收货地址
        $sql = "SELECT address_id,consignee,address,tel,mobile,country,province,city,district".
                " FROM " . $this->table_prefix . "user_address".
                " WHERE user_id = :user_id ";

        if($result = $this->query($sql, array(':user_id' => $user_id))) {
            $address_list = array();
            foreach ($result as $key => $val) {
                $address_list[$key]['address_id']   = $val['address_id'];
                $address_list[$key]['consignee']    = $val['consignee'];
                $address_list[$key]['address']      = $val['address'];
                $address_list[$key]['tel']          = $val['tel'];  // 收货人的电话
                $address_list[$key]['mobile']       = $val['mobile'];   // 收货人的手机号
                $address_list[$key]['country']      = $this->get_region_name($val['country']);// 收货人的国家
                $address_list[$key]['province']     = $this->get_region_name($val['province']);// 收货人的省份
                $address_list[$key]['city']         = $this->get_region_name($val['city']); // 收货人城市
                $address_list[$key]['district']     = $this->get_region_name($val['district']); // 收货人的地区

                $address_list[$key]['province_id']  = $val['province'];// 收货人的省份id
                $address_list[$key]['city_id']      = $val['city']; // 收货人城市id
                $address_list[$key]['district_id']  = $val['district']; // 收货人的地区id
            }

            return $address_list;
        }
    }
	
    /**
     * 获取地区的名字
     */
    public function get_region_name($region_id = 0) {
        /// 查找符合条件的数据
        $sql = "SELECT region_name FROM ".$this->table_prefix."region WHERE region_id = :region_id";
				
        $row = $this->query($sql, array( ':region_id'=>$region_id ));

        return isset($row[0]['region_name']) ? $row[0]['region_name'] : '';
    }
}