<?php
/**
 * 项目单入口文件
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */

$_ENV['_start_time'] = microtime(1); // 记录开始运行时间
define('MEMORY_LIMIT_ON', 	function_exists('memory_get_usage'));
if(MEMORY_LIMIT_ON) 		$_ENV['_start_memory'] = memory_get_usage(); // 记录内存初始使用
defined('DS') 				or define('DS', 		DIRECTORY_SEPARATOR); // 目录分隔符
defined('ROOT_PATH') 		or define('ROOT_PATH', 	realpath('./') . DS); // 网站根目录
defined('BASE_PATH')		or define('BASE_PATH', 	dirname(__FILE__) . DS); // 框架路径
defined('APP_PATH')			or define('APP_PATH', 	dirname($_SERVER['SCRIPT_FILENAME']) . DS); // 应用路径
require BASE_PATH . 'core/app.class.php';
app::run();