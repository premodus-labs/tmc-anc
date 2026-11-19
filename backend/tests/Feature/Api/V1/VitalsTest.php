<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use App\Models\Visitation;
use App\Models\VisitationVitals;
use Database\Seeders\CheckpointSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VitalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CheckpointSeeder::class);
    }

    private function actingWith(string ...$permissions): AppUser
    {
        $user = AppUser::factory()->withPermissions(...$permissions)->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_records_vitals(): void
    {
        $user = $this->actingWith('vitals.record');
        $visit = Visitation::factory()->at('triage')->create();

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", [
            'temperature_c' => 36.8,
            'bp_systolic' => 118,
            'bp_diastolic' => 76,
            'pulse_bpm' => 82,
            'weight_kg' => 64.5,
        ])
            ->assertCreated()
            ->assertJsonPath('data.bp_systolic', 118)
            ->assertJsonPath('data.temperature_c', 36.8)
            ->assertJsonPath('data.recorded_by', $user->id)
            ->assertJsonPath('data.spo2_pct', null);
    }

    public function test_needs_at_least_one_measurement(): void
    {
        $this->actingWith('vitals.record');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", ['notes' => 'Patient anxious'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('temperature_c');
    }

    public function test_blood_pressure_needs_both_numbers_in_the_right_order(): void
    {
        $this->actingWith('vitals.record');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", ['bp_systolic' => 120])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bp_diastolic');

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", ['bp_systolic' => 70, 'bp_diastolic' => 110])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bp_systolic');
    }

    public function test_rejects_impossible_readings(): void
    {
        $this->actingWith('vitals.record');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", ['temperature_c' => 368, 'spo2_pct' => 120])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['temperature_c', 'spo2_pct']);
    }

    public function test_closed_visit_takes_no_vitals(): void
    {
        $this->actingWith('vitals.record');
        $visit = Visitation::factory()->closed()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", ['pulse_bpm' => 80])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visit');
    }

    public function test_recording_requires_permission(): void
    {
        $this->actingWith('visit.view', 'visit.update');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/vitals", ['pulse_bpm' => 80])->assertForbidden();
    }

    public function test_lists_vitals_latest_first(): void
    {
        $user = $this->actingWith('visit.view');
        $visit = Visitation::factory()->create();
        $first = $visit->vitals()->make(['bp_systolic' => 150, 'bp_diastolic' => 95]);
        $first->forceFill(['recorded_by' => $user->id, 'recorded_at' => now()->subMinutes(20)])->save();
        $repeat = $visit->vitals()->make(['bp_systolic' => 138, 'bp_diastolic' => 88]);
        $repeat->forceFill(['recorded_by' => $user->id, 'recorded_at' => now()])->save();

        $this->getJson("/api/v1/visits/{$visit->id}/vitals")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $repeat->id);

        $this->assertSame(2, VisitationVitals::count());
    }
}
