<?php

namespace App\Helper\QR;

abstract class QRData
{
    protected $mode;

    protected $data;

    public function __construct($mode, $data)
    {
        $this->mode = $mode;
        $this->data = $data;
    }

    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return strlen((string)$this->getData());
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @param \QRBitBuffer $buffer
     */
    abstract public function write(&$buffer);

    public function getLengthInBits($type)
    {
        if (1 <= $type && $type < 10) {
            // 1 - 9

            switch ($this->mode) {
                case QR_MODE_NUMBER:
                    return 10;
                case QR_MODE_ALPHA_NUM:
                    return 9;
                case QR_MODE_8BIT_BYTE:
                    return 8;
                case QR_MODE_KANJI:
                    return 8;
                default:
                    trigger_error("mode:$this->mode", E_USER_ERROR);
            }
        } elseif ($type < 27) {
            // 10 - 26

            switch ($this->mode) {
                case QR_MODE_NUMBER:
                    return 12;
                case QR_MODE_ALPHA_NUM:
                    return 11;
                case QR_MODE_8BIT_BYTE:
                    return 16;
                case QR_MODE_KANJI:
                    return 10;
                default:
                    trigger_error("mode:$this->mode", E_USER_ERROR);
            }
        } elseif ($type < 41) {
            // 27 - 40

            switch ($this->mode) {
                case QR_MODE_NUMBER:
                    return 14;
                case QR_MODE_ALPHA_NUM:
                    return 13;
                case QR_MODE_8BIT_BYTE:
                    return 16;
                case QR_MODE_KANJI:
                    return 12;
                default:
                    trigger_error("mode:$this->mode", E_USER_ERROR);
            }
        } else {
            trigger_error("mode:$this->mode", E_USER_ERROR);
        }
    }
}
