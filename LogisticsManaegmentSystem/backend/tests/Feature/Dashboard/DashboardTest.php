<?php

namespace Tests\Feature\Dashboard;

use App\Enums\CustomsStatus;
use App\Enums\ShipmentStatus;
use App\Models\Carrier;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShipmentCustomsClearance;
use App\Models\ShipmentDelivery;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Tests\Feature\MasterData\MasterDataTestCase;

/**
 * The overview endpoint.
 *
 * The figures are only worth anything if they agree with the lists they summarise,
 * so most of what follows is an agreement test rather than a count test: the
 * headline number, the worklist it claims to summarise, and the flag on the row
 * all have to say the same thing about the same record. A dashboard that is
 * internally consistent and quietly wrong is the failure mode worth guarding
 * against here, and it cannot be caught by asserting a count against a number
 * typed into the test.
 */
class DashboardTest extends MasterDataTestCase
{
    public function test_it_requires_a_session(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        // A signed-in user holding no roles at all. Every configured role happens
        // to carry `dashboard.view`, so the way to prove the permission is enforced
        // is a user who is outside the configured set.
        $user = User::factory()->create();

        $this->withToken($user->createToken('spa')->plainTextToken)
            ->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_a_viewer_may_read_it(): void
    {
        // The permission's whole point: read access is granted far more widely than
        // write access, and the dashboard is the widest read in the system.
        $this->actingAsRole('viewer')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_it_returns_the_whole_summary_in_the_success_envelope(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'generated_at',
                    'shipments' => [
                        'total', 'open_count', 'late_count',
                        'draft_count', 'booked_count', 'in_transit_count',
                        'customs_count', 'arrived_count', 'delivered_count', 'cancelled_count',
                    ],
                    'operations' => [
                        'held_customs', 'customs_needing_attention',
                        'outstanding_deliveries', 'overdue_deliveries',
                        'containers_needing_attention',
                    ],
                    'finance' => [
                        'available', 'outstanding_invoices', 'outstanding_amount',
                        'revenue', 'expenses', 'profit', 'base_currency',
                    ],
                    'charts' => [
                        'volume_by_month', 'status_distribution', 'top_customers', 'top_carriers',
                    ],
                    'upcoming' => ['etd', 'eta', 'late', 'overdue_invoices'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.generated_at'));
    }

    public function test_an_empty_system_reports_zeroes_for_what_it_can_see(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        // Zero, not null, and not absent: an empty system genuinely has no
        // consignments, and that is a fact rather than a gap in the data.
        $response->assertJsonPath('data.shipments.total', 0)
            ->assertJsonPath('data.shipments.open_count', 0)
            ->assertJsonPath('data.shipments.late_count', 0)
            ->assertJsonPath('data.operations.held_customs', 0)
            ->assertJsonPath('data.operations.outstanding_deliveries', 0)
            ->assertJsonPath('data.charts.top_customers', [])
            ->assertJsonPath('data.charts.top_carriers', [])
            ->assertJsonPath('data.upcoming.etd', [])
            ->assertJsonPath('data.upcoming.late', []);
    }

    public function test_it_counts_consignments_by_status(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            Shipment::factory()->withStatus($status)->create();
        }

        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.shipments.total', count(ShipmentStatus::cases()))
            ->assertJsonPath('data.shipments.draft_count', 1)
            ->assertJsonPath('data.shipments.booked_count', 1)
            ->assertJsonPath('data.shipments.in_transit_count', 1)
            ->assertJsonPath('data.shipments.customs_count', 1)
            ->assertJsonPath('data.shipments.arrived_count', 1)
            ->assertJsonPath('data.shipments.delivered_count', 1)
            ->assertJsonPath('data.shipments.cancelled_count', 1)
            ->assertJsonPath('data.shipments.open_count', 5);
    }

    public function test_open_excludes_cancelled_as_well_as_delivered(): void
    {
        Shipment::factory()->delivered()->create();
        Shipment::factory()->withStatus(ShipmentStatus::Cancelled)->create();
        Shipment::factory()->withStatus(ShipmentStatus::Arrived)->create();

        // A consignment arrived and waiting to be handed over is open even though
        // it is no longer moving, and a consignment that was cancelled is closed
        // even though it never arrived. Only the two together make the arithmetic
        // come out.
        $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->assertJsonPath('data.shipments.open_count', 1)
            ->assertJsonPath('data.shipments.total', 3);
    }

    public function test_it_excludes_archived_consignments_from_every_figure(): void
    {
        Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create();
        $archived = Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create();

        $archived->delete();

        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        // The global scope does this without being asked. A total no list can
        // reproduce is the specific failure: an operator counts the rows on
        // /shipments, gets a different number, and stops trusting the page.
        $response->assertJsonPath('data.shipments.total', 1)
            ->assertJsonPath('data.shipments.in_transit_count', 1)
            ->assertJsonPath('data.shipments.open_count', 1)
            ->assertJsonPath('data.charts.volume_by_month.11.total', 1);

        // And the status breakdown sums to the same total, so the headline and the
        // chart cannot disagree.
        $this->assertSame(
            1,
            array_sum(array_column($response->json('data.charts.status_distribution'), 'count')),
        );

        // Nor does it turn up in a preview list under a live consignment's dates.
        $this->assertNotContains(
            $archived->id,
            array_column($response->json('data.upcoming.eta'), 'id'),
        );
    }

    public function test_the_late_count_agrees_with_the_flag_on_every_row(): void
    {
        // The assertion that matters most on this endpoint: the headline figure and
        // the per-row flag are the same question, so they are compared against
        // each other over a set that includes every awkward case rather than
        // against a number typed into the test.
        Shipment::factory()->overdue()->create();                                   // past ETA, moving
        Shipment::factory()->withStatus(ShipmentStatus::Customs)
            ->create(['eta' => now()->subDays(2)->format('Y-m-d')]);                // past ETA, held
        Shipment::factory()->withStatus(ShipmentStatus::Arrived)
            ->create(['eta' => now()->format('Y-m-d')]);                           // due today
        Shipment::factory()->withStatus(ShipmentStatus::InTransit)
            ->create(['eta' => now()->addDay()->format('Y-m-d')]);                 // due tomorrow
        Shipment::factory()->unassigned()->create();                               // no ETA at all
        Shipment::factory()->delivered()->create(['eta' => now()->subWeek()->format('Y-m-d')]); // arrived in the end

        $flagged = Shipment::query()->get()->filter(fn (Shipment $s): bool => $s->isLate())->count();

        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        $this->assertSame(3, $flagged, 'Fixture sanity: three consignments read as late.');
        $response->assertJsonPath('data.shipments.late_count', $flagged);
    }

    public function test_a_consignment_due_today_already_reads_as_late(): void
    {
        // The boundary, pinned deliberately. `isLate()` compares the ETA's midnight
        // against now, so by the afternoon a consignment due today is past its
        // arrival - and the scope has to agree, because the dashboard and the row
        // flag are read side by side.
        Shipment::factory()->withStatus(ShipmentStatus::InTransit)
            ->create(['eta' => now()->format('Y-m-d')]);

        $this->assertTrue(Shipment::query()->first()->isLate());

        $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->assertJsonPath('data.shipments.late_count', 1);
    }

    public function test_the_operations_figures_are_the_worklist_counts(): void
    {
        // Pinned to `in_transit`, and not for tidiness. `ShipmentFactory` rolls the
        // status at random across the whole lifecycle, but every figure asserted
        // below is `open()`-scoped - a consignment that happens to come out
        // `delivered` or `cancelled` is excluded by `open()` and the test fails for
        // a reason that has nothing to do with what it is checking. It would pass
        // most runs and fail on the seed that drew a closed status, which is the
        // worst way for a test to break.
        $shipment = Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create();

        ShipmentCustomsClearance::factory()->create([
            'shipment_id' => $shipment->id,
            'status' => CustomsStatus::Pending,
        ]);
        ShipmentCustomsClearance::factory()->create([
            'shipment_id' => $shipment->id,
            'status' => CustomsStatus::UnderReview,
        ]);
        ShipmentCustomsClearance::factory()->rejected()->create(['shipment_id' => $shipment->id]);
        ShipmentCustomsClearance::factory()->completed()->create(['shipment_id' => $shipment->id]);

        ShipmentDelivery::factory()->scheduled()->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->overdue()->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->failed()->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->delivered()->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->cancelled()->create(['shipment_id' => $shipment->id]);

        ShipmentContainer::factory()->customsHold()->create(['shipment_id' => $shipment->id]);
        ShipmentContainer::factory()->damaged()->create(['shipment_id' => $shipment->id]);
        ShipmentContainer::factory()->inTransit()->create(['shipment_id' => $shipment->id]);

        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        // Two waiting on the authority, one refused, and the refusal is counted
        // under attention rather than held - a combined figure would serve neither
        // question. Two outstanding deliveries out of five rows: the handover, the
        // cancellation and the refusal are all finished, and the failed attempt is
        // outstanding, which is what makes it worth chasing.
        $response->assertJsonPath('data.operations.held_customs', 2)
            ->assertJsonPath('data.operations.customs_needing_attention', 1)
            ->assertJsonPath('data.operations.outstanding_deliveries', 3)
            ->assertJsonPath('data.operations.containers_needing_attention', 2);

        // Asserted against the scope rather than a literal, because a failed
        // attempt is outstanding and *not* overdue - the one place where those two
        // figures must not agree.
        $this->assertSame(
            ShipmentDelivery::query()->overdue()->count(),
            $response->json('data.operations.overdue_deliveries'),
        );
        $this->assertSame(1, $response->json('data.operations.overdue_deliveries'));
    }

    public function test_the_worklist_figures_exclude_archived_consignments(): void
    {
        // Open, for the same reason as the test above: the counts being checked are
        // `open()`-scoped, so a randomly-closed consignment would make the figures
        // zero before the delete rather than after it - which would pass for the
        // wrong reason on a green run and fail on a different one.
        $shipment = Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create();

        ShipmentContainer::factory()->customsHold()->create(['shipment_id' => $shipment->id]);
        ShipmentCustomsClearance::factory()->rejected()->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->scheduled()->create(['shipment_id' => $shipment->id]);
        ShipmentDelivery::factory()->overdue()->create(['shipment_id' => $shipment->id]);

        $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->assertJsonPath('data.operations.containers_needing_attention', 1)
            ->assertJsonPath('data.operations.customs_needing_attention', 1)
            ->assertJsonPath('data.operations.outstanding_deliveries', 2)
            ->assertJsonPath('data.operations.overdue_deliveries', 1);

        $shipment->delete();

        // Every one drops to nothing. A box that is stuck on a consignment nobody
        // can open is not a thing anybody can act on, and the same goes for a
        // refused filing and a delivery with no addressable consignment behind it.
        // The worklist screens themselves narrow this way - ContainerController
        // asks `open()` then `needsAttention()`, CustomsController `open()` then
        // `held()` - so the number and the list it points at cannot disagree.
        $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->assertJsonPath('data.operations.held_customs', 0)
            ->assertJsonPath('data.operations.containers_needing_attention', 0)
            ->assertJsonPath('data.operations.customs_needing_attention', 0)
            ->assertJsonPath('data.operations.outstanding_deliveries', 0)
            ->assertJsonPath('data.operations.overdue_deliveries', 0);
    }

    public function test_the_held_figures_mirror_the_worklist_not_the_status_enum(): void
    {
        /*
         * The figure is taken from the list screen's own composition and not from
         * the bare status query, and the difference only shows itself here.
         *
         * A filing left pending on an archived consignment still exists, so the
         * model-level `held()` scope alone would count it: "the goods are with the
         * authority" is true of the cargo. But the customs screen opens with
         * `open()` before it applies `held()`, so no screen would ever show that
         * row, and a dashboard number that counted it would describe rows nobody
         * can reach. `held_customs` follows the screen, not the status.
         */
        $shipment = Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create();
        ShipmentCustomsClearance::factory()->create(['shipment_id' => $shipment->id]);
        ShipmentContainer::factory()->customsHold()->create(['shipment_id' => $shipment->id]);

        $this->assertSame(
            1,
            ShipmentCustomsClearance::query()->held()->count(),
            'The filing itself is still pending with the authority.',
        );

        $shipment->delete();

        $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->assertJsonPath('data.operations.held_customs', 0)
            ->assertJsonPath('data.operations.containers_needing_attention', 0);
    }

    public function test_an_empty_system_reports_a_finance_block_of_zeroes(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        // Zero, and `available` true. This used to be null with `available` false,
        // back when there was no finance module and the honest answer to "what is
        // the revenue" was "nobody knows". Now the tables exist, and an empty one
        // genuinely *is* zero: no invoice has been sent, so nothing is outstanding
        // and nothing has been billed. Reporting that as unknown would hide a
        // broken import behind a dash, which is the worse failure - a zero gets
        // looked into, a dash does not.
        $response->assertJsonPath('data.finance.available', true)
            ->assertJsonPath('data.finance.revenue', '0.0000')
            ->assertJsonPath('data.finance.expenses', '0.0000')
            ->assertJsonPath('data.finance.profit', '0.0000')
            ->assertJsonPath('data.finance.outstanding_invoices', 0)
            ->assertJsonPath('data.finance.outstanding_amount', '0.0000')
            ->assertJsonPath('data.finance.base_currency', 'USD');

        // And the list is present and empty rather than absent, so a client never
        // has to test for a key before reading it.
        $response->assertJsonPath('data.upcoming.overdue_invoices', []);
    }

    public function test_revenue_counts_invoices_sent_in_the_month_and_not_the_paid_ones_out(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        // Issued this month, fully settled. The one that proves the two sets are
        // different: this is revenue for the month, and it is no longer part of
        // what is outstanding, because a receivables total that counted paid
        // invoices would overstate what customers owe by every account that paid on
        // time.
        Invoice::factory()->issued()->create([
            'total' => '1000.0000',
            'paid_amount' => '1000.0000',
            'balance' => '0.0000',
            'issue_date' => '2026-09-10',
        ]);

        // Issued this month, part-paid: revenue in full, outstanding in part.
        Invoice::factory()->issued()->create([
            'total' => '800.0000',
            'paid_amount' => '300.0000',
            'balance' => '500.0000',
            'issue_date' => '2026-09-12',
        ]);

        // Sent last month, still owing. Not this month's revenue - it was billed in
        // August - but it is outstanding now, and an aged receivables figure that
        // only looked at the current month would hide it.
        Invoice::factory()->issued()->create([
            'total' => '400.0000',
            'paid_amount' => '0.0000',
            'balance' => '400.0000',
            'issue_date' => '2026-08-20',
        ]);

        $metrics = new DashboardMetrics($now);

        $this->assertSame('1800.0000', $metrics->summary()['finance']['revenue']);

        $finance = $metrics->summary()['finance'];

        $this->assertSame(2, $finance['outstanding_invoices']);
        $this->assertSame('900.0000', $finance['outstanding_amount']);
    }

    public function test_a_draft_or_a_void_invoice_is_neither_revenue_nor_receivable(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        // Assembled, not sent. Counting it would be billing money nobody has been
        // asked for, from whatever is sitting in somebody's drafts folder.
        Invoice::factory()->create([
            'total' => '5000.0000',
            'balance' => '5000.0000',
            'issue_date' => '2026-09-05',
        ]);

        // Withdrawn. It was never revenue and is nothing anybody owes.
        Invoice::factory()->voided()->create([
            'total' => '3000.0000',
            'balance' => '3000.0000',
            'issue_date' => '2026-09-06',
        ]);

        $finance = (new DashboardMetrics($now))->summary()['finance'];

        $this->assertSame('0.0000', $finance['revenue']);
        $this->assertSame(0, $finance['outstanding_invoices']);
        $this->assertSame('0.0000', $finance['outstanding_amount']);
    }

    public function test_profit_is_revenue_less_margin_bearing_costs(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        Invoice::factory()->issued()->create([
            'total' => '10000.0000',
            'paid_amount' => '10000.0000',
            'balance' => '0.0000',
            'issue_date' => '2026-09-02',
        ]);

        Expense::factory()->ofAmount('4000.0000')->incurredOn('2026-09-07')->create();
        Expense::factory()->ofAmount('1500.0000')->incurredOn('2026-09-08')->create();

        // Duty is collected from the customer and remitted, not spent. Including it
        // would put a cost in the profit figure that never was one, and the error
        // would always flatter the bottom line - which is the direction of mistake
        // that survives longest unquestioned.
        Expense::factory()->dutyAndTax()->ofAmount('2000.0000')->incurredOn('2026-09-09')->create();

        $finance = (new DashboardMetrics($now))->summary()['finance'];

        $this->assertSame('10000.0000', $finance['revenue']);
        $this->assertSame('5500.0000', $finance['expenses']);
        $this->assertSame('4500.0000', $finance['profit']);
    }

    public function test_a_cost_incurred_last_month_is_not_this_months_profit(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        Invoice::factory()->issued()->create([
            'total' => '1000.0000',
            'paid_amount' => '1000.0000',
            'balance' => '0.0000',
            'issue_date' => '2026-09-02',
        ]);

        // Typed in this month for work done in August. A profit figure is about the
        // period the cost belongs to, or the month an operator happened to type is
        // the month the business looks profitable.
        Expense::factory()->ofAmount('900.0000')->incurredOn('2026-08-14')->create();

        $finance = (new DashboardMetrics($now))->summary()['finance'];

        $this->assertSame('0.0000', $finance['expenses']);
        $this->assertSame('1000.0000', $finance['profit']);
    }

    public function test_the_overdue_invoice_list_leads_with_the_longest_overdue(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        // 40 days past due, but only 300 outstanding.
        $oldest = Invoice::factory()->issued()->create([
            'total' => '300.0000',
            'paid_amount' => '0.0000',
            'balance' => '300.0000',
            'due_date' => '2026-08-19',
        ]);

        // 5 days past due and much larger. A list ordered by balance would open
        // with this one, and it is the wrong row to open with: the invoice nobody
        // has answered for is the one that will not get paid by chasing harder.
        Invoice::factory()->issued()->create([
            'total' => '9000.0000',
            'paid_amount' => '0.0000',
            'balance' => '9000.0000',
            'due_date' => '2026-09-23',
        ]);

        $list = (new DashboardMetrics($now))->summary()['upcoming']['overdue_invoices'];

        $this->assertCount(2, $list);
        $this->assertSame($oldest->id, $list[0]['id']);
        $this->assertSame(40, $list[0]['days_overdue']);
        $this->assertSame('300.0000', $list[0]['balance']);
        $this->assertSame('2026-08-19', $list[0]['due_date']);
    }

    public function test_the_overdue_invoice_list_excludes_settled_and_undated_invoices(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        // Paid, and long past due. The balance is what decides, not the calendar.
        Invoice::factory()->issued()->create([
            'total' => '1000.0000',
            'paid_amount' => '1000.0000',
            'balance' => '0.0000',
            'due_date' => '2026-01-01',
        ]);

        // No due date, so no clock has started. There is nothing to be late against.
        Invoice::factory()->issued()->create([
            'total' => '1000.0000',
            'paid_amount' => '0.0000',
            'balance' => '1000.0000',
            'due_date' => null,
        ]);

        $list = (new DashboardMetrics($now))->summary()['upcoming']['overdue_invoices'];

        $this->assertSame([], $list);
    }

    public function test_the_revenue_chart_shares_the_volume_chart_buckets(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        $metrics = (new DashboardMetrics($now))->summary();

        $volume = $metrics['charts']['volume_by_month'];
        $money = $metrics['charts']['revenue_vs_expense'];

        $this->assertCount(12, $money);

        // Two charts on one card, so a reader will line a revenue spike up against
        // a shipment month. If the axes are not identical that comparison is wrong,
        // and nobody can tell by looking.
        $this->assertSame(
            array_column($volume, 'month_key'),
            array_column($money, 'month_key'),
        );

        // The month in progress is flagged, because a revenue line that stops short
        // mid-month reads as a collapse rather than an unfinished month.
        $this->assertTrue(end($money)['is_current']);
        $this->assertFalse($money[0]['is_current']);

        // And an empty history is zero-filled rather than absent, for the same
        // reason the volume chart is: a gap reads as missing data, a zero as a
        // quiet month.
        $this->assertSame('0.0000', $money[0]['revenue']);
        $this->assertSame('0.0000', $money[0]['expenses']);
    }

    public function test_volume_by_month_is_twelve_zero_filled_buckets_ending_with_this_month(): void
    {
        $months = (new DashboardMetrics(CarbonImmutable::parse('2026-09-28 09:00:00')))
            ->summary()['charts']['volume_by_month'];

        $this->assertCount(12, $months);

        // Oldest first, ending with the month in progress, and every label is
        // already formatted for an axis. The clock is pinned because a bucket
        // name is only assertable against the same moment that produced it.
        $this->assertSame('Oct 2025', $months[0]['month']);
        $this->assertSame('Sep 2026', $months[11]['month']);
        $this->assertSame('2026-09', $months[11]['month_key']);

        // A quiet month is a zero, not a gap: a missing bucket reads as missing
        // data and a bar of zero reads as the answer.
        $this->assertSame(0, $months[0]['total']);
    }

    public function test_volume_counts_a_consignment_in_the_month_it_was_raised(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        $raised = Shipment::factory()->create([
            'created_at' => $now->subMonths(2)->startOfMonth()->addDays(3),
            'updated_at' => $now->subMonths(2)->startOfMonth()->addDays(3),
        ]);

        Shipment::factory()->create([
            'created_at' => $now->startOfMonth()->addHours(2),
            'updated_at' => $now->startOfMonth()->addHours(2),
        ]);

        // Deliberately far outside the window, to prove the series is bounded.
        Shipment::factory()->create([
            'created_at' => $now->subMonths(20),
            'updated_at' => $now->subMonths(20),
        ]);

        $months = (new DashboardMetrics($now->toDateTime()))
            ->summary()['charts']['volume_by_month'];

        $this->assertSame(1, $months[9]['total'], 'The consignment raised two months ago.');
        $this->assertSame(1, $months[11]['total'], 'The consignment raised this month.');
        $this->assertSame(0, $months[10]['total'], 'Nothing raised last month.');

        // Counted on created_at, not etd. A consignment raised months ago for a
        // sailing next week is this month's work or it is nothing - the sailing is
        // a forecast, and the volume figure describes work, not intentions. The
        // factory default leaves `etd` in the future under the pinned clock.
        $this->assertNotNull($raised->etd);
    }

    public function test_status_distribution_carries_every_status_in_lifecycle_order(): void
    {
        Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create();

        $distribution = $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->json('data.charts.status_distribution');

        // All seven, in the enum's order, so the pie holds the same colours in the
        // same places between loads. A chart whose wedges move on refresh is read
        // as new information.
        $this->assertSame(
            ShipmentStatus::values(),
            array_column($distribution, 'status'),
        );

        // Including the empty ones, which makes "nothing cancelled" readable.
        $byStatus = array_column($distribution, 'count', 'status');
        $this->assertSame(1, $byStatus['in_transit']);
        $this->assertSame(0, $byStatus['cancelled']);

        // The label is the model's, not the browser's, for the same reason
        // ShipmentResource sends one.
        $this->assertSame(
            'In transit',
            $distribution[array_search('in_transit', array_column($distribution, 'status'), true)]['label'],
        );
    }

    public function test_top_customers_counts_by_consignee(): void
    {
        $busy = Party::factory()->create(['code' => 'CON-BUSY', 'name' => 'Harbour Freight Imports']);
        $quiet = Party::factory()->create(['code' => 'CON-QUIET', 'name' => 'Quiet Co']);

        Shipment::factory()->count(3)->create(['consignee_id' => $busy->id]);
        Shipment::factory()->create(['consignee_id' => $quiet->id]);

        $top = $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->json('data.charts.top_customers');

        $this->assertSame([
            ['code' => 'CON-BUSY', 'customer' => 'Harbour Freight Imports', 'count' => 3],
            ['code' => 'CON-QUIET', 'customer' => 'Quiet Co', 'count' => 1],
        ], $top);

        // The consignee, not the shipper: the consignee is the customer, and an
        // account manager is measured against them.
        $this->assertNotSame(
            Shipment::query()->first()->shipper_id,
            $busy->id,
        );
    }

    public function test_the_charts_leave_out_consignments_with_nobody_to_credit(): void
    {
        // No consignee and no carrier is normal on a draft, and "which customer" is
        // unanswerable for it. Crediting it to a blank row at the top of a chart
        // called "top customers" would be worse than leaving it out.
        Shipment::factory()->unassigned()->create([
            'consignee_id' => null,
            'carrier_id' => null,
        ]);

        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.shipments.total', 1)
            ->assertJsonPath('data.charts.top_customers', [])
            ->assertJsonPath('data.charts.top_carriers', []);
    }

    public function test_top_carriers_breaks_ties_deterministically(): void
    {
        $first = Carrier::factory()->create(['code' => 'CAR-B', 'name' => 'Braemar Lines']);
        $second = Carrier::factory()->create(['code' => 'CAR-A', 'name' => 'Anchor Shipping']);

        Shipment::factory()->create(['carrier_id' => $first->id]);
        Shipment::factory()->create(['carrier_id' => $second->id]);

        // Two rows with one consignment each. Ordered by code so the chart does not
        // swap its two bars between requests and appear to flicker.
        $this->actingAsAdmin()->getJson('/api/v1/dashboard')
            ->assertJsonPath('data.charts.top_carriers', [
                ['code' => 'CAR-A', 'carrier' => 'Anchor Shipping', 'count' => 1],
                ['code' => 'CAR-B', 'carrier' => 'Braemar Lines', 'count' => 1],
            ]);
    }

    public function test_the_charts_are_capped(): void
    {
        foreach (range(1, 10) as $ignored) {
            Shipment::factory()->create();
        }

        // A preview, not a report. Past about eight entries a horizontal bar
        // chart stops being readable, and the longer answer is the reports module.
        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        $this->assertCount(8, $response->json('data.charts.top_customers'));
        $this->assertCount(8, $response->json('data.charts.top_carriers'));
        $this->assertSame(10, $response->json('data.shipments.total'));
    }

    public function test_the_upcoming_lists_hold_the_next_five_open_consignments(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        foreach (range(1, 7) as $step) {
            Shipment::factory()->withStatus(ShipmentStatus::Booked)->create([
                'etd' => $now->addDays($step * 3)->format('Y-m-d'),
                'eta' => $now->addDays($step * 3 + 30)->format('Y-m-d'),
            ]);
        }

        $upcoming = (new DashboardMetrics($now->toDateTime()))->summary()['upcoming'];

        $this->assertCount(5, $upcoming['etd']);
        $this->assertCount(5, $upcoming['eta']);

        // Nearest first, so the top row is the one happening today rather than the
        // one happening furthest away.
        $etd = array_column($upcoming['etd'], 'etd');
        $sorted = $etd;
        sort($sorted);
        $this->assertSame($sorted, $etd);
    }

    public function test_the_upcoming_lists_exclude_consignments_that_are_already_behind_us(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        $done = Shipment::factory()->delivered()->create([
            'etd' => $now->subWeek()->format('Y-m-d'),
            'eta' => $now->subDay()->format('Y-m-d'),
        ]);

        $upcoming = (new DashboardMetrics($now->toDateTime()))->summary()['upcoming'];

        // A delivered consignment is not "upcoming". Its history lives on the
        // tracking screen, and offering it on a shift-start list would be wrong
        // twice over: it is not coming, and it is not late.
        $this->assertSame([], $upcoming['etd']);
        $this->assertSame([], $upcoming['eta']);
        $this->assertSame([], $upcoming['late']);

        $this->assertNotContains($done->id, array_column($upcoming['late'], 'id'));
    }

    public function test_a_preview_row_carries_the_consignment_and_its_label(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        $customer = Party::factory()->create(['name' => 'Meridian Foods']);
        $shipment = Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create([
            'consignee_id' => $customer->id,
            'etd' => $now->addDay()->format('Y-m-d'),
            'eta' => $now->addWeek()->format('Y-m-d'),
        ]);

        $row = (new DashboardMetrics($now->toDateTime()))
            ->summary()['upcoming']['etd'][0];

        $this->assertSame($shipment->id, $row['id']);
        $this->assertSame($shipment->reference, $row['reference']);
        $this->assertSame('Meridian Foods', $row['customer']);
        $this->assertSame('in_transit', $row['status']);
        $this->assertSame('In transit', $row['status_label']);
        $this->assertFalse($row['is_late']);

        // `Y-m-d`, matching ShipmentResource. An ETD is a day on a booking
        // confirmation, and sending a time component invites a timezone bug on a
        // field that has none.
        $this->assertSame($shipment->etd->format('Y-m-d'), $row['etd']);
    }

    public function test_the_late_list_holds_the_consignments_the_late_count_counts(): void
    {
        $now = CarbonImmutable::parse('2026-09-28 09:00:00');

        foreach (range(1, 2) as $ignored) {
            Shipment::factory()->withStatus(ShipmentStatus::InTransit)->create([
                'etd' => $now->subDays(30)->format('Y-m-d'),
                'eta' => $now->subDays(3)->format('Y-m-d'),
            ]);
        }

        $summary = (new DashboardMetrics($now->toDateTime()))->summary();

        $this->assertSame(2, $summary['shipments']['late_count']);
        $this->assertCount(2, $summary['upcoming']['late']);

        // Same records, and each row's own flag agrees with the list it is in -
        // the agreement that went wrong between the customs worklists once already.
        foreach ($summary['upcoming']['late'] as $row) {
            $this->assertTrue($row['is_late']);
        }
    }

    public function test_a_draft_with_no_dates_is_late_on_no_list(): void
    {
        // The case the `late` rule exists for. A missing ETA is not an overrun, and
        // treating it as one would light up every draft in the system on a page
        // whose entire job is to be believed.
        Shipment::factory()->unassigned()->create();

        $response = $this->actingAsAdmin()->getJson('/api/v1/dashboard');

        $response->assertJsonPath('data.shipments.late_count', 0)
            ->assertJsonPath('data.upcoming.late', []);

        $this->assertFalse(Shipment::query()->first()->isLate());
    }
}
