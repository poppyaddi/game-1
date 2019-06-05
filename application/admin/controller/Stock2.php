<?php
namespace app\admin\controller;

use think\Db;
use app\admin\model\StockLogModel;
use app\admin\model\UserModel;
use app\admin\model\StockModel;
use app\admin\model\GamesModel;
use app\admin\model\ItemizeModel;

class Stock2 extends Base
{
    /**
     * 库存列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {

        $games = new GamesModel();

        if(request()->isAjax())
        {
            $user = new UserModel();
            $stock = new StockModel();
            $itemize = new ItemizeModel();

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            $order = 'id';
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //所属用户
            if (isset($param['userName']) && !empty($param['userName'])) {

                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }
            }
            //库存单号
            if (isset($param['orderId']) && !empty($param['orderId'])) {
                $where['identifier'] = ['like', '%' . $param['orderId'] . '%'];
            }
            //库存单号
            if (isset($param['type']) && !empty($param['type'])) {
                $order = 'use_time';
            }
            //游戏名id
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['game_id'] = $param['gamesId'];
            }
            //面值名称
            if (isset($param['itemizeId']) && !empty($param['itemizeId'])) {
                $where['price_id'] = ['like', '%' . $param['itemizeId'] . '%'];
            }
            //库存状态
            if (isset($param['status']) && !empty($param['status'])) {
                $where['status'] = $param['status'];
            }
            //是否交易
            if (isset($param['isGoods']) && !empty($param['isGoods'])) {
                if ($param['isGoods'] == 2){
                    $where['is_goods'] = 0;
                }else{
                    $where['is_goods'] = $param['isGoods'];
                }

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
                $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }

            $selectResult = $stock->getStockByWhere($where, $offset, $limit,'',$order);

            $status = config('stock_status');
            $goods = config('stock_goods');
            //用来更新使用
            $stockstatus = ['正常有效(可以交易)'];
            foreach($selectResult as $key=>$vo){
				$p = db('games_price')->find($vo['price_id']);
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['user_id'] = $user->getOneUser($vo['user_id'])['username'];
                $selectResult[$key]['price_id'] = $itemize->getOneItemizes(['gs_id'=>$vo['game_id'],'id'=>$vo['price_id']])['gold'];
                $selectResult[$key]['game_id'] = $games->getOneGames($vo['game_id'])['gs_name'];
              	$selectResult[$key]['money'] = $p['money'];
                $selectResult[$key]['is_goods'] = $goods[$vo['is_goods']];
//按钮筛选

                if (in_array($vo['status'], $stockstatus))
                {
                    $selectResult[$key]['operate'] = <<<EOT
    <div class="btn-group">
        <button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false" disabled>
            操作 <span class="caret"></span>
        </button>
    </div>
EOT;
                }else{
                    $operate = [
                        '设置为可出库(不可交易)' => "javascript:saveStock('".$vo['id']."')",
                        '设置为已使用' => "javascript:destructionStock('".$vo['id']."')",
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }
            }
            $return['total'] = $stock->getAllStockCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

            $ulist=db('user')->select();
        $this->assign('ulist',$ulist);
        $this->assign([
            'games' => $games->getAllGames(''),
        ]);

        return $this->fetch();
    }
  
  	/**
     * ⇒凭证管理分配
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index2()
    {

        $games = new GamesModel();

        if(request()->isAjax())
        {
            $user = new UserModel();
            $stock = new StockModel();
            $itemize = new ItemizeModel();

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            $order = 'id';
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //所属用户
            if (isset($param['userName']) && !empty($param['userName'])) {

                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }
            }
            //库存单号
            if (isset($param['orderId']) && !empty($param['orderId'])) {
                $where['identifier'] = ['like', '%' . $param['orderId'] . '%'];
            }
            //库存单号
            if (isset($param['type']) && !empty($param['type'])) {
                $order = 'use_time';
            }
            //游戏名id
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['game_id'] = $param['gamesId'];
            }
            //面值名称
            if (isset($param['itemizeId']) && !empty($param['itemizeId'])) {
                $where['price_id'] = ['like', '%' . $param['itemizeId'] . '%'];
            }
            //库存状态
            if (isset($param['status']) && !empty($param['status'])) {
                $where['status'] = $param['status'];
            }
            //是否交易
            if (isset($param['isGoods']) && !empty($param['isGoods'])) {
                if ($param['isGoods'] == 2){
                    $where['is_goods'] = 0;
                }else{
                    $where['is_goods'] = $param['isGoods'];
                }

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
                $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
          	//排除uid
          	if (isset($param['u_id']) && !empty($param['u_id'])) {
                $where['user_id'] = ['<>', $param['u_id']];
            }
          	
          	//包含指定uid
          	if (isset($param['z_id']) && !empty($param['z_id'])) {
                $where['user_id'] = [$where['user_id'],['=', $param['z_id']],"and"];
            }

            $selectResult = $stock->getStockByWhere($where, $offset, $limit,'',$order);
			
            $status = config('stock_status');
            $goods = config('stock_goods');
            //用来更新使用
            $stockstatus = ['正常有效(可以交易)'];
            foreach($selectResult as $key=>$vo){
				$p = db('games_price')->find($vo['price_id']);
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['user_id'] = $user->getOneUser($vo['user_id'])['username'];
                $selectResult[$key]['price_id'] = $itemize->getOneItemizes(['gs_id'=>$vo['game_id'],'id'=>$vo['price_id']])['gold'];
                $selectResult[$key]['game_id'] = $games->getOneGames($vo['game_id'])['gs_name'];
              	$selectResult[$key]['money'] = $p['money'];
                $selectResult[$key]['is_goods'] = $goods[$vo['is_goods']];
//按钮筛选

                if (in_array($vo['status'], $stockstatus))
                {
                    $selectResult[$key]['operate'] = <<<EOT
    <div class="btn-group">
        <button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false" disabled>
            操作 <span class="caret"></span>
        </button>
    </div>
EOT;
                }else{
                    $operate = [
                        '设置为可出库(不可交易)' => "javascript:saveStock('".$vo['id']."')",
                        '设置为已使用' => "javascript:destructionStock('".$vo['id']."')",
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }
            }
            $return['total'] = $stock->getAllStockCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

            $ulist=db('user')->select();
        $this->assign('ulist',$ulist);
        $this->assign([
            'games' => $games->getAllGames(''),
        ]);

        return $this->fetch();
    }

    /**
     * 库存概览
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function stockOver()
    {

        $games = new GamesModel();

        if(request()->isAjax())
        {
            $user = new UserModel();
            $stock = new StockModel();
            $itemize = new ItemizeModel();

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //所属用户
            if (isset($param['userName']) && !empty($param['userName'])) {
                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }
            }
            //游戏名id
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['game_id'] = $param['gamesId'];
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
                $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }

            $selectResult = $stock->getStockByWhere($where, $offset, $limit,'user_id,game_id,price_id');

            //用来更新使用
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['sort'] = $key + 1;
                //统计数量
                $stockNum = Db::query("SELECT count(*) AS stock_num,`status` FROM `cz_store` WHERE `user_id`= ".$vo['user_id']." AND `game_id` = ".$vo['game_id']." AND `price_id` = ".$vo['price_id']." GROUP BY status ORDER BY status ASC");

                //状态默认值
                $selectResult[$key]['enable'] = 0;
                $selectResult[$key]['usable'] = 0;
                $selectResult[$key]['fail']   = 0;
                $selectResult[$key]['sure']   = 0;

                //开始循环
                foreach ($stockNum as $kk => $vv){

                    //按状态区分类型
                    switch ($vv['status']){
                        case 1 :
                            //正常有效
                            $selectResult[$key]['enable'] = $vv['stock_num'];
                            break;
                        case 2 :
                            //已用数量
                            $selectResult[$key]['usable'] = $vv['stock_num'];
                            break;
                        case 4 :
                            //使用失败
                            $selectResult[$key]['fail']   = $vv['stock_num'];
                            break;
                        case 5 :
                            //可以出库
                            $selectResult[$key]['sure']   = $vv['stock_num'];
                            break;
                    }
                }

                //用户名称
                $selectResult[$key]['user_id']  = $user->getOneUser($vo['user_id'])['username'];
                //面值名称
                $selectResult[$key]['price_id'] = $itemize->getOneItemizes(['gs_id'=>$vo['game_id'],'id'=>$vo['price_id']])['gold'];
                //游戏名称
                $selectResult[$key]['game_id']  = $games->getOneGames($vo['game_id'])['gs_name'];

            }
            $return['total'] = db('store')->where($where)->group('user_id,game_id,price_id')->count();
            $return['rows'] = $selectResult;

            return json($return);
        }

        $this->assign([
            'games' => $games->getAllGames(''),
        ]);

        return $this->fetch('stock/stockOver');
    }

    /**
     * 库存日志
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function stockLog()
    {

        if(request()->isAjax())
        {
            $user = new UserModel();
            $stock = new StockModel();
            $games = new GamesModel();
            $itemize = new ItemizeModel();
            $stockLog = new StockLogModel();

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //所属用户
            if (isset($param['userName']) && !empty($param['userName'])) {

                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }
            }
            //开始日期   --- 结束日期
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }

            $selectResult = $stockLog->getStockLogByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['user_id'] = $user->getOneUser($vo['user_id'])['username'];

                $stockInfo = $stock->getOneStock($vo['store_id']);

                $selectResult[$key]['store_id'] = $games->getOneGames($stockInfo['game_id'])['gs_name']."----".$itemize->getOneItemize($stockInfo['price_id'])['gold'];

            }
            $return['total'] = $stockLog->getAllStockLogCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch('stock/stockLog');
    }

    /**
     * 用游戏ID获取面值列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function itemizeList(){
        //实例化面值模型
        $itemize = new ItemizeModel();
        $data = $itemize->getAllItemize(['gs_id'=>input('post.id')]);
        if (!empty($data)){

            return json(['code'=> 1 , 'msg' => '数据获取成功' , 'data' => $data]);
        }

        return json(['code'=> -1 , 'msg' => '数据获取失败' , 'data' => array()]);
    }

    /**
     * 设置未使用，不可交易
     * @return \think\response\Json
     */
    public function saveStock()
    {
        if (request()->isAjax())
        {
            $id = input('param.id');
            $data = db('store')->where(['id'=>$id])->update(['status'=>5]);
            if (!empty($data))
            {
                $param = [
                    'user_id' => $this->userInfo['id'],
                    'desc'    => '设置未使用，不可交易',
                    'store_id'=> $id,
                ];
                db('store_log')->insertGetId($param);
                return json(['code'=> 1 , 'msg' => '设置成功' , 'data' => array()]);
            }

            return json(['code'=> -1 , 'msg' => '设置失败' , 'data' => array()]);
        }
    }

    /**
     * 设置已使用
     * @return \think\response\Json
     */
    public function destructionStock()
    {
        if (request()->isAjax())
        {
            $id = input('param.id');
            $data = db('store')->where(['id'=>$id])->update(['status'=>2]);
            if (!empty($data))
            {
                $param = [
                    'user_id' => $this->userInfo['id'],
                    'desc'    => '设置已使用',
                    'store_id'=> $id,
                ];
                db('store_log')->insertGetId($param);
                return json(['code'=> 1 , 'msg' => '设置成功' , 'data' => array()]);
            }

            return json(['code'=> -1 , 'msg' => '设置失败' , 'data' => array()]);
        }
    }

    /**
     * 外部库存导入
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function stockImport(){


        if (request()->isAjax()){

            import('PHPExcel.PHPExcel', EXTEND_PATH);//方法二
            //获取表单上传文件
            $file = request()->file('stock');
            $userId = input('user_id');
            //判断是否选中所属用户
            if (empty($userId)){

                return json(['code' => -1, 'data' => array(), 'msg' => '请选择需要导入的用户']);
            }

            //文件不为空的情况下
            if (!empty($file)){

                //判断文件大小和后缀
                $info = $file->validate(['size'=>1567800,'ext'=>'xlsx,xls,csv'])->move(ROOT_PATH . 'public' . DS . 'excel');
                if(!empty($info)){

                    $exclePath = $info->getSaveName();  //获取文件名

                    $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $exclePath;   //上传文件的地址

                    //判断截取文件
                    $extension = strtolower( pathinfo($file_name, PATHINFO_EXTENSION) );

                    //区分上传文件格式
                    if($extension == 'xlsx') {

                        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                        $objPHPExcel = $objReader->load($file_name, $encode = 'utf-8');

                    }else if($extension == 'xls'){

                        $objReader = \PHPExcel_IOFactory::createReader('Excel5');
                        $objPHPExcel = $objReader->load($file_name, $encode = 'utf-8');
                    }else if($extension == 'csv'){

                        $objReader = \PHPExcel_IOFactory::createReader('CSV');
                        $objPHPExcel = $objReader->load($file_name, $encode = 'utf-8');
                    }

                    $excel_array = $objPHPExcel->getsheet(0)->toArray();   //转换为数组格式

                    array_shift($excel_array);  //删除第一个数组(标题);

                    $data = [];
                    $r = [];
                    db()->startTrans();
                    foreach($excel_array as $k => $v) {
                        $data[$k]['user_id']  = $userId;
                        $data[$k]['receipt']  = $v[0];
                        $data[$k]['status']  = 1;
                        $data[$k]['add_time'] = time();

                        $r[] = db('receipt')->insertGetId($data[$k]);
                    }

                    if (!in_array(false,$r)){
                        db()->commit();
                        return json(['code' => 1, 'data' => array(), 'msg' => '导入成功']);
                    }else{
                        db()->rollback();
                        return json(['code' => -1, 'data' => array(), 'msg' => '导入失败']);
                    }
                }else{
                    // 上传失败获取错误信息
                    return json(['code' => -1, 'data' => array(), 'msg' => '文件格式不正确']);
                }
            }else{
                //判断是否选中文件
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择Excel文件']);
            }
        }

        $this->assign('user',db('user')->field('id,username')->select());

        return $this->fetch('stock/stockImport');

    }

    /**
     * 库存导入情况
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function stockInfo()
    {
        $user = new UserModel();

        if(request()->isAjax())
        {


            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //所属用户
            if (isset($param['userName']) && !empty($param['userName'])) {

                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }
            }
            //开始日期   --- 结束日期
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['add_time'] = array('between',array($param['startTime'],$param['endTime']));
            }
            $status = config('import_status');
//            $selectResult = $stockLog->getAllStockLog($where, $offset, $limit);
            $selectResult = db('receipt')->where($where)->order('id desc')->field('add_time,id,status,user_id,status_zn,updatetime')->limit($offset,$limit)->select();

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['user_id'] = $user->getOneUser($vo['user_id'])['username'];
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['add_time'] = date('Y-m-d h:i:s',$vo['add_time']);
            }
            $return['total'] = db('receipt')->where($where)->count();  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        $this->assign('user' ,$user->getAllUsers(''));

        return $this->fetch('stock/stockInfo');
    }

    /**
     * 导出失败库存
     */
    public function stockExcel(){

        $list = db('receipt')->where(['status' => 2 ,'user_id' => input('user_id')])->select();
        $title = [
            '库存内容',
        ];
        $data = [];
        foreach ($list as $k => $v){
            $data[$k][] = $v['receipt'];
        }

        $this->exportExcel($data,$title,'库存导入失败'.date('Y-m-d H:i:s',time()));
    }


    /**
     * 导出个人库存
     */
    public function exportStock()
    {
        $user = new UserModel();

        ini_set('memory_limit','512M');

        $where = [];
        $param  = input('post.');
        if (session('role') == '管理员'){
            if (!empty($param['userName']))
            {
                $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
            }
        }else{
            $where['user_id'] = $this->userInfo['id'];
        }

        //所属用户
        if (isset($param['userName']) && !empty($param['userName'])) {

            if (session('role') == '管理员'){
                $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                $where['user_id'] = $user ->getUsersByField($userName)['id'] ;
            }else{
                $where['user_id'] = $this->userInfo['id'];
            }
        }
        //库存单号
        if (isset($param['orderId']) && !empty($param['orderId'])) {
            $where['identifier'] = ['like', '%' . $param['orderId'] . '%'];
        }
        //游戏名id
        if (isset($param['gamesId']) && !empty($param['gamesId'])) {
            if ($param['export']){
                $where['game_id'] = db('games')->where(['gs_name'=>$param['gamesId']])->find()['gs_id'];
            }else{
                $where['game_id'] = $param['gamesId'];
            }
        }
        //面值名称
        if (isset($param['itemizeId']) && !empty($param['itemizeId'])) {
            $where['price_id'] = $param['itemizeId'];
        }
        //库存状态
        if (isset($param['status']) && !empty($param['status'])) {
            $where['status'] = $param['status'];
        }
        //是否交易
        if (isset($param['isGoods']) && !empty($param['isGoods'])) {
            if ($param['isGoods'] == 2){
                $where['is_goods'] = 0;
            }else{
                $where['is_goods'] = $param['isGoods'];
            }

        }
        //游戏名称
        if (isset($param['gamesName']) && !empty($param['gamesName'])) {
            $gamesName['gs_name'] = ['like', '%' . $param['gamesName'] . '%'];
            $gamesArr = db('games')->where($gamesName)->select();

            if (count($gamesArr))
            {
                $gs_id = [];
                foreach ($gamesArr as $key => $val)
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
            $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
        }
        $list = db('store')->where($where)->field('id,receipt')->select();

        if (empty($list))
        {
            $this->error('数据为空','admin/stock/index');
        }

        $title = [
            '库存内容',
        ];
        $data = [];
        $id = '';
        $r1 = [];
        $r2 = [];
        foreach ($list as $k => $v){
            $data[$k][] = $v['receipt'];
            $id .= $v['id'].',';
            $r2[] = (string) db('store_log')->insertGetId(['desc'=>'导出用户凭证','user_id'=>$this->userInfo['id'],'store_id'=>$v['id']]);
        }
        db()->startTrans();
        $r1[] = (string) db('store')->where(['id'=>['in',rtrim($id,',')]])->update(['status'=> 7]);

        if (!in_array('false', $r1) && !in_array('false', $r2)){
            db()->commit();
            $this->exportExcel($data,$title,'库存'.date('Y-m-d H:i:s',time()));
        }else{
            db()->rollback();
            $this->error('导出失败','index/indexPage');
        }
    }



    /**
     * 匹配管理员
     * */
    public function pipei_admin(){
        if(request()->isPost()){
            $data=input('post.');
            $res=db('store')->where(['id'=>['in',$data['ids']]])->update(['user_id'=>$data['uid']]);
            if($res){
                return $this->result('','1','操作成功');
            }else{
                return $this->result('','0','操作失败');
            }

        }
    }
  
  	/**
     * 分配管理员
     * */
    public function pipei_admin2(){
        if(request()->isPost()){
            $data=input('post.');
          	
          	$param = $data;      
            $where = [];     
          	//游戏名id
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['game_id'] = $param['gamesId'];
            }
            //面值名称
            if (isset($param['itemizeId']) && !empty($param['itemizeId'])) {
                $where['price_id'] = $param['itemizeId'];
            }  
          	//排除uid
          	if (isset($param['u_id']) && !empty($param['u_id'])) {
                $where['user_id'] = ['<>', $param['u_id']];
            }
          	//包含指定uid
          	if (isset($param['z_id']) && !empty($param['z_id'])) {
                $where['user_id'] = [$where['user_id'],['=', $param['z_id']],"and"];
            }
          	$where['status'] = 1;
          	//数量
          	$fpnum = isset($param['fpnum']) && !empty($param['fpnum'])?$param['fpnum']:0;
          	if(!$fpnum){
            	return $this->result('','0','数量不能为空');exit;
            }
            $res=db('store')->where($where)->limit($fpnum)->update(['user_id'=>$data['u_id']]);exit;
            if($res){
                return $this->result('','1','操作成功');
            }else{
                return $this->result('','0','操作失败');
            }

        }
    }

    /**
     * 删除数据
     * */
    public function del_store(){
        if(request()->isPost()){
            $data=input('post.');
            $res=db('store')->where(['id'=>['in',$data['ids']]])->delete();
            if($res){
                return $this->result('','1','操作成功');
            }else{
                return $this->result('','0','操作失败');
            }

        }
    }




}
