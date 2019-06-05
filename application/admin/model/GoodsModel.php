<?php
namespace app\admin\model;

use think\Model;

class GoodsModel extends Model
{
    protected $name = 'goods';

    /**
     * 根据条件获取商品列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('gd_id ASC')
            ->select();
    }

    /**
     * 根据条件获取所有的商品数量
     * @param $where
     * @return int|string
     */
    public function getAllGoods($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入商品信息
     * @param $param
     * @return array
     */
    public function insertGoods($param)
    {
        try{
            $result =  $this->validate('GoodsValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加商品成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑商品信息
     * @param $param
     * @return array
     */
    public function editGoods($param)
    {
        try{

            $result =  $this->validate('GoodsValidate')->save($param, ['gd_id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑商品成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据商品id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneGoods($id)
    {
        return $this->where('gd_id', $id)->find();
    }

    /**
     * 删除商品
     * @param $id
     * @return array
     */
    public function delGoods($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('gd_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除商品成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}