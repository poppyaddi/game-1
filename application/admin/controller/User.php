<?php
namespace app\admin\controller;
use app\admin\model\UserModel;
use app\admin\model\UserTypeModel;

class User extends Base
{
    /**
     * 用户列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if(request()->isAjax())
        {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            if (isset($param['status']))
            {
                if ($param['status'] == 'enabled')
                {
                    $where['status'] = 1;
                }
                else if ($param['status'] == 'disabled') {
                    $where['status'] = 0;
                }
                else {

                }
            }
            else {
                $where['status'] = 1;
            }

            if (isset($param['userName']) && !empty($param['userName']))
            {
                $where['username'] = ['like', '%' . $param['userName'] . '%'];
            }
            if (isset($param['phone']) && !empty($param['phone'])) {
                $where['phone'] = $param['phone'];
            }
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['add_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $user = new UserModel();
            $selectResult = $user->getUsersByWhere($where, $offset, $limit);

            $status = config('user_status');
            $sex = config('sex_status');
            foreach($selectResult as $key=>$vo){
                $operate = [];
                $operate['编辑'] = url('user/userEdit', ['id' => $vo['id']]);
                if ($vo['status'] == 1)
                {
                    $operate['禁用'] = "javascript:userDel('".$vo['id']."')";

                }else {
                    $operate['启用'] = "javascript:userAct('".$vo['id']."')";
                }
              	$operate['删除'] = "javascript:del('".$vo['id']."')";

                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['end_time'] = $vo['end_time'];
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['sex'] = $sex[$vo['sex']];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['rows'] = $selectResult;
            $return['total'] = $user->getAllUsersCount($where);  //总数据
            $return['mem_num'] = $user->getAllUsersCount('');
            $return['for_num'] = $user->getAllUsersCount(['status'=>1]);
            $return['thr_num'] = $user->getAllUsersCount(['status'=>2]);

            return json($return);
        }

        $this->assign('status', !empty($where['status']) ? $where['status'] : '');

        return $this->fetch();
    }

    /**
     * 添加用户
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userAdd()
    {
        $user = new UserModel();

        if(request()->isPost())
        {
            $param = input('param.');
            $param = parseParams($param['data']);

            $param['password'] = md5($param['password']);
            $param['pay_pass'] = md5($param['pay_pass']);
            $param['add_time'] = time();
            $param['end_time'] = strtotime($param['end_time']);
            $param['end_time'] = date('Y-m-d H:i:s', $param['end_time']);

            if (!$param['role_id'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '必须选择团队']);
            }

            if (!$param['password'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '登录密码不能为空']);
            }

            if (!$param['pay_pass'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '交易密码不能为空']);
            }

            if (!$param['end_time'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择截止时间']);
            }

            if (!$param['end_time'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择截止时间']);
            }

            $flag = $user->insertUser($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $role = new UserTypeModel();
        $this->assign([
            'end_time'=> date('Y-m-d\TH:i:s', time() +86400 *30),
            'role' => $role->getRole(),
            'status' => config('user_status'),
            'sex' => config('sex_status')
        ]);

        return $this->fetch();
    }

    /**
     * 编辑角色
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userEdit()
    {
        $user = new UserModel();

        if(request()->isPost())
        {
            $param = input('post.');
            $param = parseParams($param['data']);
            $param['end_time'] = strtotime($param['end_time']);
            $param['end_time'] = date('Y-m-d H:i:s', $param['end_time']);

            if (!$param['role_id'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '必须选择团队']);
            }

            if (!$param['end_time'])
            {
                return json(['code' => -1, 'data' => array(), 'msg' => '请选择截止时间']);
            }

            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5($param['password']);
            }

            if(empty($param['pay_pass'])){
                unset($param['pay_pass']);
            }else{
                $param['pay_pass'] = md5($param['pay_pass']);
            }
            $flag = $user->editUser($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');
        $role = new UserTypeModel();

        $user = $user->getOneUser($id);

        $user['end_time'] = strtotime($user['end_time']);
        $user['end_time'] = date('Y-m-d\TH:i:s', $user['end_time']);

        $this->assign([
            'user' => $user,
            'status' => config('user_status'),
            'sex' => config('sex_status'),
            'role' => $role->getRole()
        ]);
        return $this->fetch();
    }
  
  	public function delopt(){
        $id = input('param.id');
        $games = new UserModel();
        $flag = $games->delUser((int)$id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * 删除角色
     * @return \think\response\Json
     */
    public function userDel()
    {
        $id = input('param.id');
        $role = new UserModel();
        $role->where(['id'=> $id])->update(['status'=> 0]);
        return json(['code' => 1, 'data' => '', 'msg' => '禁用成功']);
    }

    /**
     * 删除角色
     * @return \think\response\Json
     */
    public function userAct()
    {
        $id = input('param.id');
        $role = new UserModel();
        $role->where(['id'=> $id])->update(['status'=> 1]);
        return json(['code' => 1, 'data' => '', 'msg' => '启用成功']);
    }

    /**
     * 导出用户信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userExcel(){
        $User = new UserModel();
        $list = $User->getUsersToExcel('');
        $title = [
            '用户ID',
            '用户昵称',
            '手机号码',
            '真实姓名',
            '身份证号',
            '所属团队',
            '登陆次数',
            '用户资产',
            '冻结资产',
            '会员状态',
            '上次登录IP',
            '上次登录时间',
            '到期时间',
        ];
        $data = [];
        $status = config('role_status');
        foreach ($list as $k => $v){
            $data[$k][] = $v['id'];
            $data[$k][] = $v['username'];
            $data[$k][] = $v['phone'];
            $data[$k][] = $v['really_name'];
            $data[$k][] = $v['idcard'];
            $data[$k][] = $v['rolename'];
            $data[$k][] = $v['loginnum'];
            $data[$k][] = $v['money'];
            $data[$k][] = $v['fro_money'];
            $data[$k][] = $status[$v['status']];
            $data[$k][] = $v['last_login_ip'];
            $data[$k][] = date('Y-m-d H:i:s',$v['last_login_time']);
            $data[$k][] = $v['end_time'];
        }

        $this->exportExcel($data,$title,'会员信息'.date('Y-m-d H:i:s',time()));
    }

    /**
     * 用户日志
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userLog()
    {

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

            //开始日期   --- 结束日期
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }

            $selectResult = db('adminlog')->where($where)->limit($offset,$limit)->order('login_time DESC')->select();

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['login_time'] = date('Y-m-d H:i:s',$vo['login_time']);

            }
            $return['total'] = db('adminlog')->where($where)->count();  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch('user/userLog');
    }

}
