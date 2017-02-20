<?php
/**
 * 框架核心文件
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
final class app {
	static public function run() {
		// 加载核心文件
		self::load_file();
		// 初始化应用
		self::init_set();
		// URL处理
		self::url_parse();
		// 执行应用
		self::execute();
	}
	
	/**
	 * 初始化基本设置
	 */
	static public function init_set() {
		// 页头编码
		header('Content-type: text/html; charset=' . $GLOBALS['app']['charset']);
		// 设置系统时区
		date_default_timezone_set($GLOBALS['app']['timezone']);
		// 是否开启SESSION
		$GLOBALS['session']['session_auto_start'] && session_start();
        // 页面压缩输出支持
        if ($GLOBALS['app']['output_encode']) {
            $zlib 				= ini_get('zlib.output_compression');
            if(empty($zlib)) 	ob_start('ob_gzhandler');
        }
		
        // 定义当前请求的系统常量
        define('NOW_TIME',      $_SERVER['REQUEST_TIME']);
        define('REQUEST_METHOD',$_SERVER['REQUEST_METHOD']);
        define('IS_GET',        REQUEST_METHOD =='GET' 		? true : false);
        define('IS_POST',       REQUEST_METHOD =='POST' 	? true : false);
        define('IS_PUT',        REQUEST_METHOD =='PUT' 		? true : false);
        define('IS_DELETE',     REQUEST_METHOD =='DELETE' 	? true : false);
        define('IS_AJAX',       ajax_request() );
		
		// 环境设置
		@ini_set('memory_limit', 		'128M');
		@ini_set('register_globals', 	'off');
		if (version_compare(PHP_VERSION, '5.4.0', '<')) {
			// 对外部引入文件禁止加转义符
			ini_set('magic_quotes_runtime', 0);
			// GPC 安全过滤,删除系统自动加的转义符号
			if (get_magic_quotes_gpc()) {
				_stripslashes($_POST);
				_stripslashes($_GET);
				_stripslashes($_REQUEST);
				_stripslashes($_COOKIE);
			}
		}
		
		// 某些IIS环境 fix
		if (!isset($_SERVER['REQUEST_URI'])) {
			if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
				$_SERVER['REQUEST_URI'] = &$_SERVER['HTTP_X_REWRITE_URL'];
			} else {
				$_SERVER['REQUEST_URI'] = '';
				$_SERVER['REQUEST_URI'] .= $_SERVER['REQUEST_URI'];
				$_SERVER['REQUEST_URI'] .= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
				$_SERVER['REQUEST_URI'] .= empty($_SERVER['QUERY_STRING']) ? '' : '?'.$_SERVER['QUERY_STRING'];
			}
		}
		
		// 系统错误处理
		if ($GLOBALS['app']['debug']) {
			error_reporting(-1);
			ini_set("display_errors", "On");
		} else {
			error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
			ini_set("display_errors", "Off");
			ini_set("log_errors", "On");
		}
	}
	
	/**
	 * URL处理
	 */
	static private function url_parse() {
/* 		if(!empty($GLOBALS['rewrite'])){
			if( ($pos = strpos( $_SERVER['REQUEST_URI'], '?' )) !== false )
				parse_str( substr( $_SERVER['REQUEST_URI'], $pos + 1 ), $_GET );
			foreach($GLOBALS['rewrite'] as $rule => $mapper){
				if('/' == $rule)$rule = '';
				if(0!==stripos($rule, 'http://'))
					$rule = 'http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/\\') .'/'.$rule;
				$rule = '/'.str_ireplace(array('\\\\', 'http://', '/', '<', '>',  '.'), 
					array('', '', '\/', '(?P<', '>\w+)', '\.'), $rule).'/i';
				if(preg_match($rule, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], $matchs)){
					$route = explode("/", $mapper);
					
					if(isset($route[2])){
						list($_GET['m'], $_GET['c'], $_GET['a']) = $route;
					}else{
						list($_GET['c'], $_GET['a']) = $route;
					}
					foreach($matchs as $matchkey => $matchval){
						if(!is_int($matchkey))$_GET[$matchkey] = $matchval;
					}
					break;
				}
			}
		} */
	}
	
	/**
	 * 载入核心文件
	 */
	static private function load_file() {
		// 载入配置文件
		$GLOBALS = require APP_PATH . 'config'.DS.'config.php';
		
		// 加载基础函数库
		if (is_file(BASE_PATH . 'lib'.DS.'base.func.php')) {
			require BASE_PATH . 'lib'.DS.'base.func.php';
		}
		
		// 加载自定义函数库
		if (is_file(APP_PATH . 'common'.DS.'functions.php')) {
			require APP_PATH . 'common'.DS.'functions.php';
		}

		// 注册类的自动加载
		spl_autoload_register(array('app', 'autoload_handler'));
	}
	
	/**
	 * 实现类库的自动加载
	 *
	 * @param string $classname 类名
	 */
	static private function autoload_handler($classname) {
		static $_import_files = array();
		if ( ! isset($_import_files[$classname])) {
			$dir_array = array(
				BASE_PATH.'core'.DS,
				BASE_PATH.'lib'.DS,
				BASE_PATH.'ext'.DS,
				APP_PATH.'controller'.DS,
				APP_PATH.'controller'.DS.MODULE_NAME.DS,
				APP_PATH.'model'.DS,
				APP_PATH.'model'.DS.MODULE_NAME.DS,
			);
			foreach ($dir_array as $dir) {
				$file = $dir . $classname . '.class.php';
				if (is_file($file)) {
					require_once ($file);
					$_import_files[$classname] = true;
				} else {
					$_import_files[$classname] = false;
				}
			}
			return $_import_files[$classname];
		}
		return $_import_files[$classname];
	}
	
    /**
     * 执行应用
     */
	static private function execute() {
		try {
			$_REQUEST = array_merge($_POST, $_GET);
			$module_name     = isset($_REQUEST['m']) ? strtolower($_REQUEST['m']) : 'home';
			$controller_name = isset($_REQUEST['c']) ? strtolower($_REQUEST['c']) : 'index';
			$action_name     = isset($_REQUEST['a']) ? strtolower($_REQUEST['a']) : 'index';
			
			define('MODULE_NAME', 		$module_name); // 当前模块名称
			define('CONTROLLER_NAME', 	$controller_name); // 当前控制器名称
			define('ACTION_NAME', 		$action_name);	// 当前方法名称
			
			$controller = CONTROLLER_NAME . '_controller'; // 当前控制器
			$action = ACTION_NAME; // 当前方法
			// 检查控制器类是否存在 
			if (! class_exists($controller)) {
				halt(MODULE_NAME . DS . $controller . '.class.php 控制器类不存在', 404);
			}
			$obj = new $controller(); // 初始化当前控制器
			
			// 检查方法名称是否合法
			if (! preg_match('/^[A-Za-z](\w)*$/', $action)) {
				halt(MODULE_NAME . DS . $controller . '.class.php的' . $action . '() 方法不合法', 404);
			}
			
			// 检查控制器类中的方法是否存在
			if (! method_exists($obj, $action)) {
				halt(MODULE_NAME . DS . $controller . '.class.php的' . $action . '() 方法不存在', 404);
			}
			
			/* 执行当前操作 */
			$method = new ReflectionMethod($obj, $action);
			if ($method->isPublic() && !$method->isStatic()) {
				$obj->$action();
			} else {
				/* 操作方法不是Public 抛出异常 */
				halt(MODULE_NAME . DS . $controller . '.class.php的' . $action . '() 方法没有访问权限', 404);
			}
		} catch(Exception $e) {
			error::show($e->getMessage(), $e->getCode());
		}
	}
}