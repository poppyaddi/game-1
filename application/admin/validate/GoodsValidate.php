<?php
namespace app\admin\validate;

use think\Validate;

class GoodsValidate extends Validate
{
    protected $rule = [
        ['gd_name', 'unique:goods', '商品已经存在']
    ];

}