<?php
namespace app\admin\controller;
use app\admin\model\GoodsModel;
use app\admin\model\UserModel;

class Goods extends Base
{
    /**
     * 商品列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件+++
            $where = [];
            if (isset($param['goodsName']) && !empty($param['goodsName'])) {
                $where['gd_name'] = ['like', '%' . $param['goodsName'] . '%'];
            }
            if (isset($param['goodsPrice']) && !empty($param['goodsPrice'])) {
                $where['gd_price'] = $param['goodsPrice'];
            }
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $goods = new GoodsModel();
            $selectResult = $goods->getGoodsByWhere($where, $offset, $limit);

            $status = config('role_status');
            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['created_at'] = date('Y-m-d H:i:s', $vo['created_at']);
                $selectResult[$key]['gd_status'] = $status[$vo['gd_status']];

                if ($this->userInfo['id'] == 1){
                    $operate = [
                        '编辑' => url('goods/goodsEdit', ['gd_id' => $vo['gd_id']]),
                        '删除' => "javascript:goodsDel('".$vo['gd_id']."')"
                    ];
                }else{
                    $operate = [
                        '购买' => url('goods/goodsBuy', ['gd_id' => $vo['gd_id']])
                    ];
                }

                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $goods->getAllGoods($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 添加商品
     * @return mixed|\think\response\Json
     */
    public function goodsAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);
            $param['created_at'] = time();
            $param['updated_at'] = '';
            $goods = new GoodsModel();
            $flag = $goods->insertgoods($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $this->assign([
            'status' => config('role_status'),
        ]);

        return $this->fetch();
    }

    /**
     * 编辑商品
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsEdit()
    {
        $goods = new GoodsModel();

        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);
            $param['updated_at'] = time();
            $flag = $goods->editgoods($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.gd_id');

        $this->assign([
            'goods' => $goods->getOnegoods($id),
            'status' => config('role_status')
        ]);

        return $this->fetch();
    }

    /**
     * 删除商品
     * @return \think\response\Json
     */
    public function goodsDel()
    {
        if ($this->userInfo['id'] != 1){
            return json(['code' => -1, 'data' => '', 'msg' => '权限不足']);
        }
        $id = input('param.gd_id');
        $goods = new GoodsModel();
        $flag = $goods->delgoods((int)$id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * 商品购买
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodBuy(){
        if(request()->isAjax()){

            $Goods   = new GoodsModel();
            $Users   = new UserModel();
            $goodsId = input('gd_id');
            if (!$goodsId){
                return json(['code' => -1, 'data' => '', 'msg' => '参数错误']);
            }
            //卖家ID
            $buyUid  = $this->userInfo['id'];
            //商品信息
            $goodsInfo = $Goods->getOneGoods($goodsId);
            //卖家ID
            $sellUid  = $goodsInfo['gd_uid'];
            $sellUser = $Users->getOneUser($sellUid);
            //计算余额
            $surPlus = $this->userInfo['money'] - $goodsInfo['gd_price'];
            //余额小于0
            if ($surPlus < 0){
                return json(['code' => -1, 'data' => '', 'msg' => '账户余额不足']);
            }
            //事务开始
            $Goods->startTrans();
            //卖家扣款
            $this->userInfo['money'] = $surPlus;
            //卖家收取%1手续费
            $sellUser['money'] += $goodsInfo * 0.99;
            //更新数据
            $r[] = $Users->editUser($this->userInfo);
            $r[] = $Users->editUser($sellUser);
            //TODO 订单表未确定
        }
    }

}
