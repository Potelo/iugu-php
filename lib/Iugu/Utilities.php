<?php

class Iugu_Utilities
{
    public static function authFromEnv()
    {
        $apiKey = getenv('IUGU_API_KEY');
        if ($apiKey) {
            Iugu::setApiKey($apiKey);
            Iugu::setLogErrors(getenv("IUGU_LOG_ERRORS"));
        }
    }

    public static function utf8($value)
    {
        return (is_string($value) && mb_detect_encoding($value, 'UTF-8', true) != 'UTF-8') ? utf8_encode($value) : $value;
    }

    public static function convertDateFromISO($datetime)
    {
        return strtotime($datetime);
    }

    public static function convertEpochToISO($epoch)
    {
        return date('c', $epoch);
    }

    public static function arrayToParams($array, $prefix = null)
    {
        if (!is_array($array)) {
            return $array;
        }

        $params = [];

        foreach ($array as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if ($prefix && $k && !is_int($k)) {
                $k = $prefix . '[' . $k . ']';
            } elseif ($prefix) {
                $k = $prefix . '[]';
            }

            if (is_array($v)) {
                $params[] = self::arrayToParams($v, $k);
            } else {
                $params[] = $k . '=' . urlencode($v);
            }
        }

        return implode('&', $params);
    }
}
