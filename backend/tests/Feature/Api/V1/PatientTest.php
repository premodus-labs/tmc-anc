<?php

namespace Tests\Feature\Api\V1;

use App\Models\AppUser;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private function actingWith(string ...$permissions): AppUser
    {
        $user = AppUser::factory()->withPermissions(...$permissions)->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_lists_patients_with_pagination(): void
    {
        $this->actingWith('patient.view');
        Patient::factory(3)->create();

        $this->getJson('/api/v1/patients?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'patient_number', 'first_name', 'last_name']], 'links', 'meta']);
    }

    public function test_search_filters_by_name(): void
    {
        $this->actingWith('patient.view');
        Patient::factory()->create(['last_name' => 'Banda']);
        Patient::factory()->create(['last_name' => 'Phiri']);

        $this->getJson('/api/v1/patients?search=band')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_name', 'Banda');
    }

    public function test_listing_requires_permission(): void
    {
        $this->actingWith();

        $this->getJson('/api/v1/patients')->assertForbidden();
    }

    public function test_registers_a_patient(): void
    {
        $user = $this->actingWith('patient.create');

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Jane',
            'last_name' => 'Banda',
            'sex' => 'female',
            'date_of_birth' => '1998-04-12',
        ])
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.date_of_birth', '1998-04-12')
            ->assertJsonPath('data.dob_estimated', false);

        $patient = Patient::sole();
        $this->assertSame($user->id, $patient->created_by);
        $this->assertNotEmpty($patient->patient_number);
    }

    public function test_registration_validates_input(): void
    {
        $this->actingWith('patient.create');
        Patient::factory()->create(['national_id' => 'AB123456']);

        $this->postJson('/api/v1/patients', [
            'sex' => 'unknown',
            'date_of_birth' => now()->addDay()->toDateString(),
            'national_id' => 'AB123456',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'sex', 'date_of_birth', 'national_id']);
    }

    public function test_client_cannot_set_server_owned_fields(): void
    {
        $this->actingWith('patient.create');

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Jane',
            'last_name' => 'Banda',
            'sex' => 'female',
            'patient_number' => 'HACKED',
            'created_by' => 999,
        ])->assertCreated();

        $this->assertNotSame('HACKED', Patient::sole()->patient_number);
    }

    public function test_registration_requires_permission(): void
    {
        $this->actingWith('patient.view');

        $this->postJson('/api/v1/patients', ['first_name' => 'Jane'])->assertForbidden();
    }

    public function test_shows_a_patient(): void
    {
        $this->actingWith('patient.view');
        $patient = Patient::factory()->create();

        $this->getJson("/api/v1/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('data.patient_number', $patient->patient_number);
    }

    public function test_missing_patient_returns_json_404(): void
    {
        $this->actingWith('patient.view');

        $this->getJson('/api/v1/patients/999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_updates_only_the_fields_sent(): void
    {
        $this->actingWith('patient.update');
        $patient = Patient::factory()->create(['first_name' => 'Jane', 'address' => 'Area 47']);

        $this->patchJson("/api/v1/patients/{$patient->id}", ['address' => 'Area 18'])
            ->assertOk()
            ->assertJsonPath('data.address', 'Area 18')
            ->assertJsonPath('data.first_name', 'Jane');
    }

    public function test_update_allows_keeping_own_national_id(): void
    {
        $this->actingWith('patient.update');
        $patient = Patient::factory()->create(['national_id' => 'AB123456']);

        $this->patchJson("/api/v1/patients/{$patient->id}", ['national_id' => 'AB123456'])->assertOk();
    }

    public function test_update_requires_permission(): void
    {
        $this->actingWith('patient.view');
        $patient = Patient::factory()->create();

        $this->patchJson("/api/v1/patients/{$patient->id}", ['address' => 'x'])->assertForbidden();
    }

    public function test_patients_cannot_be_deleted(): void
    {
        $this->actingWith('patient.view', 'patient.create', 'patient.update');
        $patient = Patient::factory()->create();

        $this->deleteJson("/api/v1/patients/{$patient->id}")->assertMethodNotAllowed();
    }
}
