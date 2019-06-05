<?php

    /**
     * 获取客户端 IP
     * 2016/10/13
     */
    function getIP()
    {
        if (getenv('HTTP_CLIENT_IP'))
        {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        }
        elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        }
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * 返回 json 数据
     * 2018/06/07
     */
    function succ($param) { return returnData(['code'=> 200, 'message'=> '请求成功!'], $param); }

    /**
     * 返回 error 信息
     * 2018/06/07
     */
    function error($param) { return returnData(['code'=> 400, 'message'=> '请求失败'], $param); }

    /*
     * 返回 json 数据
     * 2018/06/07
     */
    function returnData($data, $param)
    {
        // 对象转数组
            if (is_object($param))
            {
                $param = json_decode(json_encode($param), true);
            }

        // 合并数据
            if (is_array($param))
            {
                // 登陆状态
                    if (isset($param['auth']))
                    {
                        $data['auth'] = $param['auth'];
                        unset($param['auth']);
                    }
                    else {
                        $data['auth'] = true;
                    }

                // 提示信息
                    if (!empty($param['message']))
                    {
                        $data['message'] = $param['message'];
                        unset($param['message']);
                    }

                // 检查数据是否拥有下一页
                    if (isset($param['page']))
                    {
                        $current_page = $param['page']['current_page']; // 当前第几页
                        $per_page = $param['page']['per_page']; // 每页显示几条
                        $total = $param['page']['total']; // 总记录数

                        if ($current_page * $per_page < $total)
                        {
                            $param['page']['hasmore'] = true;
                        }
                        else {
                            $param['page']['hasmore'] = false;
                        }
                    }

                $data['data'] = $param;
            }
            else {
                $data['message'] = $param;
            }


        return $data;
    }