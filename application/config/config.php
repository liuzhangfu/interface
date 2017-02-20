<?php
/**
 * 全局配置文件
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
/* return array( */
$config = array(
    /* 项目基本设定 */
	'app' => array(
		'debug'       			=> true, 							// 是否开启高度模式
		'charset'       		=> 'utf-8', 						// 默认输出编码
		'timezone'      		=> 'prc',							// 默认时区
		'output_encode'         => false, 							// 页面压缩输出
		'error_url' 			=> '',								// 错误跳转链接
	),
	
    /* 日志设置 */
	'log' => array(
		'log_record'            => true,   							// 是否开启日志功能
		'log_path' 				=> APP_PATH.'temp'.DS.'log'.DS,		// 日志存放目录
		'log_file_size'         => 2097152,							// 日志文件大小限制
	),
	
    /* 模板引擎设置 */
	'view' => array(
		'template_dir'			=> APP_PATH.'view'.DS,				// 模板目录
		'template_suffix'		=> '.html',							// 模板后缀,只适用于自动输出模板
		'template_depr'         =>  '_', 							// 模板文件CONTROLLER_NAME与ACTION_NAME之间的分割符
		'compile_dir'			=> APP_PATH.'temp'.DS.'compile'.DS,	// 编译目录
		'tmpl_exception_file'   => APP_PATH.'view'.DS.'404.html',	// 异常页面的模板文件
	),
	
    /* SESSION设置 */
	'session' => array(
		'session_auto_start' 	=> true,    						// 是否自动开启Session
	),

    /* 数据库设置 */
	'db' => array(
		'db_host' 				=> 'localhost',						// 服务器地址
		'db_name' 				=> 'shop',							// 数据库名
		'db_user' 				=> 'root',							// 用户名
		'db_pwd'   				=> '123456',						// 密码
		'db_port' 				=> '3306',							// 端口
		'db_prefix' 			=> 'ecs_',							// 数据库表前缀
		'db_charset' 			=> 'utf8',							// 数据库编码默认采用utf8 
	),
	
    /* URL伪静态设置 */
	'rewrite' => array(
/* 		'admin/index.html' => 'admin/index/index',
		'admin/<c>_<a>.html'    => 'admin/<c>/<a>', 
		'<c>/<a>'          => '<c>/<a>',
		'/'                => 'index/index',
		'ad.html'          => 'ad/index', */
	),
);

require APP_PATH . 'config'.DS.'api.config.php';
return $api_config + $config;