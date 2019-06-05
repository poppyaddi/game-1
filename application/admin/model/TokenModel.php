<?php
namespace app\admin\model;

use think\Model;

class TokenModel extends Model
{
    protected $name = 'token';
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 根据条件获取Token列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTokenByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('id ASC')
            ->select();
    }

    /**
     * 根据条件获取所有的Token数量
     * @param $where
     * @return int|string
     */
    public function getAllToken($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入Token信息
     * @param $param
     * @return array
     */
    public function insertToken($param)
    {
        try{
            $result =  $this->validate('TokenValidate')->save($param);
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
     * 编辑Token信息
     * @param $param
     * @return array
     */
    public function editToken($param)
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
     * 根据Tokenid获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneToken($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除Token
     * @param $id
     * @return array
     */
    public function delToken($id)
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