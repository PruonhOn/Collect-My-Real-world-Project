<?php

namespace App\Services\Support;

use setasign\Fpdi\Fpdi;

/**
 * FPDI exposes its low-level parser only through protected methods. The
 * outline reader needs that raw parser to walk the `/Outlines` tree, so this
 * thin subclass opens the one accessor without duplicating FPDI internals.
 */
class OutlineReadableFpdi extends Fpdi
{
    public function parsedDocument(): object
    {
        return $this->getPdfReader($this->currentReaderId)->getParser();
    }
}
