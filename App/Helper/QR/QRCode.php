<?php

//---------------------------------------------------------------
// QRCode for PHP5
//
// Copyright (c) 2009 Kazuhiko Arase
//
// URL: http://www.d-project.com/
//
// Licensed under the MIT license:
//   http://www.opensource.org/licenses/mit-license.php
//
// The word "QR Code" is registered trademark of
// DENSO WAVE INCORPORATED
//   http://www.denso-wave.com/qrcode/faqpatent-e.html
//
//---------------------------------------------------------------------

namespace App\Helper\QR;

use App\Helper\QR;

class QRCode
{
    protected $typeNumber;

    protected $modules;

    protected $moduleCount;

    protected $errorCorrectLevel;

    protected $qrDataList;

    public function __construct($typeNumber = 1, $errorCorrectLevel = QR_ERROR_CORRECT_LEVEL_H, $qrDataList = [])
    {
        $this->typeNumber = $typeNumber;
        $this->errorCorrectLevel = $errorCorrectLevel;
        $this->qrDataList = $qrDataList;
    }

    public static function getMinimumQRCode($data, $errorCorrectLevel)
    {
        $mode = QR\QRUtil::getMode($data);

        $qr = new self(1, $errorCorrectLevel);
        $qr->addData($data, $mode);

        $qrData = $qr->getData(0);
        $length = $qrData->getLength();

        for ($typeNumber = 1; $typeNumber <= 40; $typeNumber++) {
            if ($length <= QR\QRUtil::getMaxLength($typeNumber, $mode, $errorCorrectLevel)) {
                $qr->setTypeNumber($typeNumber);
                break;
            }
        }

        $qr->make();

        return $qr;
    }

    public function addData($data, $mode = 0)
    {
        if ($mode === 0) {
            $mode = QR\QRUtil::getMode($data);
        }

        switch ($mode) {
            case QR_MODE_NUMBER:
                $d = new QR\QRNumber($data);
                $this->addDataImpl($d);
                break;

            case QR_MODE_ALPHA_NUM:
                $d = new QRAlphaNum($data);
                $this->addDataImpl($d);
                break;

            case QR_MODE_8BIT_BYTE:
                $d = new QR8BitByte($data);
                $this->addDataImpl($d);
                break;

            case QR_MODE_KANJI:
                $d = new QRKanji($data);
                $this->addDataImpl($d);
                break;

            default:
                trigger_error("mode:$mode", E_USER_ERROR);
        }
    }

    public function addDataImpl($qrData)
    {
        $this->qrDataList[] = $qrData;
    }

    public function getData($index)
    {
        return $this->qrDataList[$index];
    }

    public function make()
    {
        $this->makeImpl(false, $this->getBestMaskPattern());
    }

    public function makeImpl($test, $maskPattern)
    {
        $this->moduleCount = $this->typeNumber * 4 + 17;

        $this->modules = array();
        for ($i = 0; $i < $this->moduleCount; $i++) {
            $this->modules[] = $this->createNullArray($this->moduleCount);
        }

        $this->setupPositionProbePattern(0, 0);
        $this->setupPositionProbePattern($this->moduleCount - 7, 0);
        $this->setupPositionProbePattern(0, $this->moduleCount - 7);

        $this->setupPositionAdjustPattern();
        $this->setupTimingPattern();

        $this->setupTypeInfo($test, $maskPattern);

        if ($this->typeNumber >= 7) {
            $this->setupTypeNumber($test);
        }

        $dataArray = $this->qrDataList;

        $data = $this->createData($this->typeNumber, $this->errorCorrectLevel, $dataArray);

        $this->mapData($data, $maskPattern);
    }

    public function createNullArray($length)
    {
        $nullArray = array();
        for ($i = 0; $i < $length; $i++) {
            $nullArray[] = null;
        }

        return $nullArray;
    }

    public function setupPositionProbePattern($row, $col)
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                if ($row + $r <= -1 || $this->moduleCount <= $row + $r
                    || $col + $c <= -1 || $this->moduleCount <= $col + $c
                ) {
                    continue;
                }

                $this->modules[$row + $r][$col + $c] =
                    (0 <= $r && $r <= 6 && ($c === 0 || $c === 6))
                    || (0 <= $c && $c <= 6 && ($r === 0 || $r === 6))
                    || (2 <= $r && $r <= 4 && 2 <= $c && $c <= 4);
            }
        }
    }

    public function setupPositionAdjustPattern()
    {
        $pos = QR\QRUtil::getPatternPosition($this->typeNumber);

        foreach ($pos as $iValue) {
            foreach ($pos as $jValue) {
                $row = $iValue;
                $col = $jValue;

                if ($this->modules[$row][$col] !== null) {
                    continue;
                }

                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $this->modules[$row + $r][$col + $c] =
                            $r === -2 || $r === 2 || $c === -2 || $c === 2 || ($r === 0 && $c === 0);
                    }
                }
            }
        }
    }

    public function setupTimingPattern()
    {
        for ($i = 8; $i < $this->moduleCount - 8; $i++) {
            if ($this->modules[$i][6] !== null || $this->modules[6][$i] !== null) {
                continue;
            }

            $this->modules[$i][6] = ($i % 2 === 0);
            $this->modules[6][$i] = ($i % 2 === 0);
        }
    }

    public function setupTypeInfo($test, $maskPattern)
    {
        $data = ($this->errorCorrectLevel << 3) | $maskPattern;
        $bits = QR\QRUtil::getBCHTypeInfo($data);

        for ($i = 0; $i < 15; $i++) {
            $mod = (!$test && (($bits >> $i) & 1) === 1);

            if ($i < 6) {
                $this->modules[$i][8] = $mod;
            } elseif ($i < 8) {
                $this->modules[$i + 1][8] = $mod;
            } else {
                $this->modules[$this->moduleCount - 15 + $i][8] = $mod;
            }

            if ($i < 8) {
                $this->modules[8][$this->moduleCount - $i - 1] = $mod;
            } elseif ($i < 9) {
                $this->modules[8][15 - $i - 1 + 1] = $mod;
            } else {
                $this->modules[8][15 - $i - 1] = $mod;
            }
        }

        $this->modules[$this->moduleCount - 8][8] = !$test;
    }

    // used for converting fg/bg colors (e.g. #0000ff = 0x0000FF)
    // added 2015.07.27 ~ DoktorJ

    public function setupTypeNumber($test)
    {
        $bits = QR\QRUtil::getBCHTypeNumber($this->typeNumber);

        for ($i = 0; $i < 18; $i++) {
            $mod = (!$test && (($bits >> $i) & 1) === 1);
            $this->modules[(int)floor($i / 3)][$i % 3 + $this->moduleCount - 8 - 3] = $mod;
            $this->modules[$i % 3 + $this->moduleCount - 8 - 3][floor($i / 3)] = $mod;
        }
    }

    public function createData($typeNumber, $errorCorrectLevel, $dataArray)
    {
        $rsBlocks = QR\QRRSBlock::getRSBlocks($typeNumber, $errorCorrectLevel);

        $buffer = new QRBitBuffer();
        foreach ($dataArray as $iValue) {
            /** @protected \QRData $data */
            $data = $iValue;
            $buffer->put($data->getMode(), 4);
            $buffer->put($data->getLength(), $data->getLengthInBits($typeNumber));
            $data->write($buffer);
        }

        $totalDataCount = 0;
        foreach ($rsBlocks as $iValue) {
            $totalDataCount += $iValue->getDataCount();
        }

        if ($buffer->getLengthInBits() > $totalDataCount * 8) {
            trigger_error("code length overflow. ("
                . $buffer->getLengthInBits()
                . ">"
                . $totalDataCount * 8
                . ")", E_USER_ERROR);
        }

        // end code.
        if ($buffer->getLengthInBits() + 4 <= $totalDataCount * 8) {
            $buffer->put(0, 4);
        }

        // padding
        while ($buffer->getLengthInBits() % 8 !== 0) {
            $buffer->putBit(false);
        }

        // padding
        while (true) {
            if ($buffer->getLengthInBits() >= $totalDataCount * 8) {
                break;
            }
            $buffer->put(QR_PAD0, 8);

            if ($buffer->getLengthInBits() >= $totalDataCount * 8) {
                break;
            }
            $buffer->put(QR_PAD1, 8);
        }

        return $this->createBytes($buffer, $rsBlocks);
    }

    public function getDataCount()
    {
        return count($this->qrDataList);
    }

    /**
     * @param QRBitBuffer $buffer
     * @param QRRSBlock[] $rsBlocks
     *
     * @return array
     */
    public function createBytes(&$buffer, &$rsBlocks)
    {
        $offset = 0;

        $maxDcCount = 0;
        $maxEcCount = 0;

        $dcdata = $this->createNullArray(count($rsBlocks));
        $ecdata = $this->createNullArray(count($rsBlocks));

        $rsBlockCount = count($rsBlocks);
        for ($r = 0; $r < $rsBlockCount; $r++) {
            $dcCount = $rsBlocks[$r]->getDataCount();
            $ecCount = $rsBlocks[$r]->getTotalCount() - $dcCount;

            $maxDcCount = max($maxDcCount, $dcCount);
            $maxEcCount = max($maxEcCount, $ecCount);

            $dcdata[$r] = $this->createNullArray($dcCount);
            foreach ($dcdata[$r] as $i => $iValue) {
                $bdata = $buffer->getBuffer();
                $dcdata[$r][$i] = 0xff & $bdata[$i + $offset];
            }
            $offset += $dcCount;

            $rsPoly = QR\QRUtil::getErrorCorrectPolynomial($ecCount);
            $rawPoly = new QR\QRPolynomial($dcdata[$r], $rsPoly->getLength() - 1);

            $modPoly = $rawPoly->mod($rsPoly);
            $ecdata[$r] = $this->createNullArray($rsPoly->getLength() - 1);

            foreach ($ecdata[$r] as $i => $iValue) {
                $modIndex = $i + $modPoly->getLength() - count($ecdata[$r]);
                $ecdata[$r][$i] = ($modIndex >= 0) ? $modPoly->get($modIndex) : 0;
            }
        }

        $totalCodeCount = 0;
        foreach ($rsBlocks as $rsBlock) {
            $totalCodeCount += $rsBlock->getTotalCount();
        }

        $data = $this->createNullArray($totalCodeCount);

        $index = 0;

        for ($i = 0; $i < $maxDcCount; $i++) {
            for ($r = 0; $r < $rsBlockCount; $r++) {
                if ($i < count($dcdata[$r])) {
                    $data[$index++] = $dcdata[$r][$i];
                }
            }
        }

        for ($i = 0; $i < $maxEcCount; $i++) {
            for ($r = 0; $r < $rsBlockCount; $r++) {
                if ($i < count($ecdata[$r])) {
                    $data[$index++] = $ecdata[$r][$i];
                }
            }
        }

        return $data;
    }

    public function mapData(&$data, $maskPattern)
    {
        $inc = -1;
        $row = $this->moduleCount - 1;
        $bitIndex = 7;
        $byteIndex = 0;

        for ($col = $this->moduleCount - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }

            while (true) {
                for ($c = 0; $c < 2; $c++) {
                    if ($this->modules[$row][$col - $c] === null) {
                        $dark = false;

                        if ($byteIndex < count($data)) {
                            $dark = ((($data[$byteIndex] >> $bitIndex) & 1) === 1);
                        }

                        if (QR\QRUtil::getMask($maskPattern, $row, $col - $c)) {
                            $dark = !$dark;
                        }

                        $this->modules[$row][$col - $c] = $dark;
                        $bitIndex--;

                        if ($bitIndex === -1) {
                            $byteIndex++;
                            $bitIndex = 7;
                        }
                    }
                }

                $row += $inc;

                if ($row < 0 || $this->moduleCount <= $row) {
                    $row -= $inc;
                    $inc = -$inc;
                    break;
                }
            }
        }
    }

    public function getBestMaskPattern()
    {
        $minLostPoint = 0;
        $pattern = 0;

        for ($i = 0; $i < 8; $i++) {
            $this->makeImpl(true, $i);

            $lostPoint = QR\QRUtil::getLostPoint($this);

            if ($i === 0 || $minLostPoint > $lostPoint) {
                $minLostPoint = $lostPoint;
                $pattern = $i;
            }
        }

        return $pattern;
    }

    public function getTypeNumber()
    {
        return $this->typeNumber;
    }

    public function setTypeNumber($typeNumber)
    {
        $this->typeNumber = $typeNumber;
    }

    public function getErrorCorrectLevel()
    {
        return $this->errorCorrectLevel;
    }

    public function setErrorCorrectLevel($errorCorrectLevel)
    {
        $this->errorCorrectLevel = $errorCorrectLevel;
    }

    public function clearData()
    {
        $this->qrDataList = array();
    }

    public function createImage($size = 2, $margin = 2, $fg = 0x000000, $bg = 0xFFFFFF, $bgtrans = false)
    {
        // size/margin EC
        if (!is_numeric($size)) {
            $size = 2;
        }
        if (!is_numeric($margin)) {
            $margin = 2;
        }
        if ($size < 1) {
            $size = 1;
        }
        if ($margin < 0) {
            $margin = 0;
        }

        $image_size = $this->getModuleCount() * $size + $margin * 2;

        $image = imagecreatetruecolor($image_size, $image_size);

        // fg/bg EC
        if ($fg < 0 || $fg > 0xFFFFFF) {
            $fg = 0x0;
        }
        if ($bg < 0 || $bg > 0xFFFFFF) {
            $bg = 0xFFFFFF;
        }

        // convert hexadecimal RGB to arrays for imagecolorallocate
        $fgrgb = $this->hex2rgb($fg);
        $bgrgb = $this->hex2rgb($bg);

        // replace $black and $white with $fgc and $bgc
        $fgc = imagecolorallocate($image, $fgrgb['r'], $fgrgb['g'], $fgrgb['b']);
        $bgc = imagecolorallocate($image, $bgrgb['r'], $bgrgb['g'], $bgrgb['b']);
        if ($bgtrans) {
            imagecolortransparent($image, $bgc);
        }

        // update $white to $bgc
        imagefilledrectangle($image, 0, 0, $image_size, $image_size, $bgc);

        for ($r = 0; $r < $this->getModuleCount(); $r++) {
            for ($c = 0; $c < $this->getModuleCount(); $c++) {
                if ($this->isDark($r, $c)) {
                    // update $black to $fgc
                    imagefilledrectangle(
                        $image,
                        $margin + $c * $size,
                        $margin + $r * $size,
                        $margin + ($c + 1) * $size - 1,
                        $margin + ($r + 1) * $size - 1,
                        $fgc
                    );
                }
            }
        }

        return $image;
    }

    public function getModuleCount()
    {
        return $this->moduleCount;
    }

    public function hex2rgb($hex = 0x0)
    {
        return [
            'r' => floor($hex / 65536),
            'g' => floor($hex / 256) % 256,
            'b' => $hex % 256
        ];
    }

    // added $fg (foreground), $bg (background), and $bgtrans (use transparent bg) parameters
    // also added some simple error checking on parameters
    // updated 2015.07.27 ~ DoktorJ

    public function isDark($row, $col)
    {
        return $this->modules[$row][$col] ?? false;
    }

    public function printHTML($size = "2px")
    {
        $style = "border-style:none;border-collapse:collapse;margin:0px;padding:0px;";

        $returnStr = "<table style='%s'>%s</table>";

        $tr = '';
        $trStr = "<tr style='%s'>%s</tr>";
        for ($r = 0; $r < $this->getModuleCount(); $r++) {
            $td = '';
            for ($c = 0; $c < $this->getModuleCount(); $c++) {
                $color = $this->isDark($r, $c) ? "#000000" : "#ffffff";
                $td .= sprintf("<td style='%s;width:%s;height:%s;background-color:%s'></td>", $style, $size, $size, $color);
            }

            $tr .= sprintf($trStr, $style, $td);
        }

        return sprintf($returnStr, $style, $tr);
    }

    public function printSVG($size = 2)
    {
        $size = (int)$size;
        $width = $this->getModuleCount() * $size;
        $height = $width;
        $returnStr = '<svg width="%s" height="%s" viewBox="0 0 %s %s" xmlns="http://www.w3.org/2000/svg">%s</svg>';
        $rectStr = '<rect x="%s" y="%s" width="%s" height="%s" fill="%s" shape-rendering="crispEdges"/>';
        $rect = '';
        for ($r = 0; $r < $this->getModuleCount(); $r++) {
            for ($c = 0; $c < $this->getModuleCount(); $c++) {
                $color = $this->isDark($r, $c) ? "#000000" : "#ffffff";
                $rect .= sprintf(
                    $rectStr,
                    ($c * $size),
                    ($r * $size),
                    $size,
                    $size,
                    $color
                );
            }
        }

        return sprintf($returnStr, $width, $height, $width, $height, $rect);
    }
}
