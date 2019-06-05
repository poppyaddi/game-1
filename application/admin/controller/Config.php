<?php
/**
 *  系统配置
 *  @author Summer
 *  @time 2018-06-12
 */
namespace app\admin\controller;
use \think\Request;
class Config extends Base {

    /**
     * 网站配置列表
     * @param $param ''
     * @return 渲染页面
     */
	public function index() {
		$config = db('Config')->select();
		foreach ($config as  $k=>$v){
			$list[$v['key']] = $v['value'];
		}
		$this ->assign('config',$list);
		return $this->fetch ();
	
	}


    /**
     * 修改配置信息
     * @param $param ''
     * @return 渲染页面
     */
	public function edit() {
	    if(Request::instance()->isPost()){
            $picture = $this->request->file('picture');
            if (!empty($picture)){
                $r = $this->uploadOne($picture);
                $re = db('Config')->where(['key'=>'picture'])->update(['value'=>$r]);
            }
            foreach ($_POST as $k=>$v){
                $where['key'] = $k;
                $data['value'] = $v;
                $re = db('Config')->where($where)->update($data);
            }
        }
		$this->success ( '配置修改成功');
	}

}
