<?php
namespace app\admin\controller;
use app\admin\model\NoticeModel;

class Notice extends Base
{
    /**
     * 公告列表
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
            if (isset($param['noticeName']) && !empty($param['noticeName'])) {
                $where['ne_name'] = ['like', '%' . $param['noticeName'] . '%'];
            }
            if (!empty($param['startTime']) && !empty($param['endTime'])) {
                $where['created_at'] = array('between',array(strtotime($param['startTime']),strtotime($param['endTime'])));
            }
            $notice = new NoticeModel();
            $selectResult = $notice->getNoticeByWhere($where, $offset, $limit);

            $status = config('role_status');
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['ne_status'] = $status[$vo['ne_status']];
                $operate = [
                    '编辑公告' => url('Notice/noticeEdit', ['ne_id' => $vo['ne_id']]),
                    '删除公告' => "javascript:noticeDel('".$vo['ne_id']."')",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }
            $return['total'] = $notice->getAllNotice($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 添加公告
     * @return mixed|\think\response\Json
     */
    public function NoticeAdd()
    {
        if(request()->isPost()){

            $param = input('param.');
            $param = parseParams($param['data']);
            $param['created_at'] = time();
            $param['updated_at'] = '';
            $notice = new NoticeModel();
            $flag = $notice->insertNotice($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $this->assign([
            'status'    => config('role_status'),
        ]);

        return $this->fetch();
    }

    /**
     * 编辑公告
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function noticeEdit()
    {
        $notice = new NoticeModel();

        if(request()->isPost()){

            $param = input('post.');
            $param = parseParams($param['data']);
            $param['updated_at'] = time();
            $flag = $notice->editNotice($param);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        $id = input('param.ne_id');

        $this->assign([
            'notice' => $notice->getOneNotice($id),
            'status' => config('role_status')
        ]);

        return $this->fetch();
    }

    /**
     * 删除公告
     * @return \think\response\Json
     */
    public function noticeDel()
    {
        $id = input('param.ne_id');
        $notice = new NoticeModel();
        $flag = $notice->delNotice((int)$id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

}
