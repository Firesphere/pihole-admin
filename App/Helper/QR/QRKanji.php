<?php

namespace App\Helper\QR;

class QRKanji extends QRData
{
    public function __construct($data)
    {
        parent::__construct(QR_MODE_KANJI, $data);
    }

    public function write(&$buffer)
    {
        $data = $this->getData();

        $i = 0;

        while ($i + 1 < strlen((string)$data)) {
            $c = ((0xff & ord($data[$i])) << 8) | (0xff & ord($data[$i + 1]));

            if (0x8140 <= $c && $c <= 0x9FFC) {
                $c -= 0x8140;
            } elseif (0xE040 <= $c && $c <= 0xEBBF) {
                $c -= 0xC140;
            } else {
                trigger_error("illegal char at " . ($i + 1) . "/$c", E_USER_ERROR);
            }

            $c = (($c >> 8) & 0xff) * 0xC0 + ($c & 0xff);

            $buffer->put($c, 13);

            $i += 2;
        }

        if ($i < strlen((string)$data)) {
            trigger_error("illegal char at " . ($i + 1), E_USER_ERROR);
        }
    }

    public function getLength()
    {
        return floor(strlen((string)$this->getData()) / 2);
    }
}
