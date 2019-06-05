<?php
namespace app\admin\model;

use think\Model;

class MoneyLogModel extends Model
{
    protected $name = 'moneylog';

    /**
     * 根据条件获取充值列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMoneyLogByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('created_at DESC')
            ->select();
    }
    /**
     * 导出财务信息
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMoneyToExcel($where)
    {
        return $this
            ->where($where)
            ->order('cz_status ASC')
            ->select();
    }

    /**
     * 根据条件获取所有的充值数量
     * @param $where
     * @return int|string
     */
    public function getAllMoneyLogCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的充值
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllMoneyLog($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 插入充值信息
     * @param $param
     * @return array
     */
    public function insertMoneyLog($param)
    {
        try{
            $result =  $this->validate('MoneyLogValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加充值成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑充值信息
     * @param $param
     * @return array
     */
    public function editMoneyLog($param)
    {
        try{

            $result =  $this->save($param, ['cz_id' => $param['cz_id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑充值成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据充值id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneMoneyLog($id)
    {
        return $this->where('cz_id', $id)->find();
    }

    /**
     * 删除充值
     * @param $id
     * @return array
     */
    public function delMoneyLog($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('cz_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除充值成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}