<?php
namespace app\admin\validate;

use think\Validate;

class HelpValidate extends Validate
{
    protected $rule = [
        ['title', 'unique:helps', '标题已经存在']
    ];

}