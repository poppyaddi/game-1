<?php
namespace app\admin\model;

use think\Model;

class StockModel extends Model
{
    protected $name = 'store';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据条件获取库存列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $map
     * @param $order
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStockByWhere($where, $offset, $limit, $map = '' ,$order = 'game_id')
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order("$order DESC")
            ->field('id,game_id,end_time,desc,create_time,identifier,is_goods,price,price_id,start_time,status,user_id,use_time')
            ->group($map)
            ->select();
    }

    /**
     * 根据条件获取所有的库存数量
     * @param $where
     * @return int|string
     */
    public function getAllStockCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的库存
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllStock($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 插入库存信息
     * @param $param
     * @return array
     */
    public function insertStock($param)
    {
        try{
            $result =  $this->validate('StockValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加库存成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑库存信息
     * @param $param
     * @return array
     */
    public function editStock($param)
    {
        try{

            $result =  $this->save($param, ['gs_id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑库存成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 更新库存信息
     * @param $where
     * @param $param
     * @return array
     */
    public function saveStock($where,$param)
    {
        try{

            $result =  $this->where($where)->update($param);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '更新库存成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据库存id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneStock($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除库存
     * @param $id
     * @return array
     */
    public function delStock($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('gs_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除库存成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}