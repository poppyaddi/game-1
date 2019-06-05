<?php
namespace app\admin\model;

use think\Model;

class GamesModel extends Model
{
    protected $name = 'games';

    /**
     * 自动格式化时间
     * @param string $value
     * @return int Y m d H i s
     */
    public function getCreatedAtAttr($value, $data){
        return date('Y-m-d h:i:s', $value);
    }

    /**
     * 根据条件获取游戏列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGamesByWhere($where, $offset, $limit)
    {
        return $this
            ->where($where)
            ->limit($offset, $limit)
            ->order('convert(gs_name using gbk) collate gbk_chinese_ci')
            ->select();
    }

    /**
     * 根据条件获取所有的游戏数量
     * @param $where
     * @return int|string
     */
    public function getAllGamesCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的游戏
     * @param $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllGames($where)
    {
        return $this->where($where)->order('convert(gs_name using gbk) collate gbk_chinese_ci')->select();
    }

    /**
     * 插入游戏信息
     * @param $param
     * @return array
     */
    public function insertGames($param)
    {
        try{
            $result =  $this->validate('GamesValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加游戏成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑游戏信息
     * @param $param
     * @return array
     */
    public function editGames($param)
    {
        try{

            $result =  $this->save($param, ['gs_id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑游戏成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据游戏id获取信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneGames($id)
    {
        return $this->where(['gs_id'=> $id])->find();
    }

    /**
     * 删除游戏
     * @param $id
     * @return array
     */
    public function delGames($id)
    {
        try{

            if(!is_int($id)){
                return ['code' => -1, 'data' => '', 'msg' => '参数错误'];
            }
            $this->where('gs_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除游戏成功'];

        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}