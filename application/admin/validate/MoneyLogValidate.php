<?php
namespace app\admin\validate;

use think\Validate;

class MoneyLogValidate extends Validate
{
    protected $rule = [
        'cz_money' => 'require',
        'cz_real_name' => 'require',
        'cz_ali_number' => 'require',
    ];

    protected $message = [
        'cz_money.require'   => '充值金额不能为空',
        'cz_real_name.require'   => '打款账户不能为空',
        'cz_ali_number.require'   => '打款账号不能为空',
    ];

}