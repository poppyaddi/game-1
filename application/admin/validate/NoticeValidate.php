<?php
namespace app\admin\validate;

use think\Validate;

class NoticeValidate extends Validate
{
    protected $rule = [
        ['ne_name', 'unique:notice', '标题已存在']
    ];

}