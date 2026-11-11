<?php
// ============================================================
// tests/Feature/WorkOrderTest.php
// ============================================================
namespace Tests\Feature;

use App\Models\{User, Asset, WorkOrder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $technician;
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);

        $this->admin      = User::factory()->create()->assignRole('admin');
        $this->technician = User::factory()->create()->assignRole('technician');
        $this->asset      = Asset::factory()->create();
    }

    public function test_admin_can_create_work_order(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('work-orders.store'), [
                'title'    => 'Test Work Order',
                'type'     => 'corrective',
                'priority' => 'high',
                'asset_id' => $this->asset->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('work_orders', ['title' => 'Test Work Order']);
    }

    public function test_viewer_cannot_create_work_order(): void
    {
        $viewer = User::factory()->create()->assignRole('viewer');

        $this->actingAs($viewer)
            ->post(route('work-orders.store'), [
                'title'    => 'Should Fail',
                'type'     => 'corrective',
                'priority' => 'low',
                'asset_id' => $this->asset->id,
            ])
            ->assertForbidden();
    }

    public function test_work_order_generates_code(): void
    {
        $wo = WorkOrder::create([
            'title'      => 'Auto Code Test',
            'type'       => 'preventive',
            'priority'   => 'low',
            'asset_id'   => $this->asset->id,
            'created_by' => $this->admin->id,
            'status'     => 'open',
            'progress'   => 0,
        ]);

        $this->assertStringStartsWith('WO-', $wo->code);
    }

    public function test_overdue_scope(): void
    {
        WorkOrder::create([
            'title'      => 'Overdue Order',
            'type'       => 'corrective',
            'priority'   => 'high',
            'asset_id'   => $this->asset->id,
            'created_by' => $this->admin->id,
            'status'     => 'open',
            'progress'   => 0,
            'due_at'     => now()->subDays(3),
        ]);

        $this->assertEquals(1, WorkOrder::overdue()->count());
    }

    public function test_technician_can_complete_assigned_order(): void
    {
        $wo = WorkOrder::create([
            'title'       => 'Assigned to Tech',
            'type'        => 'corrective',
            'priority'    => 'medium',
            'asset_id'    => $this->asset->id,
            'created_by'  => $this->admin->id,
            'assigned_to' => $this->technician->id,
            'status'      => 'in_progress',
            'progress'    => 50,
            'started_at'  => now()->subHour(),
        ]);

        $this->actingAs($this->technician)
            ->patch(route('work-orders.complete', $wo), ['notes' => 'All done.'])
            ->assertRedirect();

        $this->assertEquals('completed', $wo->fresh()->status);
    }
}


// ============================================================
// tests/Feature/DashboardTest.php
// ============================================================
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_for_authenticated_user(): void
    {
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.index');
    }

    public function test_dashboard_redirects_guests(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
