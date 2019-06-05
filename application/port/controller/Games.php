<?php
namespace app\port\controller;

use app\port\base\Token as TokenBase;
use app\port\model\Games as GamesModel;
use app\port\model\GamesPrice as GamesPriceModel;

class Games extends TokenBase
{
    protected $time;
    protected $games_model;
    protected $games_price_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->games_model = new GamesModel;
        $this->games_price_model = new GamesPriceModel;

        $this->time = time();
    }

    public function index()
    {
        return $this->game();
    }

//    /**
//     * 获取支持游戏列表
//     * */
//    public function games()
//    {
//        $list = $this->games_model->select(function ($query) {
//            $query->where('gs_status', 1)
//                ->whereOr('productIdentifier', ['exp', 'is not null']);
//        });
//
//        $productIdentifier = [];
//
//        foreach ($list as $key=> $value)
//        {
//            $productIdentifier[] = $value['productIdentifier'];
//        }
//
//        $data['productIdentifier'] = $productIdentifier;
//
//        return $this->RSA_private_encrypt(succ($data));
//    }

    /**
     * 入库-根据游戏ID获取支持的面值列表
     * */
    public function price()
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

            if (empty($game))
            {
                return $this->RSA_private_encrypt(error('不支持这个游戏入库'));
            }

        // 获取支持面值
            $map = [
                'status'=> 1,
                'gs_id'=> $game['gs_id'],
            ];

            $price = $this->games_price_model->where($map)->select();

            $data = [];

            foreach ($price as $key=> $value)
            {
                $item = [];
                $item['id'] = $value['id'];
                $item['title'] = $value['title'];
                $item['money'] = number_format($value['money']) . '元';
                $item['gold'] = $value['gold'];

                $data[] = $item;
            }


        return $this->RSA_private_encrypt(succ($data));
    }

    /**
     * 增加游戏类型
     * */
    public function addGame()
    {
        $gs_name = $this->param('gs_name'); // 游戏名称
        $productIdentifier = $this->param('productIdentifier'); // 游戏包名
        $money = $this->param('money'); // 人民币
        $gold = $this->param('gold'); // 金币
        $title = $this->param('title'); // 面值标识

        if (empty($this->userInfo() ['admin']))
        {
            return $this->RSA_private_encrypt(error('您没有添加面值的权限'));
        }

        // 验证游戏是否已经存在
            $map = [
                'productIdentifier'=> $productIdentifier,
            ];

            $game = $this->games_model->where($map)->find();

            if (empty($game))
            {
                $data = [
                    'gs_name'=> $gs_name,
                    'gs_content'=> '手机添加，用户ID: ' . $this->user_id,
                    'productIdentifier'=> $productIdentifier,
                    'created_at'=> $this->time,
                    'updated_at'=> $this->time,
                ];

                $game_id = $this->games_model->insertGetId($data);

                if (!$game_id)
                {
                    return $this->RSA_private_encrypt(error('添加游戏失败'));
                }
            }
            else {
                $game_id = $game['gs_id'];
            }

        // 验证面值是否已经存在
            $map = [
                'title'=> $title,
            ];

            $price = $this->games_price_model->where($map)->find();

            if (empty($price))
            {
                $data = [
                    'gs_id'=> $game_id,
                    'money'=> $money,
                    'gold'=> $gold,
                    'title'=> $title,
                ];

                $price_id = $this->games_price_model->insertGetId($data);

                if ($price_id)
                {
                    return $this->RSA_private_encrypt(succ('面值添加成功'));
                }
                else {
                    return $this->RSA_private_encrypt(error('面值添加失败'));
                }
            }
            else {
                return $this->RSA_private_encrypt(succ('面值已经添加到数据库'));
            }
    }
}
