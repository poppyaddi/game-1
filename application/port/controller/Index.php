<?php
namespace app\port\controller;

use app\port\model\Games as GamesModel;
use app\port\base\Rsa1024;

class Index extends Rsa1024
{
    protected $games_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->games_model = new GamesModel;
    }

    public function index()
    {
        return $this->support();
    }

    /**
     * 判断游戏是否支持
     * */
    public function support()
    {
        $productIdentifier = $this->param('productIdentifier'); // 包名

        if (empty($productIdentifier))
        {
            return $this->RSA_private_encrypt(error('productIdentifier length is 0'));
        }

        // 获取游戏
        $map = [
            'gs_status'=> 1,
            'productIdentifier'=> $productIdentifier,
        ];

        $game = $this->games_model->where($map)->find();

        if (!empty($game))
        {
//            $data = [
//                'message'=> '支持这个游戏',
//                'receipt_type'=> $game['receipt_type'],
//            ];
//
//            return $this->RSA_private_encrypt(succ($data));
            $data = [
                'code'=> 200,
                'auth'=> true,
                'data'=> (object) [],
                'message'=> '支持这个游戏',
                'receipt_type'=> $game['receipt_type'],
            ];

            return $this->RSA_private_encrypt(($data));
        }
        else {
            return $this->RSA_private_encrypt(error('不支持这个游戏'));
        }
    }
}
