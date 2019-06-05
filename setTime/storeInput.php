<?php
/**
 * 定时校验等待倒入的凭证
 * */

    $time = time();
    $date = date('Y-m-d H:i', $time);

    define('DS', DIRECTORY_SEPARATOR);
    define('PATH', dirname(dirname(__FILE__)));

    $db = include_once PATH . DS . 'application' . DS . 'database.php';


    /**
     * 打印 log
     * */
    function put_log($msg)
    {
        echo $msg . "\n";

        $date = date('Y-m-d H:i');
        $filename = PATH . DS . 'setTime' . DS . 'log' . DS . $date . '.txt';

        file_put_contents($filename, $msg . "\n", FILE_APPEND);
    }

    /**
     * 验证AppStore内付
     * @param  string $receipt_data 付款后凭证
     * @return array                验证是否成功
     */
    function validate_apple_pay($receipt_data)
    {
        //* 21000 App Store不能读取你提供的JSON对象
        //* 21002 receipt-data域的数据有问题
        //* 21003 receipt无法通过验证
        //* 21004 提供的shared secret不匹配你账号中的shared secret
        //* 21005 receipt服务器当前不可用
        //* 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
        //* 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
        //* 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务

        //小票信息
            $POSTFIELDS = json_encode(array("receipt-data" => $receipt_data));

            $url_buy     = "https://buy.itunes.apple.com/verifyReceipt"; //正式购买地址
            // $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt"; // 沙盒购买地址

        //简单的curl
            $ch = curl_init($url_buy);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
            $result = curl_exec($ch);
            curl_close($ch);

        // 请求验证
            $data = json_decode($result,true);
            $status = intval($data['status']) === 0;

        // 判断是否购买成功
            return [
                'status' => $status,
                'message' => $status ? '获取成功' : $data['status'],
                'data' => $status ? $data : '',
            ];
    }

    /**
     * 保存游戏凭证失败
     * */
    function save_result($pdo, $id, $status_zn)
    {
        try {
            // 标记凭证导入失败
            $sql = 'update `cz_receipt` set `status` = 2,`status_zn`=:status_zn where `id`=:id';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':status_zn', $status_zn);

            $obj = $stmt->execute();
            $stmt->closeCursor();

            if (!$obj) {
                throw new PDOException('标记凭证失败 ' . $id);
            }
        } catch(PDOException $e) {
            // 记录错误日志
            put_err($e->getMessage() . " \r\n");
        }
    }

    // 连接数据库
        $db_addr = $db['hostname'];
        $db_name = $db['database'];
        $root = $db['username'];
        $pass = $db['password'];
        $dbs = 'mysql:host=' . $db_addr. ';dbname=' . $db_name;
        $pdo = new PDO($dbs, $root, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 从数据库中获取等待倒入的记录
        $sql = 'select `id`, `user_id`, `receipt` from `cz_receipt` where `status` = 1 && CHAR_LENGTH(`receipt`) > 20 order by id asc limit 0,8';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $store = $stmt->fetchAll();
        $stmt->closeCursor();

        if (empty($store))
        {
            return put_log('没有需要倒入的数据');
        }

        foreach ($store as $key=> $value)
        {
            $id = $value['id'];
            $user_id = $value['user_id'];
            $receipt = $value['receipt'];

            if (mb_strlen($receipt, 'utf8') < 20)
            {
                save_result($pdo, $id, '凭证长度未达到要求');
                continue;
            }

            // 获取面值信息
            $sql = 'select * from `cz_store` where `receipt` = "' . $receipt . '" limit 0,1';


            var_dump($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $store = $stmt->fetch();
            $stmt->closeCursor();

            if (!empty($store))
            {
                save_result($pdo, $id, '凭证已经导入过了');
                continue;
            }

            $type_str = mb_substr(trim($receipt, 'utf8'),0,3);

            if (!in_array($type_str, ['MII', 'ewo']))
            {
                save_result($pdo, $id, '未知类型凭证');
                continue;
            }

            //验证凭证
            $verify = validate_apple_pay($receipt);

            if (empty($verify['status']))
            {
                save_result($pdo, $id, '未通过苹果校验, code: ' . $verify['message']);
                continue;
            }
            else {
                $status     = 5;
                $start_time = date('Y-m-d h:i:s', strtotime($verify['data']['receipt']['original_purchase_date_pst']));
                $is_goods   = 0;

                $receipt = trim($receipt, "\n");
                $receipt = trim($receipt, "\r");
                $receipt = trim($receipt, "\t");
                $receipt = trim($receipt, " ");

                $receipt1 = explode("\n", $receipt);
                $receipt2 = explode("\r", $receipt);
                $receipt3 = explode("\t", $receipt);
                $receipt4 = explode(" ", $receipt);

                if (count($receipt1) > 1)
                {
                    $receipt = $receipt1[0];
                }

                if (count($receipt2) > 1)
                {
                    $receipt = $receipt2[0];
                }

                if (count($receipt3) > 1)
                {
                    $receipt = $receipt3[0];
                }

                if (count($receipt4) > 1)
                {
                    $receipt = $receipt4[0];
                }

                switch ($type_str)
                {
                    case 'MII':
                        $identifier  = $verify['data']['receipt']['in_app'][0]['transaction_id']; // 新凭证-订单号
                        $title = $verify['data']['receipt']['in_app'][0]['product_id'];
                        break;
                    case 'ewo':
                        $identifier  = $verify['data']['receipt']['transaction_id']; // 老凭证-订单号
                        $title = $verify['data']['receipt']['product_id'];
                        break;
                }

                // 获取面值信息
                    $sql = 'select * from `cz_games_price` where `title` = "' . $title . '" limit 0,1';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $game_price= $stmt->fetch();
                    $stmt->closeCursor();

                //目前不支持该游戏,库存
                    if (empty($game_price))
                    {
                        save_result($pdo, $id, '目前不支持当前游戏');
                        continue;
                    }

                    $game_id = $game_price['gs_id'];
                    $price_id = $game_price['id'];
                    $price = $game_price['money']; // 游戏面值
                    $desc = '【用户导入】' . $game_price['gold'];

                // 导入凭证
                try{
                    /** 开启事务处理 **/
                        $pdo->beginTransaction();

                    // 插入凭证
                        $sql = 'insert into `cz_store`(`user_id`, `desc`, `status`, `start_time`, `price`, `price_id`, `game_id`, `is_goods`, `receipt`, `identifier`) value(:user_id, :desc, :status, :start_time, :price, :price_id, :game_id, :is_goods, :receipt, :identifier)';
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':desc', $desc);
                        $stmt->bindParam(':status', $status);
                        $stmt->bindParam(':start_time', $start_time);
                        $stmt->bindParam(':price', $price);
                        $stmt->bindParam(':price_id', $price_id);
                        $stmt->bindParam(':game_id', $game_id);
                        $stmt->bindParam(':is_goods', $is_goods);
                        $stmt->bindParam(':receipt', $receipt);
                        $stmt->bindParam(':identifier', $identifier);

                        $obj = $stmt->execute();
                        $insertId = $pdo->lastInsertId();
                        $stmt->closeCursor();

                        if (!$obj) {
                            throw new PDOException('凭证导入失败');
                        }

                    // 标记凭证导入成功
                        $sql = 'update `cz_receipt` set `status` = 0,`status_zn`="导入成功" where `id`=:id';
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':id', $id);

                        $obj = $stmt->execute();
                        $stmt->closeCursor();

                        if (!$obj) {
                            throw new PDOException('标记凭证导入失败 ' . $id);
                        }


                    /** 提交事物 **/
                        $pdo->commit();

                        $errorLog = 'id=' . $id . '; user_id=' . $user_id . '; insertId: ' . $insertId . '; receipt=' . $receipt . "; 导入成功 \r\n";
                        put_log($errorLog);

                } catch(PDOException $e) {
                    /** 回滚事物 **/
                        $pdo->rollback();

                    /** 记录错误日志 **/
                        put_log($e->getMessage() . " \r\n");
                }
            }
        }


    $msg = '程序运行 finish';
    put_log($msg);
    $pdo = null;
    $stmt = null;
    exit;








