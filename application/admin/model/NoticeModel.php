<?php
namespace app\admin\model;

use think\Model;

class NoticeModel extends Model
{
    protected $name = 'notice';

    /**
     * 自动格式化时间
     * @param string $value
     * @return int Y m d H i s
     */
    public function getCreatedAtAttr($value, $data){
        return date('Y-m-d h:i:s', $value);
    }

    /**
     * 根据条件获取公告列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNoticeByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('ne_id DESC')
            ->select();
    }

    /**
     * 根据条件获取所有的公告数量
     * @param $where
     * @return int|string
     */
    public function getAllNotice($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入公告信息
     * @param $param
     * @return array
     */
    public function insertNotice($param)
    {
        try{
            $result =  $this->validate('NoticeValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加公告成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑公告信息
     * @param $param
     * @return array
     */
    public function editNotice($param)
    {
        try{

            $result =  $this->save($param, ['ne_id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑公告成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据公告id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneNotice($id)
    {
        return $this->where('ne_id', $id)->find();
    }

    /**
     * 删除公告
     * @param $id
     * @return array
     */
    public function delNotice($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $r = $this->where('ne_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除公告成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}