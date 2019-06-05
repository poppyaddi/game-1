<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
error_reporting(E_ERROR | E_WARNING | E_PARSE);

	/**
	 * 使用正则验证数据
	 * @access public
	 * @param string $value  要验证的数据
	 * @param string $rule 验证规则
	 * @return boolean
	 */
	function regular($value,$rule) {
		$validate = array(
            'email'     =>  '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url'       =>  '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
            'currency'  =>  '/^\d+(\.\d+)?$/',
            'number'    =>  '/^\d+$/',
            'num'    	=>  '/^[0-9]*$/',
            'zip'       =>  '/^\d{6}$/',
            'integer'   =>  '/^[-\+]?\d+$/',
            'double'    =>  '/^[-\+]?\d+(\.\d+)?$/',
            'english'   =>  '/^[A-Za-z]+$/',
            // 			'name'		=>  '/[^\D]/g',
            'idCard'	=>	'/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i' ,

            'img'		=>	'/\.(jpg|gif|png)$/i',
            'phone'		=>  '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,3,6,7,8]{1}\d{8}$|^18[\d]{9}$#',
            'password'  =>  '#(?=^.*?[a-z])(?=^.*?[A-Z])(?=^.*?\d)^(.{6,})$#',
            'bankcard'  =>  '/^(\d{16}|\d{19})$/',
            'account'   =>  '/^[a-zA-Z0-9_]+$/',
            'user'   =>  '/^[a-zA-Z\d]{6,10}$/',
            'name'  =>  ' /^\w{6,20}$/',
		);
		// 检查是否有内置的正则表达式
		if(isset($validate[strtolower($rule)])){
			$rule       =   $validate[strtolower($rule)];
			return preg_match($rule,$value)===1;
		}
	}

    /**
     * CURL函数
     * @param  $url
     * @return  $data;
     */
    function getCurl($url,$arr){
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_HTTPHEADER,array(
            'Accept-Language:ZH-CN',
            'Accept-Encoding:UTF-8'
        ));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /**
     * 将字符解析成数组
     * @param $str
     */
    function parseParams($str)
    {
        $arrParams = [];
        parse_str(html_entity_decode(urldecode($str)), $arrParams);
        return $arrParams;
    }

    /**
     * 子孙树 用于菜单整理
     * @param $param
     * @param int $pid
     */
    function subTree($param, $pid = 0)
    {
        static $res = [];
        foreach($param as $key=>$vo){

            if( $pid == $vo['pid'] ){
                $res[] = $vo;
                subTree($param, $vo['id']);
            }
        }

        return $res;
    }

    /**
     * 密码正则验证
     * @param $str
     * @return true,false
     */
    function ispassword($str) {
        if (preg_match('/^[_0-9a-z]{6,16}$/i',$str)){
            return true;
        }else {
            return false;
        }
    }

    /**
     * 按日期分组
     * @param $addtime
     * @return $list
     */
    function groupByAddtime($addtime)
    {
        //今天日期
        $curyear = date('Y m d');
        //昨天日期
        $yesterday = date("Y m d",strtotime("-1 day"));
        $visit_list = [];

        foreach ($addtime as $v) {
            //今天
            if ($curyear == date('Y m d', $v['addtime'])) {
                $date = date('今天', $v['addtime']);
            } elseif($yesterday == date('Y m d', $v['addtime'])) {
                $date = date('昨天', $v['addtime']);
            }else{
                $date = date('m月d日', $v['addtime']);
            }
            $visit_list[$date][]= $v;
        }
        //再次循环赋键值
        $i = 0;
        foreach ($visit_list as $k => $v){
            $list[$i]['time'] = $k;
            $list[$i]['value'] = $v;
            $i++;
        }
        return $list;
    }

    /**
     * 对象转数组
     * @param $data
     * @return $data
     */
    function ObjectToArray($data){
        //利用助手函数进行转换
        $data = collection($data)->toArray();
        //返回数组
        return $data;
    }

    /**
     * 生成操作按钮
     * @param array $operate 操作按钮数组
     */
    function showOperate($operate = [])
    {
        if(empty($operate)){
            return '';
        }
        $option = <<<EOT
    <div class="btn-group">
        <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            操作 <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
EOT;

        foreach($operate as $key=>$vo){

            $option .= '<li><a href="'.$vo.'">'.$key.'</a></li>';
        }
        $option .= '</ul></div>';

        return $option;
    }

    /**
     * 整理菜单住方法
     * @param $param
     * @return array
     */
    function prepareMenu($param)
    {
        $parent = []; //父类
        $child = [];  //子类

        foreach($param as $key=>$vo){

            if($vo['typeid'] == 0){
                $vo['href'] = '#';
                $parent[] = $vo;
            }else{
                $vo['href'] = url($vo['control_name'] .'/'. $vo['action_name']); //跳转地址
                $child[] = $vo;
            }
        }

        foreach($parent as $key=>$vo){
            foreach($child as $k=>$v){

                if($v['typeid'] == $vo['id']){
                    $parent[$key]['child'][] = $v;
                }
            }
        }
        unset($child);

        return $parent;
    }

    /**
     * 解析备份sql文件
     * @param $file
     */
    function analysisSql($file)
    {
        // sql文件包含的sql语句数组
        $sqls = array ();
        $f = fopen ( $file, "rb" );
        // 创建表缓冲变量
        $create = '';
        while ( ! feof ( $f ) ) {
            // 读取每一行sql
            $line = fgets ( $f );
            // 如果包含空白行，则跳过
            if (trim ( $line ) == '') {
                continue;
            }
            // 如果结尾包含';'(即为一个完整的sql语句，这里是插入语句)，并且不包含'ENGINE='(即创建表的最后一句)，
            if (! preg_match ( '/;/', $line, $match ) || preg_match ( '/ENGINE=/', $line, $match )) {
                // 将本次sql语句与创建表sql连接存起来
                $create .= $line;
                // 如果包含了创建表的最后一句
                if (preg_match ( '/ENGINE=/', $create, $match )) {
                    // 则将其合并到sql数组
                    $sqls [] = $create;
                    // 清空当前，准备下一个表的创建
                    $create = '';
                }
                // 跳过本次
                continue;
            }

            $sqls [] = $line;
        }
        fclose ( $f );
        return $sqls;
    }

    /**
     * 对象转数组
     * @param  $object
     * @return  $array;
     */
    function object2array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }

    /**
     * 数组随机合并
     * @param  $arr，$list
     * @return  $mergeArray;
     */
    function shuffleMergeArray($arr,$list){
        $mergeArray = array();
        $sum = count($arr) + count($list);
        for ($k = $sum; $k > 0; $k--) {
            $number = mt_rand(1, 2);
            if ($number == 1) {
                $mergeArray[] = $list ? array_shift($list) : array_shift($arr);
            } else {
                $mergeArray[] = $arr ? array_shift($arr) : array_shift($list);
            }
        }
        return $mergeArray;
    }

    /**
     * 二维数组某个值进行降序
     * @param  $preData，$sortType='newPrice'
     * @return  $sortData;
     */
    function sortArrayDesc($preData,$sortType='newPrice'){
        $sortData = array();
        foreach ($preData as $key_i => $value_i){
            $price_i = $value_i[$sortType];
            $min_key = '';
            $sort_total = count($sortData);
            foreach ($sortData as $key_j => $value_j){
                if($price_i>$value_j[$sortType]){
                    $min_key = $key_j+1;
                    break;
                }
            }
            if(empty($min_key)){
                array_push($sortData, $value_i);
            }else {
                $sortData1 = array_slice($sortData, 0,$min_key-1);
                array_push($sortData1, $value_i);
                if(($min_key-1)<$sort_total){
                    $sortData2 = array_slice($sortData, $min_key-1);
                    foreach ($sortData2 as $value){
                        array_push($sortData1, $value);
                    }
                }
                $sortData = $sortData1;
            }
        }
        return $sortData;
    }

    /**
     * 二维数组某个值进行升序
     * @param  $preData，$sortType='newPrice'
     * @return  $sortData;
     */
    function sortArrayAsc($preData,$sortType='newPrice'){
        $sortData = array();
        foreach ($preData as $key_i => $value_i){
            $price_i = $value_i[$sortType];
            $min_key = '';
            $sort_total = count($sortData);
            foreach ($sortData as $key_j => $value_j){
                if($price_i<$value_j[$sortType]){
                    $min_key = $key_j+1;
                    break;
                }
            }
            if(empty($min_key)){
                array_push($sortData, $value_i);
            }else {
                $sortData1 = array_slice($sortData, 0,$min_key-1);
                array_push($sortData1, $value_i);
                if(($min_key-1)<$sort_total){
                    $sortData2 = array_slice($sortData, $min_key-1);
                    foreach ($sortData2 as $value){
                        array_push($sortData1, $value);
                    }
                }
                $sortData = $sortData1;
            }
        }
        return $sortData;
    }

    /**
     * 时间格式化
     * @param  $addtime
     * @return  $data;
     */
     function Timeformat($addtime){
         if (is_array($addtime)){
             foreach ($addtime as $k => $v) {
                 $time = ceil((time() - $v['addtime'])/3600);
                 if($time >= 24){
                     //多少小时
                     $hour = $time % 24;
                     //多少天
                     $day = floor($time/24);
                     if ($hour == 0){

                         $addtime[$k]['addtime'] =  $day.'天前发布';
                     }else{

                         $addtime[$k]['addtime'] =  $day.'天'.$hour.'小时前发布';
                     }
                 }else if($time > 1 && $time <24){

                     $addtime[$k]['addtime'] =  $time.'小时前发布';
                 }else{
                     $arr = ceil((time() - $v['addtime']) / 60);
                     if ($arr == 0)
                     {
                         $addtime[$k]['addtime'] =  '刚刚发布';
                     }else{

                         $addtime[$k]['addtime'] =  $arr.'分钟前发布';
                     }
                 }
             }
             return $addtime;
         }else{
             $time = ceil((time() - $addtime['addtime'])/3600);
             if($time >= 24){
                 //多少小时
                 $hour = $time % 24;
                 //多少天
                 $day = floor($time/24);
                 if ($hour == 0){

                     $addtime['addtime'] =  $day.'天前发布';
                 }else{

                     $addtime['addtime'] =  $day.'天'.$hour.'小时前发布';
                 }
             }else if($time > 1 && $time <24){

                 $addtime['addtime'] =  $time.'小时前发布';
             }else{
                 $arr = ceil((time() - $addtime['addtime']) / 60);
                 if ($arr == 0)
                 {
                     $addtime['addtime'] =  '刚刚发布';
                 }else{

                     $addtime['addtime'] =  $arr.'分钟前发布';
                 }
             }
             return $addtime;
         }
     }

    /**
     * 递归 整理分类
     *
     * @param int $show_deep 显示深度
     * @param array $class_list 类别内容集合
     * @param int $deep 深度
     * @param int $parent_id 父类编号
     * @param int $i 上次循环编号
     * @return array $show_class 返回数组形式的查询结果
     */
    function _getTreeClassList($show_deep,$class_list,$deep=1,$parent_id=0,$i=0){
        static $show_class = array();//树状的平行数组
        if(!empty($class_list) && !empty($class_list)) {
            $size = count($class_list);
            if($i == 0) $show_class = array();//从0开始时清空数组，防止多次调用后出现重复
            for ($i;$i < $size;$i++) {//$i为上次循环到的分类编号，避免重新从第一条开始
                $val = $class_list[$i];
                $gc_id = $val['id'];
                $gc_parent_id    = $val['parent_id'];
                if($gc_parent_id == $parent_id) {
                    $val['deep'] = $deep;
                    $show_class[] = $val;
                    if($deep < $show_deep && $deep < 3) {//本次深度小于显示深度时执行，避免取出的数据无用
                        $this->_getTreeClassList($show_deep,$class_list,$deep+1,$gc_id,$i+1);
                    }
                }
                if($gc_parent_id > $parent_id) break;//当前分类的父编号大于本次递归的时退出循环
            }
        }
        return $show_class;
    }

    /**
     * 取分类列表，最多为三级
     * @param int $show_deep 显示深度
     * @param array $condition 检索条件
     * @return array 数组类型的返回结果
     */
    function getTreeClassList($show_deep='3',$condition=array()){
        //获取所有的分类
        $class_list = $this->getGoodsClassList($condition);
        $goods_class = array();//分类数组
        if(!empty($class_list) && !empty($class_list)) {
            $show_deep = intval($show_deep);
            if ($show_deep == 1){//只显示第一级时用循环给分类加上深度deep号码
                foreach ($class_list as $val) {
                    if($val['parent_id'] == 0) {
                        $val['deep'] = 1;
                        $goods_class[] = $val;
                    } else {
                        break;//父类编号不为0时退出循环
                    }
                }
            } else {//显示第二和三级时用递归
                $goods_class = $this->_getTreeClassList($show_deep,$class_list);
            }
        }
        return $goods_class;
    }



