<?php
/**
 * 全局配置文件
 *
 * [APP项目开发] Copyright (c) 2016 LIUZHANGFU.COM
 */
$api_config = array(
    /* 接口基本设定 */
	'api' => array(
		'shop_name' 			=> '商城',
		'http_host' 			=> 'http://www.liuzhangfu.com/',
		'hash_code'				=> 'd44426bd5b64dd2ce05e88473bb59429',
		'price_format'			=> '0', // 商品价格显示规则;0:不处理,1:保留不为0的尾数,2:不四舍五入,保留一位小数,3:不四舍五入,不保留小数,4:先四舍五入,保留一位小数,5:先四舍五入,不保留小数
		'currency_format' 		=> '￥%s元', // 显示商品价格的格式，%s将被替换为相应的价格数字。
		'no_picture'			=> '',
		'top10_time' 			=> 0, // 排行榜统计时间段，0:不限时间,1:一年,2:半年,3:三个月,4:一个月
		'timezone' 				=> 8,
		'use_storage' 			=> '1', 				// 是否启用库存管理,1:启用,2:不启用
		'date_format' 			=> 'Y-m-d',
		'time_format' 			=> 'Y-m-d H:i:s',
		'integral_scale' 		=> 1,					// 积分换算比例
		'article_title_length' 	=> 20,					// 文章标题长度
	),
    /* 阿里大鱼短信参数设置 */
    'alidayu' => array(
        'APP_KEY'               => '23385833',
        'APP_SECRET'            => '3b54b854bd42235e9a850d317b71b83d',
        'method'                => 'alibaba.aliqin.fc.sms.num.send', // 短信模块
        'format'                => 'json', // 返回格式xml/json
    ),
);
