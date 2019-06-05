<?php
namespace app\admin\model;
use think\Model;
class MemberModel extends Model
{
    protected $name = 'member';

    /**
     * 获取单个用户信息
     * @param $user_id
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMemberOne($user_id,$field = ''){
		$re = $this->field($field) ->where(['user_id'=>$user_id])->find();
		return $re;
    }

    /**
     * 根据条件获取用户列表信息
     * @param $where
     * @param string $pagdata
     * @param int $limit
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getMemberPage($where,$pagdata='',$limit=10)
    {
    	if(!empty($where) && empty($pagdata)){
    		//分页配置
    		foreach ($where as $k=>$v){
    			$pagdata ['query'][$k] = $v;
    		}
    	}
    	if(empty($pagdata)){
    		$re = $this->where($where)->paginate($limit);
    	}else{
    		$re = $this->where($where)->paginate($limit,false,$pagdata);
    	}
    	return $re;
    }

    /**
     * 根据条件获取所有的用户数量
     * @param $where
     * @return int|string
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据条件获取所有的用户数量
     * @param $where
     * @param string $field
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllMember($where,$field = '')
    {
    	return $this->where($where)->field($field)->select();
    }

    /**
     * 添加会员
     * @param $data
     * @return int|string
     */
	public function insertMember($data){
		//执行添加操作
		$result =  $this->insertGetId($data);
		return $result;
	}

    /**
     * 修改会员
     * @param $user_id
     * @param $data
     * @return MemberModel|bool
     */
    public function updata($user_id,$data){
    	if(!is_int($user_id)){
    		return false;
    	}
    	return $this ->where(['user_id'=>$user_id])->update($data);
    }

    /**
     * 获取单条通过字段
     * @param $field
     * @param $value
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMemberOneByField($field,$value){
    	return $this ->where(array($field=>$value))->find();
    }

}