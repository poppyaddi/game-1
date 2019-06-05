<?php
namespace app\admin\controller;

use app\admin\model\NodeModel;
use app\admin\model\UserTypeModel;

class Role extends Base
{

    /**
     * 角色列表
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
            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['rolename'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $user = new UserTypeModel();
            $selectResult = $user->getRoleByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){
                $operate = [
                    '编辑' => url('role/roleEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:roleDel('".$vo['id']."')",
                    '分配权限' => "javascript:giveQx('".$vo['id']."')"
                ];
                $selectResult[$key]['operate'] = showOperate($operate);

            }
            $return['total'] = $user->getAllRole($where);  //总数据
            $return['rows'] = $selectResult;
            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 添加角色
     * @return mixed|\think\response\Json
     */
    public function roleAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);

            $role = new UserTypeModel();
            $flag = $role->insertRole($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        return $this->fetch();
    }

    /**
     * 编辑角色
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function roleEdit()
    {
        $role = new UserTypeModel();

        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);

            $flag = $role->editRole($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $this->assign([
            'role' => $role->getOneRole($id)
        ]);
        return $this->fetch();
    }

    /**
     * 删除角色
     * @return \think\response\Json
     */
    public function roleDel()
    {
        $id = input('param.id');

        $role = new UserTypeModel();
        $flag = $role->delRole($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**
     * 分配权限
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function giveAccess()
    {
        $param = input('param.');
        $node = new NodeModel();
        //获取现在的权限
        if('get' == $param['type']){ 
            $nodeStr = $node->getNodeInfo($param['id']); 
            return json(['code' => 1, 'data' => $nodeStr, 'msg' => 'success']);
        }
        //分配新权限
        if('give' == $param['type']){

            $doparam = [
                'id' => $param['id'],
                'rule' => $param['rule']
            ];
            $user = new UserTypeModel();
            $flag = $user->editAccess($doparam);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
    }
}