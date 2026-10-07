<?php

namespace GlpiPlugin\Os;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * Gera um QR Code PNG usando a biblioteca bacon/bacon-qr-code que já acompanha o GLPI
 * (e a extensão GD, requisito do GLPI). Retorna o caminho de um arquivo temporário.
 */
class QrImage
{
    public static function toTempFile(string $text, int $module_px = 8, int $quiet = 2): ?string
    {
        try {
            $matrix = Encoder::encode($text, ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
        } catch (\Throwable) {
            return null;
        }
        $n    = $matrix->getWidth();
        $size = ($n + 2 * $quiet) * $module_px;
        $img  = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $quiet) * $module_px;
                    $py = ($y + $quiet) * $module_px;
                    imagefilledrectangle($img, $px, $py, $px + $module_px - 1, $py + $module_px - 1, $black);
                }
            }
        }
        $dir = defined('GLPI_TMP_DIR') ? GLPI_TMP_DIR : sys_get_temp_dir();
        $tmp = tempnam($dir, 'osqr');
        if ($tmp === false) {
            return null;
        }
        $file = $tmp . '.png';
        rename($tmp, $file);
        return imagepng($img, $file) ? $file : null;
    }
}
