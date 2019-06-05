<?php
namespace app\admin\controller;
use think\Db;
use app\admin\model\UserModel;
use app\admin\model\UserLogModel;
use app\admin\model\MoneyLogModel;

class Money extends Base
{
    /**
     * 充值列表
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
            //查询条件
            $where = [];
            //用户昵称
            if (isset($param['userName']) && !empty($param['userName'])) {
                $why['username'] = ['like', '%' . $param['userName'] . '%'];
                $user = new UserModel();
                $where['cz_uid'] = $user->getUsersByField($why)['id'];

            }
            //审核状态
            if (isset($param['status']) && !empty($param['status'])) {
                $where['cz_status'] = ['like', '%' . $param['status'] . '%'];
            }
            //操作类型
            if (isset($param['type']) && !empty($param['type'])) {
                $where['cz_type'] = ['like', '%' . $param['type'] . '%'];
            }
            //打款账户
            if (isset($param['realName']) && !empty($param['realName'])) {
                $where['cz_real_name'] = ['like', '%' . $param['realName'] . '%'];
            }
            //打款号码
            if (isset($param['aliNumber']) && !empty($param['aliNumber'])) {
                $where['cz_ali_number'] = ['like', '%' . $param['aliNumber'] . '%'];
            }
            //开始时间  --- 结束时间
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $MoneyLog = new MoneyLogModel();
            $user = new UserModel();
            $selectResult = $MoneyLog->getMoneyLogByWhere($where, $offset, $limit);

            $status = config('money_status');
            $type = config('money_type');
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['created_at'] = date('Y-m-d H:i:s', $vo['created_at']);
                $selectResult[$key]['cz_status'] = $status[$vo['cz_status']];
                $selectResult[$key]['cz_type'] = $type[$vo['cz_type']];
                $selectResult[$key]['cz_uid'] = db('user')->where(['id'=>$vo['cz_uid']])->find()['username'];
                //按钮筛选
                if ($vo['cz_status'] != '等待审核'){
                    $selectResult[$key]['operate'] = <<<EOT
    <div class="btn-group">
        <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false" disabled>
            操作 <span class="caret"></span>
        </button>
    </div>
EOT;
                }else{
                    $operate = [
                        '通过审核' => "javascript:passAudit('".$vo['cz_id']."')",
                        '拒绝审核' => "javascript:refuseAudit('".$vo['cz_id']."')",
                    ];
                    $selectResult[$key]['operate'] = showOperate($operate);
                }



            }
            $return['total'] = $MoneyLog->getAllMoneyLogCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 充值审核通过
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function passAudit(){
        //实例化模型
        $moneyLog = new MoneyLogModel();
        $user = new UserModel();
        $userLog = new UserLogModel();
        //获取参数
        $param['cz_id'] = input('param.cz_id');
        //获取当前充值LOG
        $data = $moneyLog->getOneMoneyLog($param['cz_id']);
        //获取个人信息
        $info = $user->getOneUser($data['cz_uid']);
        $param['cz_status'] = 2;
        $param['updated_at'] = time();
        //事务开启
        Db::startTrans();
        $r1 = $moneyLog->editMoneyLog($param);
        //添加用户日志
        $list = [
            'ug_uid'     => $data['cz_uid'],
            'ug_status'  => 1 ,
            'ug_money'   => $data['cz_money'],
            'ug_cgmoney' => $data['cz_money'] + $info['money'],
            'created_at' => time()

        ];
        //类型为提现更新状态
        if ($data['cz_type'] == 1){

            $r2 = $user->setUserInc($data['cz_uid'],'money',$data['cz_money']);

        }else{

            $r2 = $user->setUserDec($data['cz_uid'],'fro_money',$data['cz_money']);
            $list['ug_money'] = 0;
            $list['ug_status'] = 2;
            $list['ug_cgmoney'] = $info['money'];
        }

        $r3 = $userLog->insertUserLog($list);
        //判断返回值
        if ($r1['code'] == 1 && !empty($r2) && $r3['code'] == 1){
            Db::commit();
            $info = ['code' => 1, 'data' => array(), 'msg' => '操作成功'];
        }else{
            Db::rollback();
            $info = ['code' => -1, 'data' => array(), 'msg' => '操作失败'];
        }
        return json($info);

    }


    /**
     * 充值审核拒绝
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refuseAudit(){
        //实例化模型
        $moneyLog = new MoneyLogModel();
        $user = new UserModel();
        $userLog = new UserLogModel();
        //获取参数
        $param['cz_id'] = input('param.cz_id');
        $content = input('param.content');

        //获取当前充值LOG
        $data = $moneyLog->getOneMoneyLog($param['cz_id']);
        $param['cz_status'] = 3;
        $param['updated_at'] = time();
        //事务开启
        Db::startTrans();
        $r1 = $moneyLog->editMoneyLog($param);
        //获取个人信息
        $info = $user->getOneUser($data['cz_uid']);
        //添加用户日志
        $list = [
            'ug_uid'     => $data['cz_uid'],
            'ug_status'  => 7 ,
            'ug_money'   => 0,
            'ug_cgmoney' => $info['money'],
            'ug_content' => $content,
            'created_at' => time()

        ];
        //类型为提现更新状态
        if ($data['cz_type'] == 1) {
            //TODO 充值被拒不用操作用户资产  后期有什么变化再改。。
            $r2['code'] = 1;
            $r3['code'] = 1;
        }else{

            $list['ug_status'] = 8;
            $list['ug_money'] = $data['cz_money'];
            $list['ug_cgmoney'] = $info['money'] + $data['cz_money'];
            $r2 = $user->setUserDec((int)$data['cz_uid'],'fro_money',$data['cz_money']);
            $r3 = $user->setUserInc((int)$data['cz_uid'],'money',$data['cz_money']);
        }

        $r4 = $userLog->insertUserLog($list);
        //判断返回值
        if ($r1['code'] == 1 && !empty($r2) && !empty($r3) && $r4['code'] == 1){
            Db::commit();
            $info = ['code' => 1, 'data' => array(), 'msg' => '操作成功'];
        }else{
            Db::rollback();
            $info = ['code' => -1, 'data' => array(), 'msg' => '操作失败'];
        }
        return json($info);

    }

    /**
     * 导出财务信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function moneyExcel(){
        $Money = new MoneyLogModel();
        $User = new UserModel();
        $list = $Money->getMoneyToExcel('');

        $title = [
            '编号ID',
            '用户昵称',
            '操作金额',
            '打款账户',
            '打款号码',
            '操作类型',
            '最新状态',
            '添加时间',
        ];
        $data = [];
        foreach ($list as $k => $v){
            $data[$k][] = $v['cz_id'];
            $data[$k][] = $User->getUsersByField(['id'=>$v['cz_uid']])['username'];
            $data[$k][] = $v['cz_money'];
            $data[$k][] = $v['cz_real_name'];
            $data[$k][] = $v['cz_ali_number'];
            if ($v['cz_type'] == 1){
                $data[$k][] = '充值';
            }else{
                $data[$k][] = '提现';
            }
            if ($v['cz_status'] == 1){
                $data[$k][] = '等待审核';
            }elseif ($v['cz_status'] == 2){
                $data[$k][] = '审核通过';
            }else{
                $data[$k][] = '审核被拒';
            }

            $data[$k][] = date('Y-m-d H:i:s',$v['created_at']);
        }

        $this->exportExcel($data,$title,'财务日志'.date('Y-m-d H:i:s',time()));
    }
}
