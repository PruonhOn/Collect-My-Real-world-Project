<?php

namespace App\Enums;

/**
 * What an outgoing cost was for.
 *
 * The mirror of `ChargeType` and deliberately not the same list, because an
 * expense and a charge are two sides of a trade and the forwarder is not on the
 * same side of both. A terminal's detention fee arrives as an expense and is
 * usually recharged as a charge, so the two vocabularies overlap heavily - but a
 * broker's entry filing fee is paid and never recharged, and a customs duty is
 * money the forwarder holds and passes on, which is neither a cost nor revenue.
 *
 * Which is why `duty` is here and `customs` is not: duties and taxes are
 * collected from the customer and remitted to the authority, so counting them as
 * the forwarder's cost would make every consignment look unprofitable by exactly
 * the amount of the tax. They are tracked so they can be reconciled against what
 * was collected, and they are excluded from anything that claims to be profit.
 *
 * A closed vocabulary rather than free text, for the same reason as every other
 * one in this schema: an expense report that can only be grouped by a
 * misspelling is not a report.
 */
enum ExpenseCategory: string
{
    /** Freight bought in - a carrier's invoice for a leg we sold on. */
    case Freight = 'freight';

    /**
     * Duties and taxes collected from the customer and passed to the authority.
     *
     * Held, not earned. Tracked for reconciliation and excluded from margin, and
     * a consignment's profit figure is only true because this is a separate case
     * rather than folded into `customs`.
     */
    case Duty = 'duty';

    /** A customs broker's fee for making the entry. */
    case Brokerage = 'brokerage';

    /** Terminal, port and warehouse handling. */
    case Handling = 'handling';

    /** Storage, free-time or paid. */
    case Storage = 'storage';

    /** Demurrage and detention we have been billed for. */
    case Demurrage = 'demurrage';

    /** Marine, warehouse or transit cover. */
    case Insurance = 'insurance';

    /** Fuel, tolls, driver time - a road movement's own running costs. */
    case Fuel = 'fuel';

    /** Equipment, IT, licences, subscriptions. */
    case Overhead = 'overhead';

    /** Salaries, wages, and the costs of employing people. */
    case Payroll = 'payroll';

    /** Anything the categories above do not cover. */
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
            self::Duty => 'Duty and tax',
            self::Brokerage => 'Brokerage',
            self::Handling => 'Handling',
            self::Storage => 'Storage',
            self::Demurrage => 'Demurrage',
            self::Insurance => 'Insurance',
            self::Fuel => 'Fuel',
            self::Overhead => 'Overhead',
            self::Payroll => 'Payroll',
            self::Other => 'Other',
        };
    }

    /**
     * Whether this cost is money the forwarder holds rather than spends.
     *
     * Duty and tax is collected from one party and remitted to another, and
     * treating it as spend would make the cost of a consignment exceed the
     * revenue billed against it. Every margin figure in this system subtracts
     * only the expenses for which this returns false.
     */
    public function isHeldMoney(): bool
    {
        return $this === self::Duty;
    }
}
