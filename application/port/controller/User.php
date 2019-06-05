<?php
namespace app\port\controller;

use app\port\base\Token as TokenBase;
use app\port\model\User as UserModel;
use app\port\model\Token as TokenModel;

class User extends TokenBase
{
    protected $time;
    protected $user_model;
    protected $token_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->user_model = new UserModel;
        $this->token_model = new TokenModel;

        $this->time = time();
    }

    public function index()
    {
        return $this->logout();
    }

    /**
     * 退出登陆
     * */
    public function logout()
    {
        $out = $this->token_model->out($this->tokenInfo['id']);

        if ($out)
        {
            return $this->RSA_private_encrypt(error('退出成功'));
        }
        else {
            return $this->RSA_private_encrypt(error('退出失败'));
        }
    }
}
