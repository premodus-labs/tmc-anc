<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use App\Models\Patient;
use App\Models\Visitation;
use App\Models\VisitationCheckpointLog;
use Database\Seeders\CheckpointSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VisitTest extends TestCase
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

    public function test_checks_a_patient_in_at_reception(): void
    {
        $user = $this->actingWith('visit.create');
        $patient = Patient::factory()->create();

        $this->postJson('/api/v1/visits', [
            'patient_id' => $patient->id,
            'visit_type' => 'anc',
            'patient_consent' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'routine')
            ->assertJsonPath('data.current_checkpoint.code', 'reception')
            ->assertJsonPath('data.patient.id', $patient->id)
            ->assertJsonCount(1, 'data.checkpoint_log');

        $visit = Visitation::sole();
        $this->assertSame($user->id, $visit->registered_by);
        $this->assertNotEmpty($visit->visit_number);
        $this->assertNull($visit->currentLog->cleared_at);
    }

    public function test_check_in_validates_input(): void
    {
        $this->actingWith('visit.create');

        $this->postJson('/api/v1/visits', ['patient_id' => 999, 'visit_type' => 'spa', 'priority' => 'whenever'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_id', 'visit_type', 'priority', 'patient_consent']);
    }

    public function test_client_cannot_set_server_owned_fields(): void
    {
        $this->actingWith('visit.create');
        $patient = Patient::factory()->create();

        $this->postJson('/api/v1/visits', [
            'patient_id' => $patient->id,
            'visit_type' => 'general',
            'patient_consent' => true,
            'status' => 'completed',
            'visit_number' => 'HACKED',
            'current_checkpoint_id' => 999,
        ])->assertCreated()->assertJsonPath('data.status', 'open');

        $this->assertNotSame('HACKED', Visitation::sole()->visit_number);
    }

    public function test_patient_cannot_have_two_open_visits(): void
    {
        $this->actingWith('visit.create');
        $visit = Visitation::factory()->create();

        $this->postJson('/api/v1/visits', [
            'patient_id' => $visit->patient_id,
            'visit_type' => 'general',
            'patient_consent' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('patient_id');
    }

    public function test_patient_can_check_in_again_after_closing(): void
    {
        $this->actingWith('visit.create');
        $visit = Visitation::factory()->closed()->create();

        $this->postJson('/api/v1/visits', [
            'patient_id' => $visit->patient_id,
            'visit_type' => 'follow_up',
            'patient_consent' => true,
        ])->assertCreated();
    }

    public function test_check_in_requires_permission(): void
    {
        $this->actingWith('visit.view');

        $this->postJson('/api/v1/visits', [])->assertForbidden();
    }

    public function test_moves_a_visit_and_logs_both_checkpoints(): void
    {
        $user = $this->actingWith('visit.update', 'visit.view');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'triage', 'note' => 'Paid registration'])
            ->assertOk()
            ->assertJsonPath('data.current_checkpoint.code', 'triage')
            ->assertJsonCount(2, 'data.checkpoint_log')
            ->assertJsonPath('data.checkpoint_log.0.checkpoint.code', 'reception')
            ->assertJsonPath('data.checkpoint_log.0.note', 'Paid registration')
            ->assertJsonPath('data.checkpoint_log.0.cleared_by', $user->id)
            ->assertJsonPath('data.checkpoint_log.1.checkpoint.code', 'triage')
            ->assertJsonPath('data.checkpoint_log.1.cleared_at', null);

        $this->assertSame(1, VisitationCheckpointLog::whereNull('cleared_at')->count());
    }

    public function test_can_go_back_to_an_earlier_checkpoint(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->at('consultation')->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'lab'])->assertOk();
        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'consultation'])
            ->assertOk()
            ->assertJsonCount(3, 'data.checkpoint_log');
    }

    public function test_skipping_marks_the_checkpoint_being_left(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->at('lab')->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'pharmacy', 'skipped' => true])
            ->assertOk()
            ->assertJsonPath('data.checkpoint_log.0.skipped', true)
            ->assertJsonPath('data.checkpoint_log.1.skipped', false);
    }

    public function test_cannot_move_to_the_current_checkpoint(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->at('triage')->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'triage'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('checkpoint');
    }

    public function test_move_rejects_unknown_checkpoint(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'spa'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('checkpoint');
    }

    public function test_closed_visit_cannot_move(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->closed()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'triage'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visit');
    }

    public function test_move_requires_permission(): void
    {
        $this->actingWith('visit.view');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/move", ['checkpoint' => 'triage'])->assertForbidden();
    }

    public function test_completes_a_visit(): void
    {
        $user = $this->actingWith('visit.update');
        $visit = Visitation::factory()->at('billing')->create();

        $this->postJson("/api/v1/visits/{$visit->id}/close", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.current_checkpoint', null)
            ->assertJsonPath('data.closed_by', $user->id);

        $this->assertNotNull($visit->refresh()->closed_at);
        $this->assertSame(0, VisitationCheckpointLog::whereNull('cleared_at')->count());
    }

    public function test_cancelling_needs_a_reason(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/close", ['status' => 'cancelled'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('closure_reason');

        $this->postJson("/api/v1/visits/{$visit->id}/close", ['status' => 'cancelled', 'closure_reason' => 'Left before being seen'])
            ->assertOk()
            ->assertJsonPath('data.closure_reason', 'Left before being seen');
    }

    public function test_cannot_close_twice(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->closed()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/close", ['status' => 'completed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visit');
    }

    public function test_close_cannot_reopen(): void
    {
        $this->actingWith('visit.update');
        $visit = Visitation::factory()->create();

        $this->postJson("/api/v1/visits/{$visit->id}/close", ['status' => 'open'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_lists_and_filters_visits(): void
    {
        $this->actingWith('visit.view');
        $patient = Patient::factory()->create();
        Visitation::factory()->closed()->create(['patient_id' => $patient->id]);
        Visitation::factory()->at('lab')->create(['patient_id' => $patient->id]);
        Visitation::factory()->create();

        $this->getJson('/api/v1/visits')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/visits?status=open')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/visits?patient_id={$patient->id}")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/visits?checkpoint=lab')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.current_checkpoint.code', 'lab');
    }

    public function test_shows_a_visit(): void
    {
        $this->actingWith('visit.view');
        $visit = Visitation::factory()->create();

        $this->getJson("/api/v1/visits/{$visit->id}")
            ->assertOk()
            ->assertJsonPath('data.visit_number', $visit->visit_number)
            ->assertJsonStructure(['data' => ['patient', 'current_checkpoint', 'checkpoint_log', 'vitals']]);
    }

    public function test_viewing_requires_permission(): void
    {
        $this->actingWith('patient.view');
        $visit = Visitation::factory()->create();

        $this->getJson('/api/v1/visits')->assertForbidden();
        $this->getJson("/api/v1/visits/{$visit->id}")->assertForbidden();
    }

    public function test_visits_cannot_be_edited_or_deleted(): void
    {
        $this->actingWith('visit.view', 'visit.create', 'visit.update');
        $visit = Visitation::factory()->create();

        $this->patchJson("/api/v1/visits/{$visit->id}", ['priority' => 'urgent'])->assertMethodNotAllowed();
        $this->deleteJson("/api/v1/visits/{$visit->id}")->assertMethodNotAllowed();
    }

    public function test_lists_checkpoints_in_order(): void
    {
        $this->actingWith('visit.view');

        $this->getJson('/api/v1/checkpoints')
            ->assertOk()
            ->assertJsonCount(count(CheckpointSeeder::CHECKPOINTS), 'data')
            ->assertJsonPath('data.0.code', 'reception');
    }
}
