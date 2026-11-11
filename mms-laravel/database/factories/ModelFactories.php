<?php
// ============================================================
// database/factories/UserFactory.php
// ============================================================
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'is_active'         => true,
        ];
    }
}


// ============================================================
// database/factories/AssetFactory.php
// ============================================================
namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'name'                => fake()->words(3, true),
            'code'                => 'AST-' . fake()->unique()->numerify('###'),
            'category'            => fake()->randomElement(['HVAC', 'Electrical', 'Plumbing', 'Mechanical', 'Safety']),
            'location'            => 'Building ' . fake()->randomLetter() . ', Floor ' . fake()->numberBetween(1, 5),
            'status'              => 'active',
            'installed_at'        => fake()->dateTimeBetween('-8 years', '-1 year'),
            'warranty_expires_at' => fake()->optional()->dateTimeBetween('now', '+3 years'),
        ];
    }
}


// ============================================================
// database/factories/WorkOrderFactory.php
// ============================================================
namespace Database\Factories;

use App\Models\{WorkOrder, Asset, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type'        => fake()->randomElement(['corrective', 'preventive', 'inspection', 'emergency']),
            'priority'    => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status'      => fake()->randomElement(['open', 'assigned', 'in_progress', 'completed']),
            'asset_id'    => Asset::factory(),
            'created_by'  => User::factory(),
            'assigned_to' => null,
            'progress'    => fake()->numberBetween(0, 100),
            'due_at'      => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => 'open', 'progress' => 0]);
    }

    public function overdue(): static
    {
        return $this->state(['status' => 'open', 'due_at' => now()->subDays(3)]);
    }

    public function completed(): static
    {
        return $this->state([
            'status'       => 'completed',
            'progress'     => 100,
            'started_at'   => now()->subHours(4),
            'completed_at' => now()->subHour(),
        ]);
    }
}
