<?php
namespace app\admin\controller;

use app\admin\model\HelpModel;

class Help extends Base
{
    /**
     * 帮助列表
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
            if (isset($param['title']) && !empty($param['title'])) {
                $where['title'] = ['like', '%' . $param['title'] . '%'];
            }
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $Help = new HelpModel();
            $selectResult = $Help->getHelpsByWhere($where, $offset, $limit);
            foreach($selectResult as $key=>$vo){

                $operate = [
                    '编辑' => url('Help/helpEdit', ['hp_id' => $vo['hp_id']]),
                    '删除' => "javascript:helpDel('".$vo['hp_id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $Help->getAllHelps($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 添加帮助
     * @return mixed|\think\response\Json
     */
    public function helpAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);
            $param['created_at'] = time();
            $Help = new HelpModel();
            $flag = $Help->insertHelp($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $this->assign([
            'status' => config('role_status')
        ]);
        return $this->fetch();
    }

    /**
     * 编辑帮助
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function helpEdit()
    {
        $Help = new HelpModel();

        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);
            $param['updated_at'] = time();
            $flag = $Help->editHelp($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.hp_id');
        $this->assign([
            'help' => $Help->getOneHelp($id),
            'status' => config('role_status')
        ]);
        return $this->fetch();
    }

    /**
     * 帮助删除
     * @return \think\response\Json
     */
    public function helpDel()
    {
        $id = input('param.hp_id');
        $role = new HelpModel();
        $flag = $role->delHelp($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
