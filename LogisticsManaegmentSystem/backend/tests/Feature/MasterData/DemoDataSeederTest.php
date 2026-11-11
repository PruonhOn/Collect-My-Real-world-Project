<?php

namespace Tests\Feature\MasterData;

use App\Enums\BookingStatus;
use App\Enums\ContainerStatus;
use App\Enums\CustomsStatus;
use App\Enums\DeliveryStatus;
use App\Enums\PartyType;
use App\Enums\ShipmentStatus;
use App\Enums\SiteKind;
use App\Enums\TrackingMilestone;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Driver;
use App\Models\Party;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShipmentCustomsClearance;
use App\Models\ShipmentDelivery;
use App\Models\ShipmentTracking;
use App\Models\Site;
use App\Models\Truck;
use App\Models\Vessel;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The demo data set.
 *
 * The properties worth defending are the ones a human would otherwise have to
 * eyeball: every surface gets at least one record, the records reference each
 * other coherently, and re-running the seeder does not duplicate anything.
 *
 * It lives under MasterData/ because that is where the data set started, but it
 * now covers shipments too - the coherence checks are the point, and a
 * consignment pointing at a customer where a shipper belongs is exactly the sort
 * of nonsense that is only visible if something asserts it.
 */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemoData(): void
    {
        $this->seed(DemoDataSeeder::class);
    }

    public function test_every_surface_gets_at_least_one_record(): void
    {
        $this->seedDemoData();

        foreach ([Party::class, Carrier::class, Vessel::class, Port::class, Site::class, Truck::class, Driver::class, Booking::class, Shipment::class] as $model) {
            $this->assertGreaterThan(
                0,
                $model::query()->count(),
                class_basename($model).' has no demo records.',
            );
        }
    }

    public function test_the_ten_surfaces_are_all_represented(): void
    {
        $this->seedDemoData();

        // The three party surfaces and the two site surfaces share tables, so
        // counting rows would hide a surface that was never seeded. `pluck` is
        // what makes the DISTINCT reach SQL - `distinct()->get()` still selects
        // every column and so comes back with a row per party. The columns are
        // cast to enums, so compare the backing values.
        $partyTypes = Party::query()->distinct()->pluck('type')
            ->map(fn (PartyType $type): string => $type->value)
            ->unique()
            ->values()
            ->all();

        $siteKinds = Site::query()->distinct()->pluck('kind')
            ->map(fn (SiteKind $kind): string => $kind->value)
            ->unique()
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing(['customer', 'shipper', 'consignee'], $partyTypes);
        $this->assertEqualsCanonicalizing(['warehouse', 'factory'], $siteKinds);
    }

    /**
     * The whole reason the demo data is hand-written: a driver attached to a
     * truck owned by a different carrier is not a state any screen should render.
     */
    public function test_a_drivers_truck_belongs_to_the_same_carrier(): void
    {
        $this->seedDemoData();

        Driver::query()
            ->with('truck')
            ->get()
            ->each(function (Driver $driver): void {
                if ($driver->truck === null) {
                    return;
                }

                $this->assertSame(
                    $driver->carrier_id,
                    $driver->truck->carrier_id,
                    "Driver {$driver->code} is paired with another carrier's truck.",
                );
            });
    }

    public function test_no_demo_factory_carries_storage_only_fields(): void
    {
        $this->seedDemoData();

        Site::query()
            ->where('kind', 'factory')
            ->get()
            ->each(function (Site $site): void {
                $this->assertNull($site->site_type);
                $this->assertNull($site->capacity_pallets);
            });
    }

    public function test_a_warehouse_carries_its_storage_fields(): void
    {
        $this->seedDemoData();

        $warehouse = Site::query()->where('kind', 'warehouse')->firstOrFail();

        $this->assertNotNull($warehouse->site_type);
        $this->assertGreaterThan(0, $warehouse->capacity_pallets);
    }

    /**
     * The demo data has to be a demonstration of coherent references, not just
     * of non-empty tables: a consignment naming a customer as its shipper is a
     * state no screen should ever have to render.
     */
    public function test_a_shipment_names_a_shipper_and_a_consignee_of_the_right_kind(): void
    {
        $this->seedDemoData();

        Shipment::query()
            ->with('shipper', 'consignee')
            ->get()
            ->each(function (Shipment $shipment): void {
                $this->assertSame(
                    PartyType::Shipper,
                    $shipment->shipper?->type,
                    "Shipment {$shipment->reference} does not name a shipper.",
                );

                $this->assertSame(
                    PartyType::Consignee,
                    $shipment->consignee?->type,
                    "Shipment {$shipment->reference} does not name a consignee.",
                );
            });
    }

    /**
     * A demo set that only contains the happy path leaves the problem screens
     * empty, so the states a fresh install needs to demonstrate are seeded on
     * purpose: something unassigned, something overdue, something closed.
     */
    public function test_the_demo_set_contains_the_states_the_shipment_screens_filter_for(): void
    {
        $this->seedDemoData();

        $this->assertGreaterThan(
            0,
            Shipment::query()->unassigned()->count(),
            'No unassigned draft, so the unassigned filter has nothing to find.',
        );

        $this->assertGreaterThan(
            0,
            Shipment::query()
                ->open()
                ->whereNotNull('eta')
                ->whereDate('eta', '<', now()->toDateString())
                ->count(),
            'No overdue consignment, so the late filter has nothing to find.',
        );

        $this->assertGreaterThan(
            0,
            Shipment::query()
                ->whereIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
                ->count(),
            'No closed consignment, so the open-only filter cannot be told apart from no filter.',
        );

        $this->assertGreaterThan(
            0,
            Shipment::query()->whereNotNull('vessel_id')->count(),
            'No sea freight, so the vessel and port references are never demonstrated.',
        );
    }

    /**
     * A booking names the same kinds of party a shipment does, so the same check
     * applies: a demo set with a booking pointing at a customer as its shipper
     * would render a blank dropdown and teach the wrong thing about the screen.
     */
    public function test_a_booking_names_a_shipper_and_a_consignee_of_the_right_kind(): void
    {
        $this->seedDemoData();

        Booking::query()
            ->with('shipper', 'consignee')
            ->get()
            ->each(function (Booking $booking): void {
                $this->assertSame(
                    PartyType::Shipper,
                    $booking->shipper?->type,
                    "Booking {$booking->reference} does not name a shipper.",
                );

                $this->assertSame(
                    PartyType::Consignee,
                    $booking->consignee?->type,
                    "Booking {$booking->reference} does not name a consignee.",
                );
            });
    }

    /**
     * The booking screens filter for states a happy-path demo would never show:
     * something unassigned, something awaiting an answer, something closed.
     *
     * Exact counts, not "greater than zero". A seed row that names a carrier or a
     * booking which does not exist resolves to null, so a typo turns an assigned
     * record into an unassigned one and the filter quietly starts returning rows
     * the data never meant to include - while every "is there at least one"
     * assertion here still passes. Pinning the numbers makes that a failure.
     */
    public function test_the_demo_set_contains_the_states_the_booking_screens_filter_for(): void
    {
        $this->seedDemoData();

        $this->assertSame(
            1,
            Booking::query()->unassigned()->count(),
            'Exactly one booking is meant to be unsent; anything more means a named carrier failed to resolve.',
        );

        $this->assertSame(
            1,
            Booking::query()->awaitingConfirmation()->count(),
            'Exactly one request is meant to be awaiting an answer.',
        );

        $this->assertSame(
            1,
            Booking::query()->whereIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::Expired->value,
            ])->count(),
            'Exactly one booking is meant to be closed, so open-only can be told apart from no filter.',
        );

        /*
         * The record the pending filter exists to find has to be a request that
         * actually went somewhere. A pending booking with no carrier is an
         * unassigned draft wearing a different label.
         */
        $pending = Booking::query()->awaitingConfirmation()->sole();

        $this->assertNotNull(
            $pending->carrier_id,
            'The unanswered request names a carrier that does not exist in the demo set.',
        );
        $this->assertSame(
            0,
            $pending->shipments()->count(),
            'The unanswered request is meant to have produced no consignment yet.',
        );
    }

    /**
     * The demo set has to show the link in both directions, because each side
     * proves something different. A shipment with no booking is what makes the
     * nullable column honest - a consignment from a paper bill of lading is
     * normal, and a demo where every row points at a request teaches that it is
     * not. A booking with no shipment is the case the module exists for: a real
     * request on file that has not become cargo.
     */
    public function test_the_demo_set_shows_the_booking_link_from_both_directions(): void
    {
        $this->seedDemoData();

        $this->assertGreaterThan(
            0,
            Shipment::query()->whereNotNull('booking_id')->count(),
            'No consignment is raised from a booking, so the link is never demonstrated.',
        );

        $this->assertGreaterThan(
            0,
            Shipment::query()->whereNull('booking_id')->count(),
            'Every consignment points at a booking, which misrepresents the nullable column.',
        );

        $this->assertGreaterThan(
            0,
            Booking::query()->whereDoesntHave('shipments')->count(),
            'Every booking has a consignment, so the request-without-cargo case is never shown.',
        );
    }

    /**
     * A shipment raised from a booking copies what the carrier confirmed, so the
     * two agreeing is the demo being coherent. A shipment whose cargo silently
     * differs from the request it came from would be a data-entry error presented
     * as a worked example.
     */
    public function test_a_shipment_raised_from_a_booking_agrees_with_it(): void
    {
        $this->seedDemoData();

        Shipment::query()
            ->whereNotNull('booking_id')
            ->with('booking')
            ->get()
            ->each(function (Shipment $shipment): void {
                $booking = $shipment->booking;

                $this->assertNotNull($booking, "Shipment {$shipment->reference} points at nothing.");

                $this->assertSame(
                    $booking->shipper_id,
                    $shipment->shipper_id,
                    "Shipment {$shipment->reference} disagrees with {$booking->reference} on the shipper.",
                );

                $this->assertSame(
                    $booking->consignee_id,
                    $shipment->consignee_id,
                    "Shipment {$shipment->reference} disagrees with {$booking->reference} on the consignee.",
                );
            });
    }

    /**
     * `db:seed` is run more than once during setup, so a seeder that only
     * inserts would fail on the unique indices.
     */
    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->seedDemoData();
        $before = $this->recordCounts();

        $this->seedDemoData();
        $after = $this->recordCounts();

        $this->assertSame($before, $after);
    }

    public function test_the_demo_flag_gates_the_whole_data_set(): void
    {
        config(['app.seed_demo_data' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Party::query()->count());
        $this->assertSame(0, Driver::query()->count());
    }

    public function test_the_demo_flag_includes_the_whole_data_set(): void
    {
        config(['app.seed_demo_data' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Party::query()->count());
        $this->assertGreaterThan(0, Driver::query()->count());
    }

    /**
     * Every consignment has a tracking row, even though `tracking` has no endpoint
     * that could create one.
     *
     * Seeded through DatabaseSeeder rather than DemoDataSeeder on purpose, because
     * the gap this guards against is not in the demo seeder but in what wraps it.
     * DatabaseSeeder suppressed model events, so the `created` listener on Shipment
     * that provisions the row never ran, and a fresh install got consignments with
     * no tracking at all - a silent failure that showed up as an empty screen
     * rather than an error. Seeding the same way an installation does is the only
     * version of this test that would have caught it.
     */
    public function test_every_seeded_consignment_has_a_tracking_row(): void
    {
        config(['app.seed_demo_data' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Shipment::query()->count());

        $this->assertSame(
            0,
            Shipment::query()->whereDoesntHave('tracking')->count(),
            'a consignment was seeded without the tracking row its own model creates'
        );

        $this->assertSame(
            Shipment::query()->count(),
            ShipmentTracking::query()->count(),
            'tracking rows and consignments should be one to one'
        );
    }

    /**
     * The tracking reports are a set of cases rather than four rows of noise.
     *
     * Each one exists to make a filter on the tracking screen return something, so
     * the assertions are about the filters finding them - a demo set that merely
     * contained tracking data would still leave the worklists untested.
     */
    public function test_the_demo_set_covers_the_tracking_worklists(): void
    {
        $this->seedDemoData();

        // Ordinary, on schedule, reported recently: on neither list.
        $onSchedule = Shipment::query()->where('reference', 'SHP-2026-0001')->firstOrFail();
        $this->assertFalse($onSchedule->tracking->isDelayed());
        $this->assertFalse($onSchedule->tracking->isStale());

        // Held at customs with a revised date: on both lists at once, which is the
        // point - they are not the same set and the demo set has to show it.
        $held = Shipment::query()->where('reference', 'SHP-2026-0002')->firstOrFail();
        $this->assertSame(TrackingMilestone::CustomsHold, $held->tracking->last_milestone);
        $this->assertTrue($held->tracking->isDelayed());
        $this->assertTrue($held->tracking->isStale());

        // Delivered: excluded from the worklists because the consignment is closed,
        // even though the row itself has been quiet for weeks.
        $delivered = Shipment::query()->where('reference', 'SHP-2026-0003')->firstOrFail();
        $this->assertSame(TrackingMilestone::Delivered, $delivered->tracking->last_milestone);

        // Never reported on: the stale filter's other branch, where the absence of
        // a report is itself the finding.
        $draft = Shipment::query()->where('reference', 'SHP-2026-0004')->firstOrFail();
        $this->assertTrue($draft->tracking->isUnreported());
        $this->assertTrue($draft->tracking->isStale());

        $this->assertSame(
            1,
            ShipmentTracking::query()->delayed()->open()->count(),
            'only the held consignment is both delayed and still moving'
        );

        $this->assertSame(
            2,
            ShipmentTracking::query()->stale()->open()->count(),
            'the held consignment and the unreported draft, but not the delivered one'
        );
    }

    /**
     * The equipment is a set of cases rather than four rows of noise.
     *
     * Each consignment exists to make a filter on the container screen return
     * something, so the assertions are about the worklist finding them. The
     * delivered box matters as much as the held one: it has to be *excluded*,
     * because its last state before that was a customs hold and a worklist built
     * on the status column alone would happily put it back.
     */
    public function test_the_demo_set_covers_the_container_worklist(): void
    {
        $this->seedDemoData();

        $atSea = Shipment::query()->where('reference', 'SHP-2026-0001')->firstOrFail();
        $this->assertCount(2, $atSea->containers, 'a two-box manifest, which is the ordinary case');
        $atSea->containers->each(function (ShipmentContainer $box): void {
            $this->assertSame(ContainerStatus::InTransit, $box->status);
            $this->assertFalse($box->needsAttention());
            // The figures a carrier actually quotes: a rating, and a gross that sits
            // under it. A demo set with a null weight on a loaded box would leave
            // the utilisation column blank on every row.
            $this->assertNotNull($box->utilisationPercent());
        });

        $held = Shipment::query()->where('reference', 'SHP-2026-0002')->firstOrFail();
        $this->assertTrue($held->containers->first()->needsAttention());
        $this->assertSame(ContainerStatus::CustomsHold, $held->containers->first()->status);

        $delivered = Shipment::query()->where('reference', 'SHP-2026-0003')->firstOrFail();
        $this->assertTrue($delivered->containers->first()->isClosed());
        $this->assertFalse($delivered->containers->first()->needsAttention());

        // A draft with no equipment. Not an oversight in the seeder - it is the only
        // thing that makes "a consignment need not have containers" true in the demo
        // data rather than just in a factory default.
        $draft = Shipment::query()->where('reference', 'SHP-2026-0004')->firstOrFail();
        $this->assertCount(0, $draft->containers);

        $this->assertSame(
            1,
            ShipmentContainer::query()->open()->needsAttention()->count(),
            'only the held box, and not the delivered one'
        );
    }

    /**
     * The filings are a set of cases, and the one that matters most is the pair on a
     * single consignment: a transshipment clears at more than one port and the demo
     * set has to show it or the multi-port design is untested against real data.
     */
    public function test_the_demo_set_covers_the_customs_worklists(): void
    {
        $this->seedDemoData();

        $transshipped = Shipment::query()->where('reference', 'SHP-2026-0001')->firstOrFail();
        $transshipped->load(['customsClearances.port']);
        $this->assertCount(
            2,
            $transshipped->customsClearances,
            'a consignment that cleared at two ports has to have both on the record'
        );

        $ports = $transshipped->customsClearances->pluck('port.un_locode');
        $this->assertContains('SGSIN', $ports);
        $this->assertContains('ESALG', $ports);

        // Cleared at origin, so on neither list.
        $origin = $transshipped->customsClearances->firstWhere('port.un_locode', 'SGSIN');
        $this->assertSame(CustomsStatus::Completed, $origin->status);
        $this->assertTrue($origin->isClosed());
        $this->assertFalse($origin->isHeld());

        // Refused at the transshipment hub, so it is on the problem list and not the
        // held list. The consignment is still at sea waiting on the answer, which is
        // what makes the row worth somebody's time rather than a historical note.
        $refused = $transshipped->customsClearances->firstWhere('port.un_locode', 'ESALG');
        $this->assertSame(CustomsStatus::Rejected, $refused->status);
        $this->assertTrue($refused->needsAttention());
        $this->assertFalse($refused->isHeld());

        // The filing that has not happened yet - no entry number at all, because
        // nothing has been submitted. Stuck rather than wrong, which is the
        // distinction the two worklists are built on and why it is on the other list.
        $held = Shipment::query()->where('reference', 'SHP-2026-0002')->firstOrFail();
        $clearance = $held->customsClearances()->firstOrFail();
        $this->assertSame(CustomsStatus::Pending, $clearance->status);
        $this->assertNull($clearance->entry_number);
        $this->assertTrue($clearance->isHeld());
        $this->assertFalse($clearance->needsAttention());

        $this->assertSame(
            1,
            ShipmentCustomsClearance::query()->needsAttention()->count(),
            'only the refusal, and not the filing that has not been made yet'
        );

        $this->assertSame(
            1,
            ShipmentCustomsClearance::query()->open()->held()->count(),
            'the filing blocked on a certificate nobody has produced'
        );
    }

    /**
     * The deliveries cover the one-to-many case and the two worklists.
     *
     * The consignment with two legs is the case a single-row design would have had
     * to overwrite, and the overdue leg is the alarm the module exists to raise - so
     * the pair is what the assertions are about.
     */
    public function test_the_demo_set_covers_the_delivery_worklists(): void
    {
        $this->seedDemoData();

        // At sea and two ports from its destination: no collection booked, because
        // booking one would be booking a guess.
        $atSea = Shipment::query()->where('reference', 'SHP-2026-0001')->firstOrFail();
        $this->assertCount(0, $atSea->deliveries);

        // Booked for a slot that passed while the consignment sat at customs: the
        // overdue row, and the reason is visible on the consignment above it.
        $blocked = Shipment::query()->where('reference', 'SHP-2026-0002')->firstOrFail();
        $this->assertCount(1, $blocked->deliveries);
        $this->assertTrue($blocked->deliveries->first()->isOverdue());
        $this->assertTrue($blocked->deliveries->first()->isOutstanding());

        // Two legs: a failed attempt and the successful retry, in order. The failure
        // is what explains the retry, so it has to still be there.
        $delivered = Shipment::query()->where('reference', 'SHP-2026-0003')->firstOrFail();
        $this->assertCount(2, $delivered->deliveries);

        [$failed, $succeeded] = $delivered->deliveries->all();

        $this->assertSame(DeliveryStatus::Failed, $failed->status);
        $this->assertTrue($failed->hasFailed());
        // A failed attempt is not the end: the cargo is still in the vehicle.
        $this->assertTrue($failed->isOutstanding());
        $this->assertNotNull($failed->failure_reason);
        $this->assertNull($failed->delivered_at);

        $this->assertSame(DeliveryStatus::Delivered, $succeeded->status);
        $this->assertTrue($succeeded->isClosed());
        $this->assertNotNull($succeeded->pod_reference);

        $this->assertSame(
            1,
            ShipmentDelivery::query()->overdue()->count(),
            'the leg booked for a slot that has passed, and not either of the 0003 legs'
        );

        // A failed attempt is on the outstanding list, which is why that list is not
        // the same as "not cancelled".
        $this->assertSame(
            2,
            ShipmentDelivery::query()->open()->count(),
            'the overdue leg and the failed attempt'
        );
    }

    /**
     * The three operations modules are keyed three different ways, and a seeder that
     * got one of them wrong would still run - it would just quietly move a record or
     * collapse two consignments onto one. These assertions are about the keys
     * resolving, not about the counts.
     */
    public function test_the_seeded_operations_data_hangs_off_the_right_consignments(): void
    {
        $this->seedDemoData();

        $byReference = fn (string $reference): Shipment => Shipment::query()
            ->where('reference', $reference)
            ->firstOrFail();

        $containers = ShipmentContainer::query()->with('shipment')->get();
        $this->assertCount(4, $containers);
        $containers->each(function (ShipmentContainer $box): void {
            $this->assertContains(
                $box->shipment->reference,
                ['SHP-2026-0001', 'SHP-2026-0002', 'SHP-2026-0003'],
                'a container seeded against a consignment that is not one of the three'
            );
        });

        $clearances = ShipmentCustomsClearance::query()->with('port')->get();
        $this->assertCount(4, $clearances);
        $clearances->each(function (ShipmentCustomsClearance $clearance): void {
            $this->assertNotNull(
                $clearance->port,
                'every seeded filing is at a port the seeder actually knows about'
            );
        });

        $deliveries = ShipmentDelivery::query()->with(['truck', 'driver'])->get();
        $this->assertCount(3, $deliveries);
        $deliveries->each(function (ShipmentDelivery $delivery): void {
            $this->assertNotNull($delivery->truck, 'a delivery seeded with a plate that does not exist');
            $this->assertNotNull($delivery->driver, 'a delivery seeded with a driver that does not exist');
        });

        // And the specific pairing that a container key missing its shipment half
        // would break: the two boxes on 0001 must be on 0001, not split.
        $this->assertCount(2, $byReference('SHP-2026-0001')->containers);
    }

    /**
     * @return array<string, int>
     */
    private function recordCounts(): array
    {
        return [
            'parties' => Party::query()->count(),
            'carriers' => Carrier::query()->count(),
            'vessels' => Vessel::query()->count(),
            'ports' => Port::query()->count(),
            'sites' => Site::query()->count(),
            'trucks' => Truck::query()->count(),
            'drivers' => Driver::query()->count(),
            'bookings' => Booking::query()->count(),
            'shipments' => Shipment::query()->count(),
            // The three operations modules, which are keyed three different ways and
            // so are the real test of the seeder's idempotency. A composite key that
            // does not include the shipment would still "not duplicate" while quietly
            // moving a container onto whichever consignment was written last.
            'shipment_containers' => ShipmentContainer::query()->count(),
            'shipment_customs_clearances' => ShipmentCustomsClearance::query()->count(),
            'shipment_deliveries' => ShipmentDelivery::query()->count(),
        ];
    }
}
