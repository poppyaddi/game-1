<?php
namespace app\admin\controller;
use app\admin\model\GamesModel;

class Games extends Base
{
    /**
     * 游戏列表
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
            if (isset($param['gamesName']) && !empty($param['gamesName'])) {
                $where['gs_name'] = ['like', '%' . $param['gamesName'] . '%'];
            }
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $games = new GamesModel();
            $selectResult = $games->getGamesByWhere($where, $offset, $limit);

            $status = config('role_status');
            foreach($selectResult as $key=>$vo){
//                $selectResult[$key]['created_at'] = date('Y-m-d H:i:s', $vo['created_at']);
                $selectResult[$key]['gs_status'] = $status[$vo['gs_status']];
                $operate = [
                    '编辑游戏' => url('Games/gamesEdit', ['gs_id' => $vo['gs_id']]),
                    '删除游戏' => "javascript:gamesDel('".$vo['gs_id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $games->getAllGamesCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 添加游戏
     * @return mixed|\think\response\Json
     */
    public function gamesAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);
            $param['created_at'] = time();
            $param['updated_at'] = '';
            $games = new GamesModel();
            $info = db('games')->where(['productIdentifier'=>$param['productIdentifier']])->find();
            if (!empty($info)){
                return json(['code' => -1, 'data' => array(), 'msg' => '游戏包名已存在']);
            }
            $flag = $games->insertGames($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $this->assign([
            'status'    => config('role_status'),
            'receipt_type' => config('receipt_type'),
        ]);

        return $this->fetch();
    }

    /**
     * 编辑游戏
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gamesEdit()
    {
        $games = new GamesModel();

        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);
            $param['updated_at'] = time();
            $flag = $games->editGames($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.gs_id');

        $this->assign([
            'games' => $games->getOneGames($id),
            'status' => config('role_status'),
            'receipt_type' => config('receipt_type'),
        ]);

        return $this->fetch();
    }

    /**
     * 删除游戏
     * @return \think\response\Json
     */
    public function gamesDel()
    {
        $id = input('param.gs_id');
        $games = new GamesModel();
        $flag = $games->delGames((int)$id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}
