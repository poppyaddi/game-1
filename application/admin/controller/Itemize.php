<?php
namespace app\admin\controller;
use app\admin\model\GamesModel;
use app\admin\model\ItemizeModel;

class Itemize extends Base
{
    /**
     * 面值列表
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
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            //查询条件
            $where = [];
            //游戏ID
            if (isset($param['gamesId']) && !empty($param['gamesId'])) {
                $where['gs_id'] = $param['gamesId'];
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

                    $where['gs_id'] = ['in', $gs_id];
                }
                else {
                    $where['gs_id'] = ['lt', 0];
                }
            }
            //面值名称
            if (isset($param['itemizeName']) && !empty($param['itemizeName'])) {
                $where['gold'] = $param['itemizeName'];
            }
          	//标识
          	if (isset($param['title']) && !empty($param['title'])) {
                $where['title'] = $param['title'];
            }

            //开始时间  -- 结束时间
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['create_time'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $itemize = new ItemizeModel();
            $selectResult = $itemize->getItemizeByWhere($where, $offset, $limit);

            $status = config('role_status');
            foreach($selectResult as $key=>$vo)
            {
                $game = $games->getOneGames($vo['gs_id']);
                $selectResult[$key]['gs_name'] = !empty($game['gs_name']) ? $game['gs_name'] : '--';
                $selectResult[$key]['status'] = $status[$vo['status']];
                $operate = [
                    '编辑面值' => url('itemize/itemizeEdit', ['id' => $vo['id']]),
                    '删除面值' => "javascript:itemizeDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $itemize->getAllItemizeCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        $this->assign([
            'games' => $games->getAllGames(''),
        ]);

        return $this->fetch();
    }

    /**
     * 添加面值
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function itemizeAdd()
    {
        $games = new GamesModel();
        if(request()->isPost()){

            $param = input('param.');
          
            $param = parseParams($param['data']);
            $param['gs_id']=db('games')->where(['gs_name'=>$param['gs_id']])->find()['gs_id'];
         // print_r($param['gs_id']);exit;
           $itemize = new itemizeModel();
           $info = db('games_price')->where(['title'=>$param['title']])->find();
            if (!empty($info)){
                return json(['code' => -1, 'data' => array(), 'msg' => '面值标识已存在']);
            }
            $flag = $itemize->insertitemize($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
            
            
        }

        $this->assign([
            'status'    => config('role_status'),
            'games' => $games->getAllGames(''),
        ]);

        return $this->fetch();
    }

    /**
     * 编辑面值
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function itemizeEdit()
    {
        $games = new GamesModel();
        $itemize = new itemizeModel();
        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);
            $flag = $itemize->edititemize($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.id');

        $item = $itemize->getOneitemize($id);
        $this->assign([
            'itemize' => $item,
            'games'   => $games->getOneGames($item['gs_id']),
            'status'  => config('role_status')
        ]);

        return $this->fetch();
    }

    /**
     * 删除面值
     * @return \think\response\Json
     */
    public function itemizeDel()
    {
        $id = input('param.id');
        $itemize = new itemizeModel();
        $flag = $itemize->delitemize((int)$id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}
