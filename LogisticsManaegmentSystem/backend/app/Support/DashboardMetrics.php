<?php

namespace App\Support;

use App\Enums\ShipmentStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShipmentCustomsClearance;
use App\Models\ShipmentDelivery;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The numbers on the front page.
 *
 * Not a report module and not an export: a read model assembled from the tables
 * that already exist, with each definition held next to the query that uses it so
 * the two cannot drift apart quietly. Nothing is stored or cached - every figure is
 * computed on request, because a cached dashboard is a dashboard showing
 * yesterday's exceptions.
 *
 * Two conventions run through all of it, and every method below depends on them:
 *
 * 1. The counting is the same counting the worklists do. Late is
 *    `Shipment::late()`, held is `ShipmentCustomsClearance::held()`, overdue is
 *    `ShipmentDelivery::overdue()`. A dashboard that disagreed with the list it
 *    summarises would be worse than no dashboard, so the scopes are reused rather
 *    than reimplemented as raw predicates - which is also why a figure here can be
 *    clicked through to and verified by eye.
 * 2. A figure the system cannot know is null, never zero. There is nothing to
 *    count for a system that has not been seeded, and a zero would be a claim
 *    about this month that nobody has counted. Once a table does exist, an empty
 *    one genuinely *is* zero - so the null is reserved for a figure with no
 *    meaning, not for an empty one, and the UI renders a dash.
 */
final class DashboardMetrics
{
    /**
     * How many months of volume history to return.
     *
     * Twelve is a year of comparison, which is the shortest span that answers "is
     * this month normal". Longer and the chart becomes a wall; shorter and every
     * January looks like a collapse.
     */
    private const VOLUME_MONTHS = 12;

    /**
     * How many rows each of the upcoming lists returns.
     *
     * A preview, not a queue. Five is what fits on a card and is enough for an
     * operator to see whether this week's problem is one consignment or everything
     * moving on the same day.
     */
    private const PREVIEW_LIMIT = 5;

    /**
     * How many entries the top-customers and top-carriers charts carry.
     *
     * Recharts draws a horizontal bar per entry against a fixed axis width, and
     * past about eight the labels stop being readable. Anything longer is a
     * report, and there is a reports module for that.
     */
    private const TOP_LIMIT = 8;

    private readonly CarbonImmutable $now;

    public function __construct(?DateTimeInterface $now = null)
    {
        /*
         * Injected so a test can ask the same question at a fixed moment, and
         * pinned to a month boundary as well as a day: "the last twelve months"
         * has to be twelve buckets whatever time of day the request arrives, or
         * the chart would gain or lose a month depending on the hour.
         */
        $this->now = $now === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($now instanceof DateTime ? $now : DateTime::createFromInterface($now));
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'generated_at' => $this->now->toIso8601String(),
            'shipments' => $this->shipments(),
            'operations' => $this->operations(),
            'finance' => $this->finance(),
            'charts' => $this->charts(),
            'upcoming' => $this->upcoming(),
        ];
    }

    /**
     * Consignment counts, by status and in total.
     *
     * One grouped query rather than one per status: the figures have to describe
     * the same instant, and a dashboard that read "4 total" and "3 in transit"
     * from two separate round trips could show a set of numbers that never
     * coexisted.
     *
     * Soft-deleted consignments are excluded, which the model's global scope does
     * without being asked. An archived consignment is a record, not a live one,
     * and counting it would put a total on the front page that no list can
     * reproduce.
     *
     * @return array<string, mixed>
     */
    private function shipments(): array
    {
        $byStatus = Shipment::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $count = static function (ShipmentStatus $status) use ($byStatus): int {
            return (int) $byStatus->get($status->value, 0);
        };

        $delivered = $count(ShipmentStatus::Delivered);
        $cancelled = $count(ShipmentStatus::Cancelled);

        return [
            'total' => (int) $byStatus->sum(),
            'late_count' => Shipment::query()->late($this->now)->count(),

            /*
             * Open is the complement of *closed*, not of delivered: a cancelled
             * consignment is finished and never arrived, so subtracting only the
             * delivered ones would count the cancelled as work still to do.
             */
            'open_count' => (int) $byStatus->sum() - $delivered - $cancelled,

            'draft_count' => $count(ShipmentStatus::Draft),
            'booked_count' => $count(ShipmentStatus::Booked),
            'in_transit_count' => $count(ShipmentStatus::InTransit),
            'customs_count' => $count(ShipmentStatus::Customs),
            'arrived_count' => $count(ShipmentStatus::Arrived),
            'delivered_count' => $delivered,
            'cancelled_count' => $cancelled,
        ];
    }

    /**
     * The operations worklists, as counts.
     *
     * Each figure is the question the corresponding list screen asks, and it is
     * taken from that screen's own composition, not from the status enum alone.
     * The customs and containers screens both open with `open()` before they
     * apply their attention filter - ContainerController asks `open()` then
     * `needsAttention()`, CustomsController asks `open()` then `held()` - and the
     * two figures here must do the same, or the number on the dashboard would
     * describe rows the list it points at never shows.
     *
     * The consequence is that everything in this block excludes archived
     * consignments: `open()` narrows through `whereHas('shipment', open())`, and
     * the whole point of the narrow is that a stuck box or a pending filing on a
     * record nobody can open is not a thing anybody can act on.
     *
     * @return array<string, mixed>
     */
    private function operations(): array
    {
        return [
            /*
             * Filings waiting on the authority, kept apart from filings that went
             * wrong. A refusal is counted under attention instead, because the two
             * drive opposite actions - one is a date to wait for, the other is a
             * phone call to make - and a combined figure would serve neither.
             */
            'held_customs' => ShipmentCustomsClearance::query()->open()->held()->count(),
            'customs_needing_attention' => ShipmentCustomsClearance::query()->needsAttention()->count(),

            /*
             * Deliveries with the cargo still to be handed over, and the subset
             * whose slot passed without an attempt. A failed attempt is
             * outstanding and not overdue, so it is counted once and not twice.
             */
            'outstanding_deliveries' => ShipmentDelivery::query()->open()->count(),
            'overdue_deliveries' => ShipmentDelivery::query()->overdue($this->now)->count(),

            'containers_needing_attention' => ShipmentContainer::query()->open()->needsAttention()->count(),
        ];
    }

    /**
     * Money, over the month in progress and the receivables standing at it.
     *
     * Two different questions and therefore two different sets of invoices, which
     * is the whole subtlety of this block. `outstanding` counts what is still owed
     * right now (`Invoice::outstanding()` - a demand with money outstanding),
     * while `revenue` counts what has been sent this month whether or not it has
     * been paid (`Invoice::recognised()`). Using one set for both is the error
     * that makes a dashboard quietly wrong: an invoice paid on time would leave
     * the collectable set and the month's revenue would drop, so a good month
     * would look worse the better the business did.
     *
     * Expenses are margin-bearing only, on the same reasoning the model uses: duty
     * and tax is collected and remitted rather than spent, and a profit figure that
     * counted it would be short by exactly the tax - wrong in the direction that
     * always flatters nothing, which is the useful direction to be wrong in but
     * still wrong.
     *
     * Null rather than zero was the old rule, for when there was nothing to count.
     * There is now something to count, and the null case is gone for a better
     * reason than "the module landed": an empty table genuinely *is* zero
     * outstanding and zero revenue, and dressing that as unknown would hide a
     * broken import behind a dash.
     *
     * `period` travels with the figures rather than the UI assuming one. A bare
     * "48,200" with nothing saying which month it is a claim nobody can check, and
     * the difference between this month and a rolling twelve is the difference
     * between a good month and a bad quarter.
     *
     * @return array<string, mixed>
     */
    private function finance(): array
    {
        $start = $this->now->startOfMonth();

        /*
         * Plucked and summed in PHP rather than `SUM(balance)` in SQL, which is a
         * deliberate loss of the database's help.
         *
         * On SQLite - and on MySQL too - an aggregate over a DECIMAL comes back
         * through a float, so `SUM` would quietly reintroduce exactly the rounding
         * the money columns exist to avoid. A receivables figure that is out by a
         * fraction of a minor unit on a large book is the kind of error nobody
         * finds, because it is always smaller than the rounding the screen does
         * anyway. `Money::sum` is bcmath, so the total is the sum of the column.
         */
        $outstanding = Invoice::query()->outstanding()->pluck(Invoice::BALANCE);

        $outstandingAmounts = [];
        foreach ($outstanding as $balance) {
            $outstandingAmounts[] = (string) $balance;
        }

        return [
            'available' => true,

            'outstanding_invoices' => $outstanding->count(),
            'outstanding_amount' => Money::sum($outstandingAmounts),

            'revenue' => $this->recognisedRevenue($start),
            'expenses' => $this->incurredCosts($start),
            'profit' => Money::subtract(
                $this->recognisedRevenue($start),
                $this->incurredCosts($start),
            ),

            'base_currency' => Money::baseCurrency(),

            'period' => [
                'label' => $start->format('F Y'),
                'start' => $start->toDateString(),
                'end' => $this->now->toDateString(),
            ],
        ];
    }

    /**
     * Sent-and-not-withdrawn invoice totals raised in the period.
     *
     * On `issue_date` rather than `created_at`, for the same reason due dates start
     * at issue rather than at creation: a draft assembled over three days and sent
     * on the fourth was billed on the fourth. Recognised revenue is a statement
     * about when the customer was asked to pay, which is the only date the
     * accounting period can be defended on.
     */
    private function recognisedRevenue(CarbonImmutable $from): string
    {
        $invoices = Invoice::query()
            ->recognised()
            ->whereNotNull('issue_date')
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->pluck('total');

        $amounts = [];
        foreach ($invoices as $total) {
            $amounts[] = (string) $total;
        }

        return Money::sum($amounts);
    }

    /**
     * Margin-bearing expenses incurred in the period, as a decimal string.
     *
     * On `incurred_on` rather than `created_at`, so a bill typed in on the 3rd for
     * goods consumed on the 28th lands in the month the cost belongs to. Duty and
     * tax are excluded by the scope, which is the same exclusion the expense's own
     * two figures are built on.
     */
    private function incurredCosts(CarbonImmutable $from): string
    {
        $expenses = Expense::query()
            ->marginBearing()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->pluck('amount');

        $amounts = [];
        foreach ($expenses as $amount) {
            $amounts[] = (string) $amount;
        }

        return Money::sum($amounts);
    }

    /**
     * @return array<string, mixed>
     */
    private function charts(): array
    {
        return [
            'volume_by_month' => $this->volumeByMonth(),
            'status_distribution' => $this->statusDistribution(),
            'revenue_vs_expense' => $this->revenueVsExpense(),
            'top_customers' => $this->topCustomers(),
            'top_carriers' => $this->topCarriers(),
        ];
    }

    /**
     * Revenue against costs per month, oldest first, zero-filled.
     *
     * The same twelve buckets and the same `DATE()` grouping as
     * `volumeByMonth()`, deliberately, and not only to share a loop: two charts on
     * one card with different x-axes invite the reader to line a February revenue
     * spike up against March's shipments, and if the axes do not actually align
     * that comparison is wrong. Same start, same count, same labels.
     *
     * Zero-filled for the same reason, and a partial current month is a smaller
     * number rather than a missing one - but unlike volume, a revenue line that
     * stops short mid-month reads as a collapse rather than an unfinished month,
     * so the client is told which bucket is in progress rather than left to guess.
     *
     * Revenue on `issue_date` and costs on `incurred_on`, each to the month it
     * belongs to rather than the month it was typed in. That is also why the two
     * series will not tie to a bank statement, and is the honest comparison to
     * make: one is what has been billed, the other what has been consumed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function revenueVsExpense(): array
    {
        $start = $this->now->startOfMonth()->subMonths(self::VOLUME_MONTHS - 1);
        $from = $start->toDateString();

        $revenue = [];

        foreach (
            Invoice::query()
                ->recognised()
                ->whereNotNull('issue_date')
                ->whereDate('issue_date', '>=', $from)
                ->selectRaw('DATE(issue_date) as billed_on, total')
                ->get() as $invoice
        ) {
            $month = CarbonImmutable::parse((string) $invoice->billed_on)->format('Y-m');

            $revenue[$month] = Money::add(
                $revenue[$month] ?? Money::zero(),
                (string) $invoice->total,
            );
        }

        $expenses = [];

        foreach (
            Expense::query()
                ->marginBearing()
                ->whereDate('incurred_on', '>=', $from)
                ->selectRaw('DATE(incurred_on) as spent_on, amount')
                ->get() as $expense
        ) {
            $month = CarbonImmutable::parse((string) $expense->spent_on)->format('Y-m');

            $expenses[$month] = Money::add(
                $expenses[$month] ?? Money::zero(),
                (string) $expense->amount,
            );
        }

        $months = [];

        for ($offset = 0; $offset < self::VOLUME_MONTHS; $offset++) {
            $month = $start->addMonths($offset);
            $key = $month->format('Y-m');

            $months[] = [
                'month' => $month->format('M Y'),
                'month_key' => $key,
                'revenue' => $revenue[$key] ?? Money::zero(),
                'expenses' => $expenses[$key] ?? Money::zero(),
                'is_current' => $key === $this->now->format('Y-m'),
            ];
        }

        return $months;
    }

    /**
     * Shipments raised per month, oldest first, zero-filled.
     *
     * Counted on `created_at` rather than `etd`. A consignment is raised when the
     * work arrives, which is what a forwarder's volume figure describes, and `etd`
     * would make this a forecast that moves every time a sailing is rescheduled.
     *
     * Grouping happens on the day and the month is folded in PHP, because the
     * three supported databases spell "month of this timestamp" three different
     * ways. `DATE()` is the one spelling all of them accept.
     *
     * The fold is not optional. A day-grouped result is one row per day the work
     * arrived, so a month with shipments on the 3rd, the 5th and the 20th arrives
     * as three rows and a lookup keyed on the month's first day finds nothing.
     * That reads as a month with no shipments in it, which is precisely the kind of
     * quietly wrong figure this class is meant to rule out.
     *
     * Months with no shipments are present with a zero rather than omitted,
     * because a gap in a time series reads as missing data while a bar of zero
     * reads as a quiet month - which is the answer. The last bucket is the month
     * in progress, so a partial month is never compared against twelve complete
     * ones without looking like a collapse.
     *
     * @return array<int, array<string, mixed>>
     */
    private function volumeByMonth(): array
    {
        $start = $this->now->startOfMonth()->subMonths(self::VOLUME_MONTHS - 1);

        $byMonth = [];

        foreach (
            Shipment::query()
                ->where('created_at', '>=', $start)
                ->selectRaw('DATE(created_at) as raised_on, count(*) as aggregate')
                ->groupByRaw('DATE(created_at)')
                ->get() as $row
        ) {
            $month = CarbonImmutable::parse((string) $row->raised_on)->format('Y-m');

            $byMonth[$month] = ($byMonth[$month] ?? 0) + (int) $row->aggregate;
        }

        $months = [];

        for ($offset = 0; $offset < self::VOLUME_MONTHS; $offset++) {
            $month = $start->addMonths($offset);
            $key = $month->format('Y-m');

            $months[] = [
                /*
                 * `M YYYY`, the label an axis has room for, sent rather than
                 * formatted in the browser so the axis and any tooltip agree with
                 * whatever locale the API is running under. `month_key` is the
                 * sortable form for the same reason.
                 */
                'month' => $month->format('M Y'),
                'month_key' => $key,
                'total' => $byMonth[$key] ?? 0,
            ];
        }

        return $months;
    }

    /**
     * Consignments per status, in lifecycle order.
     *
     * Ordered by the enum rather than by count so the slices keep a stable
     * arrangement between loads; a chart whose wedges move on every refresh is
     * read as new information when it is only new rendering.
     *
     * Every status is included, including the empty ones, so the legend holds the
     * same colours in the same order every time. It also makes "no cancelled
     * consignments" readable as a zero rather than something to infer from an
     * absence.
     *
     * @return array<int, array<string, mixed>>
     */
    private function statusDistribution(): array
    {
        $counts = Shipment::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return array_map(
            static fn (ShipmentStatus $status): array => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) $counts->get($status->value, 0),
            ],
            ShipmentStatus::cases(),
        );
    }

    /**
     * Consignments per consignee, busiest first.
     *
     * The consignee rather than the shipper, because the consignee is the
     * customer: the party the forwarder works for and the one an account manager
     * is measured against. A shipment with no consignee is skipped rather than
     * filed under a blank, since "which customer" is unanswerable for it.
     *
     * Ties break on the code so the order is deterministic; without that, two
     * equal counts can swap between requests and the chart appears to flicker.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topCustomers(): array
    {
        return Shipment::query()
            ->join('parties', 'parties.id', '=', 'shipments.consignee_id')
            ->groupBy('parties.id', 'parties.code', 'parties.name')
            ->orderByDesc('aggregate')
            ->orderBy('parties.code')
            ->limit(self::TOP_LIMIT)
            /*
             * `toBase()` rather than `get()`, because these rows are not
             * consignments: they are grouped counts that happen to be selected
             * from the shipments table. Hydrating a Shipment here would give the
             * model a `status` of null and an `id` of the party's, which is a lie
             * of exactly the kind this class exists to avoid. Scopes are still
             * applied, so the soft-delete exclusion survives.
             */
            ->toBase()
            ->get([
                'parties.code as code',
                'parties.name as customer',
                DB::raw('count(*) as aggregate'),
            ])
            ->map(fn (object $row): array => [
                'code' => (string) $row->code,
                'customer' => (string) $row->customer,
                'count' => (int) $row->aggregate,
            ])
            ->values()
            ->all();
    }

    /**
     * Consignments per carrier, busiest first.
     *
     * As for the customers, and the same exclusion of the unset: a consignment
     * with no carrier is the booking desk's problem, and crediting it to nobody
     * beats crediting it to a blank row at the top of a chart called "top
     * carriers".
     *
     * @return array<int, array<string, mixed>>
     */
    private function topCarriers(): array
    {
        return Shipment::query()
            ->join('carriers', 'carriers.id', '=', 'shipments.carrier_id')
            ->groupBy('carriers.id', 'carriers.code', 'carriers.name')
            ->orderByDesc('aggregate')
            ->orderBy('carriers.code')
            ->limit(self::TOP_LIMIT)
            ->toBase()
            ->get([
                'carriers.code as code',
                'carriers.name as carrier',
                DB::raw('count(*) as aggregate'),
            ])
            ->map(fn (object $row): array => [
                'code' => (string) $row->code,
                'carrier' => (string) $row->carrier,
                'count' => (int) $row->aggregate,
            ])
            ->values()
            ->all();
    }

    /**
     * The three short lists a shift starts with, plus the one a finance officer does.
     *
     * @return array<string, mixed>
     */
    private function upcoming(): array
    {
        return [
            'etd' => $this->previews(
                Shipment::query()->open()->whereNotNull('etd')->whereDate('etd', '>=', $this->now)->orderBy('etd'),
            ),
            'eta' => $this->previews(
                Shipment::query()->open()->whereNotNull('eta')->whereDate('eta', '>=', $this->now)->orderBy('eta'),
            ),
            'late' => $this->previews(Shipment::query()->late($this->now)->orderBy('eta')),
            'overdue_invoices' => $this->overdueInvoicePreviews(),
        ];
    }

    /**
     * The oldest overdue invoices, with how far past due each one is.
     *
     * Ordered by due date rather than by balance. A dashboard that leads with the
     * largest number leads with the invoice least likely to be chased, because the
     * one that has been overdue longest is the one whose customer has stopped
     * answering - and that is the row a person needs to see.
     *
     * `days_overdue` is sent rather than left to the browser, for the same reason
     * the shipment previews send `status_label`: the difference between "late" and
     * "eleven days late" is the difference between a chase and a write-off, and
     * two clients computing days differently is how that gets lost. Derived from
     * the same `$this->now` the list was filtered with, so the number cannot
     * disagree with the membership.
     *
     * @return array<int, array<string, mixed>>
     */
    private function overdueInvoicePreviews(): array
    {
        return Invoice::query()
            ->overdue($this->now)
            ->with('customer')
            ->orderBy('due_date')
            ->limit(self::PREVIEW_LIMIT)
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'reference' => $invoice->reference,
                'customer' => $invoice->customer?->name,
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'balance' => (string) $invoice->balance,
                /*
                 * Counted from the due date forward, and absolute, so the figure is
                 * a count of days rather than a difference with a sign. Carbon
                 * returns a negative value for a date in the past, which would put
                 * "-40 days overdue" in front of somebody deciding whether to
                 * chase or write off - the one number on this card that must be
                 * read the obvious way.
                 */
                'days_overdue' => (int) $invoice->due_date->startOfDay()
                    ->diffInDays($this->now->startOfDay(), true),
            ])
            ->all();
    }

    /**
     * A short list of consignments, shaped for a preview table.
     *
     * `status_label` travels with each row rather than being derived in the
     * browser, for the reason ShipmentResource sends it: one translation of the
     * vocabulary, in one place.
     *
     * @param  Builder<Shipment>  $query
     * @return array<int, array<string, mixed>>
     */
    private function previews(Builder $query): array
    {
        return $query
            ->with('consignee')
            ->limit(self::PREVIEW_LIMIT)
            ->get()
            ->map(fn (Shipment $shipment): array => [
                'id' => $shipment->id,
                'reference' => $shipment->reference,
                'customer' => $shipment->consignee?->name,
                'etd' => $shipment->etd?->format('Y-m-d'),
                'eta' => $shipment->eta?->format('Y-m-d'),
                'status' => $shipment->status->value,
                'status_label' => $shipment->status->label(),
                'is_late' => $shipment->isLate($this->now),
            ])
            ->all();
    }
}
