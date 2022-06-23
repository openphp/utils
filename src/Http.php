<?php

namespace Openphp;

class Http
{
    /**
     * @param $url
     * @param $params
     * @return array
     */
    public static function get($url, $params = [])
    {
        return static::request('GET', $url, $params);
    }

    /**
     * @param $url
     * @param $params
     * @return array
     */
    public static function post($url, $params = [])
    {
        return static::request('POST', $url, $params);
    }

    /**
     * @param $url
     * @param $name
     * @param $file
     * @return array
     */
    public static function upload($url, $name, $file)
    {
        return static::request('POST', $url, [$name => $file], 120);
    }

    /**
     * @param $url
     * @param $params
     * @return array
     */
    public static function put($url, $params = [])
    {
        return static::request('PUT', $url, $params);
    }

    /**
     * @param $url
     * @param $params
     * @return array
     */
    public static function delete($url, $params = [])
    {
        return static::request('DELETE', $url, $params);
    }

    /**
     * @param $url
     * @param $json
     * @return array
     */
    public static function postJson($url, $json)
    {
        return static::request('POST', $url, $json, ["Content-Type: application/json"]);
    }


    /**
     * @param $method
     * @param $url
     * @param $params
     * @param $timeout
     * @param $header
     * @param $userAgent
     * @param $option
     * @return array
     */
    public static function request($method, $url, $params, $timeout = 30, $header = [], $userAgent = '', $option = [])
    {
        $ch = curl_init();
        if (substr($url, 0, 8) == 'https://') {
            $option[CURLOPT_SSL_VERIFYPEER] = false;
            $option[CURLOPT_SSL_VERIFYHOST] = 2;
        }
        $option[CURLOPT_FOLLOWLOCATION] = true;
        $option[CURLOPT_MAXREDIRS]      = 5;
        $requestParams                  = [];
        //判断是否存在文件
        foreach ($params as $name => $file) {
            if (is_file($file)) {
                if (class_exists('CURLFile')) {
                    $requestParams[$name] = new \CURLFile(realpath($file));
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        $option[CURLOPT_SAFE_UPLOAD] = false;
                    }
                    $option[CURLOPT_POSTFIELDS] = [$name => '@' . realpath($file)];
                }
            }
        }
        if (empty($requestParams)) {
            $requestParams = is_array($params) ? http_build_query($params) : $params;
        }
        switch (strtoupper($method)) {
            case 'POST':
                $option[CURLOPT_POST]       = true;
                $option[CURLOPT_POSTFIELDS] = $requestParams;
                $option[CURLOPT_URL]        = $url;
                break;
            case 'PUT':
                $option[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $option[CURLOPT_POSTFIELDS]    = $requestParams;
                $option[CURLOPT_URL]           = $url;
                break;
            case 'DELETE':
                $option[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $option[CURLOPT_POSTFIELDS]    = $requestParams;
                $option[CURLOPT_URL]           = $url;
                break;
            default:
                $extStr              = (strpos($url, '?') !== false) ? '&' : '?';
                $option[CURLOPT_URL] = $url . ($requestParams ? ($extStr . $requestParams) : '');
                break;
        }
        if ($userAgent) {
            $option[CURLOPT_USERAGENT] = $userAgent;
        }
        $option[CURLOPT_CONNECTTIMEOUT] = $timeout;
        $option[CURLOPT_TIMEOUT]        = $timeout;

        $option[CURLOPT_RETURNTRANSFER] = true;
        if ($header) {
            $option[CURLOPT_HTTPHEADER] = $header;
        }
        curl_setopt_array($ch, $option);
        $result = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $return = [
                'errno'  => curl_errno($ch),
                'msg'    => curl_error($ch),
                'info'   => curl_getinfo($ch),
                'result' => '',
            ];
            curl_close($ch);
            return $return;
        } else {
            $info = curl_getinfo($ch);
            curl_close($ch);
            return [
                'errno'  => 0,
                'msg'    => '',
                'info'   => $info,
                'result' => $result,
            ];
        }

    }


    /**
     * @param $url
     * @param $fileName
     * @param array $option
     * @return array
     */
    public function fileDownload($url, $fileName, $option = [])
    {
        $ch                  = curl_init();
        $option[CURLOPT_URL] = $url;
        if (substr($url, 0, 8) == 'https://') {
            $option[CURLOPT_SSL_VERIFYPEER] = false;
            $option[CURLOPT_SSL_VERIFYHOST] = 2;
        }
        $option[CURLOPT_HEADER]         = 0;
        $option[CURLOPT_RETURNTRANSFER] = 1;
        $option[CURLOPT_NOPROGRESS]     = false;
        $option[CURLOPT_FOLLOWLOCATION] = true;
        $option[CURLOPT_BUFFERSIZE]     = 64000;
        $option[CURLOPT_POST]           = false;
        $fp                             = fopen($fileName, 'wb');
        $option[CURLOPT_FILE]           = $fp;
        curl_setopt_array($ch, $option);
        $result   = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        if (curl_errno($ch) || $curlInfo['http_code'] != 200) {
            $return = [
                'errno'  => curl_errno($ch),
                'msg'    => curl_error($ch),
                'info'   => curl_getinfo($ch),
                'result' => '',
            ];
            curl_close($ch);
            @unlink($fileName);
            return $return;
        } else {
            $info = curl_getinfo($ch);
            curl_close($ch);
            if ($fileName) {
                isset($fp) && is_resource($fp) && fclose($fp);
                return [
                    'errno'  => 0,
                    'msg'    => '',
                    'info'   => $info,
                    'result' => $fileName,
                ];
            } else {
                return [
                    'errno'  => 0,
                    'msg'    => '',
                    'info'   => $info,
                    'result' => $result,
                ];
            }
        }
    }
}