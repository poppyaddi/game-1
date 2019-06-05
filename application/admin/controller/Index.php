<?php
namespace app\admin\controller;
use think\Db;
use app\admin\model\UserModel;
use app\admin\model\HelpModel;
use app\admin\model\StockModel;
use app\admin\model\NoticeModel;
use app\admin\model\UserLogModel;
use app\admin\model\MoneyLogModel;

class Index extends Base
{
    /**
     * 后台默认首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch('/index');
    }

    /**
     * 后台默认首页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function indexPage()
    {
        $Notice  = new NoticeModel();
        $Help    = new HelpModel();
        $User    = new UserModel();
        $Stock   = new StockModel();

        $data['total']  = '平台总额';
        $data['stock_num']  = '库存数量';
        $data['stocks']     = '库存价值';
        $data['orders']      = '订单数量';
        $data['trans']      = '出售总额';
        $data['assets']     = '冻结金额';

        if (session('role') == '管理员'){
            $data['user']       = db('user')->sum('money');
            $data['stock']      = db('store')->sum('price');
            $data['order']      = db('order')->sum('trans_total');
            $data['money']      = db('user')->sum('fro_money');
            $data['stock_nums'] = $Stock->getAllStockCount('');
            $data['order_nums'] = db('order')->count();
            $data['notice']     = $Notice ->getNoticeByWhere('',0,10);
            $data['help']       = $Help->getHelpsByWhere('',0,10);
            $data['stock_info'] = Db::query("select DATE_FORMAT(create_time,'%Y-%m-%d') days,count(*) AS  day_count,sum(price) AS day_sum  from cz_store group by days order by days DESC LIMIT 0,10");

            $data['order_info'] = db('order')->order('created_at DESC')->field('sell_uid,trans_total,created_at')->limit(0,10)->select();

            if (!empty($data['order_info'])){
                foreach ($data['order_info'] as $k => $v){
                    $data['order_info'][$k]['sell_uid'] = mb_substr(db('user')->where(['id' => $v['sell_uid']])->find()['username'], 0, 1,'utf-8') . "****";
                    $data['order_info'][$k]['created_at'] = date('Y-m-d H:i:s',$v['created_at']);
                }
            }else{
                $data['order_info'] = 0;
            }

        }else{
            $data['total']  = '账户余额';
            $data['user']   = db('user')->where(['id'=>$this->userInfo['id']])->sum('money');
            $data['stock']  = db('store')->where(['user_id'=>$this->userInfo['id']])->sum('price');
            $data['order']  = db('order')->where(['sell_uid'=>$this->userInfo['id']])->sum('trans_total');
            $data['money']  = db('user')->where(['id'=>$this->userInfo['id']])->sum('fro_money');
            $data['stock_nums']   = $Stock->getAllStockCount(['user_id' => $this->userInfo['id']]);
            $data['order_nums'] = db('order')->whereOr(['buy_uid'=>$this->userInfo['id'],'sell_uid'=>$this->userInfo['id']])->count();
            $data['notice']     = $Notice ->getNoticeByWhere('',0,10);
            $data['help']       = $Help->getHelpsByWhere('',0,10);
            $data['stock_info'] = Db::query("select DATE_FORMAT(create_time,'%Y-%m-%d') days,count(*) AS  day_count,sum(price) AS day_sum  from cz_store WHERE `user_id` = ".$this->userInfo['id']." group by days order by days DESC LIMIT 0,10");

            $data['order_info'] = db('order')->order('created_at DESC')->field('sell_uid,trans_total,created_at')->limit(0,10)->select();

            if (!empty($data['order_info'])){
                foreach ($data['order_info'] as $k => $v){
                    $data['order_info'][$k]['sell_uid'] = mb_substr(db('user')->where(['id' => $v['sell_uid']])->find()['username'], 0, 1) . "****";
                    $data['order_info'][$k]['created_at'] = date('Y-m-d H:i:s',$v['created_at']);
                }
            }else{
                $data['order_info'] = 0;
            }

        }
        $data['status'] = $User->getOneUser($this->userInfo['id'])['notice'];

        //输出信息
        $this ->assign('data',$data);
        // 渲染模板输出
        return $this->fetch('index');
    }

    /**
     * 公告WEB页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function noticeWeb(){
        $Notice = new NoticeModel();
        $list = $Notice ->getOneNotice(input('ne_id'));
        //输出信息
        $this ->assign('data',$list);
        // 渲染模板输出
        return $this->fetch('noticeWeb');
    }

    /**
     * 帮助WEB页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function helpWeb(){
        $Help = new HelpModel();
        $list = $Help ->getOneHelp(input('hp_id'));
        //输出信息
        $this ->assign('data',$list);
        // 渲染模板输出
        return $this->fetch('helpWeb');
    }

    /**
     * 免责声明WEB页
     * @return mixed
     */
    public function disclaWeb(){
        $list = Db::name('config')->where(['key'=>'discla'])->find()['value'];
        //输出信息
        $this ->assign('data',$list);
        // 渲染模板输出
        return $this->fetch('disclaWeb');
    }

    /**
     * 免责声明WEB页
     * @return mixed
     */
    public function forgetPay(){
        // 渲染模板输出
        return $this->fetch('forgetPay');
    }

    /**
     * 首次登陆WEB页
     * @return mixed
     */
    public function firstWeb(){
        $list = Db::name('config')->where(['key'=>'protocol'])->find()['value'];
        //输出信息
        $this ->assign('data',$list);
        // 渲染模板输出
        return $this->fetch('firstWeb');
    }

    /**
     * 首次登陆成功
     * 更新用户状态
     * @return mixed
     */
    public function firstLogin(){
        if(request()->isPost()) {
            $User = new UserModel();
            $this->userInfo['notice'] = 2;
            $result = $User->editUser($this->userInfo);
            //判断返回值
            if (!$result) {
                return json(['data' => array(), 'code' => -1, 'msg' => '数据获取失败']);
            }
            return json(['data' => array(), 'code' => 1, 'msg' => '感谢您观看用户协议']);
        }
    }

    /**
     * 个人信息Web页
     * @return mixed
     */
    public function personWeb(){

        $this->assign('user',$this->userInfo);
        return $this->fetch('personWeb');
    }

    /**
     * 修改密码Web页
     * @return mixed
     */
    public function forgetPass(){

        return $this->fetch('forgetPass');
    }

    /**
     * 充值Web页
     * @return mixed
     */
    public function moneyWeb(){

        $config = Db::name('config')->select();

        foreach ($config as  $k=>$v){
            $list[$v['key']] = $v['value'];
        }
        $this->assign('config',$list);

        return $this->fetch('moneyWeb');
    }

    /**
     * 更新个人信息
     * @return \think\response\Json
     */
    public function personSave(){

        if(request()->isPost()){

            $user = new UserModel();
            $param = input('post.');
            $pass = input('post.pass');
            $type = input('post.type');
            $param = parseParams($param['data']);

            //判断交易密码是否一致
            if (!empty($type)){
                //默认没有交易密码提醒用户
                if (!$this->userInfo['pay_pass'])
                    return json(['code' => -1, 'data' => array(), 'msg' => '请前往修改二级密码处设置二级密码']);

                if (md5($pass) != $this->userInfo['pay_pass'])
                    return json(['code' => -1, 'data' => array(), 'msg' => '二级密码不正确']);
            }
            //登录密码
            if (!empty($param['password'])){
                if (strlen($param['password']) < 6)
                    return json(['code' => -1, 'data' => array(), 'msg' => '密码长度需要大于6位']);
                if (md5($param['old_pass']) != $this->userInfo['password'])
                    return json(['code' => -1, 'data' => array(), 'msg' => '旧的密码不正确']);
                if (md5($param['new_pass']) != md5($param['password']))
                    return json(['code' => -1, 'data' => array(), 'msg' => '两次密码输入不一致']);
                if (md5($param['password']) == $this->userInfo['password'])
                    return json(['code' => -1, 'data' => array(), 'msg' => '新密码不能与原密码相同']);
                unset($param['old_pass']);
                unset($param['new_pass']);
                $param['password'] = md5($param['password']);
                $param['session_id'] = '';
            }
            //交易密码
            if (!empty($param['pay_pass'])){
                if (strlen($param['pay_pass']) < 6)
                    return json(['code' => -1, 'data' => array(), 'msg' => '密码长度需要大于6位']);
                if ($this->userInfo['pay_pass']){
                    if (md5($param['old_pay']) != $this->userInfo['pay_pass'])
                        return json(['code' => -1, 'data' => array(), 'msg' => '旧的密码不正确']);
                }
                if (md5($param['new_pay']) != md5($param['pay_pass']))
                    return json(['code' => -1, 'data' => array(), 'msg' => '两次密码输入不一致']);
                if (md5($param['pay_pass']) == $this->userInfo['pay_pass'])
                    return json(['code' => -1, 'data' => array(), 'msg' => '新密码不能与原密码相同']);
                unset($param['old_pay']);
                unset($param['new_pay']);
                $param['pay_pass'] = md5($param['pay_pass']);
            }
            $param['id'] = $this->userInfo['id'];
            $flag = $user->editUser($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
    }

    /**
     * 充值写入日志
     * @return \think\response\Json
     */
    public function moneyAccount(){

        if(request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            if ($param['cz_money'] != $param['cz_moneys']){
                return json(['code' => -1, 'data' => array(), 'msg' => '两次输入的金额不一致']);
            }
            $moneylog = new MoneyLogModel();
            unset($param['cz_moneys']);
            $param['cz_uid'] = $this->userInfo['id'];
            $param['cz_type'] = 1;
            $param['created_at'] = time();
            $flag = $moneylog->insertMoneyLog($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => '充值申请成功']);
        }
    }

    /**
     * 账户提现WEB
     * @return mixed
     */
    public function forwardWeb(){
        $config['desc'] = Db::name('config')->where(['key'=>'desc'])->find()['value'];
        $config['money'] = $this->userInfo['money'];
        $this->assign('user',$config);
        return $this->fetch('forwardWeb');
    }


    /**
     * 账户余额WEB
     * @return mixed
     */
    public function myBalance(){

        $this->assign('user',$this->userInfo);
        return $this->fetch('myBalance');
    }

    /**
     * 提现写入日志
     * @return \think\response\Json
     */
    public function moneyForward(){

        if(request()->isPost()) {
            $param = input('post.');
            $param = parseParams($param['data']);
            $pass  = input('post.pass');
            if (!$this->userInfo['pay_pass']){
                return json(['code' => -1, 'data' => array(), 'msg' => '您当前没有二级密码请修改二级密码']);
            }

            if (md5($pass) != $this->userInfo['pay_pass']){
                return json(['code' => -1, 'data' => array(), 'msg' => '二级密码不正确']);
            }
            //判断两次输入的金额是否一致
            if ($param['cz_money'] != $param['cz_moneys']){
                return json(['code' => -1, 'data' => array(), 'msg' => '两次输入的金额不一致']);
            }
            //判断金额不能小于100
            if ($param['cz_money'] < 100){
                return json(['code' => -1, 'data' => array(), 'msg' => '提现最低金额100元']);
            }
            //判断余额是否足够
            if ($this->userInfo['money'] - $param['cz_money'] < 0){
                return json(['code' => -1, 'data' => array(), 'msg' => '账户余额不足']);
            }
            $fee = db('config')->where(['key'=>'putfee'])->find()['value'];


            Db::startTrans();


            //添加用户日志
            $userLog = new UserLogModel();
            $list = [
                'ug_uid'     => $this->userInfo['id'],
                'ug_status'  => 6 ,
                'ug_money'   => $param['cz_money'],
                'ug_cgmoney' => $this->userInfo['money'] - $param['cz_money'],
                'created_at' => time()

            ];
            $r2 = $userLog->insertUserLog($list);

            $user = new UserModel();
            $this->userInfo['money'] -= $param['cz_money'];
            $this->userInfo['fro_money'] += $param['cz_money'];
            $r3 = $user->editUser($this->userInfo);
            //添加提现日志
            $moneyLog = new MoneyLogModel();
            unset($param['cz_moneys']);
            $param['cz_uid'] = $this->userInfo['id'];
            $param['cz_type'] = 2;
            $param['cz_money'] = $param['cz_money'] - ($param['cz_money'] * $fee);
            $param['created_at'] = time();
            $r1 = $moneyLog->insertMoneyLog($param);
            //返回值判断
            if ($r1['code'] == 1 && $r2['code'] == 1 && $r3['code'] == 1){
                Db::commit();
                return json(['code' => 1, 'data' => array(), 'msg' => '提现申请成功']);

            }
            Db::rollback();
            return json(['code' => 1, 'data' => array(), 'msg' => '提现申请失败']);
        }
    }

    /**
     * 资产明细日志列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assetDetail(){

        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];

            $where['ug_uid'] = $this->userInfo['id'];
            //日志状态
            if (isset($param['status']) && !empty($param['status'])) {
                $where['ug_status'] = ['like', '%' . $param['status'] . '%'];
            }
            //开始时间  --- 结束时间
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $userLog= new UserLogModel();
            $user = new UserModel();
            $selectResult = $userLog->getUserLogByWhere($where, $offset, $limit);

            $status = config('log_status');
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['created_at'] = date('Y-m-d H:i:s', $vo['created_at']);
                $selectResult[$key]['ug_status'] = $status[$vo['ug_status']];
                $selectResult[$key]['ug_uid'] = $user->getOneUser($vo['ug_uid'])['username'];

            }
            $return['total'] = $userLog->getAllUserLogCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch('assetDetail');
    }


    /**
     * 验证交易密码
     * @return \think\response\Json
     */
    public function getSavePass(){

        if(request()->isAjax()){
            $pass = input('post.pass');
            //如果都为空的情况下
            if (empty($pass) && empty($this->userInfo['pay_pass'])){
                return json(['code' => 1, 'data' => array(), 'msg' => '验证成功']);
            }
            //请输入交易密码
            if (empty($pass)){

                return json(['code' => -1, 'data' => array(), 'msg' => '请输入二级密码']);
            }
            //判断交易密码
            if (md5($pass) != $this->userInfo['pay_pass']){

                return json(['code' => -1, 'data' => array(), 'msg' => '二级密码不正确']);
            }

            return json(['code' => 1, 'data' => array(), 'msg' => '验证成功']);
        }
    }

}
