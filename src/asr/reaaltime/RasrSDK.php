<?php


namespace Devtech\Tencent\Asr\Realtime;


class RasrSDK
{
    // -------------- Required. 请登录腾讯云官网控制台获取 ---------------------
    private $SECRET_ID = "AKIDoQq1zhZMN8dv0psmvud6OUKuGPO7pu0r";
    private $SECRET_KEY = "kFpwoX5RYQ2SkqpeHgqmSzHK7h3A2fni";
    private $APPID = "1259228442";

    // --------------- Optional, 请按需修改 ---------------------
    /* 识别引擎 8k_0 or 16k_0 */
    private $ENGINE_MODEL_TYPE = '8k_0';

    //结果返回方式 0：同步返回，拿到全部中间结果， or 1：尾包返回
    private $RES_TYPE = 0;

    // 识别结果文本编码方式 0:UTF-8, 1:GB2312, 2:GBK,3:BIG5
    private $RESULT_TEXT_FORMAT = 0;

    // 语音编码方式，可选，默认值为 4。1：wav(pcm)；4：speex(sp)；6：silk；8：mp3。
    private $VOICE_FORMAT = 1;

    // 语音切片长度 cutlength<200000
    private $CUTLENGTH = 6400;

    public function __construct(array $config)
    {
        if (array_key_exists('SECRET_ID', $config)) $this->SECRET_ID = $config['SECRET_ID'];
        if (array_key_exists('SECRET_KEY', $config)) $this->SECRET_ID = $config['SECRET_KEY'];
        if (array_key_exists('APPID', $config)) $this->SECRET_ID = $config['APPID'];
        if (array_key_exists('ENGINE_MODEL_TYPE', $config)) $this->SECRET_ID = $config['ENGINE_MODEL_TYPE'];
        if (array_key_exists('RES_TYPE', $config)) $this->SECRET_ID = $config['RES_TYPE'];
        if (array_key_exists('RESULT_TEXT_FORMAT', $config)) $this->SECRET_ID = $config['RESULT_TEXT_FORMAT'];
        if (array_key_exists('VOICE_FORMAT', $config)) $this->SECRET_ID = $config['VOICE_FORMAT'];
        if (array_key_exists('CUTLENGTH', $config)) $this->SECRET_ID = $config['CUTLENGTH'];
    }


    function createSign($reqArr, $method, $domain, $path, $secretKey)
    {
        $signStr = "";
        $signStr .= $method;
        $signStr .= $domain;
        $signStr .= $path;
        $signStr .= $reqArr['appid'];
        $signStr .= "?";

        ksort($reqArr, SORT_STRING);

        foreach ($reqArr as $key => $val) {
            if ($key == "appid")
                continue;
            $signStr .= $key . "=" . $val . "&";
        }
        $signStr = substr($signStr, 0, -1);
        //echo "plainText : \n".$signStr."\n";

        $signStr = base64_encode(hash_hmac('SHA1', $signStr, $secretKey, true));

        return $signStr;
    }

    function http_curl_exec($url, $data, &$rsp_str, &$http_code, $method = 'POST', $timeout = 10, $cookie = array(), $headers = array())
    {
        $ch = curl_init();
        if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1)) {
            echo 'http_curl_ex set returntransfer failed';
            return -1;
        }

        if (count($headers) > 0) {
            //Log::debug('http_curl_ex set headers');
            if (!curl_setopt($ch, CURLOPT_HTTPHEADER, $headers)) {
                echo 'http_curl_ex set httpheader failed';
                return -1;
            }
        }

        if ($method != 'GET') {
            $data = is_array($data) ? json_encode($data) : $data;
            //Log::debug('data (non GET method) : '.$data);
            if (!curl_setopt($ch, CURLOPT_POSTFIELDS, $data)) {
                echo 'http_curl_ex set postfields failed';
                return -1;
            }
        } else {
            $data = is_array($data) ? http_build_query($data) : $data;
            if (strpos($url, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $url .= $data;
        }

        if (!empty ($cookies)) {
            $cookie_str = '';
            foreach ($cookies as $key => $val) {
                $cookie_str .= "$key=$val; ";
            }

            if (!curl_setopt($ch, CURLOPT_COOKIE, trim($cookie_str))) {
                echo 'http_curl_ex set cookie failed';
                return -1;
            }
        }

        if (!curl_setopt($ch, CURLOPT_URL, $url)) {
            echo 'http_curl_ex set url failed';
            return -1;
        }

        if (!curl_setopt($ch, CURLOPT_TIMEOUT, $timeout)) {
            echo 'http_curl_ex set timeout failed';
            return -1;
        }

        if (!curl_setopt($ch, CURLOPT_HEADER, 0)) {
            echo 'http_curl_ex set header failed';
            return -1;
        }

        $rsp_str = curl_exec($ch);
        if ($rsp_str === false) {
            var_dump(curl_error($ch));
            curl_close($ch);
            return -2;
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return 0;
    }

    function randstr($num)
    {
        $str = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        str_shuffle($str);
        $name = substr(str_shuffle($str), 26, $num);
        return $name;
    }

    function sendvoice($filepath, $printCutResponse)
    {
        if (empty ($filepath)) {
            echo "filepath can not be empty";
            return -1;
        }

        $reqArr = array();
        $reqArr['appid'] = $this->APPID;
        $reqArr['projectid'] = 0;
        $reqArr['sub_service_type'] = 1;
        $reqArr['engine_model_type'] = $this->ENGINE_MODEL_TYPE;
        $reqArr['res_type'] = $this->RES_TYPE;
        $reqArr['result_text_format'] = $this->RESULT_TEXT_FORMAT;
        $reqArr['voice_id'] = randstr(16);
        $reqArr['needvad'] = 0;
        $reqArr['timeout'] = 20000;
        $reqArr['source'] = 0;
        $reqArr['secretid'] = $this->SECRET_ID;
        $reqArr['timestamp'] = time();
        $reqArr['expired'] = time() + 24 * 60 * 60;
        $reqArr['nonce'] = rand(1, 10000);
        $reqArr['voice_format'] = $this->VOICE_FORMAT;
        $secretKey = $this->SECRET_KEY;
        $voicedata = file_get_contents($filepath);
        $datalen = strlen($voicedata);
        $cutlegth = $this->CUTLENGTH;
        $seq = 0;
        while ($datalen > 0) {
            $end = 0;
            if ($datalen < $cutlegth)
                $end = 1;
            $serverUrl = "http://asr.cloud.tencent.com/asr/v1/";
            $reqArr['end'] = $end;
            $reqArr['seq'] = $seq;
            $serverUrl .= $reqArr['appid'] . "?";
            $serverUrl .= "projectid=" . $reqArr['projectid'] . "&";
            if (isset ($reqArr['template_name'])) {
                $serverUrl .= "template_name=" . $reqArr['template_name'] . "&";
            }
            $serverUrl .= "sub_service_type=" . $reqArr['sub_service_type'] . "&";
            $serverUrl .= "engine_model_type=" . $reqArr['engine_model_type'] . "&";
            $serverUrl .= "res_type=" . $reqArr['res_type'] . "&";
            $serverUrl .= "result_text_format=" . $reqArr['result_text_format'] . "&";
            $serverUrl .= "voice_id=" . $reqArr['voice_id'] . "&";
            $serverUrl .= "seq=" . $seq . "&";
            $serverUrl .= "end=" . $end . "&";
            $serverUrl .= "timeout=" . $reqArr['timeout'] . "&";
            $serverUrl .= "source=" . $reqArr['source'] . "&";
            $serverUrl .= "secretid=" . $reqArr['secretid'] . "&";
            $serverUrl .= "timestamp=" . $reqArr['timestamp'] . "&";
            $serverUrl .= "expired=" . $reqArr['expired'] . "&";
            $serverUrl .= "nonce=" . $reqArr['nonce'] . "&";
            $serverUrl .= "needvad=" . $reqArr['needvad'] . "&";
            $serverUrl .= "voice_format=" . $reqArr['voice_format'];

            $autho = createSign($reqArr, "POST", "asr.cloud.tencent.com", "/asr/v1/", $secretKey);
            if ($datalen < $cutlegth) {
                $data = file_get_contents($filepath, NULL, NULL, $seq * $cutlegth, $cutlegth);
            } else {
                $data = file_get_contents($filepath, NULL, NULL, $seq * $cutlegth, $cutlegth);
            }
            $seq = $seq + 1;
            $datalen = $datalen - $cutlegth;
            /*echo "datalen :" . $datalen;*/

            $header = array(
                'Authorization: ' . $autho,
                'Content-Length: ' . strlen($data),
            );

            $rsp_str = "";
            $http_code = -1;
            $ret = http_curl_exec($serverUrl, $data, $rsp_str, $http_code, 'POST', 10, array(), $header);
            if ($ret != 0) {
                echo "http_curl_exec failed \n";
                return false;
            }
            // echo "seq : \n".$seq."\n";
            // echo "http_code : \n".$http_code."\n";
            if ($printCutResponse)
                echo "rsp_str : \n" . $rsp_str . "\n";
        }
        return $rsp_str;
    }
}