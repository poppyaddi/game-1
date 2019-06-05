<?php
namespace app\port\controller;

use app\port\base\Token as TokenBase;
use app\port\model\Games as GamesModel;
use app\port\model\Store as StoreModel;
use app\port\model\StoreLog as StoreLogModel;
use app\port\model\GamesPrice as GamesPriceModel;

class Transaction extends TokenBase
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
                'price'=> $localizedprice,
                'desc'=> $localizedDescription,
                'game_id'=> $game['gs_id'],
                'price_id'=> $price['id'],
                'status'=> 1,
                'start_time'=> $transactionDate,
//                'end_time',
                'identifier'=> $transactionIdentifier,
                'receipt'=> $transactionReceipt,
                'user_id'=> $this->user_id,
            ];

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
     * 出库-根据游戏包名获取可用凭证列表
     * */
    public function table()
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

            $sort = [
                'money'=> 'asc',
            ];

            $price = $this->games_price_model->where($map)->order($sort)->select();

            $data = [];

            foreach ($price as $key=> $value)
            {
                $map = [
                    'status'=> ['in', [1,5]],
                    'is_goods'=> 0,
                    'price_id'=> $value['id'],
                    'user_id'=> $this->user_id,
                ];

                $num = $this->store_model->where($map)->count();
                
                $item = [];
                $item['id'] = $value['id'];
                $item['title'] = $value['title'];
                $item['money'] = number_format($value['money']) . '元';
                $item['gold'] = $value['gold'];
                $item['num'] = $num;

                $data[] = $item;
            }

        return $this->RSA_private_encrypt(succ($data));
    }

//    /**
//     * 出库-获取单个凭证
//     * */
//    public function info()
//    {
//        $id = $this->param('id'); // 包名
//
//        if (empty($id ))
//        {
//            return $this->RSA_private_encrypt(error('id length is 0'));
//        }
//
//        $map = [
//            'id'=> $id,
//            'user_id'=> $this->user_id,
//        ];
//
//        $store = $this->store_model->where($map)->find();
//
//        if (empty($store))
//        {
//            return $this->RSA_private_encrypt(error('凭证不存在'));
//        }
//
//        $data = [
//            "id"=> $store['id'],
//            "price"=> $store['price'],
//            "desc"=> $store['desc'],
//            "status"=> $store['status'],
//            "start_time"=> $store['start_time'],
//            "end_time"=> $store['end_time'],
//            "identifier"=> $store['identifier'],
//            "receipt"=> $store['receipt'],
//        ];
//
//        return $this->RSA_private_encrypt(succ($data));
//    }


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
            ];


        // 兼容新版插件入库的凭证
        //...
            if (empty($data['receipt']))
            {
                $data['receipt'] = $store['new_receipt'];
            }
        //...


            return $this->RSA_private_encrypt(succ($data));
    }

    /**
     * 出库-使用凭证
     * */
    public function consumption()
    {
        $id = $this->param('id'); // 凭证ID

        if (empty($id ))
        {
            return $this->RSA_private_encrypt(error('id length is 0'));
        }

        // 验证凭证是否未使用
            $map = [
                'id'=> $id,
                'user_id'=> $this->user_id,
            ];

            $store = $this->store_model->where($map)->find();

            if ($store['status'] != 1 && $store['status'] != 5 && $store['status'] != 6)
            {
                return $this->RSA_private_encrypt(error('凭证状态错误'));
            }

            if ($store['is_goods'])
            {
                return $this->RSA_private_encrypt(error('凭证已经发布到交易市场，无法出库'));
            }

        // 修改凭证状态
            $data = [
                'status'=> 2,
                'use_time'=> $this->date,
            ];

            if ($this->store_model->where($map)->update($data))
            {
                // 获取有效同类型凭证数量
                    $map = [
                        'is_goods'=> 0,
                        'status'=> ['in', [1, 5]],
                        'price_id'=> $store['price_id'],
                        'user_id'=> $this->user_id,
                    ];

                    $count = $this->store_model->where($map)->count();

                // 记录日志
                    $data = [
                        'desc'=> '标记凭证出库成功',
                        'user_id'=> $this->user_id,
                        'store_id'=> $store['id'],
                    ];

                    $this->store_log_model->insert($data);


                return $this->RSA_private_encrypt(succ('标记出库成功, 同类型凭证剩余 ' . $count . ' 个'));
            }
            else {
                return $this->RSA_private_encrypt(error('标记出库失败'));
            }
    }

    /**
     * 出库-凭证无效
     * */
    public function invalid()
    {
        $id = $this->param('id'); // 凭证ID
        $err_code = $this->param('err_code'); // 凭证ID
        $err_msg = $this->param('err_msg'); // 凭证ID

        if (empty($id))
        {
            return $this->RSA_private_encrypt(error('id length is 0'));
        }

        // 验证凭证是否未使用
            $map = [
                'id'=> $id,
                'user_id'=> $this->user_id,
            ];

            $store = $this->store_model->where($map)->find();

            if ($store['status'] != 1 && $store['status'] != 5 && $store['status'] != 6)
            {
                return $this->RSA_private_encrypt(error('凭证状态错误'));
            }

            if ($store['is_goods'])
            {
                return $this->RSA_private_encrypt(error('凭证已经发布到交易市场，无法出库'));
            }

        // 修改凭证状态
            $data = [
                'status'=> 4,
                'err_code'=> $err_code,
                'err_msg'=> $err_msg,
                'use_time'=> $this->date,
            ];

            if ($this->store_model->where($map)->update($data))
            {
                // 记录日志
                    $data = [
                        'desc'=> '标记凭证出库失败',
                        'user_id'=> $this->user_id,
                        'store_id'=> $store['id'],
                    ];

                    $this->store_log_model->insert($data);

                return $this->RSA_private_encrypt(succ('标记成功'));
            }
            else {
                return $this->RSA_private_encrypt(error('标记失败'));
            }
    }
}
