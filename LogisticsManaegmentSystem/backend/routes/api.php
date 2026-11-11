<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CarrierController;
use App\Http\Controllers\Api\V1\ChargeController;
use App\Http\Controllers\Api\V1\ContainerController;
use App\Http\Controllers\Api\V1\CustomsController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\PortController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\System\PingController;
use App\Http\Controllers\Api\V1\TrackingController;
use App\Http\Controllers\Api\V1\TruckController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VesselController;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes - version 1
|--------------------------------------------------------------------------
|
| Registered in bootstrap/app.php with the `api` prefix, so everything here
| lives under /api. The v1 group below yields /api/v1/...
|
| Adding routes is not enough: every route must also carry the permission it
| requires. The React router mirrors these permissions for UI purposes only -
| the server is the authority.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    /*
    |----------------------------------------------------------------------
    | Public - liveness and version discovery
    |----------------------------------------------------------------------
    */
    Route::get('ping', PingController::class)->name('ping');
    Route::get('ping/health', [PingController::class, 'health'])->name('ping.health');
    Route::get('version', [PingController::class, 'version'])->name('version');

    /*
    |----------------------------------------------------------------------
    | Authentication
    |----------------------------------------------------------------------
    |
    | The sign-in surface. None of it carries a permission: a user has to be
    | able to authenticate before there is anything to authorise them against.
    | Each endpoint is rate limited by the `api` limiter on top of the tighter
    | per-credential limit LoginController applies to itself.
    |
    */
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('register', [RegisterController::class, 'store'])->name('register');
        Route::post('login', [LoginController::class, 'store'])->name('login');
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('forgot-password');
        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('reset-password');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        /*
        | Google sign-in. The redirect and callback are browser navigations, so
        | they answer with 302s; the SPA redeems the one-time code through the
        | exchange endpoint, which is the only one of the three that returns the
        | JSON envelope.
        */
        Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])
            ->middleware('throttle:10,1')
            ->name('google.redirect');
        Route::get('google/callback', [GoogleAuthController::class, 'callback'])
            ->middleware('throttle:10,1')
            ->name('google.callback');
        Route::post('google/exchange', [GoogleAuthController::class, 'exchange'])
            ->middleware('throttle:10,1')
            ->name('google.exchange');

        /*
        | Everything below needs a valid, unexpired bearer token.
        |
        | `auth:sanctum` is listed on the group rather than applied globally,
        | because the routes above it are reachable without a session. An
        | unauthenticated request here produces the 401 envelope from
        | bootstrap/app.php, never a redirect.
        */
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [SessionController::class, 'show'])->name('me');
            Route::post('logout', [SessionController::class, 'destroy'])->name('logout');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Master data
    |----------------------------------------------------------------------
    |
    | The lookup tables every other module references. Nothing here moves a
    | shipment, so these are the records that can safely be exposed widest.
    |
    | `auth:sanctum` comes first on the group. The permission middleware answers
    | 403 to an anonymous caller, which would tell a signed-out browser that the
    | endpoint exists rather than that it needs a session.
    |
    | Read and write are deliberately separate permissions: a viewer can be
    | granted the list without being able to change a rate or delete a carrier.
    |
    */
    Route::middleware('auth:sanctum')->group(function (): void {

        /**
         * `Route::defaults()` takes one key and one value, and is not available on
         * a RouteRegistrar, so defaults are applied per route through this.
         *
         * @param  Illuminate\Routing\Route  $route
         * @param  array<string, string>  $defaults
         */
        $withDefaults = static function (Illuminate\Routing\Route $route, array $defaults): Illuminate\Routing\Route {
            foreach ($defaults as $key => $value) {
                $route->defaults($key, $value);
            }

            return $route;
        };

        /*
         * Registers the five methods every master-data module exposes.
         *
         * @param  class-string<\Illuminate\Routing\Controller>  $controller
         * @param  array<string, string>  $permissions
         * @param  array<string, string>  $defaults
         */
        $module = function (string $prefix, string $controller, array $permissions, array $defaults = []) use ($withDefaults): void {
            Route::prefix($prefix)->name($prefix.'.')->group(function () use ($controller, $permissions, $defaults, $withDefaults): void {
                $withDefaults(
                    Route::get('/', [$controller, 'index'])
                        ->middleware('permission:'.$permissions['view']),
                    $defaults,
                )->name('index');

                $withDefaults(
                    Route::post('/', [$controller, 'store'])
                        ->middleware('permission:'.$permissions['create']),
                    $defaults,
                )->name('store');

                $withDefaults(
                    Route::get('{record}', [$controller, 'show'])
                        ->middleware('permission:'.$permissions['view']),
                    $defaults,
                )->name('show');

                $withDefaults(
                    Route::match(['put', 'patch'], '{record}', [$controller, 'update'])
                        ->middleware('permission:'.$permissions['update']),
                    $defaults,
                )->name('update');

                $withDefaults(
                    Route::delete('{record}', [$controller, 'destroy'])
                        ->middleware('permission:'.$permissions['delete']),
                    $defaults,
                )->name('destroy');
            });
        };

        $crud = static fn (string $namespace): array => [
            'view' => $namespace.'.view',
            'create' => $namespace.'.create',
            'update' => $namespace.'.update',
            'delete' => $namespace.'.delete',
        ];

        /**
         * Registers the three methods a module that cannot be created or deleted
         * exposes.
         *
         * Deliberately not a flag on the helper above. Passing `false` for create
         * and delete would leave the controller's `store` and `destroy` reachable
         * in principle and guarded only by a missing permission key, which is the
         * kind of near-miss that reads as working until someone notices the
         * permission lookup returning nothing and allowing the request. Registering
         * the three routes means an unrouteable path is a 405 from the router, and
         * the controller never has to contain a method that must not be called.
         *
         * @param  class-string<Controller>  $controller
         * @param  array<string, string>  $permissions
         * @param  array<string, string>  $defaults
         */
        $readUpdate = function (string $prefix, string $controller, array $permissions, array $defaults = []) use ($withDefaults): void {
            Route::prefix($prefix)->name($prefix.'.')->group(function () use ($controller, $permissions, $defaults, $withDefaults): void {
                $withDefaults(
                    Route::get('/', [$controller, 'index'])
                        ->middleware('permission:'.$permissions['view']),
                    $defaults,
                )->name('index');

                $withDefaults(
                    Route::get('{record}', [$controller, 'show'])
                        ->middleware('permission:'.$permissions['view']),
                    $defaults,
                )->name('show');

                $withDefaults(
                    Route::match(['put', 'patch'], '{record}', [$controller, 'update'])
                        ->middleware('permission:'.$permissions['update']),
                    $defaults,
                )->name('update');
            });
        };

        /*
        | Overview.
        |
        | The one endpoint in the API that is not a module: a single read-only
        | aggregate of the tables below, behind a single permission.
        |
        | It is registered before the module groups and named the same way they
        | are, so `permission:dashboard.view` reads as a first-class grant rather
        | than as an exception. The permission is already in the inventory and
        | already held by every configured role, so no seeder change was needed -
        | which is the point of the catalogue listing what the screens need rather
        | than what has been built.
        */
        Route::get('dashboard', DashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        $module('carriers', CarrierController::class, $crud('carrier'));
        $module('vessels', VesselController::class, $crud('vessel'));
        $module('ports', PortController::class, $crud('port'));
        $module('trucks', TruckController::class, $crud('truck'));
        $module('drivers', DriverController::class, $crud('driver'));

        /*
         * Customers, shippers and consignees share a table and a controller.
         *
         * The `partyType` route default is the only thing that decides what kind
         * of record an endpoint writes. It is not client-settable, so posting a
         * shipper's details to /customers creates a customer, not a shipper.
         *
         * Reading any party is allowed with the broad `customer.view` on every
         * surface: an operator working a shipment has to see who sent it and
         * who is receiving it, and requiring `shipper.view` for that would leave
         * them unable to read their own screen. Writing stays per module, because
         * who may edit a customer's terms is not who may rewrite a consignee's
         * delivery address.
         */
        foreach (['customers' => 'customer', 'shippers' => 'shipper', 'consignees' => 'consignee'] as $prefix => $type) {
            $module(
                prefix: $prefix,
                controller: PartyController::class,
                permissions: ['view' => 'customer.view'] + $crud($type),
                defaults: ['partyType' => $type],
            );
        }

        /*
         * Warehouses and factories, the same arrangement: one `sites` table, two
         * surfaces, and the write permission follows the surface being written.
         */
        foreach (['warehouses' => 'warehouse', 'factories' => 'factory'] as $prefix => $kind) {
            $module(
                prefix: $prefix,
                controller: SiteController::class,
                permissions: ['view' => 'warehouse.view'] + $crud($kind),
                defaults: ['siteKind' => $kind],
            );
        }

        /*
        | Shipments.
        |
        | The first module that is not master data, registered through the same
        | helper because the five methods and the read/write permission split are
        | genuinely identical. What differs lives in ShipmentController: the search
        | reaches the shipper, consignee and carrier, the party references are
        | checked for kind, and there is no `is_active`.
        |
        | No defaults here, because a shipment is not shared between surfaces the
        | way parties and sites are - there is exactly one list.
        */
        $module('shipments', ShipmentController::class, $crud('shipment'));

        /*
        | Bookings.
        |
        | Registered through the same helper and for the same reasons: five methods,
        | a read/write permission split. Bookings sits above shipments in the file
        | because the dependency runs that way - a shipment points at the booking
        | it came from, and the migration adding that column has to run after
        | `bookings` exists.
        |
        | What differs lives in BookingController, and it is about the request
        | rather than the movement: `pending` for the requests a carrier has not
        | answered, and requested_* dates because what was asked for is not always
        | what was agreed.
        */
        $module('bookings', BookingController::class, $crud('booking'));

        /*
        | Tracking.
        |
        | The first module here that is not CRUD, and the route registration is
        | what shows it. `tracking` grants `view` and `update` and nothing else in
        | config/permissions.php, so there is no create and no delete: a tracking
        | row is born with its consignment in Shipment::booted() and dies with it.
        |
        | That is why it goes through `$readUpdate` rather than `$module`. The row
        | is a report about a consignment, not a thing anybody files, and giving
        | the screen a way to orphan one from the consignment it describes would be
        | a capability the domain does not have.
        */
        $readUpdate('tracking', TrackingController::class, [
            'view' => 'tracking.view',
            'update' => 'tracking.update',
        ]);

        /*
        | Containers.
        |
        | The first of the equipment modules, and the route registration is plain
        | CRUD - `container` grants all four permissions, so a box that was booked
        | and then released has to be removable, which tracking's report does not.
        |
        | What is unusual about this module is the uniqueness of its key, and that
        | lives in ContainerController rather than here: a container number is
        | reusable across consignments, so the constraint is on the pair.
        */
        $module('containers', ContainerController::class, $crud('container'));

        /*
        | Customs.
        |
        | CRUD in full, and registered with the same helper for the same reason. The
        | prefix is the permission namespace and the word an operator uses, though
        | what is recorded is one consignment's clearance with the authority rather
        | than anything the authority itself is.
        |
        | The one thing to know about this table is that a filing is keyed on a
        | consignment *and* a port, because a transshipment clears more than once and
        | a refusal has to stay on the record. That is CustomsController::rules().
        */
        $module('customs', CustomsController::class, $crud('customs'));

        /*
        | Deliveries.
        |
        | CRUD in full, same helper. A consignment can have several of these - a
        | consignment delivered in parts, or a failed attempt that was retried - so
        | nothing is keyed on the consignment alone. The key is `reference`, which is
        | ours and globally unique.
        |
        | The three operations modules differ in their keys on purpose, which is the
        | thing worth knowing before editing any of them: a delivery reference is
        | ours and unique, a container number is the carrier's and reusable, and a
        | customs filing belongs to a consignment *and* a port.
        */
        $module('deliveries', DeliveryController::class, $crud('delivery'));

        /*
        |-----------------------------------------------------------------
        | Finance
        |-----------------------------------------------------------------
        |
        | Four modules, and they are not four independent lists - they are
        | the settlement chain, in the order a forwarder walks it when chasing
        | an unpaid bill: what did we do for this consignment, what did we
        | bill, what came back, and what did it cost us.
        |
        | The read/write permission split is the generic one, so a role can be
        | given `invoice.view` and nothing else and be able to read a bill
        | without being able to void one - which is the split the permissions
        | catalogue is explicit about and the reason the inventory lists
        | finance entries separately per resource.
        |
        | Invoices are the only module here with extra routes. `issue` and
        | `void` are actions rather than edits: sending a bill is a commitment
        | the customer can rely on and starts the due-date clock, while an
        | ordinary PATCH to an issued invoice is refused by
        | InvoiceController::guardEditable(). Modelling those as an update that
        | happens to change `status` would make them indistinguishable from
        | correcting a due date.
        |
        | Both take `invoice.update` rather than a permission of their own, and
        | that is a considered choice: an issuer and an editor are the same
        | person. Somebody who may change the contents of a bill may certainly
        | send it, and a separate `invoice.issue` grant would in practice be
        | held alongside `invoice.update` in every role, so it would be a second
        | name for the same decision.
        */
        $module('charges', ChargeController::class, $crud('charge'));
        $module('invoices', InvoiceController::class, $crud('invoice'));
        $module('payments', PaymentController::class, $crud('payment'));
        $module('expenses', ExpenseController::class, $crud('expense'));

        Route::prefix('invoices')->name('invoices.')->group(function () use ($withDefaults): void {
            $withDefaults(
                Route::post('{record}/issue', [InvoiceController::class, 'issue'])
                    ->middleware('permission:invoice.update'),
                [],
            )->name('issue');

            $withDefaults(
                Route::post('{record}/void', [InvoiceController::class, 'void'])
                    ->middleware('permission:invoice.update'),
                [],
            )->name('void');
        });

        /*
        |-----------------------------------------------------------------
        | Documents
        |-----------------------------------------------------------------
        |
        | CRUD in full through the same helper, with a fifth permission the
        | helper does not know about.
        |
        | The catalogue grants `document.view`, `.create`, `.update`, `.delete`
        | and `.download`, and the split between the first and the last is the
        | point of the module: a bill of lading is commercially sensitive, so
        | somebody can be shown that a document exists and be unable to open it.
        | `$crud` covers four names and `document.download` is routed on its own
        | below rather than being folded into the helper, because it is a GET that
        | returns bytes rather than the JSON envelope.
        */
        $module('documents', DocumentController::class, $crud('document'));

        Route::prefix('documents')->name('documents.')->group(function () use ($withDefaults): void {
            $withDefaults(
                Route::get('{record}/download', [DocumentController::class, 'download'])
                    ->middleware('permission:document.download'),
                [],
            )->name('download');
        });

        /*
        |-----------------------------------------------------------------
        | Administration
        |-----------------------------------------------------------------
        |
        | The accounts that can sign in. Registered through the same helper as
        | every other module - five methods and the read/write split - but the
        | controller is not master data: a user carries a role set and a
        | password, so its writes are its own.
        |
        | `users/roles` is a static path and is registered *before* the module.
        | `$module` puts a `users/{record}` route on the same prefix, and the
        | router matches in declaration order, so registering the module first
        | would make a request for the role options look like a request for the
        | user whose id is "roles".
        |
        */
        Route::get('users/roles', [UserController::class, 'roles'])
            ->middleware('permission:user.view')
            ->name('users.roles');

        $module('users', UserController::class, $crud('user'));

        /*
        |----------------------------------------------------------------------
        | Administration
        |----------------------------------------------------------------------
        |
        | Accounts, roles, permissions, settings, notifications and the activity
        | log. Each route carries the permission it requires, and every one of
        | them is behind `auth:sanctum` so an anonymous caller never learns they
        | exist.
        |
        */
        Route::prefix('admin')->name('admin.')->group(function () use ($module, $readUpdate, $crud): void {
            Route::get('users/roles', [UserController::class, 'roles'])
                ->middleware('permission:user.view')
                ->name('users.roles');

            $module('users', UserController::class, $crud('user'));

            $module('roles', RoleController::class, $crud('role'));

            Route::get('permissions', [PermissionController::class, 'index'])
                ->middleware('permission:role.view')
                ->name('permissions.index');

            $readUpdate('settings', SettingController::class, [
                'view' => 'setting.view',
                'update' => 'setting.update',
            ]);

            $readUpdate('notifications', NotificationController::class, [
                'view' => 'notification.view',
                'update' => 'notification.update',
            ]);

            Route::get('activity-log', [ActivityLogController::class, 'index'])
                ->middleware('permission:activity_log.view')
                ->name('activity-log.index');

            Route::get('activity-log/{record}', [ActivityLogController::class, 'show'])
                ->middleware('permission:activity_log.view')
                ->name('activity-log.show');
        });

        /*
        |----------------------------------------------------------------------
        | Reports
        |----------------------------------------------------------------------
        |
        | Read-only aggregates behind `report.view`. `report.export` gates the
        | download of a report as CSV.
        |
        */
        Route::prefix('reports')->name('reports.')->group(function (): void {
            Route::get('shipments', [ReportController::class, 'shipments'])
                ->middleware('permission:report.view')
                ->name('shipments');

            Route::get('operational', [ReportController::class, 'operational'])
                ->middleware('permission:report.view')
                ->name('operational');

            Route::get('customers', [ReportController::class, 'customers'])
                ->middleware('permission:report.view')
                ->name('customers');

            Route::get('carriers', [ReportController::class, 'carriers'])
                ->middleware('permission:report.view')
                ->name('carriers');

            Route::get('financial', [ReportController::class, 'financial'])
                ->middleware('permission:report.view')
                ->name('financial');

            Route::get('{report}/export', [ReportController::class, 'export'])
                ->middleware('permission:report.export')
                ->name('export');
        });
    });

    /*
    |----------------------------------------------------------------------
    | Later phases
    |----------------------------------------------------------------------
    |
    | Nothing here yet: report, notification, role, setting and activity_log
    | are implemented above in the Administration and Reports groups. The
    | modules that remain for a later phase would register in their own group
    | with the same read/write permission split.
    |
    | Shipment, booking, tracking, container, customs, delivery, the four finance
    | modules and documents are implemented above and are deliberately not part of
    | this list. None of them is master data either, so none of them extends
    | MasterDataController - they extend RecordController and declare their own
    | lifecycle.
    |
    */
});
