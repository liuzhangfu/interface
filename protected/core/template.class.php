<?php
/**
 * 模板视图类
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
class template {
    /**
     * 标签左定界符
     */
	private $left_delimiter; 
	
    /**
     * 标签右定界符
     */
	private $right_delimiter;
	
    /**
     * 模板存放目录
     *
     * @var array
     */
	private $template_dir;
	
    /**
     * 模板编译目录
     *
     * @var array
     */
	private $compile_dir;
	
    /**
     * 模板变量
     *
     * @var array
     */
	private $template_vals = array();
	
    /**
     * 构造函数
     */
	public function __construct($template_dir, $compile_dir, $left_delimiter = '<{', $right_delimiter = '}>') {
		$this->left_delimiter = $left_delimiter;
		$this->right_delimiter = $right_delimiter;
		$this->template_dir = $template_dir;
		$this->compile_dir  = $compile_dir;
	}
	
    /**
     * 模板输出显示
	 *
     * @param string $tempalte_name 模板文件名
     */
	public function render($tempalte_name) {
		$complied_file = $this->compile($tempalte_name);

		@ob_start();
		extract($this->template_vals, EXTR_SKIP);
		$_view_obj = & $this;
		include $complied_file;

		return ob_get_clean();
	}
	
    /**
     * 向模板中传入变量
     *
     * @param string|array 	$var 变量名
     * @param mixed 		$val 变量值
     * @return bool
     */
	public function assign($var, $val = '') {
        if(is_array($var)) {
            foreach($var as $k => $v) {
                if($k != '') $this->template_vals[$k] = $v;
            }
        } else {
            if($var != '') $this->template_vals[$var] = $val;
        }
	}
	
    /**
     * 模板编译
	 *
     * @param $tempalte_name	模板文件
     * @return string
     */
	public function compile($tempalte_name = '') {
		$file = $this->template_dir.$tempalte_name;
		if(!file_exists($file)) halt('模板文件 '.$file.' 不存在!');
		if(!is_writable($this->compile_dir) || !is_readable($this->compile_dir)) 
			halt('目录 "'.$this->compile_dir.'" 不存在或没有读写权限!');

		$complied_file = $this->compile_dir.DS.md5(realpath($file)).'.'.filemtime($file).'.'.basename($tempalte_name).'.php';
		if(file_exists($complied_file)) return $complied_file;

		$template_data = file_get_contents($file); 
		$template_data = $this->_compile_struct($template_data);
		$template_data = $this->_compile_function($template_data);
		$template_data = '<?php if(!class_exists("template", false)) exit("no direct access allowed");?>'.$template_data;
		
		$this->_clear_compliedfile($tempalte_name);
		$tmp_file = $complied_file.uniqid('_tpl', true);
		if (!file_put_contents($tmp_file, $template_data)) halt('File "'.$tmp_file.'" can not be generated.');

		$success = @rename($tmp_file, $complied_file);
		if(!$success){
			if(is_file($complied_file)) @unlink($complied_file);
			$success = @rename($tmp_file, $complied_file);
		}
		if(!$success) halt('File "'.$complied_file.'" can not be generated.');
		return $complied_file;
	}
	
    /**
     * 解析模板标签
	 *
     * @param $template_data	模板内容
     * @return string
     */
	private function _compile_struct($template_data) {
		$foreach_inner_before = '<?php $_foreach_$3_counter = 0; $_foreach_$3_total = count($1);?>';
		$foreach_inner_after  = '<?php $_foreach_$3_index = $_foreach_$3_counter;$_foreach_$3_iteration = $_foreach_$3_counter + 1;$_foreach_$3_first = ($_foreach_$3_counter == 0);$_foreach_$3_last = ($_foreach_$3_counter == $_foreach_$3_total - 1);$_foreach_$3_counter++;?>';
		$pattern_map = array(
			'<{\*([\s\S]+?)\*}>'      => '<?php /* $1*/?>',
			'(<{((?!}>).)*?)(\$[\w\_\"\'\[\]]+?)\.(\w+)(.*?}>)' => '$1$3[\'$4\']$5',
			'(<{.*?)(\$(\w+)@(index|iteration|first|last|total))+(.*?}>)' => '$1$_foreach_$3_$4$5',
			'<{(\$[\S]+?)\snofilter\s*}>'          => '<?php echo $1; ?>',
			'<{(\$[\w\_\"\'\[\]]+?)\s*=(.*?)\s*}>'           => '<?php $1 =$2; ?>',
			'<{(\$[\S]+?)\s*}>'          => '<?php echo htmlspecialchars($1, ENT_QUOTES, "UTF-8"); ?>',
			'<{if\s*(.+?)}>'          => '<?php if ($1) : ?>',
			'<{else\s*if\s*(.+?)}>'   => '<?php elseif ($1) : ?>',
			'<{else}>'                => '<?php else : ?>',
			'<{break}>'               => '<?php break; ?>',
			'<{continue}>'            => '<?php continue; ?>',
			'<{\/if}>'                => '<?php endif; ?>',
			'<{foreach\s*(\$[\w\.\_\"\'\[\]]+?)\s*as(\s*)\$([\w\_\"\'\[\]]+?)}>' => $foreach_inner_before.'<?php foreach( $1 as $$3 ) : ?>'.$foreach_inner_after,
			'<{foreach\s*(\$[\w\.\_\"\'\[\]]+?)\s*as\s*(\$[\w\_\"\'\[\]]+?)\s*=>\s*\$([\w\_\"\'\[\]]+?)}>'  => $foreach_inner_before.'<?php foreach( $1 as $2 => $$3 ) : ?>'.$foreach_inner_after,
			'<{\/foreach}>'           => '<?php endforeach; ?>',
			'<{include\s*file=(.+?)}>'=> '<?php include $_view_obj->compile($1); ?>',
		);
		$pattern = $replacement = array();
		foreach($pattern_map as $p => $r){
			$pattern = '/'.str_replace(array("<{", "}>"), array($this->left_delimiter.'\s*','\s*'.$this->right_delimiter), $p).'/i';
			$count = 1;
			while($count != 0){
				$template_data = preg_replace($pattern, $r, $template_data, -1, $count);
			}
		}
		return $template_data;
	}
    /**
     * 解析模板内函数
	 *
     * @param $template_data	模板内容
     * @return string
     */
	private function _compile_function($template_data) {
		$pattern = '/'.$this->left_delimiter.'([\w_]+)\s*(.*?)'.$this->right_delimiter.'/';
		return preg_replace_callback($pattern, array($this, '_compile_function_callback'), $template_data);
	}
	
	private function _compile_function_callback( $matches ) {
		if(empty($matches[2])) return '<?php echo '.$matches[1].'();?>';
		$sysfunc = preg_replace('/\((.*)\)\s*$/', '<?php echo '.$matches[1].'($1);?>', $matches[2], -1, $count);
		if($count) return $sysfunc;
		
		$pattern_inner = '/\b([\w_]+?)\s*=\s*(\$[\w"\'\]\[\-_>\$]+|"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')\s*?/'; 
		$params = "";
		if(preg_match_all($pattern_inner, $matches[2], $matches_inner, PREG_SET_ORDER)){
			$params = "array(";
			foreach($matches_inner as $m)$params .= '\''. $m[1]."'=>".$m[2].", ";
			$params .= ")";
		}else{
			halt('Parameters of \''.$matches[1].'\' is incorrect!');
		}
		return '<?php echo '.$matches[1].'('.$params.');?>';
	}

    /**
     * 清除模板编译文件
	 *
     * @param $tempalte_name	模板文件
     * @return string
     */
	private function _clear_compliedfile($tempalte_name) {
		$dir = scandir($this->compile_dir);
		if($dir) {
			$part = md5(realpath($this->template_dir.DS.$tempalte_name));
			foreach($dir as $d){
				if(substr($d, 0, strlen($part)) == $part){
					@unlink($this->compile_dir.DS.$d);
				}
			}
		}
	}
}