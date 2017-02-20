<?php
/**
 * 控制器基类
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class controller {
    /**
     * 模板视图对象
	 *
     * @var view
     * @access protected
     */
	private $_view;
	
    /**
     * 数据缓存
	 *
     * @var _data
     * @access private
     */
	private $_data = array();

    /**
     * 构造函数
     */
	public function __construct() {
		// 自动运行的魔术方法
        if (method_exists($this, 'init')) {
            $this->init();
        }
	}
	
    /**
     * 魔术方法,获取私有成员属性值
	 *
     * @param $name 	变量名
     */
	public function __get($name) {
		return $this->_data[$name];
	}
	
    /**
     * 魔术方法,设置私有成员属性值
	 *
     * @param $name		变量名
     * @param $value 	变量值
     */
	public function __set($name, $value) {
		$this->_data[$name] = $value;
	}
	
    /**
     * 模板输出
	 *
     * @param  string $tpl_name 模板名
     * @return mixed
     */
	public function display($tpl_name = '', $return = false) {
		if(!$this->_view) {
			$this->_view = new template($GLOBALS['view']['template_dir'].MODULE_NAME.DS, $GLOBALS['view']['compile_dir']);
		}
		
		$this->_view->assign(get_object_vars($this)); 
		$this->_view->assign($this->_data);
		
		if(!$tpl_name) {
			$tpl_name = CONTROLLER_NAME . $GLOBALS['view']['template_depr'] . ACTION_NAME . $GLOBALS['view']['template_suffix'];
		}
		
		echo $this->_view->render($tpl_name);
	}
	
	/**
	 * 魔术方法,
	 */
/* 	public function __call($method, $args) {
		// DEBUG关闭时，为防止泄漏敏感信息，用404错误代替
		if(DEBUG) {
			throw new Exception('控制器没有找到：'.get_class($this).'->'.$method.'('.(empty($args) ? '' : var_export($args, 1)).')');
		}else{
			core::error404();
		}
	} */
}