<?php
namespace app\admin\controller;
use app\admin\model\PhoneModel;
use app\admin\model\TokenModel;
use app\admin\model\UserModel;

class Phone extends Base
{
    /**
     * Token列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if(request()->isAjax())
        {
            $user = new UserModel();
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            $where['status'] = 1;
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //token查询
            if (isset($param['phoneName']) && !empty($param['phoneName'])) {
                $where['token'] = ['like', '%' . $param['phoneName'] . '%'];
            }
            //用户查询
            if (isset($param['userName']) && !empty($param['userName'])) {
                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;

                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }
            }

            //开始时间  --- 结束时间
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['create_time'] = array('between',array($param['startTime'],$param['endTime']));
            }
            $token = new TokenModel();
            $selectResult = $token->getTokenByWhere($where, $offset, $limit);

            $status = config('token_status');
            foreach($selectResult as $key => $vo){

                $selectResult[$key]['status'] = $status[$vo['status']];
                $operate = [
                    '退出登录' => "javascript:saveToken('".$vo['id']."')",
                ];
                $selectResult[$key]['operate'] = showOperate($operate);

            }
            $return['total'] = $token->getAllToken($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 手机列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function phone()
    {
        if(request()->isAjax())
        {
            $user = new UserModel();
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            //管理员能查看全部 别人看见自己的
            if (session('role') != '管理员'){
                $where['user_id'] = $this->userInfo['id'];
            }
            //token查询
            if (isset($param['phoneName']) && !empty($param['phoneName'])) {
                $where['token'] = ['like', '%' . $param['phoneName'] . '%'];
            }
            //用户查询
            if (isset($param['userName']) && !empty($param['userName'])) {
                if (session('role') == '管理员'){
                    $userName['username'] = ['like', '%' . $param['userName'] . '%'];
                    $where['user_id'] = $user ->getUsersByField($userName)['id'] ;

                }else{
                    $where['user_id'] = $this->userInfo['id'];
                }

            }

            //开始时间  --- 结束时间
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['create_time'] = array('between',array($param['startTime'],$param['endTime']));
            }
            $phone = new phoneModel();
            $selectResult = $phone->getPhoneByWhere($where, $offset, $limit);

            $status = config('phone_status');
            foreach($selectResult as $key=>$vo)
            {
                $selectResult[$key]['status'] = $status[$vo['status']];

                if ($vo['status'] == '正常使用'){
                    $operate = [
                        '禁用设备' => "javascript:saveStatus('".$vo['id']."', '" . $vo['status'] . "')",
                    ];
                }else{
                    $operate = [
                        '启用设备' => "javascript:saveStatus('".$vo['id']."', '". $vo['status'] ."')",
                    ];
                }
				$operate['删除'] = "javascript:del('".$vo['id']."')";
                  
                $selectResult[$key]['user_id'] = $user->getUsersByField(['id' => $vo['user_id']])['username'];

                $selectResult[$key]['operate'] = showOperate($operate);

            }
            $return['total'] = $phone->getAllphone($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
  
  	public function delopt(){
        $id = input('param.id');
        $games = new PhoneModel();
        $flag = $games->delPhone((int)$id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * 禁用设备
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveStatus()
    {
        $param['id'] = input('param.id');
        $pass = input('param.pass');
        if (!$this->userInfo['pay_pass']){
            return json(['code' => -1, 'data' => array(), 'msg' => '您当前没有二级密码请修改二级密码']);
        }
        if (md5($pass) != $this->userInfo['pay_pass']){
            return json(['code' => -1, 'data' => array(), 'msg' => '二级密码错误']);
        }
        $phone = new phoneModel();
        $arr = $phone->getOnePhone($param['id'])['status'];
        if ($arr == 0){
            $param['status'] = 1;
        }else {
            $param['status'] = 0;
        }

        $flag = $phone->editPhone($param);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * 禁用Token
     * @return \think\response\Json
     */
    public function saveToken()
    {
        $param['id'] = input('param.id');
        $pass = input('param.pass');
        if (!$this->userInfo['pay_pass']){
            return json(['code' => -1, 'data' => array(), 'msg' => '您当前没有二级密码请修改二级密码']);
        }
        if (md5($pass) != $this->userInfo['pay_pass']){
            return json(['code' => -1, 'data' => array(), 'msg' => '二级密码错误']);
        }
        $param['status'] = 0;
        $token = new TokenModel();
        $flag = $token->editToken($param);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }


}
