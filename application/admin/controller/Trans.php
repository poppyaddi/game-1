<?php
namespace app\admin\controller;

use app\admin\model\UserModel;
use app\admin\model\OrderModel;
use app\admin\model\TransModel;
use app\admin\model\StockModel;
use app\admin\model\GamesModel;
use app\admin\model\ItemizeModel;
use app\admin\model\UserLogModel;
use app\admin\model\StockLogModel;
class Trans extends Base {

    /**
     * 交易列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function index()
    {

//        if (!in_array($this->userInfo['id'],['24','18','5','97'])){
//            $this->error('建设完善中','Index/indexPage');
//            return;
//        }

        $games = new GamesModel();

        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            $where['status'] = array('in','1,2');
            //所属用户
            if (isset($param['userName']) && !empty($param['userName'])) {
                $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                $where['user_id'] = db('user')->where($userName)->find()['id'];
            }
            //库存单号
            if (isset($param['orderId']) && !empty($param['orderId'])) {
                $where['order_id'] = ['like', '%' . $param['orderId'] . '%'];
            }
            //游戏名称
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['game_id'] = ['like', '%' . $param['gamesId'] . '%'];
            }
            //面值名称
            if (isset($param['itemizeId']) && !empty($param['itemizeId'])) {
                $where['price_id'] = ['like', '%' . $param['itemizeId'] . '%'];
            }
            //游戏名称
            if (isset($param['gamesName']) && !empty($param['gamesName'])) {
                $gamesName['gs_name'] = ['like', '%' . $param['gamesName'] . '%'];
                $gamesArr = db('games')->where($gamesName)->select();

                if (count($gamesArr))
                {
                    $gs_id = [];
                    foreach ($gamesArr as $key=> $val)
                    {
                        $gs_id[] = $val['gs_id'];
                    }

                    $where['game_id'] = ['in', $gs_id];
                }
                else {
                    $where['game_id'] = ['lt', 0];
                }
            }
            //开始日期   --- 结束日期
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }

            $user = new UserModel();
            $trans = new TransModel();
            $itemize = new ItemizeModel();
            $selectResult = $trans->getTransByWhere($where, $offset, $limit);

            $status = config('trans_status');
            //用来更新使用
            $orderstatus = ['已经过期','订单下架'];
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['user_name'] = $user->getOneUser($vo['user_id'])['username'];
                $selectResult[$key]['price_id'] = $itemize->getOneItemizes(['gs_id'=>$vo['game_id'],'id'=>$vo['price_id']])['gold'];
                $selectResult[$key]['game_id'] = $games->getOneGames($vo['game_id'])['gs_name'];
                $selectResult[$key]['end_time'] = date('Y-m-d h:i:s',$vo['end_time']);
                //按钮筛选
                if (in_array($vo['status'],$orderstatus)){
                    $selectResult[$key]['operate'] = <<<EOT
    <div class="btn-group">
        <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false" disabled>
            操作 <span class="caret"></span>
        </button>
    </div>
EOT;
                }else{
                    //判断是否是自己的 是自己的可以下架
                    if ($this->userInfo['id'] == $vo['user_id']){
                        $operate = [
                            '下架订单' => "javascript:downTrans('".$vo['trans_id']."')",
                        ];
                    }else{
                        $operate = [
                            '购买订单' => "javascript:buyTrans('".$vo['trans_id']."')",
                        ];
                    }

                    $selectResult[$key]['operate'] = showOperate($operate);
                }
            }
            $return['total'] = $trans->getAllTransCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        $this->assign([
            'games' => $games->getAllGames(''),
        ]);
        return $this->fetch();
	}

    /**
     * 发布交易
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function transAdd() {

        $games = new GamesModel();

        if(request()->isAjax()) {
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['game_id'] = input('post.id');
            //获取库存ID
            $where = [
                'user_id'  => $this->userInfo['id'],
                'game_id'  => $param['game_id'],
                'price_id' => $param['price_id'],
                'is_goods' => 0,
                'status'   => 1,
            ];
            //判断交易密码
            if (empty($param['pay_pass'])){

                return json(['code' => -1, 'data' => array(), 'msg' => '请输入二级密码']);
            }

            if (md5($param['pay_pass']) != $this->userInfo['pay_pass']){

                return json(['code' => -1, 'data' => array(), 'msg' => '交易密码不正确']);
            }
            unset($param['pay_pass']);

            $stockNum = db('store')->where($where)->field('id')->select();
            //判断游戏是否选中
            if (!$param['game_id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择游戏名称']);
            }
            //判断面值是否选中
            if (!$param['price_id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择游戏面值']);
            }
            //判断价格是否有值
            if (!$param['trans_price'] || $param['trans_price'] < 0){
                return json(['code' => -1, 'data' => array(), 'msg' => '请输入游戏价格']);
            }
            //判断交易数量
            if (!$param['trans_num'] || $param['trans_num'] < 0 || $param['trans_num'] > count($stockNum) || is_int($param['trans_num'])){
                return json(['code' => -1, 'data' => array(), 'msg' => '请输入正确的数量']);
            }

            //事务开始
            db()->startTrans();

            $trans = new TransModel();
            $stock = new StockModel();
            $stockLog = new StockLogModel();
            $map['id'] = array('in',$param['store_id']);
            //记录库存ID
            for ($i = 0 ; $i < $param['trans_num'];$i++){
                $param['store_id'] .= $stockNum[$i]['id'].',';
                //发布库存日志
                $data[$i] = [
                    'desc'     => "用户".$this->userInfo['username']."上架库存",
                    'user_id'  => $this->userInfo['id'],
                    'store_id' => $stockNum[$i]['id'],
                ];

                $r3[] = $stockLog->insertGetId($data[$i]);
            }

            $param['store_id'] = rtrim($param['store_id'],',');
            $param['user_id'] = $this->userInfo['id'];
            $param['trans_total'] = $param['trans_num'] * $param['trans_price'];
            $param['order_id'] =  date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            $param['created_at'] = time();
            $param['updated_at'] = '';
            $param['end_time'] = time() + 259200;

            $r1 = $stock->saveStock($map,['is_goods' => 1]);
            $r2 = $trans->insertTrans($param);
            //返回值判断事务是否回滚
            if ($r1['code'] == 1 && $r2['code'] == 1 && !in_array(false,$r3)){
                db()->commit();
                return json(['code' => 1, 'data' => array(), 'msg' => '发布交易成功']);
            }else{
                db()->rollback();
                return json(['code' => -1, 'data' => array(), 'msg' => '发布交易失败']);
            }

        }
        $this->assign([
            'games' => $games->getAllGames(''),
        ]);
		// 渲染模板输出
		return $this->fetch ();
	}

    /**
     * 订单记录
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function orderList()
    {

//        if (!in_array($this->userInfo['id'],['24','18','5','97'])){
//            $this->error('建设完善中','Index/indexPage');
//            return;
//        }

        $games = new GamesModel();
        if (request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            $whereOr = [];
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $whereOr['buy_uid'] = array('eq',$this->userInfo['id']);
                $whereOr['sell_uid'] = array('eq',$this->userInfo['id']);
            }

            //买家用户
            if (isset($param['buyName']) && !empty($param['buyName'])) {
                $buyName['username'] = ['like', '%' . $param['buyName'] . '%'];
                $where['buy_uid'] = db('user')->where($buyName)->find()['id'];
            }
            //卖家用户
            if (isset($param['sellName']) && !empty($param['sellName'])) {
                $sellName['username'] = ['like', '%' . $param['sellName'] . '%'];
                $where['sell_uid'] = db('user')->where($sellName)->find()['id'];
            }

            //订单单号
            if (isset($param['orderId']) && !empty($param['orderId'])) {
                $where['order_id'] = ['like', '%' . $param['orderId'] . '%'];
            }
            //游戏名称
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['game_id'] = $param['gamesId'];
            }
            //面值名称
            if (isset($param['itemizeId']) && !empty($param['itemizeId'])) {
                $where['price_id'] = $param['itemizeId'];
            }
            //游戏名称
            if (isset($param['gamesName']) && !empty($param['gamesName'])) {
                $gamesName['gs_name'] = ['like', '%' . $param['gamesName'] . '%'];
                $gamesArr = db('games')->where($gamesName)->select()['id'];

                if (count($gamesArr))
                {
                    $gs_id = [];
                    foreach ($gamesArr as $key=> $val)
                    {
                        $gs_id[] = $val['gs_id'];
                    }

                    $where['game_id'] = ['in', $gs_id];
                }
                else {
                    $where['game_id'] = ['lt', 0];
                }
            }

            //开始日期   --- 结束日期
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between', array(strtotime($param['startTime']), strtotime($param['endTime'])));
            }
            $user = new UserModel();
            $orders = new OrderModel();
            $itemize = new ItemizeModel();
            $selectResult = $orders->getOrderByWhere($where, $offset, $limit, $whereOr);
            //用来更新使用
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['buy_uid'] = $user->getOneUser($vo['buy_uid'])['username'];
                $selectResult[$key]['sell_uid'] = $user->getOneUser($vo['sell_uid'])['username'];
                $selectResult[$key]['price_id'] = $itemize->getOneItemizes(['gs_id'=>$vo['game_id'],'id'=>$vo['price_id']])['gold'];
                $selectResult[$key]['game_id'] = $games->getOneGames($vo['game_id'])['gs_name'];

            }
            $return['total'] = $orders->getAllOrderCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        $this->assign([
            'games' => $games->getAllGames(''),
        ]);
		// 渲染模板输出
		return $this->fetch ('trans/orderList');
	}

    /**
     * 订单交易
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function buyTrans(){

        $trans = new TransModel();
        $games = new GamesModel();
        $price = new ItemizeModel();
        if (request()->isAjax()){
            $param = input('param.');
            $param = parseParams($param['data']);
            $stockNum = $trans->getOneTrans($param['trans_id']);
            //判断交易密码
            if (empty($param['pay_pass'])){
                return json(['code' => -1, 'data' => array(), 'msg' => '请输入二级密码']);
            }
            //判断交易密码
            if (md5($param['pay_pass']) != $this->userInfo['pay_pass']){

                return json(['code' => -1, 'data' => array(), 'msg' => '交易密码不正确']);
            }
            //判断订单是否过期
            if ($stockNum['end_time'] - time() < 0){
                return json(['code' => -1, 'data' => array(), 'msg' => '该商品已过期']);
            }
            //判断交易数量
            if (!$param['trans_num'] || $param['trans_num'] < 0 || $param['trans_num'] > $stockNum['trans_num'] || is_int($param['trans_num'])){
                return json(['code' => -1, 'data' => array(), 'msg' => '请输入正确的数量']);
            }
            //判断用户余额
            if ($this->userInfo['money'] - ($stockNum['trans_price'] * $param['trans_num']) < 0){
                return json(['code' => -1, 'data' => array(), 'msg' => '用户余额不足']);
            }

            $order    = new OrderModel();
            $user     = new UserModel();
            $userLog  = new UserLogModel();
            $stock    = new StockModel();
            $stockLog = new StockLogModel();
            //事务开启
            db()->startTrans();

            //处理数据
            $store_id = explode(',',$stockNum['store_id']);
            $stockNum['store_id'] = '';
            //第一次循环将用户买到的ID分离出来 更新用户订单
            for ($i = 0; $i < $param['trans_num'];$i++){

                $param['store_id'] .= $store_id[$i].',';


                //发布库存日志
                $Log[$i] = [
                    'desc'     => "用户".$this->userInfo['username']."购买库存",
                    'user_id'  => $this->userInfo['id'],
                    'store_id' => $store_id[$i],
                ];
                unset($store_id[$i]);

                //开始添加库存日志
                $r10[] = $stockLog->insertGetId($Log[$i]);
            }

            //是否全部购买
            if (!empty($store_id)){
                $store_id = array_values($store_id);
                //第二次循环将剩余的ID进行分离   更新商品信息
                for ($j = 0; $j < count($store_id); $j++){

                    $stockNum['store_id'] .= $store_id[$j].',';
                }
            }else{
                $stockNum['store_id'] = '';
            }

            //保险起见在来一次
            $transNum = $stockNum['trans_num'] - $param['trans_num'];
            //保险起见判断一下
            if ($transNum < 0){
                return json(['code' => -1, 'data' => array(), 'msg' => '当前商品数量不足']);
            }elseif ($transNum == 0){
                //全部出售  改为已过期
                $status = 3;
            }else{
                //部分出售
                $status = 2;
            }

            //手续费
            $fee     = db('config')->where(['key'=>'transfee'])->find()['value'];
            $sellInfo =  $user->getOneUser($stockNum['user_id']);
            $price = $stockNum['trans_price'] * $param['trans_num'];

            //更新商品信息
            $transInfo = [
                'trans_id'    => $stockNum['trans_id'],
                'store_id'    => rtrim($stockNum['store_id'],','),
                'trans_num'   => $transNum,
                'trans_total' => $stockNum['trans_total'] - $price,
                'status'      => $status,
                'updated_at'  => time(),
            ];

            //更新订单信息
            $transOrder = [
                'order_id'    => date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8),
                'buy_uid'     => $this->userInfo['id'],
                'sell_uid'    => $stockNum['user_id'],
                'game_id'     => $stockNum['game_id'],
                'price_id'    => $stockNum['price_id'],
                'trans_num'   => $param['trans_num'],
                'trans_price' => $stockNum['trans_price'],
                'trans_total' => $price,
                'created_at'  => time(),
                'updated_at'  => '',
                'store_id'    => rtrim($param['store_id'],','),
            ];

            //添加卖家用户日志
            $buyUser = [
                'ug_uid'     => $this->userInfo['id'],
                'ug_status'  => 4 ,
                'ug_money'   => $price ,
                'ug_cgmoney' => $this->userInfo['money'] - $price,
                'ug_content' => '订单交易减少',
                'created_at' => time()
            ];

            //添加卖家用户日志
            $sellUser = [
                'ug_uid'     => $stockNum['user_id'],
                'ug_status'  => 3 ,
                'ug_money'   => $price ,
                'ug_cgmoney' => $sellInfo['money'] + $price,
                'ug_content' => '订单交易增加',
                'created_at' => time()
            ];

            //添加卖家扣除手续费
            $sellFee = [
                'ug_uid'     => $stockNum['user_id'],
                'ug_status'  => 5 ,
                'ug_money'   => $price * $fee,
                'ug_cgmoney' => $sellInfo['money'] + $price - ($price * $fee),
                'ug_content' => '收取1%交易手续费',
                'created_at' => time()
            ];

            //更新库存给买家
            $buyStock = [
                'user_id' => $this->userInfo['id'],
                'status'  => 1,
                'is_goods'=> 0,
            ];

            //买家扣除资产
            $r1 = $user->setUserDec($this->userInfo['id'],'money',$price);
            //卖家增加资产
            $r2 = $user->setUserInc($stockNum['user_id'],'money',$price - ($price * $fee));
            //更新交易订单
            $r3 = $trans->editTrans($transInfo);
            //添加购买记录
            $r4 = $order->insertOrder($transOrder);
            //添加买家用户日志
            $r5 = $userLog->insertUserLog($buyUser);
            //添加卖家用户日志
            $r6 = $userLog->insertUserLog($sellUser);
            //添加卖家扣除手续费
            $r7 = $userLog->insertUserLog($sellFee);
            //更新库存到买家
            $where['id'] = array('in',rtrim($param['store_id'],','));
            $r8 = $stock->saveStock($where,$buyStock);
            //更新总手续费
            $r9 = db('config')->where(array('key'=>'money'))->setInc('value',$price * $fee);
            //开始判断返回值
            if(!empty($r1) && !empty($r2) && $r3['code'] == 1 && $r4['code'] == 1 && $r5['code'] == 1 && $r6['code'] == 1 && $r7['code'] == 1 && $r8['code'] == 1 && !empty($r9) && !in_array(false,$r10)){
                db()->commit();
                return json(['code' => 1, 'data' => array(), 'msg' => '购买成功']);
            }else{
                db()->rollback();
                return json(['code' => -1, 'data' => array(), 'msg' => '购买失败']);
            }

        }
        $data = $trans->getOneTrans(input('param.id'));
        $data['game_id']  = $games->getOneGames($data['game_id'])['gs_name'];
        $data['price_id'] = $price->getOneItemize($data['price_id'])['gold'];
        $this->assign([
            'trans' => $data,
        ]);
        return $this->fetch ();
    }

    /**
     * 订单下架
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downTrans(){
        if (request()->isAjax()){
            $param['trans_id'] = input('param.id');
            $param['status'] = 4;
            if (!$param['trans_id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '参数错误']);
            }
            $trans = new TransModel();
            $stock = new StockModel();
            $stockLog = new StockLogModel();

            $trans_id = $trans->getOneTrans($param['trans_id'])['store_id'];
            $id = explode(',',$trans_id);
            //事务开启
            db()->startTrans();
            for ($i = 0; $i < count($id); $i++){
                $data[$i] = [
                    'desc'     => "用户".$this->userInfo['username']."下架库存",
                    'user_id'  => $this->userInfo['id'],
                    'store_id' => $id[$i],
                ];
                $r3[] = $stockLog->insertGetId($data[$i]);
            }
            $map['id'] = array('in',$trans_id);
            $r1 = $stock->saveStock($map,['is_goods' => 0]);
            $r2 = $trans->editTrans($param);
            //通过返回值判断事务是否提交
            if ($r1['code'] == 1 && $r2['code'] == 1){
                db()->commit();
                return json(['code' => 1, 'data' => array(), 'msg' => '下架订单成功']);
            }else{
                db()->rollback();
                return json(['code' => -1, 'data' => array(), 'msg' => '下架订单失败']);
            }
        }

    }

    /**
     * 获取库存数量
     * @return \think\response\Json
     */
    public function stockNum(){

        if (request()->isAjax()){

            $where['user_id']  = $this->userInfo['id'];
            $where['game_id']  = input('param.game_id');
            $where['price_id'] = input('param.price_id');
            $where['is_goods'] = 0;
            $where['status']   = 1;
            //赠送的时候可以多个状态
            if (input('type') == 1){
                $where['status']  = array('in','1,5,6');
            }

            $data = db('store')->where($where)->count();
            if (!empty($data)){

                return json(['code'=> 1 , 'msg' => '获取数据成功' , 'data' => $data]);
            }

            return json(['code'=> -1 , 'msg' => '获取数据失败' , 'data' => array()]);
        }

    }

    /**
     * 库存赠送
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function transGive() {

        $games = new GamesModel();

        if(request()->isAjax()) {
            $param = input('param.');
            $param = parseParams($param['data']);
            $param['game_id'] = input('post.id');
            $param['user_id'] = input('post.user_id');
            //获取库存ID
            $where = [
                'user_id'  => $this->userInfo['id'],
                'game_id'  => $param['game_id'],
                'price_id' => $param['price_id'],
                'is_goods' => 0,
                'status'   => array('in','1,5,6'),
            ];
            //判断交易密码
            if (empty($param['pay_pass'])){

                return json(['code' => -1, 'data' => array(), 'msg' => '请输入二级密码']);
            }

            if (md5($param['pay_pass']) != $this->userInfo['pay_pass']){

                return json(['code' => -1, 'data' => array(), 'msg' => '交易密码不正确']);
            }

            $stockNum = db('store')->where($where)->field('id')->select();

            //判断游戏是否选中
            if (!$param['game_id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择游戏名称']);
            }
            //判断面值是否选中
            if (!$param['price_id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择游戏面值']);
            }

            //判断用户是否选中
            if (!$param['user_id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择要赠送的用户']);
            }

            if ($param['user_id'] == $this->userInfo['id']){
                return json(['code' => -1, 'data' => array(), 'msg' => '库存不能自己赠送给自己']);
            }

            //判断交易数量
            if (!$param['trans_num'] || $param['trans_num'] < 0 || $param['trans_num'] > count($stockNum) || is_int($param['trans_num'])){
                return json(['code' => -1, 'data' => array(), 'msg' => '请输入正确的数量']);
            }

            $data = [];

            //事务开始
            db()->startTrans();

            $stock = new StockModel();
            $stockLog = new StockLogModel();

            //记录库存ID

            for ($i = 0 ; $i < $param['trans_num'];$i++){
                //更新库存使用
                $param['store_id'] .= $stockNum[$i]['id'].',';
                //赠送使用
                $data[$i] = [
                    'desc'     => "用户".$this->userInfo['username']."赠送库存给".db('user')->where(['id' => $param['user_id']])->find()['username'],
                    'user_id'  => $this->userInfo['id'],
                    'store_id' => $stockNum[$i]['id'],

                ];

                $r2[] = $stockLog->insertGetId($data[$i]);
            }

            $param['store_id'] = rtrim($param['store_id'],',');


            $map['id'] = array('in',$param['store_id']);

            $r1 = $stock->saveStock($map,['user_id' => $param['user_id']]);

            //返回值判断事务是否回滚
            if ($r1['code'] == 1 && !in_array(false,$r2)){
                db()->commit();
                return json(['code' => 1, 'data' => array(), 'msg' => '赠送成功']);
            }else{
                db()->rollback();
                return json(['code' => -1, 'data' => array(), 'msg' => '赠送失败']);
            }

        }
        $this->assign([
            'games' => $games->getAllGames(''),
            'user'  => db('user')->field('id,username')->select(),
        ]);
        // 渲染模板输出
        return $this->fetch ();
    }

}
