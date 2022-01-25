<?php

namespace FLW_SDK;

Trait FlutterwaveEventTracker
{

    static $time_start = 0;
    static $response_time = 0;
    static $publicKey = "";

    static function setPublicKey($key)
    {
        self::$publicKey = $key;
    }

    static function startRecording()
    {
        self::$time_start = microtime(true);
    }

    static function setResponseTime()
    {
        self::$response_time = microtime(true) - self::$time_start;
    }

    static function sendAnalytics($title)
    {
        if (self::$response_time <= 0)
            self::setResponseTime();

        $url = "https://kgelfdz7mf.execute-api.us-east-1.amazonaws.com/staging/sendevent";

        $data = [
            "publicKey" => self::$publicKey,
            "language" => "PHP V3 - WordPress",
            "version" => "1.1.0",
            "title" => $title,
            "message" => self::$response_time
        ];

        $response = wp_remote_post($url, $data);

        self::resetTime();
    }

    private static function resetTime() {
        self::$time_start = 0;
        self::$response_time = 0;
    }
}