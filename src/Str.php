<?php

namespace Openphp;


class Str
{
    /**
     * @param $number
     * @param $length
     * @return string
     */
    public static function zeroPad($number, $length)
    {
        return str_pad($number, $length, '0', \STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    public static function uuid()
    {
        $charid = md5(uniqid(mt_rand(), true) . gethostname());
        $uuid   = substr($charid, 0, 8) . '-'
            . substr($charid, 8, 4) . '-'
            . substr($charid, 12, 4) . '-'
            . substr($charid, 16, 4) . '-'
            . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * 自动转换字符集 支持数组转换
     * @param $string
     * @param $from
     * @param $to
     * @return array|bool|float|int|string
     */
    public static function autoCharset($string, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to   = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($string) || (is_scalar($string) && !is_string($string))) {
            //如果编码相同或者非字符串标量则不转换
            return $string;
        }
        if (is_string($string)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($string, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $string);
            } else {
                return $string;
            }
        } elseif (is_array($string)) {
            foreach ($string as $key => $val) {
                $_key          = self::autoCharset($key, $from, $to);
                $string[$_key] = self::autoCharset($val, $from, $to);
                if ($key != $_key) {
                    unset($string[$key]);
                }

            }
            return $string;
        } else {
            return $string;
        }
    }

    /**
     * 生成Guid主键
     * @return array|string|string[]
     */
    public static function keyGen()
    {
        return str_replace('-', '', static::uuid());
    }

    /**
     * 获取一定范围内的随机数字 位数不足补零
     * @param $min
     * @param $max
     * @return string
     */
    public static function randNumber($min, $max)
    {
        return sprintf("%0" . strlen($max) . "d", mt_rand($min, $max));
    }

    /**
     * 检查字符串是否是UTF8编码
     * @param $str
     * @return Boolean
     */
    public static function isUtf8($str)
    {
        $c    = 0;
        $b    = 0;
        $bits = 0;
        $len  = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }
                if (($i + $bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }
        return true;
    }


    /**
     * 将unicode编码值转换为utf-8编码字符.
     * @param $cp
     * @return string
     */
    public static function utf8Bytes($cp)
    {
        if ($cp > 0x10000) {
            // 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)) .
                chr(0x80 | (($cp & 0x3F000) >> 12)) .
                chr(0x80 | (($cp & 0xFC0) >> 6)) .
                chr(0x80 | ($cp & 0x3F));
        } elseif ($cp > 0x800) {
            // 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)) .
                chr(0x80 | (($cp & 0xFC0) >> 6)) .
                chr(0x80 | ($cp & 0x3F));
        } elseif ($cp > 0x80) {
            // 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)) .
                chr(0x80 | ($cp & 0x3F));
        } else {
            // 1 byte
            return chr($cp);
        }
    }

    /**
     * @param $subject
     * @param $search
     * @return mixed|string
     */
    public static function after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * @param $subject
     * @param $search
     * @return false|mixed|string
     */
    public static function afterLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }
        $position = strrpos($subject, (string)$search);
        if ($position === false) {
            return $subject;
        }
        return substr($subject, $position + strlen($search));
    }

    /**
     * @param $subject
     * @param $search
     * @return mixed|string
     */
    public static function before($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }
        $result = strstr($subject, (string)$search, true);
        return $result === false ? $subject : $result;
    }

    /**
     * @param $subject
     * @param $search
     * @return mixed|string
     */
    public static function beforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }
        $pos = mb_strrpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }
        return static::substr($subject, 0, $pos);
    }

    /**
     * @param $subject
     * @param $from
     * @param $to
     * @return mixed|string
     */
    public static function between($subject, $from, $to)
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return static::beforeLast(static::after($subject, $from), $to);
    }

    /**
     * @param $value
     * @param $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * 获取字符串的长度
     * @param $value
     * @param $encoding
     * @return false|int
     */
    public static function length($value, $encoding = null)
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }

        return mb_strlen($value);
    }

    /**
     * 字符串转小写
     * @param $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * @param $string
     * @param $character
     * @param $index
     * @param $length
     * @param $encoding
     * @return mixed|string
     */
    public static function mask($string, $character, $index, $length = null, $encoding = 'UTF-8')
    {
        if ($character === '') {
            return $string;
        }
        if (is_null($length) && PHP_MAJOR_VERSION < 8) {
            $length = mb_strlen($string, $encoding);
        }
        $segment = mb_substr($string, $index, $length, $encoding);
        if ($segment === '') {
            return $string;
        }
        $start = mb_substr($string, 0, mb_strpos($string, $segment, 0, $encoding), $encoding);
        $end   = mb_substr($string, mb_strpos($string, $segment, 0, $encoding) + mb_strlen($segment, $encoding));
        return $start . str_repeat(mb_substr($character, 0, 1, $encoding), mb_strlen($segment, $encoding)) . $end;
    }

    /**
     * @param $pattern
     * @param $subject
     * @return mixed|string
     */
    public static function match($pattern, $subject)
    {
        preg_match($pattern, $subject, $matches);
        if (!$matches) {
            return '';
        }
        return isset($matches[1]) ? $matches[1] : $matches[0];
    }

    /**
     * @param $value
     * @param $length
     * @param $pad
     * @return string
     */
    public static function padBoth($value, $length, $pad = ' ')
    {
        return str_pad($value, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * @param $value
     * @param $length
     * @param $pad
     * @return string
     */
    public static function padLeft($value, $length, $pad = ' ')
    {
        return str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * @param $value
     * @param $length
     * @param $pad
     * @return string
     */
    public static function padRight($value, $length, $pad = ' ')
    {
        return str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * @param int $length
     * @param string $charlist
     * @return string
     * @throws \Exception
     */
    public static function random($length = 16, $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $chLen = strlen($chars);
        if ($length < 1) {
            // mcrypt_create_iv does not support zero length
            return '';
        } elseif ($chLen < 2) {
            // random_int does not support empty interval
            return str_repeat($chars, $length);
        }
        $str = '';
        if (PHP_VERSION_ID >= 70000) {
            for ($i = 0; $i < $length; $i++) {
                $str .= $chars[random_int(0, $chLen - 1)];
            }
            return $str;
        }

        $bytes = '';
        // slow in PHP 5.3 & Windows
        if (function_exists('openssl_random_pseudo_bytes') && (PHP_VERSION_ID >= 50400 || !defined('PHP_WINDOWS_VERSION_BUILD'))) {
            $bytes = openssl_random_pseudo_bytes($length, $secure);
            if (!$secure) {
                $bytes = '';
            }
        }
        // PHP bug #52523
        $windows = defined('PHP_WINDOWS_VERSION_BUILD');
        if (strlen($bytes) < $length && function_exists('mcrypt_create_iv') && (PHP_VERSION_ID >= 50307 || !$windows)) {
            $bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
        }
        if (strlen($bytes) < $length && !$windows && @is_readable('/dev/urandom')) {
            $bytes = file_get_contents('/dev/urandom', FALSE, NULL, -1, $length);
        }
        if (strlen($bytes) < $length) {
            $rand3 = md5(serialize($_SERVER), TRUE);
            $chars = str_shuffle($chars);
            for ($i = 0; $i < $length; $i++) {
                if ($i % 5 === 0) {
                    list($rand1, $rand2) = explode(' ', microtime());
                    $rand1 += lcg_value();
                }
                $rand1 *= $chLen;
                $str   .= $chars[($rand1 + $rand2 + ord($rand3[$i % strlen($rand3)])) % $chLen];
                $rand1 -= (int)$rand1;
            }
            return $str;
        }
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[($i + ord($bytes[$i])) % $chLen];
        }
        return $str;
    }

    /**
     * @param string $string
     * @param int $times
     * @return string
     */
    public static function repeat($string, $times)
    {
        return str_repeat($string, $times);
    }

    /**
     * @param $search
     * @param array $replace
     * @param $subject
     * @return mixed|string|null
     */
    public static function replaceArray($search, array $replace, $subject)
    {
        $segments = explode($search, $subject);

        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $result .= (array_shift($replace) !== null ? array_shift($replace) : $search) . $segment;
        }

        return $result;
    }

    /**
     * @param $search
     * @param $replace
     * @param $subject
     * @return array|string|string[]
     */
    public static function replace($search, $replace, $subject)
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * @param $search
     * @param $replace
     * @param $subject
     * @return array|mixed|string|string[]
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $search = (string)$search;

        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * @param $search
     * @param $replace
     * @param $subject
     * @return array|mixed|string|string[]
     */
    public static function replaceLast($search, $replace, $subject)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * @param $search
     * @param $subject
     * @param $caseSensitive
     * @return array|string|string[]
     */
    public static function remove($search, $subject, $caseSensitive = true)
    {
        $subject = $caseSensitive
            ? str_replace($search, '', $subject)
            : str_ireplace($search, '', $subject);

        return $subject;
    }

    /**
     * @param $value
     * @param $prefix
     * @return string
     */
    public static function start($value, $prefix)
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }

    /**
     * 字符串转大写
     * @param $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * 转为首字母大写的标题格式
     * @param $value
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * 截取字符串
     * @param $string
     * @param $start
     * @param $length
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * @param $haystack
     * @param $needle
     * @param $offset
     * @param $length
     * @return int
     */
    public static function substrCount($haystack, $needle, $offset = 0, $length = null)
    {
        if (!is_null($length)) {
            return substr_count($haystack, $needle, $offset, $length);
        } else {
            return substr_count($haystack, $needle, $offset);
        }
    }

    /**
     * @param $string
     * @param $replace
     * @param $offset
     * @param $length
     * @return array|string|string[]
     */
    public static function substrReplace($string, $replace, $offset = 0, $length = null)
    {
        if ($length === null) {
            $length = strlen($string);
        }

        return substr_replace($string, $replace, $offset, $length);
    }

    /**
     * @param array $map
     * @param $subject
     * @return string
     */
    public static function swap(array $map, $subject)
    {
        return strtr($subject, $map);
    }

    /**
     * @param $string
     * @return string
     */
    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * @param $string
     * @return array|false|string[]
     */
    public static function ucsplit($string)
    {
        return preg_split('/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param $string
     * @return array|int|string[]
     */
    public static function wordCount($string)
    {
        return str_word_count($string);
    }


    /**
     * @param $value
     * @param $limit
     * @param $end
     * @return mixed|string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * 检查字符串中是否包含某些字符串
     * @param $haystack
     * @param $needles
     * @param $strict
     * @return bool
     */
    public static function contains($haystack, $needles, $strict = true)
    {
        // 不区分大小写的情况下 全部转为小写
        if (!$strict) $haystack = mb_strtolower($haystack);
        // 支持以数组方式传入 needles 检查多个字符串
        foreach ((array)$needles as $needle) {
            if (!$strict) $needle = mb_strtolower($needle);
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查字符串是否以某些字符串开头
     * @param $haystack
     * @param $needles
     * @param $strict
     * @return bool
     */
    public static function startsWith($haystack, $needles, $strict = true)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查字符串是否以某些字符串结尾
     * @param $haystack
     * @param $needles
     * @param $strict
     * @return bool
     */
    public static function endsWith($haystack, $needles, $strict = true)
    {
        foreach ((array)$needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string)$needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * 驼峰转下划线
     * @param $value
     * @param $delimiter
     * @return mixed|string
     */
    public static function snake($value, $delimiter = '_')
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);
            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }
        return $value;
    }

    /**
     * 下划线转驼峰(首字母小写)
     * @param $value
     * @return string
     */
    public static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    /**
     * 下划线转驼峰(首字母大写)
     * @param $value
     * @return array|string|string[]
     */
    public static function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

}