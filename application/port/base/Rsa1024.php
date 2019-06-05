<?php
namespace app\port\base;

use think\Controller;

class Rsa1024 extends Controller
{
    protected $rsa_path = '';
    protected $raw = [];

    public function _initialize()
    {
        $this->rsa_path = ROOT_PATH . 'application' . DS . 'port' . DS;

        $this->get_raw();
    }

    /**
     * 从 raw 中获取数据
     * */
    protected function param($name, $allow_null=false)
    {
        if (empty($name))
        {
            echo $this->RSA_private_encrypt(error('参数名为空'));
            exit;
        }

        if (empty($this->raw->$name))
        {
            if ($allow_null == true)
            {
                return '';
            }

            echo $this->RSA_private_encrypt(error($name . ' is null'));
            exit;
        }

        return $this->raw->$name;
    }

    /*
     * raw 参数处理
     * */
    protected function get_raw()
    {
        // 接收参数
            $data = trim(file_get_contents('php://input'));

            if (empty($data))
            {
                echo $this->RSA_private_encrypt(error('参数为空'));
                exit;
            }

        // 解密参数
            $raw = $this->RSA_public_decrypt($data);

            if (empty($raw))
            {
                echo $this->RSA_private_encrypt(error('数据错误'));
                exit;
            }

            if (is_string($raw))
            {
                $raw = json_decode($raw);
            }

            $this->raw = $raw;
    }

    /*
     * RSA 私钥加密[默认,服务端]
     * 2018/06/07
     * */
    public function RSA_private_encrypt($data, $type=1)
    {
        $data = json_encode($data);

        $crypto = '';

        foreach (str_split($data, 117) as $chunk)
        {
            openssl_private_encrypt($chunk, $encrypted, $this->getPrivate($type)); // 私钥加密

            $crypto .= $encrypted;
        }

        return base64_encode($crypto);
    }

    /*
     * RSA 公钥解密[默认,客户端]
     * 2018/06/07
     * */
    protected function RSA_public_decrypt($data, $type=2)
    {
        $data = base64_decode($data);

        $crypto = '';

        foreach (str_split($data, 128) as $chunk)
        {
            openssl_public_decrypt($chunk, $decryptData, $this->getPublic($type)); // 使用私匙解密

            $crypto .= $decryptData;
        }

        return json_decode($crypto);
    }

    /*
     * 读取公钥
     * */
    protected function getPublic($type=1)
    {
        $key = $type == 1 ? config('server_public_key') : config('client_public_key');

        $public_key = file_get_contents($this->rsa_path . $key);

        if (empty($public_key))
        {
            echo $this->RSA_private_encrypt(error("公钥文件不存在"));
            exit;
        }

        $pu_key = openssl_pkey_get_public($public_key);

        if (empty($pu_key))
        {
            echo $this->RSA_private_encrypt(error("公钥读取失败"));
            exit;
        }

        return $pu_key;
    }

    /*
     * 读取私钥
     * */
    protected function getPrivate($type=1)
    {
        $key = $type == 1 ? config('server_private_key') : config('client_private_key');

        $private_key = file_get_contents($this->rsa_path . $key);

        if (empty($private_key))
        {
            echo $this->RSA_private_encrypt(error("私钥文件不存在"));
            exit;
        }

        $pi_key = openssl_pkey_get_private($private_key);

        if (empty($pi_key))
        {
            echo $this->RSA_private_encrypt(error("私钥读取失败"));
            exit;
        }

        return $pi_key;
    }
}  