<?php

namespace App\Enums;

/**
 * Incoterms 2020, the cost-and-risk allocation between seller and buyer.
 *
 * A shipment's incoterm decides which side of the journey pays for what, so it
 * is part of the record rather than something derivable from the carrier.
 *
 * Only the codes actually used in practice are listed. EXW, DDP and the
 * container-friendly variants are included; the minor variants (FCA/EXW/cip
 * split by mode, CPT/CIP and their rail/air counterparts) are not, because the
 * mode split is exactly the ambiguity these codes exist to remove - a shipment
 * is recorded with the code that actually appeared on its paperwork, and the
 * mode-specific twin would need a second column to disambiguate it.
 */
enum Incoterm: string
{
    /** Ex Works. */
    case Exw = 'EXW';

    /** Free Carrier. */
    case Fca = 'FCA';

    /** Free Alongside Ship. */
    case Fas = 'FAS';

    /** Free On Board. */
    case Fob = 'FOB';

    /** Cost, Insurance and Freight. */
    case Cif = 'CIF';

    /** Cost and Freight. */
    case Cfr = 'CFR';

    /** Carriage and Insurance Paid To. */
    case Cip = 'CIP';

    /** Carriage Paid To. */
    case Cpt = 'CPT';

    /** Delivered at Place. */
    case Dap = 'DAP';

    /** Delivered at Terminal. */
    case Dpu = 'DPU';

    /** Delivered Duty Paid. */
    case Ddp = 'DDP';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * A label for the UI.
     */
    public function label(): string
    {
        return $this->value;
    }
}
