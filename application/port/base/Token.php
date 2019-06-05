<?php
namespace app\port\base;

use app\port\base\Rsa1024 as Rsa1024Base;
use app\port\model\Token as TokenModel;
use app\port\model\User as UserModel;

/**
 * 接口 token 基础控制器
 */
class Token extends Rsa1024Base
{
    protected $time; // 当前时间戳
    protected $user_id; // 当前 user_id
    protected $token; // 当前 token
    protected $tokenInfo; // token 信息
    protected $expiryDate; // token 有效时间（秒）

    protected $token_model;
    protected $user_model;
    protected $user_common_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->token_model = new TokenModel;
        $this->user_model = new UserModel;
        $this->expiryDate = 86400 * 30;
        $this->time = time();

        $this->init();
        $this->checkAuth();
        $this->updateToken();

        $userInfo = $this->userInfo();

        if ($userInfo['status'] != 1)
        {
            echo $this->RSA_private_encrypt(error(['auth'=> false, 'message'=> '用户已被禁用，请联系管理员']));
            exit;
        }
        
        if ($this->time >= strtotime($userInfo['end_time']))
        {
            echo $this->RSA_private_encrypt(error(['auth'=> false, 'message'=> '用户已到期，请联系管理员续费']));
            exit;
        }
    }

    /**
     * 初始化参数
     */
    protected function init()
    {
        $token = $this->param('token'); // 订单号

        if (empty($token)) {
            echo $this->RSA_private_encrypt(error('token is null'));
            exit;
        }

        $this->token = $token;
        $this->time = time();
        $this->date = date('Y-m-d H:i:s');

        if (config('expiryDate') > 0)
        {
            $this->expiryDate = config('expiryDate');
        }
    }

    /**
     * 权限检查
     */
    protected function checkAuth()
    {
        $tokenInfo = $this->token_model->checkToken($this->token);

        if(empty($tokenInfo))
        {
            echo $this->RSA_private_encrypt(error(['auth'=> false, 'message'=> '令牌不存在，请重新登录']));
            exit;
        }

        if ($tokenInfo['status'] != 1)
        {
            echo $this->RSA_private_encrypt(error(['auth'=> false, 'message'=> '令牌无效，请重新登录']));
            exit;
        }

        $tokenCreate = strtotime($tokenInfo['create_time']);
        $tokenUpdate = strtotime($tokenInfo['update_time']);
        $lostTime = $this->time - $this->expiryDate;

        if ($tokenCreate < $lostTime && $tokenUpdate < $lostTime)
        {
            echo $this->RSA_private_encrypt(error(['auth'=> false, 'message'=> '令牌已过期，请重新登录']));
            exit;
        }

        $this->user_id = $tokenInfo['user_id'];
        $this->tokenInfo = $tokenInfo;
    }

    /**
     * 延期 token 过期时间
     */
    protected function updateToken()
    {
        return $this->token_model->where(['id'=> $this->tokenInfo['id']])->setField('update_time', $this->date);
    }

    /**
     * token 对应的用户信息
     */
    protected function userInfo($id=null)
    {
        if (empty($id))
        {
            $id = $this->tokenInfo['user_id'];
        }

        return $this->user_model->where(['id'=> $id])->find();
    }

    /**
     * token 对应的用户信息
     */
    protected function userCommon($id=null)
    {
        if (empty($id))
        {
            $id = $this->tokenInfo['user_id'];
        }

        return $this->user_common_model->where(['user_id'=> $id])->find();
    }
}