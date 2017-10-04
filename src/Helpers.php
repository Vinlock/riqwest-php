<?php
/**
 * Created by PhpStorm.
 * User: dak
 * Date: 10/3/17
 * Time: 4:59 PM
 */

namespace NCWest\Riqwest;


class Helpers {

    public static function endsWith($haystack, $needle) {
        if (is_array($needle)) {
            foreach ($needle as $n) {
                $length = strlen($n);
                if (substr($haystack, -$length) == $n) {
                    return true;
                } else {
                    continue;
                }
            }
            return false;
        }

        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Find if string starts with the $needle
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Check if string is JSON
     *
     * @param $string
     * @return bool
     */
    public static function is_json($string) {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

}