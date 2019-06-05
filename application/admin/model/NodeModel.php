<?php
namespace app\admin\model;
use think\Model;

class NodeModel extends Model
{

    protected $name = "node";

    /**
     * 获取节点数据
     * @param $id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNodeInfo($id)
    {
        $result = $this->field('id,node_name,typeid')->order('id asc')->select();
        $str = "";

        $role = new UserTypeModel();
        $rule = $role->getRuleById($id);

        if(!empty($rule)){
            $rule = explode(',', $rule);
        }
        foreach($result as $key=>$vo){
            $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['typeid'] . '", "name":"' . $vo['node_name'].'"';

            if(!empty($rule) && in_array($vo['id'], $rule)){
                $str .= ' ,"checked":1';
            }

            $str .= '},';

        } 
        return "[" . substr($str, 0, -1) . "]";
    }

    /**
     * 根据节点数据获取对应的菜单
     * @param string $nodeStr
     * @return array
     */
    public function getMenu($nodeStr = '')
    {
        //超级管理员没有节点数组
        $where = empty($nodeStr) ? 'is_menu = 2' : 'is_menu = 2 and id in('.$nodeStr.')';

        $result = db('node')->field('id,node_name,typeid,control_name,action_name,style')
            ->where($where)->order('id asc')->select();
        $menu = prepareMenu($result);

        return $menu;
    }
}