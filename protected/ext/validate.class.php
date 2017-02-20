<?php
/**
 * 表单验证处理类
 */
class validate {

    /**
     * 内容不能为空
     *
     * @param $name
     * @param $value
     * @param $msg
     *
     * @return bool
     */
    public function is_nonull($value) {
        if (empty($value)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 邮箱验证
     *
     * @param $name  变量名
     *
     * @return bool
     */
    public function is_email($value) {
        $preg = "/^([a-zA-Z0-9_\-\.])+@([a-zA-Z0-9_-])+((\.[a-zA-Z0-9_-]{2,3}){1,2})$/i";
        if (preg_match($preg, $value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 最大长度验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_maxlen($name, $value, $msg, $arg) {
        if (mb_strlen($value, 'utf-8') <= $arg) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 最小长度验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_minlen($name, $value, $msg, $arg) {
        if (mb_strlen($value, 'utf-8') >= $arg) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 网址验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_http($name, $value, $msg, $arg) {
        $preg
            = "/^(http[s]?:)?(\/{2})?([a-z0-9]+\.)?[a-z0-9]+(\.(com|cn|cc|org|net|com.cn))$/i";
        if (preg_match($preg, $value)) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 电话号码
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_tel($name, $value, $msg, $arg) {
        $preg = "/(?:\(\d{3,4}\)|\d{3,4}-?)\d{8}/";
        if (preg_match($preg, $value)) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 手机号验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_phone($value) {
        $preg = "/^\d{11}$/";
        if (preg_match($preg, $value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 身份证验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_identity($name, $value, $msg, $arg) {
        $preg = "/^(\d{15}|\d{18})$/";
        if (preg_match($preg, $value)) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 用户名验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_user($name, $value, $msg, $arg) {
        //用户名长度
        $len = mb_strlen($value, 'utf-8');
        $arg = explode(',', $arg);
        if ($len >= $arg[0] && $len <= $arg[1]) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 数字范围
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_num($name, $value, $msg, $arg) {
        $arg = explode(',', $arg);
        if ($value >= $arg[0] && $value <= $arg[1]) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 正则验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_regexp($name, $value, $msg, $preg) {
        if (preg_match($preg, $value)) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 两个表单比对
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_confirm($name, $value, $msg, $arg) {
        if ($value == $_POST[$arg]) {
            return true;
        } else {
            return $msg;
        }
    }

    /**
     * 中文验证
     *
     * @param $name  变量名
     * @param $value 变量值
     * @param $msg   错误信息
     *
     * @return bool
     */
    public function is_china($name, $value, $msg, $arg) {
        if (preg_match('/^[\x{4e00}-\x{9fa5}a-z0-9]+$/ui', $value)) {
            return true;
        } else {
            return $msg;
        }
    }
}