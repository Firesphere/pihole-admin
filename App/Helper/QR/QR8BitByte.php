<?php

namespace App\Helper\QR;

class QR8BitByte extends QRData
{
    public function __construct($data)
    {
        parent::__construct(QR_MODE_8BIT_BYTE, $data);
    }

    public function write(&$buffer)
    {
        $data = $this->getData();
        $length = strlen($data);
        for ($i = 0; $i < $length; $i++) {
            $buffer->put(ord($data[$i]), 8);
        }
    }
}
