<?php
namespace app\admin\validate;

use think\Validate;

class GamesValidate extends Validate
{
    protected $rule = [
        ['gs_name', 'unique:games', '游戏已经存在']
    ];

}