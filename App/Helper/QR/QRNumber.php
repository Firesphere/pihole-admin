<?php

namespace App\Helper\QR;

class QRNumber extends QRData
{
    public function __construct($data)
    {
        parent::__construct(QR_MODE_NUMBER, $data);
    }

    public function write(&$buffer)
    {
        $data = $this->getData();

        $i = 0;

        $length = strlen((string)$data);
        while ($i + 2 < $length) {
            $num = self::parseInt(substr($data, $i, 3));
            $buffer->put($num, 10);
            $i += 3;
        }

        if ($i < $length) {
            if (strlen((string)$data) - $i === 1) {
                $num = self::parseInt(substr($data, $i, $i + 1));
                $buffer->put($num, 4);
            } elseif (strlen((string)$data) - $i === 2) {
                $num = self::parseInt(substr($data, $i, $i + 2));
                $buffer->put($num, 7);
            }
        }
    }

    public static function parseInt($s)
    {
        $num = 0;
        $length = strlen((string)$s);
        for ($i = 0; $i < $length; $i++) {
            $num = $num * 10 + self::parseIntAt(ord($s[$i]));
        }

        return $num;
    }

    public static function parseIntAt($c)
    {
        if (QRUtil::toCharCode('0') <= $c && $c <= QRUtil::toCharCode('9')) {
            return $c - QRUtil::toCharCode('0');
        }

        trigger_error("illegal char : $c", E_USER_ERROR);
    }
}
