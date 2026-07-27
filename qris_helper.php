<?php
function parseEMV($qris) {
    $data = [];
    $i = 0;
    while ($i < strlen($qris)) {
        $tag = substr($qris, $i, 2);
        $len = intval(substr($qris, $i + 2, 2));
        $val = substr($qris, $i + 4, $len);
        $data[$tag] = $val;
        $i += 4 + $len;
    }
    return $data;
}

function crc_ccitt($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
            $crc &= 0xFFFF;
        }
    }
    return $crc;
}

function generateDynamicQris($qrisString, $amount) {
    if (empty($qrisString) || $amount <= 0) return $qrisString;
    $parsed = parseEMV($qrisString);

    // Tag 54 = Transaction Amount
    $parsed['54'] = (string)$amount;
    
    // Also point of initiation method to dynamic if needed, usually tag 01 from 11 (static) to 12 (dynamic)
    if (isset($parsed['01']) && $parsed['01'] == '11') {
        $parsed['01'] = '12';
    }

    $newQris = '';
    foreach ($parsed as $tag => $val) {
        $newQris .= $tag . sprintf("%02d", strlen($val)) . $val;
    }

    $newQrisNoCRC = preg_replace('/6304.{4}$/', '', $newQris);
    $crc = strtoupper(dechex(crc_ccitt(hex2bin(bin2hex($newQrisNoCRC . '6304')))));
    $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);

    return $newQrisNoCRC . '6304' . $crc;
}
?>
