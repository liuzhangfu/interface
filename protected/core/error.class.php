<?php
/**
 * 错误处理类
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class error extends Exception {
    private $error_msg 		= ''; 	// 错误提示信息
    private $error_file 	= '';	// 错误文件
    private $error_line 	= 0;	// 错误行
    private $error_code 	= '';	// 错误代码
    private $error_level 	= '';	// 错误级别
    private $trace 			= '';	// 错误跟踪信息

    /**
     * 构造函数
	 *
     * @param unknown $error_msg
     * @param number $error_code
     * @param string $error_file
     * @param number $error_line
     */
    public function __construct($error_msg, $error_code = 0, $error_file = '', $error_line = 0) {
        parent::__construct($error_msg, $error_code);
        $this->error_msg 	= $error_msg;
        $this->error_code 	= $error_code == 0 	? $this->getCode() : $error_code;
        $this->error_file 	= $error_file == '' ? $this->getFile() : $error_file;
        $this->error_line 	= $error_line == 0 	? $this->getLine() : $error_line;
        $this->error_level 	= $this->get_level();
        $this->trace 		= $this->trace();
        $this->show_error();
    }
	
    /**
     * 抛出错误信息，用于外部调用
	 *
     * @param string $error_msg
     * @param number $error_code
     * @param string $error_file
     * @param number $error_line
     */
    static public function show($error_msg = "", $error_code = 0, $error_file = '', $error_line = 0) {
        if (function_exists('ec_error_ext')) {
            ec_error_ext($error_msg, $error_code, $error_file, $error_line);
        } else {
            new error($error_msg, $error_code, $error_file, $error_line);
        }
    }
	
    /**
     * 错误等级
	 *
     * @return string
     */
    protected function get_level() {
        $level_array = array(
			1 => '致命错误(E_ERROR)',
            2 => '警告(E_WARNING)',
            4 => '语法解析错误(E_PARSE)',
            8 => '提示(E_NOTICE)',
            16 => 'E_CORE_ERROR',
            32 => 'E_CORE_WARNING',
            64 => '编译错误(E_COMPILE_ERROR)',
            128 => '编译警告(E_COMPILE_WARNING)',
            256 => '致命错误(E_USER_ERROR)',
            512 => '警告(E_USER_WARNING)',
            1024 => '提示(E_USER_NOTICE)',
            2047 => 'E_ALL',
            2048 => 'E_STRICT'
        );
        return isset($level_array[$this->error_code]) ? $level_array[$this->error_code] : $this->error_code;
    }
	
    /**
	 *
     * 获取trace信息
     * @return string
     */
    protected function trace() {
        $trace = $this->getTrace();
        $trace_info = '';
        $time = date("Y-m-d H:i:s");
        foreach ($trace as $t) {
            $trace_info.= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
            !empty($t['class']) 	&& $trace_info .= $t['class'];
            !empty($t['function']) 	&& $trace_info .= $t['function'];
            !empty($t['type']) 		&& $trace_info .= $t['type'];
            $trace_info .= '(';
            $trace_info .= ")<br />\r\n";
        }
        return $trace_info;
    }
	
    /**
     * 记录错误信息
	 *
     * @param unknown $message
     */
    static public function write($message) {
        $log_path = $GLOBALS['log']['log_path'];
		
        // 检查日志记录目录是否存在
        if (!is_dir($log_path)) {
            // 创建日志记录目录
            @mkdir($log_path, 0777, true);
        }
        $time 			= date('Y-m-d H:i:s');
        $ip 			= function_exists('get_client_ip') ? get_client_ip() : $_SERVER['REMOTE_ADDR'];
        $destination 	= $log_path . date("Y-m-d_") . md5($log_path) . ".log";
        // 写入文件，记录错误信息
        @error_log("{$time} | {$ip} | {$_SERVER['PHP_SELF']} |{$message}\r\n", 3, $destination);
    }
	
    /**
     * 输出错误信息
     */
    protected function show_error() {
        // 如果开启了日志记录，则写入日志
        if ($GLOBALS['log']['log_record']) {
            self::write($this->error_msg);
        }

        $error_url = $GLOBALS['app']['error_url'];
        // 错误页面重定向
        if ($error_url != '') {
            echo '<script language="javascript">
                if(self != top){
                  parent.location.href="' . $error_url . '";
                } else {
                 window.location.href="' . $error_url . '";
                }
                </script>';
            exit;
        }
		
        echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>系统发生错误</title>
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<style type="text/css">
*{ padding: 0; margin: 0; }
html{ overflow-y: scroll; }
body{ background: #fff; font-family: \'微软雅黑\'; color: #333; font-size: 16px; }
img{ border: 0; }
.error{ margin: 36px 12px; }
.face{ font-size: 36px; font-weight: normal; line-height: 36px; margin-bottom: 36px; }
h1{ font-size: 16px; line-height: 24px; }
.error .content{ padding-top: 10px}
.error .info{ margin-bottom: 12px; }
.error .info .title{ margin-bottom: 3px; }
.error .info .title h3{ color: #000; font-weight: 700; font-size: 16px; }
.error .info .text{ line-height: 24px; word-wrap: break-word;}
.copyright{ padding: 12px; color: #999; }
.copyright a{ color: #000; text-decoration: none; }
</style>
</head>
<body>
<div class="error">
<p class="face">:(</p>
<h1>' . $this->error_msg . '</h1>';
//开启调试模式之后，显示详细信息
if (($this->error_code > 0) && ($this->error_code != 404) && $GLOBALS['app']['debug']) {
    echo '
    <div class="content">
	<div class="info">
		<div class="title"><h3>出错信息</h3></div>
		<div class="text">
            <p>FILE: ' . $this->error_file . ' &#12288;LINE: ' . $this->error_file . '</p>
		</div>
		<div class="text">
			<p>错误级别: ' . $this->error_level . '</p>
		</div>
	</div>
    </div>';
}
echo '
</div>
<div class="copyright">
<p><a title="官方网站" href="http://www.LIUZHANGFU.COM">MICI</a><sup>1.0</sup></p>
<p style="text-align:right">[ 百次幂商城 专注美妆护肤 ]</p>
</div>
</body>
</html>';
        exit;
    }
}