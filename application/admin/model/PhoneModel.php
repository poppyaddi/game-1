<?php
namespace app\admin\model;

use think\Model;

class PhoneModel extends Model
{
    protected $name = 'device';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据条件获取手机列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPhoneByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('id ASC')
            ->select();
    }

    /**
     * 根据条件获取所有的手机数量
     * @param $where
     * @return int|string
     */
    public function getAllPhone($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入手机信息
     * @param $param
     * @return array
     */
    public function insertPhone($param)
    {
        try{
            $result =  $this->validate('PhoneValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑手机信息
     * @param $param
     * @return array
     */
    public function editPhone($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '操作成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据手机id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOnePhone($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除手机
     * @param $id
     * @return array
     */
    public function delPhone($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}