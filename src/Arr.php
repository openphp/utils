<?php

namespace Openphp;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

class Arr
{
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }
        return value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * @param $array
     * @param $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * @param $array
     * @return string
     */
    public static function query($array)
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Determine if the given key exists in the provided array.
     * @param ArrayAccess|array $array
     * @param string|int $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Determine whether the given value is array accessible.
     * @param mixed $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * @param $array
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * @param $array
     * @param $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    /**
     * @param $array
     * @param $keys
     * @return mixed
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * @param $array
     * @param $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;
        $keys     = (array)$keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }

    /**
     * @param $array
     * @return array
     */
    public static function undot($array)
    {
        $results = [];
        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }
        return $results;
    }

    /**
     * @param $array
     * @param $keys
     * @return bool
     */
    public static function hasAny($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }
        $keys = (array)$keys;
        if (!$array) {
            return false;
        }
        if ($keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $array
     * @param $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array)$keys;
        if (!$array || $keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            $subKeyArray = $array;
            if (static::exists($array, $key)) {
                continue;
            }
            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param array $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * @param $array
     * @param $value
     * @param $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = [];
        list($value, $key) = static::explodePluckParameters($value, $key);
        foreach ($array as $item) {
            $itemValue = static::get($item, $value);
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::get($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string)$itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }
        return $results;
    }

    /**
     * @param $array
     * @param $value
     * @param $key
     * @return array|mixed
     */
    public static function prepend($array, $value, $key = null)
    {
        if (func_num_args() == 2) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }

    /**
     * @param $array
     * @param $key
     * @param $default
     * @return array|ArrayAccess|mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);
        return $value;
    }

    /**
     * @param $array
     * @param $number
     * @param $preserveKeys
     * @return array|mixed
     */
    public static function random($array, $number = null, $preserveKeys = false)
    {
        $requested = is_null($number) ? 1 : $number;
        $count     = count($array);
        if ($requested > $count) {
            throw new InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }
        if (is_null($number)) {
            return $array[array_rand($array)];
        }
        if ((int)$number === 0) {
            return [];
        }
        $keys    = array_rand($array, $number);
        $results = [];
        if ($preserveKeys) {
            foreach ((array)$keys as $key) {
                $results[$key] = $array[$key];
            }
        } else {
            foreach ((array)$keys as $key) {
                $results[] = $array[$key];
            }
        }
        return $results;
    }

    /**
     * @param $value
     * @param $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key   = is_null($key) || is_array($key) ? $key : explode('.', $key);
        return [$value, $key];
    }

    /**
     * @param $array
     * @param $seed
     * @return mixed
     */
    public static function shuffle($array, $seed = null)
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * @param $array
     * @return string
     */
    public static function toCssClasses($array)
    {
        $classList = static::wrap($array);
        $classes   = [];
        foreach ($classList as $class => $constraint) {
            if (is_numeric($class)) {
                $classes[] = $constraint;
            } elseif ($constraint) {
                $classes[] = $class;
            }
        }
        return implode(' ', $classes);
    }

    /**
     * @param $array
     * @param callable $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param $array
     * @return array
     */
    public static function whereNotNull($array)
    {
        return static::where($array, function ($value) {
            return !is_null($value);
        });
    }

    /**
     * @param $value
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }

    /**
     * @param array $array
     * @return array
     */
    public static function unique(array $array)
    {
        $result = [];
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $result[$key] = self::unique($item);
            } else {
                $result[$key] = $item;
            }
        }
        if (!self::isAssoc($result)) {
            return array_unique($result);
        }
        return $result;
    }


    /**
     * @param ...$arrays
     * @return array|array[]
     */
    public static function crossJoin(...$arrays)
    {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[]        = $product;
                }
            }
            $results = $append;
        }
        return $results;
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function add($array, $key, $value)
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }
        return $array;
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array|mixed
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }
            unset($keys[$i]);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * Get an item from an array using "dot" notation.
     * @param ArrayAccess|array $array
     * @param string|int|null|Closure $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }
        if (!static::accessible($array)) {
            return $default instanceof Closure ? $default() : $default;
        }
        if (is_null($key)) {
            return $array;
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : ($default instanceof Closure ? $default() : $default);
        }
        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default instanceof Closure ? $default() : $default;
            }
        }

        return $array;
    }

    /**
     * The anonymous function can be used in the array of grouping keys as well:
     * @param array $array
     * @param mixed $key
     * @param array $groups
     * @return array|mixed
     */
    public static function index(array $array, $key, $groups = [])
    {
        $results = [];
        $groups  = (array)$groups;
        foreach ($array as $element) {
            $lastArray = &$results;
            foreach ($groups as $group) {
                $value = static::get($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }
            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = static::get($element, $key);
                if ($value !== null) {
                    if (is_float($value)) {
                        $value = str_replace(',', '.', (string)$value);
                    }
                    $lastArray[$value][] = $element;
                }
            }
            unset($lastArray);
        }
        return $results;
    }

    /**
     * @param $array
     * @param $name
     * @param $keepKeys
     * @return array
     */
    public static function column($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::get($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::get($element, $name);
            }
        }
        return $result;
    }


    /**
     * @param      $array
     * @param      $from
     * @param      $to
     * @param null $group
     * @return array
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key   = static::get($element, $from);
            $value = static::get($element, $to);
            if ($group !== null) {
                $result[static::get($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @param $a
     * @param $b
     * @return mixed|null
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res  = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }
}