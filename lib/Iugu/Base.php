<?php

class IuguAuthenticationException extends Exception
{
}
class IuguRequestException extends Exception
{
}
class IuguObjectNotFound extends Exception
{
}
class IuguException extends Exception
{
}

abstract class IuguResource
{
}

abstract class Iugu
{
    const VERSION = '1.0.6';

    public static $api_key = null;
    public static $api_version = 'v1';
    public static $endpoint = 'https://api.iugu.com';
    public static $log_errors = true;

    public static function getBaseURI()
    {
        return self::$endpoint . '/' . self::$api_version;
    }

    public static function setApiKey($_api_key)
    {
        self::$api_key = $_api_key;
    }

    public static function getApiKey()
    {
        return self::$api_key;
    }

    public static function setLogErrors($trueOrFalse)
    {
        if ($trueOrFalse === 'false') {
            $trueOrFalse = false;
        }

        if ($trueOrFalse === 'true') {
            $trueOrFalse = true;
        }

        self::$log_errors = $trueOrFalse;
    }

    public static function getLogErrors()
    {
        return self::$log_errors;
    }
}
