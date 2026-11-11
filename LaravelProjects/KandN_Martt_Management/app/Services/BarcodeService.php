<?php

namespace App\Services;

use Milon\Barcode\DNS1D;

class BarcodeService
{
    protected $barcode;

    public function __construct()
    {
        $this->barcode = new DNS1D();
    }

    public function generate(string $code, string $type = 'C128')
    {
        return $this->barcode->getBarcodePNG($code, $type);
    }

    public function generateSvg(string $code, string $type = 'C128')
    {
        return $this->barcode->getBarcodeSVG($code, $type);
    }

    public function generateHtml(string $code, string $type = 'C128')
    {
        return $this->barcode->getBarcodeHTML($code, $type);
    }

    public function generateProductBarcode(int $productId)
    {
        $code = str_pad($productId, 8, '0', STR_PAD_LEFT);
        $checksum = $this->calculateChecksum($code);
        return $code . $checksum;
    }

    protected function calculateChecksum(string $code): string
    {
        $sum = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $sum += intval($code[$i]) * ($i % 2 === 0 ? 1 : 3);
        }
        $checksum = (10 - ($sum % 10)) % 10;
        return (string) $checksum;
    }
}
