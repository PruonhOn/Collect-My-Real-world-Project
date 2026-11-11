<?php

namespace App\Enums;

/**
 * What a line on a customer's invoice is for.
 *
 * The categories a freight forwarder actually quotes from, rather than a general
 * ledger's. They are a closed vocabulary because a charge is a priced line
 * against a consignment and the price depends on what the line is: ocean freight
 * is quoted per 40ft box and documentation is quoted as a flat fee, and neither
 * is a row the other can be.
 *
 * `other` is the escape and it is a real case rather than a hedge: a demurrage
 * invoice from a terminal arrives with a name nobody has a category for, and
 * forcing it into `handling` would quietly corrupt a margin report. A charge
 * nobody can categorise is worth seeing labelled as such.
 *
 * Deliberately *not* the same vocabulary as `ExpenseCategory`. A charge is what
 * we bill and an expense is what we pay, and the two are not the same list
 * because the two sides of a transaction are not the same: the forwarder pays a
 * terminal for detention but bills the customer for it, while it pays a broker a
 * fee and may never re-charge it. Overlapping the two would make a
 * "what did this cost us" report silently sum the wrong side.
 */
enum ChargeType: string
{
    /** The carriage itself - ocean, air or road freight. */
    case Freight = 'freight';

    /** Loading, unloading, terminal handling, trucking between port and door. */
    case Handling = 'handling';

    /** Bills of lading, certificates of origin, packing lists, cargo plans. */
    case Documentation = 'documentation';

    /** Entry filing, duties and taxes, broker's fee. */
    case Customs = 'customs';

    /** Bonded or free-time storage, whether or not the charge is a penalty. */
    case Storage = 'storage';

    /** Marine cargo, warehouse or transit cover. */
    case Insurance = 'insurance';

    /** Fuel, driver, tolls - a road movement's own costs. */
    case Fuel = 'fuel';

    /**
     * The container is held past its free time.
     *
     * Its own case because it is the single most common dispute in the business
     * and it is billed at a daily rate that escalates. Filing it under `storage`
     * would average it into a rate that means nothing when somebody asks why a
     * consignment cost what it did.
     */
    case Demurrage = 'demurrage';

    /**
     * The box is held outside the terminal past its free time.
     *
     * The same idea against the equipment rather than the cargo, and the reason
     * it is separate from `demurrage` is that it is usually somebody else's
     * problem: a detention fee is very often charged back to the shipper rather
     * than absorbed, and mixing the two hides that.
     */
    case Detention = 'detention';

    /** Anything the rate card does not have a line for. */
    case Other = 'other';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Freight => 'Freight',
            self::Handling => 'Handling',
            self::Documentation => 'Documentation',
            self::Customs => 'Customs',
            self::Storage => 'Storage',
            self::Insurance => 'Insurance',
            self::Fuel => 'Fuel',
            self::Demurrage => 'Demurrage',
            self::Detention => 'Detention',
            self::Other => 'Other',
        };
    }

    /**
     * Whether a charge of this kind is normally measured in time.
     *
     * A `demurrage` line is "four days at $150", and knowing that lets the client
     * label the quantity column "Days" rather than "Quantity" without every form
     * needing a second field for the unit. The server does not enforce it - a
     * quantity is a quantity - but the one place the distinction changes anything
     * a person reads is preserved.
     */
    public function isTimeBased(): bool
    {
        return $this === self::Demurrage || $this === self::Detention || $this === self::Storage;
    }
}
