<?php

namespace App\Helper\QR;

class QRRSBlock
{
    public static $QR_RS_BLOCK_TABLE = array(

        // L
        // M
        // Q
        // H

        // 1
        array(1, 26, 19),
        array(1, 26, 16),
        array(1, 26, 13),
        array(1, 26, 9),

        // 2
        array(1, 44, 34),
        array(1, 44, 28),
        array(1, 44, 22),
        array(1, 44, 16),

        // 3
        array(1, 70, 55),
        array(1, 70, 44),
        array(2, 35, 17),
        array(2, 35, 13),

        // 4
        array(1, 100, 80),
        array(2, 50, 32),
        array(2, 50, 24),
        array(4, 25, 9),

        // 5
        array(1, 134, 108),
        array(2, 67, 43),
        array(2, 33, 15, 2, 34, 16),
        array(2, 33, 11, 2, 34, 12),

        // 6
        array(2, 86, 68),
        array(4, 43, 27),
        array(4, 43, 19),
        array(4, 43, 15),

        // 7
        array(2, 98, 78),
        array(4, 49, 31),
        array(2, 32, 14, 4, 33, 15),
        array(4, 39, 13, 1, 40, 14),

        // 8
        array(2, 121, 97),
        array(2, 60, 38, 2, 61, 39),
        array(4, 40, 18, 2, 41, 19),
        array(4, 40, 14, 2, 41, 15),

        // 9
        array(2, 146, 116),
        array(3, 58, 36, 2, 59, 37),
        array(4, 36, 16, 4, 37, 17),
        array(4, 36, 12, 4, 37, 13),

        // 10
        array(2, 86, 68, 2, 87, 69),
        array(4, 69, 43, 1, 70, 44),
        array(6, 43, 19, 2, 44, 20),
        array(6, 43, 15, 2, 44, 16),

        // 11
        array(4, 101, 81),
        array(1, 80, 50, 4, 81, 51),
        array(4, 50, 22, 4, 51, 23),
        array(3, 36, 12, 8, 37, 13),

        // 12
        array(2, 116, 92, 2, 117, 93),
        array(6, 58, 36, 2, 59, 37),
        array(4, 46, 20, 6, 47, 21),
        array(7, 42, 14, 4, 43, 15),

        // 13
        array(4, 133, 107),
        array(8, 59, 37, 1, 60, 38),
        array(8, 44, 20, 4, 45, 21),
        array(12, 33, 11, 4, 34, 12),

        // 14
        array(3, 145, 115, 1, 146, 116),
        array(4, 64, 40, 5, 65, 41),
        array(11, 36, 16, 5, 37, 17),
        array(11, 36, 12, 5, 37, 13),

        // 15
        array(5, 109, 87, 1, 110, 88),
        array(5, 65, 41, 5, 66, 42),
        array(5, 54, 24, 7, 55, 25),
        array(11, 36, 12, 7, 37, 13),

        // 16
        array(5, 122, 98, 1, 123, 99),
        array(7, 73, 45, 3, 74, 46),
        array(15, 43, 19, 2, 44, 20),
        array(3, 45, 15, 13, 46, 16),

        // 17
        array(1, 135, 107, 5, 136, 108),
        array(10, 74, 46, 1, 75, 47),
        array(1, 50, 22, 15, 51, 23),
        array(2, 42, 14, 17, 43, 15),

        // 18
        array(5, 150, 120, 1, 151, 121),
        array(9, 69, 43, 4, 70, 44),
        array(17, 50, 22, 1, 51, 23),
        array(2, 42, 14, 19, 43, 15),

        // 19
        array(3, 141, 113, 4, 142, 114),
        array(3, 70, 44, 11, 71, 45),
        array(17, 47, 21, 4, 48, 22),
        array(9, 39, 13, 16, 40, 14),

        // 20
        array(3, 135, 107, 5, 136, 108),
        array(3, 67, 41, 13, 68, 42),
        array(15, 54, 24, 5, 55, 25),
        array(15, 43, 15, 10, 44, 16),

        // 21
        array(4, 144, 116, 4, 145, 117),
        array(17, 68, 42),
        array(17, 50, 22, 6, 51, 23),
        array(19, 46, 16, 6, 47, 17),

        // 22
        array(2, 139, 111, 7, 140, 112),
        array(17, 74, 46),
        array(7, 54, 24, 16, 55, 25),
        array(34, 37, 13),

        // 23
        array(4, 151, 121, 5, 152, 122),
        array(4, 75, 47, 14, 76, 48),
        array(11, 54, 24, 14, 55, 25),
        array(16, 45, 15, 14, 46, 16),

        // 24
        array(6, 147, 117, 4, 148, 118),
        array(6, 73, 45, 14, 74, 46),
        array(11, 54, 24, 16, 55, 25),
        array(30, 46, 16, 2, 47, 17),

        // 25
        array(8, 132, 106, 4, 133, 107),
        array(8, 75, 47, 13, 76, 48),
        array(7, 54, 24, 22, 55, 25),
        array(22, 45, 15, 13, 46, 16),

        // 26
        array(10, 142, 114, 2, 143, 115),
        array(19, 74, 46, 4, 75, 47),
        array(28, 50, 22, 6, 51, 23),
        array(33, 46, 16, 4, 47, 17),

        // 27
        array(8, 152, 122, 4, 153, 123),
        array(22, 73, 45, 3, 74, 46),
        array(8, 53, 23, 26, 54, 24),
        array(12, 45, 15, 28, 46, 16),

        // 28
        array(3, 147, 117, 10, 148, 118),
        array(3, 73, 45, 23, 74, 46),
        array(4, 54, 24, 31, 55, 25),
        array(11, 45, 15, 31, 46, 16),

        // 29
        array(7, 146, 116, 7, 147, 117),
        array(21, 73, 45, 7, 74, 46),
        array(1, 53, 23, 37, 54, 24),
        array(19, 45, 15, 26, 46, 16),

        // 30
        array(5, 145, 115, 10, 146, 116),
        array(19, 75, 47, 10, 76, 48),
        array(15, 54, 24, 25, 55, 25),
        array(23, 45, 15, 25, 46, 16),

        // 31
        array(13, 145, 115, 3, 146, 116),
        array(2, 74, 46, 29, 75, 47),
        array(42, 54, 24, 1, 55, 25),
        array(23, 45, 15, 28, 46, 16),

        // 32
        array(17, 145, 115),
        array(10, 74, 46, 23, 75, 47),
        array(10, 54, 24, 35, 55, 25),
        array(19, 45, 15, 35, 46, 16),

        // 33
        array(17, 145, 115, 1, 146, 116),
        array(14, 74, 46, 21, 75, 47),
        array(29, 54, 24, 19, 55, 25),
        array(11, 45, 15, 46, 46, 16),

        // 34
        array(13, 145, 115, 6, 146, 116),
        array(14, 74, 46, 23, 75, 47),
        array(44, 54, 24, 7, 55, 25),
        array(59, 46, 16, 1, 47, 17),

        // 35
        array(12, 151, 121, 7, 152, 122),
        array(12, 75, 47, 26, 76, 48),
        array(39, 54, 24, 14, 55, 25),
        array(22, 45, 15, 41, 46, 16),

        // 36
        array(6, 151, 121, 14, 152, 122),
        array(6, 75, 47, 34, 76, 48),
        array(46, 54, 24, 10, 55, 25),
        array(2, 45, 15, 64, 46, 16),

        // 37
        array(17, 152, 122, 4, 153, 123),
        array(29, 74, 46, 14, 75, 47),
        array(49, 54, 24, 10, 55, 25),
        array(24, 45, 15, 46, 46, 16),

        // 38
        array(4, 152, 122, 18, 153, 123),
        array(13, 74, 46, 32, 75, 47),
        array(48, 54, 24, 14, 55, 25),
        array(42, 45, 15, 32, 46, 16),

        // 39
        array(20, 147, 117, 4, 148, 118),
        array(40, 75, 47, 7, 76, 48),
        array(43, 54, 24, 22, 55, 25),
        array(10, 45, 15, 67, 46, 16),

        // 40
        array(19, 148, 118, 6, 149, 119),
        array(18, 75, 47, 31, 76, 48),
        array(34, 54, 24, 34, 55, 25),
        array(20, 45, 15, 61, 46, 16)

    );
    protected $totalCount;
    protected $dataCount;

    public function __construct($totalCount, $dataCount)
    {
        $this->totalCount = $totalCount;
        $this->dataCount = $dataCount;
    }

    public static function getRSBlocks($typeNumber, $errorCorrectLevel)
    {
        $rsBlock = QRRSBlock::getRsBlockTable($typeNumber, $errorCorrectLevel);
        $length = count($rsBlock) / 3;

        $list = array();

        for ($i = 0; $i < $length; $i++) {
            $count = $rsBlock[$i * 3 + 0];
            $totalCount = $rsBlock[$i * 3 + 1];
            $dataCount = $rsBlock[$i * 3 + 2];

            for ($j = 0; $j < $count; $j++) {
                $list[] = new QRRSBlock($totalCount, $dataCount);
            }
        }

        return $list;
    }

    public static function getRsBlockTable($typeNumber, $errorCorrectLevel)
    {
        switch ($errorCorrectLevel) {
            case QR_ERROR_CORRECT_LEVEL_L:
                return self::$QR_RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 0];
            case QR_ERROR_CORRECT_LEVEL_M:
                return self::$QR_RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 1];
            case QR_ERROR_CORRECT_LEVEL_Q:
                return self::$QR_RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 2];
            case QR_ERROR_CORRECT_LEVEL_H:
                return self::$QR_RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 3];
            default:
                trigger_error("tn:$typeNumber/ecl:$errorCorrectLevel", E_USER_ERROR);
        }
    }

    public function getDataCount()
    {
        return $this->dataCount;
    }

    public function getTotalCount()
    {
        return $this->totalCount;
    }
}
