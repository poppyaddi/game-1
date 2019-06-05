<?php
namespace app\admin\model;

use think\Model;

class OrderModel extends Model
{
    protected $name = 'order';

    /**
     * 自动格式化时间
     * @param string $value
     * @return int Y m d H i s
     */
    public function getCreatedAtAttr($value, $data){
        return date('Y-m-d h:i:s', $value);
    }

    /**
     * 根据条件获取订单列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @param $whereOr
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderByWhere($where, $offset, $limit, $whereOr = '')
    {
        return $this
            ->where($where)
            ->where(function ($query) use($whereOr){
                $query->whereOr($whereOr);
            })
            ->limit($offset, $limit)
            ->order('trans_id DESC')
            ->select();
    }

    /**
     * 根据条件获取所有的订单数量
     * @param $where
     * @return int|string
     */
    public function getAllOrderCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的订单
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllOrder($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 插入订单信息
     * @param $param
     * @return array
     */
    public function insertOrder($param)
    {
        try{
            $result =  $this->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '购买成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑订单信息
     * @param $param
     * @return array
     */
    public function editOrder($param)
    {
        try{

            $result =  $this->save($param, ['trans_id' => $param['trans_id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑订单成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据订单id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneOrder($id)
    {
        return $this->where('trans_id', $id)->find();
    }

    /**
     * 删除订单
     * @param $id
     * @return array
     */
    public function delOrder($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('trans_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除订单成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}