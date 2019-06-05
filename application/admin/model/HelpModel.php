<?php
namespace app\admin\model;

use think\Model;

class HelpModel extends Model
{
    protected $name = 'helps';

    /**
     * 自动格式化时间
     * @param string $value
     * @return int Y m d H i s
     */
    public function getCreatedAtAttr($value, $data){
        return date('Y-m-d h:i:s', $value);
    }


    /**
     * 根据条件获取帮助列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHelpsByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('hp_id ASC')
            ->select();
    }

    /**
     * 根据条件获取所有的帮助数量
     * @param $where
     * @return int|string
     */
    public function getAllHelps($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入帮助信息
     * @param $param
     * @return array
     */
    public function insertHelp($param)
    {
        try{
            $result =  $this->validate('HelpValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加帮助成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑帮助信息
     * @param $param
     * @return array
     */
    public function editHelp($param)
    {
        try{

            $result =  $this->save($param, ['hp_id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑帮助成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据帮助id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneHelp($id)
    {
        return $this->where('hp_id', $id)->find();
    }

    /**
     * 删除帮助
     * @param $id
     * @return array
     */
    public function delHelp($id)
    {
        try{

            $this->where('hp_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除帮助成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}