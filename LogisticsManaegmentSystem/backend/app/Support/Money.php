<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Decimal arithmetic for the finance module.
 *
 * Every amount in this system is a `decimal(15,4)` column, Eloquent casts it to a
 * string, and nothing here ever hands one to PHP as a float. That is not
 * fastidiousness: a float has 15-17 significant decimal digits in total, not
 * after the point, so `0.1 + 0.2` is `0.30000000000000004` and an invoice that
 * summed its lines that way would be a cent out on some months and not others.
 * A freight forwarder that cannot state what it is owed is not a freight
 * forwarder, so the arithmetic is done in bcmath and the rounding is ours.
 *
 * The class is a namespace for functions rather than a value object, and that is
 * deliberate. A `Money` object would have to carry a currency, and the two
 * decisions that follow from that - whether two amounts may be added at all, and
 * what to do when they are in different currencies - have no answer this codebase
 * is ready to make. It bills in the base currency and does not yet convert, so a
 * single `Money` value with a hidden currency would be a way to add two things
 * that are not the same thing without anybody noticing. The column carries the
 * currency and the API returns it; nothing here pretends to police it.
 *
 * Every function takes and returns a string, and accepts a nullable one only
 * where the business meaning is "not recorded yet" rather than "zero". A charge
 * with no tax rate is a charge that was not taxed; it is not a charge taxed at
 * zero percent, and the two must not read alike on a tax summary.
 */
final class Money
{
    /**
     * The working scale, from `config('logistics.finance.scale')`.
     *
     * Read per call rather than baked into a constant, because the column scale
     * and the config value have to agree and a constant would let them drift
     * apart with nothing complaining. A mismatch shows up as an amount that
     * silently gains or loses digits in the database, which is the failure mode
     * this whole class exists to prevent.
     */
    public static function scale(): int
    {
        $scale = (int) config('logistics.finance.scale', 4);

        if ($scale < 0 || $scale > 8) {
            throw new InvalidArgumentException(sprintf(
                'logistics.finance.scale must be between 0 and 8, got [%d].',
                $scale,
            ));
        }

        return $scale;
    }

    /**
     * The currency everything is billed in.
     *
     * One currency, and the reason the dashboard's `base_currency` is a single
     * string rather than a per-record one. Nothing converts yet; when it does,
     * this is the rate table's base and every stored amount keeps the currency it
     * was written in.
     */
    public static function baseCurrency(): string
    {
        return strtoupper((string) config('logistics.finance.base_currency', 'USD'));
    }

    /**
     * Coerces anything a request can carry into a canonical decimal string.
     *
     * This is the boundary function. A JSON body can hold `12.5` as a number or
     * `"12.50"` as a string depending on how it was written, and `1e3` is a legal
     * JSON number that no amount column will accept. Normalising on the way in -
     * and, as with references, comparing the `unique` and range rules against the
     * normalised form - means validation sees the value that is about to be
     * stored rather than whatever shape the client happened to send.
     *
     * Returns null for a null input, so `sometimes|nullable` fields still clear.
     * A non-numeric string is a thrown InvalidArgumentException rather than a
     * null: a caller that swallows it and stores zero would be worse than a 500,
     * and the controller turns this into a 422 before it gets there.
     */
    public static function normalise(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        /*
         * A float or int has already lost precision by the time it arrives in a
         * JSON body - `0.1` is not exactly one tenth - so it is rendered at the
         * working scale and re-read as a string rather than being trusted. That
         * recovers the shortest representation the client typed rather than the
         * full binary expansion, which is the closest a float can honestly get.
         */
        if (is_float($value)) {
            $value = number_format($value, self::scale(), '.', '');
        } elseif (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                'An amount must be a number or a numeric string, got [%s].',
                get_debug_type($value),
            ));
        }

        $trimmed = trim($value);

        if (preg_match('/^-?\d+(\.\d+)?$/', $trimmed) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'An amount must be a plain decimal number, got [%s].',
                $trimmed,
            ));
        }

        return self::of($trimmed);
    }

    /**
     * Renders any decimal input at the working scale, exactly.
     *
     * Always returns a string with the full number of decimals the columns
     * declare, so a stored `8.5` reads back as `8.5000` and a form round-tripping
     * it sends back something the column means. Trailing zeros are not cosmetic
     * here: they are the difference between the value that was stored and the
     * value that will be compared against it later.
     */
    public static function of(string $amount): string
    {
        return bcadd($amount, '0', self::scale());
    }

    /**
     * Zero at the working scale. Named so call sites read as arithmetic.
     */
    public static function zero(): string
    {
        return bcadd('0', '0', self::scale());
    }

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::scale());
    }

    public static function subtract(string $a, string $b): string
    {
        return bcsub($a, $b, self::scale());
    }

    /**
     * Sums a list of amounts at the working scale.
     *
     * The one that has no `array_sum` equivalent. PHP's `array_sum` walks a float,
     * so a hundred charges of 0.1 come to `9.999999999999831`, and that number
     * becomes an invoice total. Iterating with `bcadd` costs nothing at the scale
     * of a page of lines and is exact at any count.
     *
     * An empty list is zero rather than an error, because a customer with no
     * expenses this month owes nothing and that is a legitimate answer rather
     * than a missing value.
     *
     * @param  array<int, string|int|float|null>  $amounts
     */
    public static function sum(array $amounts): string
    {
        $total = self::zero();

        foreach ($amounts as $amount) {
            if ($amount === null || $amount === '') {
                continue;
            }

            $total = bcadd($total, self::of((string) $amount), self::scale());
        }

        return $total;
    }

    public static function multiply(string $a, string $b): string
    {
        return bcmul($a, $b, self::scale());
    }

    /**
     * Divides to the working scale, which is a deliberate rounding.
     *
     * Only used for rates that are not money - a tax percentage applied to a
     * net amount, a unit price times a quantity. Money is never divided in this
     * module, because an invoice is built by summing lines rather than by
     * splitting a total, so there is no place where a half-cent has to be
     * allocated to one side or the other.
     */
    public static function divide(string $a, string $b): string
    {
        if (bccomp($b, '0', self::scale()) === 0) {
            throw new InvalidArgumentException('Cannot divide an amount by zero.');
        }

        return bcdiv($a, $b, self::scale());
    }

    /**
     * A percentage of an amount, at the working scale.
     *
     * `5` means five percent, not 0.05, because a tax rate is stored and entered
     * as the number a person reads off a rate card. The division by 100 happens
     * here so no call site has to remember it, and the result is a money amount
     * like any other.
     */
    public static function percentageOf(string $amount, string $percent): string
    {
        return self::divide(self::multiply($amount, $percent), '100');
    }

    /**
     * -1, 0 or 1, the sign of the difference. For `a < b` style tests.
     */
    public static function compare(string $a, string $b): int
    {
        return bccomp($a, $b, self::scale());
    }

    public static function isZero(string $amount): bool
    {
        return self::compare($amount, self::zero()) === 0;
    }

    public static function isPositive(string $amount): bool
    {
        return self::compare($amount, self::zero()) > 0;
    }

    public static function isNegative(string $amount): bool
    {
        return self::compare($amount, self::zero()) < 0;
    }

    /**
     * Whether $a is greater than $b. `greaterThan(1, 1)` is false, and spelled
     * this way rather than as `compare() > 0` at the call site so a comparison
     * cannot be read the wrong way round.
     */
    public static function greaterThan(string $a, string $b): bool
    {
        return self::compare($a, $b) > 0;
    }

    public static function lessThan(string $a, string $b): bool
    {
        return self::compare($a, $b) < 0;
    }

    /**
     * Rounds to a currency's minor units, half-up.
     *
     * Output formatting only. Nothing stored in this system is rounded: amounts
     * are decimal(15,4) so that a rate card's unit price of 0.0875 survives, and
     * the tax on it is exact rather than a per-line approximation of 5%. This
     * exists for the one place a figure has to be stated in whole cents - a
     * printed invoice, a dashboard tile - and half-up is the convention a finance
     * department expects, where the schoolbook method would round 0.005 down and
     * a line would silently lose half a cent against a total computed from the
     * unrounded parts.
     */
    public static function round(string $amount, int $minorUnits = 2): string
    {
        if ($minorUnits < 0 || $minorUnits > 8) {
            throw new InvalidArgumentException(sprintf(
                'Minor units must be between 0 and 8, got [%d].',
                $minorUnits,
            ));
        }

        return bcadd($amount, '0', $minorUnits);
    }
}
