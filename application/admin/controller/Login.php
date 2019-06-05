<?php
namespace app\admin\controller;
use think\Controller;
use app\admin\model\UserTypeModel;

class Login extends Controller
{
    /**
     * 登录页面
     * @return mixed
     */
    public function index()
    {
        return $this->fetch('/login');
    }

    /**
     * 登录操作
     * @return \think\response\Json
     */
    public function doLogin()
    {
        $username = input("param.username");
        $password = input("param.password");
        $code = input("param.code");
        $ip = request()->ip();

        //添加登录日志
        $params = [
            'ip_address' => request()->ip(),
            'login_time' => time(),
        ];

        //验证验证码
        if(!captcha_check($code)){

            $params['content'] = "用户".$username."登录,验证码错误";
            db('adminlog')->insertGetId($params);
            return json(['code' => -4, 'data' => '', 'msg' => '验证码错误']);
        };

        //手机号和用户名登录
        $map = [
            'username|phone|email'=> $username,
        ];

        $hasUser  = db('user')->where($map)->find();

        //判断用户是否存在
        if(empty($hasUser)){
            $params['content'] = "用户".$username."登录,用户不存在";
            db('adminlog')->insertGetId($params);

            return json(['code' => -1, 'data' => '', 'msg' => '用户不存在']);
        }

        //判断密码是否正确
      //echo md5($password);
        if(md5($password) != $hasUser['password']){
            //密码错误的时候记录IP 防止暴力破解

            $info = $this->ipaddrlog($ip,$username);

            if ($info['code'] == -1)  return json($info);

            $params['content'] = "用户".$username."登录,密码错误";
            db('adminlog')->insertGetId($params);

            return json(['code' => -2, 'data' => '', 'msg' => '密码错误']);
        }

        //判断账号状态
        if(1 != $hasUser['status']){

            $params['content'] = "用户".$username."登录,该账号被禁用";
            db('adminlog')->insertGetId($params);

            return json(['code' => -6, 'data' => '', 'msg' => '该账号被禁用']);
        }

        //判断防止暴力破解
        if (1 != $hasUser['id']){
            $data = db('ipaddrlog')->where(['is_ip'=>$ip])->find();
            if (!empty($data['end_time'])){
                if ($data['end_time'] - time() > 0 ){

                    $params['content'] = "用户".$username."登录,密码输入错误次数太多,2小时后再试";
                    db('adminlog')->insertGetId($params);

                    return json(['code' => -3, 'data' => '', 'msg' => '密码输入错误次数太多,请2小时后再试']);
                }
            }
        }

        //判断账号到期时间
        if (1 != $hasUser['id']){
            if (strtotime($hasUser['end_time']) - time() < 0 ){

                $params['content'] = "用户".$username."登录,账号已过期";
                db('adminlog')->insertGetId($params);
                return json(['code' => -5, 'data' => '', 'msg' => '您的账号已过期，请联系管理员充值']);
            }
        }

        //获取该管理员的角色信息
        $user = new UserTypeModel();
        $info = $user->getRoleInfo($hasUser['role_id']);
        session('username', $hasUser['username']);
        session('id', $hasUser['id']);
        session('role', $info['rolename']);  //角色名
        session('rule', $info['rule']);  //角色节点
        session('action', $info['action']);  //角色权限
        session('USER_KEY_ID',$hasUser['id']);

        //更新管理员状态
        $param = [
            'loginnum' => $hasUser['loginnum'] + 1,
            'last_login_ip' => request()->ip(),
            'last_login_time' => time(),
            'session_id' => session_id()
        ];

        db('user')->where('id', $hasUser['id'])->update($param);


        $params['content'] = "用户".$username."登录,成功登录";
        db('adminlog')->insertGetId($params);

        //密码错误次数小于5次  登录成功删除暴力日志
        $r = db('ipaddrlog')->where(['is_ip'=>request()->ip(),'username'=>$username])->find();

        if (!empty($r))  db('ipaddrlog')->where(['is_id'=>$r['is_id']])->delete();



        return json(['code' => 1, 'data' => url('index/index'), 'msg' => '登录成功']);
    }

    /**
     * 退出操作
     * @return mixed
     */
    public function loginOut()
    {
        session('username', null);
        session('id', null);
        session('role', null);  //角色名
        session('rule', null);  //角色节点
        session('action', null);  //角色权限

        $this->redirect(url('index'));
    }

    /**
     * 密码错误记录IP
     * 防止暴力破解
     * @param $ip
     * @param $username
     * @return array
     */
    protected function ipaddrlog($ip,$username){

        $data = db('ipaddrlog')->where(['is_ip'=>$ip])->find();

        //判断有没有值
        if (!empty($data)){
            //到期时间有值证明
            if (!empty($data['end_time'])){
                //封号时间 - 当前时间
               if ($data['end_time'] - time() > 0){

                   return ['code'=> -1 , 'msg'=>'密码输入错误次数太多,请2小时后再试', 'data'=>array()];

                }else{

                   db('ipaddrlog')->where(['is_ip'=>$ip])->delete();

                   return ['code'=> 1 , 'msg'=>'添加成功', 'data'=>array()];
               }
            }
            //密码错误大于5次的时候封号2小时
            if ($data['is_num'] > 4){
                //封号两小时
                $time = time() + 7200;
                db('ipaddrlog')->where(['is_ip' => $ip])->update(['end_time'=>$time]);

                return ['code'=> -1 , 'msg'=>'密码输入错误次数太多,请2小时后再试', 'data'=>array()];
            }
            db('ipaddrlog')->where(['is_ip'=>$ip])->setInc('is_num',1);
        }else{

            db('ipaddrlog')->insertGetId(['is_ip'=>$ip,'is_num'=>1,'add_time'=>time(),'username'=>$username]);

            return ['code'=> 1 , 'msg'=>'添加成功', 'data'=>array()];
        }

    }

    /**
     * 首次登陆WEB页
     * @return mixed
     */
    public function protocol(){
        $list = db('config')->where(['key'=>'protocol'])->find()['value'];
        //输出信息
        $this ->assign('data',$list);
        // 渲染模板输出
        return $this->fetch('index/firstWeb');
    }
}