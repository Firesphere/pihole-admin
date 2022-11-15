<?php

namespace App\Helper\QR;

class QRBitBuffer
{
    protected $buffer;
    protected $length;

    public function __construct()
    {
        $this->buffer = array();
        $this->length = 0;
    }

    public function getBuffer()
    {
        return $this->buffer;
    }

    public function __toString()
    {
        $buffer = "";
        for ($i = 0; $i < $this->getLengthInBits(); $i++) {
            $buffer .= $this->get($i) ? '1' : '0';
        }

        return $buffer;
    }

    public function getLengthInBits()
    {
        return $this->length;
    }

    public function get($index)
    {
        $bufIndex = (int)floor($index / 8);

        return (($this->buffer[$bufIndex] >> (7 - $index % 8)) & 1) == 1;
    }

    public function put($num, $length)
    {
        for ($i = 0; $i < $length; $i++) {
            $this->putBit((($num >> ($length - $i - 1)) & 1) == 1);
        }
    }

    public function putBit($bit)
    {
        $bufIndex = (int)floor($this->length / 8);
        if (count($this->buffer) <= $bufIndex) {
            $this->buffer[] = 0;
        }

        if ($bit) {
            $this->buffer[$bufIndex] |= (0x80 >> ($this->length % 8));
        }

        $this->length++;
    }
}
