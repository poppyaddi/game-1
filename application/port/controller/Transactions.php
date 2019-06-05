<?php
namespace app\port\controller;

use app\port\base\Token as TokenBase;
use app\port\model\Games as GamesModel;
use app\port\model\Store as StoreModel;
use app\port\model\StoreLog as StoreLogModel;
use app\port\model\GamesPrice as GamesPriceModel;

class Transactions extends TokenBase
{
    protected $games_model;
    protected $store_model;
    protected $store_log_model;
    protected $games_price_model;
    protected $time;
    protected $date;

    public function _initialize()
    {
        parent::_initialize();
        $this->games_model = new GamesModel;
        $this->store_model = new StoreModel;
        $this->store_log_model = new StoreLogModel;
        $this->games_price_model = new GamesPriceModel;

        $this->time = time();
        $this->date = date('Y-m-d H:i:s');
    }

    public function index()
    {
        return $this->input();
    }

    /**
     * 入库-接收凭证
     * */
    public function input()
    {
        // 处理参数
            $transactionIdentifier = $this->param('transactionIdentifier'); // 订单号
            $transactionDate = $this->param('transactionDate'); // 生成日期
            $transactionReceipt = $this->param('transactionReceipt'); // 凭证
            $newTransactionReceipt = $this->param('newTransactionReceipt'); // 新凭证

            $localizedprice = $this->param('localizedPrice'); // 面值
            $localizedTitle = $this->param('localizedTitle'); // 标题
            $localizedDescription = $this->param('localizedDescription'); // 描述

            $productIdentifier = $this->param('productIdentifier'); // 包名

            if (empty($transactionIdentifier))
            {
                return $this->RSA_private_encrypt(error('transactionIdentifier length is 0'));
            }

            if (empty($transactionDate))
            {
                return $this->RSA_private_encrypt(error('transactionDate length is 0'));
            }

            if (empty($transactionReceipt))
            {
                return $this->RSA_private_encrypt(error('transactionReceipt length is 0'));
            }

            if (empty($newTransactionReceipt))
            {
                return $this->RSA_private_encrypt(error('newTransactionReceipt length is 0'));
            }

            if (empty($localizedprice))
            {
                return $this->RSA_private_encrypt(error('price length is 0'));
            }

            if (empty($localizedTitle))
            {
                return $this->RSA_private_encrypt(error('localizedTitle length is 0'));
            }

            if (empty($localizedDescription))
            {
                return $this->RSA_private_encrypt(error('localizedDescription length is 0'));
            }

            if (empty($productIdentifier))
            {
                return $this->RSA_private_encrypt(error('productIdentifier length is 0'));
            }

        // 验证游戏凭证是否重复录入
            $game = $this->store_model->find(function ($query) use($transactionIdentifier, $transactionReceipt)
            {
                $query->where('identifier', $transactionIdentifier)
                    ->whereOr('receipt', $transactionReceipt);
            });

            if (!empty($game))
            {
                return $this->RSA_private_encrypt(error('凭证重复'));
            }

        // 验证游戏类型是否支持
            $map = [
                'productIdentifier'=> $productIdentifier,
            ];

            $game = $this->games_model->where($map)->find();

            if (empty($game))
            {
                return $this->RSA_private_encrypt(error('不支持该类型游戏'));
            }

        // 验证游戏面值是否支持
            $map = [
                'title'=> $localizedTitle,
            ];

            $price = $this->games_price_model->where($map)->find();

            if (empty($price))
            {
                return $this->RSA_private_encrypt(error('不支持该面值'));
            }


        // 保存凭证到库存
            $data = [
                'price'=> $localizedprice != '---null---' ? $localizedprice : $price['money'],
                'desc'=> $localizedDescription != '---null---' ? $localizedDescription : $price['gold'],
                'game_id'=> $game['gs_id'],
                'price_id'=> $price['id'],
                'status'=> 1,
                'start_time'=> $transactionDate,
//                'end_time',
                'identifier'=> $transactionIdentifier,
                'receipt'=> $transactionReceipt,
                'user_id'=> $this->user_id,
            ];

            if (!empty($newTransactionReceipt))
            {
                $data['new_receipt'] = $newTransactionReceipt;
            }

            $store_id = $this->store_model->insertGetId($data);

            if ($store_id)
            {
                // 记录凭证
                    $data = [
                        'desc'=> '手机用户入库',
                        'user_id'=> $this->user_id,
                        'store_id'=> $store_id,
                    ];

                    $this->store_log_model->insert($data);


                return $this->RSA_private_encrypt(succ('入库成功'));
            }
            else {
                return $this->RSA_private_encrypt(error('入库失败, 请稍后再试'));
            }
    }

    /**
     * 出库-根据面值标识获取单个凭证
     * */
    public function info_one()
    {
        $title = $this->param('title'); // 面值名

        if (empty($title))
        {
            return $this->RSA_private_encrypt(error('title length is 0'));
        }

        // 获取支持面值
            $map = [
                'status'=> 1,
                'title'=> $title,
            ];

            $price = $this->games_price_model->where($map)->find();

            if (empty($price))
            {
                return $this->RSA_private_encrypt(error('面值未开放'));
            }

        // 获取凭证
            $map = [
                'is_goods'=> 0,
                'price_id'=> $price['id'],
                'user_id'=> $this->user_id,
            ];

        // 跳过使用过的凭证
            $userInfo = $this->userInfo();

            if ($userInfo['pass_store'] == 1)
            {
                $map['status'] = ['in', [1,5]];
            }
            else {
                $map['status'] = ['in', [1, 5, 6]];
            }

            $store = $this->store_model->where($map)->order('id asc')->limit(1)->select();

            if (empty($store))
            {
                return $this->RSA_private_encrypt(error('凭证不存在'));
            }

            $store = $store[0];

        // 标记凭证已经使用
            $this->store_model->where(['id'=> $store['id']])->update(['status'=> 6, 'use_time'=> $this->date]);

        // 记录日志
            $data = [
                'desc'=> '用户获取凭证',
                'user_id'=> $this->user_id,
                'store_id'=> $store['id'],
            ];

            $this->store_log_model->insert($data);

        // 返回凭证
            $data = [
                "id"=> $store['id'],
                "price"=> $store['price'],
                "desc"=> $store['desc'],
                "status"=> $store['status'],
                "start_time"=> $store['start_time'],
                "end_time"=> $store['end_time'],
                "identifier"=> $store['identifier'],
                "receipt"=> $store['receipt'],
                "new_receipt"=> $store['new_receipt'],
            ];


        // 兼容老版本插件入库的凭证
        //...
            if (empty($data['receipt']) && !empty($data['new_receipt']))
            {
                if (mb_strlen($data['new_receipt']) > 3)
                {
                    if (strtolower(mb_substr(trim($data['new_receipt'], 'utf8'),0,3)) == 'ewo') {
                        $data['receipt'] = $data['new_receipt'];
                    }
                }
            }

            if (empty($data['new_receipt']) && !empty($data['receipt']))
            {
                if (mb_strlen($data['receipt']) > 3)
                {
                    if (strtolower(mb_substr(trim($data['receipt'], 'utf8'),0,3)) == 'mii') {
                        $data['new_receipt'] = $data['receipt'];
                    }
                }
            }
        //...


            return $this->RSA_private_encrypt(succ($data));
    }
}
