<?php
namespace app\admin\validate;

use think\Validate;

class PhoneValidate extends Validate
{
    protected $rule = [
        ['pe_name', 'unique:phone', '设备编号已存在']
    ];

}