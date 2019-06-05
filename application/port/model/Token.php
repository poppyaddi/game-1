<?php
namespace app\port\model;

use think\Model;
use app\port\base\Rsa1024 as Rsa1024Base;

class Token extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $rsa1024_model;
    protected $insert = ['create_time'];

    protected $rule = [
        'token' => 'require|unique:token',
        'user_id' => 'require',
        'user_name' => 'require',
        'ip' => 'require',
        'client' => 'require',
        'version' => 'require',
    ];

    protected $message = [
        'username.require' => '令牌不能为空',
        'username.unique' => '令牌不唯一',
        'user_id.require' => '用户ID不能为空',
        'user_name.require'   => '用户名不能为空',
        'ip.captcha'   => '登陆IP不能为空',
        'client.captcha'   => '登陆客户端类型不能为空',
        'version.captcha'   => '登陆客户端版本不能为空',
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->rsa1024_model = new Rsa1024Base;
    }

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * 创建令牌
     * */
    public function createToken($user, $client, $version, $device_id)
    {
        $data = [
            'token' => $this->rand_token($user['id']),
            'user_id' => $user['id'],
            'user_name' => $user['username'],
            'ip' => getIP(),
            'client' => $client,
            'version' => $version,
            'device_id' => $device_id,
        ];

        $validate_result = $this->validate($this->rule, $this->message);

        if ($validate_result === false)
        {
            echo $this->rsa1024_model->RSA_private_encrypt(error($this->getError()));
            exit;
        } else {
            $id = $this->insertGetId($data);

            if ($id)
            {
                // 过期同设备ID 登陆的 token
                    $this->where(['device_id'=> $device_id, 'id'=> ['neq', $id]])->update(['status'=> 2]);

                $data['id'] = $id;

                return $data;
            } else {
                echo $this->rsa1024_model->RSA_private_encrypt(error('创建令牌失败了'));
                exit;
            }
        }
    }

    /**
     * 验证令牌
     */
    public function checkToken($token)
    {
        return $this->where(['token' => $token])->find();
    }

    /**
     * 退出登陆
     */
    public function out($id)
    {
        return $this->where(['id' => $id])->update(['status'=> 3]);
    }

    /**
     * 获取随机 token
     * */
    protected function rand_token($prefix)
    {
        $token = md5(time() . $prefix) . time() . mt_rand(0,9) . mt_rand(0,9);

        $user = $this->where(['token'=> $token])->find();

        if (empty($user)) {
            return $token;
        }
        else {
            return $this->rand_token($prefix);
        }
    }
}