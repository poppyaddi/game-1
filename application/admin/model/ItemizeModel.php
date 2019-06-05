<?php
namespace app\admin\model;

use think\Model;

class ItemizeModel extends Model
{
    protected $name = 'games_price';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据条件获取面值列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItemizeByWhere($where, $offset, $limit)
    {
         return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order(['id'=> 'desc'])
            ->select();
    }

    /**
     * 根据条件获取所有的面值数量
     * @param $where
     * @return int|string
     */
    public function getAllItemizeCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的面值
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllItemize($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 插入面值信息
     * @param $param
     * @return array
     */
    public function insertItemize($param)
    {
        try{
            $result =  $this->validate('ItemizeValidate')->save($param);
            if(false === $result){

                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加面值成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑面值信息
     * @param $param
     * @return array
     */
    public function editItemize($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑面值成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据面值id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneItemize($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 根据面值id获取信息
     * @param $where
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneItemizes($where)
    {
        return $this->where($where)->find();
    }

    /**
     * 删除面值
     * @param $id
     * @return array
     */
    public function delItemize($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除面值成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}