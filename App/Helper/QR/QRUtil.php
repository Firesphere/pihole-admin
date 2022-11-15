<?php

namespace App\Helper\QR;

use App\Helper\QR;
use App\Helper\QRCode;

class QRUtil
{
    public static $QR_MAX_LENGTH = [
        [[41, 25, 17, 10], [34, 20, 14, 8], [27, 16, 11, 7], [17, 10, 7, 4]],
        [[77, 47, 32, 20], [63, 38, 26, 16], [48, 29, 20, 12], [34, 20, 14, 8]],
        [[127, 77, 53, 32], [101, 61, 42, 26], [77, 47, 32, 20], [58, 35, 24, 15]],
        [[187, 114, 78, 48], [149, 90, 62, 38], [111, 67, 46, 28], [82, 50, 34, 21]],
        [[255, 154, 106, 65], [202, 122, 84, 52], [144, 87, 60, 37], [106, 64, 44, 27]],
        [[322, 195, 134, 82], [255, 154, 106, 65], [178, 108, 74, 45], [139, 84, 58, 36]],
        [[370, 224, 154, 95], [293, 178, 122, 75], [207, 125, 86, 53], [154, 93, 64, 39]],
        [[461, 279, 192, 118], [365, 221, 152, 93], [259, 157, 108, 66], [202, 122, 84, 52]],
        [[552, 335, 230, 141], [432, 262, 180, 111], [312, 189, 130, 80], [235, 143, 98, 60]],
        [[652, 395, 271, 167], [513, 311, 213, 131], [364, 221, 151, 93], [288, 174, 119, 74]]
    ];

    public static $QR_PATTERN_POSITION_TABLE = [
        [],
        [6, 18],
        [6, 22],
        [6, 26],
        [6, 30],
        [6, 34],
        [6, 22, 38],
        [6, 24, 42],
        [6, 26, 46],
        [6, 28, 50],
        [6, 30, 54],
        [6, 32, 58],
        [6, 34, 62],
        [6, 26, 46, 66],
        [6, 26, 48, 70],
        [6, 26, 50, 74],
        [6, 30, 54, 78],
        [6, 30, 56, 82],
        [6, 30, 58, 86],
        [6, 34, 62, 90],
        [6, 28, 50, 72, 94],
        [6, 26, 50, 74, 98],
        [6, 30, 54, 78, 102],
        [6, 28, 54, 80, 106],
        [6, 32, 58, 84, 110],
        [6, 30, 58, 86, 114],
        [6, 34, 62, 90, 118],
        [6, 26, 50, 74, 98, 122],
        [6, 30, 54, 78, 102, 126],
        [6, 26, 52, 78, 104, 130],
        [6, 30, 56, 82, 108, 134],
        [6, 34, 60, 86, 112, 138],
        [6, 30, 58, 86, 114, 142],
        [6, 34, 62, 90, 118, 146],
        [6, 30, 54, 78, 102, 126, 150],
        [6, 24, 50, 76, 102, 128, 154],
        [6, 28, 54, 80, 106, 132, 158],
        [6, 32, 58, 84, 110, 136, 162],
        [6, 26, 54, 82, 110, 138, 166],
        [6, 30, 58, 86, 114, 142, 170]
    ];

    public static function getPatternPosition($typeNumber)
    {
        return self::$QR_PATTERN_POSITION_TABLE[$typeNumber - 1];
    }

    public static function getMaxLength($typeNumber, $mode, $errorCorrectLevel)
    {
        $t = $typeNumber - 1;
        $e = 0;
        $m = 0;

        switch ($errorCorrectLevel) {
            case QR_ERROR_CORRECT_LEVEL_L:
                $e = 0;
                break;
            case QR_ERROR_CORRECT_LEVEL_M:
                $e = 1;
                break;
            case QR_ERROR_CORRECT_LEVEL_Q:
                $e = 2;
                break;
            case QR_ERROR_CORRECT_LEVEL_H:
                $e = 3;
                break;
            default:
                trigger_error("e:$errorCorrectLevel", E_USER_ERROR);
        }

        switch ($mode) {
            case QR_MODE_NUMBER:
                $m = 0;
                break;
            case QR_MODE_ALPHA_NUM:
                $m = 1;
                break;
            case QR_MODE_8BIT_BYTE:
                $m = 2;
                break;
            case QR_MODE_KANJI:
                $m = 3;
                break;
            default:
                trigger_error("m:$mode", E_USER_ERROR);
        }

        return self::$QR_MAX_LENGTH[$t][$e][$m];
    }

    public static function getErrorCorrectPolynomial($errorCorrectLength)
    {
        $a = new QR\QRPolynomial(array(1));

        for ($i = 0; $i < $errorCorrectLength; $i++) {
            $a = $a->multiply(new QR\QRPolynomial(array(1, QRMath::gexp($i))));
        }

        return $a;
    }

    public static function getMask($maskPattern, $i, $j)
    {
        switch ($maskPattern) {
            case QR_MASK_PATTERN000:
                return ($i + $j) % 2 == 0;
            case QR_MASK_PATTERN001:
                return $i % 2 == 0;
            case QR_MASK_PATTERN010:
                return $j % 3 == 0;
            case QR_MASK_PATTERN011:
                return ($i + $j) % 3 == 0;
            case QR_MASK_PATTERN100:
                return (floor($i / 2) + floor($j / 3)) % 2 == 0;
            case QR_MASK_PATTERN101:
                return ($i * $j) % 2 + ($i * $j) % 3 == 0;
            case QR_MASK_PATTERN110:
                return (($i * $j) % 2 + ($i * $j) % 3) % 2 == 0;
            case QR_MASK_PATTERN111:
                return (($i * $j) % 3 + ($i + $j) % 2) % 2 == 0;

            default:
                trigger_error("mask:$maskPattern", E_USER_ERROR);
        }
    }

    /**
     * @param QRCode $qrCode
     *
     * @return float|int
     */
    public static function getLostPoint($qrCode)
    {
        $moduleCount = $qrCode->getModuleCount();

        $lostPoint = 0;


        // LEVEL1

        for ($row = 0; $row < $moduleCount; $row++) {
            for ($col = 0; $col < $moduleCount; $col++) {
                $sameCount = 0;
                $dark = $qrCode->isDark($row, $col);

                for ($r = -1; $r <= 1; $r++) {
                    if ($row + $r < 0 || $moduleCount <= $row + $r) {
                        continue;
                    }

                    for ($c = -1; $c <= 1; $c++) {
                        if (($col + $c < 0 || $moduleCount <= $col + $c) || ($r == 0 && $c == 0)) {
                            continue;
                        }

                        if ($dark == $qrCode->isDark($row + $r, $col + $c)) {
                            $sameCount++;
                        }
                    }
                }

                if ($sameCount > 5) {
                    $lostPoint += (3 + $sameCount - 5);
                }
            }
        }

        // LEVEL2

        for ($row = 0; $row < $moduleCount - 1; $row++) {
            for ($col = 0; $col < $moduleCount - 1; $col++) {
                $count = 0;
                if ($qrCode->isDark($row, $col)) {
                    $count++;
                }
                if ($qrCode->isDark($row + 1, $col)) {
                    $count++;
                }
                if ($qrCode->isDark($row, $col + 1)) {
                    $count++;
                }
                if ($qrCode->isDark($row + 1, $col + 1)) {
                    $count++;
                }
                if ($count == 0 || $count == 4) {
                    $lostPoint += 3;
                }
            }
        }

        // LEVEL3

        for ($row = 0; $row < $moduleCount; $row++) {
            for ($col = 0; $col < $moduleCount - 6; $col++) {
                if ($qrCode->isDark($row, $col)
                    && !$qrCode->isDark($row, $col + 1)
                    && $qrCode->isDark($row, $col + 2)
                    && $qrCode->isDark($row, $col + 3)
                    && $qrCode->isDark($row, $col + 4)
                    && !$qrCode->isDark($row, $col + 5)
                    && $qrCode->isDark($row, $col + 6)
                ) {
                    $lostPoint += 40;
                }
            }
        }

        for ($col = 0; $col < $moduleCount; $col++) {
            for ($row = 0; $row < $moduleCount - 6; $row++) {
                if ($qrCode->isDark($row, $col)
                    && !$qrCode->isDark($row + 1, $col)
                    && $qrCode->isDark($row + 2, $col)
                    && $qrCode->isDark($row + 3, $col)
                    && $qrCode->isDark($row + 4, $col)
                    && !$qrCode->isDark($row + 5, $col)
                    && $qrCode->isDark($row + 6, $col)
                ) {
                    $lostPoint += 40;
                }
            }
        }

        // LEVEL4

        $darkCount = 0;

        for ($col = 0; $col < $moduleCount; $col++) {
            for ($row = 0; $row < $moduleCount; $row++) {
                if ($qrCode->isDark($row, $col)) {
                    $darkCount++;
                }
            }
        }

        $ratio = abs(100 * $darkCount / $moduleCount / $moduleCount - 50) / 5;
        $lostPoint += $ratio * 10;

        return $lostPoint;
    }

    public static function getMode($s)
    {
        if (self::isAlphaNum($s)) {
            if (self::isNumber($s)) {
                return QR_MODE_NUMBER;
            }

            return QR_MODE_ALPHA_NUM;
        }
        if (self::isKanji($s)) {
            return QR_MODE_KANJI;
        }

        return QR_MODE_8BIT_BYTE;
    }

    public static function isAlphaNum($s)
    {
        $length = strlen((string)$s);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($s[$i]);
            if (!(self::toCharCode('0') <= $c && $c <= self::toCharCode('9'))
                && !(self::toCharCode('A') <= $c && $c <= self::toCharCode('Z'))
                && strpos(" $%*+-./:", $s[$i]) === false
            ) {
                return false;
            }
        }

        return true;
    }

    public static function toCharCode($s)
    {
        return ord($s[0]);
    }

    public static function isNumber($s)
    {
        $length = strlen((string)$s);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($s[$i]);
            if (!(self::toCharCode('0') <= $c && $c <= self::toCharCode('9'))) {
                return false;
            }
        }

        return true;
    }

    public static function isKanji($s)
    {
        $data = $s;

        $i = 0;

        while ($i + 1 < strlen((string)$data)) {
            $c = ((0xff & ord($data[$i])) << 8) | (0xff & ord($data[$i + 1]));

            if (!(0x8140 <= $c && $c <= 0x9FFC) && !(0xE040 <= $c && $c <= 0xEBBF)) {
                return false;
            }

            $i += 2;
        }

        return $i >= strlen((string)$data);
    }

    public static function getBCHTypeInfo($data)
    {
        $d = $data << 10;
        while (QRUtil::getBCHDigit($d) - QRUtil::getBCHDigit(QR_G15) >= 0) {
            $d ^= (QR_G15 << (QRUtil::getBCHDigit($d) - QRUtil::getBCHDigit(QR_G15)));
        }

        return (($data << 10) | $d) ^ QR_G15_MASK;
    }

    public static function getBCHDigit($data)
    {
        $digit = 0;

        while ($data != 0) {
            $digit++;
            $data >>= 1;
        }

        return $digit;
    }

    public static function getBCHTypeNumber($data)
    {
        $d = $data << 12;
        while (QRUtil::getBCHDigit($d) - QRUtil::getBCHDigit(QR_G18) >= 0) {
            $d ^= (QR_G18 << (QRUtil::getBCHDigit($d) - QRUtil::getBCHDigit(QR_G18)));
        }

        return ($data << 12) | $d;
    }
}
