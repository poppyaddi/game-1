<?php
namespace app\admin\model;

use think\Model;

class UserLogModel extends Model
{
    protected $name = 'userlog';

    /**
     * 根据条件获取用户日志列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserLogByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('created_at DESC')
            ->select();
    }

    /**
     * 根据条件获取所有的用户日志数量
     * @param $where
     * @return int|string
     */
    public function getAllUserLogCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的用户日志
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllUserLog($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 插入用户日志信息
     * @param $param
     * @return array
     */
    public function insertUserLog($param)
    {
        try{
            $result =  $this->insertGetId($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加用户日志成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑用户日志信息
     * @param $param
     * @return array
     */
    public function editUserLog($param)
    {
        try{

            $result =  $this->save($param, ['ug_id' => $param['ug_id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑用户日志成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据用户日志id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneUserLog($id)
    {
        return $this->where('ug_id', $id)->find();
    }

    /**
     * 删除用户日志
     * @param $id
     * @return array
     */
    public function delUserLog($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('ug_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除用户日志成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}