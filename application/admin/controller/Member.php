<?php
namespace app\admin\controller;
use think\Request;
use app\admin\model\MemberModel;
class Member extends Base {
	
	/*
	 * 会员列表
	 */
	public function index() {
		$Member = new MemberModel();
        //获取数据
        $param = [
            'type' => 'type',
            'nickname' => 'nickname',
            'phone' => 'phone',
            'start_time' => 'start_time',
            'end_time' => 'end_time',
        ];
        $param_data = $this->buildParam($param);
		$start_time = strtotime($param_data['start_time']);
        $end_time = strtotime($param_data['end_time']);
		$where = '';
		$pagdata = '';
		if(!empty($type)){
			if($type != 3){
				$where['type'] =$type;
			}else{
				$where['on_line'] = '1';
				$pagdata ['query']['type'] = 3;
			}
		}
		if(!empty($phone)){
			$where['phone'] =$param_data['phone'];
		}
		if(!empty($full_name)){
			$where['nick_name'] = $param_data['nick_name'];
		}
		if(!empty($start_time) && !empty($end_time)){
            $where['add_time'] = array('between',array("$start_time","$end_time"));
        }
		$list = $Member ->getMemberPage($where,$pagdata);
        //分页
        $page = $list->render();
        $list = $list->all();
        $mem_num = $Member ->getAllUsers('');
        $for_num = $Member ->getAllUsers(['status'=>1]);
        $thrw_num = $Member ->getAllUsers(['status'=>2]);
		//输出信息
        $this ->assign('mem_num',$mem_num);
        $this ->assign('for_num',$for_num);
        $this ->assign('thrw_num',$thrw_num);
		$this ->assign('data',$list);
		$this ->assign('page',$page);
		// 渲染模板输出
		return $this->fetch ();
	}

	/*
	 * 添加会员信息
	 */
	public function add(){

		if(Request::instance()->isPost()){
			//获取数据
            $param = [
                'nick_name' => 'nick_name',
                'user_name' => 'user_name',
                'phone' => 'phone',
                'pass_word' => 'pass_word',
                'status' => 'status',
            ];
            $param_data = $this->buildParam($param);
            $param_data['title_pic'] = '/public/uploads/logo.png';
            $param_data['reg_time'] = time();
            $param_data['pass_word'] = md5($param_data['pass_word']);
			$Member = new MemberModel();
			if(empty($param_data['pass_word'])){
				$this->error ( '用户密码不能为空！' );
			}
			if(empty($param_data['phone'])){
				$this->error ( '用户手机号不能为空！' );
			}
			//获取当前手机号信息
			$member_phone = $Member ->getMemberOneByField('phone', $param_data['phone']);
			if(!empty($member_phone)){
				$this->error ( '该手机号已存在！请核实！');
			}
			//添加学员
			$re = $Member ->insertMember($param_data);
			if(!$re){
				$this ->error('添加失败');exit();
			}
			$this ->success('添加成功','index');exit();
		}
		return $this->fetch ();
	}

	/*
	 * 修改会员信息
	 */
	public function edit(){
        $Member = new MemberModel();
        $user_id = Request::instance()->param('user_id');
		if(Request::instance()->isPost()){
            //获取数据
            $param = [
                'nickname' => 'nickname',
                'username' => 'username',
                'email' => 'email',
                'phone' => 'phone',
                'password' => 'password',
            ];
            $param_data = $this->buildParam($param);

			if(empty($user_id)){
				$this->error ( '会员id不能为空！' );
			}
			if(!regex('phone', $param_data['phone'])){
				$this->error ( '手机号不正确！' );
			}
			//获取当前手机号信息
			$member = $Member ->getMemberOneByField('phone', $param_data['phone']);
			if(!empty($member) && $member['user_id'] != $user_id){
				$this->error ( '该手机号已存在！请核实！' );
			}
			if(empty($param_data['password'])){
			    //没有则没有修改
                unset($param_data['password']);
			}
            $param_data['password'] = md5($param_data['password']);
            $re = $Member ->updata((int)$user_id, $param_data);
			if(!$re){
				$this ->error('修改失败');exit();
			}
			$this ->success('修改成功','index');exit();
		}
		$member = $Member ->getMemberOne($user_id);
		$this ->assign('member',$member);
		return $this->fetch ();
	}
	
	/*
	 * 冻结会员
	 */
	public function frozenMember(){
		$user_id = Request::instance()->param('user_id');
		$re = $this ->HandleMember($user_id,2);
		if($re['status'] != 1){
			$this ->error($re['msg']);
		}
		$this -> success($re['msg']);
	}
	
	/*
	 * 解冻会员冻会员
	 */
	public function thawMember(){
        $user_id = Request::instance()->param('user_id');
		$re = $this ->HandleMember($user_id,1);
		if($re['status'] != 1){
			$this ->error($re['msg']);
		}
		$this -> success($re['msg']);
	}

    /*
     * 处理冻结解冻会员
     * @param unknown $student_id 	学员id
     * @param unknown $status		状态
     * @return array 	状态信息
     */
    protected  function HandleMember($user_id,$status){
        $Member = new MemberModel();
        if(empty($user_id)){
            $data['status'] = -1;
            $data['msg'] = '请传入会员ID！';
            return $data;
        }
        //查询当前学员信息
        $member = $Member ->getMemberOne((int)$user_id);
        if(empty($member)){
            $data['status'] = -2;
            $data['msg'] = '会员ID不正确！';
            return $data;
        }
        $data['status'] = $status;
        $re = $Member ->updata((int)$user_id, $data);
        //判断修改状态
        if(!$re){
            $data['status'] = -3;
            $data['msg'] = '修改会员信息失败！';
            return $data;
        }
        $data['status'] = 4;
        $data['msg'] = '修改会员信息成功！';
        return $data;
    }
}
