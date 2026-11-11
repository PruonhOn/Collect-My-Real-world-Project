<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\CarrierType;
use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\ContainerStatus;
use App\Enums\CustomsStatus;
use App\Enums\DeliveryStatus;
use App\Enums\ExpenseCategory;
use App\Enums\Incoterm;
use App\Enums\InvoiceStatus;
use App\Enums\PartyType;
use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Enums\SiteKind;
use App\Enums\TrackingMilestone;
use App\Models\Booking;
use App\Models\Carrier;
use App\Models\Charge;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShipmentCustomsClearance;
use App\Models\ShipmentDelivery;
use App\Models\Site;
use App\Models\Truck;
use App\Models\Vessel;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A small, coherent demonstration data set for master data, bookings and
 * shipments.
 *
 * Deliberately hand-written rather than factory-generated. A demo has to look
 * like a real business, because that is the point of it: if the carriers are
 * `fake()->company()` then nobody can tell whether a screen is rendering the
 * data correctly or merely rendering nonsense convincingly. Every record here
 * is one an operations team would recognise, and they reference each other the
 * way real records do - a driver's truck belongs to the driver's carrier, the
 * warehouse a shipment would route through is a warehouse.
 *
 * Idempotent by natural key: re-running updates in place rather than colliding
 * with the unique indices, so `db:seed` is safe to repeat.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->parties();
        $this->carriers();
        $this->vessels();
        $this->ports();
        $this->sites();
        $this->fleet();
        $this->bookings();
        $this->shipments();
        $this->containers();
        $this->customsClearances();
        $this->deliveries();
        $this->invoices();
        $this->charges();
        $this->payments();
        $this->expenses();
    }

    /**
     * Customers, shippers and consignees.
     *
     * The three surfaces share a table, so the type is stated explicitly on
     * every row rather than relying on a default.
     */
    private function parties(): void
    {
        $parties = [
            // Customers: the accounts this logistics company bills.
            [
                'type' => PartyType::Customer,
                'code' => 'ACME',
                'name' => 'Acme Electronics Pte Ltd',
                'contact_name' => 'Priya Raman',
                'contact_email' => 'priya.raman@acme-electronics.example',
                'contact_phone' => '+65-6555-0101',
                'tax_number' => 'SG199001234A',
                'address_line_1' => '10 Marina Boulevard',
                'city' => 'Singapore',
                'postal_code' => '018981',
                'country_code' => 'SG',
                'notes' => 'Key account. Air freight lane SIN-US west coast.',
            ],
            [
                'type' => PartyType::Customer,
                'code' => 'NORTHWIND',
                'name' => 'Northwind Trading Co',
                'contact_name' => 'Daniel Okonkwo',
                'contact_email' => 'daniel@northwind-trading.example',
                'contact_phone' => '+1-206-555-0142',
                'tax_number' => 'US91-4471283',
                'address_line_1' => '1420 Alaskan Way',
                'city' => 'Seattle',
                'state' => 'WA',
                'postal_code' => '98101',
                'country_code' => 'US',
                'notes' => null,
            ],

            // Shippers: the cargo owners. Usually, but not always, a customer too.
            [
                'type' => PartyType::Shipper,
                'code' => 'SHPR-HARVEST',
                'name' => 'Harvest Foods International',
                'contact_name' => 'Mei Ling Tan',
                'contact_email' => 'mei.tan@harvest-foods.example',
                'contact_phone' => '+65-6555-0188',
                'tax_number' => 'SG200455667Z',
                'address_line_1' => '28 Tuas Avenue',
                'city' => 'Singapore',
                'postal_code' => '639800',
                'country_code' => 'SG',
                'notes' => 'Reefer cargo. Needs cold chain on every leg.',
            ],
            [
                'type' => PartyType::Shipper,
                'code' => 'SHPR-TEXTILE',
                'name' => 'Textile Mills Sdn Bhd',
                'contact_name' => 'Aisyah Rahman',
                'contact_email' => 'aisyah@textilemills.example',
                'contact_phone' => '+60-3-5555-0119',
                'tax_number' => 'MY-1188220-K',
                'address_line_1' => ' Jalan Gudang, Section 14',
                'city' => 'Shah Alam',
                'state' => 'Selangor',
                'postal_code' => '40000',
                'country_code' => 'MY',
                'notes' => null,
            ],

            // Consignees: who receives it. Rarely the same legal entity as the
            // shipper, which is exactly why they are a separate surface.
            [
                'type' => PartyType::Consignee,
                'code' => 'CONS-SEAHAWK',
                'name' => 'Seahawk Distribution Inc',
                'contact_name' => 'Jordan Ellis',
                'contact_email' => 'j.ellis@seahawk-dist.example',
                'contact_phone' => '+1-310-555-0177',
                'tax_number' => 'US84-3390217',
                'address_line_1' => '725 Harbor Plaza',
                'city' => 'Long Beach',
                'state' => 'CA',
                'postal_code' => '90802',
                'country_code' => 'US',
                'notes' => 'Final mile delivered by drayage partner.',
            ],
            [
                'type' => PartyType::Consignee,
                'code' => 'CONS-FRESH',
                'name' => 'Fresh Market Grocers BV',
                'contact_name' => 'Sanne de Vries',
                'contact_email' => 's.devries@freshmarket.example',
                'contact_phone' => '+31-20-555-0166',
                'tax_number' => 'NL-8842-1199-B01',
                'address_line_1' => '88 Waalhaven Oostzijde',
                'city' => 'Amsterdam',
                'postal_code' => '3087 BM',
                'country_code' => 'NL',
                'notes' => 'Requires delivery before 07:00 local.',
            ],
        ];

        foreach ($parties as $party) {
            // updateOrCreate keyed on the natural key the database actually
            // enforces: (type, code).
            Party::query()->updateOrCreate(
                ['type' => $party['type'], 'code' => $party['code']],
                $party,
            );
        }
    }

    private function carriers(): void
    {
        $carriers = [
            [
                'code' => 'MAEU',
                'name' => 'Maersk Line',
                'scac' => 'MAEU',
                'type' => CarrierType::Sea,
                'contact_name' => 'Lars Mikkelsen',
                'contact_email' => 'sg.agency@maersk.example',
                'contact_phone' => '+65-6555-0200',
                'address_line_1' => '1 Harbourfront Avenue',
                'city' => 'Singapore',
                'postal_code' => '098632',
                'country_code' => 'SG',
                'notes' => 'Primary ocean carrier for the Southeast Asia lanes.',
            ],
            [
                'code' => 'SWFT',
                'name' => 'Swee Heng Freight Services',
                'scac' => null,
                'type' => CarrierType::Road,
                'contact_name' => 'Wei Jian Lim',
                'contact_email' => 'dispatch@sweeheng.example',
                'contact_phone' => '+65-6555-0311',
                'address_line_1' => '55 Tuas South Avenue',
                'city' => 'Singapore',
                'postal_code' => '637333',
                'country_code' => 'SG',
                'notes' => 'Local drayage and last-mile. No SCAC assigned.',
            ],
        ];

        foreach ($carriers as $carrier) {
            Carrier::query()->updateOrCreate(['code' => $carrier['code']], $carrier);
        }
    }

    private function vessels(): void
    {
        $vessels = [
            [
                'name' => 'Maersk Sentosa',
                'imo_number' => '9781100',
                'mmsi' => '565820000',
                'flag_country' => 'SG',
                'vessel_type' => 'Container Ship',
                'length_meters' => 399,
                'beam_meters' => 59,
                'capacity_teu' => 14400,
                'notes' => 'On the SIN-LGB string, rotation 2.',
            ],
            [
                'name' => 'CMA CGM Kerguelen',
                'imo_number' => '9731123',
                'mmsi' => '636019000',
                'flag_country' => 'MH',
                'vessel_type' => 'Container Ship',
                'length_meters' => 398,
                'beam_meters' => 54,
                'capacity_teu' => 17200,
                'notes' => null,
            ],
        ];

        foreach ($vessels as $vessel) {
            Vessel::query()->updateOrCreate(['imo_number' => $vessel['imo_number']], $vessel);
        }
    }

    private function ports(): void
    {
        $ports = [
            [
                'un_locode' => 'SGSIN',
                'name' => 'Port of Singapore',
                'country_code' => 'SG',
                'port_type' => 'Container Terminal',
                'latitude' => 1.2644,
                'longitude' => 103.8200,
                'timezone' => 'Asia/Singapore',
                'notes' => 'Primary port of loading.',
            ],
            [
                'un_locode' => 'USLGB',
                'name' => 'Port of Long Beach',
                'country_code' => 'US',
                'port_type' => 'Container Terminal',
                'latitude' => 33.7540,
                'longitude' => -118.2160,
                'timezone' => 'America/Los_Angeles',
                'notes' => 'Main discharge port for the US west coast lane.',
            ],
            [
                // Added for the reefer request that has not been confirmed yet.
                // A European discharge port is the whole reason that booking is
                // worth chasing: the answer the carrier owes is an allocation on
                // this lane, and without the port the request would name no
                // destination at all.
                'un_locode' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'country_code' => 'NL',
                'port_type' => 'Container Terminal',
                'latitude' => 51.9500,
                'longitude' => 4.1400,
                'timezone' => 'Europe/Amsterdam',
                'notes' => 'Discharge port for the chilled reefer request.',
            ],
            [
                // The transshipment port, and it exists for the customs module's
                // benefit rather than the routing table's: SHP-2026-0001 is reported
                // at Algeciras waiting for a second leg, and a consignment that
                // clears at two ports has to have a record of both. Without this port
                // that filing would have nowhere to point, and the second clearance
                // would be the one thing the demo set could not show.
                'un_locode' => 'ESALG',
                'name' => 'Port of Algeciras',
                'country_code' => 'ES',
                'port_type' => 'Container Terminal',
                'latitude' => 36.1300,
                'longitude' => -5.4400,
                'timezone' => 'Europe/Madrid',
                'notes' => 'Transshipment hub on the SHP-2026-0001 routing.',
            ],
        ];

        foreach ($ports as $port) {
            Port::query()->updateOrCreate(['un_locode' => $port['un_locode']], $port);
        }
    }

    private function sites(): void
    {
        $sites = [
            // Warehouses carry the two storage-only fields; factories do not, and
            // the API rejects them, so the demo data has to respect that too.
            [
                'kind' => SiteKind::Warehouse,
                'code' => 'WH-SIN-01',
                'name' => 'Jurong West Distribution Centre',
                'contact_name' => 'Ng Boon Keng',
                'contact_email' => 'jurong@warehouse.example',
                'contact_phone' => '+65-6555-0401',
                'address_line_1' => '25 Jurong West Street 51',
                'city' => 'Singapore',
                'postal_code' => '648555',
                'country_code' => 'SG',
                'site_type' => 'general',
                'capacity_pallets' => 4200,
                'notes' => 'Free-trade-zone warehouse. Bonded area to the rear.',
            ],
            [
                'kind' => SiteKind::Warehouse,
                'code' => 'WH-SIN-02',
                'name' => 'Changi Cold Store',
                'contact_name' => 'Ramesh Menon',
                'contact_email' => 'coldstore@warehouse.example',
                'contact_phone' => '+65-6555-0402',
                'address_line_1' => '8 Airport Boulevard',
                'city' => 'Singapore',
                'postal_code' => '437819',
                'country_code' => 'SG',
                'site_type' => 'cold_store',
                'capacity_pallets' => 900,
                'notes' => 'Holds -18C. Generator backed.',
            ],
            [
                'kind' => SiteKind::Factory,
                'code' => 'FAC-SIN-01',
                'name' => 'Jurong Assembly Plant',
                'contact_name' => 'Chandra Prasad',
                'contact_email' => 'plant@assembly.example',
                'contact_phone' => '+65-6555-0501',
                'address_line_1' => '3 Tuas South Circus',
                'city' => 'Singapore',
                'postal_code' => '637350',
                'country_code' => 'SG',
                'site_type' => null,
                'capacity_pallets' => null,
                'notes' => 'Electronics assembly. Goods-in via Gate 2.',
            ],
        ];

        foreach ($sites as $site) {
            Site::query()->updateOrCreate(
                ['kind' => $site['kind'], 'code' => $site['code']],
                $site,
            );
        }
    }

    /**
     * Trucks and drivers, tied to the road carrier above.
     *
     * The link matters: a driver whose truck belongs to a different carrier is a
     * state the operations screens should never have to survive, so the seeder
     * resolves the carrier once and points both records at it.
     */
    private function fleet(): void
    {
        $carrier = Carrier::query()->where('code', 'SWFT')->firstOrFail();

        $trucks = [
            [
                'plate_number' => 'SH1234A',
                'code' => 'TRK-0001',
                'truck_type' => 'tractor',
                'capacity_kg' => 24000,
                'capacity_pallets' => 26,
                'make' => 'Volvo',
                'model' => 'FH16',
                'year' => '2021',
                'notes' => 'Reefer trailer available on request.',
            ],
            [
                'plate_number' => 'SH5678B',
                'code' => 'TRK-0002',
                'truck_type' => 'box_truck',
                'capacity_kg' => 12000,
                'capacity_pallets' => 14,
                'make' => 'Isuzu',
                'model' => 'FTR 850',
                'year' => '2019',
                'notes' => null,
            ],
        ];

        $createdTrucks = [];

        foreach ($trucks as $truck) {
            $createdTrucks[] = Truck::query()->updateOrCreate(
                ['plate_number' => $truck['plate_number']],
                $truck + ['carrier_id' => $carrier->getKey()],
            );
        }

        $drivers = [
            [
                'code' => 'DRV-0001',
                'name' => 'Lim Wei Ming',
                'license_number' => 'CDL-88230145',
                'phone' => '+65-8123-4567',
                'email' => 'weiming.lim@sweeheng.example',
                'nationality' => 'SG',
                'truck' => 'SH1234A',
                'license_expiry' => now()->addYears(3)->toDateString(),
                'medical_expiry' => now()->addYears(2)->toDateString(),
                'notes' => null,
            ],
            [
                'code' => 'DRV-0002',
                'name' => 'Siti Nurhaliza',
                'license_number' => 'CDL-77190288',
                'phone' => '+65-8234-5678',
                'email' => 'siti.nur@sweeheng.example',
                'nationality' => 'MY',
                'truck' => 'SH5678B',
                // Deliberately inside a 30 day window so the compliance screen
                // has something real to show on a fresh install.
                'license_expiry' => now()->addDays(21)->toDateString(),
                'medical_expiry' => now()->addMonths(14)->toDateString(),
                'notes' => 'Licence renewal booked. Chase the depot before it lapses.',
            ],
        ];

        foreach ($drivers as $driver) {
            $plate = $driver['truck'];
            unset($driver['truck']);

            Driver::query()->updateOrCreate(
                ['code' => $driver['code']],
                $driver + [
                    'carrier_id' => $carrier->getKey(),
                    'truck_id' => collect($createdTrucks)
                        ->firstWhere('plate_number', $plate)?->getKey(),
                ],
            );
        }
    }

    /**
     * Requests for space, and what the carriers said.
     *
     * Bookings are seeded before shipments because a consignment points at the
     * booking it came from, and two of the four shipments below are raised from a
     * booking here. The set is chosen to exercise the states a fresh install
     * would otherwise never show, and - more importantly - the state that has no
     * shipment attached at all:
     *
     * - BKG-...-0001 confirmed, and a shipment was raised from it
     * - BKG-...-0002 confirmed, and a shipment was raised from it
     * - BKG-...-0003 requested and unanswered: somebody's follow-up, with no
     *   consignment, which is the case the module exists for
     * - BKG-...-0004 draft, unassigned and undated: the record somebody started
     *   and abandoned
     * - BKG-...-0005 cancelled, so `is_closed` and the closed states are not all
     *   theoretical
     *
     * The cargo and routing on the first two deliberately match the shipments
     * raised from them, because that is what a booking confirmation looks like
     * once the consignment has been cut from it.
     */
    /**
     * Resolve a seeded lookup by its natural key, or null when there is no key.
     *
     * Omitting the key is a genuine "none" - a draft booking has no carrier, most
     * consignments came from no booking - and that has to stay null. A key that
     * was named and does not resolve is a typo, and it must not be allowed to
     * pass: `value('id')` would hand back null and turn "sent to the carrier" into
     * an unassigned draft, a "chosen port" into no port, and a "raised from
     * booking" consignment into one that looks like it came from nowhere. The
     * row would contradict its own comment and the unassigned and pending
     * filters would count records the data never meant to include.
     */
    private function lookupId(string $table, string $column, ?string $key, string $label): ?int
    {
        if ($key === null) {
            return null;
        }

        $id = DB::table($table)->where($column, $key)->value('id');

        if ($id === null) {
            throw new RuntimeException("Demo {$label} {$key} does not exist.");
        }

        return $id;
    }

    private function bookings(): void
    {
        $party = fn (PartyType $type, string $code): ?int => Party::query()
            ->where('type', $type)
            ->where('code', $code)
            ->value('id');

        $port = fn (?string $code): ?int => $this->lookupId('ports', 'un_locode', $code, 'port');

        $vessel = fn (?string $name): ?int => $this->lookupId('vessels', 'name', $name, 'vessel');

        $site = fn (?string $code): ?int => $this->lookupId('sites', 'code', $code, 'site');

        $carrierId = fn (?string $code): ?int => $this->lookupId('carriers', 'code', $code, 'carrier');

        $bookings = [
            [
                'reference' => 'BKG-2026-0001',
                'status' => BookingStatus::Confirmed,
                'shipper' => [PartyType::Shipper, 'SHPR-TEXTILE'],
                'consignee' => [PartyType::Consignee, 'CONS-SEAHAWK'],
                'carrier' => 'MAEU',
                'vessel' => 'Maersk Sentosa',
                'loading' => 'SGSIN',
                'discharge' => 'USLGB',
                'origin' => 'FAC-SIN-01',
                'destination' => 'WH-SIN-02',
                'incoterm' => Incoterm::Fob,
                'incoterm_place' => 'Singapore',
                'cargo_description' => 'Cotton garments, 40ft high cube',
                'pieces' => 640,
                'weight_kg' => 18400,
                'volume_cbm' => 58.000,
                'requested_etd' => now()->subDays(14)->toDateString(),
                'requested_eta' => now()->addDays(17)->toDateString(),
                'notes' => 'Space confirmed by the carrier, booking number issued.',
            ],
            [
                'reference' => 'BKG-2026-0002',
                'status' => BookingStatus::Confirmed,
                'shipper' => [PartyType::Shipper, 'SHPR-TEXTILE'],
                'consignee' => [PartyType::Consignee, 'CONS-SEAHAWK'],
                'carrier' => 'SWFT',
                'loading' => 'SGSIN',
                'discharge' => 'USLGB',
                'origin' => 'WH-SIN-01',
                'destination' => 'WH-SIN-01',
                'incoterm' => Incoterm::Cif,
                'incoterm_place' => 'Long Beach',
                'cargo_description' => 'Finished garments, 2 x 20ft',
                'pieces' => 210,
                'weight_kg' => 8400,
                'volume_cbm' => 22.400,
                'requested_etd' => now()->subDays(60)->toDateString(),
                'requested_eta' => now()->subDays(47)->toDateString(),
                'notes' => 'Closed booking, consignment delivered.',
            ],
            [
                // Sent to the carrier and still unanswered. The reason the module
                // exists: there is a real request on file and no consignment yet.
                'reference' => 'BKG-2026-0003',
                'status' => BookingStatus::Requested,
                'shipper' => [PartyType::Shipper, 'SHPR-HARVEST'],
                'consignee' => [PartyType::Consignee, 'CONS-FRESH'],
                'carrier' => 'SWFT',
                'loading' => 'SGSIN',
                'discharge' => 'NLRTM',
                'origin' => 'WH-SIN-01',
                'destination' => 'WH-SIN-01',
                'incoterm' => Incoterm::Fob,
                'incoterm_place' => 'Singapore',
                'cargo_description' => 'Chilled durian, reefer',
                'pieces' => 180,
                'weight_kg' => 9600,
                'volume_cbm' => 26.000,
                'requested_etd' => now()->addDays(9)->toDateString(),
                'requested_eta' => now()->addDays(38)->toDateString(),
                'notes' => 'Awaiting confirmation of the reefer allocation.',
            ],
            [
                // Never sent anywhere: no carrier, no dates.
                'reference' => 'BKG-2026-0004',
                'status' => BookingStatus::Draft,
                'shipper' => [PartyType::Shipper, 'SHPR-HARVEST'],
                'consignee' => [PartyType::Consignee, 'CONS-FRESH'],
                'incoterm' => null,
                'incoterm_place' => null,
                'cargo_description' => 'Palm oil, bulk',
                'pieces' => null,
                'weight_kg' => null,
                'volume_cbm' => null,
                'requested_etd' => null,
                'requested_eta' => null,
                'notes' => 'Started from the customer instruction, not yet sent.',
            ],
            [
                'reference' => 'BKG-2026-0005',
                'status' => BookingStatus::Cancelled,
                'shipper' => [PartyType::Shipper, 'SHPR-TEXTILE'],
                'consignee' => [PartyType::Consignee, 'CONS-SEAHAWK'],
                'carrier' => 'MAEU',
                'incoterm' => Incoterm::Fob,
                'incoterm_place' => 'Singapore',
                'cargo_description' => 'Textiles, air freight',
                'pieces' => 24,
                'weight_kg' => 480,
                'volume_cbm' => 2.400,
                'requested_etd' => now()->subDays(20)->toDateString(),
                'requested_eta' => now()->addDays(2)->toDateString(),
                'notes' => 'Withdrawn by the shipper before the carrier replied.',
            ],
        ];

        foreach ($bookings as $booking) {
            [$shipperType, $shipperCode] = $booking['shipper'];
            [$consigneeType, $consigneeCode] = $booking['consignee'];

            Booking::query()->updateOrCreate(
                ['reference' => $booking['reference']],
                [
                    'status' => $booking['status'],
                    'shipper_id' => $party($shipperType, $shipperCode),
                    'consignee_id' => $party($consigneeType, $consigneeCode),
                    'carrier_id' => $carrierId($booking['carrier'] ?? null),
                    'vessel_id' => $vessel($booking['vessel'] ?? null),
                    'port_of_loading_id' => $port($booking['loading'] ?? null),
                    'port_of_discharge_id' => $port($booking['discharge'] ?? null),
                    'origin_site_id' => $site($booking['origin'] ?? null),
                    'destination_site_id' => $site($booking['destination'] ?? null),
                    'incoterm' => $booking['incoterm'],
                    'incoterm_place' => $booking['incoterm_place'],
                    'cargo_description' => $booking['cargo_description'],
                    'pieces' => $booking['pieces'],
                    'weight_kg' => $booking['weight_kg'],
                    'volume_cbm' => $booking['volume_cbm'],
                    'requested_etd' => $booking['requested_etd'],
                    'requested_eta' => $booking['requested_eta'],
                    'notes' => $booking['notes'],
                ],
            );
        }
    }

    /**
     * Consignments in flight, and one that has gone wrong.
     *
     * The point of these is the shipment screen, so they are chosen to exercise
     * the states a fresh install would otherwise never show: an overdue
     * consignment, an unassigned draft somebody started and abandoned, one that
     * is closed, and one on the water with a full set of references. Every
     * reference is looked up by the same natural key the other seed methods
     * write with, so re-running updates in place instead of colliding.
     *
     * Two are raised from a booking seeded above. The rest are not, which is the
     * point: a consignment entered from a paper bill of lading has no booking
     * behind it, so leaving `booking` off those rows is what makes the nullable
     * column honest in the demo data rather than every row pointing somewhere.
     */
    private function shipments(): void
    {
        $party = fn (PartyType $type, string $code): ?int => Party::query()
            ->where('type', $type)
            ->where('code', $code)
            ->value('id');

        // Every lookup tolerates a null, because a draft has no ports, no vessel
        // and possibly no carrier. Forcing a value for each would mean inventing
        // a leg a consignment does not have.
        $port = fn (?string $code): ?int => $this->lookupId('ports', 'un_locode', $code, 'port');

        $vessel = fn (?string $name): ?int => $this->lookupId('vessels', 'name', $name, 'vessel');

        $site = fn (?string $code): ?int => $this->lookupId('sites', 'code', $code, 'site');

        $carrierId = fn (?string $code): ?int => $this->lookupId('carriers', 'code', $code, 'carrier');

        // Not every consignment came from a booking, so this one is null for two
        // of the four. Naming one that does not exist is still a typo, and see
        // lookupId() for why that is not allowed to pass silently.
        $bookingId = fn (?string $reference): ?int => $this->lookupId('bookings', 'reference', $reference, 'booking');

        $shipments = [
            [
                'reference' => 'SHP-2026-0001',
                'status' => ShipmentStatus::InTransit,
                'booking' => 'BKG-2026-0001',
                'shipper' => [PartyType::Shipper, 'SHPR-TEXTILE'],
                'consignee' => [PartyType::Consignee, 'CONS-SEAHAWK'],
                'carrier' => 'MAEU',
                'vessel' => 'Maersk Sentosa',
                'loading' => 'SGSIN',
                'discharge' => 'USLGB',
                'origin' => 'FAC-SIN-01',
                'destination' => 'WH-SIN-02',
                'incoterm' => Incoterm::Fob,
                'incoterm_place' => 'Singapore',
                'cargo_description' => 'Cotton garments, 40ft high cube',
                'pieces' => 640,
                'weight_kg' => 18400,
                'volume_cbm' => 58.000,
                'etd' => now()->subDays(12)->toDateString(),
                'eta' => now()->addDays(19)->toDateString(),
                'actual_departure' => now()->subDays(11)->setTime(6, 40),
                'notes' => 'Certificate of origin issued to the forwarder.',
                'tracking' => [
                    'last_milestone' => TrackingMilestone::Transshipment,
                    'current_location' => 'Algeciras, ES',
                    'last_reported_at' => now()->subDays(2)->setTime(9, 15),
                    // On schedule: still landing on the original ETA, and nothing
                    // has come in to suggest otherwise.
                    'revised_eta' => null,
                    'delay_reason' => null,
                    'source' => 'Maersk Connect',
                    'notes' => 'Discharged to feeder, awaiting second leg.',
                ],
            ],
            [
                'reference' => 'SHP-2026-0002',
                'status' => ShipmentStatus::Customs,
                'shipper' => [PartyType::Shipper, 'SHPR-HARVEST'],
                'consignee' => [PartyType::Consignee, 'CONS-FRESH'],
                'carrier' => 'SWFT',
                'vessel' => 'CMA CGM Kerguelen',
                'loading' => 'SGSIN',
                'discharge' => 'USLGB',
                'origin' => 'WH-SIN-01',
                'destination' => 'WH-SIN-01',
                'incoterm' => Incoterm::Cif,
                'incoterm_place' => 'Long Beach',
                'cargo_description' => 'Chilled durian, reefer',
                'pieces' => 320,
                'weight_kg' => 19200,
                'volume_cbm' => 44.800,
                'etd' => now()->subDays(31)->toDateString(),
                'eta' => now()->subDays(2)->toDateString(),
                'actual_departure' => now()->subDays(30)->setTime(14, 15),
                // Held: the phytosanitary certificate is still with the broker,
                // which is the whole reason a consignment sits in customs.
                'notes' => 'Held for phytosanitary certificate. Broker chasing.',
                'tracking' => [
                    'last_milestone' => TrackingMilestone::CustomsHold,
                    'current_location' => 'Long Beach, US',
                    // Nine days of silence, so this is the stale case as well as
                    // the problem one - the two worklists are not the same set.
                    'last_reported_at' => now()->subDays(9)->setTime(16, 40),
                    'revised_eta' => now()->addDays(4)->toDateString(),
                    'delay_reason' => 'Awaiting phytosanitary certificate from broker',
                    'source' => 'CMA CGM eBusiness',
                    'notes' => 'Reefer plugged in at terminal, monitoring continues.',
                ],
            ],
            [
                'reference' => 'SHP-2026-0003',
                'status' => ShipmentStatus::Delivered,
                'booking' => 'BKG-2026-0002',
                'shipper' => [PartyType::Shipper, 'SHPR-TEXTILE'],
                'consignee' => [PartyType::Consignee, 'CONS-SEAHAWK'],
                'carrier' => 'SWFT',
                'loading' => 'SGSIN',
                'discharge' => 'USLGB',
                'origin' => 'WH-SIN-01',
                'destination' => 'WH-SIN-01',
                'incoterm' => Incoterm::Cif,
                'incoterm_place' => 'Long Beach',
                'cargo_description' => 'Finished garments, 2 x 20ft',
                'pieces' => 210,
                'weight_kg' => 8400,
                'volume_cbm' => 22.400,
                'etd' => now()->subDays(58)->toDateString(),
                'eta' => now()->subDays(45)->toDateString(),
                'actual_departure' => now()->subDays(57)->setTime(3, 5),
                'actual_arrival' => now()->subDays(44)->setTime(9, 20),
                'notes' => 'Signed on delivery, POD filed.',
                'tracking' => [
                    'last_milestone' => TrackingMilestone::Delivered,
                    'current_location' => 'Long Beach, US',
                    'last_reported_at' => now()->subDays(44)->setTime(9, 20),
                    'revised_eta' => null,
                    'delay_reason' => null,
                    'source' => 'Swift Track',
                    'notes' => 'Delivered to consignee, POD on file.',
                ],
            ],
            [
                // No carrier and no dates: the record somebody started and
                // abandoned. A fresh install needs one, or the "unassigned"
                // filter has nothing in it to find.
                'reference' => 'SHP-2026-0004',
                'status' => ShipmentStatus::Draft,
                'shipper' => [PartyType::Shipper, 'SHPR-HARVEST'],
                'consignee' => [PartyType::Consignee, 'CONS-FRESH'],
                'incoterm' => null,
                'incoterm_place' => null,
                'cargo_description' => 'Palm oil, bulk',
                'pieces' => null,
                'weight_kg' => null,
                'volume_cbm' => null,
                'etd' => null,
                'eta' => null,
                'notes' => 'Awaiting vessel rate from the carrier.',
                // Nothing at all, which is the point: a draft has no carrier to
                // report from, so the unreported branch of the stale filter needs
                // this one to be non-empty.
            ],
        ];

        foreach ($shipments as $shipment) {
            $loading = $shipment['loading'] ?? null;
            $discharge = $shipment['discharge'] ?? null;
            $vesselName = $shipment['vessel'] ?? null;
            $origin = $shipment['origin'] ?? null;
            $destination = $shipment['destination'] ?? null;
            $carrier = $shipment['carrier'] ?? null;
            [$shipperType, $shipperCode] = $shipment['shipper'];
            [$consigneeType, $consigneeCode] = $shipment['consignee'];

            $consignment = Shipment::query()->updateOrCreate(
                ['reference' => $shipment['reference']],
                [
                    'status' => $shipment['status'],
                    'booking_id' => $bookingId($shipment['booking'] ?? null),
                    'shipper_id' => $party($shipperType, $shipperCode),
                    'consignee_id' => $party($consigneeType, $consigneeCode),
                    'carrier_id' => $carrierId($carrier),
                    'vessel_id' => $vessel($vesselName),
                    'port_of_loading_id' => $port($loading),
                    'port_of_discharge_id' => $port($discharge),
                    'origin_site_id' => $site($origin),
                    'destination_site_id' => $site($destination),
                    'truck_id' => null,
                    'driver_id' => null,
                    'incoterm' => $shipment['incoterm'],
                    'incoterm_place' => $shipment['incoterm_place'],
                    'cargo_description' => $shipment['cargo_description'],
                    'pieces' => $shipment['pieces'],
                    'weight_kg' => $shipment['weight_kg'],
                    'volume_cbm' => $shipment['volume_cbm'],
                    'etd' => $shipment['etd'],
                    'eta' => $shipment['eta'],
                    'actual_departure' => $shipment['actual_departure'] ?? null,
                    'actual_arrival' => $shipment['actual_arrival'] ?? null,
                    'notes' => $shipment['notes'],
                ],
            );

            /*
             * The tracking report that goes with each consignment above.
             *
             * The row already exists - Shipment::booted() made it with the
             * consignment - so this fills it in rather than creating one, which is
             * the same arrangement the module's own write path enforces.
             *
             * The four are chosen to cover the three worklists and the honest
             * empty state:
             *
             * - 0001 reported two days ago, on schedule: the ordinary case
             * - 0002 held at customs with a revised date: a problem *and* a delay,
             *   so it appears on both lists and the two are not the same set
             * - 0003 delivered: finished, so it is on neither - the worklists
             *   exclude closed consignments, and the demo set has to show that
             * - 0004 never reported on at all: a draft nobody has heard anything
             *   about, which is what the stale filter is for
             */
            $consignment->tracking->update($shipment['tracking'] ?? []);
        }
    }

    /**
     * The equipment each consignment travels in.
     *
     * Seeded after shipments and keyed on (consignment, container number) - the
     * natural key the schema actually enforces. Not the number alone, and the
     * difference is the module's whole design: the same box goes round again on a
     * later consignment, so keying the seeder on the number would silently make the
     * demo set impossible and the reuse case untestable.
     *
     * The four consignments cover the states the module would otherwise never show:
     *
     * - 0001 on the water with a full 40ft and a 20ft: the ordinary manifest, and
     *   the case where the two boxes' weights have to add up to something sensible
     * - 0002 held at customs: the worklist's flagship row, with the hold recorded
     *   against the box rather than only against the consignment
     * - 0003 delivered: finished, so it must not appear on the needs-attention list
     *   even though its last state before that was a customs hold
     * - 0004 a draft with no equipment at all. Left out deliberately, and it is what
     *   makes the nullable-and-legitimately-empty case real rather than theoretical
     */
    private function containers(): void
    {
        $containers = [
            [
                'shipment' => 'SHP-2026-0001',
                'container_number' => 'MSCU7741203',
                'iso_code' => '45G1',
                'type' => '40ft high cube, dry',
                'status' => ContainerStatus::InTransit,
                'seal_number' => 'SL771204',
                'tare_weight_kg' => 3900,
                'max_gross_weight_kg' => 30480,
                'gross_weight_kg' => 12680,
                'notes' => 'Main box. Garment rails loaded to the lashing deck.',
            ],
            [
                'shipment' => 'SHP-2026-0001',
                'container_number' => 'TGHU3350817',
                'iso_code' => '22G1',
                'type' => '20ft dry',
                'status' => ContainerStatus::InTransit,
                'seal_number' => 'SL335081',
                'tare_weight_kg' => 2250,
                'max_gross_weight_kg' => 30480,
                // Deliberately heavier per the rating than the main box, because a
                // half-empty 20ft in a full 40ft is what a real manifest looks like
                // and it is the case where `utilisation_percent` is worth reading.
                'gross_weight_kg' => 4620,
                'notes' => 'Accessory cartons for the same consignment.',
            ],
            [
                // The box is held, and the reason is on the box. An operator chasing
                // a consignment at customs is chasing a specific piece of steel.
                'shipment' => 'SHP-2026-0002',
                'container_number' => 'CMAU1180233',
                'iso_code' => '42R1',
                'type' => '40ft reefer, high cube',
                'status' => ContainerStatus::CustomsHold,
                'seal_number' => 'SL118023',
                'tare_weight_kg' => 4600,
                'max_gross_weight_kg' => 30480,
                'gross_weight_kg' => 19200,
                'notes' => 'Held pending phytosanitary certificate. Broker chasing.',
            ],
            [
                'shipment' => 'SHP-2026-0003',
                'container_number' => 'HLXU9920415',
                'iso_code' => '22G1',
                'type' => '20ft dry',
                'status' => ContainerStatus::Delivered,
                'seal_number' => 'SL992041',
                'tare_weight_kg' => 2250,
                'max_gross_weight_kg' => 30480,
                'gross_weight_kg' => 8400,
                'notes' => 'Cut seals on arrival, empty returned to depot.',
            ],
        ];

        foreach ($containers as $container) {
            ShipmentContainer::query()->updateOrCreate(
                [
                    'shipment_id' => $this->shipmentId($container['shipment']),
                    'container_number' => $container['container_number'],
                ],
                Arr::except($container, ['shipment']),
            );
        }
    }

    /**
     * Where each consignment stands with the authority, per port.
     *
     * Keyed on (consignment, port), the pair the schema enforces, for the reason the
     * migration gives: a transshipment clears more than once, and a refusal has to
     * stay on the record rather than be overwritten by the attempt that followed it.
     *
     * The set is chosen to make the module's two worklists both non-empty and
     * different, which they never are by accident:
     *
     * - 0001 cleared at origin and is at Algeciras now: two rows for one consignment,
     *   the case a single row per consignment could not represent. The Algeciras
     *   filing was *refused* for want of the onward details, so it is the one row
     *   anybody has to chase, and it is still live - the consignment is at sea waiting
     *   on the answer. A resolved rejection would have been a poor choice here: the
     *   worklist is where somebody goes to do something, and a refusal nobody needs
     *   to act on is just noise on it
     * - 0002 has nothing on file at Long Beach - not even an entry number, because the
     *   certificate that would let it be filed has not arrived. That is the held row,
     *   and it is stuck rather than wrong, which is the distinction the two worklists
     *   are built on
     * - 0003 completed at Long Beach, so the demo set has a finished filing and the
     *   "nothing further expected" state is not theoretical
     *
     * 0004 is absent on purpose, like its containers: a draft has not been filed
     * anywhere.
     */
    private function customsClearances(): void
    {
        $port = fn (string $code): int => $this->lookupId('ports', 'un_locode', $code, 'port');

        $clearances = [
            [
                'shipment' => 'SHP-2026-0001',
                'port' => 'SGSIN',
                'status' => CustomsStatus::Completed,
                'entry_number' => 'SG-4471/2026',
                'broker' => 'Apex Customs Services',
                'submitted_at' => now()->subDays(20)->setTime(9, 30),
                'cleared_at' => now()->subDays(17)->setTime(15, 10),
                'hold_reason' => null,
                'notes' => 'Export cleared. Duty not applicable on FOB re-export.',
            ],
            [
                'shipment' => 'SHP-2026-0001',
                'port' => 'ESALG',
                'status' => CustomsStatus::Rejected,
                'entry_number' => 'ES-2210/2026',
                'broker' => 'Apex Customs Services',
                'submitted_at' => now()->subDays(3)->setTime(11, 5),
                'cleared_at' => null,
                'hold_reason' => 'Re-export declaration refused: onward feeder booking not confirmed',
                'notes' => 'Refused at examination. Re-filing with the broker once the second leg is booked, which is what the consignment is waiting on.',
            ],
            [
                // No entry number, because nothing has been filed. The certificate
                // the authority wants is still with the broker, which is exactly what
                // the consignment's tracking note says.
                'shipment' => 'SHP-2026-0002',
                'port' => 'USLGB',
                'status' => CustomsStatus::Pending,
                'entry_number' => null,
                'broker' => 'Meridian Freight Services',
                'submitted_at' => null,
                'cleared_at' => null,
                'hold_reason' => 'Phytosanitary certificate not presented',
                'notes' => 'Cannot be filed until the phyto certificate arrives from the broker.',
            ],
            [
                'shipment' => 'SHP-2026-0003',
                'port' => 'USLGB',
                'status' => CustomsStatus::Completed,
                'entry_number' => 'US-9902/2026',
                'broker' => 'Meridian Freight Services',
                'submitted_at' => now()->subDays(52)->setTime(8, 45),
                'cleared_at' => now()->subDays(50)->setTime(17, 25),
                'hold_reason' => null,
                'notes' => 'Import cleared, duty settled by the importer.',
            ],
        ];

        foreach ($clearances as $clearance) {
            ShipmentCustomsClearance::query()->updateOrCreate(
                [
                    'shipment_id' => $this->shipmentId($clearance['shipment']),
                    'port_id' => $port($clearance['port']),
                ],
                Arr::except($clearance, ['shipment', 'port']),
            );
        }
    }

    /**
     * The last leg, and the one module where a consignment has several rows.
     *
     * Keyed on `reference` alone, because unlike a container number or a customs
     * filing this number is ours and unique - the difference the three migrations
     * between them come down to.
     *
     * The three consignments cover the states worth seeing on a fresh install:
     *
     * - 0001 has none at all. It is at sea and two ports away from its destination,
     *   so booking a collection would be booking a guess. This is the empty case that
     *   a module with a nullable shipment-side relation has to tolerate
     * - 0002 is booked for a slot that has passed while the consignment sits at
     *   customs, which is the overdue row the alarm list exists to show - and the
     *   reason it is late is one line above it on the consignment
     * - 0003 was delivered, and also had a failed attempt first. Two rows for one
     *   consignment, in order, which is the case a single-row design would have had
     *   to overwrite
     */
    private function deliveries(): void
    {
        $truckId = fn (?string $code): ?int => $this->lookupId('trucks', 'code', $code, 'truck');

        $driverId = fn (?string $code): ?int => $this->lookupId('drivers', 'code', $code, 'driver');

        $deliveries = [
            [
                'reference' => 'DLV-2026-0001',
                'shipment' => 'SHP-2026-0002',
                'status' => DeliveryStatus::Scheduled,
                'truck' => 'TRK-0001',
                'driver' => 'DRV-0001',
                'scheduled_at' => now()->subDays(2)->setTime(9, 0),
                'picked_up_at' => null,
                'delivered_at' => null,
                'contact_name' => 'Jordan Ellis',
                'contact_phone' => '+1-310-555-0177',
                'delivery_address' => '725 Harbor Plaza, Long Beach, CA 90802 - bay 14, ask for Jordan',
                'pod_reference' => null,
                'failure_reason' => null,
                // The slot has passed because the consignment is still at customs,
                // so this row is overdue for a reason visible on the consignment
                // above it rather than because anybody forgot.
                'notes' => 'Booked on the assumption the phyto clears. Hold at customs; rebook once released.',
            ],
            [
                'reference' => 'DLV-2026-0002',
                'shipment' => 'SHP-2026-0003',
                'status' => DeliveryStatus::Failed,
                'truck' => 'TRK-0002',
                'driver' => 'DRV-0002',
                'scheduled_at' => now()->subDays(45)->setTime(13, 30),
                'picked_up_at' => now()->subDays(45)->setTime(14, 10),
                'delivered_at' => null,
                'contact_name' => 'Jordan Ellis',
                'contact_phone' => '+1-310-555-0177',
                'delivery_address' => '725 Harbor Plaza, Long Beach, CA 90802 - bay 14',
                'pod_reference' => null,
                'failure_reason' => 'Site gate closed on the first attempt, no access',
                'notes' => 'Second attempt booked for the following morning.',
            ],
            [
                'reference' => 'DLV-2026-0003',
                'shipment' => 'SHP-2026-0003',
                'status' => DeliveryStatus::Delivered,
                'truck' => 'TRK-0002',
                'driver' => 'DRV-0002',
                'scheduled_at' => now()->subDays(44)->setTime(8, 0),
                'picked_up_at' => now()->subDays(44)->setTime(9, 5),
                'delivered_at' => now()->subDays(44)->setTime(16, 45),
                'contact_name' => 'Jordan Ellis',
                'contact_phone' => '+1-310-555-0177',
                'delivery_address' => '725 Harbor Plaza, Long Beach, CA 90802 - bay 14',
                'pod_reference' => 'POD-2026-004412',
                'failure_reason' => null,
                'notes' => 'Signed by J. Ellis. Second attempt, one day after the first.',
            ],
        ];

        foreach ($deliveries as $delivery) {
            ShipmentDelivery::query()->updateOrCreate(
                ['reference' => $delivery['reference']],
                [
                    'shipment_id' => $this->shipmentId($delivery['shipment']),
                    'status' => $delivery['status'],
                    'truck_id' => $truckId($delivery['truck'] ?? null),
                    'driver_id' => $driverId($delivery['driver'] ?? null),
                    'scheduled_at' => $delivery['scheduled_at'],
                    'picked_up_at' => $delivery['picked_up_at'],
                    'delivered_at' => $delivery['delivered_at'],
                    'contact_name' => $delivery['contact_name'],
                    'contact_phone' => $delivery['contact_phone'],
                    'delivery_address' => $delivery['delivery_address'],
                    'pod_reference' => $delivery['pod_reference'],
                    'failure_reason' => $delivery['failure_reason'],
                    'notes' => $delivery['notes'],
                ],
            );
        }
    }

    /**
     * The consignment a piece of operational data hangs off.
     *
     * A named convenience rather than a closure per method: the shipments table is
     * keyed on `reference`, which is the only handle the literal rows above have, and
     * every module seeded after shipments needs it. The same reasoning as
     * `lookupId()` applies - a reference that does not resolve is a typo in the demo
     * data and must not be allowed to become a null foreign key.
     */
    private function shipmentId(string $reference): int
    {
        $id = $this->lookupId('shipments', 'reference', $reference, 'shipment');

        if ($id === null) {
            throw new RuntimeException("Demo shipment {$reference} does not exist.");
        }

        return $id;
    }

    /**
     * The invoice a piece of finance data hangs off.
     *
     * `updateOrCreate` on the reference means the finance methods can be re-run in
     * any order relative to each other, which is what makes the whole seeder
     * repeatable: the payments do not have to know whether the invoices they pay
     * were created in this same run.
     */
    private function invoiceId(string $reference): int
    {
        $id = $this->lookupId('invoices', 'reference', $reference, 'invoice');

        if ($id === null) {
            throw new RuntimeException("Demo invoice {$reference} does not exist.");
        }

        return $id;
    }

    /**
     * The account a demo invoice is billed to.
     *
     * Keyed on (type, code) because that is the pair the parties table actually
     * enforces, and because an invoice's customer is an *account* rather than the
     * consignee: a forwarder invoices the company it contracts with, and the goods
     * often go to somebody else entirely. Two of the four demo consignments belong
     * to Seahawk Distribution, whose account is Acme; the other two ship on
     * Northwind's account.
     */
    private function customerId(string $code): int
    {
        $id = Party::query()
            ->where('type', PartyType::Customer)
            ->where('code', $code)
            ->value('id');

        if ($id === null) {
            throw new RuntimeException("Demo customer {$code} does not exist.");
        }

        return (int) $id;
    }

    /**
     * What is charged to a customer.
     *
     * Billable lines on consignments, in the states the worklist screens have to be
     * able to show something in. Three of the four charges for each shipment are on
     * an invoice, and the fourth is deliberately not - an unbilled charge is the
     * state a forwarder spends the end of a month clearing, and a demo with none
     * in it has an empty "awaiting billing" filter on a fresh install.
     *
     * The subtotals are arithmetic a person can check by eye, because a demo whose
     * invoice total cannot be reconciled by hand teaches nobody anything. Freight
     * plus origin handling plus documentation plus the fuel surcharge, and one
     * insured line that carries a tax rate so `tax_amount` is not always zero.
     */
    private function charges(): void
    {
        $charges = [
            // SHP-2026-0001: in transit, invoiced and part-paid.
            [
                'shipment' => 'SHP-2026-0001',
                'invoice' => 'INV-2026-0001',
                'type' => ChargeType::Freight,
                'description' => 'Ocean freight, Singapore to Long Beach',
                'quantity' => '1',
                'unit_price' => '2450.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0001',
                'invoice' => 'INV-2026-0001',
                'type' => ChargeType::Handling,
                'description' => 'Terminal handling at origin',
                'quantity' => '1',
                'unit_price' => '385.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0001',
                'invoice' => 'INV-2026-0001',
                'type' => ChargeType::Documentation,
                'description' => 'Documentation and certificate of origin',
                'quantity' => '1',
                'unit_price' => '95.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0001',
                // Raised at the transshipment port and not yet put on a bill. This is
                // the charge the end-of-month billing run is looking for.
                'invoice' => null,
                'type' => ChargeType::Fuel,
                'description' => 'Low sulphur fuel surcharge, second leg',
                'quantity' => '1',
                'unit_price' => '420.0000',
                'tax_rate' => '0',
                'notes' => 'Charged by the carrier after transshipment. Not yet billed.',
            ],

            // SHP-2026-0002: sitting in customs, invoiced, unpaid and past due.
            [
                'shipment' => 'SHP-2026-0002',
                'invoice' => 'INV-2026-0002',
                'type' => ChargeType::Freight,
                'description' => 'Reefer ocean freight, Singapore to Long Beach',
                'quantity' => '1',
                'unit_price' => '3180.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0002',
                'invoice' => 'INV-2026-0002',
                'type' => ChargeType::Customs,
                'description' => 'Customs clearance and entry filing',
                'quantity' => '1',
                'unit_price' => '285.0000',
                'tax_rate' => '0',
                'notes' => 'Includes the rejected first filing; the broker re-presented it.',
            ],
            [
                'shipment' => 'SHP-2026-0002',
                'invoice' => 'INV-2026-0002',
                'type' => ChargeType::Storage,
                'description' => 'Reefer plug-in and storage, 6 days',
                'quantity' => '6',
                'unit_price' => '65.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0002',
                'invoice' => 'INV-2026-0002',
                'type' => ChargeType::Insurance,
                'description' => 'Cargo insurance, declared value USD 84,000',
                'quantity' => '1',
                'unit_price' => '168.0000',
                // The one line in the demo that carries tax, so `tax_amount` is a
                // figure somebody can see being computed rather than a column that
                // is always zero.
                'tax_rate' => '9.00',
            ],

            // SHP-2026-0003: delivered, invoiced, paid in full.
            [
                'shipment' => 'SHP-2026-0003',
                'invoice' => 'INV-2026-0003',
                'type' => ChargeType::Freight,
                'description' => 'Ocean freight, 2 x 20ft',
                'quantity' => '2',
                'unit_price' => '1180.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0003',
                'invoice' => 'INV-2026-0003',
                'type' => ChargeType::Handling,
                'description' => 'Terminal handling, both ends',
                'quantity' => '1',
                'unit_price' => '410.0000',
                'tax_rate' => '0',
            ],
            [
                'shipment' => 'SHP-2026-0003',
                'invoice' => 'INV-2026-0003',
                'type' => ChargeType::Documentation,
                'description' => 'Export documentation',
                'quantity' => '1',
                'unit_price' => '85.0000',
                'tax_rate' => '0',
            ],

            // The withdrawn charge. Its own case in the demo because a voided line
            // is the one that must not be billed and must not count towards an
            // invoice total, and the only way to see that working is for a charge
            // that looks entirely ordinary to be sitting there withdrawn.
            [
                'shipment' => 'SHP-2026-0002',
                'invoice' => null,
                'type' => ChargeType::Demurrage,
                'description' => 'Demurrage, first estimate',
                'quantity' => '1',
                'unit_price' => '1250.0000',
                'tax_rate' => '0',
                'withdrawn' => true,
                'notes' => 'Withdrawn: the detention was the terminal’s own delay, not ours.',
            ],
        ];

        foreach ($charges as $charge) {
            $invoice = $charge['invoice'] ?? null;

            // A charge on an invoice is issued, because the customer is holding a
            // copy of the number. One with no invoice is still a draft: nobody has
            // been asked to pay it yet, and a billing run that swept up "issued"
            // lines nobody sent would bill for work that was never approved.
            $status = ($charge['withdrawn'] ?? false) === true
                ? ChargeStatus::Void
                : ($invoice === null ? ChargeStatus::Draft : ChargeStatus::Issued);

            /*
             * Keyed on the shipment and the description together, because the charges
             * table has no reference of its own to seed against. The description is
             * the natural key a person would recognise - a line for ocean freight on
             * this consignment is a line for ocean freight on this consignment - and
             * it is also the line text on the invoice, so changing it is a
             * deliberate act rather than an accident of a generated id.
             */
            Charge::query()->updateOrCreate(
                [
                    'shipment_id' => $this->shipmentId($charge['shipment']),
                    'description' => $charge['description'],
                ],
                [
                    'invoice_id' => $invoice === null ? null : $this->invoiceId($invoice),
                    'status' => $status,
                    'type' => $charge['type'],
                    'quantity' => $charge['quantity'],
                    'unit_price' => $charge['unit_price'],
                    'tax_rate' => $charge['tax_rate'],
                    'currency' => Money::baseCurrency(),
                    'notes' => $charge['notes'] ?? null,
                ],
            );
        }
    }

    /**
     * What has been billed, and to whom.
     *
     * Five invoices covering the states a receivables screen has to be able to
     * filter on, which is the reason for one of each rather than a spread of
     * similar ones:
     *
     * - 0001 issued, part-paid, not yet due - the ordinary live account
     * - 0002 issued, unpaid, past its due date - the overdue worklist
     * - 0003 issued and settled in full - a closed bill, for the paid filter
     * - 0004 a draft still being assembled, deliberately with no lines yet
     * - 0005 voided, with its charge withdrawn beneath it
     *
     * Only the *lifecycle* status is written here. `partially_paid` and `paid` are
     * derived from the payments by `settle()`, which runs at the end of the finance
     * block - so no status in this file is a figure a person has to keep in step
     * with the rows beneath it, and re-running the seeder cannot leave an invoice
     * claiming to be paid when its receipt has been removed.
     */
    private function invoices(): void
    {
        $invoices = [
            [
                'reference' => 'INV-2026-0001',
                'customer' => 'ACME',
                'status' => InvoiceStatus::Issued,
                // Billed the day the goods went, and thirty days on account. The
                // dates are the ones a terms calculation produces, so the overdue
                // test on 0002 below is not a special case.
                'issue_date' => now()->subDays(12)->toDateString(),
                'due_date' => now()->addDays(18)->toDateString(),
                'notes' => '30 days from the bill of lading date. Second leg to follow under a separate invoice.',
            ],
            [
                'reference' => 'INV-2026-0002',
                'customer' => 'NORTHWIND',
                'status' => InvoiceStatus::Issued,
                'issue_date' => now()->subDays(35)->toDateString(),
                // Overdue. The dashboard's overdue list and the receivables ageing
                // both need a row in this state, and a demo where the oldest bill
                // is also the current one cannot show either.
                'due_date' => now()->subDays(5)->toDateString(),
                'notes' => 'On hold pending the phytosanitary certificate. Broker fee included.',
            ],
            [
                'reference' => 'INV-2026-0003',
                'customer' => 'ACME',
                'status' => InvoiceStatus::Issued,
                'issue_date' => now()->subDays(80)->toDateString(),
                'due_date' => now()->subDays(50)->toDateString(),
                'notes' => 'Settled in full. Proof of delivery on file.',
            ],
            [
                'reference' => 'INV-2026-0004',
                'customer' => 'NORTHWIND',
                'status' => InvoiceStatus::Draft,
                // No issue date, because a draft has not been sent. `due_date` is
                // set anyway to show the terms the operator intends to apply - which
                // is exactly the reason the due-date field is nullable and does not
                // start the clock on its own.
                'issue_date' => null,
                'due_date' => now()->addDays(30)->toDateString(),
                'notes' => 'Drafted against the Palm Oil contract. Rates not confirmed by the carrier.',
            ],
            [
                'reference' => 'INV-2026-0005',
                'customer' => 'NORTHWIND',
                'status' => InvoiceStatus::Void,
                'issue_date' => now()->subDays(22)->toDateString(),
                'due_date' => now()->addDays(8)->toDateString(),
                'notes' => 'Withdrawn: the detention charge was the terminal’s own delay. Replacement raised separately.',
            ],
        ];

        foreach ($invoices as $invoice) {
            Invoice::query()->updateOrCreate(
                ['reference' => $invoice['reference']],
                [
                    'customer_id' => $this->customerId($invoice['customer']),
                    'status' => $invoice['status'],
                    'issue_date' => $invoice['issue_date'],
                    'due_date' => $invoice['due_date'],
                    'currency' => Money::baseCurrency(),
                    // Zeroed rather than left to the column default, because a
                    // re-run over an invoice that already has lines must not briefly
                    // store a total of zero and then settle it back up again - the
                    // settle at the end of the block is what puts the real figures
                    // here, from the lines.
                    'subtotal' => '0.0000',
                    'tax_amount' => '0.0000',
                    'total' => '0.0000',
                    'notes' => $invoice['notes'],
                ],
            );
        }
    }

    /**
     * Money received, in both directions.
     *
     * Two receipts and a credit note, chosen to give every payment filter something
     * in it and to show the one thing about this table that is not obvious from a
     * single row: a credit is a *positive* amount with the `adjustment` method.
     *
     * The credit on 0001 is a real case rather than a demonstration of the type. A
     * customer was charged for a documentation fee on a shipment that then went on
     * a free-of-charge service contract, so the fee was given back - and a refund
     * that showed up as a negative bank transfer would be a figure no statement
     * would reconcile against.
     */
    private function payments(): void
    {
        $payments = [
            [
                'reference' => 'PAY-2026-0001',
                'invoice' => 'INV-2026-0001',
                'amount' => '1350.0000',
                'method' => PaymentMethod::BankTransfer,
                'paid_at' => now()->subDays(6)->setTime(14, 22),
                // Present, because that is the whole point of a transfer: it carries
                // a number that has to be matched to a statement by eye before the
                // receipt can be trusted.
                'bank_reference' => 'FT-SG-448120',
                'notes' => 'Partial payment against INV-2026-0001. Remittance advised by the customer.',
            ],
            [
                'reference' => 'PAY-2026-0002',
                'invoice' => 'INV-2026-0001',
                'amount' => '450.0000',
                'method' => PaymentMethod::Card,
                'paid_at' => now()->subDays(2)->setTime(10, 5),
                // No bank reference and no reconciliation needed: a card payment
                // arrives with the customer’s statement, so it is right by evening.
                'bank_reference' => null,
                'notes' => 'Second instalment, card.',
            ],
            [
                'reference' => 'PAY-2026-0003',
                'invoice' => 'INV-2026-0003',
                // Exactly INV-2026-0003's total: 2 x 1,180 freight, 410 handling and
                // 85 documentation. Stated as the invoice's figure rather than
                // invented, because a payment the API would have refused - a
                // negative balance is money this business owes the customer - is not
                // something a demo should be quietly showing off, and the seeder
                // writes rows directly, so nothing here would have caught it.
                'amount' => '2855.0000',
                'method' => PaymentMethod::BankTransfer,
                'paid_at' => now()->subDays(40)->setTime(9, 40),
                'bank_reference' => 'FT-US-902771',
                'notes' => 'Settled in full, 30 days early.',
            ],
            [
                'reference' => 'PAY-2026-0004',
                'invoice' => 'INV-2026-0001',
                'amount' => '95.0000',
                'method' => PaymentMethod::Adjustment,
                'paid_at' => now()->subDay()->setTime(16, 30),
                'bank_reference' => null,
                'notes' => 'Credit note: documentation fee waived under the amended service agreement.',
            ],
        ];

        foreach ($payments as $payment) {
            $invoiceId = $this->invoiceId($payment['invoice']);

            Payment::query()->updateOrCreate(
                ['reference' => $payment['reference']],
                [
                    'invoice_id' => $invoiceId,
                    // Copied rather than looked up here, and deliberately: the payer
                    // is denormalised on the receipt so a later correction to the
                    // invoice cannot rewrite the history of who paid. Read back off
                    // the invoice rather than restated, because a payment seeded
                    // against a different customer than the bill names is exactly
                    // the state the column exists to make impossible.
                    'customer_id' => Invoice::query()->whereKey($invoiceId)->value('customer_id'),
                    'amount' => $payment['amount'],
                    'method' => $payment['method'],
                    'paid_at' => $payment['paid_at'],
                    'bank_reference' => $payment['bank_reference'],
                    'notes' => $payment['notes'],
                ],
            );
        }
    }

    /**
     * What the business paid out, and on what.
     *
     * The mirror of the charges above and the reason the two are not the same
     * number: a consignment's cost to *us* is the carrier's invoice, which is
     * different from what the customer was billed, and duty is money that is
     * collected and remitted rather than spent. The overhead and payroll rows carry
     * no consignment at all, because a warehouse lease does not belong to a
     * shipment, and a demo where every cost points at a container is a demo
     * somebody will try to add a real margin to.
     *
     * The duty row is the important one to have in a demo: it is the category the
     * two expense figures are built around, and without one on screen nobody can see
     * why a total spend and a margin figure ever differ.
     */
    private function expenses(): void
    {
        $carrierId = fn (?string $code): ?int => $this->lookupId('carriers', 'code', $code, 'carrier');

        $expenses = [
            [
                'reference' => 'EXP-2026-0001',
                'shipment' => 'SHP-2026-0001',
                'carrier' => 'MAEU',
                'category' => ExpenseCategory::Freight,
                'vendor_name' => 'Maersk Line',
                'vendor_reference' => 'ML-SG-2026-88214',
                'amount' => '1980.0000',
                'incurred_on' => now()->subDays(14)->toDateString(),
                'is_billable' => true,
                'description' => 'Ocean freight bought from the carrier',
            ],
            [
                'reference' => 'EXP-2026-0002',
                'shipment' => 'SHP-2026-0001',
                'carrier' => 'MAEU',
                'category' => ExpenseCategory::Fuel,
                'vendor_name' => 'Maersk Line',
                'vendor_reference' => 'ML-SG-2026-88214',
                // The same vendor reference as the freight above, deliberately: a
                // carrier's monthly statement carries one number across every line
                // on it, which is why `vendor_reference` is indexed but not unique.
                'amount' => '340.0000',
                'incurred_on' => now()->subDays(14)->toDateString(),
                'is_billable' => true,
                'description' => 'Low sulphur fuel surcharge',
            ],
            [
                'reference' => 'EXP-2026-0003',
                'shipment' => 'SHP-2026-0002',
                'carrier' => 'SWFT',
                'category' => ExpenseCategory::Freight,
                'vendor_name' => 'Swift Shipping',
                'vendor_reference' => 'STMT-2026-08',
                'amount' => '2610.0000',
                'incurred_on' => now()->subDays(33)->toDateString(),
                'is_billable' => true,
                'description' => 'Reefer ocean freight bought from the carrier',
            ],
            [
                'reference' => 'EXP-2026-0004',
                'shipment' => 'SHP-2026-0002',
                'carrier' => null,
                'category' => ExpenseCategory::Brokerage,
                'vendor_name' => 'Straits Customs Services',
                'vendor_reference' => 'SCS-INV-1147',
                'amount' => '220.0000',
                'incurred_on' => now()->subDays(20)->toDateString(),
                'is_billable' => true,
                'description' => 'Customs entry, plus the rejected first filing',
            ],
            [
                'reference' => 'EXP-2026-0005',
                'shipment' => 'SHP-2026-0002',
                'carrier' => null,
                'category' => ExpenseCategory::Duty,
                'vendor_name' => 'US Customs',
                'vendor_reference' => 'CBP-EIN-9941',
                'amount' => '1420.0000',
                'incurred_on' => now()->subDays(18)->toDateString(),
                // Never recharged, whatever the row says, because it is owed to an
                // authority rather than to a carrier.
                'is_billable' => false,
                'description' => 'Import duty and GST, remitted to customs',
                'notes' => 'Collected from the consignee and passed on. Not a cost of ours.',
            ],
            [
                'reference' => 'EXP-2026-0006',
                'shipment' => 'SHP-2026-0002',
                'carrier' => null,
                'category' => ExpenseCategory::Storage,
                'vendor_name' => 'Pacific Reefer Terminal',
                'vendor_reference' => 'PRT-2026-4410',
                'amount' => '390.0000',
                'incurred_on' => now()->subDays(3)->toDateString(),
                'is_billable' => true,
                'description' => 'Reefer plug-in and storage, 6 days while the certificate was chased',
            ],
            [
                'reference' => 'EXP-2026-0007',
                'shipment' => 'SHP-2026-0003',
                'carrier' => 'SWFT',
                'category' => ExpenseCategory::Freight,
                'vendor_name' => 'Swift Shipping',
                'vendor_reference' => 'STMT-2026-06',
                'amount' => '1905.0000',
                'incurred_on' => now()->subDays(78)->toDateString(),
                'is_billable' => true,
                'description' => 'Ocean freight, 2 x 20ft',
            ],

            // The running costs of a business, which belong to no consignment. All
            // three references null - the whole point of the nullable columns, and
            // the reason the "unattributed" filter exists to find them.
            [
                'reference' => 'EXP-2026-0008',
                'shipment' => null,
                'category' => ExpenseCategory::Overhead,
                'vendor_name' => 'Harbour Industrial Property',
                'vendor_reference' => 'HIP-Q3-2026',
                'amount' => '9600.0000',
                'incurred_on' => now()->subDays(41)->toDateString(),
                'is_billable' => false,
                'description' => 'Warehouse lease, Long Beach and Singapore',
            ],
            [
                'reference' => 'EXP-2026-0009',
                'shipment' => null,
                'category' => ExpenseCategory::Payroll,
                'vendor_name' => null,
                'vendor_reference' => null,
                'amount' => '18400.0000',
                'incurred_on' => now()->subDays(32)->toDateString(),
                'is_billable' => false,
                'description' => 'Operations payroll, month to date',
            ],
            [
                'reference' => 'EXP-2026-0010',
                'shipment' => null,
                'category' => ExpenseCategory::Overhead,
                'vendor_name' => 'Atlas TMS',
                'vendor_reference' => 'ATLAS-ANNUAL-2026',
                'amount' => '5400.0000',
                'incurred_on' => now()->subDays(9)->toDateString(),
                'is_billable' => false,
                'description' => 'Customs and operations software, annual licence',
            ],
        ];

        foreach ($expenses as $expense) {
            $shipment = $expense['shipment'] ?? null;
            $carrier = $expense['carrier'] ?? null;

            Expense::query()->updateOrCreate(
                ['reference' => $expense['reference']],
                [
                    'shipment_id' => $shipment === null ? null : $this->shipmentId($shipment),
                    'booking_id' => null,
                    'carrier_id' => $carrier === null ? null : $carrierId($carrier),
                    'category' => $expense['category'],
                    'vendor_name' => $expense['vendor_name'] ?? null,
                    'vendor_reference' => $expense['vendor_reference'] ?? null,
                    'amount' => $expense['amount'],
                    'currency' => Money::baseCurrency(),
                    'incurred_on' => $expense['incurred_on'],
                    'is_billable' => $expense['is_billable'] ?? false,
                    'description' => $expense['description'] ?? null,
                    'notes' => $expense['notes'] ?? null,
                ],
            );
        }

        $this->settleDemoInvoices();
    }

    /**
     * The final pass that makes the finance block add up.
     *
     * Every invoice is settled once, at the end, after its lines and its receipts
     * both exist. `settle()` writes the totals from the lines and the received and
     * balance figures from the payments, and derives the status from the two - so
     * `partially_paid` and `paid` are never stated in this file at all, only
     * arrived at.
     *
     * That ordering is the point. Building an invoice with a hand-written total and
     * a matching `paid_amount` means writing the same figure twice and hoping the
     * two copies agree; letting the model derive them means the seed data cannot
     * describe a state the code would not produce, and re-running the seeder after
     * a change to the settlement rules leaves the demo consistent with them.
     *
     * Called with the default, so a line-less invoice keeps the figures stored on
     * it rather than being zeroed - which is what makes INV-2026-0004 a draft that
     * has a due date and no total, instead of a bill for nothing.
     */
    private function settleDemoInvoices(): void
    {
        Invoice::query()->each(function (Invoice $invoice): void {
            $invoice->settle()->save();
        });
    }
}
