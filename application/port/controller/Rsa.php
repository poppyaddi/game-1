<?php

/**
 * //... !!!本页方法全部为测试代码，上线全部删除
 * */

namespace app\port\controller;

use app\port\base\Rsa1024;

class Rsa extends Rsa1024
{
    public function _initialize()
    {
        $this->rsa_path = ROOT_PATH . 'application' . DS . 'port' . DS;
    }

    public function index()
    {
        echo 'Hi !';
        return;
    }

    /**
     * 客户端私钥加密
     * */
    public function client_private_encrypt()
    {
        $data = trim(file_get_contents('php://input'));

        echo $this->RSA_private_encrypt($data, 2);
    }

    /**
     * 客户端公钥解密
     * */
    public function client_public_decrypt()
    {
        $data = trim(file_get_contents('php://input'));

        var_dump($this->RSA_public_decrypt($data, 2));
    }

    /**
     * 服务端私钥加密
     * */
    public function server_private_encrypt()
    {
        $data = trim(file_get_contents('php://input'));

        echo $this->RSA_private_encrypt($data, 1);
    }

    /**
     * 服务端公钥解密
     * */
    public function server_public_decrypt()
    {
        $data = trim(file_get_contents('php://input'));

        var_dump($this->RSA_public_decrypt($data, 1));
    }
}
