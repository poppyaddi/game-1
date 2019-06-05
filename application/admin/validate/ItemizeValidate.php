<?php
namespace app\admin\validate;

use think\Validate;

class ItemizeValidate extends Validate
{
    protected $rule = [
        ['title', 'unique:games_price', '面值标识已存在']
    ];

}