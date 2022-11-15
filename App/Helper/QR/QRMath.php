<?php

namespace App\Helper\QR;

class QRMath
{
    public static $QR_MATH_EXP_TABLE = null;
    public static $QR_MATH_LOG_TABLE = null;

    public static function init()
    {
        self::$QR_MATH_EXP_TABLE = QRMath::createNumArray(256);

        for ($i = 0; $i < 8; $i++) {
            self::$QR_MATH_EXP_TABLE[$i] = 1 << $i;
        }

        for ($i = 8; $i < 256; $i++) {
            self::$QR_MATH_EXP_TABLE[$i] = self::$QR_MATH_EXP_TABLE[$i - 4]
                ^ self::$QR_MATH_EXP_TABLE[$i - 5]
                ^ self::$QR_MATH_EXP_TABLE[$i - 6]
                ^ self::$QR_MATH_EXP_TABLE[$i - 8];
        }

        self::$QR_MATH_LOG_TABLE = QRMath::createNumArray(256);

        for ($i = 0; $i < 255; $i++) {
            self::$QR_MATH_LOG_TABLE[self::$QR_MATH_EXP_TABLE[$i]] = $i;
        }
    }

    public static function createNumArray($length)
    {
        $num_array = array();
        for ($i = 0; $i < $length; $i++) {
            $num_array[] = 0;
        }

        return $num_array;
    }

    public static function glog($n)
    {
        if ($n < 1) {
            trigger_error("log($n)", E_USER_ERROR);
        }

        return self::$QR_MATH_LOG_TABLE[$n];
    }

    public static function gexp($n)
    {
        while ($n < 0) {
            $n += 255;
        }

        while ($n >= 256) {
            $n -= 255;
        }

        return self::$QR_MATH_EXP_TABLE[$n];
    }
}
