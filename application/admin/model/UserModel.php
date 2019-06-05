<?php
namespace app\admin\model;

use think\Model;

class UserModel extends Model
{
    protected $name = 'user';

    /**
     * 根据条件获取用户列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUsersByWhere($where, $offset, $limit)
    {
        return $this
        	->alias('U')
        	->field('U.*,rolename')
            ->join(['__ROLE__'=>'R'], 'U.role_id=R.id','LEFT')
            ->where($where)->limit($offset, $limit)->order('U.id DESC')->select();
    }

    /**
     * 根据条件获取用户列表信息
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUsersToExcel($where)
    {
        return $this
        	->alias('U')
        	->field('U.*,rolename')
            ->join(['__ROLE__'=>'R'], 'U.role_id=R.id','LEFT')
            ->where($where)->order('U.id DESC')->select();
    }

    /**
     * 根据条件获取单个用户信息
     * @param $where
     * @param $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUsersByField($where,$field = '')
    {
        return $this->where($where)->field($field)->find();
    }

    /**
     * 根据条件获取所有的用户数量
     * @param $where
     * @return int|string
     */
    public function getAllUsersCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据搜索条件获取所有的用户
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->select();
    }

    /**
     * 根据搜索条件获取用户总资产
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getTotalMoney($where)
    {
        return $this->where($where)->sum('money');
    }

    /**
     * 插入管理员信息
     * @param $param
     * @return array
     */
    public function insertUser($param)
    {
        try{
            $result =  $this->validate('UserValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加用户成功'];
            }
        }catch( \PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑管理员信息
     * @param $param
     * @return array
     */
    public function editUser($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '修改成功'];
            }
        }catch( \PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据管理员id获取角色信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneUser($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 增加资产
     * @param $user_id
     * @param $field
     * @param $data
     * @return MemberModel|bool
     * @throws \think\Exception
     */
    public function setUserInc($user_id,$field,$data){

        return $this ->where(['id'=>$user_id])
            ->setInc($field,$data);
    }

    /**
     * 减少资产
     * @param $user_id
     * @param $field
     * @param $data
     * @return MemberModel|bool
     * @throws \think\Exception
     */
    public function setUserDec($user_id,$field,$data){

        return $this ->where(['id'=>$user_id])
            ->setDec($field,$data);
    }

    /**
     * 删除管理员
     * @param $id
     * @return array
     */
    public function delUser($id)
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