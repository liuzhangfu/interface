<?php
/**
 * 基础函数库
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
 
/**
 * 统计程序运行时间
 */
function runtime() {
	return number_format(microtime(1) - $_ENV['_start_time'], 4);
}

/**
 * 统计程序内存开销
 */
function runmem() {
	return MEMORY_LIMIT_ON ? byte_format(memory_get_usage() - $_ENV['_start_memory']) : 'unknown';
}

/**
 * 递归清理反斜线
 */
function _stripslashes(&$var) {
	if (is_array($var)) {
		foreach($var as $k => &$v) _stripslashes($v);
	} else {
		$var = stripslashes($var);
	}
}

/**
 * 字节格式化 把字节数格式为 B K M G T 描述的大小
 *
 * @param unknown $size
 * @param number $dec
 * @return string
 */
function byte_format($size, $dec = 2) {
    $a = array(
        "B", "KB", "MB", "GB", "TB", "PB"
    );
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos ++;
    }
    return round($size, $dec) . " " . $a[$pos];
}

/**
 * 是否为AJAX提交
 *
 * @return boolean
 */
function ajax_request() {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return true;
    }
    return false;
}

/**
 * 抛出异常处理
 *
 * @param 	string 	$msg 异常消息
 * @param 	integer $code 异常代码 默认为0
 * @return 	void
 */
function halt($msg, $code = 0) {
    if ($GLOBALS['app']['debug']) {
        throw new Exception($msg, $code);
        exit($msg);
    } else {
		// 输出异常页面
        include ($GLOBALS['view']['tmpl_exception_file']);
        exit();
    }
}

/**
 * 实例化数据模型model
 *
 * @param string $name Model名称 支持指定基础模型 例如 UserModel
 * @return model
 */
function model($model = '') {
    static $_model  = array();
    $class = $model . '_model';
    if (!isset($_model[$class]))
        $_model[$class] = new $class();
    return $_model[$class];
}

/**
 * 产生随机字符串
 * @param int	$length	输出长度
 * @param int	$type	输出类型 1为数字 2为a1 3为Aa1
 * @param string	$chars	随机字符 可自定义
 * @return string
 */
function random($length, $type = 1, $chars = '0123456789abcdefghijklmnopqrstuvwxyz') {
	if($type == 1) {
		$hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
	} else {
		$hash = '';
		if($type == 3) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) $hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
}