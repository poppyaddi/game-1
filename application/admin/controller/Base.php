<?php
namespace app\admin\controller;
use app\admin\model\NodeModel;
use think\Controller;
class Base extends Controller
{
    protected $userInfo = '';

    public function _initialize()
    {
        $user = session('username');

        //检测权限
        $control = lcfirst( request()->controller() );
        $action  = strtolower( request()->action() );

        //跳过登录系列的检测以及主页权限
        if(!in_array($control, ['login', 'index'])){
            //Seeion为空时的权限检测
            if(empty($user))  $this->error('没有权限','login/index');
            //权限检测
            if(!in_array($control . '/' . $action, session('action'))){

                $this->error('没有权限','index/indexPage');
            }
        }

        //判断两个人不能同时登录
        $userInfo = db('user')->where(array('username'=>$user))->find();
        if (session_id() != $userInfo['session_id'] || $userInfo == ''){
            $this->error('您的登录状态已过期,请重新登录!','Login/index');
        }
        //赋值用户信息
        $this->userInfo = $userInfo;

        if (1 != $this->userInfo['id']){
            if (strtotime($this->userInfo['end_time']) - time() < 0){
                $this->error('您的账户已到期,请联系客服充值!','Login/index');
            }
        }

        //获取权限菜单
        $node = new NodeModel();
        if ($this->userInfo['id'] != 1) {

            $end_time =  number_format((strtotime($this->userInfo['end_time']) - time()) / 3600 / 24,1);
        }else{

            $end_time = '--';
        }

        $username = session('username');

        if (mb_strlen($username) > 5)
        {
            $username = mb_substr($username, 0, 5) . ' ...';
        }

        $this->assign([
            'username' => $username,
            'menu' => $node->getMenu(session('rule')),
            'rolename' => session('role'),
            'endtime' => $end_time
        ]);

    }

    /**
     * 上传图片
     * @param $files
     * @return string
     */
    protected function uploadOne($files){
        $wheatpic = '';
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $files->validate(['size'=>1567800,'ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            $wheatpic = '/uploads/' . str_replace('\\', '/', $info->getsaveName ());
        }else{
            // 上传失败获取错误信息
            $this ->error($files->getError());
        }
        return $wheatpic;
    }
	/**
     * 导出数据为excel表格
     * @param array $data
     * @param array $title
     * @param string $filename
     */
  	public function exportExcel($data = array(), $title = array(), $filename = 'report') {
    	$this->getExcel($filename,$title,$data);
    }
  
    /**
     * 导出数据为excel表格
     * @param array $data
     * @param array $title
     * @param string $filename
     */
	public function exportExcel2($data = array(), $title = array(), $filename = 'report') {
		header ( "Content-type:application/octet-stream" );
		header ( "Accept-Ranges:bytes" );
		header ( "Content-type:application/vnd.ms-excel" );
		header ( "Content-Disposition:attachment;filename=" . $filename . ".xlsx" );
		header ( "Pragma: no-cache" );
		header ( "Expires: 0" );
		// 导出xls 开始
		if (! empty ( $title )) {
			foreach ( $title as $k => $v ) {
				$title [$k] = iconv ( "UTF-8", "GB2312", $v );
			}
			$title = implode ( "\t", $title );
			echo "$title\n";
		}
		if (! empty ( $data )) {
			foreach ( $data as $key => $val ) {
				foreach ( $val as $ck => $cv ) {
					$data [$key] [$ck] = iconv ( "UTF-8", "GB2312", $cv );
				}
				$data [$key] = (string)implode ( "\t", $data [$key] );
			}
			echo (string)implode ( "\n", $data );
		}
	}

    /**
     * 验证AppStore内付
     * @param  string $receipt_data 付款后凭证
     * @return array                验证是否成功
     */
    public function validate_apple_pay($receipt_data){
        /**
         * 21000 App Store不能读取你提供的JSON对象
         * 21002 receipt-data域的数据有问题
         * 21003 receipt无法通过验证
         * 21004 提供的shared secret不匹配你账号中的shared secret
         * 21005 receipt服务器当前不可用
         * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
         * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
         * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
         */

        // 验证参数
        if (strlen($receipt_data) < 20){
            $result=array(
                'status' => false,
                'message' => '非法参数',
                'data' =>  '',
            );
            return $result;
        }
        // 请求验证
        $html = $this->acurl($receipt_data);
        $data = json_decode($html,true);

        // 判断是否购买成功
        if(intval($data['status'])===0){
            $result=array(
                'status' => true,
                'message' => '获取成功',
                'data' => $data
            );
        }else {
            $result = array(
                'status' => false,
                'message' => '获取失败',
                'data' => '',
            );
        }
        return $result;
    }

    /**
     * CURL函数
     * @param $receipt_data
     * @param int $sandbox
     * @return mixed
     */
    private function acurl($receipt_data, $sandbox = 0){
        //小票信息
        $POSTFIELDS = json_encode(array("receipt-data" => $receipt_data));

        //正式购买地址 沙盒购买地址
        $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
        $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
        $url = $sandbox ? $url_sandbox : $url_buy;
        //简单的curl
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
  
  	public function getExcel($fileName,$headArr,$data){
        //对数据进行检验
        if(empty($data) || !is_array($data)){
            die("data must be a array");
        }
        //检查文件名
        if(empty($fileName)){
            exit;
        }

        //$date = date("Y_m_d",time());
        $fileName .= ".xls";


        //创建PHPExcel对象，注意，不能少了\
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();
            
        //设置表头
        $key = ord("A");
        foreach($headArr as $v){
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum.'1', $v);
            $key += 1;
        }

        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();


        //设置为文本格式
        foreach($data as $key => $rows){ //行写入
            $span = ord("A");
            foreach($rows as $keyName=>$value){// 列写入
                $j = chr($span);

                $objActSheet->setCellValueExplicit($j.$column, $value);
                $span++;
            }
            $column++;
        }

        $fileName = iconv("utf-8", "gb2312", $fileName);
        //重命名表
        // $objPHPExcel->getActiveSheet()->setTitle('test');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载
        exit;
    }
}