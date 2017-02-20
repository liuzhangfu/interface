<?php
/**
 * 阿里大鱼短信模块
 */
class alidayu {
    /**
     * 签名函数
	 *
     * @param  Array  $paramArr 	参数数组
     * @param  String $secret   	secret
     * @return String          	 	返回签名
     */
    private function create_sign($paramArr, $secret) {
        $sign = $secret;
        ksort($paramArr);
        foreach ($paramArr as $key => $val) {
            if ($key != '' && $val != '') {
                $sign.= $key . $val;
            }
        }
        $sign.= $secret;
        $sign = strtoupper(md5($sign));
        return $sign;
    }
	
    /**
     * 组参函数
	 *
     * @param  Array $paramArr 	参数数组
     * @return String          	返回Url
     */
    private function create_str_param($paramArr) {
        $strParam = '';
        foreach ($paramArr as $key => $val) {
            if ($key != '' && $val != '') {
                $strParam.= $key . '=' . urlencode($val) . '&';
            }
        }
        return $strParam;
    }
	
    /**
     * 发送短信
	 *
     * @param  String $mobile    			手机号码
     * @param  String $sms_template_code    短信模板ID
     * @param  String $sms_param 			短信变量,如 "{'code':'" . mt_rand(1000, 9999) . "','product':'你好啊'}"
     * @return array
     */
    public function send_sms($mobile, $sms_template_code, $sms_free_sign_name, $sms_param) {
        $config = $GLOBALS['alidayu'];
        $paramArr = array(
            'app_key' 				=> $config['APP_KEY'],
            'session_key' 			=> '',
            'method' 				=> (empty($config['method']))	 	? 'alibaba.aliqin.fc.sms.num.send' : $config['method'],// API接口名称
            'format' 				=> (empty($config['format'])) 		? 'json' : $config['format'],// 返回格式
            'v' 					=> (empty($config['v'])) 			? '2.0' : $config['v'],// 版本号
            'sign_method' 			=> (empty($config['sign_method'])) 	? 'md5' : $config['sign_method'],// sign加密方式
            'timestamp' 			=> date('Y-m-d H:i:s'),// 时间
            'fields' 				=> 'nick,type,user_id',
            'sms_type' 				=> 'normal',// 短信类型
            'sms_free_sign_name' 	=> $sms_free_sign_name,// 短信签名
            'sms_param' 			=> $sms_param,// 短信内容替换
            'rec_num' 				=> $mobile,// 手机号码
            'sms_template_code' 	=> $sms_template_code,// 短信模板ID
        );
        $sign = $this->create_sign($paramArr, $config['APP_SECRET']);// 生成签名
        $strParam = $this->create_str_param($paramArr);// 组织参数
        $strParam.= 'sign=' . $sign;
        $url = 'http://gw.api.taobao.com/router/rest?' . $strParam; // 正式环境调用地址
        $result = file_get_contents($url);// 获取返回
        $arr = json_decode($result, true);
        return $arr;
    }
}