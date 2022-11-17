<?php

const QR_PAD0 = 0xEC;
const QR_PAD1 = 0x11;
const QR_G15 = (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0);
const QR_G18 = (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0);
const QR_G15_MASK = (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1);

const QR_MODE_NUMBER = 1 << 0;
const QR_MODE_ALPHA_NUM = 1 << 1;
const QR_MODE_8BIT_BYTE = 1 << 2;
const QR_MODE_KANJI = 1 << 3;

//---------------------------------------------------------------
// MaskPattern
//---------------------------------------------------------------

const QR_MASK_PATTERN000 = 0;
const QR_MASK_PATTERN001 = 1;
const QR_MASK_PATTERN010 = 2;
const QR_MASK_PATTERN011 = 3;
const QR_MASK_PATTERN100 = 4;
const QR_MASK_PATTERN101 = 5;
const QR_MASK_PATTERN110 = 6;
const QR_MASK_PATTERN111 = 7;

//---------------------------------------------------------------
// ErrorCorrectLevel

// 7%.
const QR_ERROR_CORRECT_LEVEL_L = 1;
// 15%.
const QR_ERROR_CORRECT_LEVEL_M = 0;
// 25%.
const QR_ERROR_CORRECT_LEVEL_Q = 3;
// 30%.
const QR_ERROR_CORRECT_LEVEL_H = 2;
