<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use App\Models\Visitation;
use Database\Seeders\CheckpointSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CheckpointSeeder::class);
        Sanctum::actingAs(AppUser::factory()->withPermissions('visit.view')->create());
    }

    public function test_groups_open_visits_by_checkpoint_in_clinic_order(): void
    {
        Visitation::factory()->at('triage')->create();
        Visitation::factory()->at('triage')->create();
        Visitation::factory()->at('lab')->create();
        Visitation::factory()->closed()->create();

        $response = $this->getJson('/api/v1/queue')
            ->assertOk()
            ->assertJsonCount(count(CheckpointSeeder::CHECKPOINTS), 'data')
            ->assertJsonPath('data.0.checkpoint.code', 'reception')
            ->assertJsonPath('data.0.count', 0)
            ->assertJsonPath('data.1.checkpoint.code', 'triage')
            ->assertJsonPath('data.1.count', 2)
            ->assertJsonPath('data.3.checkpoint.code', 'lab')
            ->assertJsonPath('data.3.count', 1);

        $this->assertSame(3, collect($response->json('data'))->sum('count'));
    }

    public function test_orders_by_priority_then_arrival(): void
    {
        $early = Visitation::factory()->at('triage')->create(['arrived_at' => now()->subHour()]);
        $urgent = Visitation::factory()->at('triage')->create(['priority' => 'urgent', 'arrived_at' => now()->subMinutes(5)]);
        $late = Visitation::factory()->at('triage')->create(['arrived_at' => now()->subMinutes(10)]);
        $emergency = Visitation::factory()->at('triage')->create(['priority' => 'emergency', 'arrived_at' => now()]);

        $ids = collect($this->getJson('/api/v1/queue?checkpoint=triage')->assertOk()->json('data.0.visits'))->pluck('id');

        $this->assertSame([$emergency->id, $urgent->id, $early->id, $late->id], $ids->all());
    }

    public function test_shows_patient_and_waiting_time(): void
    {
        $visit = Visitation::factory()->create();

        $this->getJson('/api/v1/queue?checkpoint=reception')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.visits.0.patient.patient_number', $visit->patient->patient_number)
            ->assertJsonPath('data.0.visits.0.waiting_since', $visit->currentLog->entered_at->toIso8601String());
    }

    public function test_waiting_since_resets_after_a_move(): void
    {
        Sanctum::actingAs(AppUser::factory()->withPermissions('visit.view', 'visit.update')->create());
        $visit = Visitation::factory()->create(['arrived_at' => now()->subHour()]);

        $this->travel(5)->minutes();
        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'triage'])->assertOk();

        $waitingSince = $this->getJson('/api/v1/queue?checkpoint=triage')->json('data.0.visits.0.waiting_since');
        $this->assertSame(now()->toIso8601String(), $waitingSince);
    }

    public function test_rejects_unknown_checkpoint_filter(): void
    {
        $this->getJson('/api/v1/queue?checkpoint=spa')->assertUnprocessable();
    }

    public function test_requires_permission(): void
    {
        Sanctum::actingAs(AppUser::factory()->withPermissions('patient.view')->create());

        $this->getJson('/api/v1/queue')->assertForbidden();
    }
}
