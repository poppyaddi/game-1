<?php
namespace app\port\model;

use think\Model;
use app\port\model\Token as TokenModel;
use app\port\base\Rsa1024 as Rsa1024Base;

class User extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据用户ID获取用户信息
     * */
    public function idInfo($user_id)
    {
        return $this->where(['id'=> $user_id])->find();
    }

    /**
     * 根据手机号获取用户信息
     * */
    public function mobileInfo($mobile)
    {
        return $this->where(['mobile'=> $mobile])->find();
    }

    /**
     * 返回用户登陆数据
     * */
    public function returnUser($user, $client, $version, $device_id, $password=null)
    {
        if ($user['status']!=1)
        {
            echo (new Rsa1024Base)->RSA_private_encrypt(error('用户已被禁止登陆，请联系管理员'));
            exit;
        }

        $user_id = $user['id'];

        $token_model = new TokenModel;

        // 创建令牌
            $tokens = $token_model->createToken($user, $client, $version, $device_id);
            $token = $tokens['token'];
            $token_id = $tokens['id'];

        // 更新用户登陆信息
            $data = [
                'last_login_time'=> time(),
                'last_login_ip'=> $tokens['ip'],
            ];

            if ($password) {
                $data['password'] = md5($password . config('salt'));
            }

            $this->where(['id'=> $user['id']])->update($data);

        // 返回登陆信息
            $data = [
                'user_id'=> (int) $user_id,
                'phone'=> (string) $user['phone'],
                'username'=> (string) $user['username'],
                'register_time'=> date('Y-m-d H:i:s', $user['add_time']),
                'admin'=> (bool) $user['admin'],
                'end_time'=> (string) $user['end_time'],
                'prev_login_ip'=> (string) $user['last_login_ip'],
                'prev_login_time'=> date('Y-m-d H:i:s', $user['last_login_time']),
                'token'=> (string) $token,
                'token_id'=> (int) $token_id,
                'login_num'=> (int) db('token')->where(['user_id'=> $user_id])->count(),
            ];


        return $data;
    }

    /**
     * 获取随机用户名
     * */
    public function rand_name()
    {
        $username = time() . mt_rand(0,9) . mt_rand(0,9);

        $user = $this->where(['username'=> $username])->find();

        if (empty($user)) {
            return $username;
        }
        else {
            return $this->rand_name();
        }
    }
}