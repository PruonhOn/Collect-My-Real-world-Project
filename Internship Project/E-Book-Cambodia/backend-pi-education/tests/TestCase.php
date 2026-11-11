<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Build a small but valid PDF so upload paths exercising the storage
     * service's `%PDF-` magic-byte and minimum-size checks get realistic input.
     */
    protected function fakePdf(int $pages = 4): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count '.$pages.' >>',
        ];

        for ($i = 0; $i < $pages; $i++) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>';
        }

        $pdf = "%PDF-1.3\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 ".($pages + 2)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer'
            ."\n<< /Size ".($pages + 2).' /Root 1 0 R >>'
            ."\nstartxref\n".$xrefStart."\n%%EOF\n";

        return $pdf;
    }
}
