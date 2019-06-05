<?php
namespace app\admin\model;

use think\Model;

class StockLogModel extends Model
{
    protected $name = 'store_log';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据条件获取库存日志列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $map
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStockLogByWhere($where, $offset, $limit, $map = '')
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('id DESC')
            ->group($map)
            ->select();
    }

    /**
     * 根据条件获取所有的库存日志数量
     * @param $where
     * @return int|string
     */
    public function getAllStockLogCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的库存日志
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllStockLog($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 插入库存日志信息
     * @param $param
     * @return array
     */
    public function insertStockLog($param)
    {
        try{
            $result =  $this->insertGetId($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加库存日志成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑库存日志信息
     * @param $param
     * @return array
     */
    public function editStockLog($param)
    {
        try{

            $result =  $this->save($param, ['gs_id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑库存日志成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 更新库存日志信息
     * @param $where
     * @param $param
     * @return array
     */
    public function saveStockLog($where,$param)
    {
        try{

            $result =  $this->where($where)->update($param);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '更新库存日志成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据库存日志id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneStockLog($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除库存日志
     * @param $id
     * @return array
     */
    public function delStockLog($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除库存日志成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}