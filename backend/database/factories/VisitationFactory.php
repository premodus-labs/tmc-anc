<?php

namespace Database\Factories;

use App\Models\AppUser;
use App\Models\Checkpoint;
use App\Models\Patient;
use App\Models\Visitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An open visit sitting at a checkpoint, with its matching open log row.
 * Needs checkpoints seeded (CheckpointSeeder) unless ->at() is given one.
 *
 * @extends Factory<Visitation>
 */
class VisitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_type' => 'general',
            'priority' => 'routine',
            'patient_consent' => true,
            'status' => Visitation::STATUS_OPEN,
            'current_checkpoint_id' => fn () => Checkpoint::entry()->id,
            'arrived_at' => now(),
            'registered_by' => AppUser::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Visitation $visit) {
            if ($visit->isOpen() && $visit->current_checkpoint_id) {
                $visit->checkpointLog()->create([
                    'checkpoint_id' => $visit->current_checkpoint_id,
                    'entered_at' => $visit->arrived_at,
                ]);
            }
        });
    }

    public function at(Checkpoint|string $checkpoint): static
    {
        return $this->state(fn () => [
            'current_checkpoint_id' => is_string($checkpoint)
                ? Checkpoint::where('code', $checkpoint)->firstOrFail()->id
                : $checkpoint->id,
        ]);
    }

    public function closed(string $status = Visitation::STATUS_COMPLETED): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'current_checkpoint_id' => null,
            'closed_at' => now(),
        ]);
    }
}
