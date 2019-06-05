<?php
namespace app\port\controller;

use app\port\base\Rsa1024;
use app\port\model\User as UserModel;
use app\port\model\Device as DeviceModel;

class Login extends Rsa1024
{
    protected $user_model;
    protected $time;

    public function _initialize()
    {
        parent::_initialize();
        $this->user_model = new UserModel;
        $this->device_model = new DeviceModel;

        $this->time = time();
    }

    public function index()
    {
        echo 'Hi !';
        return;
    }

    /**
     * 密码登陆
     * */
    public function mobile()
    {
        // 处理参数
            $username = $this->param('username');
            $password = $this->param('password');
            $client = $this->param('client');
            $version = $this->param('version');
            $device_id = $this->param('device_id');

            if (empty($username))
            {
                return $this->RSA_private_encrypt(error('username length is 0'));
            }

            if (empty($password))
            {
                return $this->RSA_private_encrypt(error('password length is 0'));
            }

            if (empty($client))
            {
                return $this->RSA_private_encrypt(error('client length is 0'));
            }

            if (empty($version))
            {
                return $this->RSA_private_encrypt(error('version length is 0'));
            }

            if (empty($device_id))
            {
                return $this->RSA_private_encrypt(error('device_id length is 0'));
            }

        // 检验用户信息
            $map = [
                'username|phone|email'=> $username,
            ];

            $user = $this->user_model->where($map)->find();

            if (empty($user))
            {
                return $this->RSA_private_encrypt(error('用户信息不存在'));
            }

        // 用户身份过期
            if (strtotime($user['end_time']) < $this->time)
            {
                return $this->RSA_private_encrypt(error('用户身份过期'));
            }

            if ($user['password'] != md5($password . config('salt')))
            {
                return $this->RSA_private_encrypt(error('用户密码错误'));
            }

        // 判断设备是否被授权
            $map = [
                'device'=> $device_id,
                'user_id'=> $user['id'],
            ];

            $device = $this->device_model->where($map)->find();

            if (empty($device))
            {
                // 添加设备到数据库
                    $data = [
                        'device'=> $device_id,
                        'user_id'=> $user['id'],
                    ];

                    $device_id = $this->device_model->insertGetId($data);

                // 锁定设备
                    if ($user['save_device'] == 1)
                    {
                        return $this->RSA_private_encrypt(error('设备ID: ' . $device_id . ' 首次登陆, 请登录后台进行授权'));
                    }
            }
            else {
                $device_id = $device['id'];
            }

        // 锁定设备
            if ($user['save_device'] == 1 && $device['status'] != 1)
            {
                return $this->RSA_private_encrypt(error('设备ID: ' . $device_id . ' 未授权, 请登录后台进行授权'));
            }


        // 返回用户信息
            $data = [
                'userInfo' => $this->user_model->returnUser($user, $client, $version, $device_id, $password),
            ];

            return $this->RSA_private_encrypt(succ($data));
    }
}
